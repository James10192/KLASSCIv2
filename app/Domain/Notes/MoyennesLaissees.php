<?php

namespace App\Domain\Notes;

use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;

/**
 * Ce que l'ecran d'une evaluation dit des moyennes qu'un deplacement a
 * laissees sans rien a moyenner (voir
 * {@see PerimetreDeRecalcul::recalculerUnCouple()}).
 *
 * Rendu par `evaluations/show.blade.php`, pas par le bandeau global du layout,
 * qui echappe tout et ne peut donc porter aucun lien.
 *
 * Deux choses que le premier texte faisait mal :
 *
 * - il parlait toujours de « l'ancienne matiere ». Or sur cet ecran la classe
 *   et la matiere sont verrouillees des qu'il y a des notes (sauf
 *   `evaluations.edit_locked`) : le cas courant est un changement de PERIODE.
 *   Le texte nomme ce qui a vraiment bouge ;
 * - il renvoyait vers « Modifier les moyennes » sans dire de qui. Il y a deux
 *   remedes, selon ce qui reste :
 *   - plus AUCUNE note : le pre-controle de la generation des bulletins les
 *     liste par matiere, avec une suppression douce et tracee — un lien
 *     pre-rempli par (classe, periode) ;
 *   - des absences seulement : ce pre-controle ne les voit pas. L'ecran
 *     « Modifier les moyennes » est PAR ELEVE : un lien pre-rempli par eleve.
 */
final class MoyennesLaissees
{
    /**
     * @param  array<int, array<string,mixed>>  $laissees
     * @param  array{classe_id:int, matiere_id:int, periode:string}  $avant
     * @return array{total:int, ce_qui_a_bouge:string, nettoyages:array<int,array<string,mixed>>, eleves:array<int,array<string,mixed>>}
     */
    public static function pourLEcran(array $laissees, ESBTPEvaluation $evaluation, array $avant): array
    {
        $classes = ESBTPClasse::whereIn('id', array_column($laissees, 'classe_id'))->pluck('name', 'id');

        $sansNote = array_filter($laissees, fn (array $l) => $l['reste'] === 'aucune_note');
        $absences = array_filter($laissees, fn (array $l) => $l['reste'] !== 'aucune_note');

        return [
            'total' => count($laissees),
            'ce_qui_a_bouge' => self::ceQuiABouge($evaluation, $avant),
            'nettoyages' => collect($sansNote)
                ->map(fn (array $l) => self::coordonnee($l))
                ->unique(fn (array $c) => implode('|', $c))
                ->map(fn (array $c) => $c + [
                    'libelle' => ($classes[$c['classe_id']] ?? '#'.$c['classe_id']).', '.self::libellePeriode($c['periode']),
                ])
                ->values()
                ->all(),
            'eleves' => self::elevesAReprendre($absences, $classes->all()),
        ];
    }

    /**
     * @param  array<int, array<string,mixed>>  $absences
     * @param  array<int,string>  $classes
     * @return array<int, array<string,mixed>>
     */
    private static function elevesAReprendre(array $absences, array $classes): array
    {
        if ($absences === []) {
            return [];
        }

        $etudiants = ESBTPEtudiant::whereIn('id', array_column($absences, 'etudiant_id'))
            ->get(['id', 'nom', 'prenoms'])
            ->keyBy('id');
        $matieres = ESBTPMatiere::whereIn('id', array_column($absences, 'matiere_id'))->pluck('name', 'id');

        return array_values(array_map(fn (array $l) => self::coordonnee($l) + [
            'etudiant_id' => (int) $l['etudiant_id'],
            'libelle' => trim(($etudiants[$l['etudiant_id']]->nom ?? '').' '.($etudiants[$l['etudiant_id']]->prenoms ?? ''))
                .' — '.($matieres[$l['matiere_id']] ?? '#'.$l['matiere_id'])
                .' ('.($classes[$l['classe_id']] ?? '#'.$l['classe_id']).', '.self::libellePeriode($l['periode']).')',
        ], $absences));
    }

    /**
     * @param  array<string,mixed>  $laissee
     * @return array{classe_id:int, periode:string, annee_universitaire_id:int}
     */
    private static function coordonnee(array $laissee): array
    {
        return [
            'classe_id' => (int) $laissee['classe_id'],
            'periode' => (string) $laissee['periode'],
            'annee_universitaire_id' => (int) $laissee['annee_universitaire_id'],
        ];
    }

    /** @param array{classe_id:int, matiere_id:int, periode:string} $avant */
    private static function ceQuiABouge(ESBTPEvaluation $evaluation, array $avant): string
    {
        $bouge = array_values(array_filter([
            $evaluation->classe_id != $avant['classe_id'] ? 'la classe' : null,
            $evaluation->matiere_id != $avant['matiere_id'] ? 'la matière' : null,
            $evaluation->periode != $avant['periode'] ? 'la période' : null,
        ]));

        return match (count($bouge)) {
            0 => 'l\'évaluation',
            1 => $bouge[0],
            default => implode(', ', array_slice($bouge, 0, -1)).' et '.end($bouge),
        };
    }

    private static function libellePeriode(string $periode): string
    {
        return 'Semestre '.(ESBTPEvaluation::numeroDeSemestre($periode) ?? '?');
    }
}
