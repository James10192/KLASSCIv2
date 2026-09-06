<?php

namespace App\Console\Commands;

use App\Models\ESBTPAnneeUniversitaire;
use App\Services\EcheancierRecomputeService;
use Illuminate\Console\Command;

/**
 * Regenere les snapshots d'echeancier des inscriptions actives.
 *
 * Pourquoi cette commande existe : le snapshot n'est ecrit qu'a l'ouverture de
 * la fiche financiere d'un etudiant (RelanceCalculationService::preloadForSingle).
 * Ni l'inscription, ni l'encaissement, ni l'activation d'une regle ne le
 * produisent. Tant qu'une fiche n'a pas ete ouverte, l'analytique lit donc un
 * parc presque vide et raisonne en mode degrade. Cette commande comble le trou
 * pour tout un parc, par lots, sans rien calculer elle-meme.
 *
 * Usages :
 *   php artisan echeanciers:recompute --dry-run              # compte, n'ecrit rien
 *   php artisan echeanciers:recompute                        # annee courante
 *   php artisan echeanciers:recompute --annee=3 --chunk=200  # une annee, lots de 200
 *   php artisan echeanciers:recompute --inscription=1234     # une seule inscription
 */
class EcheanciersRecompute extends Command
{
    public const NOM = 'echeanciers:recompute';

    protected $signature = self::NOM.'
        {--annee= : Annee universitaire (id) ; par defaut l\'annee courante}
        {--inscription= : Ne traiter qu\'une inscription (id) ; ignore le filtre d\'annee}
        {--dry-run : Compter ce qui serait recalcule sans rien ecrire}
        {--chunk='.EcheancierRecomputeService::CHUNK_PAR_DEFAUT.' : Taille des lots}';

    protected $description = 'Regenere les snapshots d\'echeancier des inscriptions actives (couverture analytics).';

    public function handle(EcheancierRecomputeService $service): int
    {
        $inscriptionId = $this->option('inscription') !== null ? (int) $this->option('inscription') : null;
        $anneeId = null;

        if (! $inscriptionId) {
            $annee = $this->resoudreAnnee();
            if (! $annee) {
                $this->error($this->option('annee')
                    ? 'Annee universitaire introuvable : #'.$this->option('annee')
                    : 'Aucune annee universitaire courante. Precisez --annee=ID.');

                return self::FAILURE;
            }
            $anneeId = (int) $annee->id;
            $this->line(sprintf('Annee : <fg=cyan>%s</> (#%d)', $annee->name, $annee->id));
        } else {
            $this->line('Inscription : #'.$inscriptionId);
        }

        $dryRun = (bool) $this->option('dry-run');
        $chunk = (int) $this->option('chunk');

        if ($dryRun) {
            $this->warn('Simulation : rien ne sera ecrit.');
        }

        $rapport = $service->recalculer(
            $anneeId,
            $inscriptionId,
            $dryRun,
            $chunk,
            fn (int $traitees) => $this->output->write("\r  ".$traitees.' traitee(s)'),
        );

        if (! $dryRun && $rapport['traitees'] > 0) {
            $this->newLine();
        }

        $this->afficher($rapport);

        return $rapport['erreurs'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function resoudreAnnee(): ?ESBTPAnneeUniversitaire
    {
        if ($id = $this->option('annee')) {
            return ESBTPAnneeUniversitaire::find((int) $id);
        }

        return ESBTPAnneeUniversitaire::where('is_current', true)->first();
    }

    /**
     * @param  array<string, mixed>  $rapport
     */
    private function afficher(array $rapport): void
    {
        $lignes = [
            ['Inscriptions dans le perimetre', $rapport['perimetre']],
            ['Sans snapshot (a creer)', $rapport['a_creer']],
            ['Avec snapshot (a mettre a jour)', $rapport['a_mettre_a_jour']],
        ];

        if (! $rapport['dry_run']) {
            $lignes[] = ['Traitees', $rapport['traitees']];
            $lignes[] = ['Creees', $rapport['creees']];
            $lignes[] = ['Mises a jour', $rapport['mises_a_jour']];
            $lignes[] = ['Sans regle (echeance unique par defaut)', $rapport['sans_regle']];
            $lignes[] = ['Erreurs', $rapport['erreurs']];
        }

        $this->table(['Compte-rendu', $rapport['dry_run'] ? 'Simulation' : 'Execute'], $lignes);

        foreach ($rapport['echantillon_erreurs'] as $erreur) {
            $this->error(sprintf('  Inscription #%d : %s', $erreur['inscription_id'], $erreur['message']));
        }

        if ($rapport['dry_run'] && $rapport['perimetre'] > 0) {
            $this->line('Relancer sans --dry-run pour ecrire.');
        }

        if (! $rapport['dry_run'] && $rapport['sans_regle'] > 0 && $rapport['sans_regle'] === $rapport['traitees'] - $rapport['erreurs']) {
            $this->warn('Aucune inscription traitee n\'a trouve de regle d\'echeancier : les regles actives ne couvrent pas ce parc (portee filiere/niveau/statut a verifier).');
        }
    }
}
