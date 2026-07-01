<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@fannan.sa',
            'role' => 'admin',
            'is_verified' => true,
            'completed_profile' => true,
            'password' => '12345678'
        ]);
        User::create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'role' => 'admin',
            'is_verified' => true,
            'completed_profile' => true,
            'password' => 'password'
        ]);
    }
}
