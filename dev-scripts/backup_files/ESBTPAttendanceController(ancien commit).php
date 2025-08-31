<?php

namespace App\Http\Controllers;

use App\Models\ESBTPAttendance;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPAcademicYear;
use App\Notifications\AbsenceNotification;
use App\Services\MatiereService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;
use App\Notifications\AbsenceJustificationNotification;
use App\Notifications\ESBTPNotification;

class ESBTPAttendanceController extends Controller
{
    protected $matiereService;

    public function __construct(MatiereService $matiereService)
    {
        $this->matiereService = $matiereService;
    }

    /**
     * Affiche la liste des présences.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Get active classes for the filter dropdown
        $classes = ESBTPClasse::where('is_active', true)->orderBy('name')->get();

        // Get all subjects for the filter dropdown
        $matieres = ESBTPMatiere::orderBy('name')->get();

        // Build the base query with necessary relationships
        $query = ESBTPAttendance::with([
            'etudiant.user',
            'seanceCours.matiere',
            'seanceCours.emploiTemps.classe'
        ]);

        // Apply filters
        if ($request->filled('classe_id')) {
            $query->whereHas('seanceCours.emploiTemps', function ($q) use ($request) {
                $q->where('classe_id', $request->classe_id);
            });
        }

        if ($request->filled('matiere_id')) {
            $query->whereHas('seanceCours', function ($q) use ($request) {
                $q->where('matiere_id', $request->matiere_id);
            });
        }

        if ($request->filled('date_debut')) {
            $query->whereDate('date', '>=', $request->date_debut);
        }

        if ($request->filled('date_fin')) {
            $query->whereDate('date', '<=', $request->date_fin);
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        // Get student filter
        if ($request->filled('etudiant_id')) {
            $query->where('etudiant_id', $request->etudiant_id);
        }

        // Create a copy of the query for statistics BEFORE pagination
        $statsQuery = clone $query;

        // Get paginated results
        $attendances = $query->latest('date')->paginate(15);

        // Calculate total statistics
        $statsTotal = ESBTPAttendance::count();

        // Calculate statistics for each status using the unpaginated query
        $stats = [
            'present' => (clone $statsQuery)->where('statut', 'present')->count(),
            'absent' => (clone $statsQuery)->where('statut', 'absent')->count(),
            'retard' => (clone $statsQuery)->where('statut', 'retard')->count(),
            'excuse' => (clone $statsQuery)->where('statut', 'excuse')->count()
        ];

        // Calculate total for filtered data
        $filteredTotal = $stats['present'] + $stats['absent'] + $stats['retard'] + $stats['excuse'];

        // Calculate percentages for each status
        $statsPresentPercent = $filteredTotal > 0 ? round(($stats['present'] / $filteredTotal) * 100) : 0;
        $statsAbsentPercent = $filteredTotal > 0 ? round(($stats['absent'] / $filteredTotal) * 100) : 0;
        $statsRetardPercent = $filteredTotal > 0 ? round(($stats['retard'] / $filteredTotal) * 100) : 0;
        $statsExcusePercent = $filteredTotal > 0 ? round(($stats['excuse'] / $filteredTotal) * 100) : 0;

        // Get current academic year
        $anneeUniversitaire = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $anneeLabel = $anneeUniversitaire ? $anneeUniversitaire->libelle : 'Année en cours';

        // Calculate statistics by day for chart
        $statsParJour = [];
        $statsParStatus = [];

        // Get data for the last 7 days
        $dateDebut = Carbon::now()->subDays(6)->startOfDay();
        $dateFin = Carbon::now()->endOfDay();

        // Create array with all 7 days
        for($i = 0; $i < 7; $i++) {
            $date = Carbon::now()->subDays(6-$i)->format('Y-m-d');
            $statsParJour[$date] = 0;
            $statsParStatus[$date] = [
                'present' => 0,
                'absent' => 0,
                'retard' => 0,
                'excuse' => 0
            ];
        }

        // Collect attendance data for each day
        $attendancesByDay = ESBTPAttendance::whereBetween('date', [$dateDebut, $dateFin])
            ->selectRaw('DATE(date) as jour, statut, COUNT(*) as total')
            ->groupBy('jour', 'statut')
            ->get();

        // Fill in the data
        foreach($attendancesByDay as $record) {
            $jour = $record->jour;
            $statut = $record->statut;
            $total = $record->total;

            if(isset($statsParJour[$jour])) {
                $statsParJour[$jour] += $total;
            }

            if(isset($statsParStatus[$jour][$statut])) {
                $statsParStatus[$jour][$statut] = $total;
            }
        }

        // Create variables for statistics
        $statsPresent = $stats['present'];
        $statsAbsent = $stats['absent'];
        $statsRetard = $stats['retard'];
        $statsExcuse = $stats['excuse'];

        // Get statistics per student
        $statsParEtudiant = [];

        // Get class filter
        $classeId = $request->filled('classe_id') ? $request->classe_id : null;

        // Get all students for the selected class or all active students
        $etudiants = collect();
        if ($classeId) {
            $classe = ESBTPClasse::find($classeId);
            if ($classe) {
                $etudiants = $classe->etudiants()->with('user')->get();
            }
        } else {
            // Get students from attendances to avoid loading too many students
            $etudiantIds = ESBTPAttendance::distinct('etudiant_id')->pluck('etudiant_id')->toArray();
            if (!empty($etudiantIds)) {
                $etudiants = ESBTPEtudiant::whereIn('id', $etudiantIds)->with('user')->get();
            }
        }

        // Calculate statistics for each student
        foreach ($etudiants as $etudiant) {
            // Create a query specific to this student
            $etudiantQuery = (clone $statsQuery)->where('etudiant_id', $etudiant->id);

            // Count attendances by status
            $present = (clone $etudiantQuery)->where('statut', 'present')->count();
            $absent = (clone $etudiantQuery)->where('statut', 'absent')->count();
            $retard = (clone $etudiantQuery)->where('statut', 'retard')->count();
            $excuse = (clone $etudiantQuery)->where('statut', 'excuse')->count();
            $total = $present + $absent + $retard + $excuse;

            // Calculate percentages
            $presentPercent = $total > 0 ? round(($present / $total) * 100) : 0;
            $absentPercent = $total > 0 ? round(($absent / $total) * 100) : 0;
            $retardPercent = $total > 0 ? round(($retard / $total) * 100) : 0;
            $excusePercent = $total > 0 ? round(($excuse / $total) * 100) : 0;

            // Store statistics for this student
            $statsParEtudiant[$etudiant->id] = [
                'etudiant' => $etudiant,
                'present' => $present,
                'absent' => $absent,
                'retard' => $retard,
                'excuse' => $excuse,
                'total' => $total,
                'present_percent' => $presentPercent,
                'absent_percent' => $absentPercent,
                'retard_percent' => $retardPercent,
                'excuse_percent' => $excusePercent
            ];
        }

        return view('esbtp.attendances.index', compact(
            'attendances',
            'classes',
            'matieres',
            'stats',
            'statsTotal',
            'anneeLabel',
            'statsPresent',
            'statsPresentPercent',
            'statsAbsent',
            'statsAbsentPercent',
            'statsRetard',
            'statsRetardPercent',
            'statsExcuse',
            'statsExcusePercent',
            'statsParJour',
            'statsParStatus',
            'filteredTotal',
            'statsParEtudiant',
            'etudiants'
        ));
    }

    /**
     * Affiche le formulaire pour marquer les présences d'une séance.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        // Récupérer les classes pour le filtre
        $classes = ESBTPClasse::all();

        // Initialiser les variables
        $seances = collect();
        $etudiants = collect();
        $dateSeance = null;
        $messageErreur = null;
        $classeSelectionnee = false;
        $debug = []; // Tableau pour stocker les informations de débogage

        // Si une classe est sélectionnée (et que la valeur n'est pas vide)
        if ($request->filled('classe_id') && !empty($request->classe_id)) {
            $classeSelectionnee = true;
            $debug['classe_id'] = $request->classe_id;

            try {
                // Vérifier que la classe existe
                $classe = ESBTPClasse::findOrFail($request->classe_id);
                $debug['classe_trouvee'] = true;
                $debug['classe_nom'] = $classe->name;

                // Récupérer les séances de cours pour cette classe
                $seances = ESBTPSeanceCours::whereHas('emploiTemps', function($query) use ($request) {
                    $query->where('classe_id', $request->classe_id)
                          ->where('is_active', true);
                })->with(['emploiTemps', 'matiere'])->get();

                $debug['nombre_seances'] = $seances->count();

                // Ajouter des informations supplémentaires pour l'affichage
                $seances->each(function($seance) {
                    $seance->jour_nom = $seance->getNomJour();
                    $seance->date_calculee = $seance->getDateSeance() ? $seance->getDateSeance()->format('Y-m-d') : null;
                });

                // Si aucune séance n'est trouvée, afficher un message
                if ($seances->isEmpty()) {
                    $messageErreur = 'Aucune séance active n\'a été trouvée pour cette classe. Veuillez vérifier que l\'emploi du temps est actif.';
                    $debug['erreur'] = 'aucune_seance_active';
                }

                // Si une séance est sélectionnée, vérifier qu'elle appartient à la classe sélectionnée
                if ($request->filled('seance_id')) {
                    $debug['seance_id'] = $request->seance_id;
                    $seanceAppartientAClasse = $seances->contains('id', $request->seance_id);
                    $debug['seance_appartient_a_classe'] = $seanceAppartientAClasse;

                    if (!$seanceAppartientAClasse) {
                        // La séance sélectionnée n'appartient pas à la classe sélectionnée
                        // Rediriger vers la même page sans le paramètre seance_id
                        return redirect()->route('esbtp.attendances.create', ['classe_id' => $request->classe_id])
                            ->with('warning', 'La séance sélectionnée n\'appartient pas à la classe sélectionnée.');
                    }

                    // Récupérer la séance avec ses relations
                    $seance = ESBTPSeanceCours::with(['emploiTemps.classe', 'matiere'])->findOrFail($request->seance_id);
                    $debug['seance_trouvee'] = true;
                    $debug['seance_emploi_temps_existe'] = isset($seance->emploiTemps);

                    // Vérifier si l'emploi du temps existe
                    if (!$seance->emploiTemps) {
                        $messageErreur = 'L\'emploi du temps associé à cette séance n\'existe pas ou a été supprimé.';
                        $debug['erreur'] = 'emploi_temps_manquant';
                    }
                    // Vérifier si la classe existe
                    elseif (!$seance->emploiTemps->classe) {
                        $messageErreur = 'La classe associée à cet emploi du temps n\'existe pas ou a été supprimée.';
                        $debug['erreur'] = 'classe_manquante';
                    }
                    else {
                        // Récupérer les étudiants directement de la classe sélectionnée
                        // plutôt que de la classe associée à la séance
                        $etudiants = $classe->etudiants;
                        $debug['nombre_etudiants'] = $etudiants->count();
                        $debug['etudiants_ids'] = $etudiants->pluck('id')->toArray();

                        // Vérifier si la classe a des étudiants
                        if ($etudiants->isEmpty()) {
                            $messageErreur = 'Aucun étudiant n\'est inscrit dans cette classe. Veuillez d\'abord inscrire des étudiants.';
                            $debug['erreur'] = 'aucun_etudiant';
                        }

                        // Calculer la date de la séance
                        $dateCalculee = $seance->getDateSeance();
                        if ($dateCalculee) {
                            $dateSeance = $dateCalculee->format('Y-m-d');
                            $debug['date_seance'] = $dateSeance;
                        } else {
                            $messageErreur = 'Impossible de calculer la date de cette séance. Veuillez vérifier les dates de l\'emploi du temps.';
                            $debug['erreur'] = 'date_calcul_impossible';
                            $dateSeance = now()->format('Y-m-d'); // Date par défaut
                        }
                    }
                } else {
                    // Si la classe est sélectionnée mais pas de séance, récupérer quand même les étudiants
                    // pour vérifier s'il y en a dans cette classe
                    $etudiants = $classe->etudiants;
                    $debug['nombre_etudiants_classe'] = $etudiants->count();

                    if ($etudiants->isEmpty()) {
                        $messageErreur = 'Aucun étudiant n\'est inscrit dans cette classe. Veuillez d\'abord inscrire des étudiants.';
                        $debug['erreur'] = 'aucun_etudiant_dans_classe';
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Erreur lors de la récupération des données pour le marquage des présences: ' . $e->getMessage());
                $messageErreur = 'Une erreur est survenue lors de la récupération des données: ' . $e->getMessage();
                $debug['exception'] = $e->getMessage();
                $debug['exception_trace'] = $e->getTraceAsString();
            }
        } else {
            // Si aucune classe n'est sélectionnée mais qu'une séance est spécifiée,
            // rediriger vers la page sans paramètres pour éviter les incohérences
            if ($request->filled('seance_id')) {
                return redirect()->route('esbtp.attendances.create')
                    ->with('warning', 'Veuillez d\'abord sélectionner une classe.');
            }
        }

        // Enregistrer les informations de débogage dans le journal
        \Log::info('Débogage marquage présences', $debug);

        return view('esbtp.attendances.create', compact('classes', 'seances', 'etudiants', 'dateSeance', 'messageErreur', 'classeSelectionnee', 'debug'));
    }

    /**
     * Enregistre les présences des étudiants.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Valider les données
        $validatedData = $request->validate([
            'seance_cours_id' => 'required|exists:esbtp_seance_cours,id',
            'date' => 'required|date',
            'statuts' => 'required|array',
            'statuts.*' => 'required|in:present,absent,retard,excuse',
            'commentaires' => 'nullable|array',
            'commentaires.*' => 'nullable|string'
        ]);

        // Vérifier que la date correspond au jour de la séance
        $seance = ESBTPSeanceCours::findOrFail($validatedData['seance_cours_id']);
        $dateCalculee = $seance->getDateSeance() ? $seance->getDateSeance()->format('Y-m-d') : null;

        if ($dateCalculee && $dateCalculee != $validatedData['date']) {
            return back()->withInput()->withErrors([
                'date' => 'La date sélectionnée ne correspond pas au jour de la séance dans l\'emploi du temps.'
            ]);
        }

        try {
            DB::beginTransaction();

            // Initialiser un tableau pour suivre les opérations effectuées
            $summary = [
                'created' => 0,
                'updated' => 0,
                'students_processed' => count($validatedData['statuts'])
            ];

            foreach ($validatedData['statuts'] as $etudiantId => $statut) {
                // Vérifier si l'enregistrement existe déjà
                $attendance = ESBTPAttendance::where([
                    'seance_cours_id' => $validatedData['seance_cours_id'],
                    'etudiant_id' => $etudiantId,
                    'date' => $validatedData['date']
                ])->first();

                $commentaire = $validatedData['commentaires'][$etudiantId] ?? null;

                if ($attendance) {
                    // Mémoriser l'ancien statut pour vérifier s'il change en absent
                    $oldStatut = $attendance->statut;

                    // Mettre à jour l'enregistrement existant
                    $attendance->update([
                        'statut' => $statut,
                        'commentaire' => $commentaire,
                        'updated_by' => Auth::id()
                    ]);

                    // Si le statut est changé en 'absent', envoyer une notification
                    if ($statut === 'absent' && $oldStatut !== 'absent') {
                        $this->sendAbsenceNotification($etudiantId, $seance, $validatedData['date']);
                    }

                    $summary['updated']++;
                } else {
                    // Récupérer les heures de début et de fin de la séance
                    // S'assurer que les valeurs sont au format correct pour la base de données
                    $heureDebut = $seance->heure_debut ? $seance->heure_debut->format('H:i:s') : '08:00:00';
                    $heureFin = $seance->heure_fin ? $seance->heure_fin->format('H:i:s') : '10:00:00';

                    // Créer un nouvel enregistrement
                    ESBTPAttendance::create([
                        'seance_cours_id' => $validatedData['seance_cours_id'],
                        'etudiant_id' => $etudiantId,
                        'date' => $validatedData['date'],
                        'heure_debut' => $heureDebut,
                        'heure_fin' => $heureFin,
                        'statut' => $statut,
                        'commentaire' => $commentaire,
                        'created_by' => Auth::id()
                    ]);

                    // Si le statut est 'absent', envoyer une notification
                    if ($statut === 'absent') {
                        $this->sendAbsenceNotification($etudiantId, $seance, $validatedData['date']);
                    }

                    $summary['created']++;
                }
            }

            DB::commit();

            // Message de succès détaillé
            $message = 'Les présences ont été enregistrées avec succès. ';
            if ($summary['created'] > 0) {
                $message .= $summary['created'] . ' nouvelles présences créées. ';
            }
            if ($summary['updated'] > 0) {
                $message .= $summary['updated'] . ' présences existantes mises à jour.';
            }

            return redirect()->route('esbtp.attendances.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()
                ->with('error', 'Une erreur est survenue lors de l\'enregistrement des présences: ' . $e->getMessage());
        }
    }

    /**
     * Affiche les détails d'une présence.
     *
     * @param ESBTPAttendance $attendance
     * @return \Illuminate\Http\Response
     */
    public function show(ESBTPAttendance $attendance)
    {
        return view('esbtp.attendances.show', compact('attendance'));
    }

