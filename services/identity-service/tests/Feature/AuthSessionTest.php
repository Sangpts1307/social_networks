<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['identity.gateway_key' => str_repeat('t', 64)]);
        $this->withHeader('X-Identity-Service-Key', str_repeat('t', 64));
    }

    public function test_direct_untrusted_service_is_rejected(): void
    {
        $this->flushHeaders();
        $this->login()->assertUnauthorized();
        $this->withHeader('X-Identity-Service-Key', 'wrong');
        $this->login()->assertUnauthorized();
        config(['identity.gateway_key' => '']);
        $this->login()->assertStatus(503);
    }

    private const PASSWORD = 'Initial!Password2026';

    private function account(array $changes = []): User
    {
        $org = (string) Str::uuid();
        DB::table('organizations')->insert(['id' => $org, 'code' => $org, 'name' => 'Test']);

        return User::create(array_merge(['organization_id' => $org, 'email' => 'user@example.test',
            'display_name' => 'Test', 'password_hash' => Hash::make(self::PASSWORD), 'status' => 'active'], $changes));
    }

    private function login(string $password = self::PASSWORD)
    {
        return $this->postJson('/api/v1/auth/login', ['email' => 'user@example.test', 'password' => $password]);
    }

    private function token(): string
    {
        return $this->login()->assertOk()->json('access_token');
    }

    private function headers(string $token): array
    {
        return ['Authorization' => 'Bearer '.$token];
    }

    public function test_validation_and_unknown_account(): void
    {
        $this->postJson('/api/v1/auth/login', ['email' => 'bad'])->assertUnprocessable();
        $this->login()->assertUnauthorized();
        $this->assertDatabaseCount('user_sessions', 0);
    }

    public function test_login_hashes_token_and_me_returns_only_allowed_fields(): void
    {
        $user = $this->account(['failed_login_count' => 3]);
        $token = $this->token();
        $session = UserSession::firstOrFail();
        $this->assertSame(hash('sha256', $token), $session->session_hash);
        $this->assertNotSame($token, $session->session_hash);
        $this->assertSame(0, $user->fresh()->failed_login_count);
        $this->getJson('/api/v1/me', $this->headers($token))->assertOk()->assertExactJson(['user' => [
            'id' => $user->id, 'organization_id' => $user->organization_id, 'email' => $user->email,
            'display_name' => 'Test', 'must_change_password' => false, 'permissions' => [], 'authorization' => ['version' => 1, 'grants' => [], 'org_unit_ids' => [], 'organization_unit_ids' => []]]]);
    }

    public function test_tenth_failure_locks_without_automatic_unlock(): void
    {
        $user = $this->account();
        for ($n = 1; $n <= 10; $n++) {
            $this->login('wrong')->assertUnauthorized();
            $this->assertSame($n, $user->fresh()->failed_login_count);
        }
        $this->assertNotNull($user->fresh()->locked_at);
        $this->travel(2)->days();
        $this->login()->assertForbidden();
        $this->assertDatabaseCount('user_sessions', 0);
    }

    public function test_inactive_accounts_cannot_login(): void
    {
        $user = $this->account();
        foreach (['suspended', 'terminated', 'locked'] as $status) {
            $user->update(['status' => $status]);
            $this->login()->assertForbidden();
        }
    }

    public function test_ambiguous_email_never_selects_arbitrary_organization(): void
    {
        $this->account();
        $this->account();
        $this->login()->assertUnauthorized();
        $this->assertDatabaseCount('user_sessions', 0);
    }

    public function test_missing_invalid_expired_and_wrong_version_tokens_are_rejected(): void
    {
        $user = $this->account();
        $this->getJson('/api/v1/me')->assertUnauthorized();
        $this->getJson('/api/v1/me', $this->headers('bad'))->assertUnauthorized();
        $this->getJson('/api/v1/me', $this->headers(str_repeat('a', 64)))->assertUnauthorized();
        $token = $this->token();
        UserSession::query()->update(['expires_at' => now()->subMinute()]);
        $this->getJson('/api/v1/me', $this->headers($token))->assertUnauthorized();
        $token = $this->token();
        $user->increment('password_version');
        $this->getJson('/api/v1/me', $this->headers($token))->assertUnauthorized();
    }

    public function test_logout_revokes_only_current_session(): void
    {
        $this->account();
        $first = $this->token();
        $second = $this->token();
        $this->postJson('/api/v1/auth/logout', [], $this->headers($first))->assertNoContent();
        $this->getJson('/api/v1/me', $this->headers($first))->assertUnauthorized();
        $this->getJson('/api/v1/me', $this->headers($second))->assertOk();
    }

    public function test_logout_all_is_scoped_to_current_user(): void
    {
        $this->account();
        $first = $this->token();
        $second = $this->token();
        $other = $this->account(['email' => 'other@example.test']);
        $otherToken = $this->postJson('/api/v1/auth/login', ['email' => $other->email, 'password' => self::PASSWORD])->assertOk()->json('access_token');
        $this->postJson('/api/v1/auth/logout-all', [], $this->headers($first))->assertNoContent();
        foreach ([$first, $second] as $token) {
            $this->getJson('/api/v1/me', $this->headers($token))->assertUnauthorized();
        }
        $this->getJson('/api/v1/me', $this->headers($otherToken))->assertOk();
    }

    public function test_password_change_required_then_revokes_all_old_sessions(): void
    {
        $user = $this->account(['must_change_password_at' => now()]);
        $a = $this->token();
        $b = $this->token();
        $this->getJson('/api/v1/me', $this->headers($a))->assertForbidden()->assertJsonPath('code', 'PASSWORD_CHANGE_REQUIRED');
        $this->getJson('/api/v1/auth/session', $this->headers($a))->assertOk()->assertJsonPath('user.must_change_password', true);
        $new = 'Changed!Password2026';
        $this->postJson('/api/v1/auth/password', ['current_password' => self::PASSWORD, 'password' => $new, 'password_confirmation' => $new], $this->headers($a))->assertOk();
        $this->assertNull($user->fresh()->must_change_password_at);
        $this->assertSame(2, $user->fresh()->password_version);
        foreach ([$a, $b] as $t) {
            $this->getJson('/api/v1/me', $this->headers($t))->assertUnauthorized();
        }
        $this->login()->assertUnauthorized();
        $token = $this->login($new)->assertOk()->json('access_token');
        $this->getJson('/api/v1/me', $this->headers($token))->assertOk();
    }

    public function test_invalid_password_change_leaves_session_and_credential_intact(): void
    {
        $user = $this->account();
        $token = $this->token();
        $hash = $user->password_hash;
        foreach ([
            ['current_password' => 'wrong', 'password' => 'Changed!Password2026', 'password_confirmation' => 'Changed!Password2026'],
            ['current_password' => self::PASSWORD, 'password' => 'weak', 'password_confirmation' => 'weak'],
            ['current_password' => self::PASSWORD, 'password' => str_repeat('á', 40).'Aa1!', 'password_confirmation' => str_repeat('á', 40).'Aa1!'],
            ['current_password' => self::PASSWORD, 'password' => self::PASSWORD, 'password_confirmation' => self::PASSWORD],
            ['current_password' => self::PASSWORD, 'password' => 'Changed!Password2026', 'password_confirmation' => 'mismatch'],
        ] as $data) {
            $this->postJson('/api/v1/auth/password', $data, $this->headers($token))->assertUnprocessable();
        }
        $this->assertSame($hash, $user->fresh()->password_hash);
        $this->getJson('/api/v1/me', $this->headers($token))->assertOk();
    }

    public function test_account_state_changes_invalidate_existing_token(): void
    {
        $user = $this->account();
        $token = $this->token();
        $user->update(['status' => 'suspended']);
        $this->getJson('/api/v1/me', $this->headers($token))->assertUnauthorized();
        $user->update(['status' => 'active', 'locked_at' => now()]);
        $this->getJson('/api/v1/me', $this->headers($token))->assertUnauthorized();
        $user->update(['locked_at' => null]);
        $user->delete();
        $this->getJson('/api/v1/me', $this->headers($token))->assertUnauthorized();
    }
}
