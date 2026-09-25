<?php

namespace App\Domain\Exports\Reports;

use App\Domain\Exports\ExportableReport;
use App\Exports\FeuilleRendezVousExport;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;

/**
 * Feuille de suivi des rendez-vous, a imprimer pour le guichet : une page par
 * jour, une case « Reçue » et une colonne d'observations a remplir a la main.
 */
class FeuilleRendezVousReport extends ExportableReport
{
    public const PDF_MAX_ROWS = 1000;

    public const EXCEL_MAX_ROWS = 50000;

    /** @param  list<array<string, mixed>>  $lignes */
    public function __construct(
        private readonly array $lignes,
        private readonly Carbon $debut,
        private readonly Carbon $fin,
        private readonly bool $jourSeul,
    ) {
    }

    public function title(): string
    {
        return 'Suivi des rendez-vous';
    }

    public function subtitle(): ?string
    {
        return $this->jourSeul
            ? 'Rendez-vous d\'inscription du '.$this->debut->translatedFormat('l j F Y')
            : 'Rendez-vous d\'inscription du '.$this->debut->translatedFormat('j F').' au '.$this->fin->translatedFormat('j F Y');
    }

    public function filters(): array
    {
        return [
            'Familles attendues' => (string) count($this->lignes),
            'Arrêtée le' => now()->format('d/m/Y H:i'),
        ];
    }

    public function pdfView(): string
    {
        return 'esbtp.rendez-vous.pdf.feuille-suivi';
    }

    public function viewData(): array
    {
        return ['lignes' => $this->lignes, 'sousTitre' => $this->subtitle()];
    }

    public function excelExport(): FromCollection
    {
        return new FeuilleRendezVousExport($this->lignes);
    }

    public function orientation(): string
    {
        return 'landscape';
    }

    public function filename(): string
    {
        return 'suivi-rendez-vous_'.$this->debut->format('Ymd')
            .($this->jourSeul ? '' : '-'.$this->fin->format('Ymd'));
    }

    /**
     * La feuille change sans qu'aucun filtre ne bouge (une famille recue, une
     * reservation de plus) : la cle porte le contenu.
     */
    public function cacheKey(): string
    {
        return hash('sha256', $this->pdfView().'|'.json_encode($this->lignes, JSON_UNESCAPED_UNICODE));
    }
}
