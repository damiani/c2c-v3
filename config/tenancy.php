<?php

return [
    'default_tenant' => [
        'name' => env('C2C_DEFAULT_TENANT_NAME', 'C2C'),
        'slug' => env('C2C_DEFAULT_TENANT_SLUG', 'c2c'),
    ],

    'session_key' => env('C2C_TENANT_SESSION_KEY', 'current_tenant_id'),
];
