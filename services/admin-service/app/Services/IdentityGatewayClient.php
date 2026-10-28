<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class IdentityGatewayClient
{
    /** @return array{token: string, user: array<string, mixed>}|null */
    public function login(string $email, string $password): ?array
    {
        try {
            $response = $this->request()->post('/api/v1/auth/login', ['email' => $email, 'password' => $password]);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful() || ! is_string($response->json('access_token')) || ! is_array($response->json('user'))) {
            return null;
        }

        return ['token' => $response->json('access_token'), 'user' => $response->json('user')];
    }

    /** @return array<string, mixed>|null */
    public function session(string $token): ?array
    {
        try {
            $response = $this->request()->withToken($token)->get('/api/v1/auth/session');
        } catch (ConnectionException) {
            return null;
        }

        return $response->successful() && is_array($response->json('user')) ? $response->json('user') : null;
    }

    /** @return array<string, mixed>|null */
    public function get(string $path, string $token): ?array
    {
        return $this->send('GET', $path, $token);
    }

    /** @param array<string, mixed> $payload
     *  @return array<string, mixed>|null */
    public function send(string $method, string $path, string $token, array $payload = []): ?array
    {
        try {
            $response = $this->request()->withToken($token)->send($method, '/api/v1/admin/'.$path, $payload === [] ? [] : ['json' => $payload]);
        } catch (ConnectionException) {
            return null;
        }

        if ($response->status() === 204) {
            return [];
        }

        return $response->successful() && is_array($response->json()) ? $response->json() : null;
    }

    private function request()
    {
        return Http::acceptJson()->asJson()->timeout(5)->baseUrl(rtrim((string) config('services.identity_gateway.url'), '/'));
    }
}
