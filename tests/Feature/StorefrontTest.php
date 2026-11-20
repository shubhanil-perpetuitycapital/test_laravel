<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['store.admin.password' => 'secret-pass-123', 'store.products_per_page' => 50]);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_homepage_renders_all_sections(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Shop by category')
            ->assertSee('Bestselling chocolates')
            ->assertSee('Gift boxes')
            ->assertSee('Chocolates with your words on them')
            ->assertSee('What goes into every box')
            ->assertSee('Kind words')
            ->assertSee('Rather just message us?')
            ->assertSee('https://wa.me/', false)
            ->assertSee('application/ld+json', false);
    }

    public function test_shop_lists_active_products_with_pagination(): void
    {
        $this->get('/shop')
            ->assertOk()
            ->assertSee('All chocolates')
            ->assertSee('data-result-count>15<', false);
    }

    public function test_category_page_only_shows_that_category(): void
    {
        $this->get('/shop/gift-boxes')
            ->assertOk()
            ->assertSee('Signature Assorted Box')
            ->assertDontSee('Ruby Rose Bar');
    }

    public function test_search_and_price_filters(): void
    {
        $this->get('/shop?q=ruby')->assertOk()->assertSee('Ruby Rose Bar')->assertDontSee('Classic Dark Truffles');

        // Ruby bar is discounted to 299, so it counts as under 300.
        $this->get('/shop?max_price=300')->assertOk()->assertSee('Ruby Rose Bar')->assertDontSee('Signature Assorted Box');

        $this->get('/shop?min_price=1000')->assertOk()->assertSee('Festive Hamper')->assertDontSee('Classic Milk Chocolate Bar');
    }

    public function test_in_stock_filter_hides_sold_out_products(): void
    {
        $this->get('/shop?in_stock=1')->assertOk()->assertDontSee('Coffee White Chocolate Bar');
        $this->get('/shop')->assertOk()->assertSee('Coffee White Chocolate Bar');
    }

    public function test_price_sort_orders_by_effective_price(): void
    {
        $response = $this->get('/shop?sort=price_asc')->assertOk();
        $html = $response->getContent();

        $this->assertLessThan(
            strpos($html, 'Festive Hamper'),
            strpos($html, 'Petite Thank-You Box'),
            'Cheapest product should be listed before the most expensive.'
        );
    }

    public function test_invalid_filter_values_are_ignored(): void
    {
        $this->get('/shop?sort=drop_table&min_price=abc')->assertOk()->assertSee('data-result-count>15<', false);
    }

    public function test_fetch_request_returns_json_fragment(): void
    {
        $this->getJson('/shop?type=gift')
            ->assertOk()
            ->assertJsonStructure(['html', 'count', 'title'])
            ->assertJson(['count' => 4, 'title' => 'Gift boxes']);
    }

    public function test_product_page_shows_details_and_whatsapp_order_link(): void
    {
        $this->get('/product/ruby-rose-bar')
            ->assertOk()
            ->assertSee('Ruby Rose Bar')
            ->assertSee('Ingredients')
            ->assertSee('Allergen information')
            ->assertSee('You save')
            ->assertSee('data-wa-template', false)
            ->assertSee('Order on WhatsApp');
    }

    public function test_sold_out_product_offers_back_in_stock_enquiry(): void
    {
        $this->get('/product/coffee-white-chocolate-bar')
            ->assertOk()
            ->assertSee('Sold out')
            ->assertSee("Ask when it's back", false);
    }

    public function test_inactive_products_and_categories_are_hidden(): void
    {
        Product::query()->where('slug', 'ruby-rose-bar')->update(['is_active' => false]);
        $this->get('/product/ruby-rose-bar')->assertNotFound();

        Category::query()->where('slug', 'gift-boxes')->update(['is_active' => false]);
        $this->get('/shop/gift-boxes')->assertNotFound();
        $this->get('/product/festive-hamper')->assertNotFound();
        $this->get('/shop')->assertDontSee('Festive Hamper');
    }

    public function test_information_pages_and_contact_render(): void
    {
        foreach (['about-us', 'faq', 'privacy-policy', 'terms-and-conditions', 'shipping-delivery-policy', 'cancellation-refund-policy'] as $slug) {
            $this->get('/page/'.$slug)->assertOk();
        }

        $this->get('/contact')->assertOk()->assertSee('Get in touch');
        $this->get('/page/does-not-exist')->assertNotFound()->assertSee('This page has been eaten');
    }

    public function test_sitemap_and_robots(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('/product/ruby-rose-bar', false);

        $this->get('/robots.txt')->assertOk()->assertSee('User-agent: *');
    }

    public function test_listing_paginates_at_configured_page_size(): void
    {
        config(['store.products_per_page' => 12]);

        $this->get('/shop')->assertOk()->assertSee('page=2', false);
        // Sold-out items sort last, so the sold-out bar lands on page 2.
        $this->get('/shop?page=2')->assertOk()->assertSee('Coffee White Chocolate Bar');
    }
}
