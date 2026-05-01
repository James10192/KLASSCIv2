<?php

namespace App\Mail;

use App\Domain\Exports\ExportableReport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email contenant un export PDF en pièce jointe. Queueable pour ne pas bloquer
 * la requête HTTP si le rendu PDF est lent.
 *
 * Usage : Mail::to($address)->queue(new ExportableReportMail($report, $pdfBinary))
 */
class ExportableReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $reportTitle,
        public readonly string $reportSubtitle,
        public readonly string $filename,
        public readonly string $pdfBinary,
        public readonly ?string $senderName = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[KLASSCI] ' . $this->reportTitle,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.exportable-report',
            with: [
                'reportTitle' => $this->reportTitle,
                'reportSubtitle' => $this->reportSubtitle,
                'senderName' => $this->senderName,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfBinary, $this->filename . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
