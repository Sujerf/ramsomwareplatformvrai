<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detection_rules', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();
            $table->string('name');
            $table->string('event_type')->nullable();

            $table->enum('risk_level', [
                'normal',
                'suspect',
                'high',
                'critical',
            ])->default('suspect');

            $table->unsignedInteger('score_weight')->default(10);
            $table->boolean('is_enabled')->default(true);

            $table->text('description')->nullable();
            $table->json('conditions')->nullable();

            $table->timestamps();

            $table->index(['event_type', 'risk_level', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detection_rules');
    }
};
