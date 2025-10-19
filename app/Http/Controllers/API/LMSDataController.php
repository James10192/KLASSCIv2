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
}