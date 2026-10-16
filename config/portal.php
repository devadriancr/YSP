<?php

return [

    'company_name' => 'Y-Tec Keylex Mexico.S.A.de C.V',

    // Work calendar (FSC) shown to every supplier
    'calendar' => [
        'facility' => env('PORTAL_CALENDAR_FACILITY', 'YK1'),
        'work_center' => (int) env('PORTAL_CALENDAR_WORK_CENTER', 0),
    ],

    // Sync service (supply-relay) that sends the Infor data. Every request is
    // signed with HMAC-SHA256 using this shared key; see VerifySyncSignature.
    'sync' => [
        'key' => env('SYNC_KEY'),

        // Seconds a signed request stays valid (clock difference between servers)
        'tolerance' => (int) env('SYNC_TOLERANCE_SECONDS', 300),

        // Optional: comma separated IPs or CIDR ranges allowed to send data
        'allowed_ips' => array_values(array_filter(array_map('trim', explode(',', (string) env('SYNC_ALLOWED_IPS', ''))))),
    ],

];
