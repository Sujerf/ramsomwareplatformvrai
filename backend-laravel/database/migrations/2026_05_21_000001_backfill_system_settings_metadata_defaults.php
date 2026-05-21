<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $defaults = [
        'ui_theme'                   => 'soc_dark',
        'notification_ui_enabled'    => '1',
        'notification_sound_enabled' => '1',
        'notification_mail_enabled'  => '0',
        'notification_mail_recipient'=> '',
        'notification_min_risk_level'=> 'high',
        'protection_execution_enabled' => '1',
        'enable_real_isolation'      => '0',
        'enable_real_process_kill'   => '0',
    ];

    public function up(): void
    {
        foreach ($this->defaults as $key => $defaultValue) {
            $row = DB::table('system_settings')->where('key', $key)->first();

            if ($row && empty($row->metadata)) {
                DB::table('system_settings')
                    ->where('key', $key)
                    ->update([
                        'metadata'   => json_encode(['default_value' => $defaultValue]),
                        'updated_at' => now(),
                    ]);
            }
        }

        $safeCopyRow = DB::table('system_settings')->where('key', 'safe_copy_root')->first();
        if ($safeCopyRow && empty($safeCopyRow->metadata)) {
            DB::table('system_settings')
                ->where('key', 'safe_copy_root')
                ->update([
                    'metadata'   => json_encode(['default_value' => storage_path('app/ransomshield/safe-copies')]),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void {}
};
