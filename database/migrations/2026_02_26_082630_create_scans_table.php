<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shopify_store_id')->constrained('shopify_stores')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->unsignedInteger('total_metafields')->default(0);
            $table->unsignedInteger('total_definitions')->default(0);
            $table->unsignedInteger('total_issues')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scans');
    }
};
