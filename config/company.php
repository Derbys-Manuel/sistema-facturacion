<?php

return [
    'company_name' => env('COMPANY_NAME'),
    'ruc' => env('COMPANY_RUC'),

    'urbanization' => env('COMPANY_URBANIZATION'),
    'address' => env('COMPANY_ADDRESS'),
    'cod_local' => env('COMPANY_COD_LOCAL'),

    'sol_user' => env('COMPANY_SOL_USER'),
    'sol_pass' => env('COMPANY_SOL_PASS'),

    'cert_path' => env('COMPANY_CERT_PATH'),
    'logo_path' => env('COMPANY_LOGO_PATH'),

    'production' => env('COMPANY_PRODUCTION', false),

    'ubigueo' => env('COMPANY_UBIGUEO'),
    'department' => env('COMPANY_DEPARTMENT'),
    'province' => env('COMPANY_PROVINCE'),
    'district' => env('COMPANY_DISTRICT'),
];