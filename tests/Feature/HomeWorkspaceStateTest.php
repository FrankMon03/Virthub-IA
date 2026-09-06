<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomeWorkspaceStateTest extends TestCase
{
    public function test_home_state_requires_authenticated_registered_user(): void
    {
        $this->get('/home/state')->assertStatus(403);
        $this->withSession(['_token' => 'test-csrf-token'])->post('/home/state', [
            '_token' => 'test-csrf-token',
            'todos' => [],
            'notes' => '',
            'calendarEvents' => [],
        ])->assertStatus(403);
    }
}