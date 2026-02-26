<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shopify_stores', function (Blueprint $table) {
            $table->boolean('auto_scan_enabled')->default(false)->after('scopes');
            $table->string('auto_scan_schedule')->nullable()->after('auto_scan_enabled');
            $table->boolean('email_summary_enabled')->default(false)->after('auto_scan_schedule');
            $table->string('email_summary_address')->nullable()->after('email_summary_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shopify_stores', function (Blueprint $table) {
            $table->dropColumn(['auto_scan_enabled', 'auto_scan_schedule', 'email_summary_enabled', 'email_summary_address']);
        });
    }
};
