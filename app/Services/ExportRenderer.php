<?php

namespace App\Services;

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
        $pdf = $this->buildPdf($report);
        return new Response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $report->filename() . '.pdf"',
        ]);
    }

    /**
     * Force le download PDF (Content-Disposition: attachment).
     */
    public function pdfDownload(ExportableReport $report): Response
    {
        $cacheKey = 'exports:pdf:' . $report->cacheKey();
        $binary = $this->cacheEnabled()
            ? Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, fn () => $this->buildPdf($report)->output())
            : $this->buildPdf($report)->output();

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
        return Excel::download($report->excelExport(), $report->filename() . '.xlsx');
    }

    /**
     * Invalide le cache PDF d'un report donné (utile post-recompute analytics).
     */
    public function forgetCache(ExportableReport $report): void
    {
        Cache::forget('exports:pdf:' . $report->cacheKey());
    }

    /**
     * Envoie le report PDF en pièce jointe par email. Queueable si un worker
     * tourne (`QUEUE_CONNECTION != sync`), sinon fallback envoi synchrone pour
     * garantir la délivrance.
     */
    public function emailPdf(ExportableReport $report, string $toEmail, ?string $senderName = null): void
    {
        $binary = $this->buildPdf($report)->output();
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
        return (string) \App\Helpers\SettingsHelper::get('analytics.exports.cache_enabled', '1') === '1';
    }
}
