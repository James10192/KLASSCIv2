<?php

namespace App\Services;

use App\Domain\BtsTroncCommun\BtsClassCohortCounter;
use App\Models\ESBTPEtudiant;
use App\Services\ESBTP\BtsCurrentResultSnapshotService;

/**
 * RankingService — source unique de vérité pour le calcul du rang d'un étudiant
 * dans sa classe (toutes périodes, BTS + LMD).
 *
 * Avant ce service, le rang était calculé à 3 endroits différents avec :
 * - Cohort différent (include_all_statuses vs status='active')
 * - Données différentes (snapshot vs ESBTPResultat brut)
 * - Avec/sans assiduité incohérent (bonus appliqué dans /esbtp/resultats mais pas dans etudiants.show)
 * → ADIE classé 13 sur la liste classe et 1 dans son détail étudiant.
 *
 * Ce service garantit cohérence absolue :
 * - Cohort canonique : status='active' + workflow_step='etudiant_cree' (seuls les vrais inscrits comptent)
 * - Données canoniques : BtsCurrentResultSnapshotService (live, agrégation officielle)
 * - Assiduité respecte SettingsHelper bulletin_show_attendance_note via BulletinService
 */
class RankingService
{
    public function __construct(
        private readonly BtsCurrentResultSnapshotService $snapshotService,
        private readonly BulletinService $bulletinService,
        private readonly BtsClassCohortCounter $cohortCounter,
    ) {}

    /**
     * Calcule le rang d'UN étudiant dans sa classe pour une période donnée.
     *
     * @param int $etudiantId
     * @param int $classeId
     * @param int $anneeUniversitaireId
     * @param string $periode 'semestre1'|'semestre2'|'annuel'
     * @return array{rang: ?int, total: int, moyenne_brute: ?float, moyenne_avec_assiduite: ?float, note_assiduite: ?float, attendance_enabled: bool}
     */
    public function calculerRangPourEtudiant(
        int $etudiantId,
        int $classeId,
        int $anneeUniversitaireId,
        string $periode = 'annuel'
    ): array {
        $rankings = $this->calculerRangsClasse($classeId, $anneeUniversitaireId, $periode);
        $row = $rankings['rows']->firstWhere('etudiant_id', $etudiantId);

        return [
            'rang' => $row['rang'] ?? null,
            'total' => $rankings['total'],
            'moyenne_brute' => $row['moyenne_brute'] ?? null,
            'moyenne_avec_assiduite' => $row['moyenne_avec_assiduite'] ?? null,
            'note_assiduite' => $row['note_assiduite'] ?? null,
            'attendance_enabled' => $rankings['attendance_enabled'],
        ];
    }

    /**
     * Calcule les rangs de TOUS les étudiants d'une classe pour une période.
     * Retourne une collection ordonnée par rang ascendant (1 = meilleur).
     *
     * Tri canonique :
     * - Si flag bulletin_show_attendance_note ON → tri par effective_total (avec assiduité)
     * - Si flag OFF → tri par raw_total (sans assiduité)
     * → garantit que le rang correspond à la moyenne affichée.
     */
    public function calculerRangsClasse(
        int $classeId,
        int $anneeUniversitaireId,
        string $periode = 'annuel'
    ): array {
        $attendanceEnabled = $this->bulletinService->isAttendanceNoteEnabled();

        // Cohorte canonique, resolue par les phases et non par la classe portee
        // par l'inscription courante. Un etudiant de tronc commun oriente en
        // specialite a vu son inscription suivre : chercher par `classe_id`
        // rendait une cohorte VIDE pour la classe de tronc commun, donc aucun
        // rang, et la colonne affichait « N/A » pour toute la classe alors que
        // les moyennes, elles, s'affichaient.
        //
        // Hors tronc commun, le resolveur retombe sur `inscription->classe_id` :
        // le comportement des autres classes, BTS comme LMD, est inchange.
        $etudiants = ESBTPEtudiant::whereIn(
            'id',
            $this->cohortCounter->etudiantIdsPourPeriode($classeId, $anneeUniversitaireId, $periode)
        )->get(['id', 'matricule', 'nom', 'prenoms']);

        $rows = collect();
        foreach ($etudiants as $etudiant) {
            $snapshot = $this->snapshotService->getPeriodeSnapshot(
                $etudiant->id,
                $classeId,
                $anneeUniversitaireId,
                $periode
            );

            $rawTotal = $snapshot['raw_total'] ?? null;
            $effectiveTotal = $snapshot['effective_total'] ?? null;
            $attendanceNote = $snapshot['attendance_note'] ?? null;

            // Quand l'assiduité est désactivée par setting, on ignore le bonus/malus
            // partout : la "moyenne avec assiduité" devient égale à la brute.
            if (! $attendanceEnabled) {
                $effectiveTotal = $rawTotal;
                $attendanceNote = 0.0;
            }

            $rows->push([
                'etudiant_id' => $etudiant->id,
                'matricule' => $etudiant->matricule,
                'nom_complet' => trim($etudiant->nom . ' ' . $etudiant->prenoms),
                'moyenne_brute' => $rawTotal,
                'moyenne_avec_assiduite' => $effectiveTotal,
                'note_assiduite' => $attendanceNote,
                'snapshot_state' => $snapshot['state'] ?? 'no_data',
            ]);
        }

        // Tri canonique : par la moyenne qui est affichée à l'utilisateur.
        // Si flag ON → effective ; sinon brute. Les nulls (no_data) à la fin.
        $sortKey = $attendanceEnabled ? 'moyenne_avec_assiduite' : 'moyenne_brute';
        $sorted = $rows->sortByDesc(function ($row) use ($sortKey) {
            return $row[$sortKey] ?? -1;
        })->values();

        // Attribution du rang. Ex-aequo : même rang, gap dans la séquence (rang 1, 1, 3...).
        $rank = 0;
        $previousValue = null;
        $position = 0;
        $rowsWithRank = $sorted->map(function ($row) use (&$rank, &$previousValue, &$position, $sortKey) {
            $position++;
            $value = $row[$sortKey];
            if ($value === null) {
                // no_data : pas de rang attribué (= null), reste à la fin
                return array_merge($row, ['rang' => null]);
            }
            if ($value !== $previousValue) {
                $rank = $position;
                $previousValue = $value;
            }
            return array_merge($row, ['rang' => $rank]);
        });

        return [
            'rows' => $rowsWithRank,
            'total' => $rowsWithRank->whereNotNull('rang')->count(),
            'attendance_enabled' => $attendanceEnabled,
            'periode' => $periode,
            'sort_key' => $sortKey,
        ];
    }
}
