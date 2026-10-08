<?php

return [
    'disk' => env('PRIVATE_DOCUMENT_DISK', 'local'),
    'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
    'max_size_kb' => 10240,
    'directories' => [
        'stock_movements' => env('STOCK_MOVEMENT_DOCUMENT_DIR', 'safety-shop/stock-documents'),
    ],
];
