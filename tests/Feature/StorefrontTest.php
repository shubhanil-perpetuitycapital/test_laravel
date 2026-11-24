<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedStore();
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
            ->assertSee('application/ld+json', false)
            ->assertSee('data-add-to-cart', false);
    }

    public function test_shop_lists_and_filters_products(): void
    {
        $this->get('/shop')->assertOk()->assertSee('data-result-count>15<', false);
        $this->get('/shop/gift-boxes')->assertOk()->assertSee('Signature Assorted Box')->assertDontSee('Ruby Rose Bar');
        $this->get('/shop?q=ruby')->assertOk()->assertSee('Ruby Rose Bar')->assertDontSee('Classic Dark Truffles');
        $this->get('/shop?max_price=300')->assertOk()->assertSee('Ruby Rose Bar')->assertDontSee('Signature Assorted Box');
        $this->get('/shop?min_price=1000')->assertOk()->assertSee('Festive Hamper')->assertDontSee('Classic Milk Chocolate Bar');
        $this->get('/shop?in_stock=1')->assertOk()->assertDontSee('Coffee White Chocolate Bar');
        $this->get('/shop?sort=drop_table&min_price=abc')->assertOk()->assertSee('data-result-count>15<', false);
    }

    public function test_price_sort_orders_by_effective_price(): void
    {
        $html = $this->get('/shop?sort=price_asc')->assertOk()->getContent();
        $this->assertLessThan(strpos($html, 'Festive Hamper'), strpos($html, 'Petite Thank-You Box'));
    }

    public function test_listing_paginates(): void
    {
        config(['store.products_per_page' => 12]);
        $this->get('/shop')->assertOk()->assertSee('page=2', false);
        $this->get('/shop?page=2')->assertOk()->assertSee('Coffee White Chocolate Bar');
    }

    public function test_fetch_request_returns_json_fragment(): void
    {
        $this->getJson('/shop?type=gift')->assertOk()
            ->assertJsonStructure(['html', 'count', 'title'])
            ->assertJson(['count' => 4, 'title' => 'Gift boxes']);
    }

    public function test_product_page_shows_details_and_buy_buttons(): void
    {
        $this->get('/product/ruby-rose-bar')->assertOk()
            ->assertSee('Ruby Rose Bar')->assertSee('Ingredients')->assertSee('Allergen information')
            ->assertSee('You save')->assertSee('Add to cart')->assertSee('Buy now')
            ->assertSee('Ask about this on WhatsApp')->assertSee('Write a review');
    }

    public function test_sold_out_product_offers_back_in_stock_enquiry(): void
    {
        $this->get('/product/coffee-white-chocolate-bar')->assertOk()
            ->assertSee('Sold out')->assertSee("Ask when it's back", false)->assertDontSee('Buy now');
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

    public function test_customer_review_waits_for_approval(): void
    {
        $this->post('/product/ruby-rose-bar/reviews', ['customer_name' => 'Asha', 'rating' => 5, 'comment' => 'Loved the rose petals on top.'])
            ->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('reviews', ['customer_name' => 'Asha', 'is_approved' => false]);
        $this->get('/product/ruby-rose-bar')->assertDontSee('Loved the rose petals');

        $this->post('/product/ruby-rose-bar/reviews', ['customer_name' => 'Bot', 'rating' => 5, 'comment' => 'Spam spam spam spam', 'website' => 'x'])
            ->assertSessionHasErrors('website');
    }

    public function test_information_pages_contact_and_seo_files(): void
    {
        foreach (['about-us', 'faq', 'privacy-policy', 'terms-and-conditions', 'shipping-delivery-policy', 'cancellation-refund-policy'] as $slug) {
            $this->get('/page/'.$slug)->assertOk();
        }
        $this->get('/contact')->assertOk()->assertSee('Get in touch');
        $this->get('/page/does-not-exist')->assertNotFound()->assertSee('This page has been eaten');
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->assertSee('/product/ruby-rose-bar', false);
        $this->get('/robots.txt')->assertOk()->assertSee('User-agent: *');
    }
}
