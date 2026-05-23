<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            if (! Schema::hasColumn('agents', 'enrollment_token_expires_at')) {
                $table->timestamp('enrollment_token_expires_at')
                      ->nullable()
                      ->after('enrollment_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            if (Schema::hasColumn('agents', 'enrollment_token_expires_at')) {
                $table->dropColumn('enrollment_token_expires_at');
            }
        });
    }
};
