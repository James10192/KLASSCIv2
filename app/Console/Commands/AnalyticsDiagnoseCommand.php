<?php

namespace App\Console\Commands;

use App\Domain\Analytics\DTOs\AnalyticsContext;
use App\Domain\Analytics\Predictors\DefaultRiskPredictor;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEcheancierRule;
use App\Models\ESBTPInscription;
use App\Services\EcheancierReadinessService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Diagnostic complet du sous-système Analytics : couverture des règles d'échéancier,
 * répartition des snapshots, distribution mensuelle attendue, taux de saturation
 * du risque, et recommandations de calibration.
 *
 * Usage : php artisan analytics:diagnose [--annee=ID] [--json]
 */
class AnalyticsDiagnoseCommand extends Command
{
    protected $signature = 'analytics:diagnose
                            {--annee= : ID de l\'année universitaire (par défaut : courante)}
                            {--months=12 : Nombre de mois passés à inspecter pour la distribution due_date}
                            {--json : Sortie JSON brute (utile pour le CLI distant)}';

    protected $description = 'Audit complet de l\'état du sous-système Analytics (échéanciers, snapshots, calibration risque)';

    public function handle(
        EcheancierReadinessService $readiness,
        DefaultRiskPredictor $riskPredictor,
        \App\Services\EcheancierCoverageService $coverage,
    ): int {
        $report = $this->buildReport($readiness, $riskPredictor, $coverage);
        $report['recommendations'] = $this->computeRecommendations($report);

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return self::SUCCESS;
        }

        $this->renderHumanReport($report);
        return self::SUCCESS;
    }

    private function buildReport(
        EcheancierReadinessService $readiness,
        DefaultRiskPredictor $riskPredictor,
        \App\Services\EcheancierCoverageService $coverage,
    ): array {
        $annee = $this->resolveAnnee();
        $months = (int) $this->option('months');

        return [
            'tenant'              => config('app.name'),
            'generated_at'        => now()->toIso8601String(),
            'annee_universitaire' => $annee ? ['id' => $annee->id, 'name' => $annee->name] : null,
            'echeancier' => [
                'mode'          => $readiness->mode(),
                'note'          => $readiness->noteForMode(),
                'rules_summary' => $this->summarizeRules(),
            ],
            'coverage'           => $coverage->summary($annee?->id),
            'monthly_attendu'    => $this->monthlyDistribution($annee?->id, $months),
            'risk_saturation'    => $this->riskSaturation($annee?->id, $riskPredictor),
            'top_uncovered'      => $this->topUncoveredInscriptions($annee?->id, 5),
        ];
    }

    private function computeRecommendations(array $report): array
    {
        $r = [];
        $cov = $report['coverage'] ?? [];
        $rs  = $report['risk_saturation'] ?? [];
        $ech = $report['echeancier'] ?? [];

        if (($cov['coverage_pct'] ?? 100) < 50) {
            $r[] = sprintf('Configurer ou activer une règle d\'échéancier — couverture actuelle : %.1f %%', $cov['coverage_pct'] ?? 0);
        }
        if (!empty($rs['is_saturated'])) {
            $r[] = 'Activer auto-calibration : settings(analytics.default_risk.auto_calibrate=true)';
        }
        if (($ech['mode'] ?? null) === EcheancierReadinessService::MODE_FALLBACK) {
            $r[] = 'Aucune règle active — système en mode dégradé (1 tranche par catégorie)';
        }
        if (($cov['without_snapshot'] ?? 0) > 0) {
            $r[] = sprintf('Re-générer les snapshots : %d inscriptions à recalculer (php artisan echeanciers:recompute)', $cov['without_snapshot']);
        }
        return $r ?: ['Tout est bon ✓'];
    }

    private function resolveAnnee(): ?ESBTPAnneeUniversitaire
    {
        if ($id = $this->option('annee')) {
            return ESBTPAnneeUniversitaire::find($id);
        }
        return ESBTPAnneeUniversitaire::where('is_current', true)->first();
    }

    private function summarizeRules(): array
    {
        $rules = ESBTPEcheancierRule::query()
            ->where('is_active', true)
            ->withCount(['lines as active_lines' => fn ($q) => $q->where('is_active', true)])
            ->get();

        return [
            'total_active'       => $rules->count(),
            'with_active_lines'  => $rules->where('active_lines', '>', 0)->count(),
            'by_scope'           => $rules->groupBy('scope_type')
                ->map(fn ($g, $scope) => ['scope' => $scope, 'count' => $g->count(), 'avg_lines' => round($g->avg('active_lines'), 1)])
                ->values()
                ->all(),
        ];
    }

