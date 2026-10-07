<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\SafetyCheck;

class SafetyCheckControllerTest extends TestCase
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

    public function test_authenticated_user_can_get_safety_checks(): void
    {
        $user = User::factory()->create();

        SafetyCheck::create([
            'title' => 'テスト安否確認',
            'message' => 'テストメッセージです。',
            'status' => 'active',
            'started_at' => now(),
            'ended_at' => null,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/safety-checks');

        $response
            ->assertOk()
            ->assertJsonFragment([
                'title' => 'テスト安否確認',
                'status' => 'active',
            ]);
    }
}
