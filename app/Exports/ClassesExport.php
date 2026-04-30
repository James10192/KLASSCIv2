<?php

namespace App\Exports;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPAnneeUniversitaire;
use Illuminate\Support\Collection;
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

class ClassesExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithEvents
{
    protected $classes;
    protected $anneeCourante;
    protected $filters;
    protected $settings;
    protected $rowCounter = 0;

    public function __construct(Collection $classes, ?ESBTPAnneeUniversitaire $anneeCourante = null, array $filters = [], array $settings = [])
    {
        $this->classes = $classes;
        $this->anneeCourante = $anneeCourante;
        $this->filters = $filters;
        $this->settings = !empty($settings) ? $settings : $this->loadDefaultSettings();
    }

    /**
     * Collection des classes à exporter
     */
    public function collection()
    {
        return $this->classes;
    }

    /**
     * En-têtes des colonnes
     */
    public function headings(): array
    {
        return [
            'N°',
            'Nom de la classe',
            'Code classe',
            'Filière',
            'Niveau d\'étude',
            'Effectif actuel',
            'Capacité maximale',
            'Places restantes',
            'Taux de remplissage (%)',
            'Statut'
        ];
    }

    /**
     * Mapper chaque classe vers une ligne
     */
    public function map($classe): array
    {
        $this->rowCounter++;

        // Calculer l'effectif actuel
        $effectifActuel = $classe->inscriptions()
            ->where('status', '!=', 'annulée')
            ->count();

        $capaciteMax = $classe->places_totales ?? 0;
        $placesRestantes = max(0, $capaciteMax - $effectifActuel);
        $tauxRemplissage = $capaciteMax > 0 ? round(($effectifActuel / $capaciteMax) * 100, 2) : 0;

        return [
            $this->rowCounter,
            $classe->name ?? 'N/A',
            $classe->code ?? 'N/A',
            $classe->filiere ? $classe->filiere->name : 'N/A',
            $classe->niveau ? $classe->niveau->name : 'N/A',
            $effectifActuel,
            $capaciteMax,
            $placesRestantes,
            $tauxRemplissage,
            $classe->is_active ? 'Active' : 'Inactive'
        ];
    }

    /**
     * Titre de la feuille Excel
     */
    public function title(): string
    {
        return 'Liste des Classes';
    }

