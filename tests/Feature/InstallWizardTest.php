<?php

namespace Tests\Feature;

use App\Services\JsonUserStore;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InstallWizardTest extends TestCase
{
    private bool $usersFileExisted = false;
    private string $usersFileContents = '';

    protected function setUp(): void
    {
        parent::setUp();

        config(['installation.key' => 'test-install-key']);

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

    public function test_install_route_requires_the_private_installation_key(): void
    {
        $this->get('/install')->assertNotFound();
        $this->get('/install?key=wrong-key')->assertNotFound();
    }

    public function test_fresh_local_installation_is_available_without_a_configured_key(): void
    {
        $usersFile = storage_path('app/data/users.json');

        if (file_exists($usersFile)) {
            unlink($usersFile);
        }

        config(['installation.key' => '']);

        $this->withServerVariables(['HTTP_HOST' => 'localhost'])->get('/install')->assertOk();
    }

    public function test_home_route_does_not_create_an_admin_account_when_users_store_is_empty(): void
    {
        $usersFile = storage_path('app/data/users.json');

        if (file_exists($usersFile)) {
            unlink($usersFile);
        }

        $this->get('/')->assertOk();

        $this->assertNull(app(JsonUserStore::class)->findByUsername('admin'));
    }

    public function test_install_route_can_create_an_admin_account_with_a_custom_password(): void
    {
        $usersFile = storage_path('app/data/users.json');

        if (file_exists($usersFile)) {
            unlink($usersFile);
        }

        $this->get('/install?key=test-install-key')->assertOk();

        $response = $this->withSession(['_token' => 'test-csrf-token'])->post('/install', [
            '_token' => 'test-csrf-token',
            'key' => 'test-install-key',
            'admin_username' => 'superadmin',
            'admin_password' => 'P@ssword123!',
            'admin_password_confirmation' => 'P@ssword123!',
        ]);

        $response->assertRedirect('/');

        $user = app(JsonUserStore::class)->findByUsername('superadmin');

        $this->assertNotNull($user);
        $this->assertSame('admin', $user['role']);
        $this->assertTrue(Hash::check('P@ssword123!', (string) ($user['password_hash'] ?? '')));
    }
}
