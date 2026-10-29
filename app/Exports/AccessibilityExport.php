<?php

namespace App\Exports;

use App\Models\ESBTPStudentAccessibilityProfile;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AccessibilityExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithEvents
{
    use Exportable;

    protected int $rowCounter = 0;

    public function __construct(
        protected Collection $rows,
        protected bool $includeFullDescription = false,
    ) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function title(): string
    {
        return 'Accessibilité';
    }

    public function headings(): array
    {
        $h = [
            'N°', 'Matricule', 'Nom', 'Prénoms', 'Classe', 'Filière', 'Niveau',
            'Reconnaissance officielle', 'Référence',
            'Catégories', 'Aménagements',
            'Tiers-temps', 'Tiers-temps (%)', 'Assistant requis',
            'Résumé (visible enseignants)',
            'Validité du', 'Validité au',
        ];

        if ($this->includeFullDescription) {
            $h[] = 'Description médicale complète';
            $h[] = 'Notes aménagements';
        }

        return $h;
    }

    public function map($row): array
    {
        $this->rowCounter++;

        $etudiant = $row['etudiant'];
        $profile = $row['profile'];
        $inscription = $row['inscription'] ?? null;

        $base = [
            $this->rowCounter,
            $etudiant->matricule ?? '—',
            $etudiant->nom ?? '',
            $etudiant->prenoms ?? '',
            $inscription?->classe?->name ?? '—',
            $inscription?->filiere?->name ?? '—',
            $inscription?->niveau?->name ?? '—',
            $profile->has_official_recognition ? 'Oui' : 'Non',
            $profile->recognition_reference ?? '—',
            implode(', ', $profile->categoryLabels()) ?: '—',
            implode(', ', $profile->accommodationLabels()) ?: '—',
            $profile->requires_third_time ? 'Oui' : 'Non',
            $profile->requires_third_time ? $profile->third_time_percentage . '%' : '—',
            $profile->assistant_required ? 'Oui' : 'Non',
            $profile->short_description ?? '—',
            $profile->effective_from?->format('d/m/Y') ?? '—',
            $profile->effective_to?->format('d/m/Y') ?? '—',
        ];

        if ($this->includeFullDescription) {
            $base[] = $profile->full_description ?? '';
            $base[] = $profile->accommodations_notes ?? '';
        }

        return $base;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestColumn = $sheet->getHighestDataColumn();

                $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0453CB']],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '0F1F4B']]],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$highestColumn}1");

                $highestRow = $sheet->getHighestRow();
                if ($highestRow > 1) {
                    $sheet->getStyle("A2:{$highestColumn}{$highestRow}")->getBorders()->getAllBorders()
                        ->setBorderStyle(Border::BORDER_THIN)
                        ->getColor()->setRGB('D1D5DB');
                }
            },
        ];
    }
}
