<?php

namespace App\Console\Commands;

use App\Domain\EmploiTemps\DiagnosticDesDatesDeSeance;
use Illuminate\Console\Command;

/**
 * Pose la date manquante des séances qui n'en ont pas.
 *
 * **Simule par défaut.** Écrire demande `--appliquer`, explicitement. Le
 * contraire — écrire sauf si on demande `--dry-run` — met la charge de la
 * prudence sur qui tape la commande, et huit instances en service ne sont pas
 * l'endroit pour ce pari.
 *
 * Rejouable : n'écrit que là où `date_seance` est nulle, donc un second passage
 * ne déplace aucune séance déjà datée.
 *
 * Usage : php artisan seances:date-backfill [--appliquer] [--emploi-temps=ID]
 */
class SeancesDateBackfillCommand extends Command
{
    protected $signature = 'seances:date-backfill
                            {--appliquer : Écrire réellement (sans cette option, la commande se contente de simuler)}
                            {--emploi-temps= : Se limiter à un emploi du temps, pour un essai sur un périmètre restreint}';

    protected $description = 'Recalcule la date des séances qui n\'en ont pas (simule, sauf --appliquer)';

    public function handle(DiagnosticDesDatesDeSeance $diagnostic): int
    {
        $appliquer = (bool) $this->option('appliquer');
        $emploiTempsId = $this->option('emploi-temps') !== null
            ? (int) $this->option('emploi-temps')
            : null;

        if ($appliquer && ! $this->confirmerEcriture($diagnostic, $emploiTempsId)) {
            $this->line('Abandon : rien n\'a été écrit.');

            return self::SUCCESS;
        }

        $resultat = $diagnostic->rattraper($appliquer, $emploiTempsId);

        $this->newLine();
        $this->info($appliquer ? 'Rattrapage appliqué' : 'Simulation (aucune écriture)');
        $this->line(sprintf('  Dates posées%s : %d', $appliquer ? '   ' : ' (simulées)', $resultat['dates_posees']));

        foreach ($resultat['laissees_sans_date'] as $raison => $nombre) {
            $this->warn(sprintf('  Laissées sans date — %s : %d', $raison, $nombre));
        }

        if (! $appliquer && $resultat['dates_posees'] > 0) {
            $this->newLine();
            $this->line('Pour écrire : relancer avec --appliquer');
        }

        return self::SUCCESS;
    }

    /**
     * Demande confirmation, en disant d'abord l'ampleur.
     *
     * Sans réponse possible (tâche planifiée, appel non interactif), Laravel
     * rend le défaut — ici `false`, donc rien ne s'écrit. C'est le bon sens de
     * l'échec pour une commande qui touche huit instances.
     */
    private function confirmerEcriture(DiagnosticDesDatesDeSeance $diagnostic, ?int $emploiTempsId): bool
    {
        // Le relevé porte SUR LE MÊME périmètre que l'écriture qui va suivre.
        // Sans ce second argument, la confirmation affichait les totaux de
        // toute l'instance en les suffixant « emploi du temps X seulement » :
        // l'opérateur lisait un grand nombre juste au moment de n'écrire que
        // sur une poignée de lignes.
        $rapport = $diagnostic->rapport(0, $emploiTempsId);

        $this->warn(sprintf(
            'À écrire : %d séance(s) sans date, dont %d recalculable(s)%s.',
            $rapport['total_sans_date'],
            $rapport['rattrapables'],
            $emploiTempsId !== null ? " (emploi du temps {$emploiTempsId} seulement)" : '',
        ));

        return $this->confirm('Écrire ces dates ?', false);
    }
}
