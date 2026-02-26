<?php

namespace App\Http\Controllers\Shopify;

use App\Http\Controllers\Controller;
use App\Models\ShopifyStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ShopifyStoreController extends Controller
{
    public function index(Request $request): Response
    {
        $stores = $request->user()
            ->shopifyStores()
            ->select(['id', 'shop_domain', 'shop_name', 'scopes', 'created_at', 'auto_scan_enabled', 'auto_scan_schedule', 'email_summary_enabled', 'email_summary_address'])
            ->with(['latestScan'])
            ->latest()
            ->get();

        return Inertia::render('stores/index', [
            'stores' => $stores,
            'status' => session('success'),
            'error' => session('error'),
        ]);
    }

    public function destroy(Request $request, ShopifyStore $shopifyStore): RedirectResponse
    {
        Gate::authorize('delete', $shopifyStore);

        $shopifyStore->delete();

        return redirect()->route('stores.index')->with('success', 'Store disconnected.');
    }
}
