<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Log API bearer tokens and full request details
    |--------------------------------------------------------------------------
    |
    | When true, logs the full Authorization / Bearer value, raw and parsed
    | bodies (with passwords redacted), headers, and OAuth tokens returned
    | from Passport. Enable only on trusted environments — logs become highly
    | sensitive. Set LOG_API_AUTH_DEBUG=true or rely on APP_DEBUG when unset.
    |
    */

    'enabled' => filter_var(
        env('LOG_API_AUTH_DEBUG', env('APP_DEBUG', false)),
        FILTER_VALIDATE_BOOLEAN
    ),

];
