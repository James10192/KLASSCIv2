<?php

declare(strict_types=1);

namespace App\Domain\Notes;

use App\Domain\Academique\CoherenceSystemeAcademique;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Fait d'une régularisation LMD ce qu'elle était réellement : une note d'examen.
 *
 * Un relevé saisi avant que le paramètre `nature` existe est rangé en
 * « Régularisation SEMESTREn — X » (type contrôle). Quand l'école précise que
 * c'étaient les notes d'examen, le contrôle continu venant plus tard, il faut
 * que ces évaluations s'appellent et se typent comme telles, sans ressaisir
 * une seule note (ESBTP Abidjan, octobre 2026).
 *
 * Seules les évaluations écrites par `RegularisationDeNotesLmd` sont reprises :
 * on les reconnaît à leur titre exact, recalculé depuis l'élément. Une
 * évaluation renommée à la main, ou d'un autre type, n'est pas touchée.
 *
 * Le type ne change aucun calcul (le bulletin LMD moyenne les évaluations par
 * coefficient) : rien n'est recalculé, aucune note ne bouge.
 *
 * La trace : l'audit de l'évaluation (titre et type) dit qui a requalifié et
 * quand. `type_evaluation` des notes est réaligné en masse, sans audit par note :
 * il ne fait que recopier le type de l'évaluation.
 */
final class RequalificationEnExamen
{
    /**
     * Les régularisations de la classe pour l'année, avec ce qu'elles deviendraient.
     *
     * @return list<array{evaluation_id: int, matiere: string, periode: string, titre: string, nouveau_titre: string, notes: int, conflit: ?string}>
     */
    public function inventaire(ESBTPClasse $classe, int $anneeId): array
    {
        return ESBTPEvaluation::query()
            ->where('classe_id', $classe->id)
            ->where('annee_universitaire_id', $anneeId)
            ->where('type', 'controle')
            ->where('titre', 'like', 'Régularisation %')
            ->where('status', '!=', ESBTPEvaluation::STATUS_CANCELLED)
            ->with('matiere')
            ->withCount('notes')
            ->orderBy('periode')->orderBy('titre')
            ->get()
            ->filter(fn (ESBTPEvaluation $e) => $e->matiere
                && $e->titre === RegularisationDeNotesLmd::titre(RegularisationDeNotesLmd::NATURE_REGULARISATION, (string) $e->periode, $e->matiere))
            ->map(function (ESBTPEvaluation $e): array {
                $nouveau = RegularisationDeNotesLmd::titre(RegularisationDeNotesLmd::NATURE_EXAMEN, (string) $e->periode, $e->matiere);
                // Un examen déjà saisi pour le même élément : fusionner les deux
                // compterait chaque note deux fois. C'est à l'école de trancher.
                $existant = ESBTPEvaluation::query()
                    ->where('classe_id', $e->classe_id)->where('matiere_id', $e->matiere_id)
                    ->where('annee_universitaire_id', $e->annee_universitaire_id)->where('periode', $e->periode)
                    ->where('titre', $nouveau)->where('status', '!=', ESBTPEvaluation::STATUS_CANCELLED)
                    ->value('id');

                return [
                    'evaluation_id' => (int) $e->id,
                    'matiere' => (string) $e->matiere->name,
                    'periode' => (string) $e->periode,
                    'titre' => (string) $e->titre,
                    'nouveau_titre' => $nouveau,
                    'notes' => (int) $e->notes_count,
                    'conflit' => $existant ? "Un examen « {$nouveau} » existe déjà (évaluation #{$existant})." : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Requalifie les régularisations de la classe (toutes, ou celles d'un semestre).
     *
     * @return list<array{evaluation_id: int, matiere: string, periode: string, titre: string, nouveau_titre: string, notes: int, conflit: ?string}>
     *
     * @throws ValidationException classe non LMD, rien à requalifier, ou un examen déjà présent
     */
    public function appliquer(ESBTPClasse $classe, int $anneeId, ?string $periode, bool $simulation, int $userId): array
    {
        if (! CoherenceSystemeAcademique::classeEstLmd($classe->systeme_academique)) {
            $this->refuser('classe_id', 'La requalification ne concerne que les classes LMD.');
        }

        $lignes = array_values(array_filter(
            $this->inventaire($classe, $anneeId),
            fn (array $l) => $periode === null || $l['periode'] === $periode,
        ));

        if ($lignes === []) {
            $this->refuser('periode', "Aucune régularisation à requalifier pour {$classe->name}.");
        }

        $conflits = array_filter(array_column($lignes, 'conflit'));
        if ($conflits !== []) {
            $this->refuser('evaluations', 'Rien n\'a été modifié. ' . implode(' ', $conflits)
                . ' Annulez l\'une des deux évaluations avant de requalifier.');
        }

        if ($simulation) {
            return $lignes;
        }

        DB::transaction(function () use ($lignes, $userId): void {
            foreach ($lignes as $l) {
                $evaluation = ESBTPEvaluation::lockForUpdate()->findOrFail($l['evaluation_id']);
                $evaluation->update([
                    'titre' => $l['nouveau_titre'],
                    'type' => ESBTPEvaluation::TYPE_EXAMEN,
                    'updated_by' => $userId,
                ]);
                ESBTPNote::where('evaluation_id', $evaluation->id)
                    ->update(['type_evaluation' => ESBTPEvaluation::TYPE_EXAMEN, 'updated_by' => $userId]);
            }
        });

        return $lignes;
    }

    /**
     * Qui peut requalifier : l'écran, sa route et Nanan lisent cette seule règle.
     * La requalification porte sur toutes les régularisations d'une classe ; un
     * enseignant, qui ne saisit que les évaluations qui lui sont confiées, n'y a
     * pas accès (même distinction que la saisie des notes LMD).
     *
     * @return string|null le refus, ou null si la personne peut requalifier
     */
    public static function refusPour($user): ?string
    {
        if (! $user || ! $user->can('lmd.notes.manage') || ! $user->can('evaluations.edit')) {
            return "Vous n'avez pas le droit de modifier les évaluations LMD.";
        }
        if ($user->can('identity.teach') && ! $user->can('identity.coordinate')) {
            return 'La requalification des évaluations est réservée à l\'administration.';
        }

        return null;
    }

    private function refuser(string $champ, string $message): never
    {
        throw ValidationException::withMessages([$champ => [$message]]);
    }
}
