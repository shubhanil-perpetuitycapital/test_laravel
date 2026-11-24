<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CustomOrderController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderConfirmationController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
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

// Catalogue
Route::get('/shop/{category:slug?}', [ShopController::class, 'index'])->middleware('throttle:storefront-ajax')->name('shop.index');
Route::get('/product/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::post('/product/{product:slug}/reviews', [ReviewController::class, 'store'])->middleware('throttle:public-forms')->name('reviews.store');

// Cart
Route::middleware('throttle:storefront-ajax')->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/cart/{productId}', [CartController::class, 'update'])->whereNumber('productId')->name('cart.update');
    Route::delete('/cart/{productId}', [CartController::class, 'destroy'])->whereNumber('productId')->name('cart.destroy');
    Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon');
    Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');
    Route::post('/delivery/check', [CheckoutController::class, 'quote'])->name('delivery.check');
});

// Checkout and orders
Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:public-forms')->name('checkout.store');

Route::middleware('signed')->group(function () {
    Route::get('/order/{order:order_number}/thank-you', [OrderConfirmationController::class, 'show'])->name('orders.confirmation');
    Route::post('/order/{order:order_number}/payment', [OrderConfirmationController::class, 'payment'])
        ->middleware('throttle:public-forms')->name('orders.payment');
});

Route::get('/track-order', [OrderTrackingController::class, 'index'])->name('track.index');
Route::post('/track-order', [OrderTrackingController::class, 'lookup'])->middleware('throttle:public-forms')->name('track.lookup');
Route::get('/track-order/{order}', [OrderTrackingController::class, 'show'])->name('track.show');

// Custom orders
Route::get('/custom-orders', [CustomOrderController::class, 'create'])->name('custom-orders.create');
Route::post('/custom-orders', [CustomOrderController::class, 'store'])->middleware('throttle:public-forms')->name('custom-orders.store');
Route::get('/custom-orders/thank-you', [CustomOrderController::class, 'thanks'])->name('custom-orders.thanks');

// Content
Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::get('/page/{page:slug}', [PageController::class, 'show'])->name('pages.show');

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
