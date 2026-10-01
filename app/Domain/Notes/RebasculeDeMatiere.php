<?php

namespace App\Domain\Notes;

use App\Domain\Academique\CoherenceSystemeAcademique;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use Illuminate\Support\Facades\DB;

/**
 * Rebasculer une evaluation vers la matiere du bon systeme academique : la
 * reparation d'une fuite de selecteur (une ECUE LMD evaluee dans une classe
 * BTS, ou l'inverse). Le mouvement n'est autorise que s'il RETABLIT la
 * coherence, jamais s'il la rompt.
 *
 * Partage par la CLI (`POST /api/cli/evaluations/{id}/matiere`) et par Nanan
 * (`proposer_changement_matiere_evaluation`).
 */
final class RebasculeDeMatiere
{
    /**
     * Pourquoi le mouvement est refuse, ou null s'il retablit la coherence.
     */
    public function refus(ESBTPEvaluation $evaluation, ESBTPMatiere $cible): ?string
    {
        // Une evaluation deja sur une matiere de son systeme n'a rien a
        // reparer : la deplacer vers une autre matiere du meme systeme (Maths →
        // Physique, hors maquette eventuellement) n'est pas une reparation de
        // fuite, c'est une modification que seul l'ecran de l'evaluation porte.
        if (CoherenceSystemeAcademique::estCoherente($evaluation->classe?->systeme_academique, $evaluation->matiere?->unite_enseignement_id)) {
            return 'Refus : cette evaluation est deja sur une matiere de son systeme : passez par l\'ecran de l\'evaluation.';
        }

        if (CoherenceSystemeAcademique::estCoherente($evaluation->classe?->systeme_academique, $cible->unite_enseignement_id)) {
            return null;
        }

        $classeEstLmd = CoherenceSystemeAcademique::classeEstLmd($evaluation->classe?->systeme_academique);
        $cibleEstEcue = CoherenceSystemeAcademique::matiereEstEcue($cible->unite_enseignement_id);

        return 'Refus : la matiere cible ne correspond pas au systeme de la classe. '
            .'Classe '.($classeEstLmd ? 'LMD' : 'BTS').', matiere cible '
            .($cibleEstEcue ? 'ECUE LMD' : 'BTS').'.';
    }

    /**
     * Deplace l'evaluation et la copie denormalisee de ses notes, puis
     * recalcule les deux cotes (piege #7). Le recalcul est hors transaction :
     * le deplacement reste acquis meme si un recalcul echoue.
     *
     * @return array{avant: array{matiere_id: ?int, matiere: ?string}, notes: int, recalcul: array<string,mixed>}
     */
    public function appliquer(ESBTPEvaluation $evaluation, ESBTPMatiere $cible, ?int $declencheur): array
    {
        $avant = ['matiere_id' => $evaluation->matiere_id, 'matiere' => $evaluation->matiere?->name];
        $notes = ESBTPNote::where('evaluation_id', $evaluation->id)->count();

        // Coordonnees completes d'AVANT : le recalcul rafraichit les deux cotes.
        $coordonneesAvant = [
            'classe_id' => $evaluation->classe_id,
            'matiere_id' => $evaluation->matiere_id,
            'periode' => $evaluation->periode,
            'annee_universitaire_id' => $evaluation->annee_universitaire_id,
        ];

        DB::transaction(function () use ($evaluation, $cible) {
            $evaluation->matiere_id = $cible->id;
            $evaluation->save();

            // Colonne denormalisee : sans cette mise a jour, les notes
            // resteraient rattachees a l'ancienne matiere.
            ESBTPNote::where('evaluation_id', $evaluation->id)
                ->update(['matiere_id' => $cible->id]);
        });

        $recalcul = RecalculApresDeplacement::pour($evaluation, $coordonneesAvant, $declencheur);

        return ['avant' => $avant, 'notes' => $notes, 'recalcul' => $recalcul];
    }
}
