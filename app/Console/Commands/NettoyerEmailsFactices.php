<?php

namespace App\Console\Commands;

use App\Services\Emails\NettoyageAdressesFactices;
use Illuminate\Console\Command;

/**
 * Lecture seule par defaut. `--execute` remet a NULL les adresses FACTICES
 * (domaines fabriques par KLASSCI, qui n'existent pas) des etudiants, parents,
 * candidatures et reservations, apres sauvegarde. Les comptes utilisateurs ne
 * sont touches qu'avec `--inclure-comptes`. Les fautes de frappe ne sont que
 * rapportees, avec leur suggestion.
 */
class NettoyerEmailsFactices extends Command
{
    protected $signature = 'emails:nettoyer-factices
        {--execute : écrit réellement (sinon simple rapport)}
        {--inclure-comptes : vide aussi les adresses factices des comptes utilisateurs}
        {--sans-mx : n’interroge pas le DNS}';

    protected $description = 'Liste les adresses e-mail factices et fautives ; avec --execute, vide les factices après sauvegarde.';

    public function handle(NettoyageAdressesFactices $nettoyage): int
    {
        $rapport = $nettoyage->rapport(! $this->option('sans-mx'));

        if ($rapport === []) {
            $this->info('Aucune adresse factice ou fautive.');
        } else {
            $this->table(
                ['Table', 'Colonne', 'Domaine', 'Adresses', 'Constat', 'Suggestion'],
                array_map(fn ($l) => [$l['table'], $l['colonne'], $l['domaine'], $l['nombre'], $l['type'], $l['suggestion'] ?? 'aucune'], $rapport)
            );
        }

        $comptes = $nettoyage->comptesParRole();
        if ($comptes !== []) {
            $this->line('Comptes utilisateurs à adresse factice, par rôle (vidés seulement avec --inclure-comptes) :');
            $this->table(['Rôle', 'Comptes'], array_map(fn ($role, $n) => [$role, $n], array_keys($comptes), $comptes));
        }

        if (! $this->option('execute')) {
            $this->comment('Simulation : rien n\'a été modifié. Relancer avec --execute pour vider les adresses factices.');

            return self::SUCCESS;
        }

        $resultat = $nettoyage->executer((bool) $this->option('inclure-comptes'));
        if ($resultat['modifiees'] === 0) {
            $this->info('Aucune adresse factice à vider.');

            return self::SUCCESS;
        }

        $this->info(sprintf('%d adresses factices vidées. Sauvegarde : %s', $resultat['modifiees'], $resultat['sauvegarde']));
        $this->comment('Les fautes de frappe n\'ont pas été modifiées : à corriger avec la famille.');

        return self::SUCCESS;
    }
}
