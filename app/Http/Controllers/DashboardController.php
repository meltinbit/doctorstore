<?php

namespace App\Http\Controllers;

use DoctorStore\Core\Enums\ScanStatus;
use App\Models\Scan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $storeIds = $user->shopifyStores()->pluck('id');

        $totalStores = $storeIds->count();

        $totalScans = Scan::whereIn('shopify_store_id', $storeIds)->count();

        $latestScansPerStore = Scan::whereIn('shopify_store_id', $storeIds)
            ->where('status', ScanStatus::Complete)
            ->selectRaw('shopify_store_id, MAX(id) as latest_id')
            ->groupBy('shopify_store_id')
            ->pluck('latest_id');

        $totalIssues = Scan::whereIn('id', $latestScansPerStore)
            ->sum('total_issues');

        $lastScan = Scan::whereIn('shopify_store_id', $storeIds)
            ->where('status', ScanStatus::Complete)
            ->latest('scanned_at')
            ->value('scanned_at');

        $storesWithLatestScan = $user->shopifyStores()
            ->select(['id', 'shop_domain', 'shop_name'])
            ->with(['latestScan'])
            ->latest()
            ->get()
            ->map(fn ($store) => [
                'id' => $store->id,
                'shop_domain' => $store->shop_domain,
                'shop_name' => $store->shop_name,
                'latest_scan' => $store->latestScan ? [
                    'id' => $store->latestScan->id,
                    'status' => $store->latestScan->status->value,
                    'total_issues' => $store->latestScan->total_issues,
                    'quality_score' => $store->latestScan->quality_score,
                    'scanned_at' => $store->latestScan->scanned_at?->toISOString(),
                ] : null,
            ]);

        return Inertia::render('dashboard', [
            'stats' => [
                'total_stores' => $totalStores,
                'total_scans' => $totalScans,
                'total_issues' => (int) $totalIssues,
                'last_scan_at' => $lastScan?->toISOString(),
            ],
            'stores' => $storesWithLatestScan,
        ]);
    }
}
