<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE alert_notifications MODIFY COLUMN channel ENUM('ui','sound','mail','webhook') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE alert_notifications MODIFY COLUMN channel ENUM('ui','sound','mail') NOT NULL");
    }
};
