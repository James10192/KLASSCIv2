<?php

namespace App\Console\Commands;

use App\Services\Reprise\RepriseElevesInsolvables;
use Illuminate\Console\Command;

/**
 * Recree l'annee ecoulee des eleves qui doivent encore de l'argent.
 *
 * Une ecole qui arrive sur KLASSCI avec un arriere n'a aucun moyen de le faire
 * apparaitre : l'ecran de reinscription lit l'annee precedente, et sur un eleve
 * sans passe il ne trouve rien. Cette commande reconstitue ce passe a partir de
 * l'etat de compte que l'ecole tient deja.
 *
 * Rien n'est devine : les eleves, les montants et les classes se passent en
 * fichier. Toute ligne dont les comptes ne tombent pas juste est ecartee et
 * listee, jamais ajustee.
 *
 * Sans `--apply`, la commande ne fait que montrer.
 */
class ReprendreElevesInsolvables extends Command
{
    protected $signature = 'reprise:eleves-insolvables
        {--fichier= : Chemin du JSON decrivant les eleves et leurs montants}
        {--annee= : Id de l\'annee universitaire ecoulee, celle qui porte la dette}
        {--limite= : N\'en traiter que les N premiers, pour un essai}
        {--apply : Ecrire reellement. Sans ce drapeau, la commande ne fait que montrer}';

    protected $description = "Reconstitue l'annee ecoulee des eleves debiteurs, apres l'avoir montree";

    public function handle(): int
    {
        $chemin = $this->option('fichier');
        $annee = $this->option('annee');

        if (! $chemin || ! $annee) {
            $this->error('--fichier et --annee sont obligatoires. Rien n\'est devine ici : ce sont des eleves et des montants.');

            return self::FAILURE;
        }

        if (! is_readable($chemin)) {
            $this->error(sprintf('Fichier illisible : %s', $chemin));

            return self::FAILURE;
        }

        $charge = json_decode((string) file_get_contents($chemin), true);
        if (! is_array($charge)) {
            $this->error('Le fichier n\'est pas un JSON exploitable.');

            return self::FAILURE;
        }

        $lignes = $charge['lignes'] ?? $charge;
        if (! is_array($lignes) || $lignes === []) {
            $this->error('Aucune ligne a traiter dans ce fichier.');

            return self::FAILURE;
        }

        if ($limite = $this->option('limite')) {
            $lignes = array_slice($lignes, 0, (int) $limite);
        }

        // La regle vit dans le service : la console et l'API CLI l'appellent
        // toutes deux, et deux copies d'une regle qui touche a des montants
        // finiraient par diverger sans que rien ne le dise.
        $resultat = app(RepriseElevesInsolvables::class)->executer(
            $lignes,
            (int) $annee,
            (bool) $this->option('apply'),
        );

        $this->afficher($resultat);

        return self::SUCCESS;
    }

    private function afficher(array $r): void
    {
        $this->line('');
        $this->info(sprintf(
            '%d ligne(s) lue(s) — %d retenue(s), %d ecartee(s).',
            $r['lus'], $r['retenus'], $r['ecartes']
        ));

        $this->line('');
        $this->line('  Montant reclame  : '.$this->somme($r['totaux']['montant_reclame']));
        $this->line('  Deja encaisse    : '.$this->somme($r['totaux']['paye']));
        $this->line('  Reste du         : '.$this->somme($r['totaux']['reste_du']));

        if ($r['ecarts'] !== []) {
            $this->line('');
            $this->warn(sprintf('%d ligne(s) ecartee(s) — aucune n\'a ete ajustee :', count($r['ecarts'])));
            $this->table(
                ['Matricule', 'Classe', 'Motif', 'Detail'],
                array_map(fn ($e) => [
                    $e['matricule'] ?? '—',
                    $e['classe'] ?? '—',
                    $e['motif'],
                    $this->detail($e),
                ], $r['ecarts'])
            );
        }

        $this->line('');
        if ($r['applique']) {
            $this->info(sprintf(
                'Ecrit : %d eleve(s), %d inscription(s), %d souscription(s), %d versement(s).',
                $r['totaux']['etudiants'] ?? 0,
                $r['totaux']['inscriptions'] ?? 0,
                $r['totaux']['souscriptions'] ?? 0,
                $r['totaux']['paiements'] ?? 0,
            ));
            $this->line('Les reliquats ne sont PAS crees ici : ils se poseront a la reinscription.');
        } else {
            $this->warn('Rien n\'a ete ecrit. Ajoutez --apply pour appliquer.');
        }
    }

    private function detail(array $ecart): string
    {
        $parts = [];
        foreach (['ecart', 'total_souscrit', 'montant_reclame_pdf', 'reste_calcule', 'reste_du_pdf'] as $cle) {
            if (isset($ecart[$cle])) {
                $parts[] = $cle.'='.$this->somme((float) $ecart[$cle]);
            }
        }
        if (! empty($ecart['categories_sans_tarif'])) {
            $parts[] = 'sans tarif : '.implode(', ', $ecart['categories_sans_tarif']);
        }

        return implode(' | ', $parts) ?: '—';
    }

    private function somme(float $montant): string
    {
        return number_format($montant, 0, ',', ' ').' F';
    }
}
