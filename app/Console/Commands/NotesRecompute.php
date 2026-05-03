<?php

namespace App\Console\Commands;

use App\Jobs\RecomputeStudentResultatJob;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
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

        // 1. Construire la requête sur les évaluations distinctes (clé du recompute)
        $query = ESBTPEvaluation::query()
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('classe_id')
            ->whereNotNull('matiere_id')
            ->whereNotNull('annee_universitaire_id')
            ->whereNotNull('periode');

        if ($classeId = $this->option('classe')) {
            $query->where('classe_id', (int) $classeId);
        }
        if ($matiereId = $this->option('matiere')) {
            $query->where('matiere_id', (int) $matiereId);
        }
        if ($periode = $this->option('periode')) {
            $query->where('periode', $periode);
        }
        if ($anneeId = $this->option('annee')) {
            $query->where('annee_universitaire_id', (int) $anneeId);
        }

        // 2. Récupérer la liste distincte des (etudiant_id, classe_id, matiere_id, annee, periode)
        //    en passant par les notes attachées aux évaluations sélectionnées
        $evaluationIds = $query->pluck('id');

        if ($evaluationIds->isEmpty()) {
            $this->warn('Aucune évaluation ne correspond aux filtres.');

            return self::SUCCESS;
        }

        $notesQuery = ESBTPNote::query()
            ->whereIn('evaluation_id', $evaluationIds)
            ->select('etudiant_id', 'evaluation_id')
            ->with('evaluation:id,classe_id,matiere_id,annee_universitaire_id,periode');

        if ($etudiantId = $this->option('etudiant')) {
            $notesQuery->where('etudiant_id', (int) $etudiantId);
        }

        $combinations = $notesQuery->get()
            ->map(function (ESBTPNote $note) {
                $eval = $note->evaluation;
                if (! $eval) {
                    return null;
                }

                return [
                    'etudiant_id' => (int) $note->etudiant_id,
                    'classe_id' => (int) $eval->classe_id,
                    'matiere_id' => (int) $eval->matiere_id,
                    'annee_universitaire_id' => (int) $eval->annee_universitaire_id,
                    'periode' => (string) $eval->periode,
                ];
            })
            ->filter()
            ->unique(fn ($c) => implode('|', $c))
            ->values();

        $total = $combinations->count();
        $this->line(sprintf('%d combinaison(s) (étudiant × matière × période) à recalculer.', $total));

        if ($total === 0) {
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
        $userId = null; // CLI sans contexte d'auth

        // Mute l'observer pour éviter qu'un éventuel save() collatéral
        // ne re-dispatch des jobs (on pilote tout depuis ici).
        ESBTPNoteObserver::$muted = true;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $dispatched = 0;
        $errors = 0;

        try {
            foreach ($combinations as $c) {
                try {
                    if ($useQueue) {
                        RecomputeStudentResultatJob::dispatch(
                            etudiantId: $c['etudiant_id'],
                            classeId: $c['classe_id'],
                            matiereId: $c['matiere_id'],
                            anneeUniversitaireId: $c['annee_universitaire_id'],
                            periode: $c['periode'],
                            source: 'command',
                            triggeredBy: $userId,
                        );
                    } else {
                        (new RecomputeStudentResultatJob(
                            etudiantId: $c['etudiant_id'],
                            classeId: $c['classe_id'],
                            matiereId: $c['matiere_id'],
                            anneeUniversitaireId: $c['annee_universitaire_id'],
                            periode: $c['periode'],
                            source: 'command',
                            triggeredBy: $userId,
                        ))->handle();
                    }
                    $dispatched++;
                } catch (\Throwable $e) {
                    $errors++;
                    $this->newLine();
                    $this->error(sprintf(
                        'Erreur étudiant=%d matière=%d : %s',
                        $c['etudiant_id'], $c['matiere_id'], $e->getMessage()
                    ));
                }

                $bar->advance();
            }
        } finally {
            ESBTPNoteObserver::$muted = false;
            $bar->finish();
            $this->newLine(2);
        }

        $verb = $useQueue ? 'dispatché(s)' : 'recalculé(s)';
        $this->info(sprintf('%d résultat(s) %s, %d erreur(s).', $dispatched, $verb, $errors));

        return $errors === 0 ? self::SUCCESS : self::FAILURE;
    }
}
