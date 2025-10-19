<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPEvaluation;
use App\Models\User;

/**
 * Contrôleur pour les données en lecture seule du LMS
 *
 * Fournit toutes les données nécessaires au LMS :
 * - Matières et cours
 * - Classes et étudiants
 * - Emploi du temps
 * - Structure organisationnelle
 *
 * @package App\Http\Controllers\API
 * @author KLASSCI Team
 * @version 1.0
 */
class LMSDataController extends BaseApiController
{
    /**
     * Liste des matières accessibles à l'utilisateur
     *
     * Endpoint: GET /api/lms/matieres
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function matieres(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        \Log::info('🚀 LMS Matieres API - Starting request');

        $annee = $this->getAnneeCouraante();
        $anneeTime = microtime(true);
        \Log::info('⏱️ Got current year in: ' . round(($anneeTime - $startTime) * 1000, 2) . 'ms');

        if (!$annee) {
            return $this->errorResponse('Aucune année universitaire courante trouvée');
        }

        // Base query avec relations - Utilise les tables pivot globales (filiere + niveau)
        $query = ESBTPMatiere::with([
            'filiere',  // Relation BelongsTo (unique)
            'niveauEtude',  // Relation BelongsTo (unique)
            'filieres',  // Relation BelongsToMany (plusieurs via pivot)
            'niveaux',   // Relation BelongsToMany (plusieurs via pivot)
            'enseignants' => function ($q) use ($annee) {
                $q->where('esbtp_enseignant_matiere.annee_universitaire_id', $annee->id)
                  ->where('esbtp_enseignant_matiere.is_active', true);
            }
        ])->where('esbtp_matieres.is_active', true);

        // Appliquer les filtres de rôle
        $query = $this->applyRoleFilters($query, 'matieres');

        // Filtres optionnels
        if ($request->has('filiere_id')) {
            $query->where('filiere_id', $request->filiere_id);
        }

        if ($request->has('niveau_id')) {
            $query->where('niveau_etude_id', $request->niveau_id);
        }

        if ($request->has('enseignant_id')) {
            $query->whereHas('enseignants', function ($q) use ($request, $annee) {
                $q->where('enseignant_id', $request->enseignant_id)
                  ->where('esbtp_enseignant_matiere.annee_universitaire_id', $annee->id);
            });
        }

        $matieres = $query->get();
        $queryTime = microtime(true);
        \Log::info('📊 Query executed in: ' . round(($queryTime - $anneeTime) * 1000, 2) . 'ms - Found ' . $matieres->count() . ' matieres');

        // Formater les données pour le LMS - UTILISE LES TABLES PIVOT GLOBALES
        $data = $matieres->map(function ($matiere) use ($annee) {
            // Combinaisons disponibles (filière + niveau) via tables pivot
            $combinaisons = [];
            foreach ($matiere->filieres as $filiere) {
                foreach ($matiere->niveaux as $niveau) {
                    $combinaisons[] = [
                        'filiere' => [
                            'id' => $filiere->id,
                            'nom' => $filiere->name ?? $filiere->nom,
                            'code' => $filiere->code
                        ],
                        'niveau' => [
                            'id' => $niveau->id,
                            'nom' => $niveau->name ?? $niveau->nom,
                            'code' => $niveau->code
                        ]
                    ];
                }
            }

            return [
                'id' => $matiere->id,
                'nom' => $matiere->name ?? $matiere->nom,
                'code' => $matiere->code,
                'description' => $matiere->description,
                'coefficient' => $matiere->coefficient,
                'couleur' => $matiere->couleur,
                'type_formation' => $matiere->type_formation,
                'heures' => [
                    'cm' => $matiere->heures_cm,
                    'td' => $matiere->heures_td,
                    'tp' => $matiere->heures_tp,
                    'stage' => $matiere->heures_stage ?? 0,
                    'total' => $matiere->heures_cm + $matiere->heures_td + $matiere->heures_tp + ($matiere->heures_stage ?? 0)
                ],
                'combinaisons' => $combinaisons,  // TOUTES les combinaisons possibles
                'enseignants' => $matiere->enseignants->map(function ($enseignant) {
                    return [
                        'id' => $enseignant->id,
                        'nom' => $enseignant->name,
                        'email' => $enseignant->email
                    ];
                }),
                'lms_metadata' => [
                    'has_online_courses' => false,
                    'last_course_date' => null,
                    'total_evaluations' => $matiere->evaluations()
                        ->where('annee_universitaire_id', $annee->id)->count()
                ]
            ];
        });

        $mapTime = microtime(true);
        \Log::info('🔄 Data mapping completed in: ' . round(($mapTime - $queryTime) * 1000, 2) . 'ms');

        $totalTime = microtime(true);
        \Log::info('✅ LMS Matieres API - Total time: ' . round(($totalTime - $startTime) * 1000, 2) . 'ms');

        return $this->successResponse($data, 'Matières récupérées avec succès', [
            'total' => $data->count(),
            'filters_applied' => [
                'annee_universitaire' => $annee->nom,
                'role_filter' => auth()->user()->getRoleNames()->first()
            ],
            'performance' => [
                'total_time_ms' => round(($totalTime - $startTime) * 1000, 2),
                'query_time_ms' => round(($queryTime - $anneeTime) * 1000, 2),
                'mapping_time_ms' => round(($mapTime - $queryTime) * 1000, 2)
            ]
        ]);
    }

    /**
     * Liste des classes de l'année courante
     *
     * Endpoint: GET /api/lms/classes
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function classes(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        \Log::info('🚀 LMS Classes API - Starting request');

        $annee = $this->getAnneeCouraante();
        $anneeTime = microtime(true);
        \Log::info('⏱️ Got current year in: ' . round(($anneeTime - $startTime) * 1000, 2) . 'ms');

        if (!$annee) {
            return $this->errorResponse('Aucune année universitaire courante trouvée');
        }

        $query = ESBTPClasse::with([
            'filiere:id,name,libelle,code',
            'niveau:id,name,libelle,code,type,year',
            'inscriptions' => function ($q) use ($annee) {
                $q->select('id', 'classe_id', 'etudiant_id')
                  ->where('annee_universitaire_id', $annee->id)
                  ->where('status', 'active');
            }
        ])->select([
            'id', 'name', 'libelle', 'filiere_id', 'niveau_etude_id',
            'places_totales', 'places_occupees', 'is_active'
        ])->where('esbtp_classes.is_active', true);

        // Filtres optionnels
        if ($request->has('filiere_id')) {
            $query->where('filiere_id', $request->filiere_id);
        }

        if ($request->has('niveau_id')) {
            $query->where('niveau_id', $request->niveau_id);
        }

        $classes = $query->get();
        $queryTime = microtime(true);
        \Log::info('📊 Query executed in: ' . round(($queryTime - $anneeTime) * 1000, 2) . 'ms - Found ' . $classes->count() . ' classes');

        $data = $classes->map(function ($classe) use ($annee) {
            // Compter les inscriptions actives pour l'année courante
            $nbEtudiants = $classe->inscriptions->count();

            // Récupérer les matières disponibles via combinaison (filiere_id + niveau_id)
            $matieres = ESBTPMatiere::where('is_active', true)
                ->whereHas('filieres', function ($q) use ($classe) {
                    $q->where('esbtp_filieres.id', $classe->filiere_id);
                })
                ->whereHas('niveaux', function ($q) use ($classe) {
                    $q->where('esbtp_niveau_etudes.id', $classe->niveau_etude_id);
                })
                ->get();

            // Récupérer les évaluations programmées pour cette classe
            $evaluations = ESBTPEvaluation::with('matiere')
                ->where('classe_id', $classe->id)
                ->where('annee_universitaire_id', $annee->id)
                ->orderBy('date_evaluation', 'desc')
                ->get();

            return [
                'id' => $classe->id,
                'name' => $classe->name,
                'libelle' => $classe->libelle,
                'filiere_id' => $classe->filiere_id,
                'niveau_etude_id' => $classe->niveau_etude_id,
                'places_totales' => $classe->places_totales,
                'places_occupees' => $nbEtudiants, // Nombre réel d'inscrits année courante
                'is_active' => $classe->is_active,
                'filiere' => $classe->filiere ? [
                    'id' => $classe->filiere->id,
                    'name' => $classe->filiere->name,
                    'libelle' => $classe->filiere->libelle,
                    'code' => $classe->filiere->code
                ] : null,
                'niveau' => $classe->niveau ? [
                    'id' => $classe->niveau->id,
                    'name' => $classe->niveau->name,
                    'libelle' => $classe->niveau->libelle,
                    'code' => $classe->niveau->code,
                    'type' => $classe->niveau->type,
                    'year' => $classe->niveau->year
                ] : null,
                'matieres_disponibles' => $matieres->map(function ($matiere) {
                    return [
                        'id' => $matiere->id,
                        'nom' => $matiere->name ?? $matiere->nom,
                        'code' => $matiere->code,
                        'coefficient' => $matiere->coefficient,  // Coefficient par défaut
                        'source' => 'catalogue_global'  // Via combinaison filiere+niveau
                    ];
                }),
                'evaluations_programmees' => $evaluations->map(function ($evaluation) {
                    return [
                        'id' => $evaluation->id,
                        'titre' => $evaluation->titre,
                        'description' => $evaluation->description,
                        'type' => $evaluation->type,
                        'status' => $evaluation->status,
                        'matiere' => [
                            'id' => $evaluation->matiere->id,
                            'nom' => $evaluation->matiere->name ?? $evaluation->matiere->nom,
                            'code' => $evaluation->matiere->code
                        ],
                        'programmation' => [
                            'date_evaluation' => $evaluation->date_evaluation,
                            'duree_minutes' => $evaluation->duree_minutes,
                            'coefficient' => $evaluation->coefficient,
                            'bareme' => $evaluation->bareme
                        ],
                        'publication' => [
                            'is_published' => $evaluation->is_published,
                            'notes_published' => $evaluation->notes_published
                        ],
                        'lms_integration' => [
                            'can_execute_online' => in_array($evaluation->status, ['planifiee', 'en_cours', 'brouillon']),
                            'has_online_version' => false, // À définir par le LMS
                            'notes_count' => $evaluation->notes()->count(),
                            'can_submit_notes' => true // Le LMS peut envoyer des notes via API
                        ]
                    ];
                })
            ];
        });

        $mapTime = microtime(true);
        \Log::info('🔄 Data mapping completed in: ' . round(($mapTime - $queryTime) * 1000, 2) . 'ms');

        $totalTime = microtime(true);
        \Log::info('✅ LMS Classes API - Total time: ' . round(($totalTime - $startTime) * 1000, 2) . 'ms');

        return $this->successResponse($data, 'Classes récupérées avec succès', [
            'total' => $data->count(),
            'annee_universitaire' => $annee->nom,
            'performance' => [
                'total_time_ms' => round(($totalTime - $startTime) * 1000, 2),
                'query_time_ms' => round(($queryTime - $anneeTime) * 1000, 2),
                'mapping_time_ms' => round(($mapTime - $queryTime) * 1000, 2)
            ]
        ]);
    }

    /**
     * Liste des étudiants d'une classe
     *
     * Endpoint: GET /api/lms/classes/{classeId}/etudiants
     *
     * Retourne UNIQUEMENT les étudiants avec une inscription active
     * dans l'année universitaire courante (is_current=true)
     *
     * @param Request $request
     * @param int $classeId
     * @return JsonResponse
     */
    public function etudiantsClasse(Request $request, int $classeId): JsonResponse
    {
        // Vérifier les permissions
        $roleCheck = $this->checkRoleAccess(['enseignant', 'coordinateur', 'superAdmin']);
        if ($roleCheck) {
            return $roleCheck;
        }

        $annee = $this->getAnneeCouraante();

        if (!$annee) {
            return $this->errorResponse('Aucune année universitaire courante trouvée');
        }

        $classe = ESBTPClasse::find($classeId);

        if (!$classe) {
            return $this->errorResponse('Classe introuvable', [], 404);
        }

        // Pour les enseignants, vérifier qu'ils enseignent dans cette classe
        if (auth()->user()->hasRole('enseignant')) {
            $hasAccess = ESBTPMatiere::whereHas('enseignants', function ($q) use ($annee) {
                $q->where('enseignant_id', auth()->id())
                  ->where('esbtp_enseignant_matiere.annee_universitaire_id', $annee->id)
                  ->where('esbtp_enseignant_matiere.is_active', true);
            })->whereHas('filieres', function ($q) use ($classe) {
                $q->where('esbtp_filieres.id', $classe->filiere_id);
            })->whereHas('niveaux', function ($q) use ($classe) {
                $q->where('esbtp_niveau_etudes.id', $classe->niveau_etude_id);
            })->exists();

            if (!$hasAccess) {
                return $this->errorResponse('Accès non autorisé à cette classe', [], 403);
            }
        }

        // FILTRE CORRECT: année courante + status active + classe spécifique
        $etudiants = ESBTPEtudiant::whereHas('inscriptions', function ($q) use ($classeId, $annee) {
            $q->where('classe_id', $classeId)
              ->where('annee_universitaire_id', $annee->id)
              ->where('status', 'active');
        })->with(['user', 'inscriptions' => function ($q) use ($annee, $classeId) {
            $q->where('annee_universitaire_id', $annee->id)
              ->where('classe_id', $classeId)
              ->where('status', 'active');
        }])->get();

        $data = $etudiants->map(function ($etudiant) use ($annee) {
            $inscription = $etudiant->inscriptions->first();

            return [
                'id' => $etudiant->id,
                'matricule' => $etudiant->matricule,
                'nom_complet' => $etudiant->user->name ?? 'N/A',
                'prenom' => $etudiant->user->first_name ?? null,
                'nom' => $etudiant->user->last_name ?? null,
                'email' => $etudiant->user->email ?? null,
                'telephone' => $etudiant->telephone,
                'date_naissance' => $etudiant->date_naissance,
                'sexe' => $etudiant->sexe,
                'inscription' => $inscription ? [
                    'id' => $inscription->id,
                    'annee_universitaire_id' => $inscription->annee_universitaire_id,
                    'date_inscription' => $inscription->created_at->format('Y-m-d'),
                    'status' => $inscription->status
                ] : null,
                'lms_profile' => [
                    'photo_url' => $etudiant->user->profile_photo_url ?? null,
                    'last_login' => $etudiant->user->last_login_at,
                    'is_active' => $etudiant->user->is_active ?? false
                ]
            ];
        });

        return $this->successResponse($data, 'Étudiants de la classe récupérés', [
            'classe' => [
                'id' => $classe->id,
                'nom' => $classe->name ?? $classe->nom,
                'filiere' => $classe->filiere->nom ?? null,
                'niveau' => $classe->niveau->nom ?? null
            ],
            'annee_universitaire' => [
                'id' => $annee->id,
                'nom' => $annee->nom,
                'is_current' => true
            ],
            'total_etudiants' => $data->count()
        ]);
    }

