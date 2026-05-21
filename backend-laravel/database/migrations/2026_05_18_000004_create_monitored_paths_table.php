<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitored_paths', function (Blueprint $table) {
            $table->id();

            $table->foreignId('agent_id')
                ->constrained('agents')
                ->cascadeOnDelete();

            $table->string('path');
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_recursive')->default(true);
            $table->string('description')->nullable();

            $table->timestamps();

            $table->unique(['agent_id', 'path']);
            $table->index('is_enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitored_paths');
    }
};
