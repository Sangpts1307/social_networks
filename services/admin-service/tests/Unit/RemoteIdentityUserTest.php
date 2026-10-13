<?php

namespace Tests\Unit;

use App\Auth\RemoteIdentityUser;
use PHPUnit\Framework\TestCase;

class RemoteIdentityUserTest extends TestCase
{
    public function test_it_adapts_a_remote_identity_principal_for_filament(): void
    {
        $user = new RemoteIdentityUser([
            'id' => 'remote-user-id',
            'email' => 'admin@gmail.com',
            'display_name' => 'Admin',
            'avatar_url' => 'https://example.test/avatar.png',
            'must_change_password' => false,
            'permissions' => ['identity.users.read'],
        ]);

        $this->assertSame('remote-user-id', $user->getAuthIdentifier());
        $this->assertSame('remote-user-id', $user->getKey());
        $this->assertSame('id', $user->getKeyName());
        $this->assertSame('admin@gmail.com', $user->getAttributeValue('email'));
        $this->assertSame('Admin', $user->getFilamentName());
        $this->assertSame('https://example.test/avatar.png', $user->getFilamentAvatarUrl());
        $this->assertTrue($user->can('identity.users.read'));
        $this->assertFalse($user->requiresPasswordChange());
    }
}
