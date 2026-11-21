<?php
return [
    'disk' => env('CHAT_ATTACHMENT_DISK', 's3'),
    'max_kilobytes' => (int) env('CHAT_ATTACHMENT_MAX_KB', 20480),
    'scanner_key' => env('CHAT_SCANNER_KEY'),
    // Explicit opt-in for local development without an antivirus worker.
    'trust_local_uploads' => env('APP_ENV') === 'local' && filter_var(env('CHAT_TRUST_LOCAL_UPLOADS', false), FILTER_VALIDATE_BOOLEAN),
];
