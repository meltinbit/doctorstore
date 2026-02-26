<?php

namespace App\Console\Commands;

use App\Models\ShopifyStore;
use DoctorStore\Core\Enums\ScanStatus;
use DoctorStore\Core\Jobs\RunStoreScanJob;
use Cron\CronExpression;
use Illuminate\Console\Command;

class RunAutoScans extends Command
{
    protected $signature = 'scans:run-auto';

    protected $description = 'Dispatch scan jobs for all stores with auto-scan enabled and due for a run';

    public function handle(): int
    {
        $dispatched = 0;

        ShopifyStore::where('auto_scan_enabled', true)
            ->with('latestScan')
            ->each(function (ShopifyStore $store) use (&$dispatched): void {
                if ($store->latestScan && in_array($store->latestScan->status, [ScanStatus::Pending, ScanStatus::Running])) {
                    return;
                }

                $cronMap = ['daily' => '0 8 * * *', 'weekly' => '0 8 * * 1'];
                $expr = $cronMap[$store->auto_scan_schedule] ?? $store->auto_scan_schedule;

                if (! $expr || ! CronExpression::isValidExpression($expr)) {
                    return;
                }

                $cron = new CronExpression($expr);
                $lastRun = $store->latestScan?->scanned_at ?? now()->subYear();
                $nextRun = $cron->getNextRunDate($lastRun);

                if (now() < $nextRun) {
                    return;
                }

                $scan = $store->scans()->create(['status' => ScanStatus::Pending]);
                RunStoreScanJob::dispatch($store, $scan);
                $dispatched++;
            });

        $this->info("Dispatched {$dispatched} scan job(s).");

        return self::SUCCESS;
    }
}
