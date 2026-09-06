<?php

namespace Tests\Feature;

use App\Services\JsonUserStore;
use Illuminate\Support\Facades\Crypt;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    private bool $usersFileExisted = false;
    private string $usersFileContents = '';

    protected function setUp(): void
    {
        parent::setUp();

        $usersFile = storage_path('app/data/users.json');
        $this->usersFileExisted = file_exists($usersFile);
        $this->usersFileContents = $this->usersFileExisted ? (string) file_get_contents($usersFile) : '';
    }

    protected function tearDown(): void
    {
        $usersFile = storage_path('app/data/users.json');

        if ($this->usersFileExisted) {
            file_put_contents($usersFile, $this->usersFileContents);
        } elseif (file_exists($usersFile)) {
            unlink($usersFile);
        }

        parent::tearDown();
    }

    public function test_user_can_enable_two_factor_and_complete_two_step_login(): void
    {
        $username = 'twofactor_' . bin2hex(random_bytes(4));
        $users = app(JsonUserStore::class);
        $users->createUser($username, 'Password123!', 'admin');

        $this->withSession([
            '_token' => 'test-csrf-token',
            'auth_user' => [
                'username' => $username,
                'role' => 'admin',
            ],
        ])->post('/security/2fa/setup')->assertRedirect('/configuracion');

        $encryptedSecret = (string) $this->app['session.store']->get('two_factor_setup_secret');
        $this->assertNotSame('', $encryptedSecret);

        $secret = Crypt::decryptString($encryptedSecret);
        $code = (new Google2FA())->getCurrentOtp($secret);

        $this->withSession([
            '_token' => 'test-csrf-token',
            'auth_user' => [
                'username' => $username,
                'role' => 'admin',
            ],
            'two_factor_setup_secret' => $encryptedSecret,
        ])->post('/security/2fa/confirm', [
            '_token' => 'test-csrf-token',
            'code' => $code,
        ])->assertRedirect('/configuracion');

        $this->assertTrue($users->hasTwoFactorEnabled($username));

        $recoveryResponse = $this->withSession([
            '_token' => 'test-csrf-token',
            'auth_user' => [
                'username' => $username,
                'role' => 'admin',
            ],
        ])->postJson('/security/2fa/recovery-codes', [
            '_token' => 'test-csrf-token',
            'current_password' => 'Password123!',
            'code' => (new Google2FA())->getCurrentOtp($secret),
        ]);

        $recoveryResponse->assertOk()->assertJsonCount(8, 'recovery_codes');

        $loginResponse = $this->withSession(['_token' => 'test-csrf-token'])->post('/login', [
            '_token' => 'test-csrf-token',
            'username' => $username,
            'password' => 'Password123!',
        ]);

        $loginResponse->assertRedirect('/');
        $this->assertSame($username, $this->app['session.store']->get('two_factor_pending_username'));

        $loginCode = (new Google2FA())->getCurrentOtp($secret);
        $this->withSession([
            '_token' => 'test-csrf-token',
            'two_factor_pending_username' => $username,
        ])->post('/login/2fa', [
            '_token' => 'test-csrf-token',
            'code' => $loginCode,
        ])->assertRedirect('/');
    }
}
