<?php

return [
    'session' => [
        'intent' => 'sso.intent',
        'tenant_id' => 'sso.tenant_id',
    ],

    'providers' => [
        'google' => [
            'label' => 'Google',
            'driver' => 'google',
            'scopes' => ['openid', 'profile', 'email'],
            'enabled' => true,
        ],
    ],
];
