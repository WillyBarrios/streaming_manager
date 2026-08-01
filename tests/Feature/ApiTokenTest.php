<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_revoke_a_mobile_token(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@cinematv.test',
            'password' => 'secret-password',
        ]);

        $login = $this->postJson('/api/token', [
            'email' => $user->email,
            'password' => 'secret-password',
            'device_name' => 'Flutter test device',
        ]);

        $login
            ->assertOk()
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withToken($login->json('token'))
            ->deleteJson('/api/token')
            ->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_invalid_credentials_do_not_create_a_token(): void
    {
        User::factory()->create([
            'email' => 'admin@cinematv.test',
            'password' => 'secret-password',
        ]);

        $this->postJson('/api/token', [
            'email' => 'admin@cinematv.test',
            'password' => 'incorrecta',
            'device_name' => 'Flutter test device',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
