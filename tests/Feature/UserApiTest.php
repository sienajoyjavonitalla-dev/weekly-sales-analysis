<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_create_and_update_users(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->getJson('/api/users')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $createResponse = $this->actingAs($admin)->postJson('/api/users', [
            'first_name' => 'Pat',
            'last_name' => 'Analyst',
            'email' => 'pat@example.com',
            'password' => 'password123',
            'role' => 'analyst',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.email', 'pat@example.com')
            ->assertJsonPath('data.role', 'analyst')
            ->assertJsonPath('data.is_active', true);

        $createdId = $createResponse->json('data.id');

        $this->actingAs($admin)
            ->patchJson("/api/users/{$createdId}", [
                'first_name' => 'Patricia',
                'role' => 'admin',
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.first_name', 'Patricia')
            ->assertJsonPath('data.role', 'admin')
            ->assertJsonPath('data.is_active', false);
    }

    public function test_analyst_cannot_manage_users(): void
    {
        $analyst = $this->createAnalyst();

        $this->actingAs($analyst)
            ->getJson('/api/users')
            ->assertForbidden();

        $this->actingAs($analyst)
            ->postJson('/api/users', [
                'first_name' => 'New',
                'last_name' => 'User',
                'email' => 'new@example.com',
                'password' => 'password123',
                'role' => 'analyst',
            ])
            ->assertForbidden();
    }

    public function test_admin_cannot_deactivate_or_demote_self(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->patchJson("/api/users/{$admin->id}", [
                'is_active' => false,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['is_active']);

        $this->actingAs($admin)
            ->patchJson("/api/users/{$admin->id}", [
                'role' => 'analyst',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::query()->create([
            'first_name' => 'In',
            'last_name' => 'Active',
            'email' => 'inactive@example.com',
            'password' => 'password123',
            'role' => 'analyst',
            'is_active' => false,
        ]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])
            ->assertForbidden()
            ->assertJsonPath('message', 'This account is inactive. Contact an administrator.');

        $this->assertGuest();
    }

    private function createAnalyst(): User
    {
        return User::query()->create([
            'first_name' => 'Ann',
            'last_name' => 'Lyst',
            'email' => 'analyst@example.com',
            'password' => 'password',
            'role' => 'analyst',
            'is_active' => true,
        ]);
    }

    private function createAdmin(): User
    {
        return User::query()->create([
            'first_name' => 'Ada',
            'last_name' => 'Min',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}
