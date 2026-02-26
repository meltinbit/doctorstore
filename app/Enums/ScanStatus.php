<?php

namespace App\Enums;

enum ScanStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Complete = 'complete';
    case Failed = 'failed';
}
