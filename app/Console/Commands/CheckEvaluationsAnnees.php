<?php

namespace App\Console\Commands;

use App\Domain\Notes\MoyennesLaissees;
use App\Domain\Notes\RecalculApresDeplacement;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Donne une année universitaire aux évaluations qui n'en ont pas.
 *
 * D'OÙ VIENT L'ANNÉE. Pas de `esbtp_classes.annee_universitaire_id` : une
 * classe n'appartient à aucune année (`classes-universelles-pas-annee.md`),
 * cette colonne est un reliquat. Dans l'ordre :
 *  1. les INSCRIPTIONS des élèves notés sur l'évaluation, dans sa classe —
 *     l'année que TOUS partagent. Un redoublant inscrit deux ans dans la même
 *     classe ne rend donc pas la réponse ambiguë, ses camarades la tranchent ;
 *  2. la DATE de l'évaluation, si elle tombe dans une seule année ;
 *  3. `--annee=`, si l'opérateur l'a donnée.
 * Sinon l'évaluation est laissée sans année et NOMMÉE. L'ancienne version
 * posait l'année courante : une supposition qui faisait entrer des notes d'une
 * autre année dans les moyennes de celle-ci, sans que personne le sache.
 *
 * LES MOYENNES. Tant qu'une évaluation n'a pas d'année, ses notes n'entrent
 * dans aucune moyenne ENREGISTRÉE (`esbtp_resultats`) — la moyenne annuelle
 * de secours de `BulletinService::calculateStudentAverageForPeriode()` ne
 * filtre pas l'année, elle. Lui en donner une les y fait entrer : la ligne
 * d'`esbtp_resultats` de la coordonnée rejointe est recalculée, par le même
 * garde contre le zéro que tout déplacement, une fois pour tout le lot
 * ({@see RecalculApresDeplacement::pourPlusieurs()}) : plusieurs évaluations
 * d'une même coordonnée ne la recalculent qu'une fois, et ne nomment qu'une
 * fois la même moyenne laissée. La coordonnée d'avant, sans année, est
 * incomplète et sautée : il n'y a donc pas de moyenne vidée.
 */
