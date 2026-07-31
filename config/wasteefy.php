<?php

return [
    // Bootstrap admin account, created idempotently by DatabaseSeeder on
    // every deploy. Change these via env vars before going properly live —
    // the defaults are placeholders, not meant to stay in production.
    'admin_email' => env('ADMIN_EMAIL', 'admin@wasteefy.test'),
    'admin_password' => env('ADMIN_PASSWORD', 'password123'),
];
