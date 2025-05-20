<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiere;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\ESBTPTeacher;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ESBTPSeanceCoursController extends Controller
{
    /**
     * Affiche la liste des séances de cours.
     */
    public function index(Request $request)
    {
        try {
            // Récupérer les filtres de la requête
            $emploiTempsId = $request->input('emploi_temps_id');
            $jourSemaine = $request->input('jour_semaine');
            $typeSeance = $request->input('type_seance');
            $enseignantNom = $request->input('enseignant');

            // Construire la requête de base
            $query = ESBTPSeanceCours::with(['emploiTemps.classe', 'matiere']);

            // Appliquer les filtres si présents
            if ($emploiTempsId) {
                $query->where('emploi_temps_id', $emploiTempsId);
            }

            if ($jourSemaine) {
                $query->where('jour', $jourSemaine);
            }

            if ($typeSeance) {
                $query->where('type_seance', $typeSeance);
            }

            if ($enseignantNom) {
                $query->where('enseignant', $enseignantNom);
            }

            // Récupérer les séances de cours paginées
            $seancesCours = $query->orderBy('jour')->orderBy('heure_debut')->paginate(25);

            // Récupérer tous les emplois du temps pour le filtre
            $emploisTemps = ESBTPEmploiTemps::with('classe')->orderBy('created_at', 'desc')->get();

            // Récupérer tous les enseignants pour le filtre
            $enseignants = User::role('enseignant')->where('is_active', true)->orderBy('name')->get();

            // Calculer les statistiques par type de séance
            $statsCours = [
                'cours' => ESBTPSeanceCours::where('type_seance', 'cours')->count(),
                'td' => ESBTPSeanceCours::where('type_seance', 'td')->count(),
                'tp' => ESBTPSeanceCours::where('type_seance', 'tp')->count(),
                'examen' => ESBTPSeanceCours::where('type_seance', 'examen')->count(),
                'autre' => ESBTPSeanceCours::whereNotIn('type_seance', ['cours', 'td', 'tp', 'examen'])->count(),
            ];

            // Calculer les statistiques par jour
            $statsJours = ESBTPSeanceCours::select('jour', DB::raw('count(*) as total'))
                ->groupBy('jour')
                ->pluck('total', 'jour')
                ->toArray();

            // Détecter les conflits potentiels
            $conflits = $this->detecterConflitsHoraire();

            return view('esbtp.seances-cours.index', compact(
                'seancesCours',
                'emploisTemps',
                'enseignants',
                'statsCours',
                'statsJours',
                'conflits'
            ));
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'affichage des séances de cours: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            return back()->with('error', 'Une erreur est survenue lors du chargement des séances de cours: ' . $e->getMessage());
        }
    }

    /**
     * Détecte les conflits d'horaire entre les séances de cours.
     *
     * @return array Liste des conflits détectés
     */
    private function detecterConflitsHoraire()
    {
        $conflits = [];

        // Récupérer toutes les séances actives
        $seances = ESBTPSeanceCours::with(['emploiTemps.classe', 'matiere'])
            ->where('is_active', true)
            ->get();

        // Vérifier les conflits pour chaque séance
        foreach ($seances as $seance) {
            // Vérifier les conflits avec les autres séances
            foreach ($seances as $autreSeance) {
                // Ne pas comparer une séance avec elle-même
                if ($seance->id == $autreSeance->id) {
                    continue;
                }

                // Vérifier si les séances sont le même jour et se chevauchent
                if ($seance->jour == $autreSeance->jour &&
                    $seance->heure_debut < $autreSeance->heure_fin &&
                    $seance->heure_fin > $autreSeance->heure_debut) {

                    // Vérifier les conflits d'enseignant
                    if ($seance->enseignant == $autreSeance->enseignant) {
                        $conflits[] = [
                            'type' => 'Enseignant',
                            'nom' => $seance->enseignant,
                            'jour' => $seance->jour,
                            'heure_debut' => $seance->heure_debut,
                            'heure_fin' => $seance->heure_fin,
                            'seance_id' => $seance->id
                        ];
                    }

                    // Vérifier les conflits de salle
                    if ($seance->salle == $autreSeance->salle) {
                        $conflits[] = [
                            'type' => 'Salle',
                            'nom' => $seance->salle,
                            'jour' => $seance->jour,
                            'heure_debut' => $seance->heure_debut,
                            'heure_fin' => $seance->heure_fin,
                            'seance_id' => $seance->id
                        ];
                    }

                    // Vérifier les conflits de classe
                    if ($seance->emploiTemps && $autreSeance->emploiTemps &&
                        $seance->emploiTemps->classe_id == $autreSeance->emploiTemps->classe_id) {
                        $conflits[] = [
                            'type' => 'Classe',
                            'nom' => $seance->emploiTemps->classe->name,
                            'jour' => $seance->jour,
                            'heure_debut' => $seance->heure_debut,
                            'heure_fin' => $seance->heure_fin,
                            'seance_id' => $seance->id
                        ];
                    }
                }
            }
        }

        // Éliminer les doublons
        $conflitsUniques = [];
        foreach ($conflits as $conflit) {
            $key = $conflit['type'] . '-' . $conflit['nom'] . '-' . $conflit['jour'] . '-' . $conflit['heure_debut'] . '-' . $conflit['heure_fin'];
            $conflitsUniques[$key] = $conflit;
        }

        return array_values($conflitsUniques);
    }

    /**
     * Affiche le formulaire de création d'une nouvelle séance de cours.
     */
    public function create(Request $request)
    {
        try {
            // Validate required parameters
            $request->validate([
                'emploi_temps_id' => 'required|exists:esbtp_emploi_temps,id',
                'jour' => 'required|integer|min:1|max:7',
                'heure_debut' => 'required|date_format:H:i',
            ]);

        // Récupérer l'emploi du temps
            $emploiTemps = ESBTPEmploiTemps::with('classe.filiere', 'classe.niveau')
                ->findOrFail($request->emploi_temps_id);

            // Récupérer les enseignants actifs
        $teachers = ESBTPTeacher::with('user')
                ->where('is_active', true)
            ->get()
                ->sortBy('user.name');

        // Récupérer les matières
            $matieres = ESBTPMatiere::where('is_active', true)
                ->orderBy('name')
                ->get();

            // Définir les types de séances disponibles
            $sessionTypes = [
                ESBTPSeanceCours::TYPE_COURSE => 'Cours',
                ESBTPSeanceCours::TYPE_HOMEWORK => 'Devoir',
                ESBTPSeanceCours::TYPE_BREAK => 'Récréation',
                ESBTPSeanceCours::TYPE_LUNCH => 'Pause déjeuner'
            ];

        // Définir les jours de la semaine
        $joursSemaine = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche'
        ];

            // Get default colors
            $defaultColors = ESBTPSeanceCours::DEFAULT_COLORS;

            return view('esbtp.seances-cours.create', compact(
                'emploiTemps',
                'teachers',
                'matieres',
                'sessionTypes',
                'joursSemaine',
                'defaultColors',
                'request'
            ));
        } catch (\Exception $e) {
            Log::error('Error in SeanceCoursController@create: ' . $e->getMessage());
            return back()->with('error', 'Une erreur est survenue lors du chargement du formulaire.');
        }
    }

    /**
     * Enregistre une nouvelle séance de cours.
     */
    public function store(Request $request)
    {
        try {
            // Get the emploi du temps and its associated classe_id and annee_universitaire_id
            $emploiTemps = ESBTPEmploiTemps::findOrFail($request->emploi_temps_id);
            if (!$emploiTemps->classe_id) {
                throw new \Exception('Aucune classe n\'est associée à cet emploi du temps.');
            }
            if (!$emploiTemps->annee_universitaire_id) {
                throw new \Exception('Aucune année universitaire n\'est associée à cet emploi du temps.');
            }

            // Log des données reçues
            \Log::info('Création séance - Données reçues', $request->all());

            // Validate the basic required fields first
            $baseValidator = Validator::make($request->all(), [
            'emploi_temps_id' => 'required|exists:esbtp_emploi_temps,id',
                'type' => 'required|in:course,homework,break,lunch',
            'jour' => 'required|integer|min:1|max:7',
            'heure_debut' => 'required|date_format:H:i',
                'heure_fin' => 'required|date_format:H:i|after:heure_debut',
            ]);

            // Champs additionnels selon le type
            $additionalRules = [];
            $additionalMessages = [];
            $optionalRules = [];

            if ($request->type === 'course' || $request->type === 'homework') {
                $additionalRules['matiere_id'] = 'required|exists:esbtp_matieres,id';
                $optionalRules['teacher_id'] = 'nullable|exists:esbtp_teachers,id';
                $optionalRules['salle'] = 'nullable|string|max:50';
            }
            if ($request->type === 'homework') {
                $optionalRules['homework_description'] = 'nullable|string|max:255';
                $optionalRules['homework_due_date'] = 'nullable|date';
            }
            // Pour break/lunch, on force matiere_id, salle, teacher_id à null
            if ($request->type === 'break' || $request->type === 'lunch') {
                $request->merge([
                    'matiere_id' => null,
                    'teacher_id' => null,
                    'salle' => null,
                    'homework_description' => null,
                    'homework_due_date' => null,
                ]);
            }

            $allRules = array_merge($baseValidator->getRules(), $additionalRules, $optionalRules);
            $allMessages = array_merge($baseValidator->getMessageBag()->getMessages(), $additionalMessages);

            // Validate all rules
            $validator = Validator::make($request->all(), $allRules, $allMessages);

            if ($validator->fails()) {
                \Log::warning('Erreur validation création séance', $validator->errors()->toArray());
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            // Start transaction
            DB::beginTransaction();

            try {
                // Check for scheduling conflicts (uniquement pour le jour principal, on peut améliorer pour tous les jours si besoin)
                $conflicts = $this->checkSchedulingConflicts($request);
                if (!empty($conflicts)) {
                    \Log::warning('Conflit horaire lors de la création de séance', $conflicts);
                    throw ValidationException::withMessages([
                        'conflicts' => $conflicts
                    ]);
                }

                // Prepare data for creation
                $data = $validator->validated();
                $data['classe_id'] = $emploiTemps->classe_id;
                $data['annee_universitaire_id'] = $emploiTemps->annee_universitaire_id;

                // Calcul automatique de la date de séance
                // On suppose que jour = 1 (Lundi) à 7 (Dimanche)
                $dateDebut = $emploiTemps->date_debut instanceof \Carbon\Carbon ? $emploiTemps->date_debut : \Carbon\Carbon::parse($emploiTemps->date_debut);
                $data['date_seance'] = $dateDebut->copy()->addDays($data['jour'] - 1);

                // Couleur dynamique selon le type
                if (empty($data['color'])) {
                    $data['color'] = \App\Models\ESBTPSeanceCours::DEFAULT_COLORS[$data['type']] ?? '#000000';
                }

                // Log des données à enregistrer
                \Log::info('Création séance - Données enregistrées', $data);

                // Correction : récupérer is_recurring et recurrence_days depuis $request
                $isRecurring = $request->has('is_recurring');
                $recurrenceDays = $request->input('recurrence_days', []);
                if (is_string($recurrenceDays)) {
                    $recurrenceDays = explode(',', $recurrenceDays);
                }
                \Log::info('DEBUG - is_recurring', [
                    'is_recurring' => $isRecurring,
                    'recurrence_days' => $recurrenceDays,
                    'data' => $data
                ]);

                $createdSessions = [];
                if ($isRecurring && !empty($recurrenceDays) && is_array($recurrenceDays)) {
                    foreach ($recurrenceDays as $recurringDay) {
                        $dataForDay = $data;
                        $dataForDay['jour'] = $recurringDay;
                        // Calcul automatique de la date_seance pour chaque jour récurrent
                        $dataForDay['date_seance'] = $dateDebut->copy()->addDays($recurringDay - 1);
                        $session = ESBTPSeanceCours::create($dataForDay);
                        $createdSessions[] = $session->id;
                        \Log::info('Séance récurrente créée', ['id' => $session->id, 'jour' => $recurringDay]);
                    }
                } else {
                    $session = ESBTPSeanceCours::create($data);
                    $createdSessions[] = $session->id;
                    \Log::info('Séance simple créée', ['id' => $session->id, 'jour' => $data['jour']]);
                }
                DB::commit();
                \Log::info('Création séances terminée', ['ids' => $createdSessions]);
            return redirect()
                ->route('esbtp.emploi-temps.show', $request->emploi_temps_id)
                    ->with('success', 'Séance(s) ajoutée(s) avec succès.');
            } catch (ValidationException $e) {
                DB::rollBack();
                \Log::error('Erreur validation transaction création séance', $e->errors());
                return redirect()->back()
                    ->withErrors($e->errors())
                    ->withInput();
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('Erreur exception création séance', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                throw $e;
            }
        } catch (\Exception $e) {
            \Log::error('Erreur globale création séance', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Une erreur est survenue lors de la création de la séance : ' . $e->getMessage());
        }
    }

    /**
     * Check for scheduling conflicts
     */
    private function checkSchedulingConflicts(Request $request)
    {
        $conflicts = [];

        $emploiTemps = ESBTPEmploiTemps::findOrFail($request->emploi_temps_id);
        $dateDebut = $emploiTemps->date_debut instanceof \Carbon\Carbon ? $emploiTemps->date_debut : \Carbon\Carbon::parse($emploiTemps->date_debut);
        $dateSeance = $dateDebut->copy()->addDays($request->jour - 1);

        // Conflit enseignant sur tous les emplois du temps actifs
        if (in_array($request->type, ['course', 'homework']) && $request->teacher_id) {
            $teacherConflictQuery = ESBTPSeanceCours::where('teacher_id', $request->teacher_id)
                ->where('date_seance', $dateSeance)
                ->where(function($q) use ($request) {
                    $q->where('heure_debut', '<', $request->heure_fin)
                      ->where('heure_fin', '>', $request->heure_debut);
                })
                ->whereHas('emploiTemps', function($q) {
                    $q->where('is_active', 1);
                });
            if ($teacherConflictQuery->count() > 0) {
                $teacher = ESBTPTeacher::find($request->teacher_id);
                $conflicts[] = "L'enseignant {$teacher->user->name} a déjà un cours à cet horaire sur un emploi du temps actif.";
            }
        }

        // Check room conflicts for course and homework
        if (in_array($request->type, ['course', 'homework']) && $request->salle) {
            $roomConflicts = ESBTPSeanceCours::where('salle', $request->salle)
                ->where('date_seance', $dateSeance)
                ->where('heure_debut', '<', $request->heure_fin)
                ->where('heure_fin', '>', $request->heure_debut)
                ->count();

            if ($roomConflicts > 0) {
                $conflicts[] = "La salle {$request->salle} est déjà occupée à cet horaire";
            }
        }

        // Check class conflicts
        $classConflicts = ESBTPSeanceCours::where('classe_id', $emploiTemps->classe_id)
            ->where('date_seance', $dateSeance)
            ->where(function($q) use ($request) {
                $q->where(function($q) use ($request) {
                    $q->where('heure_debut', '<', $request->heure_fin)
                      ->where('heure_fin', '>', $request->heure_debut);
                });
            })
            ->count();
        if ($classConflicts > 0) {
            $conflicts[] = "La classe a déjà une séance programmée à cet horaire";
        }

        return $conflicts;
    }

    /**
     * Afficher le formulaire de modification d'une séance de cours.
     */
    public function edit(ESBTPSeanceCours $seancesCour)
    {
        try {
            // Check if the session exists
            if (!$seancesCour->exists) {
                Log::error('Session not found when trying to edit', [
                    'session_id' => $seancesCour->id,
                    'user_id' => Auth::id()
                ]);
                return back()->with('error', 'La séance de cours n\'existe pas.');
            }

            // Check authorization
            if (!Auth::user()->can('edit', $seancesCour)) {
                Log::warning('Unauthorized attempt to edit session', [
                    'session_id' => $seancesCour->id,
                    'user_id' => Auth::id()
                ]);
                return back()->with('error', 'Vous n\'êtes pas autorisé à modifier cette séance.');
            }

            // Load the emploi du temps with error handling
            $emploiTemps = $seancesCour->emploiTemps;
        if (!$emploiTemps) {
                Log::error('Associated emploi du temps not found', [
                    'session_id' => $seancesCour->id,
                    'user_id' => Auth::id()
                ]);
                return back()->with('error', 'L\'emploi du temps associé est introuvable.');
                    }

            // Load required data with error handling
            try {
                $teachers = ESBTPTeacher::with('user')
                    ->where('is_active', true)
                    ->get()
                    ->sortBy('user.name');

                $matieres = ESBTPMatiere::where('is_active', true)
                    ->orderBy('name')
                    ->get();

                $sessionTypes = [
                    ESBTPSeanceCours::TYPE_COURSE => 'Cours',
                    ESBTPSeanceCours::TYPE_HOMEWORK => 'Devoir',
                    ESBTPSeanceCours::TYPE_BREAK => 'Récréation',
                    ESBTPSeanceCours::TYPE_LUNCH => 'Pause déjeuner'
                ];

            $joursSemaine = [
                1 => 'Lundi',
                2 => 'Mardi',
                3 => 'Mercredi',
                4 => 'Jeudi',
                5 => 'Vendredi',
                6 => 'Samedi',
                7 => 'Dimanche'
            ];

                $defaultColors = ESBTPSeanceCours::DEFAULT_COLORS;

            } catch (\Exception $e) {
                Log::error('Error loading required data for edit form', [
                    'session_id' => $seancesCour->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return back()->with('error', 'Erreur lors du chargement des données du formulaire.');
            }

            return view('esbtp.seances-cours.edit', compact(
                'seancesCour',
                'emploiTemps',
                'teachers',
                'matieres',
                'sessionTypes',
                'joursSemaine',
                'defaultColors'
            ));

        } catch (\Exception $e) {
            Log::error('Error in SeanceCoursController@edit', [
                'session_id' => $seancesCour->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);
            return back()->with('error', 'Une erreur est survenue lors du chargement du formulaire de modification: ' . $e->getMessage());
        }
    }

    /**
     * Mettre à jour une séance de cours existante.
     */
    public function update(Request $request, ESBTPSeanceCours $seancesCour)
    {
        try {
            // Base validation rules
            $rules = [
                'type' => 'required|in:' . implode(',', [
                    ESBTPSeanceCours::TYPE_COURSE,
                    ESBTPSeanceCours::TYPE_HOMEWORK,
                    ESBTPSeanceCours::TYPE_BREAK,
                    ESBTPSeanceCours::TYPE_LUNCH
                ]),
                'jour' => 'required|integer|min:1|max:7',
                'heure_debut' => 'required|date_format:H:i',
                'heure_fin' => 'required|date_format:H:i|after:heure_debut',
                'color' => 'nullable|string',
                'is_recurring' => 'boolean',
                'recurrence_days' => 'nullable|array',
                'recurrence_days.*' => 'integer|min:1|max:7',
                'priority' => 'integer'
            ];

            // Add conditional validation rules based on session type
            if (in_array($request->type, [ESBTPSeanceCours::TYPE_COURSE, ESBTPSeanceCours::TYPE_HOMEWORK])) {
                $rules = array_merge($rules, [
                    'teacher_id' => 'required|exists:esbtp_teachers,id',
                    'matiere_id' => 'required|exists:esbtp_matieres,id',
                    'salle' => 'required|string|max:50',
                ]);

                if ($request->type === ESBTPSeanceCours::TYPE_HOMEWORK) {
                    $rules = array_merge($rules, [
                        'homework_description' => 'required|string',
                        'homework_due_date' => 'required|date|after:today',
                    ]);
                }
            }

            // Validate the request
            $validated = $request->validate($rules);

            // Update the session
            $seancesCour->update($validated);

            return redirect()
                ->route('esbtp.emploi-temps.show', $seancesCour->emploi_temps_id)
                ->with('success', 'Séance mise à jour avec succès.');
        } catch (\Exception $e) {
            Log::error('Error in SeanceCoursController@update: ' . $e->getMessage());
            return back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de la mise à jour de la séance.');
        }
    }

    /**
     * Supprimer une séance de cours.
     */
    public function destroy(ESBTPSeanceCours $seancesCour)
    {
        try {
            $emploiTempsId = $seancesCour->emploi_temps_id;
            $seancesCour->delete();

            return redirect()
                ->route('esbtp.emploi-temps.show', $emploiTempsId)
                ->with('success', 'Séance supprimée avec succès.');
        } catch (\Exception $e) {
            Log::error('Error in SeanceCoursController@destroy: ' . $e->getMessage());
            return back()->with('error', 'Une erreur est survenue lors de la suppression de la séance.');
        }
    }

    /**
     * Vérifie s'il y a des conflits d'horaire pour une séance donnée.
     *
     * @param ESBTPSeanceCours $seanceCours
     * @return array Liste des conflits détectés
     */
    private function verifierConflitsHoraire(ESBTPSeanceCours $seanceCours)
    {
        $conflits = [];
        $emploiTemps = ESBTPEmploiTemps::findOrFail($seanceCours->emploi_temps_id);
        $classe = ESBTPClasse::findOrFail($emploiTemps->classe_id);

        // Requête pour trouver les séances qui se chevauchent le même jour
        $query = ESBTPSeanceCours::where('jour', $seanceCours->jour)
            ->where(function($q) use ($seanceCours) {
                // Chevauchement d'horaires
                $q->where(function($q1) use ($seanceCours) {
                    $q1->where('heure_debut', '<', $seanceCours->heure_fin)
                       ->where('heure_fin', '>', $seanceCours->heure_debut);
                });
            });

        // Exclure la séance actuelle pour les mises à jour
        if ($seanceCours->exists) {
            $query->where('id', '!=', $seanceCours->id);
        }

        // Vérifier les conflits avec la même classe
        $conflitsClasse = (clone $query)
            ->whereHas('emploiTemps', function($q) use ($classe) {
                $q->where('classe_id', $classe->id);
            })
            ->get();

        if ($conflitsClasse->count() > 0) {
            $conflits[] = "La classe {$classe->name} a déjà cours à cet horaire";
        }

        // Vérifier les conflits avec le même enseignant
        $conflitsEnseignant = (clone $query)
            ->where('enseignant', $seanceCours->enseignant)
            ->get();

        if ($conflitsEnseignant->count() > 0) {
            $conflits[] = "L'enseignant {$seanceCours->enseignant} a déjà cours à cet horaire";
        }

        // Vérifier les conflits avec la même salle
        $conflitsSalle = (clone $query)
            ->where('salle', $seanceCours->salle)
            ->get();

        if ($conflitsSalle->count() > 0) {
            $conflits[] = "La salle {$seanceCours->salle} est déjà occupée à cet horaire";
        }

        return $conflits;
    }
}
