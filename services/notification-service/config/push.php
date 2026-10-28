<?php
return ['enabled' => (bool) env('FCM_ENABLED', false), 'credentials_path' => env('FCM_SERVICE_ACCOUNT_PATH'), 'project_id' => env('FCM_PROJECT_ID')];
