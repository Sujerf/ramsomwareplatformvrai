<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Bug L — incident_uuid / alert_uuid : dead code → colonnes manquantes
 *
 * AgentRiskService génère Str::uuid() pour 'incident_uuid' et 'alert_uuid'
 * à chaque création, mais :
 *   1. Ces colonnes n'existent pas dans le schéma MySQL.
 *   2. Elles ne sont pas dans $fillable des modèles Incident / Alert.
 * Résultat : les UUIDs sont générés, silencieusement ignorés par Laravel
 * (mass-assignment protection), jamais persistés.
 *
 * Impact :
 *   - Tout code qui lit $incident->incident_uuid ou $alert->alert_uuid
 *     obtient null — null pointer si non protégé.
 *   - Toute future API REST/externe qui référence les ressources par UUID
 *     (au lieu de l'id auto-incrémenté) ne fonctionnerait pas.
 *   - Les logs et notifications affichent des UUID vides.
 *
 * Fix :
 *   - Ajouter incident_uuid VARCHAR(36) UNIQUE sur la table incidents.
 *   - Ajouter alert_uuid    VARCHAR(36) UNIQUE sur la table alerts.
 *   - Backfiller les enregistrements existants avec des UUID v4.
 *   - $fillable mis à jour dans les modèles (voir Incident.php / Alert.php).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->uuid('incident_uuid')->nullable()->unique()->after('id');
        });

        Schema::table('alerts', function (Blueprint $table) {
            $table->uuid('alert_uuid')->nullable()->unique()->after('id');
        });

        // ── Backfill des enregistrements existants ────────────────────────────
        // On ne peut pas faire un UPDATE ... SET uuid=UUID() groupé facilement
        // sans risque de collision — on itère en PHP pour garantir l'unicité.
        DB::table('incidents')->whereNull('incident_uuid')->orderBy('id')->each(function ($row) {
            DB::table('incidents')->where('id', $row->id)->update([
                'incident_uuid' => (string) Str::uuid(),
            ]);
        });

        DB::table('alerts')->whereNull('alert_uuid')->orderBy('id')->each(function ($row) {
            DB::table('alerts')->where('id', $row->id)->update([
                'alert_uuid' => (string) Str::uuid(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn('incident_uuid');
        });

        Schema::table('alerts', function (Blueprint $table) {
            $table->dropColumn('alert_uuid');
        });
    }
};
