<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\CustomOrderEnquiry;
use App\Models\DeliveryPincode;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\SettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminModulesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedStore();
        $this->admin = $this->owner();
        $this->actingAs($this->admin);
    }

    private function placeOrder(int $qty = 2): Order
    {
        $bar = Product::query()->where('slug', 'ruby-rose-bar')->first();
        $this->post('/cart', ['product_id' => $bar->id, 'quantity' => $qty]);
        $this->post('/checkout', [
            'name' => 'Riya Sen', 'mobile' => '9830012345', 'address' => '12B Lake Road, 2nd floor', 'city' => 'Kolkata',
            'state' => 'West Bengal', 'pincode' => '700029', 'delivery_method' => 'local', 'payment_method' => 'upi', 'accept_terms' => 1,
        ]);

        return Order::query()->latest('id')->firstOrFail();
    }

    public function test_every_admin_screen_renders(): void
    {
        $order = $this->placeOrder();
        $product = Product::query()->first();
        $enquiry = CustomOrderEnquiry::query()->create(['enquiry_number' => 'ENQ1', 'name' => 'A', 'mobile' => '9830012345', 'quantity' => '10', 'status' => 'new']);

        $urls = [
            '/admin', '/admin/orders', '/admin/orders/'.$order->id, '/admin/orders/'.$order->id.'/invoice',
            '/admin/enquiries', '/admin/enquiries/'.$enquiry->id, '/admin/customers', '/admin/customers/'.$order->customer_id,
            '/admin/products', '/admin/products/create', '/admin/products/'.$product->id.'/edit',
            '/admin/categories', '/admin/categories/create', '/admin/coupons', '/admin/coupons/create',
            '/admin/reviews', '/admin/reviews/create', '/admin/banners', '/admin/banners/create',
            '/admin/gallery', '/admin/gallery/create', '/admin/pages', '/admin/pages/create',
            '/admin/delivery-zones', '/admin/delivery-zones/create', '/admin/delivery-zones/1/edit',
            '/admin/settings/company', '/admin/settings/store', '/admin/users', '/admin/users/create', '/admin/profile',
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_product_create_with_photos_and_edit(): void
    {
        Storage::fake('uploads');
        $category = Category::query()->first();

        $this->post('/admin/products', [
            'name' => 'Orange Peel Dark Bar', 'sku' => 'bar-orng', 'category_id' => $category->id,
            'price' => 320, 'discount_price' => '', 'stock_quantity' => 12, 'is_active' => 1,
            'images' => [UploadedFile::fake()->image('a.jpg', 1200, 1200), UploadedFile::fake()->image('b.png', 600, 600)],
        ])->assertSessionHasNoErrors();

        $product = Product::query()->where('sku', 'BAR-ORNG')->firstOrFail();
        $this->assertSame('orange-peel-dark-bar', $product->slug);
        $this->assertCount(2, $product->images);
        $this->assertSame(1, $product->images()->where('is_primary', true)->count());
        Storage::disk('uploads')->assertExists($product->images->first()->path);

        $second = $product->images()->orderBy('id')->get()[1];
        $first = $product->images()->orderBy('id')->first();
        $this->put('/admin/products/'.$product->id, [
            'name' => 'Orange Peel Dark Bar', 'sku' => 'BAR-ORNG', 'category_id' => $category->id,
            'price' => 320, 'discount_price' => 300, 'stock_quantity' => 5, 'is_active' => 1,
            'primary_image' => $second->id,
            'existing' => [$first->id => ['remove' => 1]],
        ])->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertCount(1, $product->images);
        $this->assertTrue($product->images->first()->is_primary);
        $this->assertEquals(300, (float) $product->discount_price);
        Storage::disk('uploads')->assertMissing($first->path);

        $this->get('/product/orange-peel-dark-bar')->assertOk();
    }

    public function test_product_validation(): void
    {
        $this->post('/admin/products', ['name' => '', 'sku' => 'BAR-DK70', 'price' => 100, 'discount_price' => 150, 'stock_quantity' => -1])
            ->assertSessionHasErrors(['name', 'sku', 'category_id', 'discount_price', 'stock_quantity']);
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $category = Category::query()->where('slug', 'gift-boxes')->first();
        $this->delete('/admin/categories/'.$category->id)->assertSessionHas('error');
        $this->assertNotSoftDeleted($category);

        $this->post('/admin/categories', ['name' => 'Seasonal', 'is_active' => 1])->assertSessionHasNoErrors();
        $seasonal = Category::query()->where('slug', 'seasonal')->firstOrFail();
        $this->delete('/admin/categories/'.$seasonal->id)->assertSessionHas('success');
        $this->assertSoftDeleted($seasonal);
    }

    public function test_cancelling_an_order_returns_stock_and_cannot_be_reopened(): void
    {
        $order = $this->placeOrder(3);
        $bar = Product::query()->where('slug', 'ruby-rose-bar')->first();
        $this->assertSame(15, $bar->stock_quantity);

        $this->patch('/admin/orders/'.$order->id.'/status', ['status' => 'confirmed', 'comment' => 'Payment checked', 'visible_to_customer' => 1])->assertSessionHasNoErrors();
        $this->patch('/admin/orders/'.$order->id.'/status', ['status' => 'cancelled'])->assertSessionHasNoErrors();

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(18, $bar->fresh()->stock_quantity);
        $this->assertSame(3, $order->statusHistories()->count());

        $this->patch('/admin/orders/'.$order->id.'/status', ['status' => 'confirmed'])->assertSessionHasErrors('status');
    }

    public function test_payment_shipping_and_notes_updates(): void
    {
        $order = $this->placeOrder();

        $this->patch('/admin/orders/'.$order->id.'/payment', ['payment_status' => 'paid', 'payment_reference' => 'UTR123'])->assertSessionHasNoErrors();
        $this->patch('/admin/orders/'.$order->id.'/shipping', ['courier_name' => 'Own rider', 'tracking_number' => 'R-7', 'tracking_url' => '', 'expected_delivery_date' => now()->addDay()->toDateString()])->assertSessionHasNoErrors();
        $this->patch('/admin/orders/'.$order->id.'/notes', ['internal_notes' => 'Gift wrap'])->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame('Own rider', $order->courier_name);
        $this->assertSame('Gift wrap', $order->internal_notes);
    }

    public function test_confirming_pending_courier_charge_updates_total(): void
    {
        $bar = Product::query()->where('slug', 'ruby-rose-bar')->first();
        $this->post('/cart', ['product_id' => $bar->id]);
        $this->post('/checkout', ['name' => 'Dev', 'mobile' => '9830012345', 'address' => '5 MG Road, Indiranagar', 'city' => 'Bengaluru',
            'state' => 'Karnataka', 'pincode' => '560038', 'delivery_method' => 'outstation', 'payment_method' => 'upi', 'accept_terms' => 1]);
        $order = Order::query()->firstOrFail();

        $this->patch('/admin/orders/'.$order->id.'/payment', ['payment_status' => 'unpaid', 'delivery_charge' => 180])->assertSessionHasNoErrors();
        $order->refresh();
        $this->assertFalse($order->delivery_charge_pending);
        $this->assertEquals(299 + 180, (float) $order->total);
    }

    public function test_order_search_filters_and_csv_export(): void
    {
        $order = $this->placeOrder();

        $this->get('/admin/orders?q=98300')->assertOk()->assertSee($order->order_number);
        $this->get('/admin/orders?q=nobody')->assertOk()->assertDontSee($order->order_number);
        $this->get('/admin/orders?status=delivered')->assertOk()->assertDontSee($order->order_number);
        $this->get('/admin/orders?from='.now()->toDateString())->assertOk()->assertSee($order->order_number);

        $csv = $this->get('/admin/orders/export')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8')->streamedContent();
        $this->assertStringContainsString($order->order_number, $csv);
        $this->assertStringContainsString('Ruby Rose Bar x2', $csv);
    }

    public function test_whatsapp_links_on_order_page_use_templates(): void
    {
        $order = $this->placeOrder();
        $html = $this->get('/admin/orders/'.$order->id)->assertOk()->getContent();

        $this->assertStringContainsString('https://wa.me/919830012345?text=', $html);
        $this->assertStringContainsString(rawurlencode($order->order_number), $html);
    }

    public function test_enquiry_quote_and_convert_to_order(): void
    {
        $enquiry = CustomOrderEnquiry::query()->create([
            'enquiry_number' => 'ENQ261123-AAAA', 'name' => 'Arjun', 'mobile' => '9830098300', 'quantity' => '50 bars',
            'occasion' => 'Wedding', 'status' => 'new', 'required_date' => now()->addDays(10),
        ]);

        $this->patch('/admin/enquiries/'.$enquiry->id, ['status' => 'quoted', 'quoted_amount' => 7500, 'quotation_notes' => 'Includes printed wrappers'])->assertSessionHasNoErrors();
        $this->get('/admin/enquiries/'.$enquiry->id)->assertSee('Send quote')->assertSee(rawurlencode('₹7,500.00'), false);

        $this->post('/admin/enquiries/'.$enquiry->id.'/convert', [
            'description' => 'Wedding favour bars', 'quantity' => 50, 'unit_price' => 150, 'delivery_charge' => 0,
            'delivery_method' => 'local', 'payment_method' => 'upi', 'payment_status' => 'paid',
            'address' => 'Hall 2, Park Street', 'city' => 'Kolkata', 'state' => 'West Bengal', 'pincode' => '700016',
        ])->assertRedirect();

        $enquiry->refresh();
        $this->assertSame('converted', $enquiry->status);
        $order = $enquiry->convertedOrder;
        $this->assertEquals(7500, (float) $order->total);
        $this->assertSame('enquiry', $order->source);
        $this->assertSame(OrderStatus::Confirmed, $order->status);

        $this->post('/admin/enquiries/'.$enquiry->id.'/convert', [])->assertSessionHasErrors();
    }

    public function test_delivery_zone_with_pincodes(): void
    {
        $this->post('/admin/delivery-zones', [
            'name' => 'Howrah', 'delivery_charge' => 100, 'lead_days' => 1, 'same_day_available' => 0, 'is_active' => 1,
            'pincodes' => "711101 Howrah Maidan\n711102, Shibpur\n711103 711104",
        ])->assertSessionHasNoErrors();

        $this->assertSame(4, DeliveryPincode::query()->whereIn('pincode', ['711101', '711102', '711103', '711104'])->count());
        $this->assertSame('Shibpur', DeliveryPincode::query()->where('pincode', '711102')->value('area_name'));

        // A PIN already used by another zone is rejected.
        $this->post('/admin/delivery-zones', ['name' => 'Dup', 'delivery_charge' => 1, 'lead_days' => 1, 'pincodes' => '700029'])
            ->assertSessionHasErrors('pincodes');
        // Same-day needs a cut-off time.
        $this->post('/admin/delivery-zones', ['name' => 'X', 'delivery_charge' => 1, 'lead_days' => 1, 'same_day_available' => 1, 'pincodes' => '711201'])
            ->assertSessionHasErrors('same_day_cutoff');
    }

    public function test_coupon_crud(): void
    {
        $this->post('/admin/coupons', ['code' => 'diwali 15', 'type' => 'percent', 'value' => 15, 'is_active' => 1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('coupons', ['code' => 'DIWALI15']);
        $this->post('/admin/coupons', ['code' => 'BIG', 'type' => 'percent', 'value' => 95])->assertSessionHasErrors('value');
    }

    public function test_reviews_banners_gallery_and_pages(): void
    {
        Storage::fake('uploads');

        $this->post('/admin/reviews', ['customer_name' => 'Meera', 'rating' => 5, 'comment' => 'Beautiful box', 'is_approved' => 1, 'is_featured' => 1])->assertSessionHasNoErrors();
        $this->get('/')->assertSee('Beautiful box');

        $this->post('/admin/banners', ['title' => 'Diwali boxes are here', 'image' => UploadedFile::fake()->image('b.jpg', 1000, 1000), 'is_active' => 1, 'sort_order' => 0])->assertSessionHasNoErrors();
        $this->get('/')->assertSee('Diwali boxes are here')->assertSee('heroCarousel', false);
        $this->post('/admin/banners', ['title' => 'No image'])->assertSessionHasErrors('image');

        $this->post('/admin/gallery', ['image' => UploadedFile::fake()->image('g.jpg'), 'caption' => 'Truffle tray', 'is_active' => 1, 'sort_order' => 0])->assertSessionHasNoErrors();

        $this->post('/admin/pages', ['title' => 'Corporate gifting', 'content' => '<h2>Bulk</h2><p onclick="x()">Hi</p><script>alert(1)</script>', 'is_active' => 1])->assertSessionHasNoErrors();
        $page = Page::query()->where('slug', 'corporate-gifting')->firstOrFail();
        $this->assertStringNotContainsString('script', $page->content);
        $this->assertStringNotContainsString('onclick', $page->content);
        $this->get('/page/corporate-gifting')->assertOk()->assertSee('Bulk');

        $faq = Page::query()->where('slug', 'faq')->first();
        $this->delete('/admin/pages/'.$faq->id)->assertSessionHas('error');
    }

    public function test_company_and_store_settings_flow_to_the_site(): void
    {
        $company = [];
        foreach (SettingsSchema::fields('company') as $key => $field) {
            if ($field['type'] !== 'image') {
                $company[$key] = $field['default'];
            }
        }

        $this->put('/admin/settings/company', array_merge($company, [
            'company_name' => 'Cocoa Test Kitchen', 'whatsapp_number' => '98300 22222',
            'gstin' => '19abcde1234f1z5', 'fssai_number' => '1234 5678 9012 34', 'instagram_url' => 'https://instagram.com/cocoatest',
            'logo' => UploadedFile::fake()->image('logo.png', 400, 120),
        ]))->assertSessionHasNoErrors();

        $this->assertSame('919830022222', Setting::query()->where('key', 'whatsapp_number')->value('value'));
        $this->get('/')->assertSee('Cocoa Test Kitchen')->assertSee('https://wa.me/919830022222', false)
            ->assertSee('FSSAI Lic. No. 12345678901234')->assertSee('GSTIN 19ABCDE1234F1Z5');

        $this->put('/admin/settings/company', array_merge($company, ['gstin' => 'BAD', 'company_name' => '']))
            ->assertSessionHasErrors(['gstin', 'company_name']);

        $store = [];
        foreach (SettingsSchema::fields('store') as $key => $field) {
            if ($field['type'] !== 'image') {
                $store[$key] = $field['default'];
            }
        }
        $this->put('/admin/settings/store', array_merge($store, ['upi_id' => 'mybakery@okhdfcbank', 'cod_enabled' => 0]))->assertSessionHasNoErrors();
        $this->assertSame('0', Setting::query()->where('key', 'cod_enabled')->value('value'));

        $this->put('/admin/settings/store', array_merge($store, ['upi_id' => 'not-a-upi']))->assertSessionHasErrors('upi_id');
    }

    public function test_staff_accounts(): void
    {
        $this->post('/admin/users', ['name' => 'Puja', 'email' => 'puja@example.com', 'role' => 'staff', 'is_active' => 1,
            'password' => 'kitchen12345', 'password_confirmation' => 'kitchen12345'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'puja@example.com', 'role' => 'staff']);

        $this->delete('/admin/users/'.$this->admin->id)->assertSessionHas('error');
    }
}
