<?php

return [
    'enabled' => env('FREEFIRE_ENABLED', true),
    'default_region' => env('FREEFIRE_DEFAULT_REGION', 'BD'),
    'protocol' => env('FREEFIRE_PROTOCOL', 'OB54'),
    // Custom profiles or overrides. Built-in profiles are registered by core
    // even when an older published Laravel config does not list them.
    'profiles' => [],
    'cache_store' => env('FREEFIRE_CACHE_STORE'),
    'player_cache_ttl' => (int) env('FREEFIRE_PLAYER_CACHE_TTL', 300),
    'routes' => [
        'enabled' => env('FREEFIRE_ROUTES_ENABLED', true),
        'compatibility' => env('FREEFIRE_COMPATIBILITY_ROUTES', true),
        'prefix' => env('FREEFIRE_ROUTE_PREFIX', 'api/free-fire/v1'),
        'middleware' => ['api', 'throttle:freefire'],
        'rate_limit_per_minute' => (int) env('FREEFIRE_RATE_LIMIT_PER_MINUTE', 30),
    ],
    'media' => [
        'enabled' => env('FREEFIRE_MEDIA_ENABLED', true),
        'astcenc_binary' => env('FREEFIRE_ASTCENC_BINARY', 'astcenc'),
        'font_path' => env('FREEFIRE_FONT_PATH'),
        'temporary_directory' => env('FREEFIRE_MEDIA_TMP') ?: storage_path('app/freefire/tmp'),
        'asset_bases' => array_values(array_filter(array_map('trim', explode(',', (string) env('FREEFIRE_OFFICIAL_ASSET_BASES', ''))))),
        'asset_cache_ttl' => (int) env('FREEFIRE_ASSET_CACHE_TTL', 21600),
        'cache_ttl' => (int) env('FREEFIRE_MEDIA_CACHE_TTL', 300),
        'http_cache_ttl' => (int) env('FREEFIRE_MEDIA_HTTP_CACHE_TTL', 300),
        'quality' => (int) env('FREEFIRE_MEDIA_QUALITY', 92),
        'compatibility_routes' => env('FREEFIRE_COMPATIBILITY_ROUTES', true),
    ],
];
