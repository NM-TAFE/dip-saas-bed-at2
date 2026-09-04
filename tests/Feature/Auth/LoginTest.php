<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_log_in(): void
    {
        $this->seed(DatabaseSeeder::class);

        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->postJson('/api/v1/auth/login', [
                'email' => DatabaseSeeder::CLIENT_EMAIL,
                'password' => DatabaseSeeder::CLIENT_PASSWORD,
            ]);

        $response
            ->assertOk()
            ->assertJsonStructure(['token']);
    }

    public function test_login_remains_rate_limited_without_workspace_context(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this
                ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
                ->postJson('/api/v1/auth/login', [
                    'email' => 'user@example.com',
                    'password' => 'wrong-password',
                ])
                ->assertUnprocessable();
        }

        $this
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->postJson('/api/v1/auth/login', [
                'email' => 'user@example.com',
                'password' => 'wrong-password',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }
}