    private function monthlyDistribution(?int $anneeId, int $months): array
    {
        if (!\Schema::hasTable('esbtp_inscription_echeancier_snapshots')) {
            return ['note' => 'Table snapshots inexistante', 'rows' => []];
        }

        $start = now()->subMonths($months)->startOfMonth();
        $end   = now()->addMonths(3)->endOfMonth();

        $rows = DB::table('esbtp_inscription_echeancier_snapshots as s')
            ->join('esbtp_inscriptions as i', 'i.id', '=', 's.inscription_id')
            ->whereNull('i.deleted_at')
            ->where('i.status', 'active')
            ->where('i.workflow_step', 'etudiant_cree')
            ->when($anneeId, fn ($q) => $q->where('i.annee_universitaire_id', $anneeId))
            ->selectRaw("DATE_FORMAT(s.created_at, '%Y-%m') as mois, COUNT(*) as n_snapshots")
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();

        // On préfère regarder les due_date dans le payload JSON — fait en post-process car JSON_EXTRACT pénible cross-DB
        $aggregated = [];
        ESBTPInscription::query()
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->whereNull('deleted_at')
            ->when($anneeId, fn ($q) => $q->where('annee_universitaire_id', $anneeId))
            ->whereExists(fn ($q) => $q->selectRaw(1)
                ->from('esbtp_inscription_echeancier_snapshots as s')
                ->whereColumn('s.inscription_id', 'esbtp_inscriptions.id'))
            ->with('echeancierSnapshot')
            ->chunkById(100, function ($inscriptions) use (&$aggregated, $start, $end) {
                foreach ($inscriptions as $i) {
                    $snapshot = $i->echeancierSnapshot ?? null;
                    if (!$snapshot || empty($snapshot->payload)) continue;
                    $lines = $snapshot->payload['due_lines'] ?? [];
                    foreach ($lines as $line) {
                        if (empty($line['due_date'])) continue;
                        $date = Carbon::parse($line['due_date']);
                        if ($date->lt($start) || $date->gt($end)) continue;
                        $key = $date->format('Y-m');
                        $aggregated[$key] = ($aggregated[$key] ?? ['n' => 0, 'amount' => 0.0]);
                        $aggregated[$key]['n']++;
                        $aggregated[$key]['amount'] += (float) ($line['amount'] ?? 0);
                    }
                }
            });

        ksort($aggregated);

        return [
            'window_start' => $start->toDateString(),
            'window_end'   => $end->toDateString(),
            'rows'         => collect($aggregated)->map(fn ($v, $k) => [
                'mois'      => $k,
                'tranches'  => $v['n'],
                'attendu'   => round($v['amount'], 2),
            ])->values()->all(),
        ];
    }

    private function riskSaturation(?int $anneeId, DefaultRiskPredictor $predictor): array
    {
        $context = new AnalyticsContext(anneeId: $anneeId);
        try {
            $result = $predictor->predict($context);
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }

        if (!$result->metadata) {
            return ['unavailable_reason' => $result->explanation[0] ?? 'unknown'];
        }

        $buckets = $result->metadata['buckets'] ?? [];
        $total   = $result->metadata['total_actifs'] ?? 0;
        $tauxRisque = $result->metadata['taux_risque_pct'] ?? 0;
        $hautPct = $total > 0 ? round(($buckets['haut'] ?? 0) / $total * 100, 1) : 0.0;

        return [
            'total_actifs'        => $total,
            'buckets'             => $buckets,
            'haut_risque_pct'     => $hautPct,
            'taux_risque_total'   => $tauxRisque,
            'is_saturated'        => $hautPct >= 70.0,
            'echeancier_mode'     => $result->metadata['echeancier_mode'] ?? null,
        ];
    }

    private function topUncoveredInscriptions(?int $anneeId, int $limit): array
    {
        if (!\Schema::hasTable('esbtp_inscription_echeancier_snapshots')) return [];

        return ESBTPInscription::query()
            ->with('etudiant:id,nom,prenoms,matricule', 'classe:id,name')
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->whereNull('deleted_at')
            ->when($anneeId, fn ($q) => $q->where('annee_universitaire_id', $anneeId))
            ->whereNotExists(fn ($q) => $q->selectRaw(1)
                ->from('esbtp_inscription_echeancier_snapshots as s')
                ->whereColumn('s.inscription_id', 'esbtp_inscriptions.id'))
            ->limit($limit)
            ->get()
            ->map(fn ($i) => [
                'inscription_id' => $i->id,
                'matricule'      => $i->etudiant?->matricule,
                'nom'            => trim(($i->etudiant?->prenoms ?? '') . ' ' . ($i->etudiant?->nom ?? '')),
                'classe'         => $i->classe?->name,
                'date_inscription' => $i->date_inscription ?? $i->created_at?->toDateString(),
            ])
            ->all();
    }

