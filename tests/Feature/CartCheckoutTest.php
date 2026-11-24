<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CartCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedStore();
        $this->travelTo(now()->setTimezone('Asia/Kolkata')->setTime(10, 0)->next('Monday')); // before same-day cut-off
    }

    private function product(string $slug): Product
    {
        return Product::query()->where('slug', $slug)->firstOrFail();
    }

    private function checkoutData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Riya Sen', 'mobile' => '+91 98300 12345', 'email' => 'riya@example.com',
            'address' => '12B Lake Road, 2nd floor', 'city' => 'Kolkata', 'state' => 'West Bengal',
            'pincode' => '700029', 'delivery_method' => 'local', 'payment_method' => 'upi', 'accept_terms' => '1',
        ], $overrides);
    }

    private function setSetting(string $key, ?string $value): void
    {
        Setting::query()->updateOrCreate(['key' => $key], ['group' => 'test', 'value' => $value]);
        app(SettingService::class)->flush();
    }

    public function test_add_to_cart_with_fetch_returns_count(): void
    {
        $bar = $this->product('ruby-rose-bar');

        $this->postJson('/cart', ['product_id' => $bar->id, 'quantity' => 2])
            ->assertOk()->assertJson(['ok' => true, 'count' => 2]);

        $this->get('/cart')->assertOk()->assertSee('Ruby Rose Bar')->assertSee('₹598');
    }

    public function test_quantity_is_capped_at_stock_and_sold_out_items_are_refused(): void
    {
        $low = $this->product('almond-sea-salt-dark-bar'); // 3 in stock
        $this->postJson('/cart', ['product_id' => $low->id, 'quantity' => 10])->assertOk()->assertJson(['quantity' => 3]);

        $soldOut = $this->product('coffee-white-chocolate-bar');
        $this->postJson('/cart', ['product_id' => $soldOut->id])->assertStatus(422)->assertJson(['ok' => false]);
    }

    public function test_buy_now_goes_to_checkout(): void
    {
        $this->post('/cart', ['product_id' => $this->product('ruby-rose-bar')->id, 'quantity' => 1, 'buy_now' => 1])
            ->assertRedirect('/checkout');
        $this->get('/checkout')->assertOk()->assertSee('Place order');
    }

    public function test_update_and_remove_cart_lines(): void
    {
        $bar = $this->product('ruby-rose-bar');
        $this->post('/cart', ['product_id' => $bar->id, 'quantity' => 1]);

        $this->patch('/cart/'.$bar->id, ['quantity' => 3])->assertRedirect('/cart');
        $this->get('/cart')->assertSee('₹897');

        $this->delete('/cart/'.$bar->id)->assertRedirect('/cart');
        $this->get('/cart')->assertSee('Your cart is empty');
    }

    public function test_coupon_applies_and_validates_minimum(): void
    {
        Coupon::query()->where('code', 'WELCOME10')->update(['is_active' => true]);
        $box = $this->product('signature-assorted-box'); // 1250

        $this->post('/cart', ['product_id' => $this->product('petite-thank-you-box')->id]);
        $this->post('/cart/coupon', ['code' => 'welcome10'])->assertSessionHasErrors('code');
        $this->get('/cart'); // consume the flashed error

        $this->post('/cart', ['product_id' => $box->id]);
        $this->post('/cart/coupon', ['code' => 'welcome10'])->assertSessionHasNoErrors();
        $this->get('/cart')->assertSee('WELCOME10')->assertSee('−₹149'); // 10% of 1490
    }

    public function test_delivery_check_for_local_outstation_and_unknown_pin(): void
    {
        $this->post('/cart', ['product_id' => $this->product('ruby-rose-bar')->id]);

        $this->postJson('/delivery/check', ['method' => 'local', 'pincode' => '700029'])->assertOk()
            ->assertJsonPath('quote.ok', true)->assertJsonPath('quote.charge', 60)
            ->assertJsonPath('quote.same_day', true)->assertJsonPath('summary.total', '₹359');

        $this->postJson('/delivery/check', ['method' => 'local', 'pincode' => '110001'])->assertOk()
            ->assertJsonPath('quote.ok', false);

        $this->postJson('/delivery/check', ['method' => 'outstation', 'pincode' => '110001'])->assertOk()
            ->assertJsonPath('quote.ok', true)->assertJsonPath('quote.pending', true)
            ->assertJsonPath('payment_methods.upi', 'UPI')->assertJsonMissingPath('payment_methods.cod');

        $this->postJson('/delivery/check', ['method' => 'local', 'pincode' => '12'])->assertJsonPath('quote.ok', false);
    }

    public function test_after_cutoff_local_delivery_moves_to_next_day(): void
    {
        $this->travelTo(now()->setTime(15, 30));
        $this->post('/cart', ['product_id' => $this->product('ruby-rose-bar')->id]);

        $this->postJson('/delivery/check', ['method' => 'local', 'pincode' => '700029'])
            ->assertJsonPath('quote.same_day', false)
            ->assertJsonPath('quote.date', now()->addDay()->toDateString());
    }

    public function test_free_delivery_above_zone_threshold(): void
    {
        $this->post('/cart', ['product_id' => $this->product('festive-hamper')->id]); // 2150 > 1500
        $this->postJson('/delivery/check', ['method' => 'local', 'pincode' => '700029'])
            ->assertJsonPath('quote.charge', 0)->assertJsonPath('quote.charge_label', 'Free');
    }

    public function test_checkout_places_order_reduces_stock_and_clears_cart(): void
    {
        $bar = $this->product('ruby-rose-bar'); // 18 in stock, 299
        $this->post('/cart', ['product_id' => $bar->id, 'quantity' => 2]);

        $response = $this->post('/checkout', $this->checkoutData());

        $order = Order::query()->with('items')->firstOrFail();
        $response->assertRedirect($order->confirmationUrl());

        $this->assertSame(16, $bar->fresh()->stock_quantity);
        $this->assertSame('9830012345', $order->recipient_mobile);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(PaymentStatus::Unpaid, $order->payment_status);
        $this->assertEquals(598 + 60, (float) $order->total);
        $this->assertSame('Ruby Rose Bar', $order->items->first()->product_name);
        $this->assertEquals(299, (float) $order->items->first()->unit_price);
        $this->assertSame(now()->toDateString(), $order->expected_delivery_date->toDateString());
        $this->assertDatabaseHas('customers', ['mobile' => '9830012345', 'email' => 'riya@example.com']);
        $this->assertDatabaseHas('order_status_histories', ['order_id' => $order->id, 'to_status' => 'pending']);

        $this->get('/cart')->assertSee('Your cart is empty');

        // Snapshot stays when the product changes later.
        $bar->update(['name' => 'Renamed bar', 'price' => 999]);
        $this->assertSame('Ruby Rose Bar', $order->items()->first()->product_name);
    }

    public function test_returning_customer_is_not_duplicated(): void
    {
        foreach ([1, 2] as $i) {
            $this->post('/cart', ['product_id' => $this->product('ruby-rose-bar')->id]);
            $this->post('/checkout', $this->checkoutData(['name' => 'Riya '.$i]));
        }

        $this->assertSame(1, Customer::query()->count());
        $this->assertSame(2, Order::query()->count());
        $this->assertSame('Riya 2', Customer::query()->first()->name);
    }

    public function test_checkout_fails_when_stock_ran_out_meanwhile(): void
    {
        $bar = $this->product('ruby-rose-bar');
        $this->post('/cart', ['product_id' => $bar->id, 'quantity' => 5]);
        $bar->update(['stock_quantity' => 2]); // someone else bought them

        // Cart caps the quantity before placing, so the order succeeds with 2.
        $this->post('/checkout', $this->checkoutData())->assertRedirect();
        $this->assertSame(2, Order::query()->first()->items->first()->quantity);
        $this->assertSame(0, $bar->fresh()->stock_quantity);
    }

    public function test_checkout_validation_messages(): void
    {
        $this->post('/cart', ['product_id' => $this->product('ruby-rose-bar')->id]);

        $this->from('/checkout')->post('/checkout', $this->checkoutData(['mobile' => '12345', 'pincode' => '000001', 'accept_terms' => null]))
            ->assertRedirect('/checkout')
            ->assertSessionHasErrors(['mobile', 'pincode', 'accept_terms']);

        $this->from('/checkout')->post('/checkout', $this->checkoutData(['pincode' => '110001']))
            ->assertSessionHasErrors('pincode');

        $this->assertSame(0, Order::query()->count());
    }

    public function test_cod_rules(): void
    {
        $this->post('/cart', ['product_id' => $this->product('ruby-rose-bar')->id]);
        // Outstation + COD is refused because COD is Kolkata-only by default.
        $this->post('/checkout', $this->checkoutData(['delivery_method' => 'outstation', 'pincode' => '110001', 'payment_method' => 'cod', 'city' => 'Delhi', 'state' => 'Delhi']))
            ->assertSessionHasErrors('payment_method');

        $this->post('/checkout', $this->checkoutData(['payment_method' => 'cod']))->assertSessionHasNoErrors();
        $this->assertSame('cod', Order::query()->first()->payment_method);
    }

    public function test_outstation_order_with_pending_courier_charge(): void
    {
        $this->post('/cart', ['product_id' => $this->product('ruby-rose-bar')->id]);
        $this->post('/checkout', $this->checkoutData(['delivery_method' => 'outstation', 'pincode' => '110001', 'city' => 'Delhi', 'state' => 'Delhi']));

        $order = Order::query()->firstOrFail();
        $this->assertTrue($order->delivery_charge_pending);
        $this->assertNull($order->expected_delivery_date);
        $this->assertEquals(299, (float) $order->total);
    }

    public function test_flat_courier_charge_from_settings(): void
    {
        $this->setSetting('outstation_charge', '150');
        $this->post('/cart', ['product_id' => $this->product('ruby-rose-bar')->id]);
        $this->postJson('/delivery/check', ['method' => 'outstation', 'pincode' => '560001'])
            ->assertJsonPath('quote.charge', 150)->assertJsonPath('quote.pending', false);
    }

    public function test_coupon_usage_is_counted_on_order(): void
    {
        Coupon::query()->where('code', 'WELCOME10')->update(['is_active' => true, 'usage_limit' => 1]);
        $this->post('/cart', ['product_id' => $this->product('signature-assorted-box')->id]);
        $this->post('/cart/coupon', ['code' => 'WELCOME10']);
        $this->post('/checkout', $this->checkoutData());

        $order = Order::query()->firstOrFail();
        $this->assertSame('WELCOME10', $order->coupon_code);
        $this->assertEquals(125, (float) $order->discount_amount);
        $this->assertSame(1, Coupon::query()->where('code', 'WELCOME10')->value('used_count'));

        // Fully used now.
        $this->post('/cart', ['product_id' => $this->product('signature-assorted-box')->id]);
        $this->post('/cart/coupon', ['code' => 'WELCOME10'])->assertSessionHasErrors('code');
    }

    public function test_confirmation_page_needs_signed_link_and_accepts_payment_reference(): void
    {
        Storage::fake('local');
        $this->post('/cart', ['product_id' => $this->product('ruby-rose-bar')->id]);
        $this->post('/checkout', $this->checkoutData());
        $order = Order::query()->firstOrFail();

        $this->get('/order/'.$order->order_number.'/thank-you')->assertForbidden();

        $this->get($order->confirmationUrl())->assertOk()
            ->assertSee($order->order_number)->assertSee('yourname@okbank')->assertSee('upi://pay', false)
            ->assertSee('Open WhatsApp');

        $paymentUrl = \Illuminate\Support\Facades\URL::signedRoute('orders.payment', ['order' => $order->order_number]);
        $this->from($order->confirmationUrl())->post($paymentUrl, [
            'payment_reference' => '4321 9876 5432',
            'screenshot' => UploadedFile::fake()->image('paid.jpg'),
        ])->assertSessionHasNoErrors()->assertRedirect($order->confirmationUrl());

        $order->refresh();
        $this->assertSame(PaymentStatus::Submitted, $order->payment_status);
        $this->assertSame('432198765432', $order->payment_reference);
        Storage::disk('local')->assertExists($order->payment_screenshot);
    }

    public function test_order_tracking_requires_matching_mobile(): void
    {
        $this->post('/cart', ['product_id' => $this->product('ruby-rose-bar')->id]);
        $this->post('/checkout', $this->checkoutData());
        $order = Order::query()->firstOrFail();

        // A new visitor (no session) is asked for the mobile number.
        $this->flushSession();
        $this->get('/track-order/'.$order->order_number)->assertRedirect('/track-order?order='.$order->order_number);

        $this->post('/track-order', ['order_number' => strtolower($order->order_number), 'mobile' => '9999999999'])
            ->assertSessionHasErrors('order_number');

        $this->post('/track-order', ['order_number' => $order->order_number, 'mobile' => '098300 12345'])
            ->assertRedirect('/track-order/'.$order->order_number);

        $this->get('/track-order/'.$order->order_number)->assertOk()->assertSee('Pending')->assertSee('Ruby Rose Bar');
    }

    public function test_empty_cart_cannot_check_out(): void
    {
        $this->get('/checkout')->assertRedirect('/cart');
        $this->post('/checkout', $this->checkoutData())->assertSessionHasErrors('cart');
    }
}
