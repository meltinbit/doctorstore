<?php

namespace App\Jobs;

use App\Enums\ScanStatus;
use App\Mail\StoreScanSummaryMail;
use App\Models\Scan;
use App\Models\ShopifyStore;
use App\Services\MetafieldAnalysisService;
use App\Services\ShopifyGraphQLService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class RunStoreScanJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ShopifyStore $store,
        public Scan $scan
    ) {}

    public function handle(MetafieldAnalysisService $analyzer): void
    {
        $graphql = new ShopifyGraphQLService($this->store);

        $this->scan->update(['status' => ScanStatus::Running]);

        $definitions = $graphql->getMetafieldDefinitions();
        $products = $graphql->getProductsWithMetafields();
        $collections = $graphql->getCollectionsWithMetafields();

        $issueData = $analyzer->analyze($definitions, $products, $collections);

        $totalMetafields = collect($products)->sum(fn ($p) => count($p['metafields']))
            + collect($collections)->sum(fn ($c) => count($c['metafields']));

        foreach (array_chunk($issueData, 500) as $chunk) {
            $this->scan->issues()->createMany($chunk);
        }

        $this->scan->update([
            'status' => ScanStatus::Complete,
            'total_metafields' => $totalMetafields,
            'total_definitions' => count($definitions),
            'total_issues' => count($issueData),
            'quality_score' => $analyzer->calculateScore($issueData),
            'scanned_at' => now(),
        ]);

        if ($this->store->email_summary_enabled && $this->store->email_summary_address) {
            Mail::to($this->store->email_summary_address)->queue(
                new StoreScanSummaryMail($this->store, $this->scan)
            );
        }
    }

    public function failed(Throwable $e): void
    {
        $this->scan->update([
            'status' => ScanStatus::Failed,
            'error_message' => $e->getMessage(),
        ]);
    }
}