    private function renderHumanReport(array $report): void
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('  ANALYTICS DIAGNOSE — ' . $report['tenant']);
        $this->info('═══════════════════════════════════════════════════════════');

        if ($annee = $report['annee_universitaire']) {
            $this->line(sprintf('Année universitaire : <fg=cyan>%s</> (ID #%d)', $annee['name'], $annee['id']));
        } else {
            $this->warn('⚠ Aucune année universitaire courante définie.');
        }

        // Échéancier
        $this->newLine();
        $this->info('▸ Règles d\'échéancier');
        $ech = $report['echeancier'];
        $modeColor = $ech['mode'] === EcheancierReadinessService::MODE_CONFIGURED ? 'green' : 'yellow';
        $this->line(sprintf('  Mode : <fg=%s>%s</>', $modeColor, $ech['mode']));
        if ($ech['note']) $this->warn('  ' . $ech['note']);
        $this->line(sprintf('  Règles actives : %d (avec lignes : %d)',
            $ech['rules_summary']['total_active'],
            $ech['rules_summary']['with_active_lines']
        ));
        if (!empty($ech['rules_summary']['by_scope'])) {
            $this->table(['Scope', 'Nb règles', 'Lignes/règle (moy)'],
                array_map(fn ($s) => [$s['scope'], $s['count'], $s['avg_lines']], $ech['rules_summary']['by_scope'])
            );
        }

        // Couverture snapshots
        $this->newLine();
        $this->info('▸ Couverture snapshots');
        $cov = $report['coverage'];
        $color = $cov['coverage_pct'] >= 90 ? 'green' : ($cov['coverage_pct'] >= 50 ? 'yellow' : 'red');
        $this->line(sprintf('  %d / %d inscriptions ont un snapshot (<fg=%s>%.1f%%</>)',
            $cov['with_snapshot'], $cov['total_actives'], $color, $cov['coverage_pct']
        ));
        if ($cov['without_snapshot'] > 0) {
            $this->warn(sprintf('  ⚠ %d inscriptions sans snapshot (mode fallback)', $cov['without_snapshot']));
        }

        // Distribution mensuelle
        $this->newLine();
        $this->info('▸ Distribution mensuelle attendue (snapshots)');
        if (empty($report['monthly_attendu']['rows'])) {
            $this->line('  Aucun due_date dans la fenêtre.');
        } else {
            $this->table(['Mois', 'Tranches', 'Attendu (FCFA)'],
                array_map(fn ($r) => [$r['mois'], $r['tranches'], number_format($r['attendu'], 0, ',', ' ')],
                    $report['monthly_attendu']['rows']
                )
            );
        }

        // Saturation risque
        $this->newLine();
        $this->info('▸ Saturation risque');
        $rs = $report['risk_saturation'];
        if (isset($rs['unavailable_reason'])) {
            $this->warn('  ' . $rs['unavailable_reason']);
        } else {
            $color = $rs['is_saturated'] ? 'red' : 'green';
            $this->line(sprintf('  Haut risque : <fg=%s>%.1f%%</> (%d / %d actifs)',
                $color, $rs['haut_risque_pct'], $rs['buckets']['haut'] ?? 0, $rs['total_actifs']
            ));
            if ($rs['is_saturated']) {
                $this->warn('  ⚠ Saturation > 70 % — auto-calibration recommandée');
            }
        }

        // Top uncovered
        if (!empty($report['top_uncovered'])) {
            $this->newLine();
            $this->info('▸ Top 5 inscriptions sans snapshot');
            $this->table(['ID', 'Matricule', 'Nom', 'Classe', 'Date inscription'],
                array_map(fn ($i) => [$i['inscription_id'], $i['matricule'], $i['nom'], $i['classe'], $i['date_inscription']],
                    $report['top_uncovered']
                )
            );
        }

        // Recommandations dérivées (déjà calculées dans handle())
        $this->newLine();
        $this->info('▸ Recommandations');
        foreach (($report['recommendations'] ?? ['Tout est bon ✓']) as $r) {
            $this->line('  • ' . $r);
        }
    }
}
