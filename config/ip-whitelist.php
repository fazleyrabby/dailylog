<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable IP Whitelist
    |--------------------------------------------------------------------------
    |
    | When enabled, only requests from whitelisted IPs are allowed.
    | Defaults to true in production and false in all other environments.
    |
    */

    'enabled' => (bool) env('IP_WHITELIST_ENABLED', env('APP_ENV') === 'production'),

    /*
    |--------------------------------------------------------------------------
    | Whitelisted IPs (Environment)
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of IPs or CIDR ranges that are always allowed.
    | These cannot be removed from the settings UI and act as a lockout
    | safety net. Example: "100.101.167.10,192.168.1.0/24"
    |
    */

    'ips' => array_filter(array_map(
        'trim',
        explode(',', (string) env('IP_WHITELIST', '')),
    )),

];
