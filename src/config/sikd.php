<?php

return [

    'url' => env('SIKD_BASE_URL'),
    'webhook_url' => env('SIKD_WEBHOOK_URL'),
    'webhook_distribute_document' => env('SIKD_WEBHOOK_DISTRIBUTE_DOCUMENT_URL'),
    'webhook_secret' => env('SIKD_WEBHOOK_SECRET'),
    'enable_sign_with_nik' => env('ENABLE_SIGN_WITH_NIK'),
    'signature_nik' => env('SIKD_SIGNATURE_NIK'),
    'signature_url' => env('SIKD_SIGNATURE_URL'),
    'signature_verify_url' => env('SIKD_SIGNATURE_VERIFY_URL'),
    'signature_auth' => env('SIKD_SIGNATURE_AUTH'),
    'signature_cookies' => env('SIKD_SIGNATURE_COOKIES'),
    'base_path_file' => env('SIKD_BASE_PATH_FILE'),
    'base_path_file_letter' => env('SIKD_BASE_PATH_FILE_LETTER'),
    'timezone_server' => env('SIKD_TIMEZONE_SERVER'),
    'add_footer_url' => env('SIKD_ADD_FOOTER_URL')
];
