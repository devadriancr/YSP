<?php

/*
 * No cross-origin access: the portal pages are same-origin and the sync API is
 * called server to server by Supply Relay (signed), so no browser needs CORS.
 */
return [
    'paths' => [],
    'allowed_methods' => [],
    'allowed_origins' => [],
    'allowed_origins_patterns' => [],
    'allowed_headers' => [],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
