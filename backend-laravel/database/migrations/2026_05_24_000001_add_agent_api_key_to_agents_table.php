<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute une clé API unique par agent.
 *
 * Modèle de sécurité :
 *   - Générée lors du premier enrôlement réussi (64 chars hex aléatoires)
 *   - Renvoyée dans la réponse /enroll et stockée dans le state local de l'agent
 *   - Transmise ensuite via l'en-tête X-Agent-Secret sur toutes les requêtes
 *   - Le middleware compare en hash_equals → protège contre les timing attacks
 *   - NULL = agent non encore enrôlé OU créé avant cette migration (fallback global)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->string('agent_api_key', 80)
                  ->nullable()
                  ->after('enrollment_token_expires_at')
                  ->comment('Clé API per-agent, générée à l\'enrôlement. NULL = non encore enrôlé.');
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn('agent_api_key');
        });
    }
};
