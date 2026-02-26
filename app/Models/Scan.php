<?php

namespace App\Models;

use App\Enums\ScanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scan extends Model
{
    /** @use HasFactory<\Database\Factories\ScanFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'shopify_store_id',
        'status',
        'total_metafields',
        'total_definitions',
        'total_issues',
        'quality_score',
        'error_message',
        'scanned_at',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'status' => ScanStatus::class,
            'scanned_at' => 'datetime',
        ];
    }

    public function shopifyStore(): BelongsTo
    {
        return $this->belongsTo(ShopifyStore::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(ScanIssue::class);
    }
}
