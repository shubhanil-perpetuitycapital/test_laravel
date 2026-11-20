<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront routes
|--------------------------------------------------------------------------
| Back-office routes live in routes/admin.php (prefix from ADMIN_PATH).
*/

Route::get('/', HomeController::class)->name('home');

Route::get('/shop/{category:slug?}', [ShopController::class, 'index'])
    ->middleware('throttle:storefront-ajax')
    ->name('shop.index');

Route::get('/product/{product:slug}', [ProductController::class, 'show'])->name('products.show');

Route::get('/contact', [ContactController::class, 'show'])->name('contact');

Route::get('/page/{page:slug}', [PageController::class, 'show'])->name('pages.show');

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
