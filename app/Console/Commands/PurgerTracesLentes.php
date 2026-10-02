<?php

namespace App\Console\Commands;

use App\Models\TraceLente;
use Illuminate\Console\Command;

/**
 * Retire les traces des actions lentes plus anciennes que la rétention.
 * Supprime par paquets : sur un hébergement mutualisé, un DELETE de cent
 * mille lignes d'un coup tiendrait un verrou trop longtemps.
 */
class PurgerTracesLentes extends Command
{
    protected $signature = 'traces:purger {--jours=30 : Rétention, en jours}';

    protected $description = 'Supprime les traces des actions lentes plus anciennes que la rétention';

    public function handle(): int
    {
        $jours = max(1, (int) $this->option('jours'));
        $limite = now()->subDays($jours);
        $total = 0;

        do {
            $ids = TraceLente::query()->where('created_at', '<', $limite)->limit(1000)->pluck('id');
            $total += $ids->isEmpty() ? 0 : TraceLente::query()->whereIn('id', $ids)->delete();
        } while ($ids->count() === 1000);

        $this->info("{$total} trace(s) de plus de {$jours} jour(s) supprimée(s).");

        return self::SUCCESS;
    }
}
