<?php

namespace App\Http\Controllers;

use App\Models\ESBTPClasse;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPTeacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            Log::error('Erreur lors de l\'affichage des séances de cours: '.$e->getMessage());
            Log::error('Trace: '.$e->getTraceAsString());

            return back()->with('error', 'Une erreur est survenue lors du chargement des séances de cours: '.$e->getMessage());
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
                            'seance_id' => $seance->id,
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
                            'seance_id' => $seance->id,
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
                            'seance_id' => $seance->id,
                        ];
                    }
                }
            }
        }

        // Éliminer les doublons
        $conflitsUniques = [];
        foreach ($conflits as $conflit) {
            $key = $conflit['type'].'-'.$conflit['nom'].'-'.$conflit['jour'].'-'.$conflit['heure_debut'].'-'.$conflit['heure_fin'];
            $conflitsUniques[$key] = $conflit;
        }

        return array_values($conflitsUniques);
    }

    /**
     * Affiche les détails d'une séance de cours.
     */
    public function show(ESBTPSeanceCours $seancesCour)
    {
        try {
            // Load relationships
            $seancesCour->load(['emploiTemps.classe', 'matiere', 'teacher.user', 'sessionReport']);

            return view('esbtp.seances-cours.show', compact('seancesCour'));
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'affichage de la séance de cours: '.$e->getMessage());

            return back()->with('error', 'Une erreur est survenue lors du chargement de la séance de cours.');
        }
    }

    /**
     * Affiche le formulaire de création d'une nouvelle séance de cours.
     */
    public function create(Request $request)
    {
        try {
            // Validate required parameters - jour et heure_debut sont optionnels
            $request->validate([
                'emploi_temps_id' => 'required|exists:esbtp_emploi_temps,id',
                'jour' => 'nullable|integer|min:1|max:7',
                'heure_debut' => 'nullable|date_format:H:i',
            ]);

            // Récupérer l'emploi du temps
            $emploiTemps = ESBTPEmploiTemps::with('classe.filiere', 'classe.niveau', 'annee')
                ->findOrFail($request->emploi_temps_id);

            // Utiliser la même logique que dans l'emploi du temps pour récupérer les données de planification
            $emploiTempsController = new ESBTPEmploiTempsController;
            $reflection = new \ReflectionClass($emploiTempsController);
            $method = $reflection->getMethod('getPlanificationDataForClasse');
            $method->setAccessible(true);

            $planificationData = $method->invoke($emploiTempsController,
                $emploiTemps->classe,
                $emploiTemps->annee,
                $emploiTemps->semestre
            );

            // Récupérer les matières configurées pour cette combinaison filière/niveau
            $matieres = $planificationData['matieres_planifiees'];

            // Récupérer les enseignants avec leurs disponibilités
            $teachers = collect();
            $availabilityData = [];

            foreach ($matieres as $matiere) {
                $enseignantsPourMatiere = $matiere['enseignants_selectables'] ?? collect();

                foreach ($enseignantsPourMatiere as $teacher) {
                    if ($teacher && ! $teachers->contains('id', $teacher->id)) {
                        $teachers->push($teacher->loadMissing(['user', 'availabilities']));
                    }
                }
            }

            // Préparer les données de disponibilité pour tous les enseignants
            $prepareAvailabilityMethod = $reflection->getMethod('prepareAvailabilityData');
            $prepareAvailabilityMethod->setAccessible(true);

            foreach ($teachers as $teacher) {
                $baseAvailability = $prepareAvailabilityMethod->invoke($emploiTempsController, $teacher);

                // Ajouter les séances existantes comme créneaux occupés
                $availabilityData[$teacher->id] = $this->addExistingSessionsToAvailability($baseAvailability, $teacher);
            }

            // Définir les types de séances disponibles
            $sessionTypes = [
                ESBTPSeanceCours::TYPE_COURSE => 'Cours',
                ESBTPSeanceCours::TYPE_HOMEWORK => 'Devoir',
                ESBTPSeanceCours::TYPE_BREAK => 'Récréation',
                ESBTPSeanceCours::TYPE_LUNCH => 'Pause déjeuner',
            ];

            // Définir les jours de la semaine
            $joursSemaine = [
                1 => 'Lundi',
                2 => 'Mardi',
                3 => 'Mercredi',
                4 => 'Jeudi',
                5 => 'Vendredi',
                6 => 'Samedi',
            ];

            // Get default colors
            $defaultColors = ESBTPSeanceCours::DEFAULT_COLORS;

            $departments = \App\Models\ESBTPDepartment::where('is_active', true)->get();

            $titres_academiques = [
                'M.' => 'Monsieur',
                'Mme' => 'Madame',
                'Mlle' => 'Mademoiselle',
                'Dr.' => 'Docteur',
                'Pr.' => 'Professeur'
            ];

            $grades_academiques = [
                'assistant' => 'Assistant',
                'maitre_assistant' => 'Maître Assistant',
                'maitre_conferences' => 'Maître de Conférences',
                'professeur' => 'Professeur'
            ];

            $types_contrat = [
                'permanent' => 'Permanent',
                'temporaire' => 'Temporaire',
                'vacataire' => 'Vacataire',
                'consultant' => 'Consultant'
            ];

            $statuts_emploi = [
                'temps_plein' => 'Temps Plein',
                'temps_partiel' => 'Temps Partiel',
                'vacations' => 'Vacations'
            ];

            return view('esbtp.seances-cours.create', compact(
                'emploiTemps',
                'teachers',
                'matieres',
                'sessionTypes',
                'joursSemaine',
                'defaultColors',
                'request',
                'planificationData',
                'availabilityData',
                'departments',
                'titres_academiques',
                'grades_academiques',
                'types_contrat',
                'statuts_emploi'
            ));
        } catch (\Exception $e) {
            Log::error('Error in SeanceCoursController@create: '.$e->getMessage());

            return back()->with('error', 'Une erreur est survenue lors du chargement du formulaire.');
        }
    }

    /**
     * Ajouter les séances existantes du professeur comme créneaux occupés
     */
    private function addExistingSessionsToAvailability($baseAvailability, $teacher, $ignoreSessionId = null)
    {
        // Récupérer toutes les séances du professeur dans des emplois du temps actifs
        $today = now()->toDateString();
        $existingSessions = ESBTPSeanceCours::where('teacher_id', $teacher->id)
            ->where('is_active', true)
            ->whereHas('emploiTemps', function ($query) use ($today) {
                $query->where('is_active', true)
                    ->whereDate('date_debut', '<=', $today)
                    ->where(function ($subQuery) use ($today) {
                        $subQuery->whereNull('date_fin')
                            ->orWhereDate('date_fin', '>=', $today);
                    });
            })
            ->get();

        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        $dayTranslations = [
            'monday' => 'monday',
            'tuesday' => 'tuesday',
            'wednesday' => 'wednesday',
            'thursday' => 'thursday',
            'friday' => 'friday',
            'saturday' => 'saturday',
            'lundi' => 'monday',
            'mardi' => 'tuesday',
            'mercredi' => 'wednesday',
            'jeudi' => 'thursday',
            'vendredi' => 'friday',
            'samedi' => 'saturday',
        ];

        foreach ($existingSessions as $session) {
            if ($ignoreSessionId && (int) $session->id === (int) $ignoreSessionId) {
                // Ne pas marquer la séance en cours de modification comme occupée
                continue;
            }

            // Mapper le jour numérique vers la clé jour
            $dayKey = null;
            if (is_numeric($session->jour)) {
                $dayKey = $days[(int) $session->jour - 1] ?? null; // jour 1=lundi -> index 0=monday
            } elseif (is_string($session->jour)) {
                $normalizedDay = strtolower(trim($session->jour));
                $dayKey = $dayTranslations[$normalizedDay] ?? null;
            }

            if (! $dayKey || ! isset($baseAvailability[$dayKey])) {
                continue;
            }

            // Parser les heures de la séance
            $startHour = $session->heure_debut instanceof \Carbon\Carbon ?
                $session->heure_debut->hour :
                (int) substr($session->heure_debut, 0, 2);
            $endHour = $session->heure_fin instanceof \Carbon\Carbon ?
                $session->heure_fin->hour :
                (int) substr($session->heure_fin, 0, 2);

            // Marquer comme occupé tous les créneaux de cette séance
            for ($hour = $startHour; $hour < $endHour; $hour++) {
                $hourIndex = $hour - 8; // 8h = index 0
                if ($hourIndex >= 0 && $hourIndex < count($baseAvailability[$dayKey])) {
                    $baseAvailability[$dayKey][$hourIndex] = 'occupied';
                }
            }
        }

        return $baseAvailability;
    }

    /**
     * Enregistre une nouvelle séance de cours.
     */
    public function store(Request $request)
    {
        try {
            $expectsJson = $request->expectsJson() || $request->boolean('embed');

            // Get the emploi du temps and its associated classe_id and annee_universitaire_id
            $emploiTemps = ESBTPEmploiTemps::findOrFail($request->emploi_temps_id);
            if (! $emploiTemps->classe_id) {
                throw new \Exception('Aucune classe n\'est associée à cet emploi du temps.');
            }
            if (! $emploiTemps->annee_universitaire_id) {
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

                if ($expectsJson) {
                    return response()->json([
                        'message' => 'Validation échouée.',
                        'errors' => $validator->errors(),
                    ], 422);
                }

                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            // Start transaction
            DB::beginTransaction();
            $createdSessions = [];
            $createdSeanceId = null;
            $creationSucceeded = false;

            try {
                // Check for scheduling conflicts (uniquement pour le jour principal, on peut améliorer pour tous les jours si besoin)
                $conflicts = $this->checkSchedulingConflicts($request);
                if (! empty($conflicts)) {
                    \Log::warning('Conflit horaire lors de la création de séance', $conflicts);
                    throw ValidationException::withMessages([
                        'conflicts' => $conflicts,
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

                if ($data['type'] === ESBTPSeanceCours::TYPE_HOMEWORK) {
                    $data['teacher_id'] = null;
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
                    'data' => $data,
                ]);

                if ($isRecurring && ! empty($recurrenceDays) && is_array($recurrenceDays)) {
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

                // Création automatique d'évaluations pour les séances de type "homework"
                if ($request->type === 'homework') {
                    $createdEvaluations = [];
                    foreach ($createdSessions as $sessionId) {
                        $seance = ESBTPSeanceCours::with('matiere')->find($sessionId);

                        // Calculer la durée en minutes
                        $heureDebut = Carbon::parse($seance->heure_debut);
                        $heureFin = Carbon::parse($seance->heure_fin);
                        $dureeMinutes = $heureFin->diffInMinutes($heureDebut);

                        // Déterminer la période selon la date
                        $periode = 'semestre1'; // Par défaut
                        $dateSeance = Carbon::parse($seance->date_seance);
                        if ($dateSeance->month >= 1 && $dateSeance->month <= 6) {
                            $periode = 'semestre2';
                        }

                        $evaluationStartAt = $this->combineDateAndTime($seance->date_seance, $seance->heure_debut);
                        $evaluationEndAt = $this->combineDateAndTime($seance->date_seance, $seance->heure_fin);
                        if ($evaluationEndAt->lessThanOrEqualTo($evaluationStartAt)) {
                            $evaluationEndAt = $evaluationEndAt->addDay();
                        }
                        $dureeMinutes = $evaluationEndAt->diffInMinutes($evaluationStartAt);

                        $evaluationData = [
                            'titre' => $seance->homework_description ?: 'Devoir - '.($seance->matiere->name ?? 'Matière'),
                            'description' => $seance->homework_description,
                            'matiere_id' => $seance->matiere_id,
                            'classe_id' => $seance->classe_id,
                            'type' => 'devoir',
                            'date_evaluation' => $evaluationStartAt,
                            'coefficient' => 1.0,
                            'bareme' => 20.00,
                            'duree_minutes' => $dureeMinutes,
                            'periode' => $periode,
                            'annee_universitaire_id' => $seance->annee_universitaire_id,
                            'status' => 'draft',
                            'is_published' => false,
                            'notes_published' => false,
                            'created_by' => Auth::id(),
                            'enseignant_id' => $seance->type === ESBTPSeanceCours::TYPE_HOMEWORK ? null : $seance->teacher_id,
                        ];

                        $evaluation = ESBTPEvaluation::create($evaluationData);
                        $createdEvaluations[] = $evaluation->id;

                        if ($seance) {
                            $seance->homework_evaluation_id = $evaluation->id;
                            $seance->save();
                        }

                        \Log::info('Évaluation créée automatiquement', [
                            'evaluation_id' => $evaluation->id,
                            'seance_id' => $sessionId,
                            'date_evaluation' => $evaluationStartAt->toDateTimeString(),
                            'titre' => $evaluation->titre,
                        ]);
                    }

                    \Log::info('Évaluations automatiques créées pour séances homework', [
                        'evaluation_ids' => $createdEvaluations,
                        'session_ids' => $createdSessions,
                    ]);
                }

                DB::commit();
                \Log::info('Création séances terminée', ['ids' => $createdSessions]);
                $createdSeanceId = ! empty($createdSessions) ? end($createdSessions) : null;
                $creationSucceeded = true;

                $successMessage = 'Séance(s) ajoutée(s) avec succès.';
                if ($request->type === 'homework') {
                    $successMessage .= ' Les évaluations correspondantes ont été créées automatiquement.';
                }

                if ($expectsJson) {
                    return response()->json([
                        'success' => true,
                        'emploi_temps_id' => $request->emploi_temps_id,
                        'seance_id' => $createdSeanceId,
                        'message' => $successMessage,
                    ]);
                }

                return redirect()
                    ->route('esbtp.emploi-temps.show', $request->emploi_temps_id)
                    ->with('success', $successMessage);
            } catch (ValidationException $e) {
                DB::rollBack();
                \Log::error('Erreur validation transaction création séance', $e->errors());

                if ($expectsJson) {
                    return response()->json([
                        'message' => 'Validation échouée.',
                        'errors' => $e->errors(),
                    ], 422);
                }

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

            if ($creationSucceeded) {
                if ($expectsJson) {
                    return response()->json([
                        'success' => true,
                        'emploi_temps_id' => $request->emploi_temps_id,
                        'seance_id' => $createdSeanceId,
                        'message' => 'Séance ajoutée avec succès.',
                    ]);
                }

                return redirect()
                    ->route('esbtp.emploi-temps.show', $request->emploi_temps_id)
                    ->with('success', 'Séance ajoutée avec succès.');
            }

            if ($expectsJson) {
                return response()->json([
                    'message' => 'Une erreur est survenue lors de la création de la séance.',
                ], 500);
            }

            return back()->with('error', 'Une erreur est survenue lors de la création de la séance : '.$e->getMessage());
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
        $today = now()->toDateString();

        // Conflit enseignant sur tous les emplois du temps actifs
        if (in_array($request->type, ['course', 'homework']) && $request->teacher_id) {
            $teacherConflictQuery = ESBTPSeanceCours::where('teacher_id', $request->teacher_id)
                ->where('date_seance', $dateSeance)
                ->where('is_active', true)
                ->where(function ($q) use ($request) {
                    $q->where('heure_debut', '<', $request->heure_fin)
                        ->where('heure_fin', '>', $request->heure_debut);
                })
                ->whereHas('emploiTemps', function ($q) use ($today) {
                    $q->where('is_active', true)
                        ->whereDate('date_debut', '<=', $today)
                        ->where(function ($subQuery) use ($today) {
                            $subQuery->whereNull('date_fin')
                                ->orWhereDate('date_fin', '>=', $today);
                        });
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
                ->where('is_active', true)
                ->where('heure_debut', '<', $request->heure_fin)
                ->where('heure_fin', '>', $request->heure_debut)
                ->whereHas('emploiTemps', function ($q) use ($today) {
                    $q->where('is_active', true)
                        ->whereDate('date_debut', '<=', $today)
                        ->where(function ($subQuery) use ($today) {
                            $subQuery->whereNull('date_fin')
                                ->orWhereDate('date_fin', '>=', $today);
                        });
                })
                ->count();

            if ($roomConflicts > 0) {
                $conflicts[] = "La salle {$request->salle} est déjà occupée à cet horaire";
            }
        }

        // Check class conflicts
        $classConflicts = ESBTPSeanceCours::where('classe_id', $emploiTemps->classe_id)
            ->where('date_seance', $dateSeance)
            ->where('is_active', true)
            ->where(function ($q) use ($request) {
                $q->where(function ($q) use ($request) {
                    $q->where('heure_debut', '<', $request->heure_fin)
                        ->where('heure_fin', '>', $request->heure_debut);
                });
            })
            ->whereHas('emploiTemps', function ($q) use ($today) {
                $q->where('is_active', true)
                    ->whereDate('date_debut', '<=', $today)
                    ->where(function ($subQuery) use ($today) {
                        $subQuery->whereNull('date_fin')
                            ->orWhereDate('date_fin', '>=', $today);
                    });
            })
            ->count();
        if ($classConflicts > 0) {
            $conflicts[] = 'La classe a déjà une séance programmée à cet horaire';
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
            if (! $seancesCour->exists) {
                Log::error('Session not found when trying to edit', [
                    'session_id' => $seancesCour->id,
                    'user_id' => Auth::id(),
                ]);

                return back()->with('error', 'La séance de cours n\'existe pas.');
            }

            // Check authorization
            if (! Auth::user()->can('edit', $seancesCour)) {
                Log::warning('Unauthorized attempt to edit session', [
                    'session_id' => $seancesCour->id,
                    'user_id' => Auth::id(),
                ]);

                return back()->with('error', 'Vous n\'êtes pas autorisé à modifier cette séance.');
            }

            // Load the emploi du temps with error handling
            $seancesCour->loadMissing([
                'matiere',
                'teacher.user',
                'teacher.availabilities',
                'emploiTemps.classe.filiere',
                'emploiTemps.classe.niveau',
                'emploiTemps.annee',
            ]);

            $emploiTemps = $seancesCour->emploiTemps;
            if (! $emploiTemps) {
                Log::error('Associated emploi du temps not found', [
                    'session_id' => $seancesCour->id,
                    'user_id' => Auth::id(),
                ]);

                return back()->with('error', 'L\'emploi du temps associé est introuvable.');
            }

            // Load required data with error handling
            try {
                $emploiTempsController = new ESBTPEmploiTempsController;
                $reflection = new \ReflectionClass($emploiTempsController);

                $planificationMethod = $reflection->getMethod('getPlanificationDataForClasse');
                $planificationMethod->setAccessible(true);

                $planificationData = $planificationMethod->invoke(
                    $emploiTempsController,
                    $emploiTemps->classe,
                    $emploiTemps->annee,
                    $emploiTemps->semestre
                );

                $planificationConfigured = $planificationData['planifications_configurees'] ?? false;
                $matieres = collect($planificationData['matieres_planifiees'] ?? []);

                $teachers = collect();
                foreach ($matieres as $matiere) {
                    $enseignantsPourMatiere = $matiere['enseignants_selectables'] ?? collect();
                    foreach ($enseignantsPourMatiere as $teacher) {
                        if ($teacher && ! $teachers->contains('id', $teacher->id)) {
                            $teachers->push($teacher->loadMissing(['user', 'availabilities']));
                        }
                    }
                }

                $sessionTeacher = $seancesCour->teacher()->with(['user', 'availabilities'])->first();
                if ($sessionTeacher && ! $teachers->contains('id', $sessionTeacher->id)) {
                    $teachers->push($sessionTeacher);
                }

                $teachers = $teachers->filter()->unique('id')->sortBy(function ($teacher) {
                    return $teacher->user->name ?? $teacher->matricule ?? '';
                })->values();

                $prepareAvailabilityMethod = $reflection->getMethod('prepareAvailabilityData');
                $prepareAvailabilityMethod->setAccessible(true);

                $availabilityData = [];
                foreach ($teachers as $teacher) {
                    $baseAvailability = $prepareAvailabilityMethod->invoke($emploiTempsController, $teacher);
                    $availabilityData[$teacher->id] = $this->addExistingSessionsToAvailability($baseAvailability, $teacher, $seancesCour->id);
                }

                // Assurer la présence de la matière actuelle même si aucune planification n'est configurée
                if ($matieres->isEmpty() && $seancesCour->matiere) {
                    $planificationConfigured = true;

                    $enseignantsSelectables = collect();
                    if ($sessionTeacher) {
                        $enseignantsSelectables->push($sessionTeacher);
                    }

                    $matieres = collect([
                        [
                            'planification_id' => null,
                            'matiere' => $seancesCour->matiere,
                            'enseignant_principal' => null,
                            'enseignants_assignes' => $enseignantsSelectables,
                            'enseignants_selectables' => $enseignantsSelectables,
                            'enseignant_affiche' => optional($sessionTeacher)->user,
                            'volume_horaire_total' => '--',
                            'heures_restantes' => '--',
                            'pourcentage_utilise' => null,
                            'volume_horaire_cm' => null,
                            'volume_horaire_td' => null,
                            'volume_horaire_tp' => null,
                            'statut' => null,
                            'periode_debut' => null,
                            'periode_fin' => null,
                        ],
                    ]);
                }

                // Forcer la disponibilité des cartes matières lorsqu'on a des données (planification ou fallback)
                $planificationData['planifications_configurees'] = $planificationConfigured || $matieres->isNotEmpty();
                $planificationData['matieres_planifiees'] = $matieres->values();

                if (empty($planificationData['heures_totales'])) {
                    $planificationData['heures_totales'] = $matieres->count() ? '--' : 0;
                }
                if (empty($planificationData['heures_restantes'])) {
                    $planificationData['heures_restantes'] = $matieres->count() ? '--' : 0;
                }

                $sessionTypes = [
                    ESBTPSeanceCours::TYPE_COURSE => 'Cours',
                    ESBTPSeanceCours::TYPE_HOMEWORK => 'Devoir',
                    ESBTPSeanceCours::TYPE_BREAK => 'Récréation',
                    ESBTPSeanceCours::TYPE_LUNCH => 'Pause déjeuner',
                ];

                $joursSemaine = [
                    1 => 'Lundi',
                    2 => 'Mardi',
                    3 => 'Mercredi',
                    4 => 'Jeudi',
                    5 => 'Vendredi',
                    6 => 'Samedi',
                ];

                $defaultColors = ESBTPSeanceCours::DEFAULT_COLORS;
            } catch (\Exception $e) {
                Log::error('Error loading required data for edit form', [
                    'session_id' => $seancesCour->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
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
                'defaultColors',
                'planificationData',
                'availabilityData'
            ));

        } catch (\Exception $e) {
            Log::error('Error in SeanceCoursController@edit', [
                'session_id' => $seancesCour->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Une erreur est survenue lors du chargement du formulaire de modification: '.$e->getMessage());
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
                'type' => 'required|in:'.implode(',', [
                    ESBTPSeanceCours::TYPE_COURSE,
                    ESBTPSeanceCours::TYPE_HOMEWORK,
                    ESBTPSeanceCours::TYPE_BREAK,
                    ESBTPSeanceCours::TYPE_LUNCH,
                ]),
                'jour' => 'required|integer|min:1|max:7',
                'heure_debut' => 'required|date_format:H:i',
                'heure_fin' => 'required|date_format:H:i|after:heure_debut',
                'color' => 'nullable|string',
                'is_recurring' => 'boolean',
                'recurrence_days' => 'nullable|array',
                'recurrence_days.*' => 'integer|min:1|max:7',
                'priority' => 'integer',
            ];

            // Add conditional validation rules based on session type
            if ($request->type === ESBTPSeanceCours::TYPE_COURSE) {
                $rules = array_merge($rules, [
                    'teacher_id' => 'required|exists:esbtp_teachers,id',
                    'matiere_id' => 'required|exists:esbtp_matieres,id',
                    'salle' => 'required|string|max:50',
                ]);
            } elseif ($request->type === ESBTPSeanceCours::TYPE_HOMEWORK) {
                $rules = array_merge($rules, [
                    'teacher_id' => 'nullable|exists:esbtp_teachers,id',
                    'matiere_id' => 'required|exists:esbtp_matieres,id',
                    'salle' => 'nullable|string|max:50',
                    'homework_description' => 'required|string',
                    'homework_due_date' => 'required|date|after:today',
                ]);
            }

            // Validate the request
            $validated = $request->validate($rules);
            if (! array_key_exists('teacher_id', $validated)) {
                $validated['teacher_id'] = null;
            }
            if (($validated['type'] ?? $seancesCour->type) === ESBTPSeanceCours::TYPE_HOMEWORK) {
                $validated['teacher_id'] = null;
            }

            // Update the session
            $seancesCour->update($validated);

            if ($seancesCour->type === ESBTPSeanceCours::TYPE_HOMEWORK) {
                $this->syncHomeworkEvaluation($seancesCour);
            }

            return redirect()
                ->route('esbtp.emploi-temps.show', $seancesCour->emploi_temps_id)
                ->with('success', 'Séance mise à jour avec succès.');
        } catch (\Exception $e) {
            Log::error('Error in SeanceCoursController@update: '.$e->getMessage());

            return back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de la mise à jour de la séance.');
        }
    }

    private function syncHomeworkEvaluation(ESBTPSeanceCours $seance): void
    {
        try {
            $seance->loadMissing(['matiere', 'classe', 'homeworkEvaluation']);
            $evaluation = $seance->homeworkEvaluation;

            if (! $evaluation && $seance->homework_evaluation_id) {
                $evaluation = ESBTPEvaluation::find($seance->homework_evaluation_id);
            }

            if (! $evaluation) {
                $potentialDate = $seance->date_seance
                    ? Carbon::parse($seance->date_seance)
                    : now();

                $evaluation = ESBTPEvaluation::where('type', 'devoir')
                    ->where('classe_id', $seance->classe_id)
                    ->where('matiere_id', $seance->matiere_id)
                    ->whereDate('date_evaluation', $potentialDate->toDateString())
                    ->orderByDesc('created_at')
                    ->first();

                if ($evaluation) {
                    $seance->homework_evaluation_id = $evaluation->id;
                    $seance->save();
                }
            }

            if (! $evaluation) {
                Log::warning('Aucune évaluation associée trouvée pour le devoir', [
                    'seance_id' => $seance->id,
                    'classe_id' => $seance->classe_id,
                    'matiere_id' => $seance->matiere_id,
                ]);

                return;
            }

            [$startAt, $endAt] = $this->getHomeworkDateTimes($seance, $evaluation);

            $dureeMinutes = max(1, $endAt->diffInMinutes($startAt));
            $periode = $this->determineEvaluationPeriod($startAt);

            $evaluation->fill([
                'titre' => $seance->homework_description ?: 'Devoir - '.($seance->matiere->name ?? 'Matière'),
                'description' => $seance->homework_description,
                'matiere_id' => $seance->matiere_id,
                'classe_id' => $seance->classe_id,
                'type' => 'devoir',
                'date_evaluation' => $startAt,
                'coefficient' => $evaluation->coefficient ?? 1.0,
                'bareme' => $evaluation->bareme ?? 20.0,
                'duree_minutes' => $dureeMinutes,
                'periode' => $periode,
                'annee_universitaire_id' => $seance->annee_universitaire_id,
                'enseignant_id' => null,
                'updated_by' => Auth::id(),
            ]);

            $evaluation->save();
        } catch (\Exception $e) {
            Log::error('Erreur lors de la synchronisation de l\'évaluation associée au devoir', [
                'seance_id' => $seance->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function getHomeworkDateTimes(ESBTPSeanceCours $seance, ESBTPEvaluation $evaluation): array
    {
        $baseDate = $seance->date_seance
            ? ($seance->date_seance instanceof Carbon ? $seance->date_seance->copy() : Carbon::parse($seance->date_seance))
            : ($evaluation->date_evaluation ? $evaluation->date_evaluation->copy() : now());

        if ($seance->heure_debut) {
            $startAt = $this->combineDateAndTime($baseDate, $seance->heure_debut);
        } elseif ($evaluation->date_evaluation) {
            $startAt = $evaluation->date_evaluation->copy();
        } else {
            $startAt = $this->combineDateAndTime($baseDate, '08:00:00');
        }

        if ($seance->heure_fin) {
            $endAt = $this->combineDateAndTime($baseDate, $seance->heure_fin);
        } elseif ($evaluation->date_evaluation && $evaluation->duree_minutes) {
            $endAt = $evaluation->date_evaluation->copy()->addMinutes($evaluation->duree_minutes);
        } else {
            $endAt = $startAt->copy()->addHour();
        }

        if ($endAt->lessThanOrEqualTo($startAt)) {
            $endAt = $startAt->copy()->addHour();
        }

        return [$startAt, $endAt];
    }

    private function combineDateAndTime($date, $time): Carbon
    {
        $dateCarbon = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);

        if (! $time) {
            return $dateCarbon;
        }

        if ($time instanceof Carbon) {
            return $dateCarbon->setTime($time->hour, $time->minute, $time->second);
        }

        $timeCarbon = Carbon::parse($time);

        return $dateCarbon->setTime($timeCarbon->hour, $timeCarbon->minute, $timeCarbon->second);
    }

    private function determineEvaluationPeriod(Carbon $date): string
    {
        // Conserver la logique originale : Janvier-Juin = semestre 2, sinon semestre 1
        return ($date->month >= 1 && $date->month <= 6) ? 'semestre2' : 'semestre1';
    }

    /**
     * Supprimer une séance de cours.
     */
    public function destroy(ESBTPSeanceCours $seancesCour)
    {
        try {
            $emploiTempsId = $seancesCour->emploi_temps_id;
            if ($seancesCour->type === ESBTPSeanceCours::TYPE_HOMEWORK && $seancesCour->homeworkEvaluation) {
                $seancesCour->homeworkEvaluation->delete();
            }
            $seancesCour->delete();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'emploi_temps_id' => $emploiTempsId,
                    'message' => 'Séance supprimée avec succès.',
                ]);
            }

            return redirect()
                ->route('esbtp.emploi-temps.show', $emploiTempsId)
                ->with('success', 'Séance supprimée avec succès.');
        } catch (\Exception $e) {
            Log::error('Error in SeanceCoursController@destroy: '.$e->getMessage());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Une erreur est survenue lors de la suppression de la séance.',
                ], 500);
            }

            return back()->with('error', 'Une erreur est survenue lors de la suppression de la séance.');
        }
    }

    /**
     * Vérifie s'il y a des conflits d'horaire pour une séance donnée.
     *
     * @return array Liste des conflits détectés
     */
    private function verifierConflitsHoraire(ESBTPSeanceCours $seanceCours)
    {
        $conflits = [];
        $emploiTemps = ESBTPEmploiTemps::findOrFail($seanceCours->emploi_temps_id);
        $classe = ESBTPClasse::findOrFail($emploiTemps->classe_id);

        // Requête pour trouver les séances qui se chevauchent le même jour
        $query = ESBTPSeanceCours::where('jour', $seanceCours->jour)
            ->where(function ($q) use ($seanceCours) {
                // Chevauchement d'horaires
                $q->where(function ($q1) use ($seanceCours) {
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
            ->whereHas('emploiTemps', function ($q) use ($classe) {
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
