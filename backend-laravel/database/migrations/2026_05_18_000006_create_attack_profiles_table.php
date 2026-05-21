<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attack_profiles', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();

            $table->boolean('is_simulation')->default(false);
            $table->boolean('is_enabled')->default(true);

            $table->json('indicators')->nullable();

            $table->timestamps();

            $table->index(['is_simulation', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attack_profiles');
    }
};
