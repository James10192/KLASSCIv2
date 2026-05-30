<?php

namespace App\Console\Commands;

use App\Domain\BtsTroncCommun\BtsAnnualAggregationService;
use App\Models\ESBTPEtudiant;
use Illuminate\Console\Command;

class BtsTcStudentJourneyCommand extends Command
{
    protected $signature = 'bts-tc:student-journey {--etudiant= : ID étudiant} {--annee= : ID année} {--json : Sortie JSON}';
    protected $description = "Affiche le parcours annuel d'un étudiant BTS TC.";

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
            'annuel',
            true
        );

        $payload = [
            'status' => 'ok',
            'source_model' => $context['source_model'] ?? 'phase_based',
            'current_phase' => $context['journey']['current_phase'] ?? null,
            'timeline' => $context['journey']['timeline'] ?? [],
            'warnings' => [],
            'errors' => [],
            'recommended_actions' => [],
        ];

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->table(['Phase', 'Classe', 'Semestres'], collect($payload['timeline'])->map(fn ($phase) => [
            $phase['label'],
            $phase['classe'],
            trim(($phase['semestre_debut'] ?? '?') . ' -> ' . ($phase['semestre_fin'] ?? '...')),
        ])->all());

        return self::SUCCESS;
    }
}
