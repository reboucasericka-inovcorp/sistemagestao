<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Sanctum + SPA: supports_credentials=true e allowed_origins não pode ser *.
    | Incluir rotas Fortify em "paths" para o browser receber cabeçalhos CORS
    | em preflight (OPTIONS) e em POST /login, POST /logout, etc.
    |
    */

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'login',
        'logout',
        'forgot-password',
        'reset-password',
        'two-factor-challenge',
        'user/*'
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['http://127.0.0.1:5173'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
