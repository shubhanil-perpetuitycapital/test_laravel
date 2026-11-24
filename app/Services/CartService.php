<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;

/**
 * Session cart. Stores only product IDs and quantities; prices, names and
 * stock always come fresh from the database, so a cart can never hold a
 * stale price.
 */
class CartService
{
    private const KEY = 'cart';

    /** @var Collection<int, array>|null */
    private ?Collection $linesMemo = null;

    /** Cart contents the memo was built from, so a stale memo is never reused. */
    private ?string $memoKey = null;

    /** @var array<int, string> messages about items changed since they were added */
    private array $notices = [];

    public function __construct(
        private Session $session,
        private CouponService $coupons,
        private DeliveryService $delivery,
    ) {}

    // ---- Mutations ------------------------------------------------------

    /**
     * @return array{ok: bool, message: string, quantity: int}
     */
    public function add(Product $product, int $quantity = 1): array
    {
        $product->loadMissing('category');

        if (! $product->isPurchasable()) {
            return ['ok' => false, 'message' => $product->name.' is sold out right now.', 'quantity' => 0];
        }

        $items = $this->items();
        $current = $items[$product->id] ?? 0;
        $wanted = $current + max(1, $quantity);
        $final = min($wanted, $product->stock_quantity);

        $items[$product->id] = $final;
        $this->putItems($items);

        if ($final < $wanted) {
            return ['ok' => true, 'quantity' => $final, 'message' => "Only {$product->stock_quantity} of {$product->name} available — your cart has the maximum."];
        }

        return ['ok' => true, 'quantity' => $final, 'message' => $product->name.' added to your cart.'];
    }

    public function update(int $productId, int $quantity): void
    {
        $items = $this->items();

        if ($quantity <= 0) {
            unset($items[$productId]);
        } elseif (isset($items[$productId])) {
            $stock = (int) Product::query()->whereKey($productId)->value('stock_quantity');
            $items[$productId] = max(1, min($quantity, $stock));
        }

        $this->putItems($items);
    }

    public function remove(int $productId): void
    {
        $items = $this->items();
        unset($items[$productId]);
        $this->putItems($items);
    }

    public function clear(): void
    {
        $this->session->forget(self::KEY);
        $this->linesMemo = null;
    }

    public function applyCoupon(?string $code): void
    {
        $this->session->put(self::KEY.'.coupon', $code ? strtoupper(trim($code)) : null);
    }

    public function couponCode(): ?string
    {
        return $this->session->get(self::KEY.'.coupon');
    }

    /** Remember the PIN and method the customer checked, to prefill checkout. */
    public function rememberDelivery(string $method, string $pincode): void
    {
        $this->session->put(self::KEY.'.delivery', ['method' => $method, 'pincode' => $pincode]);
    }

    /** @return array{method?: string, pincode?: string} */
    public function rememberedDelivery(): array
    {
        return $this->session->get(self::KEY.'.delivery', []);
    }

    // ---- Reads ----------------------------------------------------------

    /** @return array<int, int> product id => quantity */
    public function items(): array
    {
        return array_map('intval', $this->session->get(self::KEY.'.items', []));
    }

    public function count(): int
    {
        return array_sum($this->items());
    }

    public function isEmpty(): bool
    {
        return $this->items() === [];
    }

    /**
     * Current lines with fresh prices. Removes products that were disabled
     * or deleted and lowers quantities above the available stock.
     *
     * @return Collection<int, array{product: Product, quantity: int, unit_price: float, line_total: float}>
     */
    public function lines(): Collection
    {
        $items = $this->items();
        $key = md5(json_encode($items));

        if ($this->linesMemo !== null && $this->memoKey === $key) {
            return $this->linesMemo;
        }

        $this->notices = [];
        $this->memoKey = $key;

        if ($items === []) {
            return $this->linesMemo = collect();
        }

        $products = Product::query()->with(['primaryImage', 'category'])->whereIn('id', array_keys($items))->get()->keyBy('id');
        $lines = collect();
        $changed = false;

        foreach ($items as $id => $qty) {
            $product = $products->get($id);

            if (! $product || ! $product->isPurchasable()) {
                $this->notices[] = ($product?->name ?? 'An item').' is no longer available and was removed from your cart.';
                unset($items[$id]);
                $changed = true;

                continue;
            }

            if ($qty > $product->stock_quantity) {
                $this->notices[] = "Only {$product->stock_quantity} of {$product->name} left, so we updated the quantity.";
                $qty = $items[$id] = $product->stock_quantity;
                $changed = true;
            }

            $lines->push([
                'product' => $product,
                'quantity' => $qty,
                'unit_price' => $product->effective_price,
                'line_total' => round($product->effective_price * $qty, 2),
            ]);
        }

        if ($changed) {
            $this->session->put(self::KEY.'.items', $items);
            $this->memoKey = md5(json_encode(array_map('intval', $items)));
        }

        return $this->linesMemo = $lines;
    }

    /** @return array<int, string> */
    public function notices(): array
    {
        return $this->notices;
    }

    /**
     * Totals for the cart page and checkout.
     *
     * @return array{subtotal: float, discount: float, coupon: ?\App\Models\Coupon, coupon_error: ?string,
     *               delivery: ?DeliveryQuote, delivery_charge: float, total: float, count: int}
     */
    public function summary(?DeliveryQuote $quote = null): array
    {
        $lines = $this->lines();
        $subtotal = round($lines->sum('line_total'), 2);

        $couponResult = $this->coupons->evaluate($this->couponCode(), $subtotal);
        $discount = $couponResult['discount'];

        $deliveryCharge = $quote && $quote->ok && ! $quote->pending ? $quote->charge : 0.0;

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'coupon' => $couponResult['error'] ? null : $couponResult['coupon'],
            'coupon_error' => $couponResult['error'],
            'delivery' => $quote,
            'delivery_charge' => $deliveryCharge,
            'total' => round(max(0, $subtotal - $discount) + $deliveryCharge, 2),
            'count' => (int) $lines->sum('quantity'),
        ];
    }

    /** Quote for the remembered PIN, if the customer has checked one. */
    public function rememberedQuote(float $amount): ?DeliveryQuote
    {
        $remembered = $this->rememberedDelivery();

        if (empty($remembered['pincode'])) {
            return null;
        }

        return $this->delivery->quote($remembered['method'] ?? 'local', $remembered['pincode'], $amount);
    }

    private function putItems(array $items): void
    {
        $this->session->put(self::KEY.'.items', $items);
        $this->linesMemo = null;
    }
}
