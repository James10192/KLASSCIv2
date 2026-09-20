<?php

namespace App\Console\Commands;

use App\Domain\Notes\PerimetreDeRecalcul;
use App\Jobs\RecomputeStudentResultatJob;
use App\Observers\ESBTPNoteObserver;
use Illuminate\Console\Command;

/**
 * Recalcule en batch les résultats par matière depuis les notes courantes.
 *
 * Usages :
 *   php artisan notes:recompute                                  # tout, en sync
 *   php artisan notes:recompute --queue                          # via queue
 *   php artisan notes:recompute --classe=12 --periode=semestre1  # subset
 *   php artisan notes:recompute --dry-run                        # simulation
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
        {--periode= : Restreindre à une période (semestre1|semestre2|annuel)}
        {--annee= : Restreindre à une année universitaire (id)}
        {--queue : Dispatcher les jobs sur la queue (sinon exécution sync)}
        {--dry-run : Liste ce qui serait recalculé sans le faire}';

    protected $description = 'Recalcule les résultats par matière depuis les notes (audit + bulletin touch).';

    public function handle(): int
    {
        $this->info('Recalcul des résultats — KLASSCI');
        $this->line('Tenant: '.$this->option('tenant'));

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
     * @param  \Illuminate\Support\Collection<int, array<string,mixed>>  $couples
     * @return array{lignes:array<int,array<string,mixed>>, echecs:int}
     */
    private function dispatcherSurLaFile(\Illuminate\Support\Collection $couples): array
    {
        $this->warn('--queue : les jobs sont posés sur la file. Rien n\'est recalculé tant qu\'un worker ne les consomme pas.');

        $lignes = [];
        $echecs = 0;

        foreach ($couples as $c) {
            try {
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

        return ['lignes' => $lignes, 'echecs' => $echecs];
    }
}
