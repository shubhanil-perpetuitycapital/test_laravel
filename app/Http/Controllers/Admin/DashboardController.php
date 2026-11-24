<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomOrderEnquiry;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Services\SettingService;
use App\Support\SettingsSchema;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(SettingService $settings): View
    {
        $monthStart = now()->startOfMonth();

        $stats = [
            'orders_total' => Order::query()->count(),
            'orders_today' => Order::query()->whereDate('created_at', today())->count(),
            'orders_pending' => Order::query()->where('status', OrderStatus::Pending)->count(),
            'orders_completed' => Order::query()->where('status', OrderStatus::Delivered)->count(),
            'payments_to_verify' => Order::query()->where('payment_status', PaymentStatus::Submitted)->count(),
            'customers' => Customer::query()->count(),
            'products' => Product::query()->count(),
            'out_of_stock' => Product::query()->where('is_active', true)->where('stock_quantity', 0)->count(),
            'enquiries_new' => CustomOrderEnquiry::query()->where('status', 'new')->count(),
            'reviews_pending' => Review::query()->where('is_approved', false)->count(),
            'revenue_month' => (float) Order::query()->revenue()->where('created_at', '>=', $monthStart)->sum('total'),
            'orders_month' => Order::query()->revenue()->where('created_at', '>=', $monthStart)->count(),
        ];

        $chart = $this->monthlyChart();

        $recentOrders = Order::query()->latest()->limit(8)->get();
        $lowStock = Product::query()->where('is_active', true)->where('stock_quantity', '<=', Product::LOW_STOCK_THRESHOLD)
            ->orderBy('stock_quantity')->limit(6)->get(['id', 'name', 'stock_quantity']);

        $checklist = $this->checklist($settings);

        return view('admin.dashboard', compact('stats', 'chart', 'recentOrders', 'lowStock', 'checklist'));
    }

    /**
     * Orders and revenue for the last 12 months. Aggregated in PHP so the
     * same code works on MySQL and SQLite; volumes for a single shop are small.
     */
    private function monthlyChart(): array
    {
        $start = now()->startOfMonth()->subMonths(11);

        $orders = Order::query()->revenue()->where('created_at', '>=', $start)->get(['created_at', 'total']);
        $grouped = $orders->groupBy(fn (Order $o) => $o->created_at->format('Y-m'));

        $labels = $counts = $revenue = [];
        for ($i = 0; $i < 12; $i++) {
            /** @var Carbon $month */
            $month = $start->copy()->addMonths($i);
            $key = $month->format('Y-m');
            $labels[] = $month->format('M y');
            $counts[] = $grouped->has($key) ? $grouped[$key]->count() : 0;
            $revenue[] = $grouped->has($key) ? round((float) $grouped[$key]->sum('total'), 2) : 0;
        }

        return compact('labels', 'counts', 'revenue');
    }

    private function checklist(SettingService $settings): array
    {
        $defaults = SettingsSchema::defaults();

        return [
            ['label' => 'Brand name', 'done' => $settings->get('company_name') !== $defaults['company_name']],
            ['label' => 'Logo', 'done' => filled($settings->raw('logo'))],
            ['label' => 'WhatsApp number', 'done' => $settings->get('whatsapp_number') !== $defaults['whatsapp_number']],
            ['label' => 'Business address', 'done' => $settings->get('address_line1') !== $defaults['address_line1']],
            ['label' => 'UPI ID', 'done' => $settings->get('upi_id') !== $defaults['upi_id']],
            ['label' => 'FSSAI number', 'done' => filled($settings->get('fssai_number'))],
        ];
    }
}
