<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('protection_actions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('agent_id')
                ->nullable()
                ->constrained('agents')
                ->nullOnDelete();

            $table->foreignId('incident_id')
                ->nullable()
                ->constrained('incidents')
                ->nullOnDelete();

            $table->foreignId('protection_policy_id')
                ->nullable()
                ->constrained('protection_policies')
                ->nullOnDelete();

            $table->string('action_type');

            $table->enum('decision_mode', [
                'automatic',
                'approval_required',
                'manual',
            ])->default('approval_required');

            $table->enum('execution_status', [
                'pending',
                'success',
                'failed',
            ])->default('pending');

            $table->enum('approval_status', [
                'pending',
                'approved',
                'rejected',
                'cancelled',
                'executed',
                'rolled_back',
            ])->default('pending');

            $table->boolean('is_reversible')->default(false);
            $table->boolean('rollback_available')->default(false);

            $table->text('description')->nullable();
            $table->json('payload')->nullable();
            $table->json('result')->nullable();

            $table->timestamp('proposed_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('rolled_back_at')->nullable();

            $table->foreignId('executed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['decision_mode', 'approval_status']);
            $table->index(['execution_status', 'action_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('protection_actions');
    }
};
