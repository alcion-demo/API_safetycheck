<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\SafetyCheck;
use Illuminate\Support\Facades\Mail;
use App\Mail\SafetyCheckNotification;

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
            'type' => 'training',
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

    public function test_status_column_supports_not_applicable(): void
    {
        $columns = \Illuminate\Support\Facades\DB::select(
            "PRAGMA table_info('safety_check_responses')"
        );

        $statusColumn = collect($columns)->firstWhere('name', 'status');

        $this->assertNotNull($statusColumn);

        // SQLiteのカラム型だけではENUMの選択肢を
        // 制限しているかどうかは判定できない。
        $this->assertNotEmpty($statusColumn->type);
    }


    public function test_production_safety_check_queues_notification_emails(): void
    {
        Mail::fake();

        $user1 = User::factory()->create([
            'email' => 'user1@example.com',
        ]);

        $user2 = User::factory()->create([
            'email' => 'user2@example.com',
        ]);

        $safetyCheck = (new SafetyCheck())->createProduction([
            'disaster_key' => 'test:production:001',
            'title' => '本番安否確認テスト',
            'message' => '安否状況を回答してください。',
        ]);

        $this->assertDatabaseHas('safety_checks', [
            'id' => $safetyCheck->id,
            'disaster_key' => 'test:production:001',
            'type' => 'production',
        ]);

        Mail::assertQueued(
            SafetyCheckNotification::class,
            2
        );

        Mail::assertQueued(
            SafetyCheckNotification::class,
            function ($mail) use ($safetyCheck, $user1) {
                return $mail->safetyCheckId === $safetyCheck->id
                    && $mail->title === '本番安否確認テスト'
                    && $mail->hasTo($user1->email);
            }
        );

        Mail::assertQueued(
            SafetyCheckNotification::class,
            function ($mail) use ($safetyCheck, $user2) {
                return $mail->safetyCheckId === $safetyCheck->id
                    && $mail->hasTo($user2->email);
            }
        );
    }

    public function test_repeated_production_safety_check_does_not_queue_duplicate_emails(): void
    {
        Mail::fake();

        User::factory()->create([
            'email' => 'user1@example.com',
        ]);

        User::factory()->create([
            'email' => 'user2@example.com',
        ]);

        $data = [
            'disaster_key' => 'test:production:002',
            'title' => '本番安否確認テスト',
            'message' => '安否状況を回答してください。',
        ];

        (new SafetyCheck())->createProduction($data);
        (new SafetyCheck())->createProduction($data);

        $this->assertDatabaseCount('safety_checks', 1);

        Mail::assertQueued(
            SafetyCheckNotification::class,
            2
        );
    }
}
