<?php

namespace App\Http\Controllers\Shopify;

use App\Enums\ScanStatus;
use App\Http\Controllers\Controller;
use App\Jobs\RunStoreScanJob;
use App\Models\Scan;
use App\Models\ShopifyStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ScanController extends Controller
{
    public function export(Request $request, ShopifyStore $shopifyStore, Scan $scan): HttpResponse
    {
        Gate::authorize('view', $scan);

        $scan->load('issues');

        $filename = "scan-{$shopifyStore->shop_domain}-{$scan->id}.csv";

        $rows = collect([['Namespace', 'Key', 'Resource Type', 'Issue Type', 'Occurrences']]);

        $scan->issues->each(function ($issue) use ($rows) {
            $rows->push([
                $issue->namespace,
                $issue->key,
                $issue->resource_type->value,
                $issue->issue_type->value,
                $issue->occurrences,
            ]);
        });

        $csv = $rows->map(fn ($row) => implode(',', array_map(
            fn ($cell) => '"'.str_replace('"', '""', (string) $cell).'"',
            $row
        )))->implode("\n");

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function index(Request $request, ShopifyStore $shopifyStore): Response
    {
        Gate::authorize('view', $shopifyStore);

        $rows = $shopifyStore->scans()
            ->select(['id', 'shopify_store_id', 'status', 'total_metafields', 'total_definitions', 'total_issues', 'quality_score', 'scanned_at', 'created_at'])
            ->latest()
            ->get();

        $scans = $rows->map(function ($scan, $index) use ($rows) {
            $previous = $rows->get($index + 1); // rows are newest-first

            return [
                'id' => $scan->id,
                'status' => $scan->status->value,
                'total_metafields' => $scan->total_metafields,
                'total_definitions' => $scan->total_definitions,
                'total_issues' => $scan->total_issues,
                'quality_score' => $scan->quality_score,
                'scanned_at' => $scan->scanned_at?->toISOString(),
                'created_at' => $scan->created_at->toISOString(),
                'delta_issues' => ($previous && $previous->status->value === 'complete' && $scan->status->value === 'complete')
                    ? $scan->total_issues - $previous->total_issues
                    : null,
                'delta_score' => ($previous && $previous->status->value === 'complete' && $scan->status->value === 'complete')
                    ? $scan->quality_score - $previous->quality_score
                    : null,
            ];
        });

        return Inertia::render('stores/scans/index', [
            'store' => [
                'id' => $shopifyStore->id,
                'shop_domain' => $shopifyStore->shop_domain,
                'shop_name' => $shopifyStore->shop_name,
            ],
            'scans' => $scans,
        ]);
    }

    public function store(Request $request, ShopifyStore $shopifyStore): RedirectResponse
    {
        Gate::authorize('view', $shopifyStore);

        $scan = $shopifyStore->scans()->create([
            'status' => ScanStatus::Pending,
        ]);

        RunStoreScanJob::dispatch($shopifyStore, $scan);

        return redirect()->route('stores.scans.show', [$shopifyStore, $scan]);
    }

    public function show(Request $request, ShopifyStore $shopifyStore, Scan $scan): Response
    {
        Gate::authorize('view', $scan);

        $scan->load('issues');

        $issuesByType = $scan->issues
            ->groupBy(fn ($issue) => $issue->issue_type->value)
            ->map(fn ($group) => $group->sum('occurrences'));

        return Inertia::render('stores/scans/show', [
            'store' => [
                'id' => $shopifyStore->id,
                'shop_domain' => $shopifyStore->shop_domain,
                'shop_name' => $shopifyStore->shop_name,
            ],
            'scan' => [
                'id' => $scan->id,
                'status' => $scan->status->value,
                'total_metafields' => $scan->total_metafields,
                'total_definitions' => $scan->total_definitions,
                'total_issues' => $scan->total_issues,
                'quality_score' => $scan->quality_score,
                'scanned_at' => $scan->scanned_at?->toISOString(),
                'error_message' => $scan->error_message,
            ],
            'issues' => $scan->issues->map(fn ($issue) => [
                'id' => $issue->id,
                'namespace' => $issue->namespace,
                'key' => $issue->key,
                'resource_type' => $issue->resource_type->value,
                'issue_type' => $issue->issue_type->value,
                'occurrences' => $issue->occurrences,
            ]),
            'issuesByType' => $issuesByType,
            'issuesByResourceType' => $scan->issues
                ->groupBy(fn ($issue) => $issue->resource_type->value)
                ->map(fn ($group) => [
                    'total_occurrences' => $group->sum('occurrences'),
                    'issue_count' => $group->count(),
                ]),
        ]);
    }
}
