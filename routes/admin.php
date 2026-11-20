<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\CompanySettingsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProfileController;
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

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

    // Company setup: owner only.
    Route::middleware('admin:owner')->group(function () {
        Route::get('company', [CompanySettingsController::class, 'edit'])->name('company.edit');
        Route::put('company', [CompanySettingsController::class, 'update'])->name('company.update');
    });
});
