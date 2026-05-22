<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            AttackProfileSeeder::class,
            DetectionRuleSeeder::class,
            DetectionThresholdSeeder::class,
            ProtectionPolicySeeder::class,
            SystemSettingSeeder::class,
            SensitiveExtensionSeeder::class,
        ]);
    }
}
