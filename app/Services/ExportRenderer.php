<?php

namespace App\Services;

use App\Domain\Exploitation\TracesLentes\EnregistreurDeTraces;
use App\Domain\Exports\ExportableReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Renderer unifié pour exports PDF + Excel. Centralise la création DomPDF +
 * Maatwebsite Excel et applique le pattern preview-inline / download-attachment.
 *
 * Cache PDF (Phase 6) : pdfDownload memorise le résultat par cacheKey() du
 * report pour 5 min. Désactivable via setting analytics.exports.cache_enabled.
 */
class ExportRenderer
{
    public const CACHE_TTL_SECONDS = 300;

    /**
     * Stream PDF inline — ouvre dans une nouvelle tab du navigateur (preview).
     * Content-Disposition: inline.
     */
    public function pdfPreview(ExportableReport $report): Response
    {
        $binary = $this->pdfBinaire($report);
        return new Response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $report->filename() . '.pdf"',
        ]);
    }

    /**
     * Force le download PDF (Content-Disposition: attachment).
     */
    public function pdfDownload(ExportableReport $report): Response
    {
        // Le PDF part en cache encodé en base64 : le pilote `database` range la
        // valeur dans une colonne texte, que MySQL strict refuse de remplir avec
        // des octets binaires (« Incorrect string value »). Tous les
        // téléchargements PDF répondaient alors 500, l'aperçu non : lui ne
        // passe pas par le cache. La clé change avec le format, pour qu'aucune
        // ancienne entrée binaire ne soit relue comme du base64.
        $cacheKey = 'exports:pdf:b64:' . $report->cacheKey();
        $binary = $this->cacheEnabled()
            ? base64_decode(Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, fn () => base64_encode($this->pdfBinaire($report))), true)
            : $this->pdfBinaire($report);

        return new Response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $report->filename() . '.pdf"',
        ]);
    }

    /**
     * Force le download Excel (xlsx) via Maatwebsite.
     */
    public function excelDownload(ExportableReport $report): BinaryFileResponse
    {
        return app(EnregistreurDeTraces::class)->mesurer(
            EnregistreurDeTraces::TRAVAIL,
            'export.excel:' . class_basename($report),
            fn () => Excel::download($report->excelExport(), \App\Support\NomDeFichier::sur($report->filename() . '.xlsx')),
        );
    }

    /**
     * Invalide le cache PDF d'un report donné (utile post-recompute analytics).
     */
    public function forgetCache(ExportableReport $report): void
    {
        Cache::forget('exports:pdf:b64:' . $report->cacheKey());
    }

    /**
     * Envoie le report PDF en pièce jointe par email. Queueable si un worker
     * tourne (`QUEUE_CONNECTION != sync`), sinon fallback envoi synchrone pour
     * garantir la délivrance.
     */
    public function emailPdf(ExportableReport $report, string $toEmail, ?string $senderName = null): void
    {
        // Le mailer `mailpulse` refuse toute pièce jointe. Le dire ici, avant de
        // construire le PDF : mis en file, le refus ne tomberait que dans un job
        // échoué, après que l'écran a annoncé « en cours d'envoi ».
        if (\App\Mail\Transport\MailPulseTransport::actif()) {
            throw new \DomainException("L'envoi d'un PDF par e-mail n'est pas disponible : la messagerie de l'établissement ne transporte pas les pièces jointes. Téléchargez le PDF.");
        }

        $binary = $this->pdfBinaire($report);
        $mailable = new \App\Mail\ExportableReportMail(
            reportTitle: $report->title(),
            reportSubtitle: $report->subtitle() ?? '',
            filename: $report->filename(),
            pdfBinary: $binary,
            senderName: $senderName,
        );

        $pendingMail = \Illuminate\Support\Facades\Mail::to($toEmail);
        if (config('queue.default') === 'sync') {
            $pendingMail->send($mailable);
        } else {
            $pendingMail->queue($mailable);
        }
    }

    /** Le rendu DomPDF, mesuré : c'est lui qui coûte, pas la réponse qui l'emporte. */
    private function pdfBinaire(ExportableReport $report): string
    {
        return app(EnregistreurDeTraces::class)->mesurer(
            EnregistreurDeTraces::TRAVAIL,
            'export.pdf:' . class_basename($report),
            fn () => $this->buildPdf($report)->output(),
        );
    }

    private function buildPdf(ExportableReport $report)
    {
        return Pdf::loadView($report->pdfView(), array_merge($report->viewData(), [
            'reportTitle' => $report->title(),
            'reportSubtitle' => $report->subtitle(),
            'reportFilters' => $report->filters(),
        ]))->setPaper($report->paper(), $report->orientation());
    }

    private function cacheEnabled(): bool
    {
        return \App\Helpers\SettingsHelper::drapeau('analytics.exports.cache_enabled', true);
    }
}
