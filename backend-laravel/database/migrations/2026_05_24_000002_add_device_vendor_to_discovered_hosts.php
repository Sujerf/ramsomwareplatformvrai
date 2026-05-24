<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les colonnes de détection du fabricant par OUI (MAC address).
 *
 *  device_vendor   — nom du fabricant (ex : "Apple, Inc.", "Xiaomi Communications")
 *  device_category — catégorie déduite (mobile | apple_device | computer | router | iot | printer | tv | unknown)
 *
 * Alimentées automatiquement par MacVendorService lors de chaque scan réseau.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discovered_hosts', function (Blueprint $table) {
            $table->string('device_vendor', 120)
                  ->nullable()
                  ->after('host_role')
                  ->comment('Fabricant identifié par OUI (ex: Apple, Inc., Xiaomi…)');

            $table->string('device_category', 30)
                  ->nullable()
                  ->after('device_vendor')
                  ->comment('Catégorie : mobile | apple_device | computer | router | iot | printer | tv | unknown');
        });
    }

    public function down(): void
    {
        Schema::table('discovered_hosts', function (Blueprint $table) {
            $table->dropColumn(['device_vendor', 'device_category']);
        });
    }
};
