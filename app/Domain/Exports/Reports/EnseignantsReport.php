<?php

namespace App\Domain\Exports\Reports;

use App\Domain\Exports\ExportableReport;
use App\Exports\EnseignantsExport;
use App\Exports\EnseignantsGroupesExport;
use App\Services\Enseignants\PresentationAnnuairePdf;
use Maatwebsite\Excel\Concerns\FromCollection;

final class EnseignantsReport extends ExportableReport
{
    public function __construct(
        private readonly array $rows,
        private readonly array $appliedFilters,
        private readonly array $affectations = [],
        private readonly ?int $totalAffectations = null,
        private readonly string $vue = 'synthese',
    ) {}

    public function title(): string { return 'Répertoire pédagogique'; }
    public function subtitle(): ?string { return 'Enseignants et informations des bulletins BTS'; }
    public function pdfView(): string { return 'esbtp.enseignants.export-pdf'; }
    public function orientation(): string { return 'landscape'; }
    public function filters(): array { return $this->appliedFilters; }

    public function viewData(): array
    {
        return array_merge(
            ['rows' => $this->rows, 'total' => count($this->rows),
                'affectations' => $this->affectations,
                'affectationsTotal' => $this->totalAffectations ?? count($this->affectations)],
            PresentationAnnuairePdf::preparer($this->rows, $this->affectations, $this->vue)
        );
    }

    public function excelExport(): FromCollection
    {
        $annee = (string) ($this->appliedFilters['Année universitaire des bulletins'] ?? '');
        // L'annuaire historique reste disponible avec ses colonnes completes.
        // Les autres documents Excel suivent desormais exactement la vue choisie.
        return $this->vue === 'annuaire'
            ? new EnseignantsExport(array_values(array_filter($this->rows, fn (array $row) =>
                str_starts_with((string) ($row['origine'] ?? 'Enregistré'), 'Enregistré')
            )), $annee)
            : new EnseignantsGroupesExport($this->rows, $this->affectations, $this->vue, $annee);
    }

    public function filename(): string
    {
        return 'enseignants_'.$this->vue.'_'.now()->format('Ymd_His');
    }

    // Ne jamais retourner une liste perimee pendant 5 min apres une edition.
    public function cacheKey(): string
    {
        return hash('sha256', json_encode([
            $this->pdfView(), $this->appliedFilters, $this->rows, $this->affectations, $this->totalAffectations, $this->vue,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
