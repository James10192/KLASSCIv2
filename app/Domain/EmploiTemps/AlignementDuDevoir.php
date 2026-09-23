<?php

namespace App\Domain\EmploiTemps;

use App\Domain\Notes\MoyennesLaissees;
use App\Domain\Notes\RecalculApresDeplacement;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPSeanceCours;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Aligne l'évaluation « devoir » liée à une séance sur cette séance.
 *
 * UNE SEULE COORDONNÉE SUIT LA SÉANCE : LA MATIÈRE, ET SEULEMENT SI ELLE A
 * CHANGÉ. Une coordonnée du devoir — classe, matière, période, année — déplace
 * ses notes et les moyennes des deux côtés. Les réécrire toutes à chaque
 * enregistrement de la séance défaisait en silence une correction faite sur
 * l'écran de l'évaluation (une période remise à la main, par exemple), dès
 * qu'on retouchait la salle ou le titre de la séance. Les autres restent
 * celles du devoir :
 *  - la classe et l'emploi du temps ne se changent pas depuis l'écran de la
 *    séance (`reglesDeModification()`) ;
 *  - la PÉRIODE n'est pas réalignée quand le jour change. La date d'une séance
 *    ne bouge qu'à l'intérieur de la semaine de son emploi du temps
 *    (`ESBTPEmploiTemps::dateDuJour()`) : ce changement ne dit rien d'un autre
 *    semestre, et réaligner défaisait la correction manuelle faite sur
 *    l'écran de l'évaluation. La période est posée à la création du devoir
 *    (`store()`, qui la déduit encore du mois — règle héritée, qui devrait lire
 *    le semestre de l'emploi du temps ; hors de ce chantier).
 * Titre, description, date et durée, qui ne déplacent aucune moyenne, suivent
 * toujours.
 *
 * DEUX TEMPS. {@see aligner()} écrit, DANS la transaction qui enregistre la
 * séance : un échec d'écriture lève et annule tout, séance comprise. Cet
 * alignement vivait dans le contrôleur des séances et avalait toute
 * exception ; la séance changeait alors seule, sans un mot.
 * {@see recalculer()} rafraîchit les moyennes APRÈS le commit, hors
 * transaction comme partout ailleurs (voir `RecalculApresDeplacement`) : un
 * recalcul en échec ne défait pas le déplacement, il est compté et dit.
 */
final class AlignementDuDevoir
{
    /**
     * @param  array<string, mixed>  $seanceAvant  `getOriginal()` de la séance, relevé avant son `update()`
     * @return array{evaluation: ESBTPEvaluation, avant: array<string, mixed>}|null ce que {@see recalculer()} doit rafraîchir
     */
    public function aligner(ESBTPSeanceCours $seance, array $seanceAvant): ?array
    {
        $evaluation = $this->devoirDe($seance);

        if (! $evaluation) {
            Log::warning('Aucune évaluation associée trouvée pour le devoir', [
                'seance_id' => $seance->id,
                'classe_id' => $seance->classe_id,
                'matiere_id' => $seance->matiere_id,
            ]);

            return null;
        }

        [$debut, $fin] = $this->horaires($seance, $evaluation);

        $evaluation->fill([
            'titre' => $seance->homework_description ?: 'Devoir - '.($seance->matiere->name ?? 'Matière'),
            'description' => $seance->homework_description,
            'type' => 'devoir',
            'date_evaluation' => $debut,
            'coefficient' => $evaluation->coefficient ?? 1.0,
            'bareme' => $evaluation->bareme ?? 20.0,
            'duree_minutes' => max(1, $fin->diffInMinutes($debut)),
            'enseignant_id' => null,
            'updated_by' => Auth::id(),
        ]);
        if ($seance->matiere_id != ($seanceAvant['matiere_id'] ?? null)) {
            $evaluation->matiere_id = $seance->matiere_id;
        }

        $avant = [
            'classe_id' => (int) $evaluation->getOriginal('classe_id'),
            'matiere_id' => (int) $evaluation->getOriginal('matiere_id'),
            'periode' => (string) $evaluation->getOriginal('periode'),
            'annee_universitaire_id' => $evaluation->getOriginal('annee_universitaire_id'),
        ];

        $evaluation->save();
        RecalculApresDeplacement::recopierSurLesNotes($evaluation, $avant);

        return ['evaluation' => $evaluation, 'avant' => $avant];
    }

    /**
     * À appeler APRÈS le commit de la transaction d'{@see aligner()}.
     *
     * @param  array{evaluation: ESBTPEvaluation, avant: array<string, mixed>}|null  $alignement
     * @return string|null l'avertissement à montrer : une moyenne laissée sans
     *                     rien à moyenner, ou un recalcul en échec
     */
    public function recalculer(?array $alignement): ?string
    {
        if ($alignement === null) {
            return null;
        }

        return MoyennesLaissees::enUnePhrase(
            RecalculApresDeplacement::pour($alignement['evaluation'], $alignement['avant'], Auth::id()),
            'n\'ont plus rien à moyenner depuis le déplacement du devoir'
        );
    }

    /**
     * Une date et une heure réunies en un instant. L'heure peut être un
     * `Carbon` (l'accesseur de `ESBTPSeanceCours` en rend un, daté du jour —
     * piège #14 de `klassci-debugging-discipline.md`) ou une chaîne.
     */
    public static function combiner(mixed $date, mixed $heure): Carbon
    {
        $jour = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);

        if (! $heure) {
            return $jour;
        }

        $h = $heure instanceof Carbon ? $heure : Carbon::parse($heure);

        return $jour->setTime($h->hour, $h->minute, $h->second);
    }

    private function devoirDe(ESBTPSeanceCours $seance): ?ESBTPEvaluation
    {
        $seance->loadMissing(['matiere', 'classe', 'homeworkEvaluation']);
        $evaluation = $seance->homeworkEvaluation;

        if (! $evaluation && $seance->homework_evaluation_id) {
            $evaluation = ESBTPEvaluation::find($seance->homework_evaluation_id);
        }

        if ($evaluation) {
            return $evaluation;
        }

        $date = $seance->date_seance ? Carbon::parse($seance->date_seance) : now();

        $evaluation = ESBTPEvaluation::where('type', 'devoir')
            ->where('classe_id', $seance->classe_id)
            ->where('matiere_id', $seance->matiere_id)
            ->whereDate('date_evaluation', $date->toDateString())
            ->orderByDesc('created_at')
            ->first();

        if ($evaluation) {
            $seance->homework_evaluation_id = $evaluation->id;
            $seance->save();
        }

        return $evaluation;
    }

    /** @return array{0:Carbon, 1:Carbon} */
    private function horaires(ESBTPSeanceCours $seance, ESBTPEvaluation $evaluation): array
    {
        $jour = $seance->date_seance
            ? ($seance->date_seance instanceof Carbon ? $seance->date_seance->copy() : Carbon::parse($seance->date_seance))
            : ($evaluation->date_evaluation ? $evaluation->date_evaluation->copy() : now());

        if ($seance->heure_debut) {
            $debut = self::combiner($jour, $seance->heure_debut);
        } elseif ($evaluation->date_evaluation) {
            $debut = $evaluation->date_evaluation->copy();
        } else {
            $debut = self::combiner($jour, '08:00:00');
        }

        if ($seance->heure_fin) {
            $fin = self::combiner($jour, $seance->heure_fin);
        } elseif ($evaluation->date_evaluation && $evaluation->duree_minutes) {
            $fin = $evaluation->date_evaluation->copy()->addMinutes($evaluation->duree_minutes);
        } else {
            $fin = $debut->copy()->addHour();
        }

        if ($fin->lessThanOrEqualTo($debut)) {
            $fin = $debut->copy()->addHour();
        }

        return [$debut, $fin];
    }
}
