<?php

return [
    'enabled' => filter_var(env('MANPOWER_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
];
