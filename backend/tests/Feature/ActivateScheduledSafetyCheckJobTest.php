<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Jobs\ActivateScheduledSafetyCheckJob;
use App\Models\SafetyCheck;

class ActivateScheduledSafetyCheckJobTest extends TestCase
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

    public function test_activateExpiredTraining(): void
    {
        $safetyCheck = SafetyCheck::create([
            'type' => 'training',
            'title' => 'テスト訓練',
            'message' => 'テストメッセージ',
            'scheduled_at' => now()->subMinute(),
            'status' => 'scheduled',
        ]);

        (new ActivateScheduledSafetyCheckJob)->handle();

        $safetyCheck->refresh();

        $this->assertSame('active', $safetyCheck->status);
        $this->assertNotNull($safetyCheck->started_at);
    }

    public function test_activates_due_training_safety_check(): void
    {
        $safetyCheck = SafetyCheck::create([
            'type' => 'training',
            'title' => '未来のテスト訓練',
            'message' => '未来のテストメッセージ',
            'scheduled_at' => now()->addMinute(),
            'status' => 'scheduled',
        ]);

        (new ActivateScheduledSafetyCheckJob)->handle();

        $safetyCheck->refresh();

        $this->assertSame('scheduled', $safetyCheck->status);
        $this->assertNull($safetyCheck->started_at);
    }

    public function test_does_not_activate_production_safety_check(): void
    {
        $safetyCheck = SafetyCheck::create([
            'type' => 'production',
            'title' => '本番安否確認',
            'message' => '本番テスト',
            'scheduled_at' => now()->subMinute(),
            'status' => 'scheduled',
        ]);

        (new ActivateScheduledSafetyCheckJob)->handle();

        $safetyCheck->refresh();

        $this->assertSame('scheduled', $safetyCheck->status);
        $this->assertNull($safetyCheck->started_at);
    }
}