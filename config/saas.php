<?php

return [
    'saas_voucher_enabled' => env('SAAS_VOUCHER_ENABLED', true),
    'grace_days' => (int) env('SAAS_VOUCHER_GRACE_DAYS', 7),
];
