<?php

namespace App\Exports;

use App\Services\Enseignants\PresentationAnnuairePdf;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exports coherents avec le document PDF choisi.
 * Les regroupements sont faits uniquement en lecture ; le nom des bulletins
 * demeure un libelle libre, sans rapprochement d'identite automatique.
 */
final class EnseignantsGroupesExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting
{
    private array $lignes = [];

    public function __construct(
        private readonly array $rows,
        private readonly array $affectations,
        private readonly string $vue,
        private readonly string $annee = '',
    ) {
        $presentation = PresentationAnnuairePdf::preparer($rows, $affectations, $vue);
        if ($vue === 'matieres') {
            foreach ($presentation['matieresGroupes'] as $groupe) {
                foreach ($groupe['enseignants'] as $prof) {
                    $this->lignes[] = [
                        $groupe['matiere'], $prof['nom'], $prof['classes'],
                        $prof['nombreClasses'], $prof['S1'], $prof['S2'],
                        $prof['total'], $annee,
                    ];
                }
            }
        } elseif ($vue === 'specialisations') {
            foreach ($presentation['specialisationsGroupes'] as $groupe) {
                foreach ($groupe['enseignants'] as $row) {
                    $this->lignes[] = [
                        $groupe['nom'], $row['matricule'], $row['nom'],
                        $row['regime'], $row['statut'], $row['telephone_affiche'],
                        $row['email_affiche'], $row['origine'],
                    ];
                }
            }
            foreach ($presentation['sansSpecialisation'] as $row) {
                $this->lignes[] = [
                    'À classer', $row['matricule'], $row['nom'],
                    $row['regime'], $row['statut'],
                    PresentationAnnuairePdf::contactValide((string) ($row['telephone'] ?? '')),
                    PresentationAnnuairePdf::contactValide((string) ($row['email'] ?? ''), true),
                    $row['origine'],
                ];
            }
        } elseif ($vue === 'affectations') {
            $affectations = $presentation['affectationsSemestres']['S1'];
            $affectations = array_merge($affectations, $presentation['affectationsSemestres']['S2']);
            usort($affectations, fn ($a, $b) => strnatcasecmp(
                implode('|', [$a['matiere'], $a['nom'], $a['classe'], $a['semestre']]),
                implode('|', [$b['matiere'], $b['nom'], $b['classe'], $b['semestre']])
            ));
            foreach ($affectations as $item) {
                $this->lignes[] = [
                    $item['matiere'], $item['nom'], $item['classe'], $item['semestre'], $annee,
                ];
            }
        } elseif ($vue === 'rapprochement') {
            foreach ($presentation['saisies'] as $row) {
                $this->lignes[] = [
                    $row['nom'], $row['classes'], $row['affectations_nombre'], $row['origine'],
                ];
            }
        } elseif ($vue === 'synthese') {
            foreach ($presentation['repartition'] as $groupe) {
                $this->lignes[] = [
                    $groupe['classe'], $groupe['S1'], $groupe['S2'], $groupe['total'],
                    $groupe['enseignants_saisis'], $annee,
                ];
            }
        }
    }

    public function collection(): Collection
    {
        return collect($this->lignes)->map(fn (array $row) => array_map(
            fn ($value) => is_string($value) ? $this->safeText($value) : $value,
            $row
        ));
    }

    public function headings(): array
    {
        return match ($this->vue) {
            'matieres' => ['Matière du bulletin', 'Professeur saisi', 'Classes et semestres', 'Classes', 'S1', 'S2', 'Affectations', 'Année universitaire'],
            'specialisations' => ['Spécialisation', 'Matricule', 'Enseignant / libellé', 'Régime', 'Statut', 'Téléphone', 'Email', 'Origine'],
            'affectations' => ['Matière du bulletin', 'Professeur saisi', 'Classe', 'Semestre', 'Année universitaire'],
            'rapprochement' => ['Nom saisi sur bulletin', 'Classes', 'Affectations', 'Origine'],
            default => ['Classe', 'S1', 'S2', 'Total affectations', 'Noms distincts', 'Année universitaire'],
        };
    }

    public function columnFormats(): array
    {
        // Matricules, telephones et classes ne doivent pas perdre leurs zeros.
        return match ($this->vue) {
            'specialisations' => ['B' => NumberFormat::FORMAT_TEXT, 'F' => NumberFormat::FORMAT_TEXT, 'G' => NumberFormat::FORMAT_TEXT],
            'matieres' => ['C' => NumberFormat::FORMAT_TEXT],
            'affectations' => ['C' => NumberFormat::FORMAT_TEXT],
            'rapprochement' => ['B' => NumberFormat::FORMAT_TEXT],
            default => ['A' => NumberFormat::FORMAT_TEXT],
        };
    }

    public function styles(Worksheet $sheet)
    {
        $last = Coordinate::stringFromColumnIndex(count($this->headings()));
        $fin = max(2, count($this->lignes) + 1);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:'.$last.$fin);
        $sheet->getStyle('A2:'.$last.$fin)->getAlignment()->setWrapText(true);
        foreach (range(1, count($this->headings())) as $index) {
            $colonne = Coordinate::stringFromColumnIndex($index);
            $sheet->getColumnDimension($colonne)->setWidth(match ($index) {
                1 => 36, 2 => 32, 3 => 55, default => 24,
            });
        }
        return [1 => [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0453CB']],
        ]];
    }

    private function safeText(string $value): string
    {
        return preg_match('/^[=+@\-\t\r]/u', $value) && ! preg_match('/^\+[0-9 ]+$/', $value)
            ? "'".$value : $value;
    }

    public function nombreLignes(): int
    {
        return count($this->lignes);
    }
}
