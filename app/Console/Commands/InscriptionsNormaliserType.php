<?php

namespace App\Console\Commands;

use App\Services\Inscriptions\NormalisationTypeInscription;
use Illuminate\Console\Command;

class InscriptionsNormaliserType extends Command
{
    protected $signature = 'inscriptions:normaliser-type {--apply : Ecrire reellement les corrections}';

    protected $description = 'Recense et normalise esbtp_inscriptions.type_inscription (montre par defaut)';

    public function handle(NormalisationTypeInscription $service): int
    {
        $recensement = $service->recenser();

        $this->info('Ce que la colonne contient :');
        foreach ($recensement['par_valeur'] as $valeur => $total) {
            $this->line(sprintf('  %-28s %d', $valeur === null ? '(null)' : $valeur, $total));
        }

        if ($recensement['hors_enum_total'] > 0) {
            $this->newLine();
            $this->warn(sprintf('%d ligne(s) hors enum :', $recensement['hors_enum_total']));
            foreach ($recensement['hors_enum'] as $valeur => $total) {
                $this->line(sprintf('  %-28s %d', $valeur, $total));
            }
        }

        $this->newLine();
        $this->line(sprintf(
            '%d inscription(s) dont le type declare contredit le rang reel.',
            $recensement['desaccords_total']
        ));

        $resultat = $service->executer((bool) $this->option('apply'));

        if ($resultat['total'] === 0) {
            $this->newLine();
            $this->info('Rien a normaliser.');

            return self::SUCCESS;
        }

        $this->newLine();
        foreach ($resultat['plan'] as $ligne) {
            $this->line(sprintf('  %-24s -> %-24s %d ligne(s)', $ligne['de'], $ligne['vers'], $ligne['lignes']));
        }

        $this->newLine();
        if ($resultat['applique']) {
            $this->info(sprintf('%d ligne(s) normalisee(s).', $resultat['total']));
        } else {
            $this->warn(sprintf(
                "%d ligne(s) a normaliser. Rien n'a ete ecrit — relancez avec --apply.",
                $resultat['total']
            ));
        }

        return self::SUCCESS;
    }
}
