<?php

namespace Tests\Feature;

use App\Services\JsonUserStore;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InstallWizardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['installation.key' => 'test-install-key']);
    }

    public function test_install_route_requires_the_private_installation_key(): void
    {
        $this->get('/install')->assertNotFound();
        $this->get('/install?key=wrong-key')->assertNotFound();
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

        $response = $this->post('/install', [
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
