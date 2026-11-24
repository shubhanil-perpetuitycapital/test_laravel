<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeliveryZoneController;
use App\Http\Controllers\Admin\EnquiryController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Back-office routes
|--------------------------------------------------------------------------
| Loaded from bootstrap/app.php with the "web" middleware, the URL prefix
| from config('store.admin_path') and the "admin." route-name prefix.
*/

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');

    // Orders
    Route::get('orders/export', [OrderController::class, 'export'])->name('orders.export');
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('orders/{order}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice');
    Route::get('orders/{order}/payment-screenshot', [OrderController::class, 'paymentScreenshot'])->name('orders.screenshot');
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
    Route::patch('orders/{order}/payment', [OrderController::class, 'updatePayment'])->name('orders.payment');
    Route::patch('orders/{order}/shipping', [OrderController::class, 'updateShipping'])->name('orders.shipping');
    Route::patch('orders/{order}/notes', [OrderController::class, 'updateNotes'])->name('orders.notes');

    // Custom order enquiries
    Route::get('enquiries', [EnquiryController::class, 'index'])->name('enquiries.index');
    Route::get('enquiries/{enquiry}', [EnquiryController::class, 'show'])->name('enquiries.show');
    Route::patch('enquiries/{enquiry}', [EnquiryController::class, 'update'])->name('enquiries.update');
    Route::get('enquiries/{enquiry}/image', [EnquiryController::class, 'image'])->name('enquiries.image');
    Route::post('enquiries/{enquiry}/convert', [EnquiryController::class, 'convert'])->name('enquiries.convert');
    Route::delete('enquiries/{enquiry}', [EnquiryController::class, 'destroy'])->name('enquiries.destroy');

    // Customers
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::patch('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');

    // Catalogue
    Route::resource('products', ProductController::class)->except('show');
    Route::resource('categories', CategoryController::class)->except('show');

    // Marketing and content
    Route::resource('reviews', ReviewController::class)->except('show');
    Route::patch('reviews/{review}/toggle', [ReviewController::class, 'toggle'])->name('reviews.toggle');
    Route::resource('banners', BannerController::class)->except('show');
    Route::resource('gallery', GalleryController::class)->except('show')->parameters(['gallery' => 'galleryItem']);
    Route::resource('pages', PageController::class)->except('show');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

    // Owner only: money, rules, identity and staff.
    Route::middleware('admin:owner')->group(function () {
        Route::resource('coupons', CouponController::class)->except('show');
        Route::resource('delivery-zones', DeliveryZoneController::class)->except('show')->parameters(['delivery-zones' => 'zone']);

        Route::get('settings/{screen}', [SettingsController::class, 'edit'])->whereIn('screen', ['company', 'store'])->name('settings.edit');
        Route::put('settings/{screen}', [SettingsController::class, 'update'])->whereIn('screen', ['company', 'store'])->name('settings.update');

        Route::resource('users', UserController::class)->except('show');
    });
});