class CheckEvaluationsAnnees extends Command
{
    protected $signature = 'esbtp:check-evaluations-annees
        {--fix : Attribuer les années sans demander confirmation}
        {--annee= : Id de l\'année à poser quand ni les inscriptions ni la date ne tranchent}';

    protected $description = 'Donne une année universitaire aux évaluations qui n\'en ont pas, et recalcule les moyennes qu\'elles rejoignent';

    public function handle(): int
    {
        $evaluations = ESBTPEvaluation::whereNull('annee_universitaire_id')->get();
        $this->info("{$evaluations->count()} évaluation(s) sans année universitaire.");

        if ($evaluations->isEmpty()) {
            return Command::SUCCESS;
        }

        $parDefaut = $this->option('annee');
        if ($parDefaut !== null && ! ESBTPAnneeUniversitaire::whereKey($parDefaut)->exists()) {
            $this->error("Année universitaire #{$parDefaut} introuvable.");

            return Command::FAILURE;
        }

        if (! $this->option('fix') && ! $this->confirm('Attribuer une année à ces évaluations ?')) {
            return Command::SUCCESS;
        }

        $parSource = ['inscriptions' => 0, 'date' => 0, 'option --annee' => 0];
        $nonResolues = [];
        $echecs = 0;
        $datees = [];

        foreach ($evaluations as $evaluation) {
            try {
                [$anneeId, $source] = $this->anneeDe($evaluation, $parDefaut !== null ? (int) $parDefaut : null);

                if ($anneeId === null) {
                    $nonResolues[] = $evaluation->id;

                    continue;
                }

                $avant = [
                    'classe_id' => $evaluation->classe_id,
                    'matiere_id' => $evaluation->matiere_id,
                    'periode' => $evaluation->periode,
                    'annee_universitaire_id' => null,
                ];
                $evaluation->annee_universitaire_id = $anneeId;
                $evaluation->save();
                $parSource[$source]++;
                $datees[] = ['evaluation' => $evaluation, 'avant' => $avant];
            } catch (\Exception $e) {
                $this->error("Évaluation #{$evaluation->id} : {$e->getMessage()}");
                $echecs++;
            }
        }

        foreach ($parSource as $source => $nombre) {
            $this->info("{$nombre} année(s) tirée(s) de : {$source}");
        }

        // Les années sont DÉJÀ enregistrées : un recalcul interrompu ne les
        // défait pas, il se dit à part des échecs d'écriture.
        try {
            $bilan = RecalculApresDeplacement::pourPlusieurs($datees);
        } catch (\Throwable $e) {
            $this->error("Recalcul des moyennes interrompu, les années restent posées : {$e->getMessage()}");
            $this->warn('Relancez `notes:recompute` sur les classes concernées.');

            return Command::FAILURE;
        }
        $this->info("{$bilan['recalculs_tentes']} recalcul(s) de moyenne lancé(s).");

        $avertissement = MoyennesLaissees::enUnePhrase($bilan, 'n\'ont, même avec ces notes, que des absences à moyenner');
        if ($avertissement !== null) {
            $this->warn($avertissement);
            foreach ($bilan['orphelins'] as $o) {
                $this->line("  élève #{$o['etudiant_id']}, classe #{$o['classe_id']}, matière #{$o['matiere_id']}, {$o['periode']} : {$o['moyenne']}");
            }
        }
        if ($nonResolues !== []) {
            $this->warn(count($nonResolues).' évaluation(s) laissée(s) sans année — ni les inscriptions ni la date ne tranchent : #'
                .implode(', #', $nonResolues).'. Relancez avec --annee=ID pour les attribuer.');
        }
        if ($echecs > 0) {
            $this->warn("{$echecs} évaluation(s) en échec, laissée(s) sans année.");
        }

        return $echecs > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * @return array{0:?int, 1:?string} [année, source]
     */
    private function anneeDe(ESBTPEvaluation $evaluation, ?int $parDefaut): array
    {
        if ($evaluation->classe_id) {
            // Les mêmes notes que celles que le recalcul lira : ni effacées ni archivées.
            $eleves = DB::table('esbtp_notes')
                ->where('evaluation_id', $evaluation->id)
                ->whereNull('deleted_at')
                ->whereNull('archived_at')
                ->distinct()
                ->count('etudiant_id');

            if ($eleves > 0) {
                $communes = DB::table('esbtp_notes as n')
                    ->join('esbtp_inscriptions as i', 'i.etudiant_id', '=', 'n.etudiant_id')
                    ->where('n.evaluation_id', $evaluation->id)
                    ->whereNull('n.deleted_at')
                    ->whereNull('n.archived_at')
                    ->where('i.classe_id', $evaluation->classe_id)
                    ->whereNull('i.deleted_at')
                    // Une inscription annulée ne dit pas l'année.
                    ->where(fn ($q) => $q->whereNull('i.status')->orWhereNotIn('i.status', ESBTPInscription::STATUTS_ANNULES))
                    ->groupBy('i.annee_universitaire_id')
                    ->havingRaw('COUNT(DISTINCT n.etudiant_id) = ?', [$eleves])
                    ->pluck('i.annee_universitaire_id');

                if ($communes->count() === 1) {
                    return [(int) $communes->first(), 'inscriptions'];
                }
            }
        }

        if ($evaluation->date_evaluation) {
            $jour = $evaluation->date_evaluation->toDateString();
            $annees = ESBTPAnneeUniversitaire::whereDate('start_date', '<=', $jour)
                ->whereDate('end_date', '>=', $jour)
                ->pluck('id');

            if ($annees->count() === 1) {
                return [(int) $annees->first(), 'date'];
            }
        }

        return $parDefaut !== null ? [$parDefaut, 'option --annee'] : [null, null];
    }
}
