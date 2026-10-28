<?php
return ['disk' => env('SOCIAL_ATTACHMENT_DISK', 's3'), 'max_kilobytes' => (int) env('SOCIAL_ATTACHMENT_MAX_KB', 20480), 'scanner_key' => env('SOCIAL_SCANNER_KEY')];
