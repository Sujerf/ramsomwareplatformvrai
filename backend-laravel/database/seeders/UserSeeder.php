<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@ransomshield.local'],
            [
                'name'     => 'Administrateur SOC',
                'role'     => 'admin',
                'password' => Hash::make('RansomShield@2026!'),
            ]
        );
    }
}
