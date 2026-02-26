<?php

return [
    'client_id' => env('SHOPIFY_CLIENT_ID'),
    'client_secret' => env('SHOPIFY_CLIENT_SECRET'),
    'scopes' => env('SHOPIFY_SCOPES', 'read_products,read_metafields'),
    'redirect_uri' => env('SHOPIFY_REDIRECT_URI'),
    'api_version' => env('SHOPIFY_API_VERSION', '2026-01'),
];
