<?php

namespace App\Providers;

use App\Models\Category;
use App\Services\CompanyProfile;
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

        // Category list for storefront navigation, cached for 10 minutes.
        View::composer(['layouts.app', 'partials.*'], function ($view) {
            try {
                $categories = Cache::remember('nav_categories', 600, fn () => Category::query()
                    ->active()->ordered()->get(['id', 'name', 'slug']));
            } catch (Throwable) {
                $categories = collect();
            }
            $view->with('navCategories', $categories);
        });
    }

    private function configureRateLimiting(): void
    {
        // Public forms (custom order, contact, order tracking, checkout).
        RateLimiter::for('public-forms', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perHour(30)->by($request->ip()),
            ];
        });

        // Storefront AJAX endpoints (filters, search).
        RateLimiter::for('storefront-ajax', fn (Request $request) => Limit::perMinute(90)->by($request->ip()));
    }
}
