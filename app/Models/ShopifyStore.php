<?php

namespace App\Models;

use DoctorStore\Core\Models\ShopifyStore as CoreShopifyStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopifyStore extends CoreShopifyStore
{
    /** @use HasFactory<\Database\Factories\ShopifyStoreFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shop_domain',
        'access_token',
        'shop_name',
        'scopes',
        'auto_scan_enabled',
        'auto_scan_schedule',
        'email_summary_enabled',
        'email_summary_address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
