<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $user->assignRole('admin');
    }
}
