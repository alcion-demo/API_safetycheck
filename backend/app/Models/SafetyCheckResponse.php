<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['safety_check_id', 'user_id', 'status', 'memo',])]
class SafetyCheckResponse extends Model
{

    public function safetyCheck(): BelongsTo
    {
        return $this->belongsTo(SafetyCheck::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 安否確認への回答を保存
     *
     * @param int $userId
     * @param array $data
     * @return SafetyCheckResponse
     */
    public function createResponse(int $safetyCheckId, int $userId, array $data): self
    {
        return self::updateOrCreate(
            [
                'safety_check_id' => $safetyCheckId,
                'user_id' => $userId,
            ],
            [
                'status' => $data['status'],
                'memo' => $data['memo'] ?? null,
            ]
        );
    }

    /**
     * 安否確認への回答一覧を取得
     *
     * @param int $safetyCheckId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getResponsesBySafetyCheckId(int $safetyCheckId)
    {
        return self::with('user')
            ->where('safety_check_id', $safetyCheckId)
            ->get();
    }


    public function test_not_applicable_response_can_be_saved_and_retrieved(): void
    {
        $user = User::factory()->create();

        $safetyCheck = SafetyCheck::create([
            'type' => 'training',
            'title' => '対象地域確認テスト',
            'message' => 'テストメッセージ',
            'status' => 'active',
            'started_at' => now(),
            'ended_at' => null,
            'created_by' => $user->id,
        ]);

        $response = new \App\Models\SafetyCheckResponse();

        $saved = $response->createResponse(
            $safetyCheck->id,
            $user->id,
            [
                'status' => 'not_applicable',
                'memo' => '災害対象外地域',
            ]
        );

        $this->assertDatabaseHas('safety_check_responses', [
            'safety_check_id' => $safetyCheck->id,
            'user_id' => $user->id,
            'status' => 'not_applicable',
            'memo' => '災害対象外地域',
        ]);

        $retrieved = \App\Models\SafetyCheckResponse::findOrFail($saved->id);

        $this->assertSame('not_applicable', $retrieved->status);
        $this->assertSame('災害対象外地域', $retrieved->memo);
    }
}