    /**
     * Emploi du temps accessible à l'utilisateur
     *
     * Endpoint: GET /api/lms/emploi-temps
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function emploiTemps(Request $request): JsonResponse
    {
        $annee = $this->getAnneeCouraante();

        if (!$annee) {
            return $this->errorResponse('Aucune année universitaire courante trouvée');
        }

        // Paramètres de date (optionnels)
        $dateDebut = $request->input('date_debut', now()->startOfWeek()->format('Y-m-d'));
        $dateFin = $request->input('date_fin', now()->endOfWeek()->format('Y-m-d'));

        // Base query pour l'emploi du temps
        $query = \App\Models\ESBTPSeanceCours::with([
            'matiere',
            'classe',
            'salle',
            'enseignant'
        ])->whereBetween('date_cours', [$dateDebut, $dateFin])
          ->whereHas('emploiTemps', function ($q) use ($annee) {
              $q->where('annee_universitaire_id', $annee->id)
                ->where('esbtp_emploi_temps.is_active', true);
          });

        // Filtrer selon le rôle
        $user = auth()->user();

        if ($user->hasRole('enseignant')) {
            $query->where('enseignant_id', $user->id);
        } elseif ($user->hasRole('etudiant')) {
            $etudiant = $user->etudiant;
            if ($etudiant) {
                $inscription = $etudiant->inscriptions()
                    ->where('annee_universitaire_id', $annee->id)
                    ->where('status', 'active')
                    ->first();

                if ($inscription) {
                    $query->where('classe_id', $inscription->classe_id);
                } else {
                    return $this->errorResponse('Aucune inscription active trouvée');
                }
            }
        }

        $seances = $query->orderBy('date_cours')
                         ->orderBy('heure_debut')
                         ->get();

        $data = $seances->map(function ($seance) {
            return [
                'id' => $seance->id,
                'titre' => $seance->titre ?: $seance->matiere->nom,
                'matiere' => [
                    'id' => $seance->matiere->id,
                    'nom' => $seance->matiere->nom,
                    'code' => $seance->matiere->code,
                    'couleur' => $seance->matiere->couleur
                ],
                'classe' => [
                    'id' => $seance->classe->id,
                    'nom' => $seance->classe->nom
                ],
                'enseignant' => $seance->enseignant ? [
                    'id' => $seance->enseignant->id,
                    'nom' => $seance->enseignant->name
                ] : null,
                'programmation' => [
                    'date_cours' => $seance->date_cours,
                    'heure_debut' => $seance->heure_debut,
                    'heure_fin' => $seance->heure_fin,
                    'duree_minutes' => $seance->duree_minutes
                ],
                'salle' => $seance->salle ? [
                    'id' => $seance->salle->id,
                    'nom' => $seance->salle->nom,
                    'capacite' => $seance->salle->capacite
                ] : null,
                'type_cours' => $seance->type_cours,
                'statut' => $seance->statut,
                'lms_integration' => [
                    'can_start_visio' => auth()->user()->hasRole('enseignant'),
                    'visio_url' => null, // À remplir par le LMS
                    'course_materials_count' => 0 // À remplir par le LMS
                ]
            ];
        });

        return $this->successResponse($data, 'Emploi du temps récupéré', [
            'periode' => [
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin
            ],
            'total_seances' => $data->count(),
            'user_context' => $user->getRoleNames()->first()
        ]);
    }

    /**
     * Structure organisationnelle (filières et niveaux)
     *
     * Endpoint: GET /api/lms/structure
     *
     * @return JsonResponse
     */
    public function structure(): JsonResponse
    {
        $filieres = ESBTPFiliere::where('esbtp_filieres.is_active', true)
            ->with(['niveauxEtudes' => function ($q) {
                $q->where('esbtp_niveau_etudes.is_active', true);
            }])
            ->get();

        $niveaux = ESBTPNiveauEtude::where('esbtp_niveau_etudes.is_active', true)->get();

        $data = [
            'filieres' => $filieres->map(function ($filiere) {
                return [
                    'id' => $filiere->id,
                    'nom' => $filiere->nom,
                    'code' => $filiere->code,
                    'description' => $filiere->description,
                    'niveaux_associes' => $filiere->niveauxEtudes->map(function ($niveau) {
                        return [
                            'id' => $niveau->id,
                            'nom' => $niveau->nom,
                            'code' => $niveau->code
                        ];
                    })
                ];
            }),
            'niveaux_etude' => $niveaux->map(function ($niveau) {
                return [
                    'id' => $niveau->id,
                    'nom' => $niveau->nom,
                    'code' => $niveau->code,
                    'description' => $niveau->description
                ];
            })
        ];

        return $this->successResponse($data, 'Structure organisationnelle récupérée');
    }

