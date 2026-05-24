<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix #3 — events.path et events.old_path étaient varchar(255).
 *
 * La validation API acceptait max:2000, créant une troncature silencieuse
 * pour les chemins Windows longs (AppData, chemins imbriqués...).
 * On passe à TEXT (65 535 chars) pour couvrir tous les cas réels.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->text('path')->nullable()->change();
            $table->text('old_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('path')->nullable()->change();
            $table->string('old_path')->nullable()->change();
        });
    }
};