    /**
     * Affiche le formulaire pour modifier une présence.
     *
     * @param ESBTPAttendance $attendance
     * @return \Illuminate\Http\Response
     */
    public function edit(ESBTPAttendance $attendance)
    {
        return view('esbtp.attendances.edit', compact('attendance'));
    }

    /**
     * Met à jour une présence.
     *
     * @param Request $request
     * @param ESBTPAttendance $attendance
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ESBTPAttendance $attendance)
    {
        // Valider les données
        $validatedData = $request->validate([
            'statut' => 'required|in:present,absent,retard,excuse',
            'commentaire' => 'nullable|string'
        ]);

        // Mémoriser l'ancien statut pour vérifier s'il change en absent
        $oldStatut = $attendance->statut;

        // Ajouter l'identifiant de l'utilisateur qui modifie
        $validatedData['updated_by'] = Auth::id();

        // Mettre à jour l'enregistrement
        $attendance->update($validatedData);

        // Si le statut est changé en 'absent', envoyer une notification
        if ($validatedData['statut'] === 'absent' && $oldStatut !== 'absent') {
            $attendance->load(['etudiant', 'seanceCours.matiere']);
            if ($attendance->etudiant && $attendance->seanceCours) {
                $this->sendAbsenceNotification(
                    $attendance->etudiant->id,
                    $attendance->seanceCours,
                    $attendance->date
                );
            }
        }

        return redirect()->route('esbtp.attendances.index')
            ->with('success', 'La présence a été mise à jour avec succès.');
    }

    /**
     * Supprime une présence.
     *
     * @param ESBTPAttendance $attendance
     * @return \Illuminate\Http\Response
     */
    public function destroy(ESBTPAttendance $attendance)
    {
        try {
            $attendance->delete();
            return redirect()->route('esbtp.attendances.index')->with('success', 'Présence supprimée avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    /**
     * Génère un rapport de présence.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function rapport(Request $request)
    {
        // Valider les données
        $validatedData = $request->validate([
            'classe_id' => 'required|exists:esbtp_classes,id',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut'
        ]);

        // Récupérer la classe
        $classe = ESBTPClasse::findOrFail($validatedData['classe_id']);

        // Récupérer les étudiants de la classe
        $etudiants = $classe->etudiants;

        // Récupérer les séances de cours de la classe
        $seances = ESBTPSeanceCours::whereHas('emploiTemps', function($query) use ($classe) {
            $query->where('classe_id', $classe->id);
        })->get();

        // Récupérer les présences pour chaque étudiant
        $statistiques = [];

        foreach ($etudiants as $etudiant) {
            $attendances = ESBTPAttendance::where('etudiant_id', $etudiant->id)
                ->whereHas('seanceCours.emploiTemps', function($query) use ($classe) {
                    $query->where('classe_id', $classe->id);
                })
                ->whereBetween('date', [$validatedData['date_debut'], $validatedData['date_fin']])
                ->get();

            // Calculer les statistiques
            $totalSeances = $seances->count();
            $present = $attendances->where('statut', 'present')->count();
            $absent = $attendances->where('statut', 'absent')->count();
            $retard = $attendances->where('statut', 'retard')->count();
            $excuse = $attendances->where('statut', 'excuse')->count();

            $tauxPresence = $totalSeances > 0 ? round(($present / $totalSeances) * 100, 2) : 0;

            $statistiques[$etudiant->id] = [
                'etudiant' => $etudiant,
                'present' => $present,
                'absent' => $absent,
                'retard' => $retard,
                'excuse' => $excuse,
                'taux_presence' => $tauxPresence
            ];
        }

        return view('esbtp.attendances.rapport', compact('classe', 'etudiants', 'statistiques', 'validatedData'));
    }

    /**
     * Affiche le formulaire pour générer un rapport.
     *
     * @return \Illuminate\Http\Response
     */
    public function rapportForm()
    {
        $classes = ESBTPClasse::all();

        return view('esbtp.attendances.rapport-form', compact('classes'));
    }

    /**
     * Display the attendance list for authenticated student.
     */
    public function studentAttendance(Request $request)
    {
        // Debug mode
        if ($request->has('debug')) {
            return response()->json([
                'user' => auth()->user(),
                'roles' => auth()->user()->roles,
                'permissions' => auth()->user()->permissions,
                'request' => $request->all()
            ]);
        }

        // Get the authenticated student
        $etudiant = auth()->user()->etudiant;
        if (!$etudiant) {
            abort(403, 'Profil étudiant non trouvé');
        }

        // Check if student has an associated class
        if (!$etudiant->classe) {
            return view('etudiants.attendances', [
                'absences' => collect(),
                'presences' => collect(),
                'retards' => collect(),
                'excuses' => collect(),
                'matieres' => collect(),
                'absencesParMatiere' => [],
                'absencesMensuelles' => collect(),
                'error' => 'Vous n\'êtes pas encore assigné à une classe.'
            ]);
        }

        // Build the base query
        $query = ESBTPAttendance::with(['seanceCours.matiere'])
            ->where('etudiant_id', $etudiant->id);

        // Apply date filters
        if ($request->filled('date_debut')) {
            $query->whereDate('date', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('date', '<=', $request->date_fin);
        }

        // Apply matiere filter
        if ($request->filled('matiere_id')) {
            $query->whereHas('seanceCours', function ($q) use ($request) {
                $q->where('matiere_id', $request->matiere_id);
            });
        }

        // Get all attendances
        $allAttendances = $query->get();

        // Group attendances by status
        $presences = $allAttendances->where('statut', 'present');
        $absences = $allAttendances->where('statut', 'absent');
        $retards = $allAttendances->where('statut', 'retard');
        $excuses = $allAttendances->where('statut', 'excuse');

        // Calculate absences by month
        $absencesMensuelles = $absences->groupBy(function($absence) {
            return $absence->date->format('Y-m');
        })->map->count();

        // Get list of subjects for filtering using the service
        $matieres = $this->matiereService->getMatieresForSelect($etudiant);

        // Extract all unique subject IDs from the attendances
        $matiereIds = $allAttendances->map(function ($attendance) {
            // Vérifier que seanceCours et matiere_id existent pour éviter des erreurs
            return $attendance->seanceCours->matiere_id ?? null;
        })->filter()->unique()->values()->toArray();

        // Get all subjects related to the attendances
        $matieresFromAttendances = ESBTPMatiere::whereIn('id', $matiereIds)->get();

        // Create a dictionary of all subjects (from both service and attendances)
        $allMatieres = [];
        foreach ($matieres as $id => $name) {
            if ($id !== 'all') { // Ignorer l'entrée 'all' => 'Toutes les matières'
                $allMatieres[$id] = $name;
            }
        }
        foreach ($matieresFromAttendances as $matiere) {
            $allMatieres[$matiere->id] = $matiere->name;
        }

        // Calculate statistics by subject
        $absencesParMatiere = [];
        foreach ($allMatieres as $matiereId => $matiereName) {
            $matiereAttendances = $allAttendances->filter(function ($attendance) use ($matiereId) {
                return ($attendance->seanceCours->matiere_id ?? null) == $matiereId;
            });

            $total = $matiereAttendances->count();
            if ($total > 0) {
                $present = $matiereAttendances->where('statut', 'present')->count();
                $absent = $matiereAttendances->where('statut', 'absent')->count();
                $retard = $matiereAttendances->where('statut', 'retard')->count();
                $excuse = $matiereAttendances->where('statut', 'excuse')->count();

                $absencesParMatiere[$matiereId] = [
                    'nom' => $matiereName, // Conserver 'nom' pour la compatibilité
                    'name' => $matiereName,
                    'total' => $total,
                    'present' => $present,
                    'absent' => $absent,
                    'retard' => $retard,
                    'excuse' => $excuse,
                    'taux_presence' => round(($present / $total) * 100)
                ];
            }
        }

        return view('etudiants.attendances', compact(
            'absences',
            'presences',
            'retards',
            'excuses',
            'matieres',
            'absencesParMatiere',
            'absencesMensuelles'
        ));
    }

    /**
     * Permet à un étudiant de justifier une absence
     */
    public function justifyAbsence(Request $request, $absenceId)
    {
        // Récupérer l'absence
        $absence = ESBTPAttendance::findOrFail($absenceId);

        // Vérifier que l'absence appartient bien à l'étudiant connecté
        $etudiant = auth()->user()->etudiant;

        if (!$etudiant || $absence->etudiant_id != $etudiant->id) {
            abort(403, 'Vous n\'êtes pas autorisé à justifier cette absence');
        }

        // Vérifier si l'absence a déjà un commentaire administratif
        $hasAdminComment = false;
        if (strpos($absence->commentaire, "Commentaire de l'administration:") !== false) {
            $hasAdminComment = true;
        }

        // Vérifications pour éviter les justifications en double:
        // 1. Si l'absence a déjà une date de justification (en attente de validation) et pas de commentaire admin
        if ($absence->justified_at && !$hasAdminComment) {
            return redirect()->back()->with('warning', 'Cette absence est déjà justifiée et en attente de validation par l\'administration');
        }

        // 2. Si l'absence a déjà été validée (statut 'excuse')
        if ($absence->statut == 'excuse') {
            return redirect()->back()->with('info', 'Cette absence a déjà été justifiée et validée par l\'administration');
        }

        // Validation des données
        $request->validate([
            'justification' => 'required|string|max:500',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        // Traitement du document justificatif
        $documentPath = $absence->document_path; // Conserver le document existant par défaut
        if ($request->hasFile('document') && $request->file('document')->isValid()) {
            $documentPath = $request->file('document')->store('absences/justifications', 'public');
        }

        // Si c'est une re-soumission après rejet, ne garder que la partie commentaire de l'étudiant
        if ($hasAdminComment) {
            $parts = explode("Commentaire de l'administration:", $absence->commentaire);
            $oldStudentComment = trim($parts[0] ?? '');
            // On ajoute un préfixe pour indiquer qu'il s'agit d'une re-soumission
            $absence->commentaire = $request->justification;
        } else {
            // Mise à jour des informations mais on garde le statut comme 'absent'
            $absence->commentaire = $request->justification;
        }

        // Mettre à jour le chemin du document si un nouveau document a été soumis
        if ($documentPath) {
            $absence->document_path = $documentPath;
        }

        $absence->justified_at = now();
        $absence->save();

        // Envoyer une notification aux administrateurs
        $this->sendJustificationNotificationToAdmins($absence, $etudiant, $request->justification, $documentPath);

        if ($hasAdminComment) {
            return redirect()->back()->with('success', 'Votre justification a été re-soumise avec succès et est en attente de validation par l\'administration');
        } else {
            return redirect()->back()->with('success', 'Votre justification a été soumise avec succès et est en attente de validation par l\'administration');
        }
    }

    /**
     * Permet à un administrateur de traiter une justification d'absence
     */
    public function processJustification(Request $request, $absenceId)
    {
        // Vérifier que l'utilisateur est admin ou secrétaire
        if (!auth()->user()->hasRole(['superAdmin', 'secretaire'])) {
            abort(403, 'Vous n\'êtes pas autorisé à traiter les justifications d\'absence');
        }

        // Récupérer l'absence
        $absence = ESBTPAttendance::findOrFail($absenceId);

        // Vérifier que l'absence a bien été justifiée
        if (!$absence->justified_at) {
            return redirect()->back()->with('error', 'Cette absence n\'a pas été justifiée');
        }

        // Validation des données
        $request->validate([
            'decision' => 'required|in:approve,reject',
            'admin_comment' => 'nullable|string|max:500',
        ]);

        // Traiter la décision
        if ($request->decision === 'approve') {
            // Approuver la justification
            $absence->statut = 'excuse';
            $absence->save();

            // Notifier l'étudiant que sa justification a été approuvée
            $etudiant = ESBTPEtudiant::find($absence->etudiant_id);
            if ($etudiant && $etudiant->user) {
                $etudiant->user->notify(new ESBTPNotification(
                    'Justification d\'absence approuvée',
                    'Votre justification d\'absence pour le cours du ' . $absence->date->format('d/m/Y') . ' a été approuvée.',
                    'success',
                    ['absence_id' => $absence->id]
                ));
            }

            return redirect()->back()->with('success', 'La justification d\'absence a été approuvée');
        } else {
            // Rejeter la justification - le statut reste 'absent'
            // Ajouter un commentaire admin si fourni
            if ($request->filled('admin_comment')) {
                $absence->commentaire .= "\n\nCommentaire de l'administration: " . $request->admin_comment;
                $absence->save();
            }

            // Notifier l'étudiant que sa justification a été rejetée
            $etudiant = ESBTPEtudiant::find($absence->etudiant_id);
            if ($etudiant && $etudiant->user) {
                $etudiant->user->notify(new ESBTPNotification(
                    'Justification d\'absence rejetée',
                    'Votre justification d\'absence pour le cours du ' . $absence->date->format('d/m/Y') . ' a été rejetée.' .
                    ($request->filled('admin_comment') ? ' Commentaire: ' . $request->admin_comment : ''),
                    'danger',
                    ['absence_id' => $absence->id]
                ));
            }

            return redirect()->back()->with('info', 'La justification d\'absence a été rejetée');
        }
    }

    /**
     * Envoie une notification aux administrateurs concernant une justification d'absence
     *
     * @param ESBTPAttendance $absence
     * @param ESBTPEtudiant $etudiant
     * @param string $justification
     * @param string|null $documentPath
     * @return void
     */
    private function sendJustificationNotificationToAdmins(ESBTPAttendance $absence, ESBTPEtudiant $etudiant, string $justification, ?string $documentPath = null)
    {
        try {
            // Récupérer tous les utilisateurs avec les rôles superAdmin et secretaire
            $admins = User::whereHas('roles', function($query) {
                $query->whereIn('name', ['superAdmin', 'secretaire']);
            })->get();

            if ($admins->isEmpty()) {
                \Log::warning("Aucun administrateur trouvé pour la notification de justification d'absence", [
                    'etudiant_id' => $etudiant->id,
                    'absence_id' => $absence->id
                ]);
                return;
            }

            $notificationsCount = 0;
            // Envoyer la notification à chaque admin
            foreach ($admins as $admin) {
                try {
                    $admin->notify(new AbsenceJustificationNotification($absence, $etudiant, $justification, $documentPath));
                    $notificationsCount++;
                } catch (\Exception $e) {
                    \Log::error("Erreur lors de l'envoi de la notification à l'administrateur", [
                        'admin_id' => $admin->id,
                        'admin_email' => $admin->email,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            \Log::info("Notification de justification d'absence envoyée aux administrateurs", [
                'etudiant_id' => $etudiant->id,
                'absence_id' => $absence->id,
                'notifications_sent' => $notificationsCount,
                'total_admins' => $admins->count()
            ]);
        } catch (\Exception $e) {
            \Log::error("Erreur lors de l'envoi de la notification de justification d'absence", [
                'etudiant_id' => $etudiant->id,
                'absence_id' => $absence->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Envoie une notification d'absence à un étudiant
     *
     * @param int $etudiantId ID de l'étudiant
     * @param ESBTPSeanceCours $seanceCours Séance de cours
     * @param string $date Date de l'absence
     * @return void
     */
    private function sendAbsenceNotification($etudiantId, $seanceCours, $date)
    {
        try {
            // Charger l'étudiant avec sa relation user
            $etudiant = ESBTPEtudiant::with('user')->find($etudiantId);

            // S'assurer que l'étudiant existe et a un compte utilisateur
            if (!$etudiant || !$etudiant->user) {
                \Log::warning("Impossible d'envoyer la notification d'absence: étudiant ou utilisateur non trouvé", [
                    'etudiant_id' => $etudiantId
                ]);
                return;
            }

            // Charger la matière associée à la séance de cours
            if (!$seanceCours->matiere) {
                $seanceCours->load('matiere');
            }

            // Envoyer la notification
            $etudiant->user->notify(new \App\Notifications\AbsenceNotification($etudiant, $seanceCours, $date));

            \Log::info("Notification d'absence envoyée", [
                'etudiant_id' => $etudiantId,
                'matiere' => $seanceCours->matiere->name ?? 'N/A',
                'date' => $date
            ]);
        } catch (\Exception $e) {
            \Log::error("Erreur lors de l'envoi de la notification d'absence", [
                'etudiant_id' => $etudiantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
