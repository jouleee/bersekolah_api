<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'storage/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'https://bersekolah-astro.vercel.app',
        'https://*.vercel.app', // untuk preview branch
        'http://localhost:4321', // jika develop lokal
    ],
    'allowed_origins_patterns' => [
        '#^https://bersekolah-astro-.*\.vercel\.app$#', // preview branch
    ],
    'allowed_headers' => ['*'],
    'supports_credentials' => true,
];