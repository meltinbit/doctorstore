<?php

namespace App\Providers;

use App\Mail\StoreScanSummaryMail;
use Carbon\CarbonImmutable;
use DoctorStore\Core\Events\StoreScanCompleted;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerEventListeners();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    protected function registerEventListeners(): void
    {
        Event::listen(StoreScanCompleted::class, function (StoreScanCompleted $event): void {
            if ($event->store->email_summary_enabled && $event->store->email_summary_address) {
                Mail::to($event->store->email_summary_address)->queue(
                    new StoreScanSummaryMail($event->store, $event->scan)
                );
            }
        });
    }
}
