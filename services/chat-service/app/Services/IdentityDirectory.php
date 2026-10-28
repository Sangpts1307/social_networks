<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class IdentityDirectory
{
    public function requireUsers(string $organizationId, array $ids): void
    {
        $key = (string) config('services.identity_key');
        abort_unless(strlen($key) >= 32, 503, 'Identity lookup is not configured.');
        try {
            $response = Http::timeout(5)->acceptJson()->withHeaders([
                'x-service-name' => 'chat', 'x-service-key' => $key,
                'x-request-id' => request()->header('x-request-id', ''),
            ])->post(rtrim(config('services.identity_url'), '/').'/api/v1/internal/users/resolve', [
                'organization_id' => $organizationId, 'user_ids' => array_values(array_unique($ids)),
            ]);
        } catch (\Throwable) { abort(503, 'Identity lookup unavailable.'); }
        if ($response->status() === 422) abort(422, 'One or more users are unavailable.');
        abort_unless($response->successful(), 503, 'Identity lookup unavailable.');
    }
}
