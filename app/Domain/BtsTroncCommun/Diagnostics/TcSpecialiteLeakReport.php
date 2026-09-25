<?php

declare(strict_types=1);

namespace App\Domain\BtsTroncCommun\Diagnostics;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiere;
use Illuminate\Support\Collection;

/**
 * Mise en forme du diagnostic : une matiere suspecte avec ses etudiants et
 * leurs lignes, puis le resume par cause et par classe.
 *
 * Aucune requete ici : tout arrive deja charge par le diagnostic.
 */
final class TcSpecialiteLeakReport
{
    /**
     * @param  array<string, mixed>  $couple
     * @param  array{notes: Collection<int, object>, moyennes: Collection<int, object>, etudiants: array<int, array<string, mixed>>, dans_maquette: bool, semestre: int}  $pieces
     * @return array<string, mixed>
     */
    public function matiere(ESBTPMatiere $matiere, string $type, ESBTPClasse $classe, array $couple, array $pieces): array
    {
        $contexte = [
            'matiere' => $matiere->name,
            'filiere' => $classe->filiere?->name,
            'niveau' => $classe->niveau?->name,
        ];

        // Tout etudiant de la cohorte qui porte une note ou une moyenne sur la
        // matiere, meme une note que le calcul actuel ecarte : c'est souvent
        // elle qui explique une moyenne enregistree restee au bulletin.
        $concernes = $pieces['notes']->pluck('etudiant_id')
            ->merge($pieces['moyennes']->pluck('etudiant_id'))
            ->map(fn ($id) => (int) $id)->unique()->sort()->values();

        $etudiants = $concernes->map(fn (int $id) => $this->etudiant(
            $id,
            $pieces['etudiants'][$id] ?? [],
            $pieces['notes']->where('etudiant_id', $id),
            $pieces['moyennes']->where('etudiant_id', $id),
            ['type' => $type, 'classe_tc' => (int) $classe->id, 'semestre' => $pieces['semestre'], 'contexte' => $contexte],
        ))->all();

        $cause = $this->causePrincipale($etudiants, $type);

        return [
            'matiere_id' => (int) $matiere->id,
            'matiere' => $matiere->name,
            'code' => $matiere->code,
            'type_suspicion' => $type,
            'classification_combo_tc' => $couple['classification'][(int) $matiere->id] ?? null,
            'sur_combo_tc' => array_key_exists((int) $matiere->id, $couple['classification']),
            'dans_maquette_bulletin' => $pieces['dans_maquette'],
            'combos_specialite' => $couple['combos_specialite'][(int) $matiere->id] ?? [],
            'cause_principale' => $cause,
            'action_suggeree' => TcSpecialiteLeakCauses::action($cause, $contexte),
            'etudiants' => $etudiants,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $classes
     * @return array<string, mixed>
     */
    public function assembler(ESBTPAnneeUniversitaire $annee, ?int $classeId, ?int $etudiantId, array $classes): array
    {
        $parCause = array_fill_keys(TcSpecialiteLeakCauses::toutes(), 0);
        $matieresParCause = $parCause;
        $parClasse = [];
        $touches = [];
        $totalMatieres = 0;
        $totalLignes = 0;

        foreach ($classes as $classe) {
            $etudiantsClasse = [];
            $matieresClasse = 0;
            $lignesClasse = 0;

            foreach ($classe['semestres'] as $semestre) {
                foreach ($semestre['matieres'] as $matiere) {
                    $matieresClasse++;
                    $matieresParCause[$matiere['cause_principale']]++;

                    foreach ($matiere['etudiants'] as $etudiant) {
                        $etudiantsClasse[$etudiant['etudiant_id']] = true;
                        foreach (array_merge($etudiant['notes'], $etudiant['moyennes_enregistrees']) as $ligne) {
                            $parCause[$ligne['cause']]++;
                            $lignesClasse++;
                        }
                    }
                }
            }

            if ($matieresClasse > 0) {
                $parClasse[] = [
                    'classe_id' => $classe['classe_id'],
                    'classe' => $classe['classe'],
                    'matieres_suspectes' => $matieresClasse,
                    'etudiants_touches' => count($etudiantsClasse),
                    'lignes' => $lignesClasse,
                ];
            }

            $touches += $etudiantsClasse;
            $totalMatieres += $matieresClasse;
            $totalLignes += $lignesClasse;
        }

        return [
            'annee_universitaire' => ['id' => (int) $annee->id, 'libelle' => $annee->display_name],
            'filtres' => ['classe_id' => $classeId, 'etudiant_id' => $etudiantId],
            'resume' => [
                'classes_analysees' => count($classes),
                'classes_touchees' => count($parClasse),
                'matieres_suspectes' => $totalMatieres,
                'lignes' => $totalLignes,
                'etudiants_touches' => count($touches),
                'par_cause' => $parCause,
                'matieres_par_cause' => $matieresParCause,
                'par_classe' => $parClasse,
                // Classes ou la regle « non classee hors planification » n'a pas
                // pu s'appliquer : a planifier avant de conclure qu'elles sont saines.
                'classes_sans_planification' => array_values(array_map(
                    fn (array $c) => ['classe_id' => $c['classe_id'], 'classe' => $c['classe']],
                    array_filter($classes, fn (array $c) => $c['planification_absente'] ?? false)
                )),
            ],
            'classes' => array_values(array_filter($classes, fn (array $c) => $c['semestres'] !== [])),
        ];
    }

    /**
     * @param  array<string, mixed>  $ctx
     * @param  Collection<int, object>  $notes
     * @param  Collection<int, object>  $moyennes
     * @param  array{type: string, classe_tc: int, semestre: int, contexte: array<string, mixed>}  $portee
     * @return array<string, mixed>
     */
    private function etudiant(int $id, array $ctx, Collection $notes, Collection $moyennes, array $portee): array
    {
        ['type' => $type, 'classe_tc' => $classeTc, 'semestre' => $semestre, 'contexte' => $contexte] = $portee;
        $specialites = $ctx['classes_specialite'] ?? [];

        $lignesNotes = $notes->map(function ($note) use ($specialites, $type, $classeTc, $semestre, $contexte) {
            $classeEval = (int) $note->evaluation_classe_id;
            $cause = TcSpecialiteLeakCauses::pourNote($classeEval, $classeTc, $specialites, $type);

            return [
                'note_id' => (int) $note->note_id,
                'note' => $note->note === null ? null : (float) $note->note,
                'is_absent' => (bool) $note->is_absent,
                'evaluation_id' => (int) $note->evaluation_id,
                'titre' => $note->titre,
                'date_evaluation' => $note->date_evaluation,
                'periode' => $note->periode,
                'semestre' => $semestre,
                'evaluation_classe_id' => $classeEval,
                'evaluation_classe' => $note->evaluation_classe,
                'evaluation_autre_classe' => $classeEval !== $classeTc,
                'retenue_au_bulletin' => (bool) $note->retenue,
                'cause' => $cause,
                'action_suggeree' => TcSpecialiteLeakCauses::action($cause, $contexte + [
                    'evaluation_id' => (int) $note->evaluation_id,
                    'evaluation_classe' => $note->evaluation_classe,
                ]),
            ];
        })->values()->all();

        $causesNotes = array_column($lignesNotes, 'cause');

        $lignesMoyennes = $moyennes->map(function ($moyenne) use ($causesNotes, $type, $contexte) {
            $cause = TcSpecialiteLeakCauses::pourMoyenne($causesNotes, $type);

            return [
                'resultat_id' => (int) $moyenne->resultat_id,
                'moyenne' => $moyenne->moyenne === null ? null : (float) $moyenne->moyenne,
                'periode' => $moyenne->periode,
                'cause' => $cause,
                'action_suggeree' => TcSpecialiteLeakCauses::action($cause, $contexte + ['moyenne_sans_note' => $causesNotes === []]),
            ];
        })->values()->all();

        $etudiant = $ctx['etudiant'] ?? null;

        return [
            'etudiant_id' => $id,
            'matricule' => $etudiant?->matricule,
            'nom' => $etudiant?->nom,
            'prenoms' => $etudiant?->prenoms,
            'phase_specialite' => $ctx['phase_specialite'] ?? null,
            'notes' => $lignesNotes,
            'moyennes_enregistrees' => $lignesMoyennes,
        ];
    }

    /** @param list<array<string, mixed>> $etudiants */
    private function causePrincipale(array $etudiants, string $type): string
    {
        $compte = array_fill_keys(TcSpecialiteLeakCauses::toutes(), 0);

        foreach ($etudiants as $etudiant) {
            foreach (array_merge($etudiant['notes'], $etudiant['moyennes_enregistrees']) as $ligne) {
                $compte[$ligne['cause']]++;
            }
        }

        if (array_sum($compte) === 0) {
            return $type === TcSpecialiteLeakCauses::TYPE_NON_CLASSEE
                ? TcSpecialiteLeakCauses::MATIERE_A_CLASSER
                : TcSpecialiteLeakCauses::AUTRE;
        }

        return array_search(max($compte), $compte, true);
    }
}
