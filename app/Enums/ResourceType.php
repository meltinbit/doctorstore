<?php

namespace App\Enums;

enum ResourceType: string
{
    case Product = 'product';
    case Variant = 'variant';
    case Collection = 'collection';
    case Global = 'global';
}
