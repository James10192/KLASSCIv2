<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPAttendance;
use App\Models\ESBTPAttendanceManualHours;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPMatiere;
use App\Services\BulletinService;
use App\Services\ESBTP\ESBTPAbsenceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Diagnostic CLI de la chaîne d'absences → note d'assiduité → bulletin.
 *
 * Montre, pour un étudiant / année / période :
 *   1. les SOURCES BRUTES (séances réelles par matière, manuel par matière, global) ;
 *   2. la PRIORITÉ APPLIQUÉE (global écrase par-matière écrase séances) et le total retenu ;
 *   3. la NOTE D'ASSIDUITÉ calculée (si activée) avec le barème configuré du tenant ;
 *   4. la valeur persistée sur le bulletin (pour comparaison).
 */
class CLIAttendanceController extends BaseApiController
{
    public function __construct(
        private ESBTPAbsenceService $absenceService,
        private BulletinService $bulletinService,
    ) {
        parent::__construct();
    }

    /**
     * GET /api/cli/attendance/etudiant/{id}/absence-diagnose
     *   ?annee_universitaire_id=&classe_id=&periode=semestre1|semestre2|annuel
     */
    public function absenceDiagnose(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $etudiant = ESBTPEtudiant::find($id);
        if (! $etudiant) {
            return $this->errorResponse('Étudiant introuvable', [], 404);
        }

        $annee = $request->filled('annee_universitaire_id')
            ? ESBTPAnneeUniversitaire::find((int) $request->input('annee_universitaire_id'))
            : ESBTPAnneeUniversitaire::where('is_current', true)->first();
        if (! $annee) {
            return $this->errorResponse('Année universitaire introuvable', [], 404);
        }

        $classeId = $request->filled('classe_id') ? (int) $request->input('classe_id') : null;
        $periode = (string) $request->input('periode', 'semestre1');
        $dateDebut = $annee->date_debut;
        $dateFin = $annee->date_fin;

        // ── 1. SOURCES BRUTES ────────────────────────────────────────────────
        $seances = $this->rawSessionsByMatiere($id, $dateDebut, $dateFin);
        [$manualPerMatiere, $globals] = $this->rawManual($id, (int) $annee->id, $periode);

        // ── 2. PRIORITÉ APPLIQUÉE (moteur réel) ─────────────────────────────
        $resolved = $this->absenceService->calculerDetailAbsences(
            $id, $classeId, $dateDebut, $dateFin, (int) $annee->id, $periode
        );

        $sourceRetenue = ! empty($resolved['has_global'])
            ? 'global_par_semestre (écrase séances + par-matière)'
            : (! empty($resolved['manual_matieres'])
                ? 'manuel_par_matiere (écrase les séances de ces matières) + séances des autres matières'
                : 'séances_réelles');

        // ── 3. NOTE D'ASSIDUITÉ ─────────────────────────────────────────────
        $noteEnabled = $this->bulletinService->isAttendanceNoteEnabled();
        $note = $this->bulletinService->resolveAttendanceNote(
            $resolved['justifiees'] ?? 0,
            $resolved['non_justifiees'] ?? 0
        );
        $bareme = $this->bulletinService->getAttendanceNoteRule()->toArray();

        // ── 4. BULLETIN PERSISTÉ (comparaison) ──────────────────────────────
        $bulletin = ESBTPBulletin::where('etudiant_id', $id)
            ->where('annee_universitaire_id', $annee->id)
            ->when($classeId, fn ($q) => $q->where('classe_id', $classeId))
            ->where('periode', $periode)
            ->first();

        return $this->successResponse([
            'etudiant' => [
                'id' => $etudiant->id,
                'nom' => trim(($etudiant->nom ?? '').' '.($etudiant->prenoms ?? '')),
                'matricule' => $etudiant->matricule,
            ],
            'contexte' => [
                'annee_universitaire_id' => $annee->id,
                'classe_id' => $classeId,
                'periode' => $periode,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
            ],
            'sources_brutes' => [
                'seances_reelles' => $seances,
                'manuel_par_matiere' => $manualPerMatiere,
                'global_par_semestre' => $globals,
            ],
            'priorite_appliquee' => [
                'regle' => 'global_par_semestre > manuel_par_matiere > seances_reelles (écrasement, jamais addition)',
                'source_retenue' => $sourceRetenue,
                'matieres_ecrasees_par_manuel' => $resolved['manual_matieres'] ?? [],
                'resultat' => [
                    'justifiees_h' => round((float) ($resolved['justifiees'] ?? 0), 2),
                    'non_justifiees_h' => round((float) ($resolved['non_justifiees'] ?? 0), 2),
                    'total_h' => round((float) ($resolved['total'] ?? 0), 2),
                ],
            ],
            'note_assiduite' => [
                'activee' => $noteEnabled,
                'setting' => 'bulletin_show_attendance_note',
                'valeur' => round($note, 3),
                'appliquee_sur_bulletin' => $noteEnabled
                    ? 'moyenne_avec_assiduite = moyenne_brute + '.round($note, 3)
                    : 'désactivée → aucun impact sur la moyenne',
                'bareme_configure' => $bareme,
            ],
            'bulletin_persiste' => $bulletin ? [
                'existe' => true,
                'bulletin_id' => $bulletin->id,
                'note_assiduite' => $bulletin->note_assiduite,
                'absences_justifiees' => $bulletin->absences_justifiees,
                'absences_non_justifiees' => $bulletin->absences_non_justifiees,
                'note' => 'ces colonnes sont figées à la génération ; regénérer le bulletin pour les resynchroniser au calcul live ci-dessus',
            ] : ['existe' => false],
        ], 'Diagnostic absences → note d\'assiduité');
    }

