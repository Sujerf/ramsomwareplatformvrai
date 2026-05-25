<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('protection_policies', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();
            $table->string('name');

            $table->string('scope', 80)->default('agent');

            $table->string('risk_level', 80)->default('suspect');

            $table->boolean('alert_only')->default(true);
            $table->boolean('emergency_backup')->default(false);
            $table->boolean('lock_safe_copy')->default(false);
            $table->boolean('isolate_host')->default(false);
            $table->boolean('kill_process')->default(false);
            $table->boolean('restrict_path')->default(false);

            $table->string('execution_mode', 80)->default('manual');

            $table->boolean('is_enabled')->default(true);
            $table->boolean('allow_admin_override')->default(true);

            $table->text('description')->nullable();
            $table->json('configuration')->nullable();

            $table->timestamps();

            /*
             * Nom d'index court obligatoire pour éviter l'erreur MySQL :
             * Identifier name is too long.
             */
            $table->index(
                ['scope', 'risk_level', 'execution_mode', 'is_enabled'],
                'pp_scope_risk_exec_enabled_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('protection_policies');
    }
};