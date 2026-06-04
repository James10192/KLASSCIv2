<?php

namespace App\Exports;

use App\Helpers\SettingsHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export Excel des paiements au format SAARI (Sage Saari Ligne 100).
 *
 * Format basé sur l'onglet "BNI BKE" du fichier de référence
 * "Export de paiement SAARI ESBTP sur KLASSCI.xlsx" :
 *
 * | A (vide) | B cj | C date | D libelle | E debit | F credit | G n°cmpte | H t | I Colonne1 |
 *
 * - cj : code journal (default "JV" = Journal de Versement, configurable par tenant)
 * - date : date du paiement
 * - libelle : libellé compta (motif + nom étudiant)
 * - debit : montant DÉBIT (0 pour un encaissement)
 * - credit : montant CRÉDIT (montant du paiement validé pour l'école)
 * - n°cmpte : numéro de compte SAARI mappé selon la catégorie de frais
 * - t : type SAARI (vide par défaut)
 * - Colonne1 : numéro d'ordre séquentiel
 *
 * Couleurs : utilise pdf_primary_color du tenant (cohérent avec PaiementsExport).
 */
class PaiementsSaariExport implements FromCollection, WithMapping, WithTitle, WithEvents
{
    protected Collection $paiements;
    protected array $filters;
    protected array $settings;
    protected int $rowCounter = 0;

    public function __construct(Collection $paiements, array $filters = [], array $settings = [])
    {
        $this->paiements = $paiements;
        $this->filters = $filters;
        $this->settings = !empty($settings) ? $settings : $this->loadDefaultSettings();
    }

    public function collection()
    {
        return $this->paiements;
    }

    /**
     * Mapping ligne par ligne au format SAARI.
     * Pas de WithHeadings : les en-têtes sont écrites manuellement dans AfterSheet
     * pour éviter le bug où PhpSpreadsheet ne réécrit pas les cellules avec 0 ou
     * chaîne vide, qui laissait les headings 'debit', 'n°cmpte', 't' apparents
     * dans la première ligne de données.
     */
    public function map($paiement): array
    {
        $this->rowCounter++;

        $cj = $this->settings['saari_code_journal'] ?? 'JV';
        $libelle = $this->buildLibelle($paiement);
        $compte = $this->resolveCompte($paiement);

        return [
            '',                                                              // A vide
            $cj,                                                             // B cj
            $paiement->date_paiement,                                        // C date (Carbon → datetime Excel)
            $libelle,                                                        // D libelle
            0,                                                               // E debit (encaissement = 0)
            (float) ($paiement->montant ?? 0),                               // F credit
            $compte,                                                         // G n°compte
            '',                                                              // H t (vide par défaut)
            $this->rowCounter,                                               // I n°colonne
        ];
    }

    public function title(): string
    {
        return 'BNI BKE';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Sans WithHeadings, le mapping écrit directement à la row 1.
                // On insère 4 lignes en haut pour : bandeau école (R1-R3) + heading row (R4).
                $sheet->insertNewRowBefore(1, 4);

                $highestColumn = 'I';
                $headingRow = 4;
                $dataStartRow = $headingRow + 1;

                $this->renderHeader($sheet, $highestColumn);
                $this->writeHeadingRow($sheet, $headingRow);
                $this->styleHeadingRow($sheet, $headingRow, $highestColumn);

                $dataEndRow = max($sheet->getHighestRow(), $headingRow);

                if ($dataEndRow >= $dataStartRow) {
                    $this->styleDataRows($sheet, $dataStartRow, $dataEndRow, $highestColumn);

                    // Format date colonne C
                    $sheet->getStyle("C{$dataStartRow}:C{$dataEndRow}")
                        ->getNumberFormat()
                        ->setFormatCode('dd/mm/yyyy');

                    // Format montant colonnes E (debit) et F (credit) — comme SAARI standard
                    $sheet->getStyle("E{$dataStartRow}:F{$dataEndRow}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0;-#,##0;""');
                }

                // Largeur colonnes (proche du sample SAARI)
                $sheet->getColumnDimension('A')->setWidth(3);
                $sheet->getColumnDimension('B')->setWidth(6);
                $sheet->getColumnDimension('C')->setWidth(12);
                $sheet->getColumnDimension('D')->setWidth(45);
                $sheet->getColumnDimension('E')->setWidth(14);
                $sheet->getColumnDimension('F')->setWidth(14);
                $sheet->getColumnDimension('G')->setWidth(14);
                $sheet->getColumnDimension('H')->setWidth(6);
                $sheet->getColumnDimension('I')->setWidth(11);

                $sheet->freezePane("A{$dataStartRow}");
                $sheet->setAutoFilter("A{$headingRow}:{$highestColumn}{$headingRow}");
            },
        ];
    }

    /**
     * Écrit la ligne d'en-têtes (thead) manuellement après le bandeau école.
     * Labels exacts demandés par Marcel : cj, date, libelle, debit, credit, n°compte, t, n°colonne.
     */
    private function writeHeadingRow(Worksheet $sheet, int $row): void
    {
        $sheet->setCellValue("A{$row}", '');
        $sheet->setCellValue("B{$row}", 'cj');
        $sheet->setCellValue("C{$row}", 'date');
        $sheet->setCellValue("D{$row}", 'libelle');
        $sheet->setCellValue("E{$row}", 'debit');
        $sheet->setCellValue("F{$row}", 'credit');
        $sheet->setCellValue("G{$row}", 'n°compte');
        $sheet->setCellValue("H{$row}", 't');
        $sheet->setCellValue("I{$row}", 'n°colonne');
    }

    /**
     * Bandeau école (école + sous-titre) sur les 3 premières lignes,
     * avec la couleur primary du tenant (settings.pdf_primary_color).
     */
    private function renderHeader(Worksheet $sheet, string $highestColumn): void
    {
        $primaryRgb = $this->normalizeHex($this->settings['primary_color'] ?? '#0453cb');

        $schoolName = $this->settings['school_name'] ?? config('app.name');
        $sheet->mergeCells("A1:{$highestColumn}1");
        $sheet->setCellValue('A1', Str::upper($schoolName));
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $primaryRgb],
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);

        // Ligne contact (adresse + tel + email)
        $contactParts = array_filter([
            $this->settings['school_address'] ?? null,
            !empty($this->settings['school_phone']) ? 'Tel: ' . $this->settings['school_phone'] : null,
            !empty($this->settings['school_email']) ? 'Email: ' . $this->settings['school_email'] : null,
        ]);
        if (!empty($contactParts)) {
            $sheet->mergeCells("A2:{$highestColumn}2");
            $sheet->setCellValue('A2', implode(' • ', $contactParts));
            $sheet->getStyle('A2')->applyFromArray([
                'font' => [
                    'size' => 10,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $this->lightenHex($primaryRgb, 25)],
                ],
            ]);
            $sheet->getRowDimension(2)->setRowHeight(20);
        }

        // Sous-titre + filtres
        $sheet->mergeCells("A3:{$highestColumn}3");
        $subtitle = 'Export SAARI — Journal des encaissements';
        if (!empty($this->filters['periode_label'])) {
            $subtitle .= ' (' . $this->filters['periode_label'] . ')';
        }
        $subtitle .= ' — Total : ' . number_format((float) $this->paiements->sum('montant'), 0, ',', ' ') . ' FCFA';
        $sheet->setCellValue('A3', $subtitle);
        $sheet->getStyle('A3')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => '0F172A'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E8EDFD'],
            ],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(22);
    }

    private function styleHeadingRow(Worksheet $sheet, int $row, string $highestColumn): void
    {
        $primaryRgb = $this->normalizeHex($this->settings['primary_color'] ?? '#0453cb');

        $range = "A{$row}:{$highestColumn}{$row}";
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $primaryRgb],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '0F1F4B'],
                ],
            ],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(22);
    }

    private function styleDataRows(Worksheet $sheet, int $startRow, int $endRow, string $highestColumn): void
    {
        if ($startRow > $endRow) {
            return;
        }

        $range = "A{$startRow}:{$highestColumn}{$endRow}";

        $sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'font' => [
                'size' => 10,
            ],
        ]);

        // Zebra : 1 ligne sur 2 légèrement teintée
        for ($r = $startRow; $r <= $endRow; $r++) {
            if (($r - $startRow) % 2 === 1) {
                $sheet->getStyle("A{$r}:{$highestColumn}{$r}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F7F8FB'],
                    ],
                ]);
            }
        }

        // Alignements spécifiques au format SAARI
        $sheet->getStyle("B{$startRow}:B{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C{$startRow}:C{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E{$startRow}:F{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("G{$startRow}:G{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("H{$startRow}:H{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("I{$startRow}:I{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    /**
     * Construit le libellé SAARI à partir des données paiement.
     * Format type : "<motif> / <NOM PRENOM>" pour matcher le sample.
     */
    private function buildLibelle($paiement): string
    {
        $motif = trim((string) ($paiement->motif ?? ''));
        if ($motif === '' && $paiement->fraisCategory) {
            $motif = (string) $paiement->fraisCategory->name;
        }
        if ($motif === '') {
            $motif = 'Paiement';
        }

        $etudiant = $paiement->etudiant ?? null;
        $etuLabel = $etudiant
            ? trim(($etudiant->nom ?? '') . ' ' . ($etudiant->prenoms ?? ''))
            : '';

        if ($etuLabel !== '') {
            return mb_substr($motif . ' / ' . $etuLabel, 0, 80);
        }

        return mb_substr($motif, 0, 80);
    }

    /**
     * Résout le numéro de compte SAARI selon la catégorie de frais.
     * Mapping configurable par tenant via setting 'saari_account_mapping' (JSON).
     * Si pas de mapping → compte par défaut 'saari_default_account' (vide par défaut).
     */
    private function resolveCompte($paiement): string
    {
        $mapping = $this->settings['saari_account_mapping'] ?? [];
        $default = $this->settings['saari_default_account'] ?? '';

        if ($paiement->fraisCategory) {
            $catId = (string) $paiement->fraisCategory->id;
            $catName = strtolower((string) $paiement->fraisCategory->name);

            if (isset($mapping[$catId])) {
                return (string) $mapping[$catId];
            }
            foreach ($mapping as $key => $compte) {
                if (! is_numeric($key) && str_contains($catName, strtolower($key))) {
                    return (string) $compte;
                }
            }
        }

        return (string) $default;
    }

    /**
     * Normalise une couleur hex en RGB hex sans dièse (6 caractères).
     */
    private function normalizeHex(string $color): string
    {
        $color = ltrim(trim($color), '#');
        if (strlen($color) === 3) {
            $color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
        }
        return strtoupper(substr($color, 0, 6) ?: '0453CB');
    }

    /**
     * Éclaircit une couleur hex (0-100 % mix avec blanc).
     */
    private function lightenHex(string $hex, int $percent): string
    {
        $percent = max(0, min(100, $percent));
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $r = (int) ($r + (255 - $r) * $percent / 100);
        $g = (int) ($g + (255 - $g) * $percent / 100);
        $b = (int) ($b + (255 - $b) * $percent / 100);
        return sprintf('%02X%02X%02X', $r, $g, $b);
    }

    /**
     * Charge les settings par défaut depuis tenant.
     */
    private function loadDefaultSettings(): array
    {
        $mappingRaw = SettingsHelper::get('saari_account_mapping', '');
        $mapping = [];
        if (is_string($mappingRaw) && $mappingRaw !== '') {
            $decoded = json_decode($mappingRaw, true);
            if (is_array($decoded)) {
                $mapping = $decoded;
            }
        } elseif (is_array($mappingRaw)) {
            $mapping = $mappingRaw;
        }

        return [
            'school_name' => SettingsHelper::get('school_name', config('app.name')),
            'school_address' => SettingsHelper::get('school_address'),
            'school_phone' => SettingsHelper::get('school_phone'),
            'school_email' => SettingsHelper::get('school_email'),
            'primary_color' => SettingsHelper::get('pdf_primary_color', '#0453cb'),
            'saari_code_journal' => SettingsHelper::get('saari_code_journal', 'JV'),
            'saari_default_account' => SettingsHelper::get('saari_default_account', ''),
            'saari_account_mapping' => $mapping,
        ];
    }
}