    /**
     * Évaluations programmées accessibles à l'utilisateur
     *
     * Endpoint: GET /api/lms/evaluations
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function evaluations(Request $request): JsonResponse
    {
        $annee = $this->getAnneeCouraante();

        if (!$annee) {
            return $this->errorResponse('Aucune année universitaire courante trouvée');
        }

        $query = ESBTPEvaluation::with(['matiere', 'classe'])
            ->where('annee_universitaire_id', $annee->id);

        // Appliquer les filtres de rôle
        $query = $this->applyRoleFilters($query, 'evaluations');

        // Filtres optionnels
        if ($request->has('matiere_id')) {
            $query->where('matiere_id', $request->matiere_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $evaluations = $query->orderBy('date_evaluation', 'desc')->get();

        $data = $evaluations->map(function ($evaluation) {
            return [
                'id' => $evaluation->id,
                'titre' => $evaluation->titre,
                'description' => $evaluation->description,
                'type' => $evaluation->type,
                'status' => $evaluation->status,
                'matiere' => [
                    'id' => $evaluation->matiere->id,
                    'nom' => $evaluation->matiere->nom,
                    'code' => $evaluation->matiere->code
                ],
                'classe' => [
                    'id' => $evaluation->classe->id,
                    'nom' => $evaluation->classe->nom
                ],
                'programmation' => [
                    'date_evaluation' => $evaluation->date_evaluation,
                    'duree_minutes' => $evaluation->duree_minutes,
                    'coefficient' => $evaluation->coefficient,
                    'bareme' => $evaluation->bareme
                ],
                'publication' => [
                    'is_published' => $evaluation->is_published,
                    'notes_published' => $evaluation->notes_published
                ],
                'lms_integration' => [
                    'can_execute_online' => in_array($evaluation->status, ['scheduled', 'draft']),
                    'has_online_version' => false, // À définir par le LMS
                    'notes_count' => $evaluation->notes()->count()
                ]
            ];
        });

        return $this->successResponse($data, 'Évaluations récupérées', [
            'total' => $data->count(),
            'annee_universitaire' => $annee->nom
        ]);
    }

    /**
     * Liste des enseignants actifs
     *
     * Endpoint: GET /api/lms/enseignants
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function enseignants(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        \Log::info('🚀 LMS Enseignants API - Starting request');

        $annee = $this->getAnneeCouraante();
        $anneeTime = microtime(true);
        \Log::info('⏱️ Got current year in: ' . round(($anneeTime - $startTime) * 1000, 2) . 'ms');

        if (!$annee) {
            return $this->errorResponse('Aucune année universitaire courante trouvée');
        }

        // Récupérer les enseignants actifs (simplifié pour l'instant)
        $query = User::where('role', 'enseignant');

        $enseignants = $query->get();
        $queryTime = microtime(true);
        \Log::info('📊 Query executed in: ' . round(($queryTime - $anneeTime) * 1000, 2) . 'ms - Found ' . $enseignants->count() . ' enseignants');

        $data = $enseignants->map(function ($enseignant) {
            return [
                'id' => $enseignant->id,
                'nom' => $enseignant->name,
                'email' => $enseignant->email,
                'role' => $enseignant->role
            ];
        });

        $mapTime = microtime(true);
        \Log::info('🔄 Data mapping completed in: ' . round(($mapTime - $queryTime) * 1000, 2) . 'ms');

        $totalTime = microtime(true);
        \Log::info('✅ LMS Enseignants API - Total time: ' . round(($totalTime - $startTime) * 1000, 2) . 'ms');

        return $this->successResponse($data, 'Enseignants récupérés avec succès', [
            'total' => $data->count(),
            'annee_universitaire' => $annee->nom,
            'performance' => [
                'total_time_ms' => round(($totalTime - $startTime) * 1000, 2),
                'query_time_ms' => round(($queryTime - $anneeTime) * 1000, 2),
                'mapping_time_ms' => round(($mapTime - $queryTime) * 1000, 2)
            ]
        ]);
    }

    /**
     * Liste des filières actives
     *
     * Endpoint: GET /api/lms/filieres
     *
     * @return JsonResponse
     */
    public function filieres(): JsonResponse
    {
        $startTime = microtime(true);
        \Log::info('🚀 LMS Filieres API - Starting request');

        $filieres = ESBTPFiliere::where('esbtp_filieres.is_active', true)
            ->withCount(['classes' => function ($q) {
                $q->where('esbtp_classes.is_active', true);
            }])
            ->get();

        $queryTime = microtime(true);
        \Log::info('📊 Query executed in: ' . round(($queryTime - $startTime) * 1000, 2) . 'ms - Found ' . $filieres->count() . ' filières');

        $data = $filieres->map(function ($filiere) {
            return [
                'id' => $filiere->id,
                'nom' => $filiere->nom,
                'code' => $filiere->code,
                'description' => $filiere->description,
                'classes_count' => $filiere->classes_count
            ];
        });

        $mapTime = microtime(true);
        \Log::info('🔄 Data mapping completed in: ' . round(($mapTime - $queryTime) * 1000, 2) . 'ms');

        $totalTime = microtime(true);
        \Log::info('✅ LMS Filieres API - Total time: ' . round(($totalTime - $startTime) * 1000, 2) . 'ms');

        return $this->successResponse($data, 'Filières récupérées avec succès', [
            'total' => $data->count(),
            'performance' => [
                'total_time_ms' => round(($totalTime - $startTime) * 1000, 2),
                'query_time_ms' => round(($queryTime - $startTime) * 1000, 2),
                'mapping_time_ms' => round(($mapTime - $queryTime) * 1000, 2)
            ]
        ]);
    }

