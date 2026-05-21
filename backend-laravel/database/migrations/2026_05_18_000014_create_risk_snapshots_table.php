<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_snapshots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('agent_id')
                ->constrained('agents')
                ->cascadeOnDelete();

            $table->foreignId('incident_id')
                ->nullable()
                ->constrained('incidents')
                ->nullOnDelete();

            $table->unsignedInteger('score')->default(0);

            $table->enum('risk_level', [
                'normal',
                'suspect',
                'high',
                'critical',
            ])->default('normal');

            $table->json('signals')->nullable();
            $table->timestamp('calculated_at')->nullable();

            $table->timestamps();

            $table->index(['agent_id', 'risk_level']);
            $table->index('calculated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_snapshots');
    }
};
