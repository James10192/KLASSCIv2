<?php

namespace App\Console\Commands;

use App\Models\ESBTPClasse;
use Illuminate\Console\Command;

class BtsTcOrientationCheckCommand extends Command
{
    protected $signature = 'bts-tc:orientation-check {--classe= : ID classe source} {--json : Sortie JSON}';
    protected $description = 'Vérifie les sorties autorisées d\'une classe BTS TC.';

    public function handle(): int
    {
        $classe = ESBTPClasse::with(['orientationTargets.targetClasse'])->find((int) $this->option('classe'));
        if (! $classe) {
            $this->error('Classe introuvable.');

            return self::FAILURE;
        }

        $items = $classe->orientationTargets->map(fn ($target) => [
            'source' => $classe->name,
            'target' => $target->targetClasse?->name,
            'semestre_activation' => $target->semestre_activation,
            'is_active' => $target->is_active,
        ])->values();

        if ($this->option('json')) {
            $this->line(json_encode(['status' => 'ok', 'items' => $items], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->table(['Source', 'Cible', 'Semestre', 'Active'], $items->map(fn ($item) => array_values($item))->all());

        return self::SUCCESS;
    }
}
