<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\CustomOrderEnquiry;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    private const NUMBER_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function __construct(
        private SettingService $settings,
        private DeliveryService $delivery,
        private CouponService $coupons,
    ) {}

    /**
     * Payment methods allowed for a delivery method and order total.
     *
     * @return array<string, string> key => label
     */
    public function paymentMethods(string $deliveryMethod, float $total): array
    {
        $methods = [];

        if ($this->settings->bool('upi_enabled') && filled($this->settings->get('upi_id'))) {
            $methods['upi'] = Order::PAYMENT_METHODS['upi'];
        }

        $codMax = $this->settings->float('cod_max_amount');
        if ($this->settings->bool('cod_enabled')
            && (! $this->settings->bool('cod_local_only') || $deliveryMethod === 'local')
            && ($codMax === null || $codMax <= 0 || $total <= $codMax)) {
            $methods['cod'] = Order::PAYMENT_METHODS['cod'];
        }

        return $methods;
    }

    /**
     * Turn the session cart into an order.
     *
     * Runs in one transaction: product rows are locked, stock is checked
     * and reduced, the coupon is re-validated and its usage counted, so two
     * people buying the last box at the same moment can't both succeed.
     *
     * @param  array<int,int>  $items  product id => quantity
     * @param  array<string,mixed>  $data  validated checkout fields
     *
     * @throws ValidationException
     */
    public function placeFromCart(array $items, ?string $couponCode, array $data): Order
    {
        if ($items === []) {
            throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
        }

        return DB::transaction(function () use ($items, $couponCode, $data) {
            $products = Product::query()->with('category')->whereIn('id', array_keys($items))
                ->lockForUpdate()->get()->keyBy('id');

            $lines = [];
            $subtotal = 0.0;

            foreach ($items as $id => $qty) {
                $product = $products->get($id);

                if (! $product || ! $product->isPurchasable()) {
                    throw ValidationException::withMessages(['cart' => ($product?->name ?? 'An item in your cart').' is no longer available. Please review your cart.']);
                }
                if ($qty > $product->stock_quantity) {
                    throw ValidationException::withMessages(['cart' => "Only {$product->stock_quantity} of {$product->name} left. Please update your cart."]);
                }

                $lineTotal = round($product->effective_price * $qty, 2);
                $subtotal += $lineTotal;
                $lines[] = [$product, $qty, $lineTotal];
            }
            $subtotal = round($subtotal, 2);

            $minOrder = $this->settings->float('min_order_amount');
            if ($minOrder && $subtotal < $minOrder) {
                throw ValidationException::withMessages(['cart' => 'The minimum order is '.money($minOrder).'.']);
            }

            $coupon = $this->coupons->evaluate($couponCode, $subtotal, lock: true);
            if ($coupon['error']) {
                throw ValidationException::withMessages(['coupon' => $coupon['error'].' Remove it from your cart to continue.']);
            }
            $discount = $coupon['discount'];

            $quote = $this->delivery->quote($data['delivery_method'], $data['pincode'], $subtotal - $discount);
            if (! $quote->ok) {
                throw ValidationException::withMessages(['pincode' => $quote->error]);
            }

            $total = round($subtotal - $discount + ($quote->pending ? 0 : $quote->charge), 2);

            if (! array_key_exists($data['payment_method'], $this->paymentMethods($data['delivery_method'], $total))) {
                throw ValidationException::withMessages(['payment_method' => 'This payment method is not available for this order. Please choose another.']);
            }

            $customer = $this->upsertCustomer($data['name'], $data['mobile'], $data['email'] ?? null);

            $order = Order::query()->create([
                'order_number' => $this->generateNumber(),
                'customer_id' => $customer->id,
                'source' => 'website',
                'status' => OrderStatus::Pending,
                'payment_method' => $data['payment_method'],
                'payment_status' => PaymentStatus::Unpaid,
                'delivery_method' => $data['delivery_method'],
                'delivery_zone_id' => $quote->zone?->id,
                'recipient_name' => $data['name'],
                'recipient_mobile' => $data['mobile'],
                'address_line' => $data['address'],
                'city' => $data['city'],
                'state' => $data['state'],
                'pincode' => $data['pincode'],
                'delivery_instructions' => $data['delivery_instructions'] ?? null,
                'order_notes' => $data['order_notes'] ?? null,
                'expected_delivery_date' => $quote->date?->toDateString(),
                'delivery_estimate_text' => $quote->estimate,
                'subtotal' => $subtotal,
                'coupon_id' => $coupon['coupon']?->id,
                'coupon_code' => $coupon['coupon']?->code,
                'discount_amount' => $discount,
                'delivery_charge' => $quote->pending ? 0 : $quote->charge,
                'delivery_charge_pending' => $quote->pending,
                'total' => $total,
            ]);

            foreach ($lines as [$product, $qty, $lineTotal]) {
                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'weight' => $product->weight,
                    'unit_price' => $product->effective_price,
                    'quantity' => $qty,
                    'line_total' => $lineTotal,
                ]);
                $product->decrement('stock_quantity', $qty);
            }

            $coupon['coupon']?->increment('used_count');

            $order->statusHistories()->create([
                'to_status' => OrderStatus::Pending->value,
                'comment' => 'Order placed on the website.',
            ]);

            return $order;
        });
    }

    /**
     * Create an order from a custom enquiry (admin). No stock is touched:
     * custom work is made to order.
     */
    public function createFromEnquiry(CustomOrderEnquiry $enquiry, array $data, User $user): Order
    {
        return DB::transaction(function () use ($enquiry, $data, $user) {
            $customer = $this->upsertCustomer($enquiry->name, $enquiry->mobile, $enquiry->email);
            $subtotal = round((float) $data['unit_price'] * (int) $data['quantity'], 2);
            $charge = (float) ($data['delivery_charge'] ?? 0);

            $order = Order::query()->create([
                'order_number' => $this->generateNumber(),
                'customer_id' => $customer->id,
                'source' => 'enquiry',
                'status' => OrderStatus::Confirmed,
                'payment_method' => $data['payment_method'],
                'payment_status' => PaymentStatus::from($data['payment_status']),
                'delivery_method' => $data['delivery_method'],
                'recipient_name' => $enquiry->name,
                'recipient_mobile' => $enquiry->mobile,
                'address_line' => $data['address'],
                'city' => $data['city'],
                'state' => $data['state'],
                'pincode' => $data['pincode'],
                'expected_delivery_date' => $data['expected_delivery_date'] ?? $enquiry->required_date,
                'order_notes' => $enquiry->message,
                'subtotal' => $subtotal,
                'delivery_charge' => $charge,
                'total' => $subtotal + $charge,
                'internal_notes' => 'Created from enquiry '.$enquiry->enquiry_number.'.',
            ]);

            $order->items()->create([
                'product_name' => $data['description'],
                'unit_price' => $data['unit_price'],
                'quantity' => $data['quantity'],
                'line_total' => $subtotal,
            ]);

            $order->statusHistories()->create([
                'to_status' => OrderStatus::Confirmed->value,
                'comment' => 'Created from custom enquiry '.$enquiry->enquiry_number.'.',
                'user_id' => $user->id,
            ]);

            $enquiry->update(['status' => 'converted', 'converted_order_id' => $order->id, 'customer_id' => $customer->id]);

            return $order;
        });
    }

    /**
     * Change status with an audit entry. Cancelling returns stock;
     * cancelled orders can't be reopened (stock may already be resold).
     *
     * @throws ValidationException
     */
    public function changeStatus(Order $order, OrderStatus $to, ?string $comment, ?User $user, bool $visibleToCustomer = true): void
    {
        $from = $order->status;

        if ($from === OrderStatus::Cancelled && $to !== OrderStatus::Cancelled) {
            throw ValidationException::withMessages(['status' => 'Cancelled orders cannot be reopened. Create a new order instead.']);
        }

        if ($from === $to && blank($comment)) {
            return;
        }

        DB::transaction(function () use ($order, $from, $to, $comment, $user, $visibleToCustomer) {
            if ($to === OrderStatus::Cancelled && $from !== OrderStatus::Cancelled) {
                foreach ($order->items()->get() as $item) {
                    if ($item->product_id) {
                        Product::withTrashed()->whereKey($item->product_id)->increment('stock_quantity', $item->quantity);
                    }
                }
            }

            $order->update(['status' => $to]);

            $order->statusHistories()->create([
                'from_status' => $from->value,
                'to_status' => $to->value,
                'comment' => $comment,
                'visible_to_customer' => $visibleToCustomer,
                'user_id' => $user?->id,
            ]);
        });
    }

    public function upsertCustomer(string $name, string $mobile, ?string $email): Customer
    {
        $customer = Customer::withTrashed()->firstOrNew(['mobile' => $mobile]);
        $customer->name = $name;
        if (filled($email)) {
            $customer->email = $email;
        }
        if ($customer->trashed()) {
            $customer->restore();
        }
        $customer->save();

        return $customer;
    }

    /** e.g. ORD241123-K7QX — date + 4 unambiguous characters. */
    public function generateNumber(string $prefix = 'ORD'): string
    {
        do {
            $suffix = '';
            for ($i = 0; $i < 4; $i++) {
                $suffix .= self::NUMBER_ALPHABET[random_int(0, strlen(self::NUMBER_ALPHABET) - 1)];
            }
            $number = $prefix.now()->format('ymd').'-'.$suffix;
        } while (
            $prefix === 'ORD'
                ? Order::withTrashed()->where('order_number', $number)->exists()
                : CustomOrderEnquiry::withTrashed()->where('enquiry_number', $number)->exists()
        );

        return $number;
    }
}
