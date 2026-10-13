<?php

return [
    /*
     * Document objects never become public. The configured S3-compatible disk
     * is MinIO locally and can be AWS S3 or another compatible provider later.
     */
    'disk' => env('LEARNING_DOCUMENT_DISK', 's3'),
    'max_upload_kilobytes' => (int) env('LEARNING_DOCUMENT_MAX_UPLOAD_KB', 51200),
    'signed_url_minutes' => (int) env('LEARNING_DOCUMENT_SIGNED_URL_MINUTES', 5),

    /*
     * A development-only bridge until the antivirus worker is introduced.
     * Keep this false in every shared/staging/production environment: uploaded
     * files stay `pending` and cannot be published until a scanner marks them clean.
     */
    'trust_local_uploads' => (bool) env('LEARNING_TRUST_LOCAL_UPLOADS', false),
];
