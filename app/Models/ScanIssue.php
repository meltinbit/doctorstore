<?php

namespace App\Models;

use App\Enums\IssueType;
use App\Enums\ResourceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanIssue extends Model
{
    /** @use HasFactory<\Database\Factories\ScanIssueFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'scan_id',
        'namespace',
        'key',
        'resource_type',
        'issue_type',
        'occurrences',
        'details',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'issue_type' => IssueType::class,
            'resource_type' => ResourceType::class,
            'details' => 'array',
        ];
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }
}
