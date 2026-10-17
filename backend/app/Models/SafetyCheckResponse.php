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
}
