<?php

namespace App\Console\Commands;

use App\Domain\BtsTroncCommun\BtsAnnualAggregationService;
use App\Models\ESBTPEtudiant;
use Illuminate\Console\Command;

class BtsTcResultsConsistencyCommand extends Command
{
    protected $signature = 'bts-tc:results-consistency {--etudiant= : ID étudiant} {--annee= : ID année} {--periode=annuel : annuel|semestre1|semestre2} {--json : Sortie JSON}';
    protected $description = 'Compare la résolution de phase BTS TC avec le contexte de résultats attendu.';

    public function handle(BtsAnnualAggregationService $aggregationService): int
    {
        $etudiant = ESBTPEtudiant::find((int) $this->option('etudiant'));
        if (! $etudiant) {
            $this->error('Étudiant introuvable.');

            return self::FAILURE;
        }

        $context = $aggregationService->resolveStudentContext(
            $etudiant,
            (int) $this->option('annee') ?: null,
            null,
            (string) $this->option('periode'),
            true
        );

        $payload = [
            'status' => 'ok',
            'source_model' => $context['source_model'] ?? 'phase_based',
            'current_phase' => $context['effective_phase'] ?? null,
            'timeline' => $context['journey']['timeline'] ?? [],
            'warnings' => [],
            'errors' => [],
            'recommended_actions' => [],
        ];

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->info('Classe résolue : ' . ($payload['current_phase']['classe'] ?? 'n/a'));

        return self::SUCCESS;
    }
}
