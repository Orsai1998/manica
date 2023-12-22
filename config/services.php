<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'interpay' => [
        'email' => env('INTERPAY_EMAIL', 'arenaa2012@mail.ru'),
        'apiKey' => env('INTERPAY_API_KEY', '18D33336-6D5A-4F00-804B-170FB11FE160'),
        'create_payment_url' => env('INTERPAY_CREATE_PAYMENT_URL', '18D33336-6D5A-4F00-804B-170FB11FE160'),
        'process_payment_url' => env('INTERPAY_PROCESS_PAYMENT_URL', '18D33336-6D5A-4F00-804B-170FB11FE160'),
    ],
    'deposit' => '20',
    'customer_email' => 'Arenaa2012@mail.ru',
    'one_c' => [
        'url' => env('one_c_url', 'http://rhouse.keenetic.pro:7071/all_bases/hs/mobiApi'),
        'username' => env('one_c_username', 'mobiApi'),
        'password' => env('one_c_password', 'dHvSHX'),
    ],
    'lock' => [
        'url' => env('LOCK_URL', 'https://automation.manica.kz/api/v1'),
        'salt' => env('LOCK_SALT', '3b6064083432d1f4c78a26bdea04bd50602ac6c13b54b8f6fbbe5dd288bcf90635cdcb301272c48ae0e3f55d114a4451d6533e8489f2329e405a4b440e1ed73b'),
    ],

    'mobizone' => [
        'api' => env('MOBIZONE_API', 'api.mobizon.kz'),
        'apiKey' => env('MOBIZONE_API_KEY', 'kz54c513523637a90b177b852684f77022064528d9aeb9ca4a6de8c012b6b7ff0a1819'),
    ],

];
