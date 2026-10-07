<?php

namespace App\Domain\Bulletins;

use App\Domain\BtsTroncCommun\BtsAnnualClassMapResolver;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNote;
use App\Services\BulletinService;
use App\Services\ESBTP\BtsCurrentResultSnapshotService;
use App\Services\ESBTP\ESBTPAbsenceService;
use App\Services\RankingService;
use Illuminate\Support\Collection;

/**
 * Moyennes, rangs et statuts d'une page du tableau de `/esbtp/resultats` (BTS).
 *
 * POURQUOI CET OBJET EXISTE. `ESBTPResultatController::loadEtudiants()`
 * calculait chaque moyenne DEUX fois : d'abord un calcul historique (en annuel,
 * note d'assiduite puis moyenne de chaque semestre, eleve par eleve), puis le
 * snapshot courant, eleve par eleve aussi — et le second ECRASAIT le premier
 * des qu'il rendait une valeur. Mesure sur presentation (octobre 2026) : 1 a
 * 5,6 s pour 26 eleves d'une classe sans aucune note, environ quatre-vingts
 * requetes par eleve en annuel.
 *
 * L'ordre est donc renverse, sans rien changer a ce qui s'affiche :
 *
 *   1. le snapshot de la page, lu pour toute la page d'un coup (et, quand une
 *      classe est choisie, repris tel quel du classement, qui l'a deja calcule
 *      pour toute la classe) ;
 *   2. le calcul historique, SEULEMENT dans les cas ou l'ancien code en gardait
 *      quelque chose : `besoinDuCalculHistorique()` les enumere.
 *
 * La regle de fusion des deux, elle, est recopiee a l'identique dans
 * `calculer()` : c'est elle qui decide de l'affichage, et le test
 * `ChargementDesResultatsTest` la fige contre une reference capturee avant ce
 * chantier.
 */
class MoyennesDeLaListe
{
    public function __construct(
        private BulletinService $bulletinService,
        private ESBTPAbsenceService $absenceService,
        private BtsCurrentResultSnapshotService $snapshotService,
        private RankingService $rankingService,
        private BtsAnnualClassMapResolver $classMapResolver,
    ) {}

    /**
     * @return array{moyennes: array<int, float>, rangs: array<int, int>, statuts_annuels: array<int, array>, coefficients_manquants: array<int, bool>}
     */
    public function calculer(Collection $etudiants, $classeId, $anneeId, $semestre): array
    {
        $inscriptions = $classeId ? collect() : $this->inscriptionsDeLAnnee($etudiants, $anneeId);
        $classeDe = fn ($etudiant) => $classeId ?: ($inscriptions[$etudiant->id]->classe_id ?? null);
        $periode = $semestre ? 'semestre'.$semestre : 'annuel';

        $classement = $classeId
            ? $this->rankingService->calculerRangsClasse((int) $classeId, (int) $anneeId, $periode)
            : null;
        [$moyennesResolues, $statutsResolus, $coefficientsManquants] = $this->resoudre(
            $etudiants, $classeDe, $anneeId, $periode, $classement['snapshots'] ?? []
        );

        [$moyennes, $rangs, $statuts] = $this->besoinDuCalculHistorique($moyennesResolues, $statutsResolus, $classeId)
            ? $this->calculHistorique($etudiants, $classeDe, $classeId, $anneeId, $semestre)
            : [[], [], []];

        // La fusion d'avant le chantier, a l'identique.
        if (! empty($moyennesResolues) || ! empty($statutsResolus)) {
            $moyennes = $moyennesResolues;
            $statuts = $statutsResolus;
        }

        if ($classement) {
            // Rang canonique de la classe (cohorte active, workflow valide,
            // ex aequo geres). Les eleves hors cohorte restent sans rang.
            $rangs = [];
            foreach ($classement['rows']->whereNotNull('rang')->keyBy('etudiant_id') as $etudiantId => $ligne) {
                $rangs[$etudiantId] = $ligne['rang'];
            }
        } elseif (! empty($moyennes)) {
            arsort($moyennes);
            $rangs = $this->rangsSequentiels($moyennes);
        }

        return [
            'moyennes' => $moyennes,
            'rangs' => $rangs,
            'statuts_annuels' => $statuts,
            'coefficients_manquants' => $coefficientsManquants,
        ];
    }

    /**
     * Le calcul historique ne survivait a la fusion que dans deux cas :
     *
     * - le snapshot n'a rien rendu du tout (aucune moyenne, aucun statut) : les
     *   moyennes et les rangs historiques sont affiches ;
     * - sans classe choisie, le snapshot a rendu des statuts mais aucune
     *   moyenne : les RANGS historiques restaient affiches (une moyenne de
     *   bulletin peut exister la ou le snapshot ne voit aucune note).
     *
     * Partout ailleurs il etait calcule puis jete.
     */
    private function besoinDuCalculHistorique(array $moyennesResolues, array $statutsResolus, $classeId): bool
    {
        return empty($moyennesResolues) && (empty($statutsResolus) || ! $classeId);
    }

