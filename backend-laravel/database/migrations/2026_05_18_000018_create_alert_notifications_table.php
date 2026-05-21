<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_notifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('alert_id')
                ->nullable()
                ->constrained('alerts')
                ->nullOnDelete();

            $table->foreignId('incident_id')
                ->nullable()
                ->constrained('incidents')
                ->nullOnDelete();

            $table->enum('channel', [
                'ui',
                'sound',
                'mail',
            ]);

            $table->enum('status', [
                'pending',
                'sent',
                'read',
                'failed',
            ])->default('pending');

            $table->string('recipient')->nullable();
            $table->string('subject')->nullable();
            $table->text('message')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->text('failure_reason')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['channel', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_notifications');
    }
};
