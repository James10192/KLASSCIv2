<?php

namespace App\Exports;

use App\Helpers\SettingsHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EtudiantsSheetExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithEvents
{
    protected Collection $etudiants;
    protected string $title;
    protected array $settings;
    protected int $rowCounter = 0;

    public function __construct(Collection $etudiants, string $title = 'Étudiants', array $settings = [])
    {
        $this->etudiants = $etudiants;
        $this->title = $title;
        $this->settings = ! empty($settings) ? $settings : $this->loadDefaultSettings();
    }

    public function collection(): Collection
    {
        return $this->etudiants;
    }

    public function headings(): array
    {
        $h = [
            'N°',
            'Matricule',
            'Nom',
            'Prénoms',
            'Sexe',
            'Date de naissance',
            'Lieu de naissance',
            'Téléphone',
            'Email',
            'Classe',
            'Filière',
            'Niveau',
            'Statut',
            'Année universitaire',
        ];

        if ($this->canIncludeAccessibility()) {
            $h[] = 'Accessibilité';
            $h[] = 'Aménagements';
        }

        return $h;
    }

    public function map($item): array
    {
        $this->rowCounter++;

        $etudiant = $item['etudiant'] ?? $item;
        $inscription = $item['inscription'] ?? null;

        $row = [
            $this->rowCounter,
            $etudiant->matricule ?? 'N/A',
            $etudiant->nom ?? '',
            $etudiant->prenoms ?? '',
            $etudiant->sexe ?? 'N/A',
            $etudiant->date_naissance ? $etudiant->date_naissance->format('d/m/Y') : 'N/A',
            $etudiant->lieu_naissance ?? 'N/A',
            $etudiant->telephone ?? 'N/A',
            $etudiant->email_personnel ?? 'N/A',
            $inscription && $inscription->classe ? $inscription->classe->name : 'N/A',
            $inscription && $inscription->filiere ? $inscription->filiere->name : 'N/A',
            $inscription && $inscription->niveau ? $inscription->niveau->name : 'N/A',
            $etudiant->statut ?? 'N/A',
            $inscription && $inscription->anneeUniversitaire ? $inscription->anneeUniversitaire->name : 'N/A',
        ];

        if ($this->canIncludeAccessibility()) {
            $profile = $etudiant->accessibilityProfile ?? null;
            $row[] = $profile ? $profile->summaryBadge() : '—';
            $row[] = $profile ? implode(', ', $profile->accommodationLabels()) : '—';
        }

        return $row;
    }

    private function canIncludeAccessibility(): bool
    {
        $user = auth()->user();
        return $user !== null && $user->can('students.accessibility.export');
    }

    public function title(): string
    {
        return Str::limit($this->title, 31);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->insertNewRowBefore(1, 5);

                $highestColumn = $sheet->getHighestDataColumn();
                $headingRow = 6;
                $dataStartRow = $headingRow + 1;

                $this->renderHeader($sheet, $highestColumn);
                $this->styleHeadingRow($sheet, $headingRow, $highestColumn);

                $dataEndRow = max($sheet->getHighestRow(), $headingRow);

                if ($dataEndRow >= $dataStartRow) {
                    $this->styleDataRows($sheet, $dataStartRow, $dataEndRow, $highestColumn);
                }

                $sheet->freezePane('A7');
                $sheet->setAutoFilter("A{$headingRow}:{$highestColumn}{$headingRow}");
            },
        ];
    }

    private function renderHeader(Worksheet $sheet, string $highestColumn): void
    {
        $schoolName = $this->settings['school_name'] ?? config('app.name');

        // Row 1 : nom établissement (bleu KLASSCI)
        $sheet->mergeCells("A1:{$highestColumn}1");
        $sheet->setCellValue('A1', Str::upper($schoolName));
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0453CB']],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);

        // Row 2 : coordonnées
        $addressParts = array_filter([
            $this->settings['school_address'] ?? null,
            $this->settings['school_city'] ?? null,
        ]);
        $contactParts = array_filter([
            ! empty($addressParts) ? implode(', ', $addressParts) : null,
            isset($this->settings['school_phone']) && $this->settings['school_phone'] ? 'Tél : '.$this->settings['school_phone'] : null,
            isset($this->settings['school_email']) && $this->settings['school_email'] ? 'Email : '.$this->settings['school_email'] : null,
        ]);

        $sheet->mergeCells("A2:{$highestColumn}2");
        $sheet->setCellValue('A2', ! empty($contactParts) ? implode(' • ', $contactParts) : '');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F6FEB']],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(20);

        // Row 3 : titre du tableau
        $sheet->mergeCells("A3:{$highestColumn}3");
        $sheet->setCellValue('A3', 'Liste des Étudiants — '.$this->title);
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '0F172A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8EDFD']],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(22);

        // Row 4 : totaux rapides
        $total = $this->etudiants->count();
        $exportDate = now()->format('d/m/Y H:i');

        $sheet->mergeCells('A4:G4');
        $sheet->setCellValue('A4', "Étudiants : {$total}");
        $sheet->mergeCells("H4:{$highestColumn}4");
        $sheet->setCellValue('H4', "Exporté le : {$exportDate}");
        $sheet->getStyle("A4:{$highestColumn}4")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '1F2937']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F3FF']],
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'C7D2FE']]],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(20);

        // Row 5 : spacer
        $sheet->mergeCells("A5:{$highestColumn}5");
        $sheet->getRowDimension(5)->setRowHeight(6);
    }

    private function styleHeadingRow(Worksheet $sheet, int $row, string $highestColumn): void
    {
        $range = "A{$row}:{$highestColumn}{$row}";
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0453CB']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '0F1F4B']]],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(24);
    }

    private function styleDataRows(Worksheet $sheet, int $startRow, int $endRow, string $highestColumn): void
    {
        if ($startRow > $endRow) {
            return;
        }

        $dataRange = "A{$startRow}:{$highestColumn}{$endRow}";
        $sheet->getStyle($dataRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle($dataRange)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setRGB('D1D5DB');

        for ($row = $startRow; $row <= $endRow; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(18);
            if (($row - $startRow) % 2 === 0) {
                $sheet->getStyle("A{$row}:{$highestColumn}{$row}")
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }
        }

        // N° centré
        $sheet->getStyle("A{$startRow}:A{$endRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function loadDefaultSettings(): array
    {
        return [
            'school_name' => SettingsHelper::get('school_name', config('app.name')),
            'school_address' => SettingsHelper::get('school_address'),
            'school_city' => SettingsHelper::get('school_city'),
            'school_phone' => SettingsHelper::get('school_phone'),
            'school_email' => SettingsHelper::get('school_email'),
        ];
    }
}
