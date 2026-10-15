<?php

return [
    'enabled' => env('ZATCA_ENABLED', false),
    'environment' => env('ZATCA_ENVIRONMENT', 'disabled'),
    'connect_timeout' => (int) env('ZATCA_CONNECT_TIMEOUT', 10),
    'request_timeout' => (int) env('ZATCA_REQUEST_TIMEOUT', 30),
    'sdk_home' => env('ZATCA_SDK_HOME', base_path('zatca-envoice-sdk-203')),
    'sdk_version' => env('ZATCA_SDK_VERSION', '3.0.8'),
    'java_binary' => env('ZATCA_JAVA_BINARY', PHP_OS_FAMILY==='Windows'?'C:\\Program Files\\Java\\jdk-17.0.1\\bin\\java.exe':'java'),
    'sdk_certificate_password' => env('ZATCA_SDK_CERTIFICATE_PASSWORD', '123456789'),
    'openssl_binary' => env('ZATCA_OPENSSL_BINARY', PHP_OS_FAMILY==='Windows'?'C:\\xampp\\apache\\bin\\openssl.exe':'openssl'),
    'endpoints' => [
        'simulation' => env('ZATCA_SIMULATION_URL', 'https://gw-fatoora.zatca.gov.sa/e-invoicing/simulation'),
        'production' => env('ZATCA_PRODUCTION_URL', 'https://gw-fatoora.zatca.gov.sa/e-invoicing/core'),
    ],
];