    /**
     * Séances réelles (esbtp_attendances) regroupées par matière sur la fenêtre de dates.
     */
    private function rawSessionsByMatiere(int $etudiantId, $dateDebut, $dateFin): array
    {
        $rows = ESBTPAttendance::where('etudiant_id', $etudiantId)
            ->whereBetween('date', [$dateDebut, $dateFin])
            ->get();

        $byMat = [];
        $totalJust = 0.0;
        $totalNon = 0.0;
        foreach ($rows as $r) {
            if (! $r->heure_debut || ! $r->heure_fin) {
                continue;
            }
            $duree = round(Carbon::parse($r->heure_debut)->diffInMinutes(Carbon::parse($r->heure_fin)) / 60, 2);
            $mid = $r->matiere_id ?? 0;
            $byMat[$mid] ??= ['matiere_id' => $r->matiere_id, 'matiere' => null, 'justifiees_h' => 0.0, 'non_justifiees_h' => 0.0];
            if (in_array($r->statut, ['excuse', 'justifie'], true)) {
                $byMat[$mid]['justifiees_h'] += $duree;
                $totalJust += $duree;
            } elseif ($r->statut === 'absent') {
                $byMat[$mid]['non_justifiees_h'] += $duree;
                $totalNon += $duree;
            }
        }

        $names = ESBTPMatiere::whereIn('id', array_filter(array_keys($byMat)))->pluck('name', 'id');
        foreach ($byMat as $mid => &$m) {
            $m['matiere'] = $mid ? ($names[$mid] ?? 'Matière #'.$mid) : '(sans matière)';
        }
        unset($m);

        return [
            'par_matiere' => array_values($byMat),
            'total_justifiees_h' => round($totalJust, 2),
            'total_non_justifiees_h' => round($totalNon, 2),
        ];
    }

    /**
     * Saisies manuelles brutes : par matière + ligne(s) globale(s). Pour l'annuel,
     * on remonte les deux semestres.
     *
     * @return array{0: array, 1: array}
     */
    private function rawManual(int $etudiantId, int $anneeId, string $periode): array
    {
        $periodes = strtolower(trim($periode)) === 'annuel'
            ? ['semestre1', 'semestre2', 'annuel']
            : [$periode];

        $rows = ESBTPAttendanceManualHours::where('etudiant_id', $etudiantId)
            ->where('annee_universitaire_id', $anneeId)
            ->whereIn('periode', $periodes)
            ->with('matiere:id,name')
            ->get();

        $perMatiere = [];
        $globals = [];
        foreach ($rows as $r) {
            $entry = [
                'periode' => $r->periode,
                'justifiees_h' => (float) $r->heures_absence_justifiees,
                'non_justifiees_h' => (float) $r->heures_absence_non_justifiees,
                'notes' => $r->notes,
            ];
            if ($r->matiere_id === null) {
                $globals[] = $entry;
            } else {
                $perMatiere[] = $entry + [
                    'matiere_id' => $r->matiere_id,
                    'matiere' => optional($r->matiere)->name,
                ];
            }
        }

        return [$perMatiere, $globals];
    }
}
