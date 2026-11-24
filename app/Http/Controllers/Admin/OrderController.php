<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderUpdateRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $orders = $this->filteredQuery($filters)
            ->with('customer:id,name')
            ->withCount('items')
            ->latest()
            ->paginate(config('store.admin_per_page'))
            ->withQueryString();

        $counts = Order::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.orders.index', compact('orders', 'filters', 'counts'));
    }

    public function show(Order $order): View
    {
        $order->load(['items.product', 'customer', 'statusHistories.user', 'coupon', 'deliveryZone']);
        $customerOrders = Order::query()->where('customer_id', $order->customer_id)->count();

        return view('admin.orders.show', compact('order', 'customerOrders'));
    }

    public function updateStatus(OrderUpdateRequest $request, Order $order): RedirectResponse
    {
        $data = $request->validated();
        $this->orders->changeStatus($order, OrderStatus::from($data['status']), $data['comment'] ?? null, $request->user(), (bool) ($data['visible_to_customer'] ?? true));

        $message = 'Status updated to '.$order->fresh()->status->label().'.';
        if ($order->fresh()->status === OrderStatus::Cancelled) {
            $message .= ' Stock has been returned.';
        }

        return back()->with('success', $message.' Use "Send status on WhatsApp" to let the customer know.');
    }

    public function updatePayment(OrderUpdateRequest $request, Order $order): RedirectResponse
    {
        $data = $request->validated();
        $changes = [
            'payment_status' => PaymentStatus::from($data['payment_status']),
            'payment_reference' => $data['payment_reference'] ?? $order->payment_reference,
        ];

        // Confirm the courier charge for outstation orders that were "to be confirmed".
        if ($order->delivery_charge_pending && isset($data['delivery_charge']) && $data['delivery_charge'] !== null) {
            $charge = (float) $data['delivery_charge'];
            $changes += [
                'delivery_charge' => $charge,
                'delivery_charge_pending' => false,
                'total' => round((float) $order->subtotal - (float) $order->discount_amount + $charge, 2),
            ];
        }

        $order->update($changes);

        $order->statusHistories()->create([
            'from_status' => $order->status->value,
            'to_status' => $order->status->value,
            'comment' => 'Payment marked '.strtolower($changes['payment_status']->label()).'.'
                .(isset($changes['delivery_charge']) ? ' Courier charge set to '.money($changes['delivery_charge']).'.' : ''),
            'visible_to_customer' => false,
            'user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Payment details saved.');
    }

    public function updateShipping(OrderUpdateRequest $request, Order $order): RedirectResponse
    {
        $order->update($request->validated());

        return back()->with('success', 'Delivery details saved. They now appear on the customer tracking page.');
    }

    public function updateNotes(OrderUpdateRequest $request, Order $order): RedirectResponse
    {
        $order->update($request->validated());

        return back()->with('success', 'Internal notes saved.');
    }

    public function invoice(Order $order): View
    {
        $order->load(['items', 'customer']);

        return view('admin.orders.invoice', compact('order'));
    }

    /** Payment screenshots live on the private disk and are only streamed to signed-in staff. */
    public function paymentScreenshot(Order $order)
    {
        abort_unless($order->payment_screenshot && Storage::disk('local')->exists($order->payment_screenshot), 404);

        return Storage::disk('local')->response($order->payment_screenshot);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->filteredQuery($this->filters($request))->with(['customer', 'items'])->latest();
        $filename = 'orders-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows ₹ and Bengali names correctly
            fputcsv($out, ['Order number', 'Date', 'Status', 'Customer', 'Mobile', 'Email', 'Items', 'Subtotal', 'Discount', 'Coupon',
                'Delivery charge', 'Total', 'Payment method', 'Payment status', 'Payment reference', 'Delivery method',
                'Address', 'City', 'State', 'PIN', 'Expected delivery', 'Courier', 'Tracking number', 'Source']);

            $query->chunk(200, function ($orders) use ($out) {
                foreach ($orders as $o) {
                    fputcsv($out, [
                        $o->order_number, $o->created_at->format('Y-m-d H:i'), $o->status->label(),
                        $o->recipient_name, $o->recipient_mobile, $o->customer?->email,
                        $o->items->map(fn ($i) => $i->product_name.' x'.$i->quantity)->implode('; '),
                        $o->subtotal, $o->discount_amount, $o->coupon_code,
                        $o->delivery_charge_pending ? 'To be confirmed' : $o->delivery_charge, $o->total,
                        $o->payment_method_label, $o->payment_status->label(), $o->payment_reference,
                        $o->delivery_method_label, $o->address_line, $o->city, $o->state, $o->pincode,
                        $o->expected_delivery_date?->format('Y-m-d'), $o->courier_name, $o->tracking_number, $o->source,
                    ]);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', 'string', 'max:30'],
            'payment' => ['nullable', 'string', 'max:20'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);
    }

    private function filteredQuery(array $filters): Builder
    {
        return Order::query()
            ->when($filters['q'] ?? null, function (Builder $q, string $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $digits = preg_replace('/\D/', '', $term);
                $q->where(function (Builder $w) use ($like, $digits) {
                    $w->where('order_number', 'like', $like)
                        ->orWhere('recipient_name', 'like', $like)
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $like));
                    if (strlen($digits) >= 4) {
                        $w->orWhere('recipient_mobile', 'like', '%'.$digits.'%');
                    }
                });
            })
            ->when(OrderStatus::tryFrom((string) ($filters['status'] ?? '')), fn ($q, $s) => $q->where('status', $s->value))
            ->when(PaymentStatus::tryFrom((string) ($filters['payment'] ?? '')), fn ($q, $s) => $q->where('payment_status', $s->value))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d));
    }
}
