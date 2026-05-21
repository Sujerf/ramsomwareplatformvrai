<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();

            $table->uuid('agent_uuid')->unique();
            $table->string('agent_name');
            $table->string('hostname')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('mac_address')->nullable();

            $table->enum('host_role', [
                'client',
                'file_server',
                'soc_server',
                'attacker_demo',
                'unknown',
            ])->default('unknown');

            $table->enum('status', [
                'active',
                'inactive',
                'compromised',
                'quarantined',
            ])->default('inactive');

            $table->enum('risk_level', [
                'normal',
                'suspect',
                'high',
                'critical',
            ])->default('normal');

            $table->unsignedInteger('risk_score')->default(0);
            $table->boolean('is_isolated')->default(false);
            $table->timestamp('last_seen_at')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['status', 'risk_level']);
            $table->index('ip_address');
            $table->index('hostname');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
