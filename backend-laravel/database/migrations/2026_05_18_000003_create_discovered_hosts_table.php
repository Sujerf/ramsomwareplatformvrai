<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discovered_hosts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('managed_network_id')
                ->constrained('managed_networks')
                ->cascadeOnDelete();

            $table->string('ip_address', 45);
            $table->string('mac_address')->nullable();
            $table->string('hostname')->nullable();

            $table->enum('host_role', [
                'client',
                'file_server',
                'soc_server',
                'attacker_demo',
                'unknown',
            ])->default('unknown');

            $table->enum('discovery_status', [
                'detected',
                'validated',
                'ignored',
                'enrolled',
            ])->default('detected');

            $table->json('open_ports')->nullable();
            $table->json('detected_services')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamp('last_seen_at')->nullable();

            $table->timestamps();

            $table->unique(['managed_network_id', 'ip_address']);
            $table->index(['host_role', 'discovery_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discovered_hosts');
    }
};
