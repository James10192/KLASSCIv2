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
 * Déplacer la séance déplace le devoir — classe, matière, période, année — et
 * donc ses notes : leur copie dénormalisée et les moyennes enregistrées des
 * deux côtés suivent ({@see RecalculApresDeplacement::apresEnregistrement()}).
 *
 * À APPELER DANS LA TRANSACTION QUI ENREGISTRE LA SÉANCE. Un échec
 * d'écriture lève, et doit tout annuler : une séance déplacée dont le devoir
 * serait resté en place ne se réconcilie plus ensuite. Cet alignement vivait
 * dans le contrôleur des séances et avalait toute exception ; la séance
 * changeait alors seule, sans un mot. Un recalcul en échec, lui, ne lève pas
 * (le déplacement reste acquis, comme partout ailleurs) : il est compté et dit.
 */
final class AlignementDuDevoir
{
    /**
     * @return string|null l'avertissement à montrer : une moyenne laissée sans
     *                     rien à moyenner, ou un recalcul en échec
     */
    public function aligner(ESBTPSeanceCours $seance): ?string
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
            'matiere_id' => $seance->matiere_id,
            'classe_id' => $seance->classe_id,
            'type' => 'devoir',
            'date_evaluation' => $debut,
            'coefficient' => $evaluation->coefficient ?? 1.0,
            'bareme' => $evaluation->bareme ?? 20.0,
            'duree_minutes' => max(1, $fin->diffInMinutes($debut)),
            // Logique d'origine : janvier-juin = semestre 2, sinon semestre 1.
            'periode' => $debut->month <= 6 ? 'semestre2' : 'semestre1',
            'annee_universitaire_id' => $seance->annee_universitaire_id,
            'enseignant_id' => null,
            'updated_by' => Auth::id(),
        ]);

        $avant = [
            'classe_id' => (int) $evaluation->getOriginal('classe_id'),
            'matiere_id' => (int) $evaluation->getOriginal('matiere_id'),
            'periode' => (string) $evaluation->getOriginal('periode'),
            'annee_universitaire_id' => $evaluation->getOriginal('annee_universitaire_id'),
        ];

        $evaluation->save();

        return MoyennesLaissees::enUnePhrase(
            RecalculApresDeplacement::apresEnregistrement($evaluation, $avant, Auth::id()),
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
