<?php

use App\Http\Controllers\Shopify\ScanController;
use App\Http\Controllers\Shopify\ShopifyOAuthController;
use App\Http\Controllers\Shopify\ShopifyStoreController;
use App\Http\Controllers\Shopify\StoreSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('stores', [ShopifyStoreController::class, 'index'])->name('stores.index');
    Route::delete('stores/{shopifyStore}', [ShopifyStoreController::class, 'destroy'])->name('stores.destroy');
    Route::post('shopify/redirect', [ShopifyOAuthController::class, 'redirect'])->name('shopify.redirect');
    Route::get('shopify/callback', [ShopifyOAuthController::class, 'callback'])->name('shopify.callback');
    Route::get('stores/{shopifyStore}/settings', [StoreSettingsController::class, 'show'])->name('stores.settings.show');
    Route::put('stores/{shopifyStore}/settings', [StoreSettingsController::class, 'update'])->name('stores.settings.update');
    Route::get('stores/{shopifyStore}/scans', [ScanController::class, 'index'])->name('stores.scans.index');
    Route::post('stores/{shopifyStore}/scans', [ScanController::class, 'store'])->name('stores.scans.store');
    Route::get('stores/{shopifyStore}/scans/{scan}', [ScanController::class, 'show'])->name('stores.scans.show');
    Route::get('stores/{shopifyStore}/scans/{scan}/export', [ScanController::class, 'export'])->name('stores.scans.export');
});
