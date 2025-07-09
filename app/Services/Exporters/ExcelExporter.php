<?php

namespace App\Services\Exporters;

use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title as ChartTitle;
use Illuminate\Support\Collection;

class ExcelExporter implements FromCollection, WithHeadings, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    private $data;
    private $options;
    private $title;

    public function __construct($data, $options = [])
    {
        $this->data = $data;
        $this->options = array_merge([
            'include_totals' => true,
            'format_numbers' => true,
            'auto_width' => true,
            'freeze_header' => true,
            'include_charts' => false,
            'sheet_name' => 'Rapport Financier'
        ], $options);

        $this->title = $this->options['sheet_name'];
    }

    /**
     * Export et téléchargement
     */
    public function export($filename = null)
    {
        $filename = $filename ?: $this->generateFilename();
        return Excel::download($this, $filename);
    }

    /**
     * Collection des données à exporter
     */
    public function collection()
    {
        $collection = new Collection();

        // Ajouter les données principales
        if (isset($this->data['donnees'])) {
            $collection = $this->processMainData($this->data['donnees']);
        }

        // Ajouter les totaux si requis
        if ($this->options['include_totals']) {
            $collection->push($this->generateTotalsRow());
        }

        return $collection;
    }

    /**
     * En-têtes des colonnes
     */
    public function headings(): array
    {
        // Déterminer les en-têtes basés sur le type de données
        if (isset($this->data['donnees']['paiements']) && $this->data['donnees']['paiements']->isNotEmpty()) {
            return [
                'Date',
                'Étudiant',
                'Montant',
                'Mode de paiement',
                'Statut',
                'Référence',
                'Créé par'
            ];
        }

        if (isset($this->data['donnees']['depenses']) && $this->data['donnees']['depenses']->isNotEmpty()) {
            return [
                'Date',
                'Libellé',
                'Montant',
                'Catégorie',
                'Fournisseur',
                'Statut',
                'Mode de paiement'
            ];
        }

        // En-têtes par défaut pour les rapports de performance
        return [
            'Indicateur',
            'Valeur',
            'Unité',
            'Période',
            'Commentaire'
        ];
    }

    /**
     * Styles des cellules
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style de l'en-tête
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => '2E86AB']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ],
            // Style des données
            '2:1000' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ]
            ]
        ];
    }

    /**
     * Titre de la feuille
     */
    public function title(): string
    {
        return $this->title;
    }

    /**
     * Largeurs des colonnes
     */
    public function columnWidths(): array
    {
        if (!$this->options['auto_width']) {
            return [];
        }

        return [
            'A' => 15, // Date
            'B' => 25, // Nom/Libellé
            'C' => 15, // Montant
            'D' => 20, // Catégorie/Mode
            'E' => 20, // Statut/Fournisseur
            'F' => 15, // Référence
            'G' => 20, // Créé par
        ];
    }

    /**
     * Événements de la feuille
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Geler la première ligne si option activée
                if ($this->options['freeze_header']) {
                    $event->sheet->freezePane('A2');
                }

                // Formatage des nombres
                if ($this->options['format_numbers']) {
                    $this->formatNumbers($event->sheet);
                }

                // Ajouter des graphiques si requis
                if ($this->options['include_charts']) {
                    $this->addCharts($event->sheet);
                }

                // Ajouter un résumé
                $this->addSummary($event->sheet);
            }
        ];
    }

    /**
     * Traiter les données principales
     */
    private function processMainData($donnees)
    {
        $collection = new Collection();

        if (isset($donnees['paiements']) && $donnees['paiements']->isNotEmpty()) {
            foreach ($donnees['paiements'] as $paiement) {
                $collection->push([
                    'date' => $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y') : '',
                    'etudiant' => $paiement->etudiant ? $paiement->etudiant->nom_complet : '',
                    'montant' => $paiement->montant,
                    'mode_paiement' => $paiement->mode_paiement,
                    'statut' => $paiement->statut,
                    'reference' => $paiement->reference ?? '',
                    'createur' => $paiement->createur ? $paiement->createur->name : ''
                ]);
            }
        } elseif (isset($donnees['depenses']) && $donnees['depenses']->isNotEmpty()) {
            foreach ($donnees['depenses'] as $depense) {
                $collection->push([
                    'date' => $depense->date_depense ? $depense->date_depense->format('d/m/Y') : '',
                    'libelle' => $depense->libelle,
                    'montant' => $depense->montant,
                    'categorie' => $depense->categorie ? $depense->categorie->nom : '',
                    'fournisseur' => $depense->fournisseur ? $depense->fournisseur->nom : '',
                    'statut' => $depense->statut,
                    'mode_paiement' => $depense->mode_paiement ?? ''
                ]);
            }
        } else {
            // Données de performance
            $collection->push(['Recettes totales', $donnees['recettes'] ?? 0, 'FCFA', $this->data['periode'] ?? '', '']);
            $collection->push(['Dépenses totales', $donnees['depenses'] ?? 0, 'FCFA', $this->data['periode'] ?? '', '']);
            $collection->push(['Résultat net', $donnees['resultat_net'] ?? 0, 'FCFA', $this->data['periode'] ?? '', '']);

            if (isset($donnees['marge_nette'])) {
                $collection->push(['Marge nette', $donnees['marge_nette'], '%', $this->data['periode'] ?? '', '']);
            }
        }

        return $collection;
    }

    /**
     * Générer une ligne de totaux
     */
    private function generateTotalsRow()
    {
        if (isset($this->data['donnees']['total_montant'])) {
            return [
                'TOTAL',
                '',
                $this->data['donnees']['total_montant'],
                '',
                '',
                '',
                ''
            ];
        }

        return ['TOTAL', '', 0, '', '', '', ''];
    }

    /**
     * Formater les nombres
     */
    private function formatNumbers($sheet)
    {
        $highestRow = $sheet->getHighestRow();

        // Format des colonnes de montant (colonne C)
        $sheet->getStyle("C2:C{$highestRow}")
              ->getNumberFormat()
              ->setFormatCode('#,##0.00 "FCFA"');
    }

    /**
     * Ajouter des graphiques
     */
    private function addCharts($sheet)
    {
        if (!isset($this->data['graphiques']) || empty($this->data['graphiques'])) {
            return;
        }

        // Créer un graphique simple
        $this->createBasicChart($sheet);
    }

    /**
     * Créer un graphique de base
     */
    private function createBasicChart($sheet)
    {
        $highestRow = $sheet->getHighestRow();

        if ($highestRow < 3) return; // Pas assez de données

        // Données pour le graphique
        $dataSeriesLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'Worksheet!$B$1', null, 1),
        ];

        $xAxisTickValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "Worksheet!\$A\$2:\$A\${$highestRow}", null, $highestRow - 1),
        ];

        $dataSeriesValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "Worksheet!\$C\$2:\$C\${$highestRow}", null, $highestRow - 1),
        ];

        // Configuration du graphique
        $series = new DataSeries(
            DataSeries::TYPE_LINECHART,
            DataSeries::GROUPING_STANDARD,
            range(0, count($dataSeriesValues) - 1),
            $dataSeriesLabels,
            $xAxisTickValues,
            $dataSeriesValues
        );

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_TOPRIGHT, null, false);
        $title = new ChartTitle('Évolution des montants');

        $chart = new Chart(
            'chart1',
            $title,
            $legend,
            $plotArea,
            true,
            0,
            null,
            null
        );

        $chart->setTopLeftPosition("F2");
        $chart->setBottomRightPosition("M15");

        $sheet->addChart($chart);
    }

    /**
     * Ajouter un résumé
     */
    private function addSummary($sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $summaryRow = $highestRow + 3;

        // Titre du résumé
        $sheet->setCellValue("A{$summaryRow}", "RÉSUMÉ");
        $sheet->getStyle("A{$summaryRow}")->getFont()->setBold(true)->setSize(14);

        $summaryRow++;

        // Informations du rapport
        $sheet->setCellValue("A{$summaryRow}", "Rapport généré le:");
        $sheet->setCellValue("B{$summaryRow}", now()->format('d/m/Y H:i'));

        $summaryRow++;
        $sheet->setCellValue("A{$summaryRow}", "Période:");
        $sheet->setCellValue("B{$summaryRow}", $this->data['periode'] ?? 'Non spécifiée');

        if (isset($this->data['donnees']['total_montant'])) {
            $summaryRow++;
            $sheet->setCellValue("A{$summaryRow}", "Total général:");
            $sheet->setCellValue("B{$summaryRow}", $this->data['donnees']['total_montant']);
            $sheet->getStyle("B{$summaryRow}")->getNumberFormat()->setFormatCode('#,##0.00 "FCFA"');
        }
    }

    /**
     * Générer un nom de fichier
     */
    private function generateFilename()
    {
        $title = str_replace(' ', '_', $this->title);
        $date = now()->format('Y-m-d_H-i');
        return "{$title}_{$date}.xlsx";
    }
}
