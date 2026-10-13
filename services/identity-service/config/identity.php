<?php

return [
    'gateway_key' => env('IDENTITY_GATEWAY_KEY', ''),
    'session_minutes' => max(1, (int) env('IDENTITY_SESSION_MINUTES', 480)),
    'lockout_attempts' => 10,
];
