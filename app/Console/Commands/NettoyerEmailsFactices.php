<?php

namespace App\Console\Commands;

use App\Services\Emails\NettoyageAdressesFactices;
use Illuminate\Console\Command;

/**
 * Lecture seule par defaut. `--execute` remet a NULL les adresses FACTICES
 * (domaines fabriques par KLASSCI, qui n'existent pas), apres sauvegarde.
 * Les fautes de frappe ne sont que rapportees, avec leur suggestion.
 */
class NettoyerEmailsFactices extends Command
{
    protected $signature = 'emails:nettoyer-factices
        {--execute : ecrit reellement (sinon simple rapport)}
        {--sans-mx : ne pas interroger le DNS}';

    protected $description = 'Liste les adresses e-mail factices et fautives ; avec --execute, vide les factices apres sauvegarde.';

    public function handle(NettoyageAdressesFactices $nettoyage): int
    {
        $rapport = $nettoyage->rapport(! $this->option('sans-mx'));

        if ($rapport === []) {
            $this->info('Aucune adresse factice ou fautive.');
        } else {
            $this->table(
                ['Table', 'Colonne', 'Domaine', 'Adresses', 'Constat', 'Suggestion'],
                array_map(fn ($l) => [$l['table'], $l['colonne'], $l['domaine'], $l['nombre'], $l['etat'], $l['suggestion'] ?? 'aucune'], $rapport)
            );
        }

        if (! $this->option('execute')) {
            $this->comment('Simulation : rien n\'a été modifié. Relancer avec --execute pour vider les adresses factices.');

            return self::SUCCESS;
        }

        $resultat = $nettoyage->executer();
        if ($resultat['modifiees'] === 0) {
            $this->info('Aucune adresse factice à vider.');

            return self::SUCCESS;
        }

        $this->info(sprintf('%d adresses factices vidées. Sauvegarde : %s', $resultat['modifiees'], $resultat['sauvegarde']));
        $this->comment('Les fautes de frappe n\'ont pas été modifiées : à corriger avec la famille.');

        return self::SUCCESS;
    }
}
