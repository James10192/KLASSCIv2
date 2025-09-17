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
        $annee = $this->getAnneeCouraante();

        if (!$annee) {
            return $this->errorResponse('Aucune année universitaire courante trouvée');
        }

        // Base query avec relations
        $query = ESBTPMatiere::with([
            'filiere',
            'niveauEtude',
            'classes' => function ($q) use ($annee) {
                $q->whereHas('inscriptions', function ($inscriptionQuery) use ($annee) {
                    $inscriptionQuery->where('annee_universitaire_id', $annee->id)
                                   ->where('status', 'active');
                });
            },
            'enseignants' => function ($q) use ($annee) {
                $q->where('esbtp_enseignant_matiere.annee_universitaire_id', $annee->id)
                  ->where('esbtp_enseignant_matiere.is_active', true);
            }
        ])->where('is_active', true);

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

        // Formater les données pour le LMS
        $data = $matieres->map(function ($matiere) use ($annee) {
            return [
                'id' => $matiere->id,
                'nom' => $matiere->nom,
                'code' => $matiere->code,
                'description' => $matiere->description,
                'coefficient' => $matiere->coefficient,
                'couleur' => $matiere->couleur,
                'heures' => [
                    'cm' => $matiere->heures_cm,
                    'td' => $matiere->heures_td,
                    'tp' => $matiere->heures_tp,
                    'total' => $matiere->heures_cm + $matiere->heures_td + $matiere->heures_tp
                ],
                'filiere' => $matiere->filiere ? [
                    'id' => $matiere->filiere->id,
                    'nom' => $matiere->filiere->nom,
                    'code' => $matiere->filiere->code
                ] : null,
                'niveau_etude' => $matiere->niveauEtude ? [
                    'id' => $matiere->niveauEtude->id,
                    'nom' => $matiere->niveauEtude->nom,
                    'code' => $matiere->niveauEtude->code
                ] : null,
                'enseignants' => $matiere->enseignants->map(function ($enseignant) {
                    return [
                        'id' => $enseignant->id,
                        'nom' => $enseignant->name,
                        'email' => $enseignant->email
                    ];
                }),
                'classes' => $matiere->classes->map(function ($classe) {
                    return [
                        'id' => $classe->id,
                        'nom' => $classe->nom,
                        'nb_etudiants' => $classe->inscriptions()
                            ->where('annee_universitaire_id', $this->getAnneeCouraante()->id)
                            ->where('status', 'active')
                            ->count()
                    ];
                }),
                'lms_metadata' => [
                    'has_online_courses' => false, // À définir selon la logique LMS
                    'last_course_date' => null,    // À définir selon les données LMS
                    'total_evaluations' => $matiere->evaluations()
                        ->where('annee_universitaire_id', $annee->id)->count()
                ]
            ];
        });

        return $this->successResponse($data, 'Matières récupérées avec succès', [
            'total' => $data->count(),
            'filters_applied' => [
                'annee_universitaire' => $annee->nom,
                'role_filter' => auth()->user()->getRoleNames()->first()
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
        $annee = $this->getAnneeCouraante();

        if (!$annee) {
            return $this->errorResponse('Aucune année universitaire courante trouvée');
        }

        $query = ESBTPClasse::with([
            'filiere',
            'niveau',
            'matieres' => function ($q) {
                $q->where('is_active', true);
            },
            'etudiants' => function ($q) use ($annee) {
                $q->whereHas('inscriptions', function ($inscriptionQuery) use ($annee) {
                    $inscriptionQuery->where('annee_universitaire_id', $annee->id)
                                   ->where('status', 'active');
                });
            }
        ])->where('is_active', true);

        // Filtres optionnels
        if ($request->has('filiere_id')) {
            $query->where('filiere_id', $request->filiere_id);
        }

        if ($request->has('niveau_id')) {
            $query->where('niveau_id', $request->niveau_id);
        }

        $classes = $query->get();

        $data = $classes->map(function ($classe) use ($annee) {
            $nbEtudiants = $classe->inscriptions()
                ->where('annee_universitaire_id', $annee->id)
                ->where('status', 'active')
                ->count();

            return [
                'id' => $classe->id,
                'nom' => $classe->nom,
                'code' => $classe->code,
                'description' => $classe->description,
                'filiere' => $classe->filiere ? [
                    'id' => $classe->filiere->id,
                    'nom' => $classe->filiere->nom,
                    'code' => $classe->filiere->code
                ] : null,
                'niveau' => $classe->niveau ? [
                    'id' => $classe->niveau->id,
                    'nom' => $classe->niveau->nom,
                    'code' => $classe->niveau->code
                ] : null,
                'statistiques' => [
                    'nb_etudiants' => $nbEtudiants,
                    'nb_matieres' => $classe->matieres->count()
                ],
                'matieres' => $classe->matieres->map(function ($matiere) {
                    return [
                        'id' => $matiere->id,
                        'nom' => $matiere->nom,
                        'code' => $matiere->code,
                        'coefficient' => $matiere->getCoefficientForClasse($classe->id)
                    ];
                })
            ];
        });

        return $this->successResponse($data, 'Classes récupérées avec succès', [
            'total' => $data->count(),
            'annee_universitaire' => $annee->nom
        ]);
    }

    /**
     * Liste des étudiants d'une classe
     *
     * Endpoint: GET /api/lms/classes/{classeId}/etudiants
     *
     * @param Request $request
     * @param int $classeId
     * @return JsonResponse
     */
    public function etudiantsClasse(Request $request, int $classeId): JsonResponse
    {
        // Vérifier les permissions
        $roleCheck = $this->checkRoleAccess(['enseignant', 'coordinateur']);
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
                  ->where('esbtp_enseignant_matiere.annee_universitaire_id', $annee->id);
            })->whereHas('classes', function ($q) use ($classeId) {
                $q->where('esbtp_classe.id', $classeId);
            })->exists();

            if (!$hasAccess) {
                return $this->errorResponse('Accès non autorisé à cette classe', [], 403);
            }
        }

        $etudiants = ESBTPEtudiant::whereHas('inscriptions', function ($q) use ($classeId, $annee) {
            $q->where('classe_id', $classeId)
              ->where('annee_universitaire_id', $annee->id)
              ->where('status', 'active');
        })->with(['user', 'inscriptions' => function ($q) use ($annee) {
            $q->where('annee_universitaire_id', $annee->id);
        }])->get();

        $data = $etudiants->map(function ($etudiant) {
            $inscription = $etudiant->inscriptions->first();

            return [
                'id' => $etudiant->id,
                'matricule' => $etudiant->matricule,
                'nom_complet' => $etudiant->user->name ?? 'N/A',
                'email' => $etudiant->user->email ?? null,
                'telephone' => $etudiant->telephone,
                'date_naissance' => $etudiant->date_naissance,
                'sexe' => $etudiant->sexe,
                'inscription' => $inscription ? [
                    'id' => $inscription->id,
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
                'nom' => $classe->nom
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
                ->where('is_active', true);
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
        $filieres = ESBTPFiliere::where('is_active', true)
            ->with(['niveauxEtudes' => function ($q) {
                $q->where('is_active', true);
            }])
            ->get();

        $niveaux = ESBTPNiveauEtude::where('is_active', true)->get();

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
}