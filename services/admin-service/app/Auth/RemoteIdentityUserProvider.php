<?php

namespace App\Auth;

use App\Services\IdentityGatewayClient;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

class RemoteIdentityUserProvider implements UserProvider
{
    private ?RemoteIdentityUser $resolvedUser = null;

    public function __construct(private readonly IdentityGatewayClient $identity) {}

    public function retrieveById($identifier): ?Authenticatable
    {
        if ($this->resolvedUser?->getAuthIdentifier() === $identifier) {
            return $this->resolvedUser;
        }
        $session = request()->session();
        $token = $session->get('admin.identity_token');
        if (! is_string($token) || $token === '') {
            return null;
        }
        $user = $this->identity->session($token);
        if (! is_array($user) || ($user['id'] ?? null) !== $identifier) {
            $session->forget(['admin.identity_token', 'admin.identity_user']);

            return null;
        }
        $session->put('admin.identity_user', $user);

        return $this->resolvedUser = new RemoteIdentityUser($user);
    }

    public function retrieveByToken($identifier, #[\SensitiveParameter] $token): ?Authenticatable
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, #[\SensitiveParameter] $token): void {}

    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials): ?Authenticatable
    {
        if ($this->resolvedUser !== null) {
            return $this->resolvedUser;
        }
        $email = $credentials['email'] ?? null;
        $password = $credentials['password'] ?? null;
        if (! is_string($email) || ! is_string($password)) {
            return null;
        }
        $result = $this->identity->login($email, $password);
        if ($result === null) {
            return null;
        }
        request()->session()->put('admin.identity_token', $result['token']);
        request()->session()->put('admin.identity_user', $result['user']);

        return $this->resolvedUser = new RemoteIdentityUser($result['user']);
    }

    public function validateCredentials(Authenticatable $user, #[\SensitiveParameter] array $credentials): bool
    {
        return $user instanceof RemoteIdentityUser;
    }

    public function rehashPasswordIfRequired(Authenticatable $user, #[\SensitiveParameter] array $credentials, bool $force = false): void {}
}