    /**
     * Liste des niveaux d'études actifs
     *
     * Endpoint: GET /api/lms/niveaux-etudes
     *
     * @return JsonResponse
     */
    public function niveauxEtudes(): JsonResponse
    {
        $startTime = microtime(true);
        \Log::info('🚀 LMS Niveaux Etudes API - Starting request');

        $niveaux = ESBTPNiveauEtude::where('esbtp_niveau_etudes.is_active', true)
            ->withCount(['classes' => function ($q) {
                $q->where('esbtp_classes.is_active', true);
            }])
            ->get();

        $queryTime = microtime(true);
        \Log::info('📊 Query executed in: ' . round(($queryTime - $startTime) * 1000, 2) . 'ms - Found ' . $niveaux->count() . ' niveaux');

        $data = $niveaux->map(function ($niveau) {
            return [
                'id' => $niveau->id,
                'nom' => $niveau->nom,
                'code' => $niveau->code,
                'description' => $niveau->description,
                'classes_count' => $niveau->classes_count
            ];
        });

        $mapTime = microtime(true);
        \Log::info('🔄 Data mapping completed in: ' . round(($mapTime - $queryTime) * 1000, 2) . 'ms');

        $totalTime = microtime(true);
        \Log::info('✅ LMS Niveaux Etudes API - Total time: ' . round(($totalTime - $startTime) * 1000, 2) . 'ms');

        return $this->successResponse($data, 'Niveaux d\'études récupérés avec succès', [
            'total' => $data->count(),
            'performance' => [
                'total_time_ms' => round(($totalTime - $startTime) * 1000, 2),
                'query_time_ms' => round(($queryTime - $startTime) * 1000, 2),
                'mapping_time_ms' => round(($mapTime - $queryTime) * 1000, 2)
            ]
        ]);
    }

