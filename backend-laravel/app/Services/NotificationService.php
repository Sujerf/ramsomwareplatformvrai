<?php

namespace App\Services;

use App\Mail\AlertMail;
use App\Models\Alert;
use App\Models\AlertNotification;
use App\Models\Incident;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function notifyAlert(Alert $alert): void
    {
        $alert->loadMissing('incident');

        // ── Notification UI ───────────────────────────────────────────────────
        if ($this->settingBool('notification_ui_enabled', true)) {
            $this->createNotification(
                alert: $alert,
                incident: $alert->incident,
                channel: 'ui',
                subject: $alert->title,
                message: $alert->message,
                metadata: [
                    'risk_level'       => $alert->risk_level,
                    'score'            => $alert->score,
                    'timeline_message' => 'Notification UI générée pour une alerte.',
                ]
            );
        }

        // ── Alarme sonore ─────────────────────────────────────────────────────
        if ($this->settingBool('notification_sound_enabled', true)) {
            $this->createNotification(
                alert: $alert,
                incident: $alert->incident,
                channel: 'sound',
                subject: 'Alarme RansomShield',
                message: $alert->risk_level,
                metadata: [
                    'risk_level'       => $alert->risk_level,
                    'score'            => $alert->score,
                    'timeline_message' => 'Alarme sonore navigateur déclenchée.',
                ]
            );
        }

        // ── Notification mail (envoi réel) ───────────────────────────────────
        if (
            $this->settingBool('notification_mail_enabled', false)
            && $this->riskIsAtLeast($alert->risk_level, $this->settingValue('notification_min_risk_level', 'high'))
        ) {
            $recipient = trim((string) $this->settingValue('notification_mail_recipient', ''));

            if ($recipient !== '' && filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                $notification = $this->createNotification(
                    alert: $alert,
                    incident: $alert->incident,
                    channel: 'mail',
                    subject: '[RansomShield] '.$alert->title,
                    message: $alert->message,
                    recipient: $recipient,
                    metadata: [
                        'risk_level'       => $alert->risk_level,
                        'score'            => $alert->score,
                        'timeline_message' => 'Envoi email en cours.',
                    ]
                );

                try {
                    Mail::to($recipient)->send(new AlertMail($alert));

                    $notification->update([
                        'status'  => 'sent',
                        'sent_at' => now(),
                        'metadata' => array_merge($notification->metadata ?? [], [
                            'timeline_message' => 'Email envoyé avec succès.',
                        ]),
                    ]);
                } catch (\Throwable $e) {
                    // Ne jamais casser la réponse API sur un échec mail
                    $notification->update([
                        'status'  => 'failed',
                        'metadata' => array_merge($notification->metadata ?? [], [
                            'timeline_message' => 'Échec envoi email : '.$e->getMessage(),
                            'error'            => $e->getMessage(),
                        ]),
                    ]);
                }
            }
        }

        // ── Notification webhook (Slack / Teams / Generic) ───────────────────
        if (
            $this->settingBool('notification_webhook_enabled', false)
            && $this->riskIsAtLeast($alert->risk_level, $this->settingValue('notification_min_risk_level', 'high'))
        ) {
            $webhookUrl = trim((string) $this->settingValue('notification_webhook_url', ''));

            if ($webhookUrl !== '' && filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
                $type = $this->settingValue('notification_webhook_type', 'slack');

                $notification = $this->createNotification(
                    alert: $alert,
                    incident: $alert->incident,
                    channel: 'webhook',
                    subject: $alert->title,
                    message: $alert->message,
                    recipient: $webhookUrl,
                    metadata: [
                        'risk_level'       => $alert->risk_level,
                        'score'            => $alert->score,
                        'webhook_type'     => $type,
                        'timeline_message' => 'Envoi webhook en cours.',
                    ]
                );

                try {
                    $payload = match ($type) {
                        'teams'   => $this->buildTeamsPayload($alert),
                        'generic' => $this->buildGenericPayload($alert),
                        default   => $this->buildSlackPayload($alert),
                    };

                    $response = Http::timeout(10)
                        ->withHeaders(['Content-Type' => 'application/json'])
                        ->post($webhookUrl, $payload);

                    if ($response->successful()) {
                        $notification->update([
                            'status'   => 'sent',
                            'sent_at'  => now(),
                            'metadata' => array_merge($notification->metadata ?? [], [
                                'timeline_message' => 'Webhook envoyé avec succès.',
                                'http_status'      => $response->status(),
                            ]),
                        ]);
                    } else {
                        $notification->update([
                            'status'   => 'failed',
                            'metadata' => array_merge($notification->metadata ?? [], [
                                'timeline_message' => 'Webhook rejeté — HTTP '.$response->status().'.',
                                'http_status'      => $response->status(),
                                'response_body'    => substr($response->body(), 0, 500),
                            ]),
                        ]);
                    }
                } catch (\Throwable $e) {
                    $notification->update([
                        'status'   => 'failed',
                        'metadata' => array_merge($notification->metadata ?? [], [
                            'timeline_message' => 'Échec webhook : '.$e->getMessage(),
                            'error'            => $e->getMessage(),
                        ]),
                    ]);
                }
            }
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
                'risk_level'       => $incident->risk_level,
                'risk_score'       => $incident->risk_score,
                'incident_status'  => $incident->status,
                'timeline_message' => 'Notification UI générée pour mise à jour incident.',
            ]
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  TEST WEBHOOK (depuis l'UI)
    // ──────────────────────────────────────────────────────────────────────────

    public function sendTestWebhook(string $webhookUrl, string $type): array
    {
        $payload = match ($type) {
            'teams'   => $this->buildTeamsTestPayload(),
            'generic' => $this->buildGenericTestPayload(),
            default   => $this->buildSlackTestPayload(),
        };

        try {
            $response = Http::timeout(10)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($webhookUrl, $payload);

            AlertNotification::create([
                'channel'   => 'webhook',
                'status'    => $response->successful() ? 'sent' : 'failed',
                'recipient' => $webhookUrl,
                'subject'   => '[TEST] RansomShield — webhook test',
                'message'   => 'Envoi de test depuis la console SOC.',
                'sent_at'   => $response->successful() ? now() : null,
                'metadata'  => [
                    'webhook_type'     => $type,
                    'is_test'          => true,
                    'http_status'      => $response->status(),
                    'timeline_message' => $response->successful()
                        ? 'Test webhook envoyé avec succès (HTTP '.$response->status().').'
                        : 'Test webhook rejeté (HTTP '.$response->status().').',
                    'response_body'    => substr($response->body(), 0, 500),
                ],
            ]);

            return [
                'success'     => $response->successful(),
                'http_status' => $response->status(),
                'error'       => $response->successful() ? null : 'HTTP '.$response->status(),
            ];
        } catch (\Throwable $e) {
            AlertNotification::create([
                'channel'   => 'webhook',
                'status'    => 'failed',
                'recipient' => $webhookUrl,
                'subject'   => '[TEST] RansomShield — webhook test',
                'message'   => 'Envoi de test depuis la console SOC.',
                'metadata'  => [
                    'webhook_type'     => $type,
                    'is_test'          => true,
                    'timeline_message' => 'Test webhook — exception : '.$e->getMessage(),
                    'error'            => $e->getMessage(),
                ],
            ]);

            return ['success' => false, 'http_status' => null, 'error' => $e->getMessage()];
        }
    }

    private function buildSlackTestPayload(): array
    {
        return [
            'attachments' => [[
                'color'   => '#38bdf8',
                'title'   => '✅ Test RansomShield SOC',
                'text'    => 'Connexion webhook opérationnelle. Vous recevrez les vraies alertes ici dès que le seuil de risque est atteint.',
                'fields'  => [
                    ['title' => 'Type',   'value' => 'Slack Incoming Webhook', 'short' => true],
                    ['title' => 'Statut', 'value' => 'Connecté',               'short' => true],
                ],
                'footer' => 'RansomShield SOC',
                'ts'     => now()->timestamp,
            ]],
        ];
    }

    private function buildTeamsTestPayload(): array
    {
        return [
            '@type'      => 'MessageCard',
            '@context'   => 'http://schema.org/extensions',
            'themeColor' => '38bdf8',
            'summary'    => 'Test RansomShield SOC',
            'sections'   => [[
                'activityTitle'    => '✅ Test RansomShield SOC',
                'activitySubtitle' => 'Connexion webhook opérationnelle.',
                'activityText'     => 'Vous recevrez les vraies alertes ici dès que le seuil de risque est atteint.',
                'facts'            => [
                    ['name' => 'Type',   'value' => 'Microsoft Teams MessageCard'],
                    ['name' => 'Statut', 'value' => 'Connecté'],
                ],
                'markdown' => true,
            ]],
        ];
    }

    private function buildGenericTestPayload(): array
    {
        return [
            'event'      => 'ransomshield.test',
            'title'      => 'Test RansomShield SOC',
            'message'    => 'Connexion webhook opérationnelle.',
            'is_test'    => true,
            'webhook_type' => 'generic',
            'sent_at'    => now()->toIso8601String(),
            'console_url' => url('/console/dashboard'),
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  PAYLOADS WEBHOOK
    // ──────────────────────────────────────────────────────────────────────────

    private function buildSlackPayload(Alert $alert): array
    {
        $agentName  = $alert->agent?->agent_name ?? 'Agent inconnu';
        $consoleUrl = url('/console/alerts/'.$alert->id);
        $color      = $this->riskColor($alert->risk_level);

        return [
            'attachments' => [[
                'color'   => $color,
                'title'   => $alert->title,
                'text'    => $alert->message,
                'fields'  => [
                    ['title' => 'Agent',      'value' => $agentName,                          'short' => true],
                    ['title' => 'Niveau',     'value' => strtoupper($alert->risk_level),      'short' => true],
                    ['title' => 'Score',      'value' => ($alert->score ?? 0).'/100',         'short' => true],
                    ['title' => 'Détecté le', 'value' => now()->format('d/m/Y H:i'),          'short' => true],
                ],
                'actions' => [[
                    'type' => 'button',
                    'text' => 'Voir dans la console →',
                    'url'  => $consoleUrl,
                ]],
                'footer'  => 'RansomShield SOC',
                'ts'      => now()->timestamp,
            ]],
        ];
    }

    private function buildTeamsPayload(Alert $alert): array
    {
        $agentName  = $alert->agent?->agent_name ?? 'Agent inconnu';
        $consoleUrl = url('/console/alerts/'.$alert->id);
        $color      = ltrim($this->riskColor($alert->risk_level), '#');

        return [
            '@type'           => 'MessageCard',
            '@context'        => 'http://schema.org/extensions',
            'themeColor'      => $color,
            'summary'         => $alert->title,
            'sections'        => [[
                'activityTitle'    => '🛡 RansomShield SOC — '.strtoupper($alert->risk_level),
                'activitySubtitle' => $alert->title,
                'activityText'     => $alert->message,
                'facts'            => [
                    ['name' => 'Agent',      'value' => $agentName],
                    ['name' => 'Niveau',     'value' => strtoupper($alert->risk_level)],
                    ['name' => 'Score',      'value' => ($alert->score ?? 0).'/100'],
                    ['name' => 'Détecté le', 'value' => now()->format('d/m/Y H:i')],
                ],
                'markdown' => true,
            ]],
            'potentialAction' => [[
                '@type'   => 'OpenUri',
                'name'    => 'Voir dans la console',
                'targets' => [['os' => 'default', 'uri' => $consoleUrl]],
            ]],
        ];
    }

    private function buildGenericPayload(Alert $alert): array
    {
        return [
            'event'       => 'ransomshield.alert',
            'title'       => $alert->title,
            'message'     => $alert->message,
            'risk_level'  => $alert->risk_level,
            'score'       => $alert->score ?? 0,
            'agent_name'  => $alert->agent?->agent_name,
            'agent_ip'    => $alert->agent?->ip_address,
            'alert_id'    => $alert->id,
            'detected_at' => now()->toIso8601String(),
            'console_url' => url('/console/alerts/'.$alert->id),
        ];
    }

    private function riskColor(string $riskLevel): string
    {
        return match ($riskLevel) {
            'critical' => '#ef4444',
            'high'     => '#f97316',
            'suspect'  => '#eab308',
            default    => '#22c55e',
        };
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
            'alert_id'    => $alert?->id,
            'incident_id' => $incident?->id,
            'channel'     => $channel,
            'status'      => 'pending',
            'recipient'   => $recipient,
            'subject'     => $subject,
            'message'     => $message,
            'metadata'    => $metadata,
        ]);
    }

    private function settingValue(string $key, mixed $default = null): mixed
    {
        return SystemSetting::getCached($key) ?? $default;
    }

    private function settingBool(string $key, bool $default = false): bool
    {
        $value = $this->settingValue($key, $default ? '1' : '0');

        return in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
    }

    private function riskIsAtLeast(string $riskLevel, string $minimum): bool
    {
        $order = ['normal' => 0, 'suspect' => 1, 'high' => 2, 'critical' => 3];

        return ($order[$riskLevel] ?? 0) >= ($order[$minimum] ?? 2);
    }
}
