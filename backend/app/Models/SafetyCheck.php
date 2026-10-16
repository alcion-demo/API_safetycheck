<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\SafetyCheckNotification;

#[Fillable(['type', 'title', 'message', 'scheduled_at', 'status', 'started_at', 'ended_at',])]
class SafetyCheck extends Model
{
    use HasFactory;

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];


    /**
     * 訓練用安否確認作成
     *
     * @param array $data
     * @return self
     */
    public function createTraining(array $data): self
    {
        return self::create([
            'type' => 'training',
            'title' => $data['title'],
            'message' => $data['message'],
            'scheduled_at' => $data['scheduled_at'],
            'status' => 'scheduled',
        ]);
    }

    /**
     * 訓練用安否確認開始状態の設定
     *
     * @return void
     */
    public function activateTraining(): void
    {
        $this->update([
            'status' => 'active',
            'started_at' => now(),
        ]);

        $users = User::all();

        User::whereNotNull('email')
            ->where('email', '!=', '')
            ->each(function (User $user) {
                Mail::to($user->email)->queue(
                    new SafetyCheckNotification(
                        $this->title,
                        $this->message
                    )
                );
            });
    }

    /**
     * 訓練用安否確認の終了
     *
     * @return void
     */
    public function closeTraining(): void
    {
        $this->update([
            'status' => 'closed',
            'ended_at' => now(),
        ]);
    }

    /**
     * 訓練用安否確認編集
     *
     * @param array $data
     * @return void
     */
    public function updateTraining(array $data): void
    {
        $this->update($data);
    }

    public function createProduction(array $data)
    {
        // ここに本番作成処理
    }
}
