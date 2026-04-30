<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPSeanceCours;

/**
 * Contrôleur pour les APIs d'écriture du LMS
 *
 * Gère les données envoyées par le LMS vers KLASSCI :
 * - Notes d'évaluations
 * - Présences aux cours en ligne
 * - Statuts des cours
 *
 * @package App\Http\Controllers\API
 * @author KLASSCI Team
 * @version 1.0
 */
class LMSWriteController extends BaseApiController
{
    /**
     * Envoie les notes d'une évaluation depuis le LMS
     *
     * Endpoint: POST /api/lms/evaluations/{evaluationId}/notes
     *
     * @param Request $request
     * @param int $evaluationId
     * @return JsonResponse
     */
    public function saveEvaluationNotes(Request $request, int $evaluationId): JsonResponse
    {
        // Vérifier les permissions (enseignants seulement)
        $roleCheck = $this->checkRoleAccess(['enseignant', 'coordinateur']);
        if ($roleCheck) {
            return $roleCheck;
        }

        $evaluation = ESBTPEvaluation::find($evaluationId);
        if (!$evaluation) {
            return $this->errorResponse('Évaluation introuvable', [], 404);
        }

        // Vérifier que l'enseignant a le droit de saisir des notes pour cette évaluation
        if (auth()->user()->can('identity.teach') && !auth()->user()->can('identity.coordinate')) {
            $hasAccess = $evaluation->matiere->enseignants()
                ->where('enseignant_id', auth()->id())
                ->where('esbtp_enseignant_matiere.annee_universitaire_id', $evaluation->annee_universitaire_id)
                ->where('esbtp_enseignant_matiere.is_active', true)
                ->exists();

            if (!$hasAccess) {
                return $this->errorResponse('Accès non autorisé à cette évaluation', [], 403);
            }
        }

        // Validation des données
        $validator = Validator::make($request->all(), [
            'notes' => 'required|array|min:1',
            'notes.*.etudiant_id' => 'required|integer|exists:esbtp_etudiants,id',
            'notes.*.note' => 'nullable|numeric|min:0|max:' . ($evaluation->bareme ?? 20),
            'notes.*.is_absent' => 'boolean',
            'notes.*.commentaire' => 'nullable|string|max:500',
            'date_saisie' => 'nullable|date',
            'commentaire_general' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Données invalides',
                $validator->errors()->toArray(),
                422
            );
        }

        try {
            DB::beginTransaction();

            $notesData = $request->input('notes');
            $dateEvaluation = $request->input('date_saisie', now());
            $commentaireGeneral = $request->input('commentaire_general');

            $notesSauvegardees = [];
            $erreurs = [];

            foreach ($notesData as $index => $noteData) {
                $etudiantId = $noteData['etudiant_id'];
                $valeurNote = $noteData['note'] ?? null;
                $isAbsent = $noteData['is_absent'] ?? false;
                $commentaire = $noteData['commentaire'] ?? null;

                // Vérifier que l'étudiant appartient à la classe de l'évaluation
                $etudiant = ESBTPEtudiant::whereHas('inscriptions', function ($q) use ($evaluation) {
                    $q->where('classe_id', $evaluation->classe_id)
                      ->where('annee_universitaire_id', $evaluation->annee_universitaire_id)
                      ->where('status', 'active');
                })->find($etudiantId);

                if (!$etudiant) {
                    $erreurs[] = "Étudiant ID {$etudiantId} non trouvé dans la classe de l'évaluation";
                    continue;
                }

                // Validation métier
                if (!$isAbsent && $valeurNote === null) {
                    $erreurs[] = "Note manquante pour l'étudiant {$etudiant->matricule} (non absent)";
                    continue;
                }

                if ($isAbsent && $valeurNote !== null) {
                    $valeurNote = null; // Forcer note à null si absent
                }

                // Créer ou mettre à jour la note
                $note = ESBTPNote::updateOrCreate(
                    [
                        'evaluation_id' => $evaluation->id,
                        'etudiant_id' => $etudiantId,
                        'matiere_id' => $evaluation->matiere_id,
                        'classe_id' => $evaluation->classe_id
                    ],
                    [
                        'note' => $valeurNote,
                        'valeur' => $valeurNote,
                        'is_absent' => $isAbsent,
                        'commentaire' => $commentaire,
                        'type_evaluation' => $evaluation->type,
                        'semestre' => $this->getSemestreFromDate($dateEvaluation),
                        'annee_universitaire' => $evaluation->anneeUniversitaire->nom ?? date('Y'),
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id()
                    ]
                );

                $notesSauvegardees[] = [
                    'etudiant_id' => $etudiantId,
                    'matricule' => $etudiant->matricule,
                    'note_id' => $note->id,
                    'valeur' => $valeurNote,
                    'is_absent' => $isAbsent,
                    'action' => $note->wasRecentlyCreated ? 'created' : 'updated'
                ];
            }

            // Mettre à jour le statut de l'évaluation
            $evaluation->update([
                'status' => ESBTPEvaluation::STATUS_COMPLETED,
                'updated_by' => auth()->id()
            ]);

            // Logger l'activité
            \Log::info('Notes saisies depuis LMS', [
                'evaluation_id' => $evaluation->id,
                'enseignant_id' => auth()->id(),
                'nb_notes' => count($notesSauvegardees),
                'nb_erreurs' => count($erreurs),
                'source' => 'LMS'
            ]);

            DB::commit();

            $response = [
                'evaluation' => [
                    'id' => $evaluation->id,
                    'titre' => $evaluation->titre,
                    'status' => $evaluation->status
                ],
                'notes_sauvegardees' => $notesSauvegardees,
                'statistiques' => [
                    'total_notes' => count($notesSauvegardees),
                    'notes_creees' => collect($notesSauvegardees)->where('action', 'created')->count(),
                    'notes_mises_a_jour' => collect($notesSauvegardees)->where('action', 'updated')->count(),
                    'absents' => collect($notesSauvegardees)->where('is_absent', true)->count(),
                    'moyenne_classe' => collect($notesSauvegardees)->where('is_absent', false)->avg('valeur')
                ]
            ];

            if (!empty($erreurs)) {
                $response['erreurs'] = $erreurs;
                $response['message'] = 'Notes partiellement sauvegardées avec quelques erreurs';
            }

            return $this->successResponse(
                $response,
                empty($erreurs) ? 'Notes sauvegardées avec succès' : 'Notes partiellement sauvegardées'
            );

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Erreur sauvegarde notes LMS', [
                'evaluation_id' => $evaluationId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return $this->errorResponse('Erreur lors de la sauvegarde des notes: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Enregistre les présences d'un cours en ligne
     *
     * Endpoint: POST /api/lms/cours/{coursId}/presences
     *
     * @param Request $request
     * @param int $coursId
     * @return JsonResponse
     */
    public function saveCourseAttendance(Request $request, int $coursId): JsonResponse
    {
        // Vérifier les permissions (enseignants seulement)
        $roleCheck = $this->checkRoleAccess(['enseignant', 'coordinateur']);
        if ($roleCheck) {
            return $roleCheck;
        }

        $cours = ESBTPSeanceCours::find($coursId);
        if (!$cours) {
            return $this->errorResponse('Cours introuvable', [], 404);
        }

        // Vérifier que l'enseignant a le droit de gérer ce cours
        if (auth()->user()->can('identity.teach') && !auth()->user()->can('identity.coordinate') && $cours->enseignant_id !== auth()->id()) {
            return $this->errorResponse('Accès non autorisé à ce cours', [], 403);
        }

        // Validation des données
        $validator = Validator::make($request->all(), [
            'date_cours' => 'required|date',
            'heure_debut' => 'required',
            'heure_fin' => 'required',
            'enseignant_present' => 'required|boolean',
            'etudiants_presents' => 'array',
            'etudiants_presents.*' => 'integer|exists:esbtp_etudiants,id',
            'etudiants_absents' => 'array',
            'etudiants_absents.*' => 'integer|exists:esbtp_etudiants,id',
            'duree_effective_minutes' => 'integer|min:1',
            'commentaire' => 'nullable|string|max:1000',
            'type_cours' => 'nullable|string|in:visio,presentiel,hybride'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Données invalides',
                $validator->errors()->toArray(),
                422
            );
        }

        try {
            DB::beginTransaction();

            $dateCours = $request->input('date_cours');
            $heureDebut = $request->input('heure_debut');
            $heureFin = $request->input('heure_fin');
            $enseignantPresent = $request->input('enseignant_present');
            $etudiantsPresents = $request->input('etudiants_presents', []);
            $etudiantsAbsents = $request->input('etudiants_absents', []);
            $dureeEffective = $request->input('duree_effective_minutes');
            $commentaire = $request->input('commentaire');
            $typeCours = $request->input('type_cours', 'visio');

            // Mettre à jour les informations du cours
            $cours->update([
                'date_cours' => $dateCours,
                'heure_debut' => $heureDebut,
                'heure_fin' => $heureFin,
                'duree_effective' => $dureeEffective,
                'type_cours' => $typeCours,
                'statut' => 'realise',
                'commentaires' => $commentaire,
                'updated_by' => auth()->id()
            ]);

            // Créer ou mettre à jour les présences
            // D'abord, supprimer les anciennes présences pour ce cours
            \App\Models\ESBTPAttendance::where('seance_cours_id', $cours->id)->delete();

            $presencesSauvegardees = [];

            // Récupérer tous les étudiants de la classe
            $tousEtudiants = ESBTPEtudiant::whereHas('inscriptions', function ($q) use ($cours) {
                $q->where('classe_id', $cours->classe_id)
                  ->where('annee_universitaire_id', $cours->emploiTemps->annee_universitaire_id)
                  ->where('status', 'active');
            })->get();

            foreach ($tousEtudiants as $etudiant) {
                $isPresent = in_array($etudiant->id, $etudiantsPresents);
                $isAbsent = in_array($etudiant->id, $etudiantsAbsents);

                // Si ni présent ni absent explicitement, considérer comme non renseigné
                $statut = 'non_renseigne';
                if ($isPresent) {
                    $statut = 'present';
                } elseif ($isAbsent) {
                    $statut = 'absent';
                }

                if ($statut !== 'non_renseigne') {
                    $presence = \App\Models\ESBTPAttendance::create([
                        'etudiant_id' => $etudiant->id,
                        'seance_cours_id' => $cours->id,
                        'date' => $dateCours,
                        'heure_debut' => $heureDebut,
                        'heure_fin' => $heureFin,
                        'statut' => $statut,
                        'call_type' => 'lms_online',
                        'commentaire' => 'Présence cours en ligne via LMS',
                        'created_by' => auth()->id()
                    ]);

                    $presencesSauvegardees[] = [
                        'etudiant_id' => $etudiant->id,
                        'matricule' => $etudiant->matricule,
                        'statut' => $statut,
                        'presence_id' => $presence->id
                    ];
                }
            }

            // Présence enseignant
            if ($enseignantPresent) {
                // Créer ou mettre à jour la présence enseignant
                \App\Models\ESBTPEnseignantPresence::updateOrCreate(
                    [
                        'enseignant_id' => $cours->enseignant_id,
                        'seance_cours_id' => $cours->id
                    ],
                    [
                        'date' => $dateCours,
                        'heure_debut' => $heureDebut,
                        'heure_fin' => $heureFin,
                        'statut' => 'present',
                        'commentaire' => 'Cours en ligne via LMS',
                        'created_by' => auth()->id()
                    ]
                );
            }

            // Logger l'activité
            \Log::info('Présences cours en ligne sauvegardées', [
                'cours_id' => $cours->id,
                'enseignant_id' => auth()->id(),
                'nb_presences' => count($presencesSauvegardees),
                'nb_presents' => count($etudiantsPresents),
                'nb_absents' => count($etudiantsAbsents),
                'source' => 'LMS'
            ]);

            DB::commit();

            return $this->successResponse([
                'cours' => [
                    'id' => $cours->id,
                    'titre' => $cours->titre,
                    'statut' => $cours->statut,
                    'type_cours' => $cours->type_cours
                ],
                'presences_sauvegardees' => $presencesSauvegardees,
                'statistiques' => [
                    'total_etudiants' => $tousEtudiants->count(),
                    'presents' => count($etudiantsPresents),
                    'absents' => count($etudiantsAbsents),
                    'non_renseignes' => $tousEtudiants->count() - count($etudiantsPresents) - count($etudiantsAbsents),
                    'taux_presence' => $tousEtudiants->count() > 0 ?
                        round((count($etudiantsPresents) / $tousEtudiants->count()) * 100, 1) : 0,
                    'enseignant_present' => $enseignantPresent
                ]
            ], 'Présences sauvegardées avec succès');

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Erreur sauvegarde présences LMS', [
                'cours_id' => $coursId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return $this->errorResponse('Erreur lors de la sauvegarde des présences: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Met à jour le statut d'un cours (démarré, en cours, terminé)
     *
     * Endpoint: PUT /api/lms/cours/{coursId}/statut
     *
     * @param Request $request
     * @param int $coursId
     * @return JsonResponse
     */
    public function updateCourseStatus(Request $request, int $coursId): JsonResponse
    {
        // Vérifier les permissions
        $roleCheck = $this->checkRoleAccess(['enseignant', 'coordinateur']);
        if ($roleCheck) {
            return $roleCheck;
        }

        $cours = ESBTPSeanceCours::find($coursId);
        if (!$cours) {
            return $this->errorResponse('Cours introuvable', [], 404);
        }

        // Vérifier que l'enseignant a le droit de modifier ce cours
        if (auth()->user()->can('identity.teach') && !auth()->user()->can('identity.coordinate') && $cours->enseignant_id !== auth()->id()) {
            return $this->errorResponse('Accès non autorisé à ce cours', [], 403);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'statut' => 'required|string|in:programme,en_cours,realise,annule,reporte',
            'commentaire' => 'nullable|string|max:500',
            'lien_visio' => 'nullable|url',
            'heure_debut_effective' => 'nullable',
            'heure_fin_effective' => 'nullable'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Données invalides',
                $validator->errors()->toArray(),
                422
            );
        }

        try {
            $cours->update([
                'statut' => $request->input('statut'),
                'commentaires' => $request->input('commentaire'),
                'lien_visio' => $request->input('lien_visio'),
                'heure_debut_effective' => $request->input('heure_debut_effective'),
                'heure_fin_effective' => $request->input('heure_fin_effective'),
                'updated_by' => auth()->id()
            ]);

            // Logger l'activité
            \Log::info('Statut cours mis à jour depuis LMS', [
                'cours_id' => $cours->id,
                'ancien_statut' => $cours->getOriginal('statut'),
                'nouveau_statut' => $cours->statut,
                'enseignant_id' => auth()->id()
            ]);

            return $this->successResponse([
                'cours' => [
                    'id' => $cours->id,
                    'titre' => $cours->titre,
                    'statut' => $cours->statut,
                    'lien_visio' => $cours->lien_visio,
                    'derniere_modification' => $cours->updated_at
                ]
            ], 'Statut du cours mis à jour');

        } catch (\Exception $e) {
            \Log::error('Erreur mise à jour statut cours', [
                'cours_id' => $coursId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return $this->errorResponse('Erreur lors de la mise à jour: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Récupère le semestre basé sur la date
     *
     * @param string $date
     * @return string
     */
    private function getSemestreFromDate(string $date): string
    {
        $mois = date('n', strtotime($date));

        // Semestre 1: Octobre à Février (10,11,12,1,2)
        // Semestre 2: Mars à Juillet (3,4,5,6,7)
        // Vacances: Août, Septembre (8,9)

        if (in_array($mois, [10, 11, 12, 1, 2])) {
            return 'S1';
        } elseif (in_array($mois, [3, 4, 5, 6, 7])) {
            return 'S2';
        } else {
            return 'Vacances';
        }
    }

    /**
     * Prévisualisation des notes avant validation définitive
     * (Permet au LMS d'envoyer les notes pour validation dans KLASSCI)
     *
     * Endpoint: POST /api/lms/evaluations/{evaluationId}/notes/preview
     *
     * @param Request $request
     * @param int $evaluationId
     * @return JsonResponse
     */
    public function previewEvaluationNotes(Request $request, int $evaluationId): JsonResponse
    {
        $evaluation = ESBTPEvaluation::find($evaluationId);
        if (!$evaluation) {
            return $this->errorResponse('Évaluation introuvable', [], 404);
        }

        // Valider les données
        $validator = Validator::make($request->all(), [
            'notes' => 'required|array|min:1',
            'notes.*.etudiant_id' => 'required|integer|exists:esbtp_etudiants,id',
            'notes.*.note' => 'nullable|numeric|min:0|max:' . ($evaluation->bareme ?? 20),
            'notes.*.is_absent' => 'boolean',
            'lms_session_id' => 'required|string', // ID de session du LMS pour le retour
            'redirect_url' => 'nullable|url' // URL de retour vers le LMS
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Données invalides',
                $validator->errors()->toArray(),
                422
            );
        }

        // Générer un token temporaire pour la validation
        $validationToken = \Str::random(64);

        // Stocker temporairement les données en cache (30 minutes)
        \Cache::put("lms_notes_preview_{$validationToken}", [
            'evaluation_id' => $evaluationId,
            'notes' => $request->input('notes'),
            'lms_session_id' => $request->input('lms_session_id'),
            'redirect_url' => $request->input('redirect_url'),
            'enseignant_id' => auth()->id(),
            'created_at' => now()
        ], 1800); // 30 minutes

        // Construire l'URL de validation dans KLASSCI
        $validationUrl = url("/esbtp/evaluations/{$evaluationId}/notes/validation") . "?token={$validationToken}";

        return $this->successResponse([
            'validation_token' => $validationToken,
            'validation_url' => $validationUrl,
            'expires_at' => now()->addMinutes(30),
            'evaluation' => [
                'id' => $evaluation->id,
                'titre' => $evaluation->titre,
                'matiere' => $evaluation->matiere->nom
            ],
            'notes_count' => count($request->input('notes'))
        ], 'Prévisualisation préparée. Redirection vers KLASSCI pour validation.');
    }
}