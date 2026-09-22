<?php

namespace App\Domain\Notes\Actions;

use App\Domain\Notes\RecalculApresDeplacement;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Enregistre une évaluation dont la classe, la matière ou la période a pu
 * changer, reporte ce changement sur ses notes, puis remet d'accord les
 * moyennes enregistrées.
 *
 * Sortie de `ESBTPEvaluationController::update()`, qui dépassait 150 lignes
 * dans un contrôleur de plus de 2000.
 *
 * Les notes portent une copie dénormalisée de ces coordonnées, que l'on
 * aligne par un `update()` de query builder : aucun observateur ne se réveille.
 * Sans {@see RecalculApresDeplacement}, la moyenne enregistrée rejointe restait
 * l'ancienne — le bulletin BTS la lit en priorité — et celle qu'on quitte
 * continuait de compter les notes parties.
 *
 * Une transaction couvre l'ensemble : un arrêt entre l'enregistrement de
 * l'évaluation et celui de ses notes laissait les notes sur l'ancienne
 * coordonnée, sans que rien ne le signale.
 */
final class DeplacerUneEvaluation
{
    public function __construct(private RecalculApresDeplacement $moyennes) {}

    /**
     * @return array{recalculs_lances:int, lignes_sans_note:list<array>, lignes_retirees:list<array>}
     */
    public function enregistrer(ESBTPEvaluation $evaluation): array
    {
        $avant = [
            'classe_id' => $evaluation->getOriginal('classe_id'),
            'matiere_id' => $evaluation->getOriginal('matiere_id'),
            'periode' => $evaluation->getOriginal('periode'),
        ];

        return DB::transaction(function () use ($evaluation, $avant) {
            $releve = $evaluation->isDirty(array_keys($avant)) ? $this->moyennes->releverAvant([$evaluation->id]) : [];
            $evaluation->save();

            $this->alignerLesNotes($evaluation, $avant);

            return $this->moyennes->apresEnRetirantLesLignesVidees($releve, "modification evaluation {$evaluation->id}", Auth::id());
        });
    }

    /**
     * Les colonnes dénormalisées des notes suivent l'évaluation : sinon les vues
     * qui groupent par `esbtp_notes.matiere_id` (résultats, bulletins)
     * continuent d'afficher l'ancienne matière jusqu'au prochain enregistrement.
     */
    private function alignerLesNotes(ESBTPEvaluation $evaluation, array $avant): void
    {
        $changements = array_filter([
            'classe_id' => $evaluation->classe_id != $avant['classe_id'] ? $evaluation->classe_id : null,
            'matiere_id' => $evaluation->matiere_id != $avant['matiere_id'] ? $evaluation->matiere_id : null,
            // semestre = entier (1 ou 2) extrait de 'semestre1'/'semestre2'
            'semestre' => $evaluation->periode != $avant['periode']
                ? (int) str_replace('semestre', '', (string) $evaluation->periode)
                : null,
        ], fn ($v) => $v !== null);

        if ($changements === []) {
            return;
        }

        $affectees = ESBTPNote::where('evaluation_id', $evaluation->id)->update($changements);
        Log::info('Notes propagées après modif évaluation', [
            'evaluation_id' => $evaluation->id,
            'changes' => $changements,
            'old' => $avant,
            'notes_affected' => $affectees,
        ]);
    }
}
