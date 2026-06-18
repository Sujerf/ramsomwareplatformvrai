<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExecutiveReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $pdfPath,
        public readonly string $periodLabel,
        public readonly array  $summary,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[RansomShield] Rapport exécutif SOC — '.$this->periodLabel,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.executive-report',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as('rapport-soc-'.\Illuminate\Support\Str::slug($this->periodLabel).'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
