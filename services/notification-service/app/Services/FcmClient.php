<?php
namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FcmClient
{
    public function configured(): bool
    {
        return config('push.enabled') && is_file((string) config('push.credentials_path')) && preg_match('/^[a-z0-9-]+$/', (string) config('push.project_id'));
    }

    public function send(string $token, object $notification): array
    {
        if (!$this->configured()) throw new \RuntimeException('FCM_NOT_CONFIGURED');
        $path = (string) config('push.credentials_path');
        $project = (string) config('push.project_id');
        $credentials = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        if (($credentials['type'] ?? null) !== 'service_account' || ($credentials['project_id'] ?? null) !== $project) throw new \RuntimeException('FCM_CREDENTIALS_INVALID');
        // Credentials are deployment-owned. Never load a user-supplied credential URL.
        $credentials['token_uri'] = 'https://oauth2.googleapis.com/token';
        $cacheKey = 'fcm:oauth:'.hash('sha256', $project.($credentials['private_key_id'] ?? '').($credentials['client_email'] ?? ''));
        $access = Cache::store('file')->remember($cacheKey, 3000, function () use ($credentials) {
            $auth = new ServiceAccountCredentials('https://www.googleapis.com/auth/firebase.messaging', $credentials);
            $response = $auth->fetchAuthToken();
            if (empty($response['access_token'])) throw new \RuntimeException('FCM_AUTH_FAILED');
            return $response['access_token'];
        });
        $response = Http::timeout(15)->withToken($access)->acceptJson()->post('https://fcm.googleapis.com/v1/projects/'.$project.'/messages:send', [
            'message' => ['token' => $token, 'notification' => ['title' => $notification->title, 'body' => mb_substr($notification->body, 0, 1000)],
                'data' => ['notification_id' => $notification->id, 'notification_type' => $notification->notification_type]],
        ]);
        if ($response->status() === 401) Cache::store('file')->forget($cacheKey);
        $providerCode = collect($response->json('error.details', []))->pluck('errorCode')->filter()->first();
        return ['sent' => $response->successful(), 'provider_message_id' => $response->json('name'),
            'invalid_token' => $providerCode === 'UNREGISTERED', 'retryable' => in_array($response->status(), [401, 429, 500, 502, 503, 504], true),
            'failure_code' => $response->successful() ? null : 'FCM_HTTP_'.$response->status()];
    }
}
