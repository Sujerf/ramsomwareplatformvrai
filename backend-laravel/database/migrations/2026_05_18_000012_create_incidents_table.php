<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('agent_id')
                ->nullable()
                ->constrained('agents')
                ->nullOnDelete();

            $table->foreignId('attack_profile_id')
                ->nullable()
                ->constrained('attack_profiles')
                ->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->enum('status', [
                'open',
                'investigating',
                'under_review',
                'reopened',
                'contained',
                'resolved',
                'false_positive',
            ])->default('open');

            $table->enum('risk_level', [
                'normal',
                'suspect',
                'high',
                'critical',
            ])->default('suspect');

            $table->unsignedInteger('risk_score')->default(0);

            $table->timestamp('detected_at')->nullable();
            $table->timestamp('contained_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('reopened_at')->nullable();

            $table->foreignId('resolved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['status', 'risk_level']);
            $table->index('detected_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
