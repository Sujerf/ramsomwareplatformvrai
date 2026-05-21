<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\AlertNotification;
use App\Models\Incident;
use App\Models\SystemSetting;

class NotificationService
{
    public function notifyAlert(Alert $alert): void
    {
        $alert->loadMissing('incident');

        if ($this->settingBool('notification_ui_enabled', true)) {
            $this->createNotification(
                alert: $alert,
                incident: $alert->incident,
                channel: 'ui',
                subject: $alert->title,
                message: $alert->message,
                metadata: [
                    'risk_level' => $alert->risk_level,
                    'score' => $alert->score,
                    'timeline_message' => 'Notification UI générée pour une alerte.',
                ]
            );
        }

        if ($this->settingBool('notification_sound_enabled', true)) {
            $this->createNotification(
                alert: $alert,
                incident: $alert->incident,
                channel: 'sound',
                subject: 'Alarme RansomShield',
                message: 'Une alerte nécessite votre attention.',
                metadata: [
                    'risk_level' => $alert->risk_level,
                    'score' => $alert->score,
                    'timeline_message' => 'Alarme sonore navigateur préparée.',
                ]
            );
        }

        if (
            $this->settingBool('notification_mail_enabled', false)
            && $this->riskIsAtLeast($alert->risk_level, $this->settingValue('notification_min_risk_level', 'high'))
        ) {
            $recipient = trim((string) $this->settingValue('notification_mail_recipient', ''));

            $this->createNotification(
                alert: $alert,
                incident: $alert->incident,
                channel: 'mail',
                subject: '[RansomShield] ' . $alert->title,
                message: $alert->message,
                recipient: $recipient !== '' ? $recipient : null,
                metadata: [
                    'risk_level' => $alert->risk_level,
                    'score' => $alert->score,
                    'timeline_message' => 'Notification mail préparée en file pending. Aucun envoi réel immédiat.',
                    'mail_dispatch_mode' => 'pending_for_future_mail_worker',
                ]
            );
        }
    }

    public function notifyIncident(Incident $incident, string $message): void
    {
        if (! $this->settingBool('notification_ui_enabled', true)) {
            return;
        }

        $this->createNotification(
            alert: null,
            incident: $incident,
            channel: 'ui',
            subject: 'Incident RansomShield',
            message: $message,
            metadata: [
                'risk_level' => $incident->risk_level,
                'risk_score' => $incident->risk_score,
                'incident_status' => $incident->status,
                'timeline_message' => 'Notification UI générée pour mise à jour incident.',
            ]
        );
    }

    private function createNotification(
        ?Alert $alert,
        ?Incident $incident,
        string $channel,
        string $subject,
        ?string $message,
        ?string $recipient = null,
        array $metadata = []
    ): AlertNotification {
        return AlertNotification::create([
            'alert_id' => $alert?->id,
            'incident_id' => $incident?->id,
            'channel' => $channel,
            'status' => 'pending',
            'recipient' => $recipient,
            'subject' => $subject,
            'message' => $message,
            'metadata' => $metadata,
        ]);
    }

    private function settingValue(string $key, mixed $default = null): mixed
    {
        return SystemSetting::where('key', $key)->value('value') ?? $default;
    }

    private function settingBool(string $key, bool $default = false): bool
    {
        $value = $this->settingValue($key, $default ? '1' : '0');

        return in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
    }

    private function riskIsAtLeast(string $riskLevel, string $minimum): bool
    {
        $order = [
            'normal' => 0,
            'suspect' => 1,
            'high' => 2,
            'critical' => 3,
        ];

        return ($order[$riskLevel] ?? 0) >= ($order[$minimum] ?? 2);
    }
}
