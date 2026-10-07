<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Tests\TestCase;

class SafetyCheckAuthorizationTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Permission::create([
            'name' => 'safety-check.create',
            'guard_name' => 'web',
        ]);

        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ])->givePermissionTo('safety-check.create');

        Role::create([
            'name' => 'employee',
            'guard_name' => 'web',
        ]);
    }

    public function test_admin_can_create_safety_check(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)
            ->postJson('/api/safety-checks/test');

        $response
            ->assertOk()
            ->assertJson([
                'message' => '安全確認を作成する権限があります。',
            ]);
    }

    public function test_employee_cannot_create_safety_check(): void
    {
        $user = User::factory()->create();
        $user->assignRole('employee');

        $response = $this->actingAs($user)
            ->postJson('/api/safety-checks/test');

        $response->assertForbidden();
    }
}