    /**
     * Dashboard de l'étudiant connecté
     *
     * Endpoint: GET /api/lms/me/dashboard
     *
     * Retourne toutes les données nécessaires pour l'étudiant:
     * - Sa classe avec inscription active
     * - Ses cours (matières disponibles)
     * - Ses évaluations (quiz) programmées
     * - Statistiques personnelles
     *
     * @return JsonResponse
     */
    public function studentDashboard(): JsonResponse
    {
        $startTime = microtime(true);
        \Log::info('🚀 LMS Student Dashboard API - Starting request', ['user_id' => auth()->id()]);

        // Vérifier que l'utilisateur est un étudiant
        $user = auth()->user();
        if (!$user->hasRole('etudiant')) {
            return $this->errorResponse('Cet endpoint est réservé aux étudiants', [], 403);
        }

        $annee = $this->getAnneeCouraante();
        if (!$annee) {
            return $this->errorResponse('Aucune année universitaire courante trouvée');
        }

        // Récupérer l'étudiant
        $etudiant = $user->etudiant;
        if (!$etudiant) {
            return $this->errorResponse('Profil étudiant introuvable', [], 404);
        }

        // Récupérer l'inscription active de l'année courante
        $inscription = $etudiant->inscriptions()
            ->with(['classe.filiere', 'classe.niveau'])
            ->where('annee_universitaire_id', $annee->id)
            ->where('status', 'active')
            ->first();

        if (!$inscription) {
            return $this->errorResponse('Aucune inscription active trouvée pour l\'année courante', [], 404);
        }

        $classe = $inscription->classe;

        // Sécuriser les dates éventuelles de l'année universitaire (certaines colonnes peuvent être nulles)
        $dateDebutAnnee = $annee->date_debut ?? now()->startOfYear();
        $dateFinAnnee = $annee->date_fin ?? now();

        // Récupérer les matières (cours) disponibles pour la classe
        $matieres = ESBTPMatiere::where('is_active', true)
            ->whereHas('filieres', function ($q) use ($classe) {
                $q->where('esbtp_filieres.id', $classe->filiere_id);
            })
            ->whereHas('niveaux', function ($q) use ($classe) {
                $q->where('esbtp_niveau_etudes.id', $classe->niveau_etude_id);
            })
            ->get();

        // Récupérer les évaluations (quiz) programmées pour la classe
        $evaluations = ESBTPEvaluation::with('matiere')
            ->where('classe_id', $classe->id)
            ->where('annee_universitaire_id', $annee->id)
            ->where('is_published', true) // Seulement les évaluations publiées
            ->orderBy('date_evaluation', 'desc')
            ->get();

        // Calculer statistiques personnelles de l'étudiant
        $statistiques = [
            'attendances' => [
                'total_presences' => \App\Models\ESBTPAttendance::where('etudiant_id', $etudiant->id)
                    ->where('date', '>=', $dateDebutAnnee)
                    ->where('date', '<=', $dateFinAnnee)
                    ->whereIn('statut', ['present', 'retard'])
                    ->count(),
                'total_absences' => \App\Models\ESBTPAttendance::where('etudiant_id', $etudiant->id)
                    ->where('date', '>=', $dateDebutAnnee)
                    ->where('date', '<=', $dateFinAnnee)
                    ->where('statut', 'absent')
                    ->count()
            ],
            'evaluations' => [
                'total_passees' => \App\Models\ESBTPNote::where('etudiant_id', $etudiant->id)
                    ->whereHas('evaluation', function($q) use ($annee) {
                        $q->where('annee_universitaire_id', $annee->id);
                    })
                    ->count(),
                'moyenne_generale' => \App\Models\ESBTPNote::where('etudiant_id', $etudiant->id)
                    ->whereHas('evaluation', function($q) use ($annee) {
                        $q->where('annee_universitaire_id', $annee->id);
                    })
                    ->avg('note')
            ]
        ];

        $queryTime = microtime(true);
        \Log::info('📊 Dashboard data collected in: ' . round(($queryTime - $startTime) * 1000, 2) . 'ms');

        // Formater les données
        $data = [
            'etudiant' => [
                'id' => $etudiant->id,
                'matricule' => $etudiant->matricule,
                'nom_complet' => $user->name,
                'email' => $user->email,
                'photo_url' => $user->profile_photo_url ?? null
            ],
            'inscription' => [
                'id' => $inscription->id,
                'date_inscription' => $inscription->created_at->format('Y-m-d'),
                'status' => $inscription->status
            ],
            'classe' => [
                'id' => $classe->id,
                'name' => $classe->name,
                'libelle' => $classe->libelle,
                'filiere' => $classe->filiere ? [
                    'id' => $classe->filiere->id,
                    'nom' => $classe->filiere->name,
                    'code' => $classe->filiere->code
                ] : null,
                'niveau' => $classe->niveau ? [
                    'id' => $classe->niveau->id,
                    'nom' => $classe->niveau->name,
                    'code' => $classe->niveau->code
                ] : null
            ],
            'cours' => $matieres->map(function ($matiere) {
                return [
                    'id' => $matiere->id,
                    'nom' => $matiere->name,
                    'code' => $matiere->code,
                    'description' => $matiere->description,
                    'coefficient' => $matiere->coefficient,
                    'couleur' => $matiere->couleur,
                    'heures_total' => $matiere->heures_cm + $matiere->heures_td + $matiere->heures_tp
                ];
            }),
            'quiz' => $evaluations->map(function ($evaluation) use ($etudiant) {
                // Vérifier si l'étudiant a déjà une note pour cette évaluation
                $note = \App\Models\ESBTPNote::where('evaluation_id', $evaluation->id)
                    ->where('etudiant_id', $etudiant->id)
                    ->first();

                return [
                    'id' => $evaluation->id,
                    'titre' => $evaluation->titre,
                    'description' => $evaluation->description,
                    'type' => $evaluation->type,
                    'status' => $evaluation->status,
                    'matiere' => [
                        'id' => $evaluation->matiere->id,
                        'nom' => $evaluation->matiere->name,
                        'code' => $evaluation->matiere->code
                    ],
                    'programmation' => [
                        'date_evaluation' => $evaluation->date_evaluation,
                        'duree_minutes' => $evaluation->duree_minutes,
                        'bareme' => $evaluation->bareme
                    ],
                    'mon_resultat' => $note ? [
                        'note' => $note->note,
                        'sur' => $note->bareme,
                        'date_obtention' => $note->created_at->format('Y-m-d'),
                        'appreciation' => $note->appreciation
                    ] : null,
                    'lms_integration' => [
                        'can_take_online' => !$note && in_array($evaluation->status, ['planifiee', 'en_cours']),
                        'is_completed' => $note !== null
                    ]
                ];
            }),
            'statistiques' => $statistiques
        ];

        $mapTime = microtime(true);
        \Log::info('🔄 Data formatting completed in: ' . round(($mapTime - $queryTime) * 1000, 2) . 'ms');

        $totalTime = microtime(true);
        \Log::info('✅ LMS Student Dashboard API - Total time: ' . round(($totalTime - $startTime) * 1000, 2) . 'ms');

        return $this->successResponse($data, 'Dashboard étudiant récupéré avec succès', [
            'annee_universitaire' => [
                'id' => $annee->id,
                'nom' => $annee->nom,
                'is_current' => true
            ],
            'counts' => [
                'cours_total' => $data['cours']->count(),
                'quiz_total' => $data['quiz']->count(),
                'quiz_completed' => $data['quiz']->where('lms_integration.is_completed', true)->count()
            ],
            'performance' => [
                'total_time_ms' => round(($totalTime - $startTime) * 1000, 2),
                'query_time_ms' => round(($queryTime - $startTime) * 1000, 2),
                'mapping_time_ms' => round(($mapTime - $queryTime) * 1000, 2)
            ]
        ]);
    }

