<?php

namespace App\Mail;

use App\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Alert $alert,
    ) {}

    public function envelope(): Envelope
    {
        $risk = strtoupper($this->alert->risk_level);
        $agent = $this->alert->agent?->agent_name ?? 'Agent inconnu';

        return new Envelope(
            subject: "[RansomShield] ⚠ Alerte {$risk} — {$agent}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.alert',
        );
    }
}
