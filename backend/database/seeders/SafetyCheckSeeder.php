<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SafetyCheck;
use App\Models\User;

class SafetyCheckSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $admin = User::where('email', 'admin@example.com')->firstOrFail();

        SafetyCheck::create([
            'type' => 'production',
            'title' => '安否確認テスト',
            'message' => '現在の状況を回答してください。',
            'status' => 'active',
            'scheduled_at' => null,
            'started_at' => now(),
            'ended_at' => null,
        ]);
    }
}
