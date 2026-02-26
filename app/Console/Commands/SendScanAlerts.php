<?php

namespace App\Console\Commands;

use App\Mail\ScanAlertMail;
use DoctorStore\Core\Enums\ScanStatus;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendScanAlerts extends Command
{
    protected $signature = 'scans:send-alerts {frequency : daily or weekly}';

    protected $description = 'Send metafield alert emails to users with the given frequency setting';

    public function handle(): int
    {
        $frequency = $this->argument('frequency');

        User::where('alert_frequency', $frequency)
            ->with(['shopifyStores.latestScan'])
            ->each(function (User $user) use ($frequency) {
                $storeReports = $user->shopifyStores
                    ->filter(fn ($store) => $store->latestScan && $store->latestScan->status === ScanStatus::Complete)
                    ->map(fn ($store) => [
                        'store' => $store->shop_name ?? $store->shop_domain,
                        'store_id' => $store->id,
                        'scan_id' => $store->latestScan->id,
                        'issues' => $store->latestScan->total_issues,
                        'quality_score' => $store->latestScan->quality_score,
                    ])
                    ->values()
                    ->all();

                if (empty($storeReports)) {
                    return;
                }

                Mail::to($user->email)->queue(
                    (new ScanAlertMail($user, $storeReports))->with('frequency', $frequency)
                );

                $this->info("Alert queued for {$user->email}");
            });

        return self::SUCCESS;
    }
}
