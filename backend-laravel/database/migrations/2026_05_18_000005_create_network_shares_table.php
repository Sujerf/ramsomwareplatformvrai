<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_shares', function (Blueprint $table) {
            $table->id();

            $table->foreignId('agent_id')
                ->nullable()
                ->constrained('agents')
                ->nullOnDelete();

            $table->string('share_name');
            $table->string('share_path');
            $table->string('protocol')->default('smb');
            $table->boolean('is_enabled')->default(true);

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['protocol', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_shares');
    }
};
