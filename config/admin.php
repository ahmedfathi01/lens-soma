<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Panel Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for the admin panel and authentication.
    |
    */

    'popup_auth' => [
        'username' => env('ADMIN_AUTH_USERNAME', 'admin'),
        'password' => env('ADMIN_AUTH_PASSWORD', 'admin123'),
    ],
];
