<?php

namespace App\Console\Commands;

use App\Domain\Notes\PerimetreDeRecalcul;
use App\Jobs\RecomputeStudentResultatJob;
use App\Observers\ESBTPNoteObserver;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Recalcule en batch les résultats par matière depuis les notes courantes.
 *
 * Usages :
 *   php artisan notes:recompute --classe=12 --annee=4                      # une classe
 *   php artisan notes:recompute --classe=12 --annee=4 --periode=semestre1  # un semestre
 *   php artisan notes:recompute --classe=12 --annee=4 --dry-run            # simulation
 *   php artisan notes:recompute --toute-l-ecole                            # tout, apres confirmation
 *
 * ## Le perimetre est obligatoire
 *
 * Un recalcul ECRASE `esbtp_resultats.moyenne`, y compris une valeur saisie a
 * la main. Tant que la commande etait cassee (elle levait sur chaque couple),
 * la lancer sans filtre ne coutait rien ; reparee, elle recalculait l'ecole
 * entiere sans rien demander. Elle exige donc `--classe` et `--annee`, comme
 * `POST /api/cli/notes/recompute`, ou `--toute-l-ecole` suivi d'une
 * confirmation qui annonce le nombre de moyennes touchees. Hors terminal
 * interactif, la confirmation vaut non.
 *
 * NB : l'option --tenant existe pour cohérence CLI mais ce script
 * tourne dans le contexte d'une seule DB tenant (modèle SaaS multi-tenant
 * où chaque worker artisan est lancé contre un .env tenant).
 */
class NotesRecompute extends Command
{
    protected $signature = 'notes:recompute
        {--tenant=all : Tenant cible (informatif, exécution dans le contexte courant)}
        {--classe= : Restreindre à une classe (id)}
        {--matiere= : Restreindre à une matière (id)}
        {--etudiant= : Restreindre à un étudiant (id)}
        {--periode= : Restreindre à une période (semestre1|semestre2)}
        {--annee= : Restreindre à une année universitaire (id)}
        {--toute-l-ecole : Recalculer sans perimetre, apres confirmation}
        {--queue : Dispatcher les jobs sur la queue (sinon exécution sync)}
        {--dry-run : Liste ce qui serait recalculé sans le faire}';

    protected $description = 'Recalcule les résultats par matière depuis les notes (audit + bulletin touch).';

