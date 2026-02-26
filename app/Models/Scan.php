<?php

namespace App\Models;

use DoctorStore\Core\Models\Scan as CoreScan;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Scan extends CoreScan
{
    /** @use HasFactory<\Database\Factories\ScanFactory> */
    use HasFactory;
}
