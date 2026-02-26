<?php

namespace App\Http\Controllers\Shopify;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shopify\StoreSettingsRequest;
use App\Models\ShopifyStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class StoreSettingsController extends Controller
{
    public function show(Request $request, ShopifyStore $shopifyStore): Response
    {
        Gate::authorize('update', $shopifyStore);

        return Inertia::render('stores/settings', [
            'store' => $shopifyStore->only([
                'id',
                'shop_domain',
                'shop_name',
                'auto_scan_enabled',
                'auto_scan_schedule',
                'email_summary_enabled',
                'email_summary_address',
            ]),
        ]);
    }

    public function update(StoreSettingsRequest $request, ShopifyStore $shopifyStore): RedirectResponse
    {
        Gate::authorize('update', $shopifyStore);

        $shopifyStore->update($request->validated());

        return redirect()->back()->with('status', 'settings-saved');
    }
}