    public function handle(): int
    {
        $this->info('Recalcul des résultats — KLASSCI');
        $this->line('Tenant: '.$this->option('tenant'));

        $sansPerimetre = ! $this->option('classe') || ! $this->option('annee');

        // `annuel` n'est porte par aucune evaluation : le perimetre serait
        // toujours vide, et la commande annoncerait un succes sans rien avoir
        // recalcule. L'endpoint le refuse deja ; la commande s'aligne.
        if ($this->option('periode') && ! in_array($this->option('periode'), ['semestre1', 'semestre2'], true)) {
            $this->error('--periode accepte semestre1 ou semestre2.');

            return self::INVALID;
        }

        if ($sansPerimetre && ! $this->option('toute-l-ecole')) {
            $this->error('Précisez --classe et --annee, ou --toute-l-ecole pour tout recalculer.');

            return self::INVALID;
        }

        // La selection des couples (etudiant × matiere × periode) est partagee
        // avec `POST /api/cli/notes/recompute` : les deux repondaient a la meme
        // question chacun de son cote, et la copie avait deja perdu le filtre
        // `--etudiant` en chemin. Voir `App\Domain\Notes\PerimetreDeRecalcul`.
        $perimetre = PerimetreDeRecalcul::depuis([
            'classe_id' => $this->option('classe'),
            'matiere_id' => $this->option('matiere'),
            'etudiant_id' => $this->option('etudiant'),
            'periode' => $this->option('periode'),
            'annee_universitaire_id' => $this->option('annee'),
        ]);

        $combinations = $perimetre->couples();

        $total = $combinations->count();
        $this->line(sprintf('%d combinaison(s) (étudiant × matière × période) à recalculer.', $total));

        if ($total === 0) {
            $this->warn('Aucune note ne correspond aux filtres.');

            return self::SUCCESS;
        }

        if ($sansPerimetre && ! $this->option('dry-run') && ! $this->confirm(sprintf(
            'Recalculer %d moyenne(s) sur toute l\'école ? Chacune sera réécrite depuis les notes, '
            .'y compris celles saisies à la main.', $total
        ))) {
            $this->warn('Abandon : rien n\'a été recalculé.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->warn('--dry-run actif : aucune écriture.');
            $this->table(
                ['Étudiant', 'Classe', 'Matière', 'Année', 'Période'],
                $combinations->take(50)->map(fn ($c) => [
                    $c['etudiant_id'], $c['classe_id'], $c['matiere_id'],
                    $c['annee_universitaire_id'], $c['periode'],
                ])->all()
            );
            if ($total > 50) {
                $this->line('... ('.($total - 50).' autres masquées)');
            }

            return self::SUCCESS;
        }

        $useQueue = (bool) $this->option('queue');

        // Mute l'observer pour éviter qu'un éventuel save() collatéral
        // ne re-dispatch des jobs (on pilote tout depuis ici).
        ESBTPNoteObserver::$muted = true;

        try {
            if ($useQueue) {
                $bilan = $this->dispatcherSurLaFile($combinations);
            } else {
                // **L'execution passe par le meme service que l'endpoint CLI.**
                // Cette boucle avait sa propre copie, qui appelait
                // `(new RecomputeStudentResultatJob(...))->handle()` SANS
                // argument alors que `handle()` exige un
                // `NoteCalculationService`. Chaque couple levait un
                // `ArgumentCountError`, avale par le `catch (\Throwable)` de la
                // boucle : la commande affichait une ligne rouge par couple et
                // ne recalculait **rien**, dans son mode par defaut, depuis
                // toujours. Une selection unifiee ne sert a rien si l'execution
                // reste dupliquee — c'est la copie qui etait cassee.
                $barre = $this->output->createProgressBar($total);
                $barre->start();
                $bilan = $perimetre->recalculer(
                    $combinations, 'command', null,
                    fn () => $barre->advance()
                );
                $barre->finish();
                $this->newLine(2);
            }
        } finally {
            ESBTPNoteObserver::$muted = false;
        }

        $modifies = collect($bilan['lignes'])->where('change', true)->count();
        $verb = $useQueue ? 'dispatché(s)' : 'recalculé(s)';

        $this->info(sprintf(
            '%d résultat(s) %s, %d moyenne(s) modifiée(s), %d erreur(s).',
            count($bilan['lignes']), $verb, $modifies, $bilan['echecs']
        ));

        if (! empty($bilan['laissees'])) {
            $this->warn(sprintf(
                '%d moyenne(s) laissée(s) telle(s) quelle(s) : plus rien à moyenner (aucune note, ou seulement des absences). '
                .'Elles ne sont jamais remises à zéro ; leur sort est une décision de l\'école.',
                count($bilan['laissees'])
            ));
        }

        return $bilan['echecs'] === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Mode `--queue` : on pose les jobs sur la file et on s'arrete la.
     *
     * Aucun worker ne tourne sur les instances mutualisees — `config/queue.php`
     * vaut `database` et `app/Console/Kernel.php` ne planifie aucun
     * `queue:work`. Ce mode existe pour une instance qui en ferait tourner un ;
     * la commande le DIT plutot que de laisser croire au recalcul.
     *
     * Le garde contre le 0/20 s'applique ici aussi, par le meme diagnostic
     * ({@see PerimetreDeRecalcul::diagnostic()}), au moment de la mise en
     * file : un couple sans rien a moyenner n'est pas pose. Ce mode l'ignorait
     * d'abord, et un worker aurait reecrit le 0/20 que les autres chemins
     * refusent.
     *
     * @param  Collection<int, array<string,mixed>>  $couples
     * @return array{lignes:array<int,array<string,mixed>>, echecs:int, laissees:array<int,array<string,mixed>>}
     */
    private function dispatcherSurLaFile(Collection $couples): array
    {
        $this->warn('--queue : les jobs sont posés sur la file. Rien n\'est recalculé tant qu\'un worker ne les consomme pas.');

        $lignes = [];
        $laissees = [];
        $echecs = 0;

        foreach ($couples as $c) {
            try {
                $diagnostic = PerimetreDeRecalcul::diagnostic($c);

                if ($diagnostic['laissee'] !== null) {
                    $laissees[] = $diagnostic['laissee'];
                }

                if ($diagnostic['statut'] !== PerimetreDeRecalcul::RECALCULE) {
                    continue;
                }

                RecomputeStudentResultatJob::dispatch(
                    etudiantId: $c['etudiant_id'],
                    classeId: $c['classe_id'],
                    matiereId: $c['matiere_id'],
                    anneeUniversitaireId: $c['annee_universitaire_id'],
                    periode: $c['periode'],
                    source: 'command',
                    triggeredBy: null,
                );
                $lignes[] = $c + ['change' => false];
            } catch (\Throwable $e) {
                $echecs++;
                $this->error(sprintf(
                    'Erreur étudiant=%d matière=%d : %s',
                    $c['etudiant_id'], $c['matiere_id'], $e->getMessage()
                ));
            }
        }

        return ['lignes' => $lignes, 'echecs' => $echecs, 'laissees' => $laissees];
    }
}
