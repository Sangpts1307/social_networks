<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class SocialGatewayClient
{
    /** @return array<string, mixed>|null */
    public function get(string $path, string $token): ?array
    {
        return $this->send('GET', $path, $token);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>|null
     */
    public function send(string $method, string $path, string $token, array $payload = []): ?array
    {
        try {
            $url = rtrim((string) config('services.identity_gateway.url'), '/') . '/api/v1/social/' . ltrim($path, '/');
            $request = Http::acceptJson()->asJson()->timeout(10)->withToken($token);

            $response = match (strtoupper($method)) {
                'GET' => $request->get($url),
                'POST' => $request->post($url, $payload),
                'PUT' => $request->put($url, $payload),
                'PATCH' => $request->patch($url, $payload),
                'DELETE' => $request->delete($url, $payload),
                default => $request->send($method, $url, ['json' => $payload]),
            };
        } catch (ConnectionException) {
            return null;
        }

        if ($response->status() === 204) {
            return [];
        }

        return $response->successful() && is_array($response->json()) ? $response->json() : null;
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>|null
     */
    public function adminPosts(string $token, array $query = []): ?array
    {
        $queryString = $query !== [] ? '?' . http_build_query($query) : '';
        return $this->get('admin/posts' . $queryString, $token);
    }

    /** @return array<string, mixed>|null */
    public function channels(string $token): ?array
    {
        return $this->get('channels', $token);
    }

    /** @return array<string, mixed>|null */
    public function moderate(string $token, string $postId, string $action, string $reasonCode, ?string $note = null): ?array
    {
        return $this->send('POST', "admin/posts/{$postId}/moderation", $token, [
            'action' => $action,
            'reason_code' => $reasonCode,
            'note' => $note,
        ]);
    }

    /** @return array<string, mixed>|null */
    public function scanPost(string $token, string $postId): ?array
    {
        return $this->send('POST', "admin/posts/{$postId}/scan", $token);
    }

    /** @return array<string, mixed>|null */
    public function scanAll(string $token): ?array
    {
        return $this->send('POST', 'admin/posts/scan-all', $token);
    }
}