    /**
     * Le snapshot de chaque eleve de la page, dans l'ordre de la page.
     *
     * @return array{0: array<int, float>, 1: array<int, array>, 2: array<int, bool>}
     */
    private function resoudre(Collection $etudiants, callable $classeDe, $anneeId, string $periode, array $dejaCalcules): array
    {
        $snapshots = $this->snapshotsDeLaPage($etudiants, $classeDe, $anneeId, $periode, $dejaCalcules);

        $moyennes = $statuts = $coefficientsManquants = [];
        foreach ($etudiants as $etudiant) {
            $classeId = $classeDe($etudiant);
            if (! $classeId) {
                continue;
            }
            $snapshot = $snapshots[$classeId][(int) $etudiant->id];

            if ($periode === 'annuel') {
                $statuts[$etudiant->id] = $this->statutAnnuel($snapshot);
            }
            if (($snapshot['effective_total'] ?? null) !== null) {
                $moyennes[$etudiant->id] = round((float) $snapshot['effective_total'], 2);
            }
            if (! empty($snapshot['coefficients_missing'])) {
                $coefficientsManquants[$etudiant->id] = true;
            }
        }

        return [$moyennes, $statuts, $coefficientsManquants];
    }

    /**
     * Par classe : les snapshots que le classement a deja calcules, et ceux
     * des eleves restants lus en une fois (`getPeriodeSnapshotsPourCohorte()`,
     * dont l'egalite avec le calcul eleve par eleve est prouvee par
     * `FicheEtudiantRequetesTest`).
     *
     * @return array<int, array<int, array>> snapshot par classe puis par eleve
     */
    private function snapshotsDeLaPage(Collection $etudiants, callable $classeDe, $anneeId, string $periode, array $dejaCalcules): array
    {
        $parClasse = [];
        foreach ($etudiants as $etudiant) {
            if ($classeId = $classeDe($etudiant)) {
                $parClasse[(int) $classeId][] = (int) $etudiant->id;
            }
        }

        $snapshots = [];
        foreach ($parClasse as $classeId => $ids) {
            $connus = array_intersect_key($dejaCalcules, array_flip($ids));
            $manquants = array_values(array_diff($ids, array_keys($connus)));
            $snapshots[$classeId] = $connus + ($manquants === []
                ? []
                : $this->snapshotService->getPeriodeSnapshotsPourCohorte($manquants, $classeId, (int) $anneeId, $periode));
        }

        return $snapshots;
    }

    private function statutAnnuel(array $snapshot): array
    {
        return match ($snapshot['state'] ?? 'no_data') {
            'annual_complete', 'annual_complete_no_coefficients' => [
                'state' => 'annual_complete',
                'label' => ($snapshot['annual_policy'] ?? 's1_s2') === 'specialisation_s2'
                    ? 'Annuel · S2 specialite'
                    : null,
            ],
            'annual_incomplete' => [
                'state' => 'annual_incomplete',
                'label' => ($snapshot['primary_semester'] ?? 'semestre1') === 'semestre2'
                    ? 'Partiel · S2 seulement'
                    : 'Partiel · S1 seulement',
            ],
            default => ['state' => 'no_data', 'label' => 'Aucune note'],
        };
    }

    /**
     * Le calcul d'avant le snapshot, inchange : il ne tourne plus que dans les
     * cas de `besoinDuCalculHistorique()`.
     *
     * @return array{0: array, 1: array, 2: array}
     */
    private function calculHistorique(Collection $etudiants, callable $classeDe, $classeId, $anneeId, $semestre): array
    {
        return $semestre
            ? $this->calculHistoriqueDuSemestre($etudiants, $classeId, $anneeId, $semestre)
            : $this->calculHistoriqueAnnuel($etudiants, $classeDe, $classeId, $anneeId);
    }

