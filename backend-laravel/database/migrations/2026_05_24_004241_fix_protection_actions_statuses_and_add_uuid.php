<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Corrige les incohérences entre les ENUMs MySQL et les valeurs utilisées dans le code.
 *
 * Problèmes originaux :
 *  - execution_status enum = (pending | success | failed)
 *    Mais le code écrit : waiting_approval | executing | executed | rolled_back
 *    → MySQL rejetait silencieusement ces valeurs (ou les mettait à '' sur strict mode)
 *
 *  - action_uuid utilisé dans AgentCommandController::pending() mais colonne absente
 *
 * Correction :
 *  - execution_status → VARCHAR(30) (plus flexible que ENUM)
 *    Valeurs valides : waiting_approval | pending | executing | executed | failed | rolled_back
 *  - approval_status → VARCHAR(20)
 *    Valeurs valides : pending | approved | rejected | cancelled
 *  - action_uuid UUID nullable ajouté ; rempli pour tous les enregistrements existants
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Étape 1 : modifier les colonnes ENUM → VARCHAR ──────────────────────
        Schema::table('protection_actions', function (Blueprint $table) {
            $table->string('execution_status', 30)->default('pending')->change();
            $table->string('approval_status', 20)->default('pending')->change();

            // Ajouter action_uuid (UUID unique par action, utilisé par l'agent)
            $table->uuid('action_uuid')->nullable()->after('id');
        });

        // ── Étape 2 : normaliser les valeurs existantes ──────────────────────────
        // 'success' → 'executed' (alignement avec le reste du code)
        DB::table('protection_actions')
            ->where('execution_status', 'success')
            ->update(['execution_status' => 'executed']);

        // ── Étape 3 : générer un UUID pour toutes les actions existantes ─────────
        DB::table('protection_actions')
            ->whereNull('action_uuid')
            ->orderBy('id')
            ->each(function ($row) {
                DB::table('protection_actions')
                    ->where('id', $row->id)
                    ->update(['action_uuid' => Str::uuid()->toString()]);
            });

        // ── Étape 4 : rendre action_uuid NOT NULL + unique ───────────────────────
        Schema::table('protection_actions', function (Blueprint $table) {
            $table->uuid('action_uuid')->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('protection_actions', function (Blueprint $table) {
            $table->dropUnique(['action_uuid']);
            $table->dropColumn('action_uuid');
            $table->enum('execution_status', ['pending', 'success', 'failed'])->default('pending')->change();
            $table->enum('approval_status', ['pending', 'approved', 'rejected', 'cancelled', 'executed', 'rolled_back'])->default('pending')->change();
        });
    }
};