    /**
     * Événements pour ajouter des totaux et informations
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Insérer 5 lignes en haut pour l'en-tête
                $sheet->insertNewRowBefore(1, 5);

                $highestColumn = $sheet->getHighestDataColumn();
                $headingRow = 6;
                $dataStartRow = $headingRow + 1;

                // Ajouter l'en-tête
                $this->renderHeader($sheet, $highestColumn);

                // Styliser la ligne d'en-têtes
                $this->styleHeadingRow($sheet, $headingRow, $highestColumn);

                $dataEndRow = max($sheet->getHighestRow(), $headingRow);

                if ($dataEndRow >= $dataStartRow) {
                    // Styliser les lignes de données
                    $this->styleDataRows($sheet, $dataStartRow, $dataEndRow, $highestColumn);
                }

                // Figer les volets
                $sheet->freezePane('A7');

                // Ajouter le filtre auto
                $sheet->setAutoFilter("A{$headingRow}:{$highestColumn}{$headingRow}");

                // Ajouter les statistiques
                $currentRow = $dataEndRow;
                $currentRow = $this->addStatistics($sheet, $currentRow, $highestColumn);

                // Ajouter les informations sur les filtres
                $this->addFiltersInfo($sheet, $currentRow, $highestColumn);
            },
        ];
    }

    /**
     * Ajoute le bandeau d'en-tête avec les informations de l'établissement
     */
    private function renderHeader($sheet, $highestColumn)
    {
        $schoolName = $this->settings['nom'] ?? 'KLASSCI';
        $anneeName = $this->anneeCourante ? $this->anneeCourante->name : 'Année en cours';

        // Ligne 1: Nom de l'établissement
        $sheet->setCellValue('A1', $schoolName);
        $sheet->mergeCells("A1:{$highestColumn}1");
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => '0453CB']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);

        // Ligne 2: Titre du document
        $sheet->setCellValue('A2', 'LISTE DES CLASSES');
        $sheet->mergeCells("A2:{$highestColumn}2");
        $sheet->getStyle('A2')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);

        // Ligne 3: Année universitaire
        $sheet->setCellValue('A3', "Année Universitaire : {$anneeName}");
        $sheet->mergeCells("A3:{$highestColumn}3");
        $sheet->getStyle('A3')->applyFromArray([
            'font' => [
                'italic' => true,
                'size' => 11
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);

        // Ligne 4: Date d'export
        $dateExport = now()->format('d/m/Y à H:i');
        $sheet->setCellValue('A4', "Généré le : {$dateExport}");
        $sheet->mergeCells("A4:{$highestColumn}4");
        $sheet->getStyle('A4')->applyFromArray([
            'font' => [
                'size' => 9,
                'color' => ['rgb' => '666666']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);

        // Ligne 5: Vide (espacement)
        $sheet->getRowDimension(5)->setRowHeight(10);
    }

    /**
     * Styliser la ligne d'en-têtes des colonnes
     */
    private function styleHeadingRow($sheet, $headingRow, $highestColumn)
    {
        $sheet->getStyle("A{$headingRow}:{$highestColumn}{$headingRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0453CB']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'FFFFFF']
                ]
            ]
        ]);

        $sheet->getRowDimension($headingRow)->setRowHeight(25);
    }

    /**
     * Styliser les lignes de données
     */
    private function styleDataRows($sheet, $startRow, $endRow, $highestColumn)
    {
        // Bordures sur toutes les cellules de données
        $sheet->getStyle("A{$startRow}:{$highestColumn}{$endRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC']
                ]
            ]
        ]);

        // Alignement
        $sheet->getStyle("A{$startRow}:A{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // N°
        $sheet->getStyle("B{$startRow}:E{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // Texte
        $sheet->getStyle("F{$startRow}:I{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Nombres
        $sheet->getStyle("J{$startRow}:J{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Statut

        // Alterner les couleurs des lignes
        for ($row = $startRow; $row <= $endRow; $row++) {
            if (($row - $startRow) % 2 == 1) {
                $sheet->getStyle("A{$row}:{$highestColumn}{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F8F9FA']
                    ]
                ]);
            }
        }
    }

    /**
     * Ajouter les statistiques en bas du tableau
     */
    private function addStatistics($sheet, $lastDataRow, $highestColumn): int
    {
        $currentRow = $lastDataRow + 2;

        // Titre section statistiques
        $sheet->setCellValue("A{$currentRow}", 'STATISTIQUES GLOBALES');
        $sheet->mergeCells("A{$currentRow}:{$highestColumn}{$currentRow}");
        $sheet->getStyle("A{$currentRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => '0453CB']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E8F4FC']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER
            ]
        ]);
        $currentRow++;

        // Calculer les statistiques
        $totalClasses = $this->classes->count();
        $classesActives = $this->classes->where('is_active', true)->count();
        $classesInactives = $this->classes->where('is_active', false)->count();

        $totalEffectif = 0;
        $totalCapacite = 0;
        foreach ($this->classes as $classe) {
            $effectif = $classe->inscriptions()->where('status', '!=', 'annulée')->count();
            $totalEffectif += $effectif;
            $totalCapacite += $classe->places_totales ?? 0;
        }

        $tauxMoyenRemplissage = $totalCapacite > 0 ? round(($totalEffectif / $totalCapacite) * 100, 2) : 0;

        // Afficher les stats
        $stats = [
            ['Label' => 'Nombre total de classes', 'Valeur' => $totalClasses],
            ['Label' => 'Classes actives', 'Valeur' => $classesActives],
            ['Label' => 'Classes inactives', 'Valeur' => $classesInactives],
            ['Label' => 'Effectif total', 'Valeur' => $totalEffectif . ' étudiants'],
            ['Label' => 'Capacité totale', 'Valeur' => $totalCapacite . ' places'],
            ['Label' => 'Taux de remplissage moyen', 'Valeur' => $tauxMoyenRemplissage . ' %'],
        ];

        foreach ($stats as $stat) {
            $sheet->setCellValue("A{$currentRow}", $stat['Label']);
            $sheet->setCellValue("B{$currentRow}", $stat['Valeur']);
            $sheet->mergeCells("B{$currentRow}:{$highestColumn}{$currentRow}");

            $sheet->getStyle("A{$currentRow}:{$highestColumn}{$currentRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ]
            ]);

            $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);
            $currentRow++;
        }

        return $currentRow;
    }

    /**
     * Ajouter les informations sur les filtres appliqués
     */
    private function addFiltersInfo($sheet, $currentRow, $highestColumn)
    {
        $currentRow += 1;

        if (!empty(array_filter($this->filters))) {
            $sheet->setCellValue("A{$currentRow}", 'FILTRES APPLIQUÉS');
            $sheet->mergeCells("A{$currentRow}:{$highestColumn}{$currentRow}");
            $sheet->getStyle("A{$currentRow}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 11,
                    'color' => ['rgb' => '495057']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFF3CD']
                ]
            ]);
            $currentRow++;

            if (!empty($this->filters['search'])) {
                $sheet->setCellValue("A{$currentRow}", "Recherche : {$this->filters['search']}");
                $sheet->mergeCells("A{$currentRow}:{$highestColumn}{$currentRow}");
                $currentRow++;
            }

            if (!empty($this->filters['filiere_id'])) {
                $filiere = \App\Models\ESBTPFiliere::find($this->filters['filiere_id']);
                if ($filiere) {
                    $sheet->setCellValue("A{$currentRow}", "Filière : {$filiere->name}");
                    $sheet->mergeCells("A{$currentRow}:{$highestColumn}{$currentRow}");
                    $currentRow++;
                }
            }

            if (!empty($this->filters['niveau_id'])) {
                $niveau = \App\Models\ESBTPNiveauEtude::find($this->filters['niveau_id']);
                if ($niveau) {
                    $sheet->setCellValue("A{$currentRow}", "Niveau : {$niveau->name}");
                    $sheet->mergeCells("A{$currentRow}:{$highestColumn}{$currentRow}");
                    $currentRow++;
                }
            }

            if (!empty($this->filters['statut'])) {
                $statutLabel = $this->filters['statut'] === 'active' ? 'Actives' : 'Inactives';
                $sheet->setCellValue("A{$currentRow}", "Statut : {$statutLabel}");
                $sheet->mergeCells("A{$currentRow}:{$highestColumn}{$currentRow}");
                $currentRow++;
            }

            if (!empty($this->filters['capacite'])) {
                $capaciteLabel = [
                    'disponible' => 'Classes avec places disponibles',
                    'pleine' => 'Classes pleines'
                ][$this->filters['capacite']] ?? $this->filters['capacite'];

                $sheet->setCellValue("A{$currentRow}", "Capacité : {$capaciteLabel}");
                $sheet->mergeCells("A{$currentRow}:{$highestColumn}{$currentRow}");
                $currentRow++;
            }
        }
    }

    /**
     * Charger les paramètres par défaut depuis la config
     */
    private function loadDefaultSettings(): array
    {
        return [
            'nom' => SettingsHelper::get('school_name', 'KLASSCI'),
            'adresse' => SettingsHelper::get('school_address', ''),
            'telephone' => SettingsHelper::get('school_phone', ''),
            'email' => SettingsHelper::get('school_email', ''),
        ];
    }
}
