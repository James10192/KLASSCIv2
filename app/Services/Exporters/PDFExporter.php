<?php

namespace App\Services\Exporters;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

class PDFExporter
{
    private $data;
    private $options;

    public function __construct($data, $options = [])
    {
        $this->data = $data;
        $this->options = array_merge([
            'orientation' => 'portrait',
            'paper' => 'a4',
            'margin' => ['top' => 20, 'right' => 15, 'bottom' => 20, 'left' => 15],
            'template' => 'default',
            'include_charts' => true,
            'watermark' => false,
            'title' => 'Rapport Financier',
            'subtitle' => null,
            'author' => 'KLASSCI System',
            'show_date' => true
        ], $options);
    }

    /**
     * Générer et retourner le PDF
     */
    public function export()
    {
        $html = $this->generateHTML();

        $pdf = Pdf::loadHTML($html);

        // Configuration du PDF
        $pdf->setPaper($this->options['paper'], $this->options['orientation']);

        // Marges personnalisées
        if (isset($this->options['margin'])) {
            $margin = $this->options['margin'];
            $pdf->setOption('margin-top', $margin['top'] ?? 20);
            $pdf->setOption('margin-right', $margin['right'] ?? 15);
            $pdf->setOption('margin-bottom', $margin['bottom'] ?? 20);
            $pdf->setOption('margin-left', $margin['left'] ?? 15);
        }

        // Options supplémentaires
        $pdf->setOption('enable-javascript', true);
        $pdf->setOption('javascript-delay', 1000);
        $pdf->setOption('enable-smart-shrinking', true);
        $pdf->setOption('no-stop-slow-scripts', true);

        return $pdf;
    }

    /**
     * Télécharger le PDF
     */
    public function download($filename = null)
    {
        $filename = $filename ?: $this->generateFilename();
        return $this->export()->download($filename);
    }

    /**
     * Retourner le PDF en stream
     */
    public function stream($filename = null)
    {
        $filename = $filename ?: $this->generateFilename();
        return $this->export()->stream($filename);
    }

    /**
     * Générer le HTML du rapport
     */
    private function generateHTML()
    {
        $template = $this->options['template'] ?? 'default';

        // Préparer les données pour la vue
        $viewData = [
            'data' => $this->data,
            'options' => $this->options,
            'title' => $this->options['title'],
            'subtitle' => $this->options['subtitle'],
            'generated_at' => now(),
            'charts' => $this->prepareChartsForPDF(),
            'totals' => $this->calculateTotals(),
            'summary' => $this->generateSummary()
        ];

        // Sélectionner le template approprié
        switch($template) {
            case 'financial':
                return View::make('esbtp.comptabilite.exports.pdf.financial', $viewData)->render();

            case 'analytical':
                return View::make('esbtp.comptabilite.exports.pdf.analytical', $viewData)->render();

            case 'comparison':
                return View::make('esbtp.comptabilite.exports.pdf.comparison', $viewData)->render();

            default:
                return View::make('esbtp.comptabilite.exports.pdf.default', $viewData)->render();
        }
    }

    /**
     * Préparer les graphiques pour le PDF
     */
    private function prepareChartsForPDF()
    {
        if (!$this->options['include_charts'] || !isset($this->data['graphiques'])) {
            return [];
        }

        $charts = [];
        foreach ($this->data['graphiques'] as $key => $chartData) {
            $charts[$key] = [
                'data' => $chartData,
                'base64' => $this->generateChartImage($chartData),
                'type' => $chartData['type'] ?? 'line'
            ];
        }

        return $charts;
    }

    /**
     * Générer une image de graphique pour le PDF
     */
    private function generateChartImage($chartData)
    {
        // Pour une implémentation complète, vous pourriez utiliser ChartJS-Node
        // ou une autre solution pour convertir les graphiques en images
        // Pour le moment, on retourne un placeholder
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==';
    }

    /**
     * Calculer les totaux
     */
    private function calculateTotals()
    {
        $totals = [
            'recettes' => 0,
            'depenses' => 0,
            'balance' => 0,
            'count' => 0
        ];

        if (isset($this->data['donnees'])) {
            $donnees = $this->data['donnees'];

            if (isset($donnees['recettes'])) {
                $totals['recettes'] = is_numeric($donnees['recettes']) ? $donnees['recettes'] : 0;
            }

            if (isset($donnees['depenses'])) {
                $totals['depenses'] = is_numeric($donnees['depenses']) ? $donnees['depenses'] : 0;
            }

            if (isset($donnees['total_montant'])) {
                $totals['total'] = $donnees['total_montant'];
            }

            if (isset($donnees['nombre_paiements'])) {
                $totals['count'] = $donnees['nombre_paiements'];
            } elseif (isset($donnees['nombre_depenses'])) {
                $totals['count'] = $donnees['nombre_depenses'];
            }
        }

        $totals['balance'] = $totals['recettes'] - $totals['depenses'];

        return $totals;
    }

    /**
     * Générer un résumé du rapport
     */
    private function generateSummary()
    {
        $totals = $this->calculateTotals();

        return [
            'period' => $this->data['periode'] ?? 'Non spécifiée',
            'balance_status' => $totals['balance'] >= 0 ? 'positive' : 'negative',
            'balance_label' => $totals['balance'] >= 0 ? 'Excédent' : 'Déficit',
            'performance' => $this->calculatePerformance($totals),
            'recommendations' => $this->generateRecommendations($totals)
        ];
    }

    /**
     * Calculer les indicateurs de performance
     */
    private function calculatePerformance($totals)
    {
        $performance = ['level' => 'normal', 'indicators' => []];

        if ($totals['recettes'] > 0) {
            $ratio = ($totals['balance'] / $totals['recettes']) * 100;

            if ($ratio >= 20) {
                $performance['level'] = 'excellent';
            } elseif ($ratio >= 10) {
                $performance['level'] = 'good';
            } elseif ($ratio >= 0) {
                $performance['level'] = 'normal';
            } else {
                $performance['level'] = 'poor';
            }

            $performance['indicators']['margin'] = round($ratio, 2);
        }

        return $performance;
    }

    /**
     * Générer des recommandations
     */
    private function generateRecommendations($totals)
    {
        $recommendations = [];

        if ($totals['balance'] < 0) {
            $recommendations[] = 'Réduire les dépenses ou augmenter les recettes pour équilibrer le budget.';
        }

        if ($totals['recettes'] > 0 && ($totals['depenses'] / $totals['recettes']) > 0.9) {
            $recommendations[] = 'Le ratio dépenses/recettes est élevé. Surveiller les coûts.';
        }

        return $recommendations;
    }

    /**
     * Générer un nom de fichier automatique
     */
    private function generateFilename()
    {
        $title = str_replace(' ', '_', $this->options['title']);
        $date = now()->format('Y-m-d_H-i');
        return "{$title}_{$date}.pdf";
    }
}
