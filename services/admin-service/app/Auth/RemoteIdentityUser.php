<?php

namespace App\Auth;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Auth\Authenticatable;

class RemoteIdentityUser implements Authenticatable, FilamentUser, HasAvatar, HasName
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes) {}

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): mixed
    {
        return $this->attributes['id'];
    }

    public function getKey(): mixed
    {
        return $this->getAuthIdentifier();
    }

    public function getKeyName(): string
    {
        return $this->getAuthIdentifierName();
    }

    public function getAttribute(string $key): mixed
    {
        return $this->getAttributeValue($key);
    }

    public function getAttributeValue(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /** @return array<string, mixed> */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void {}

    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }

    public function getFilamentName(): string
    {
        return (string) $this->attributes['display_name'];
    }

    public function getFilamentAvatarUrl(): ?string
    {
        $avatar = $this->attributes['avatar_url'] ?? null;

        return is_string($avatar) && $avatar !== '' ? $avatar : null;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->requiresPasswordChange()) {
            return false;
        }

        return collect($this->permissions())->intersect([
            'identity.users.read', 'identity.org_units.read', 'identity.roles.read',
            'learning.taxonomy.manage', 'learning.questions.moderate', 'learning.documents.create',
            'learning.documents.upload', 'learning.documents.update', 'learning.documents.review',
            'learning.documents.publish', 'learning.documents.archive', 'learning.documents.access.manage',
            'learning.courses.manage', 'ai.curation.review',
        ])->isNotEmpty();
    }

    public function requiresPasswordChange(): bool
    {
        return ($this->attributes['must_change_password'] ?? true) === true;
    }

    /** @return array<int, string> */
    public function permissions(): array
    {
        return $this->attributes['permissions'] ?? [];
    }

    public function can(string $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }

    public function email(): string
    {
        return (string) $this->attributes['email'];
    }

    /** @return array<string, mixed> */
    public function attributes(): array
    {
        return $this->attributes;
    }
}
