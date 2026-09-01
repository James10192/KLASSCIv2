<?php

namespace App\Console\Commands;

use App\Services\Reprise\InscriptionsAnneeEcoulee;
use Illuminate\Console\Command;

/**
 * Premiere marche d'une reprise d'historique : les eleves et leurs inscriptions.
 *
 * Ni frais, ni versement, ni reliquat — la dette est un chantier separe, qui
 * s'appuiera sur les inscriptions posees ici.
 *
 * Rien n'est devine : les eleves et leurs classes se passent en fichier. Toute
 * ligne qui ne se resout pas est ecartee et listee, jamais rattrapee.
 *
 * Sans `--apply`, la commande ne fait que montrer.
 */
class ReprendreInscriptionsAnneeEcoulee extends Command
{
    protected $signature = 'reprise:inscriptions-annee-ecoulee
        {--fichier= : Chemin du JSON decrivant les eleves et leur classe}
        {--annee= : Id de l\'annee universitaire ecoulee}
        {--limite= : N\'en traiter que les N premiers, pour un essai}
        {--trace= : Ou ecrire la correspondance matricule -> inscription}
        {--apply : Ecrire reellement. Sans ce drapeau, la commande ne fait que montrer}';

    protected $description = "Pose les eleves et les inscriptions d'une annee vecue hors de KLASSCI";

    public function handle(): int
    {
        $chemin = $this->option('fichier');
        $annee = $this->option('annee');

        if (! $chemin || ! $annee) {
            $this->error('--fichier et --annee sont obligatoires. Rien n\'est devine ici.');

            return self::FAILURE;
        }

        if (! is_readable($chemin)) {
            $this->error(sprintf('Fichier illisible : %s', $chemin));

            return self::FAILURE;
        }

        $charge = json_decode((string) file_get_contents($chemin), true);
        $lignes = is_array($charge) ? ($charge['lignes'] ?? $charge) : null;

        if (! is_array($lignes) || $lignes === []) {
            $this->error('Aucune ligne exploitable dans ce fichier.');

            return self::FAILURE;
        }

        if ($limite = $this->option('limite')) {
            $lignes = array_slice($lignes, 0, (int) $limite);
        }

        $resultat = app(InscriptionsAnneeEcoulee::class)->executer(
            $lignes,
            (int) $annee,
            (bool) $this->option('apply'),
        );

        $this->afficher($resultat);

        if ($resultat['applique'] && ($cheminTrace = $this->option('trace'))) {
            file_put_contents(
                $cheminTrace,
                json_encode($resultat['correspondance'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
            $this->info(sprintf('Correspondance matricule -> inscription ecrite dans %s', $cheminTrace));
        }

        return self::SUCCESS;
    }

    private function afficher(array $r): void
    {
        $this->line('');
        $this->info(sprintf(
            'Annee %s — %d ligne(s) lue(s) : %d retenue(s), %d ecartee(s).',
            $r['annee']['nom'], $r['lus'], $r['retenus'], $r['ecartes']
        ));

        $c = $r['a_creer'];
        $this->line('');
        $this->line(sprintf('  Eleves a creer          : %d   (deja en base : %d)',
            $c['etudiants'], $c['etudiants_deja_la']));
        $this->line(sprintf('  Inscriptions a creer    : %d   (deja en base : %d)',
            $c['inscriptions'], $c['inscriptions_deja_la']));

        $sansTel = count(array_filter($r['lignes'], fn ($l) => $l['telephone'] === null));
        if ($sansTel > 0) {
            $this->line(sprintf('  Sans telephone exploitable : %d — laisses vides, jamais devines', $sansTel));
        }

        if ($r['ecarts'] !== []) {
            $this->line('');
            $this->warn(sprintf('%d ligne(s) ecartee(s) :', count($r['ecarts'])));
            $this->table(
                ['Matricule', 'Classe', 'Motif'],
                array_map(fn ($e) => [
                    $e['matricule'] ?? '—',
                    $e['classe'] ?? '—',
                    $e['motif'],
                ], $r['ecarts'])
            );
        }

        $this->line('');
        if ($r['applique']) {
            $this->info(sprintf(
                'Ecrit : %d eleve(s), %d inscription(s).',
                $r['ecrit']['etudiants'], $r['ecrit']['inscriptions']
            ));
            $this->line('Aucun frais, aucun versement, aucun reliquat : c\'est l\'etape suivante.');
        } else {
            $this->warn('Rien n\'a ete ecrit. Ajoutez --apply pour appliquer.');
        }
    }
}
