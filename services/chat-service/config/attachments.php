<?php
return ['disk' => env('CHAT_ATTACHMENT_DISK', 's3'), 'max_kilobytes' => (int) env('CHAT_ATTACHMENT_MAX_KB', 20480), 'scanner_key' => env('CHAT_SCANNER_KEY')];
