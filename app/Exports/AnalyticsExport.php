<?php

namespace App\Exports;

use App\Domain\Analytics\DTOs\AnomalyAlert;
use App\Domain\Analytics\DTOs\PredictionResult;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export Excel multi-sheets pour Analytics : 1 sheet par section
 * (Synthèse / Risque de défaut / Anomalies).
 */
class AnalyticsExport implements WithMultipleSheets, FromCollection
{
    use Exportable;

    /**
     * @param  array<string, array{expected: float, paid: float, gap: float, gap_ratio: float}>  $recouvrementGaps
     */
    public function __construct(
        public readonly PredictionResult $cashFlow,
        public readonly PredictionResult $defaultRisk,
        public readonly array $anomalies,
        public readonly array $context = [],
        public readonly array $recouvrementGaps = [],
    ) {}

    public function sheets(): array
    {
        return [
            new AnalyticsSummarySheet($this->cashFlow, $this->defaultRisk, $this->anomalies, $this->recouvrementGaps),
            new AnalyticsRiskSheet($this->defaultRisk),
            new AnalyticsRecouvrementSheet($this->recouvrementGaps),
            new AnalyticsAnomaliesSheet($this->anomalies),
        ];
    }

    public function collection(): Collection
    {
        return collect();
    }
}

/**
 * Sheet 1 : Synthèse (KPIs principaux).
 */
class AnalyticsSummarySheet implements FromCollection, WithTitle, WithHeadings, WithStyles, ShouldAutoSize
{
    /**
     * @param  array<string, array{expected: float, paid: float, gap: float, gap_ratio: float}>  $recouvrementGaps
     */
    public function __construct(
        private readonly PredictionResult $cashFlow,
        private readonly PredictionResult $defaultRisk,
        private readonly array $anomalies,
        private readonly array $recouvrementGaps = [],
    ) {}

    public function title(): string
    {
        return 'Synthèse';
    }

    public function headings(): array
    {
        return ['Indicateur', 'Valeur', 'Détail'];
    }

    public function collection(): Collection
    {
        $criticalCount = collect($this->anomalies)->where('severity', AnomalyAlert::SEVERITY_CRITICAL)->count();
        $warningCount = collect($this->anomalies)->where('severity', AnomalyAlert::SEVERITY_WARNING)->count();

        $rows = [];

        if ($this->cashFlow->isAvailable()) {
            $rows[] = ['Recettes prévues mois prochain', number_format($this->cashFlow->value ?? 0, 0, ',', ' ') . ' FCFA', $this->cashFlow->confidenceLabel];
        }
        if ($this->defaultRisk->isAvailable()) {
            $buckets = $this->defaultRisk->metadata['buckets'] ?? [];
            $rows[] = ['Étudiants à haut risque', (int) ($buckets['haut'] ?? 0), 'Sur ' . ($this->defaultRisk->metadata['total_actifs'] ?? 0) . ' actifs'];
            $rows[] = ['Étudiants sous surveillance', (int) ($buckets['moyen'] ?? 0), ''];
            $rows[] = ['Solde non recouvré (haut risque)', number_format($this->defaultRisk->metadata['total_solde_haut_risque'] ?? 0, 0, ',', ' ') . ' FCFA', ''];
            $rows[] = ['Taux de risque cumulé', round($this->defaultRisk->metadata['taux_risque_pct'] ?? 0, 1) . ' %', ''];
            if (!empty($this->defaultRisk->metadata['echeancier_mode'])) {
                $rows[] = ['Mode échéanciers', ucfirst($this->defaultRisk->metadata['echeancier_mode']), $this->defaultRisk->metadata['echeancier_mode_note'] ?? ''];
            }
        }

        if (!empty($this->recouvrementGaps)) {
            $totalExpected = array_sum(array_column($this->recouvrementGaps, 'expected'));
            $totalPaid = array_sum(array_column($this->recouvrementGaps, 'paid'));
            $totalGap = max(0.0, $totalExpected - $totalPaid);
            $globalRate = $totalExpected > 0 ? round($totalPaid / $totalExpected * 100, 1) : 0;

            $rows[] = ['Recouvrement — attendu cumulé (6 mois)', number_format($totalExpected, 0, ',', ' ') . ' FCFA', ''];
            $rows[] = ['Recouvrement — encaissé cumulé (6 mois)', number_format($totalPaid, 0, ',', ' ') . ' FCFA', ''];
            $rows[] = ['Recouvrement — écart restant (6 mois)', number_format($totalGap, 0, ',', ' ') . ' FCFA', ''];
            $rows[] = ['Taux de recouvrement (6 mois)', $globalRate . ' %', ''];
        }

        $rows[] = ['Anomalies critiques', $criticalCount, ''];
        $rows[] = ['Anomalies en avertissement', $warningCount, ''];

        return collect($rows);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0453CB']],
            ],
        ];
    }
}

