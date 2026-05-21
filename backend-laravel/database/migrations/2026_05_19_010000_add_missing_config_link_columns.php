<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sensitive_extensions', function (Blueprint $table) {
            if (! Schema::hasColumn('sensitive_extensions', 'risk_level')) {
                $table->string('risk_level')->default('suspect')->after('extension');
            }

            if (! Schema::hasColumn('sensitive_extensions', 'score_weight')) {
                $table->integer('score_weight')->default(10)->after('risk_level');
            }

            if (! Schema::hasColumn('sensitive_extensions', 'description')) {
                $table->text('description')->nullable()->after('score_weight');
            }

            if (! Schema::hasColumn('sensitive_extensions', 'metadata')) {
                $table->json('metadata')->nullable()->after('description');
            }
        });

        Schema::table('detection_thresholds', function (Blueprint $table) {
            if (! Schema::hasColumn('detection_thresholds', 'code')) {
                $table->string('code')->unique()->after('id');
            }

            if (! Schema::hasColumn('detection_thresholds', 'name')) {
                $table->string('name')->nullable()->after('code');
            }

            if (! Schema::hasColumn('detection_thresholds', 'risk_level')) {
                $table->string('risk_level')->default('normal')->after('name');
            }

            if (! Schema::hasColumn('detection_thresholds', 'min_score')) {
                $table->integer('min_score')->default(0)->after('risk_level');
            }

            if (! Schema::hasColumn('detection_thresholds', 'max_score')) {
                $table->integer('max_score')->nullable()->after('min_score');
            }

            if (! Schema::hasColumn('detection_thresholds', 'description')) {
                $table->text('description')->nullable()->after('max_score');
            }

            if (! Schema::hasColumn('detection_thresholds', 'metadata')) {
                $table->json('metadata')->nullable()->after('description');
            }
        });

        Schema::table('detection_rules', function (Blueprint $table) {
            if (! Schema::hasColumn('detection_rules', 'metadata')) {
                $table->json('metadata')->nullable();
            }

            if (! Schema::hasColumn('detection_rules', 'description')) {
                $table->text('description')->nullable();
            }
        });

        Schema::table('protection_policies', function (Blueprint $table) {
            if (! Schema::hasColumn('protection_policies', 'metadata')) {
                $table->json('metadata')->nullable();
            }

            if (! Schema::hasColumn('protection_policies', 'description')) {
                $table->text('description')->nullable();
            }
        });

        Schema::table('system_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('system_settings', 'metadata')) {
                $table->json('metadata')->nullable();
            }

            if (! Schema::hasColumn('system_settings', 'label')) {
                $table->string('label')->nullable();
            }

            if (! Schema::hasColumn('system_settings', 'description')) {
                $table->text('description')->nullable();
            }

            if (! Schema::hasColumn('system_settings', 'value_type')) {
                $table->string('value_type')->default('string');
            }
        });
    }

    public function down(): void
    {
        // On ne supprime pas ces colonnes pour éviter de perdre la configuration.
    }
};
