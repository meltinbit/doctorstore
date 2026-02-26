<?php

namespace App\Models;

use DoctorStore\Core\Models\ScanIssue as CoreScanIssue;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ScanIssue extends CoreScanIssue
{
    /** @use HasFactory<\Database\Factories\ScanIssueFactory> */
    use HasFactory;
}
