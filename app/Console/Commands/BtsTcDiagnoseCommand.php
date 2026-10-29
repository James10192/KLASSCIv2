<?php

namespace App\Console\Commands;

use App\Domain\BtsTroncCommun\BtsPhaseResolver;
use App\Models\ESBTPInscription;
use Illuminate\Console\Command;

class BtsTcDiagnoseCommand extends Command
{
    protected $signature = 'bts-tc:diagnose {--inscription= : ID de l\'inscription} {--json : Sortie JSON}';
    protected $description = 'Diagnostique une inscription BTS tronc commun.';

    public function handle(BtsPhaseResolver $resolver): int
    {
        $inscriptionId = (int) $this->option('inscription');
        $inscription = ESBTPInscription::with([
            'classe.orientationTargets.targetClasse',
            'filiere',
            'phases.classe.filiere',
            'inscriptionOrigine.classe.filiere',
            'inscriptionSpecialisation.classe.filiere',
        ])->find($inscriptionId);

        if (! $inscription) {
            $this->error('Inscription introuvable.');

            return self::FAILURE;
        }

        $journey = $resolver->buildJourney($inscription);
        $payload = [
            'status' => 'ok',
            'source_model' => $journey['source_model'],
            'current_phase' => $journey['current_phase'],
            'timeline' => $journey['timeline'],
            'warnings' => [],
            'errors' => [],
            'recommended_actions' => [],
        ];

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->table(['Source', 'Phase courante', 'Classe'], [[
            $payload['source_model'],
            $payload['current_phase']['label'] ?? 'n/a',
            $payload['current_phase']['classe'] ?? 'n/a',
        ]]);

        return self::SUCCESS;
    }
}
