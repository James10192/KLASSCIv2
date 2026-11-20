<?php

namespace App\Domain\Exports\Reports;

use App\Domain\Exports\ExportableReport;
use Maatwebsite\Excel\Concerns\FromCollection;

/**
 * Le relevé de notes détaillé d'une classe (document de travail du jury),
 * assemblé par ReleveDetailleAssembler. Une page par étudiant.
 */
class ReleveDetailleReport extends ExportableReport
{
    public const PDF_MAX_ETUDIANTS = 200;

    public function __construct(private array $payload, private string $cle) {}

    public function title(): string
    {
        return 'Relevé de notes détaillé';
    }

    public function subtitle(): ?string
    {
        return trim(($this->payload['periode'] ?? '').' · '.($this->payload['annee'] ?? ''), ' ·');
    }

    public function pdfView(): string
    {
        return 'esbtp.releves.pdf.releve-detaille';
    }

    public function viewData(): array
    {
        return ['payload' => $this->payload];
    }

    /** Une ligne par note : la même matière, à plat, pour qui veut trier. */
    public function excelExport(): FromCollection
    {
        $lignes = collect([['Matricule', 'Étudiant', 'Code', 'Élément', 'Évaluation', 'Note /20', 'Moyenne']]);
        foreach ($this->payload['etudiants'] as $e) {
            foreach ($e['blocs'] as $bloc) {
                foreach ($bloc['elements'] as $el) {
                    foreach ($el['notes'] ?: [['titre' => '', 'note' => null]] as $n) {
                        $lignes->push([$e['matricule'], $e['nom'], $el['code'], $el['libelle'], $n['titre'], $n['note'], $el['moyenne']]);
                    }
                }
            }
        }

        return new class($lignes) implements FromCollection {
            public function __construct(private $lignes) {}

            public function collection()
            {
                return $this->lignes;
            }
        };
    }

    public function filename(): string
    {
        return 'releve-detaille-'.$this->cle;
    }

    public function cacheKey(): string
    {
        return hash('sha256', $this->pdfView().'|'.$this->cle.'|'.json_encode($this->payload));
    }

    public function depasseLeVolumePdf(): bool
    {
        return count($this->payload['etudiants']) > self::PDF_MAX_ETUDIANTS;
    }
}
