<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            [
                'group'       => 'notifications',
                'key'         => 'notification_webhook_enabled',
                'label'       => 'Notifications webhook',
                'value_type'  => 'boolean',
                'value'       => '0',
                'description' => 'Active l\'envoi d\'alertes vers un webhook Slack, Teams ou générique.',
            ],
            [
                'group'       => 'notifications',
                'key'         => 'notification_webhook_url',
                'label'       => 'URL du webhook',
                'value_type'  => 'string',
                'value'       => '',
                'description' => 'URL complète du webhook entrant (Slack Incoming Webhook, Teams connector, n8n, Zapier…).',
            ],
            [
                'group'       => 'notifications',
                'key'         => 'notification_webhook_type',
                'label'       => 'Type de webhook',
                'value_type'  => 'string',
                'value'       => 'slack',
                'description' => 'Format du payload envoyé : slack, teams, ou generic (JSON brut).',
            ],
        ];

        foreach ($settings as $setting) {
            if (DB::table('system_settings')->where('key', $setting['key'])->exists()) {
                continue;
            }

            DB::table('system_settings')->insert(array_merge($setting, [
                'metadata'   => json_encode(['default' => true, 'default_value' => $setting['value']], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        \App\Models\SystemSetting::clearCache();
    }

    public function down(): void
    {
        DB::table('system_settings')->whereIn('key', [
            'notification_webhook_enabled',
            'notification_webhook_url',
            'notification_webhook_type',
        ])->delete();

        \App\Models\SystemSetting::clearCache();
    }
};
