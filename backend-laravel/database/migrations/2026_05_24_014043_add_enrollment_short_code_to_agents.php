<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute enrollment_short_code : code alphanumérique 8 chars (ex: "a3f9bc12")
     * utilisé pour l'URL courte /e/{code} → beaucoup plus facile à taper dans
     * un terminal KVM où le copier-coller ne fonctionne pas.
     *
     * Exemple : curl http://10.20.0.1:8080/e/a3f9bc12 | sudo bash
     * vs       curl http://10.20.0.1:8080/api/agent/bootstrap/c075615a-abaa-... | sudo bash
     */
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->string('enrollment_short_code', 8)
                  ->nullable()
                  ->unique()
                  ->after('agent_uuid')
                  ->comment('Code court 8 chars pour URL /e/{code} — copier-coller-free');
        });

        // Générer un code pour tous les agents existants
        foreach (\DB::table('agents')->get() as $agent) {
            do {
                $code = substr(str_replace('-', '', $agent->agent_uuid), 0, 8);
                // Si collision (peu probable), on prend des chars différents
                $exists = \DB::table('agents')->where('enrollment_short_code', $code)->exists();
                if ($exists) {
                    $code = strtolower(\Illuminate\Support\Str::random(8));
                }
            } while ($exists);

            \DB::table('agents')->where('id', $agent->id)->update(['enrollment_short_code' => $code]);
        }
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn('enrollment_short_code');
        });
    }
};
