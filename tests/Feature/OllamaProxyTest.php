<?php

namespace Tests\Feature;

use App\Services\JsonUserStore;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OllamaProxyTest extends TestCase
{
    private function setEnvValue(string $key, ?string $value): void
    {
        if ($value === null) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
            return;
        }

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    public function test_ollama_proxy_returns_503_when_not_configured(): void
    {
        $this->setEnvValue('OLLAMA_BASE_URL', '');
        $this->setEnvValue('OLLAMA_MODEL', 'llama3.1');
        $this->setEnvValue('OLLAMA_SYSTEM_PROMPT', 'Responde en español, de forma clara, breve y útil.');

        app(JsonUserStore::class)->bootstrapAdminFromEnv();

        $response = $this->withSession([
            'auth_user' => [
                'username' => env('ADMIN_USERNAME', 'admin'),
                'role' => 'admin',
            ],
        ])->postJson('/ai/ollama', [
            'prompt' => 'Hola',
        ]);

        $response->assertStatus(503);
        $response->assertJsonFragment([
            'error' => 'Configura OLLAMA_BASE_URL en .env para habilitar la IA local.',
        ]);
    }

    public function test_ollama_proxy_denies_non_admin_users(): void
    {
        $this->setEnvValue('OLLAMA_BASE_URL', 'http://ollama.test');
        $this->setEnvValue('OLLAMA_MODEL', 'llama3.1');
        $this->setEnvValue('OLLAMA_SYSTEM_PROMPT', 'Responde en español, de forma clara, breve y útil.');

        $users = app(JsonUserStore::class);
        $users->bootstrapAdminFromEnv();
        $username = 'user_' . uniqid('', true);
        $users->createUser($username, 'Password123!', 'user');

        $response = $this->withSession([
            'auth_user' => [
                'username' => $username,
                'role' => 'user',
            ],
        ])->postJson('/ai/ollama', [
            'prompt' => 'Hola',
        ]);

        $response->assertStatus(403);
        $response->assertJsonFragment([
            'error' => 'Solo el admin puede usar esta IA.',
        ]);
    }

    public function test_ollama_proxy_forwards_prompt_to_ollama(): void
    {
        $this->setEnvValue('OLLAMA_BASE_URL', 'http://ollama.test');
        $this->setEnvValue('OLLAMA_MODEL', 'llama3.1');
        $this->setEnvValue('OLLAMA_SYSTEM_PROMPT', 'Responde en español, de forma clara, breve y útil.');

        app(JsonUserStore::class)->bootstrapAdminFromEnv();

        Http::fake([
            'http://ollama.test/api/generate' => Http::response([
                'response' => 'Respuesta desde Ollama',
            ], 200),
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'username' => env('ADMIN_USERNAME', 'admin'),
                'role' => 'admin',
            ],
        ])->postJson('/ai/ollama', [
            'prompt' => 'Resume este proyecto',
        ]);

        $response->assertOk();
        $response->assertJson([
            'response' => 'Respuesta desde Ollama',
            'model' => 'llama3.1',
        ]);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'http://ollama.test/api/generate'
                && ($request['prompt'] ?? null) === 'Resume este proyecto'
                && ($request['model'] ?? null) === 'llama3.1'
                && ($request['stream'] ?? null) === false;
        });
    }

    public function test_admin_ollama_chat_persists_history(): void
    {
        $this->setEnvValue('OLLAMA_BASE_URL', 'http://ollama.test');
        $this->setEnvValue('OLLAMA_MODEL', 'llama3.1');
        $this->setEnvValue('OLLAMA_SYSTEM_PROMPT', 'Responde en español, de forma clara, breve y útil.');

        $users = app(JsonUserStore::class);
        $users->bootstrapAdminFromEnv();

        Http::fake([
            'http://ollama.test/api/chat' => Http::response([
                'message' => [
                    'content' => 'Respuesta del chat de IA',
                ],
            ], 200),
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'username' => env('ADMIN_USERNAME', 'admin'),
                'role' => 'admin',
            ],
        ])->postJson('/chat/conversation/ollama', [
            'message' => 'Hola IA',
        ]);

        $response->assertCreated();
        $response->assertJsonFragment([
            'model' => 'llama3.1',
        ]);

        $history = $this->withSession([
            'auth_user' => [
                'username' => env('ADMIN_USERNAME', 'admin'),
                'role' => 'admin',
            ],
        ])->getJson('/chat/conversation/ollama');

        $history->assertOk();
        $messages = $history->json('messages');

        $this->assertTrue(collect($messages)->contains(fn (array $message): bool => ($message['from'] ?? '') === env('ADMIN_USERNAME', 'admin') && ($message['message'] ?? '') === 'Hola IA'));
        $this->assertTrue(collect($messages)->contains(fn (array $message): bool => ($message['from'] ?? '') === 'ollama' && ($message['message'] ?? '') === 'Respuesta del chat de IA'));

        Http::assertSent(function ($request): bool {
            return $request->url() === 'http://ollama.test/api/chat'
                && ($request['model'] ?? null) === 'llama3.1'
                && ($request['stream'] ?? null) === false
                && is_array($request['messages'] ?? null);
        });
    }
}

