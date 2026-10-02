<?php

namespace App\Console\Commands;

use App\Domain\Inscriptions\StatutRedoublant;
use Illuminate\Console\Command;

/**
 * Pose le statut redoublant déduit sur toutes les inscriptions, toutes années
 * confondues, sans toucher à ce qu'une personne a confirmé ou corrigé.
 */
class RecenserRedoublants extends Command
{
    protected $signature = 'inscriptions:recenser-redoublants {--apply : Écrire (sinon, à blanc)}';

    protected $description = 'Déduit le statut redoublant de toutes les inscriptions non confirmées (à blanc par défaut)';

    public function handle(StatutRedoublant $statut): int
    {
        $bilan = $statut->recenser((bool) $this->option('apply'));

        $this->table(['Mesure', 'Nombre'], collect($bilan)->except('ecrit')->map(fn ($v, $k) => [$k, $v])->values()->all());
        $this->info($bilan['ecrit'] ? 'Recensement écrit.' : 'À blanc : relancez avec --apply pour écrire.');

        return self::SUCCESS;
    }
}
