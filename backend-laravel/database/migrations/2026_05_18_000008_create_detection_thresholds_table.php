<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detection_thresholds', function (Blueprint $table) {
            $table->id();

            $table->string('key')->unique();
            $table->string('label');
            $table->integer('value');
            $table->string('unit')->nullable();
            $table->text('description')->nullable();

            $table->boolean('is_enabled')->default(true);

            $table->timestamps();

            $table->index('is_enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detection_thresholds');
    }
};
