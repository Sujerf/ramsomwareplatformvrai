<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulation_runs', function (Blueprint $table) {
            $table->id();

            $table->uuid('run_uuid')->unique();

            $table->foreignId('agent_id')
                ->nullable()
                ->constrained('agents')
                ->nullOnDelete();

            $table->foreignId('attack_profile_id')
                ->nullable()
                ->constrained('attack_profiles')
                ->nullOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            $table->enum('status', [
                'planned',
                'running',
                'completed',
                'failed',
                'cancelled',
            ])->default('planned');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['status', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulation_runs');
    }
};