    /**
     * Dashboard de l'enseignant connecté
     *
     * Endpoint: GET /api/lms/me/teacher-dashboard
     *
     * Retourne toutes les données nécessaires pour l'enseignant:
     * - Ses matières assignées (via planning ou table pivot)
     * - Ses classes où il enseigne
     * - Ses séances de cours programmées
     * - Statistiques personnelles (heures, présences)
     *
     * @return JsonResponse
     */
    public function teacherDashboard(): JsonResponse
    {
        $startTime = microtime(true);
        \Log::info('🚀 LMS Teacher Dashboard API - Starting request', ['user_id' => auth()->id()]);

        // Vérifier que l'utilisateur est un enseignant (accepter 'teacher' OU 'enseignant')
        $user = auth()->user();
        if (!$user->hasAnyRole(['teacher', 'enseignant'])) {
            return $this->errorResponse('Cet endpoint est réservé aux enseignants', [], 403);
        }

        $annee = $this->getAnneeCouraante();
        if (!$annee) {
            return $this->errorResponse('Aucune année universitaire courante trouvée');
        }

        // Récupérer le teacher associé à l'utilisateur connecté (table esbtp_teachers)
        // IMPORTANT: teacher_id dans les séances référence esbtp_teachers.id, PAS users.id
        $teacher = \App\Models\ESBTPTeacher::where('user_id', $user->id)->first();
        $teacherId = $teacher ? $teacher->id : null;

        if (!$teacherId) {
            return $this->errorResponse('Profil enseignant introuvable dans la table esbtp_teachers', [], 404);
        }

        \Log::info('🔍 LMS Teacher Dashboard - Teacher mapping', [
            'user_id' => $user->id,
            'teacher_id' => $teacherId,
            'user_name' => $user->name
        ]);

        // Récupérer les matières de l'enseignant via 2 sources:
        // 1. Table pivot esbtp_enseignant_matiere (assignations officielles)
        // 2. Séances de cours (teacher_id dans esbtp_seance_cours)

        // Source 1: Table pivot (assignations officielles)
        $matieresPivot = ESBTPMatiere::whereHas('enseignants', function($q) use ($user, $annee) {
            $q->where('enseignant_id', $user->id)
              ->where('esbtp_enseignant_matiere.annee_universitaire_id', $annee->id)
              ->where('esbtp_enseignant_matiere.is_active', true);
        })->with(['filieres', 'niveaux'])->get();

        // Source 2: Séances de cours (via planning général)
        // IMPORTANT: Utiliser $teacherId (esbtp_teachers.id), PAS $user->id
        // IMPORTANT: Requêter directement via la table esbtp_seance_cours
        $matiereIdsFromSeances = \App\Models\ESBTPSeanceCours::where('teacher_id', $teacherId)
            ->where('annee_universitaire_id', $annee->id)
            ->distinct()
            ->pluck('matiere_id');

        $matieresSeances = ESBTPMatiere::whereIn('id', $matiereIdsFromSeances)
            ->with(['filieres', 'niveaux'])
            ->get();

        // Fusionner les deux sources (unique par ID)
        $matieres = $matieresPivot->merge($matieresSeances)->unique('id');

        // Récupérer les classes où l'enseignant enseigne
        $classeIdsFromSeances = \App\Models\ESBTPSeanceCours::where('teacher_id', $teacherId)
            ->where('annee_universitaire_id', $annee->id)
            ->distinct()
            ->pluck('classe_id');

        $classes = ESBTPClasse::whereIn('id', $classeIdsFromSeances)
            ->with(['filiere', 'niveau'])
            ->get();

        // Récupérer les séances programmées (prochaines 30 jours)
        $seances = \App\Models\ESBTPSeanceCours::with(['matiere', 'classe', 'emploiTemps'])
            ->where('teacher_id', $teacherId)
            ->where('date_seance', '>=', now()->format('Y-m-d'))
            ->where('date_seance', '<=', now()->addDays(30)->format('Y-m-d'))
            ->orderBy('date_seance')
            ->orderBy('heure_debut')
            ->get();

        // Récupérer les évaluations dont l'enseignant est responsable
        // (évaluations des matières qu'il enseigne)
        $matiereIds = $matieres->pluck('id');
        $evaluations = ESBTPEvaluation::with(['matiere', 'classe'])
            ->whereIn('matiere_id', $matiereIds)
            ->where('annee_universitaire_id', $annee->id)
            ->orderBy('date_evaluation', 'desc')
            ->get();

        // Calculer statistiques personnelles
        // IMPORTANT: Utiliser $teacherId pour les requêtes sur teacher_attendances
        $statistiques = [
            'heures' => [
                'total_seances' => \App\Models\ESBTPSeanceCours::where('teacher_id', $teacherId)
                    ->whereHas('emploiTemps', function($q) use ($annee) {
                        $q->where('annee_universitaire_id', $annee->id);
                    })
                    ->count(),
                'seances_effectuees' => \App\Models\ESBTPTeacherAttendance::where('teacher_id', $teacherId)
                    ->whereYear('date', $annee->date_debut ? $annee->date_debut->year : now()->year)
                    ->whereIn('status', ['present', 'late'])
                    ->where('type', 'start')
                    ->count()
            ],
            'evaluations' => [
                'total_programmees' => $evaluations->count(),
                'a_corriger' => $evaluations->where('status', 'terminee')
                    ->filter(function($eval) {
                        return $eval->notes()->count() < $eval->classe->inscriptions()
                            ->where('status', 'active')->count();
                    })->count()
            ]
        ];

        $queryTime = microtime(true);
        \Log::info('📊 Teacher Dashboard data collected in: ' . round(($queryTime - $startTime) * 1000, 2) . 'ms');

        // Formater les données
        $data = [
            'enseignant' => [
                'id' => $user->id,
                'nom_complet' => $user->name,
                'email' => $user->email,
                'photo_url' => $user->profile_photo_url ?? null
            ],
            'matieres' => $matieres->map(function ($matiere) use ($teacherId, $annee) {
                // Récupérer les combinaisons (filieres+niveaux) pour cette matière
                $combinaisons = [];
                foreach ($matiere->filieres as $filiere) {
                    foreach ($matiere->niveaux as $niveau) {
                        $combinaisons[] = [
                            'filiere' => $filiere->name,
                            'niveau' => $niveau->name
                        ];
                    }
                }

                // Compter les séances pour cette matière
                $nbSeances = \App\Models\ESBTPSeanceCours::where('teacher_id', $teacherId)
                    ->where('matiere_id', $matiere->id)
                    ->where('annee_universitaire_id', $annee->id)
                    ->count();

                return [
                    'id' => $matiere->id,
                    'nom' => $matiere->name,
                    'code' => $matiere->code,
                    'description' => $matiere->description,
                    'coefficient' => $matiere->coefficient,
                    'couleur' => $matiere->couleur,
                    'heures_total' => $matiere->heures_cm + $matiere->heures_td + $matiere->heures_tp,
                    'combinaisons' => $combinaisons,
                    'nb_seances_programmees' => $nbSeances
                ];
            }),
            'classes' => $classes->map(function ($classe) {
                return [
                    'id' => $classe->id,
                    'name' => $classe->name,
                    'libelle' => $classe->libelle,
                    'filiere' => $classe->filiere ? [
                        'id' => $classe->filiere->id,
                        'nom' => $classe->filiere->name,
                        'code' => $classe->filiere->code
                    ] : null,
                    'niveau' => $classe->niveau ? [
                        'id' => $classe->niveau->id,
                        'nom' => $classe->niveau->name,
                        'code' => $classe->niveau->code
                    ] : null
                ];
            }),
            'prochaines_seances' => $seances->map(function ($seance) {
                return [
                    'id' => $seance->id,
                    'matiere' => [
                        'id' => $seance->matiere->id,
                        'nom' => $seance->matiere->name,
                        'code' => $seance->matiere->code
                    ],
                    'classe' => [
                        'id' => $seance->classe->id,
                        'nom' => $seance->classe->name
                    ],
                    'programmation' => [
                        'date' => $seance->date_seance,
                        'heure_debut' => $seance->heure_debut,
                        'heure_fin' => $seance->heure_fin,
                        'salle' => $seance->salle
                    ],
                    'lms_integration' => [
                        'can_start_visio' => $seance->date_seance == now()->format('Y-m-d'),
                        'can_mark_attendance' => true
                    ]
                ];
            }),
            'evaluations' => $evaluations->map(function ($evaluation) {
                $nbNotes = $evaluation->notes()->count();
                $nbEtudiantsClasse = $evaluation->classe->inscriptions()
                    ->where('status', 'active')->count();

                return [
                    'id' => $evaluation->id,
                    'titre' => $evaluation->titre,
                    'description' => $evaluation->description,
                    'type' => $evaluation->type,
                    'status' => $evaluation->status,
                    'matiere' => [
                        'id' => $evaluation->matiere->id,
                        'nom' => $evaluation->matiere->name,
                        'code' => $evaluation->matiere->code
                    ],
                    'classe' => [
                        'id' => $evaluation->classe->id,
                        'nom' => $evaluation->classe->name
                    ],
                    'programmation' => [
                        'date_evaluation' => $evaluation->date_evaluation,
                        'duree_minutes' => $evaluation->duree_minutes,
                        'coefficient' => $evaluation->coefficient,
                        'bareme' => $evaluation->bareme
                    ],
                    'correction' => [
                        'notes_saisies' => $nbNotes,
                        'notes_attendues' => $nbEtudiantsClasse,
                        'progression' => $nbEtudiantsClasse > 0
                            ? round(($nbNotes / $nbEtudiantsClasse) * 100, 1)
                            : 0,
                        'is_complete' => $nbNotes >= $nbEtudiantsClasse
                    ],
                    'lms_integration' => [
                        'can_create_online' => in_array($evaluation->status, ['brouillon', 'planifiee']),
                        'can_submit_notes' => true
                    ]
                ];
            }),
            'statistiques' => $statistiques
        ];

        $mapTime = microtime(true);
        \Log::info('🔄 Teacher Dashboard formatting completed in: ' . round(($mapTime - $queryTime) * 1000, 2) . 'ms');

        $totalTime = microtime(true);
        \Log::info('✅ LMS Teacher Dashboard API - Total time: ' . round(($totalTime - $startTime) * 1000, 2) . 'ms');

        return $this->successResponse($data, 'Dashboard enseignant récupéré avec succès', [
            'annee_universitaire' => [
                'id' => $annee->id,
                'nom' => $annee->nom,
                'is_current' => true
            ],
            'counts' => [
                'matieres_total' => $data['matieres']->count(),
                'classes_total' => $data['classes']->count(),
                'seances_a_venir' => $data['prochaines_seances']->count(),
                'evaluations_total' => $data['evaluations']->count()
            ],
            'performance' => [
                'total_time_ms' => round(($totalTime - $startTime) * 1000, 2),
                'query_time_ms' => round(($queryTime - $startTime) * 1000, 2),
                'mapping_time_ms' => round(($mapTime - $queryTime) * 1000, 2)
            ]
        ]);
    }
}
