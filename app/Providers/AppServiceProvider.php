<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\CustomOrderEnquiry;
use App\Models\Order;
use App\Models\Review;
use App\Services\CartService;
use App\Services\CompanyProfile;
use App\Services\ImageService;
use App\Services\SettingService;
use App\Services\WhatsAppService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ImageService::class);
        $this->app->singleton(SettingService::class);
        $this->app->singleton(CompanyProfile::class);
        $this->app->singleton(WhatsAppService::class);
    }

    public function boot(): void
    {
        // Older shared-hosting MySQL/MariaDB builds with utf8mb4 index limits.
        Schema::defaultStringLength(191);

        Paginator::useBootstrapFive();

        $this->configureRateLimiting();

        // Company identity and WhatsApp helper available in every view.
        View::composer('*', function ($view) {
            $view->with('company', app(CompanyProfile::class));
            $view->with('whatsapp', app(WhatsAppService::class));
        });

        // Storefront navigation: categories (cached) and cart count.
        View::composer(['layouts.app', 'partials.*'], function ($view) {
            try {
                $categories = Cache::remember('nav_categories', 600, fn () => Category::query()
                    ->active()->ordered()->get(['id', 'name', 'slug']));
            } catch (Throwable) {
                $categories = collect();
            }
            $view->with('navCategories', $categories);
            $view->with('cartCount', app(CartService::class)->count());
        });

        // Badges in the admin sidebar.
        View::composer('admin.layouts.app', function ($view) {
            $view->with('adminBadges', [
                'orders' => Order::query()->where('status', 'pending')->count(),
                'payments' => Order::query()->where('payment_status', 'submitted')->count(),
                'enquiries' => CustomOrderEnquiry::query()->where('status', 'new')->count(),
                'reviews' => Review::query()->where('is_approved', false)->count(),
            ]);
        });
    }

    private function configureRateLimiting(): void
    {
        // Public forms: checkout, custom order, payment reference, tracking, reviews.
        RateLimiter::for('public-forms', fn (Request $request) => [
            Limit::perMinute(6)->by($request->ip()),
            Limit::perHour(40)->by($request->ip()),
        ]);

        // Storefront AJAX endpoints (filters, cart, delivery check).
        RateLimiter::for('storefront-ajax', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
    }
}
