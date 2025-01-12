<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Passport Public and Private Keys
    |--------------------------------------------------------------------------
    |
    | Passport uses encryption keys while issuing access tokens. You can
    | specify the paths to these keys below. By default, the keys are
    | stored in the `storage` directory.
    |
    */

	'private_key' => env('PASSPORT_PRIVATE_KEY', storage_path('oauth-private.key')),
	'public_key' => env('PASSPORT_PUBLIC_KEY', storage_path('oauth-public.key')),

    /*
    |--------------------------------------------------------------------------
    | Token Expiration Times
    |--------------------------------------------------------------------------
    |
    | Here you can specify the expiration time for tokens issued by Passport.
    | These values are in minutes and allow you to control how long the tokens
    | remain valid.
    |
    */

    'token_lifetimes' => [
        'personal_access' => env('PASSPORT_PERSONAL_ACCESS_TOKEN_EXPIRES', 1440), // Default: 1 day
        'password_grant'  => env('PASSPORT_PASSWORD_GRANT_TOKEN_EXPIRES', 120),   // Default: 2 hours
    ],
];
