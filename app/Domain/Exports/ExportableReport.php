<?php

namespace App\Domain\Exports;

use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;

/**
 * Contrat d'un rapport exportable (PDF + Excel + preview). Toute nouvelle page
 * qui veut exporter implémente cette classe abstraite via une concrete Report
 * (ex: RecouvrementReport, AnalyticsReport, PaiementsReport).
 *
 * Le service ExportRenderer consomme cette interface uniformément.
 */
abstract class ExportableReport
{
    abstract public function title(): string;
    abstract public function pdfView(): string;
    abstract public function viewData(): array;
    abstract public function excelExport(): FromCollection;

    public function subtitle(): ?string
    {
        return null;
    }

    /**
     * Récap des filtres appliqués pour affichage en bandeau du PDF.
     * Format : ['Filière' => 'BTS Compta', 'Période' => 'Avr 2026', ...]
     *
     * @return array<string, string>
     */
    public function filters(): array
    {
        return [];
    }

    public function paper(): string
    {
        return 'A4';
    }

    public function orientation(): string
    {
        return 'portrait';
    }

    public function filename(): string
    {
        return Str::slug($this->title()) . '_' . now()->format('Ymd_His');
    }

    /**
     * Hash stable des FILTRES pour clé cache PDF. N'inclut PAS viewData
     * (peut peser MB) ni les payloads — le cache est invalide si filtres
     * changent, et expiré au TTL pour les nouveaux calculs prédictifs.
     */
    public function cacheKey(): string
    {
        return hash('sha256', json_encode([
            'view' => $this->pdfView(),
            'filters' => $this->filters(),
        ]));
    }
}
