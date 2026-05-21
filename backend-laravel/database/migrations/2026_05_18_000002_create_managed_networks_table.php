<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('managed_networks', function (Blueprint $table) {
            $table->id();

            $table->string('name')->nullable();
            $table->string('cidr');
            $table->string('gateway_ip', 45)->nullable();
            $table->string('interface_name')->nullable();

            $table->enum('status', [
                'detected',
                'approved',
                'ignored',
            ])->default('detected');

            $table->boolean('is_scannable')->default(true);
            $table->timestamp('last_scanned_at')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique('cidr');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('managed_networks');
    }
};
