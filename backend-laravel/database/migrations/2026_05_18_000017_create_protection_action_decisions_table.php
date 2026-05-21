<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('protection_action_decisions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('protection_action_id')
                ->constrained('protection_actions')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->enum('decision', [
                'approved',
                'rejected',
                'cancelled',
                'executed',
                'rolled_back',
            ]);

            $table->text('comment')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamp('decided_at')->nullable();

            $table->timestamps();

            $table->index(['decision', 'decided_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('protection_action_decisions');
    }
};