/**
 * Sheet 2 : Détail risque de défaut (top-N étudiants).
 */
class AnalyticsRiskSheet implements FromCollection, WithTitle, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    private int $counter = 0;

    public function __construct(private readonly PredictionResult $defaultRisk) {}

    public function title(): string
    {
        return 'Risque de défaut';
    }

    public function headings(): array
    {
        return ['#', 'Étudiant', 'Classe', 'Solde restant', 'Jours retard', '% payé', 'Niveau', 'Score'];
    }

    public function collection(): Collection
    {
        return collect($this->defaultRisk->metadata['top_at_risk'] ?? [])->values();
    }

    public function map($row): array
    {
        $this->counter++;
        return [
            $this->counter,
            $row['etudiant_nom'] ?? '',
            $row['classe_nom'] ?? '',
            (float) ($row['solde_restant'] ?? 0),
            (int) ($row['jours_retard'] ?? 0),
            round(((float) ($row['ratio_paye'] ?? 0)) * 100, 1) . ' %',
            ucfirst($row['level'] ?? ''),
            round((float) ($row['score'] ?? 0), 3),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0453CB']],
            ],
        ];
    }
}

/**
 * Sheet 3 : Recouvrement mois par mois (attendu vs encaissé sur 6 mois).
 */
class AnalyticsRecouvrementSheet implements FromCollection, WithTitle, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    /**
     * @param  array<string, array{expected: float, paid: float, gap: float, gap_ratio: float}>  $recouvrementGaps
     */
    public function __construct(private readonly array $recouvrementGaps) {}

    public function title(): string
    {
        return 'Recouvrement';
    }

    public function headings(): array
    {
        return ['Mois', 'Attendu (FCFA)', 'Encaissé (FCFA)', 'Écart (FCFA)', 'Taux recouvrement', 'Statut'];
    }

    public function collection(): Collection
    {
        $rows = [];
        foreach ($this->recouvrementGaps as $monthKey => $bucket) {
            $rows[] = array_merge(['month_key' => $monthKey], $bucket);
        }
        return collect($rows);
    }

    public function map($row): array
    {
        [$year, $month] = array_map('intval', explode('-', $row['month_key']));
        $monthLabel = ucfirst(\Carbon\Carbon::createFromDate($year, $month, 1)->locale('fr')->translatedFormat('F Y'));

        $expected = (float) $row['expected'];
        $paid = (float) $row['paid'];
        $gap = (float) $row['gap'];
        $gapRatio = (float) $row['gap_ratio'];
        $rate = $expected > 0 ? round(($paid / $expected) * 100, 1) : 0;

        $status = match (true) {
            $gapRatio >= 0.5 => 'CRITIQUE',
            $gapRatio >= 0.3 => 'SURVEILLANCE',
            default => 'SAIN',
        };

        return [
            $monthLabel,
            $expected,
            $paid,
            $gap,
            $rate . ' %',
            $status,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0453CB']],
            ],
        ];
    }
}

/**
 * Sheet 4 : Anomalies détectées.
 */
class AnalyticsAnomaliesSheet implements FromCollection, WithTitle, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(private readonly array $anomalies) {}

    public function title(): string
    {
        return 'Anomalies';
    }

    public function headings(): array
    {
        return ['Sévérité', 'Type', 'Entité', 'Score', 'Message'];
    }

    public function collection(): Collection
    {
        return collect($this->anomalies);
    }

    public function map($alert): array
    {
        if (!$alert instanceof AnomalyAlert) {
            return [];
        }
        return [
            strtoupper($alert->severity),
            str_replace('_', ' ', $alert->type),
            "{$alert->entityType} #{$alert->entityId}",
            round($alert->score, 2),
            $alert->message,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0453CB']],
            ],
        ];
    }
}