    private function calculHistoriqueAnnuel(Collection $etudiants, callable $classeDe, $classeId, $anneeId): array
    {
        $weights = $this->bulletinService->getSemesterWeights(
            $classeId ? ESBTPClasse::with(['filiere', 'niveau', 'niveauEtude'])->find($classeId) : null
        );
        $moyennes = $statuts = [];

        foreach ($etudiants as $etudiant) {
            $etudiantClasseId = $classeDe($etudiant);
            if (! $etudiantClasseId) {
                continue;
            }

            $assiduite = $this->bulletinService->calculateEffectiveAttendanceNoteForStudent(
                $etudiant->id, $etudiantClasseId, $anneeId ?? 0, 'annuel'
            );

            try {
                $classMap = $this->classMapResolver->resolve(
                    (int) $etudiant->id,
                    (int) $etudiantClasseId,
                    (int) ($anneeId ?? 0)
                );
                $classeIdS1 = (int) ($classMap['semestre1_classe_id'] ?? $etudiantClasseId);
                $classeIdS2 = (int) ($classMap['semestre2_classe_id'] ?? $etudiantClasseId);
                $s1 = $this->bulletinService->getAlignedBulletinAverageForPeriode(
                    $etudiant->id, $classeIdS1, $anneeId ?? 0, 'semestre1', 'annuel', 0, $assiduite
                );
                $s2 = $this->bulletinService->getAlignedBulletinAverageForPeriode(
                    $etudiant->id, $classeIdS2, $anneeId ?? 0, 'semestre2', 'annuel', 0, $assiduite
                );
                $annuelle = $this->bulletinService->calculateConfiguredAnnualAverage(
                    $s1,
                    $s2,
                    $weights,
                    $classeIdS1,
                    $classeIdS2
                );
            } catch (\RuntimeException $e) {
                continue; // coefficient manquant pour cet eleve
            }
            if ($annuelle !== null) {
                $moyennes[$etudiant->id] = round($annuelle, 2);
                $statuts[$etudiant->id] = ['state' => 'annual_complete', 'label' => null];
            } elseif ($s1 !== null) {
                $moyennes[$etudiant->id] = round($s1, 2);
                $statuts[$etudiant->id] = ['state' => 'annual_incomplete', 'label' => 'Provisoire · S1 seulement'];
            } elseif ($s2 !== null) {
                $moyennes[$etudiant->id] = round($s2, 2);
                $statuts[$etudiant->id] = ['state' => 'annual_incomplete', 'label' => 'Provisoire · S2 seulement'];
            }
        }

        $rangs = [];
        if (count($moyennes) > 0) {
            arsort($moyennes);
            $rangs = $this->rangsSequentiels($moyennes);
        }

        return [$moyennes, $rangs, $statuts];
    }

    private function calculHistoriqueDuSemestre(Collection $etudiants, $classeId, $anneeId, $semestre): array
    {
        $moyennes = $rangs = [];
        $notes = $this->notesDeLaPage($etudiants, $classeId, $anneeId, $semestre);
        $this->bulletinService->calculateStudentStatsFixed($etudiants, $notes, $moyennes, $rangs, $classeId, $anneeId, $semestre);

        $annee = $anneeId && SettingsHelper::drapeau('bulletin_show_attendance_note', true)
            ? ESBTPAnneeUniversitaire::find($anneeId)
            : null;
        if ($annee) {
            foreach ($moyennes as $etudiantId => &$moyenne) {
                $absences = $this->absenceService->calculerDetailAbsences(
                    $etudiantId, $classeId ?? 0, $annee->date_debut ?? null, $annee->date_fin ?? null,
                    $anneeId, 'semestre'.$semestre
                );
                $moyenne += $this->bulletinService->resolveAttendanceNote($absences['justifiees'] ?? 0, $absences['non_justifiees'] ?? 0);
            }
            unset($moyenne);
            arsort($moyennes);
            $rangs = $this->rangsSequentiels($moyennes);
        }

        return [$moyennes, $rangs, []];
    }

    private function notesDeLaPage(Collection $etudiants, $classeId, $anneeId, $semestre): Collection
    {
        $requete = ESBTPNote::whereIn('etudiant_id', $etudiants->pluck('id')->toArray())
            ->with(['etudiant', 'etudiant.user', 'evaluation', 'evaluation.classe', 'evaluation.matiere']);

        if ($classeId) {
            $requete->whereHas('evaluation', fn ($q) => $q->where('classe_id', $classeId));
        }
        if ($anneeId) {
            $requete->whereHas('evaluation', fn ($q) => $q->where('annee_universitaire_id', $anneeId));
        }
        $requete->whereHas('evaluation', fn ($q) => $q->where('periode', 'like', 'semestre'.$semestre.'%'));

        return $requete->get();
    }

    /** La derniere inscription de l'annee de chaque eleve de la page. */
    private function inscriptionsDeLAnnee(Collection $etudiants, $anneeId): Collection
    {
        return ESBTPInscription::query()
            ->whereIn('etudiant_id', $etudiants->pluck('id'))
            ->where('annee_universitaire_id', $anneeId)
            ->orderByDesc('date_inscription')
            ->get()
            ->unique('etudiant_id')
            ->keyBy('etudiant_id');
    }

    /** @return array<int, int> */
    private function rangsSequentiels(array $moyennesTriees): array
    {
        $rangs = [];
        $rang = 1;
        foreach (array_keys($moyennesTriees) as $etudiantId) {
            $rangs[$etudiantId] = $rang++;
        }

        return $rangs;
    }
}
