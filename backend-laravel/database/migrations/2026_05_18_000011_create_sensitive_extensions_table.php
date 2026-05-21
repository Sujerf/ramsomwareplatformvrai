<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensitive_extensions', function (Blueprint $table) {
            $table->id();

            $table->string('extension')->unique();
            $table->enum('category', [
                'important',
                'suspicious',
            ]);

            $table->string('label')->nullable();
            $table->boolean('is_enabled')->default(true);

            $table->timestamps();

            $table->index(['category', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensitive_extensions');
    }
};
