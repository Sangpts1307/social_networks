<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class LearningGatewayClient
{
    /** @return array<string, mixed>|null */
    public function get(string $path, string $token): ?array
    {
        return $this->send('GET', $path, $token);
    }

    public function topics(string $token): ?array
    {
        try {
            $response = Http::acceptJson()->withToken($token)->timeout(5)
                ->get(rtrim((string) config('services.identity_gateway.url'), '/').'/api/v1/learning/knowledge/topics');

            return $response->successful() && is_array($response->json()) ? $response->json() : null;
        } catch (ConnectionException) {
            return null;
        }
    }

    /** @param array<string, mixed> $payload
     *  @return array<string, mixed>|null */
    public function send(string $method, string $path, string $token, array $payload = []): ?array
    {
        try {
            $response = Http::acceptJson()->asJson()->timeout(5)
                ->baseUrl(rtrim((string) config('services.identity_gateway.url'), '/'))
                ->withToken($token)
                ->send($method, '/api/v1/admin/learning/'.$path, $payload === [] ? [] : ['json' => $payload]);
        } catch (ConnectionException) {
            return null;
        }

        return $response->successful() && is_array($response->json()) ? $response->json() : null;
    }

    /** @return array<string, mixed>|null */
    public function upload(string $path, string $token, UploadedFile $file): ?array
    {
        try {
            $response = Http::acceptJson()->timeout(60)
                ->baseUrl(rtrim((string) config('services.identity_gateway.url'), '/'))
                ->withToken($token)
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post('/api/v1/admin/learning/'.$path);
        } catch (ConnectionException) {
            return null;
        }

        return $response->successful() && is_array($response->json()) ? $response->json() : null;
    }
}
