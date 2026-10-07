<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use App\Models\User;

#[Fillable(['type', 'title', 'message', 'scheduled_at', 'status', 'started_at', 'ended_at',])]
class SafetyCheck extends Model
{
    use HasFactory;

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];


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

    public function updateTraining(array $data): void
    {
        $this->update($data);
    }

    public function createProduction(array $data)
    {
        // ここに本番作成処理
    }
}
