<?php

namespace App\Http\Controllers;

use App\Models\ESBTPAttendance;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPEtudiant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ESBTPCalculAbsencesController extends Controller
{
    /**
     * Récupère et calcule les absences d'un étudiant pour une période donnée
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function calculerAbsencesEtudiant(Request $request)
    {
        // Validation des données d'entrée
        $request->validate([
            'etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'classe_id' => 'required|exists:esbtp_classes,id',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
        ]);

        $etudiantId = $request->etudiant_id;
        $classeId = $request->classe_id;
        $dateDebut = $request->date_debut;
        $dateFin = $request->date_fin;

        try {
            // Récupération des données d'absences
            $resultat = $this->calculerDetailAbsences($etudiantId, $classeId, $dateDebut, $dateFin);

            return response()->json([
                'success' => true,
                'data' => $resultat,
                'message' => 'Calcul des absences effectué avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors du calcul des absences: ' . $e->getMessage(), [
                'etudiant_id' => $etudiantId,
                'classe_id' => $classeId,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'exception' => config('app.debug') ? $e->getTraceAsString() : null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors du calcul des absences: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Méthode de calcul détaillé des absences
     *
     * @param int $etudiantId
     * @param int $classeId
     * @param string $dateDebut
     * @param string $dateFin
     * @return array
     */
    public function calculerDetailAbsences($etudiantId, $classeId, $dateDebut, $dateFin)
    {
        // Initialisation des compteurs
        $heuresJustifiees = 0;
        $heuresNonJustifiees = 0;
        $detailAbsences = [
            'justifiees' => [],
            'non_justifiees' => []
        ];

        try {
            Log::info('Début du calcul détaillé des absences', [
                'etudiant_id' => $etudiantId,
                'classe_id' => $classeId,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin
            ]);

            // Récupération de toutes les absences (justifiées et non justifiées)
            $attendances = ESBTPAttendance::where('etudiant_id', $etudiantId)
                ->whereBetween('date', [$dateDebut, $dateFin])
                ->whereIn('statut', ['absent', 'excuse', 'absent_excuse'])
                ->orWhere(function($query) {
                    $query->whereNotNull('justified_at');
                })
                ->get();

            Log::info('Nombre d\'enregistrements d\'absences trouvés: ' . $attendances->count());

            // Si aucun enregistrement trouvé, essayer avec une requête alternative
            if ($attendances->count() == 0) {
                Log::warning('Aucun enregistrement trouvé, tentative avec requête alternative incluant les séances de cours');

                // Jointure avec les séances de cours pour filtrer par classe
                $attendances = ESBTPAttendance::join('esbtp_seance_cours', 'esbtp_attendances.seance_cours_id', '=', 'esbtp_seance_cours.id')
                    ->where('esbtp_attendances.etudiant_id', $etudiantId)
                    ->where('esbtp_seance_cours.classe_id', $classeId)
                    ->whereBetween('esbtp_attendances.date', [$dateDebut, $dateFin])
                    ->whereIn('esbtp_attendances.statut', ['absent', 'excuse', 'absent_excuse'])
                    ->orWhere(function($query) {
                        $query->whereNotNull('esbtp_attendances.justified_at');
                    })
                    ->select('esbtp_attendances.*')
                    ->get();

                Log::info('Nombre d\'enregistrements via requête alternative: ' . $attendances->count());
            }

            // Traitement des absences
            foreach ($attendances as $attendance) {
                // Déterminer si l'absence est justifiée
                $isJustified = in_array(strtolower($attendance->statut), ['excuse', 'absent_excuse'])
                    || !empty($attendance->justified_at);

                // Calculer la durée de l'absence en heures
                $duree = $this->calculerDureeHeures($attendance->heure_debut, $attendance->heure_fin);

                // Ajouter au total approprié
                if ($isJustified) {
                    $heuresJustifiees += $duree;
                    $detailAbsences['justifiees'][] = [
                        'id' => $attendance->id,
                        'date' => $attendance->date,
                        'heure_debut' => $attendance->heure_debut,
                        'heure_fin' => $attendance->heure_fin,
                        'duree' => $duree,
                        'commentaire' => $attendance->commentaire,
                        'seance_id' => $attendance->seance_cours_id
                    ];
                } else {
                    $heuresNonJustifiees += $duree;
                    $detailAbsences['non_justifiees'][] = [
                        'id' => $attendance->id,
                        'date' => $attendance->date,
                        'heure_debut' => $attendance->heure_debut,
                        'heure_fin' => $attendance->heure_fin,
                        'duree' => $duree,
                        'commentaire' => $attendance->commentaire,
                        'seance_id' => $attendance->seance_cours_id
                    ];
                }
            }

            // Arrondir les totaux à une décimale
            $heuresJustifiees = round($heuresJustifiees, 1);
            $heuresNonJustifiees = round($heuresNonJustifiees, 1);
            $totalHeures = round($heuresJustifiees + $heuresNonJustifiees, 1);

            Log::info('Fin du calcul détaillé des absences', [
                'heures_justifiees' => $heuresJustifiees,
                'heures_non_justifiees' => $heuresNonJustifiees,
                'total_heures' => $totalHeures
            ]);

            return [
                'justifiees' => $heuresJustifiees,
                'non_justifiees' => $heuresNonJustifiees,
                'total' => $totalHeures,
                'detail' => $detailAbsences
            ];
        } catch (\Exception $e) {
            Log::error('Erreur lors du calcul détaillé des absences: ' . $e->getMessage(), [
                'exception' => config('app.debug') ? $e->getTraceAsString() : null
            ]);

            throw $e;
        }
    }

    /**
     * Calculer la durée en heures entre deux horaires
     *
     * @param string $heureDebut Format HH:MM:SS
     * @param string $heureFin Format HH:MM:SS
     * @return float Durée en heures (avec décimales)
     */
    private function calculerDureeHeures($heureDebut, $heureFin)
    {
        // Valeur par défaut en cas d'horaires invalides
        if (empty($heureDebut) || empty($heureFin)) {
            return 1.0; // Valeur par défaut d'une heure
        }

        try {
            // Convertir les chaînes en objets Carbon
            $debut = Carbon::createFromFormat('H:i:s', $heureDebut);
            $fin = Carbon::createFromFormat('H:i:s', $heureFin);

            // Calculer la différence en heures
            $duree = $fin->diffInMinutes($debut) / 60;

            // S'assurer que la durée est positive et raisonnable
            return max(0, min($duree, 8)); // Limiter à 8 heures maximum par séance
        } catch (\Exception $e) {
            Log::warning('Erreur lors du calcul de la durée entre ' . $heureDebut . ' et ' . $heureFin . ': ' . $e->getMessage());
            return 1.0; // Valeur par défaut en cas d'erreur
        }
    }

    /**
     * API endpoint pour obtenir un résumé des absences par séance
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resumeAbsencesParSeance(Request $request)
    {
        // Validation des données d'entrée
        $request->validate([
            'etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
        ]);

        $etudiantId = $request->etudiant_id;
        $dateDebut = $request->date_debut;
        $dateFin = $request->date_fin;

        try {
            // Récupérer toutes les séances avec absences
            $absencesParSeance = DB::table('esbtp_attendances as a')
                ->join('esbtp_seance_cours as sc', 'a.seance_cours_id', '=', 'sc.id')
                ->join('esbtp_matieres as m', 'sc.matiere_id', '=', 'm.id')
                ->where('a.etudiant_id', $etudiantId)
                ->whereBetween('a.date', [$dateDebut, $dateFin])
                ->whereIn('a.statut', ['absent', 'excuse', 'absent_excuse'])
                ->select(
                    'a.id',
                    'a.date',
                    'a.heure_debut',
                    'a.heure_fin',
                    'a.statut',
                    'a.justified_at',
                    'a.commentaire',
                    'sc.matiere_id',
                    'm.nom as matiere_nom'
                )
                ->get();

            // Formater le résultat
            $resultat = [];
            foreach ($absencesParSeance as $absence) {
                $isJustified = in_array(strtolower($absence->statut), ['excuse', 'absent_excuse'])
                    || !empty($absence->justified_at);

                $duree = $this->calculerDureeHeures($absence->heure_debut, $absence->heure_fin);

                $resultat[] = [
                    'id' => $absence->id,
                    'date' => $absence->date,
                    'matiere' => $absence->matiere_nom,
                    'duree' => $duree,
                    'justifiee' => $isJustified,
                    'commentaire' => $absence->commentaire
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $resultat,
                'count' => count($resultat)
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du résumé des absences: ' . $e->getMessage(), [
                'exception' => config('app.debug') ? $e->getTraceAsString() : null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue: ' . $e->getMessage()
            ], 500);
        }
    }
}
