<?php

return [

    // Database connection (config/database.php) used to read Infor LX
    'connection' => env('INFOR_CONNECTION', 'infor'),

    // Standard Infor files (HPO, IIM, AVM, KFP, IIU, FRT, LWK)
    'library' => env('INFOR_LIBRARY', 'LX834F01'),

    // YKM custom files (YDOPC, YKRE4, YM200, YM202, TM200)
    'custom_library' => env('INFOR_CUSTOM_LIBRARY', 'LX834FU01'),

    // Vendor shown until each user gets their own vendor number
    'default_vendor' => (int) env('INFOR_DEFAULT_VENDOR', 410020),

    // Minutes the calendar keeps the Infor results before asking again
    'cache_minutes' => (int) env('INFOR_CACHE_MINUTES', 10),

    'company_name' => 'Y-Tec Keylex Mexico.S.A.de C.V',
];
