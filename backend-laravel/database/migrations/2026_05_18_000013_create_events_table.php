<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('agent_id')
                ->constrained('agents')
                ->cascadeOnDelete();

            $table->foreignId('incident_id')
                ->nullable()
                ->constrained('incidents')
                ->nullOnDelete();

            $table->string('event_uuid')->nullable()->unique();

            $table->enum('event_type', [
                'file_created',
                'file_modified',
                'file_deleted',
                'file_renamed',
                'extension_detected',
                'ransom_note_detected',
                'shared_folder_access',
                'multi_host_propagation',
                'heartbeat',
                'system',
            ]);

            $table->string('path')->nullable();
            $table->string('old_path')->nullable();
            $table->string('file_extension')->nullable();

            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('file_hash')->nullable();

            $table->unsignedInteger('score')->default(0);

            $table->enum('risk_level', [
                'normal',
                'suspect',
                'high',
                'critical',
            ])->default('normal');

            $table->boolean('is_simulation')->default(false);
            $table->string('simulation_run_uuid')->nullable();

            $table->json('raw_payload')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamp('observed_at')->nullable();

            $table->timestamps();

            $table->index(['event_type', 'risk_level']);
            $table->index(['agent_id', 'observed_at']);
            $table->index('is_simulation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
