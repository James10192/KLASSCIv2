<?php

namespace App\Http\Controllers;

use App\Exceptions\CoefficientMissingException;
use App\Helpers\SettingsHelper;
use App\Models\Classe;
use App\Models\ESBTPAbsence;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPConfigMatiere;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereCoefficient;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
use App\Services\ESBTP\BulletinConsistencyService;
use App\Services\ESBTP\ESBTPAbsenceService;
use Carbon\Carbon;
use App\Http\Requests\Bulletin\BulkUpdateMoyennesRequest;
use App\Http\Requests\Bulletin\GenerateClasseBulletinsRequest;
use App\Http\Requests\Bulletin\StoreBulletinRequest;
use App\Http\Requests\Bulletin\UpdateBulletinRequest;
use App\Http\Requests\Bulletin\UpdateMoyennesRequest;
use App\Http\Requests\Resultat\BulkUpdateAbsencesRequest;
use App\Http\Requests\Resultat\BulkUpdateCoefficientsRequest;
use App\Http\Requests\Resultat\BulkUpdateMatieresConfigRequest;
use App\Http\Requests\Resultat\BulkUpdateMatieresRequest;
use App\Http\Requests\Resultat\BulkUpdateProfesseursRequest;
use App\Http\Requests\Resultat\DeleteMoyenneRequest;
use App\Http\Requests\Resultat\GetAbsencesRequest;
use App\Http\Requests\Resultat\GetMoyennesRequest;
use App\Http\Requests\Resultat\ResultatsFilterRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PDF;

class ESBTPResultatController extends Controller
{
    private $absenceService;
    private $bulletinService;
    private $bulletinConsistencyService;

    public function __construct(
        \App\Services\ESBTP\ESBTPAbsenceService $absenceService,
        \App\Services\BulletinService $bulletinService,
        BulletinConsistencyService $bulletinConsistencyService
    )
    {
        $this->absenceService = $absenceService;
        $this->bulletinService = $bulletinService;
        $this->bulletinConsistencyService = $bulletinConsistencyService;
    }

    public function resultats(ResultatsFilterRequest $request)
    {
        $classe_id = $request->classe_id;
        $semestre = $request->semestre;
        $annee_universitaire_id = $request->annee_universitaire_id;
        $include_all_statuses = $request->has('include_all_statuses') ? $request->include_all_statuses : true; // Par défaut, inclure tous les statuts
        $periode = $semestre; // Map semestre to periode for view compatibility

        Log::info('Resultats method called with params', [
            'classe_id' => $classe_id,
            'semestre' => $semestre,
            'annee_universitaire_id' => $annee_universitaire_id,
            'include_all_statuses' => $include_all_statuses,
        ]);

        // If classe_id is provided, get the corresponding academic year
        if ($classe_id && ! $annee_universitaire_id) {
            $classe = ESBTPClasse::find($classe_id);
            if ($classe && $classe->annee_universitaire_id) {
                $annee_universitaire_id = $classe->annee_universitaire_id;
            }
        }

        // Get current academic year if not specified
        if (! $annee_universitaire_id) {
            // First try to find an academic year that has some notes/data
            $anneeWithData = ESBTPAnneeUniversitaire::whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('esbtp_evaluations')
                    ->whereColumn('esbtp_evaluations.annee_universitaire_id', 'esbtp_annee_universitaires.id')
                    ->whereExists(function ($subQuery) {
                        $subQuery->select(DB::raw(1))
                            ->from('esbtp_notes')
                            ->whereColumn('esbtp_notes.evaluation_id', 'esbtp_evaluations.id');
                    });
            })->orderBy('is_active', 'desc')->orderBy('annee_debut', 'desc')->first();

            if ($anneeWithData) {
                $annee_universitaire_id = $anneeWithData->id;
                Log::info('Année avec données trouvée: '.$anneeWithData->name.' (ID: '.$anneeWithData->id.')');
            } else {
                // Fallback to active academic year
                $annee_universitaire_id = ESBTPAnneeUniversitaire::where('is_active', true)->first()->id ?? null;
            }
        }

        // For view compatibility
        $annee_id = $annee_universitaire_id;

        // Get annee object for view display
        $anneeUniversitaire = ESBTPAnneeUniversitaire::find($annee_universitaire_id);

        // Always load all active classes with relationships, regardless of filters
        $classes = ESBTPClasse::with(['filiere', 'niveau'])
            ->where('is_active', true)
            ->where(fn($q) => $q->whereNull('systeme_academique')->orWhere('systeme_academique', '!=', 'LMD'))
            ->orderBy('name', 'asc')
            ->get();

        $periodes = ['' => 'Annuel', '1' => 'Semestre 1', '2' => 'Semestre 2'];
        // Récupérer toutes les années universitaires (on affiche name dans la vue)
        $annees_universitaires = ESBTPAnneeUniversitaire::orderBy('id', 'desc')->get();

        // Get selected classe information
        $classeObj = null;
        $classe = null;
        if ($classe_id) {
            $classeObj = ESBTPClasse::with('filiere')->find($classe_id);
            $classe = $classeObj; // Alias for view compatibility
        }

        // OPTIMISATION LAZY LOADING: Ne plus charger automatiquement tous les étudiants
        // Les étudiants seront chargés via AJAX par petits groupes pour optimiser les performances

        // Calculer seulement les KPIs généraux pour l'affichage initial
        $totalEtudiants = 0;
        $moyenneGenerale = null;
        $tauxReussite = null;
        $totalBulletins = 0;

        // Calculer rapidement le nombre total d'étudiants sans charger toutes les données
        if ($classe_id || $annee_universitaire_id) {
            $studentsCountQuery = $this->bulletinService->buildEtudiantsQuery($classe_id, $annee_universitaire_id, $include_all_statuses);
            $totalEtudiants = $studentsCountQuery->count();

            Log::info('KPIs calculés pour l\'affichage initial', [
                'total_etudiants' => $totalEtudiants,
                'classe_id' => $classe_id,
                'annee_universitaire_id' => $annee_universitaire_id,
            ]);
        }

        // Variables minimales pour la compatibilité de la vue
        $etudiants = collect(); // Collection vide pour la compatibilité
        $notes = collect([]); // Initialize as collection to work with isEmpty() in view
        $moyennes = []; // Empty for initial load
        $rangs = []; // Empty for initial load
        $bulletins = []; // Empty for initial load
        $attendanceNoteEnabled = $this->bulletinService->isAttendanceNoteEnabled();

        // Les moyennes et rangs sont maintenant calculés uniquement à partir de vraies données

        \Log::info('Résultats calculés - etudiants: '.count($etudiants).', moyennes: '.count($moyennes).', notes: '.count($notes));

        return view('esbtp.resultats.index', compact(
            'classes',
            'periodes',
            'annees_universitaires',
            'classe_id',
            'classeObj',
            'classe',
            'semestre',
            'periode',
            'annee_universitaire_id',
            'annee_id',
            'anneeUniversitaire',
            'etudiants',
            'notes',
            'moyennes',
            'rangs',
            'bulletins',
            'totalEtudiants',
            'include_all_statuses',
            'attendanceNoteEnabled'
        ));
    }

    /**
     * Affiche les résultats des étudiants
     *
     * @return \Illuminate\Http\Response
     */
    public function resultatsClasses(Request $request)
    {
        $annee_universitaire_id = $request->get('annee_universitaire_id');
        $filiere_id = $request->get('filiere_id');
        $niveau_id = $request->get('niveau_id');
        $statut = $request->get('statut');
        $search = $request->get('search');

        $annees_universitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();
        $filieres = ESBTPFiliere::where('is_active', true)->orderBy('name')->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->orderBy('name')->get();

        // Récupérer l'année courante (is_current = true) pour les inscriptions
        $currentAnnee = $annees_universitaires->firstWhere('is_current', true);

        // Si aucune année n'est spécifiée pour le filtre, utiliser l'année courante
        if (! $annee_universitaire_id && $currentAnnee) {
            $annee_universitaire_id = $currentAnnee->id;
        }

        $classesQuery = ESBTPClasse::with(['filiere', 'niveau', 'anneeUniversitaire'])
            ->where(fn($q) => $q->whereNull('systeme_academique')->orWhere('systeme_academique', '!=', 'LMD'))
            ->withCount(['inscriptions as actifs_count' => function ($query) use ($annee_universitaire_id) {
                $query->where('status', 'active')
                    ->where('annee_universitaire_id', $annee_universitaire_id);
            }]);

        // Filtre statut (actif par défaut)
        if ($statut === 'inactive') {
            $classesQuery->where('is_active', false);
        } else {
            $classesQuery->where('is_active', true);
        }

        if ($filiere_id) {
            $classesQuery->where('filiere_id', $filiere_id);
        }

        if ($niveau_id) {
            $classesQuery->where('niveau_etude_id', $niveau_id);
        }

        if ($search) {
            $classesQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%');
            });
        }

        $classes = $classesQuery->orderBy('name')->get();

        $totalClasses = $classes->count();
        $totalFilieres = $classes->pluck('filiere.name')->filter()->unique()->count();
        $totalNiveaux = $classes->pluck('niveau.name')->filter()->unique()->count();

        // Total des étudiants actifs pour l'année sélectionnée (toutes classes confondues)
        $totalEtudiants = $classes->sum('actifs_count');

        // L'année sélectionnée pour le filtre des inscriptions
        $selectedAnnee = $annee_universitaire_id
            ? $annees_universitaires->firstWhere('id', $annee_universitaire_id)
            : $currentAnnee;

        // Si requête AJAX, retourner JSON
        if ($request->ajax()) {
            return response()->json([
                'html' => view('esbtp.resultats.partials.classes-grid', compact('classes', 'annee_universitaire_id'))->render(),
                'kpis' => [
                    'totalClasses' => $totalClasses,
                    'totalFilieres' => $totalFilieres,
                    'totalNiveaux' => $totalNiveaux,
                    'totalEtudiants' => $totalEtudiants,
                ],
                'selectedAnnee' => $selectedAnnee,
            ]);
        }

        return view('esbtp.resultats.classes', [
            'classes' => $classes,
            'annees_universitaires' => $annees_universitaires,
            'filieres' => $filieres,
            'niveaux' => $niveaux,
            'annee_universitaire_id' => $annee_universitaire_id,
            'totalClasses' => $totalClasses,
            'totalFilieres' => $totalFilieres,
            'totalNiveaux' => $totalNiveaux,
            'totalEtudiants' => $totalEtudiants,
            'currentAnnee' => $currentAnnee,
            'selectedAnnee' => $selectedAnnee,
        ]);
    }

    /**
     * Affiche les résultats des étudiants d'une classe spécifique
     *
     * @param  ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function resultatClasse(ResultatsFilterRequest $request, $id)
    {
        // Normaliser : le formulaire envoie 'semestre1'/'semestre2' ou '1'/'2' ou vide (annuel)
        $rawPeriode = $request->semestre ?? $request->periode;
        if ($rawPeriode === 'semestre1') {
            $semestre = '1';
            $periode = 'semestre1';
        } elseif ($rawPeriode === 'semestre2') {
            $semestre = '2';
            $periode = 'semestre2';
        } elseif ($rawPeriode === '1') {
            $semestre = '1';
            $periode = 'semestre1';
        } elseif ($rawPeriode === '2') {
            $semestre = '2';
            $periode = 'semestre2';
        } else {
            $semestre = null; // Annuel = pas de filtre semestre
            $periode = '';
        }
        $annee_universitaire_id = $request->annee_universitaire_id;
        $include_all_statuses = $request->boolean('include_all_statuses');

        // Get current academic year if not specified (utiliser is_current au lieu de is_active)
        if (! $annee_universitaire_id) {
            $annee_universitaire_id = ESBTPAnneeUniversitaire::where('is_current', true)->first()->id ?? null;
        }

        $classe_id = $id;
        $classe = ESBTPClasse::with(['matieres' => function ($query) {
            $query->withPivot('coefficient');
        }])->findOrFail($classe_id);

        // Get students through inscriptions for the selected class and year
        $studentsQuery = ESBTPEtudiant::whereHas('inscriptions', function ($query) use ($classe_id, $annee_universitaire_id, $include_all_statuses) {
            $query->where('classe_id', $classe_id)
                ->where('annee_universitaire_id', $annee_universitaire_id);

            // CORRECTION: Si include_all_statuses = false, alors filtrer sur 'active' uniquement
            if (! $include_all_statuses) {
                $query->where('status', 'active');
            }
        });

        $students = $studentsQuery->get();

        \Log::info('Classe Results Query', [
            'classe_id' => $classe_id,
            'semestre' => $semestre,
            'annee_universitaire_id' => $annee_universitaire_id,
            'include_all_statuses' => $include_all_statuses,
            'students_count' => $students->count(),
        ]);

        // Get all notes for these students
        $notes = [];
        if ($students->count() > 0) {
            $student_ids = $students->pluck('id')->toArray();

            // Modification pour inclure toutes les notes quand "Toutes les périodes" est sélectionné
            $notesQuery = ESBTPNote::whereIn('etudiant_id', $student_ids)
                ->with(['etudiant', 'etudiant.user', 'evaluation', 'evaluation.classe', 'evaluation.matiere']);

            // Si un semestre est spécifié, filtrer par ce semestre
            // Les données en base sont incohérentes : semestre peut être '1', '2', 'semestre1', 'semestre2'
            if ($semestre) {
                $periodeFormat = 'semestre' . $semestre; // ex: 'semestre1'
                $notesQuery->where(function ($q) use ($semestre, $periodeFormat) {
                    $q->where('semestre', $semestre)
                        ->orWhere('semestre', $periodeFormat)
                        ->orWhereHas('evaluation', function ($query) use ($semestre, $periodeFormat) {
                            $query->where('periode', $periodeFormat)
                                ->orWhere('periode', $semestre);
                        });
                });
            }

            $notes = $notesQuery->get();

            \Log::info('Notes récupérées pour la classe', [
                'classe_id' => $classe_id,
                'notes_count' => $notes->count(),
                'semestre' => $semestre ? $semestre : 'Toutes les périodes',
            ]);
        }

        // Périodes disponibles (format compatible avec la vue)
        $periodes = [
            '' => 'Annuel',
            'semestre1' => 'Semestre 1',
            'semestre2' => 'Semestre 2',
        ];

        // Récupérer toutes les années universitaires (on affiche libelle ou name dans la vue)
        $annees_universitaires = ESBTPAnneeUniversitaire::orderBy('id', 'desc')->get();

        // Group notes by student and then by matière
        $notesByStudentMatiere = [];

        foreach ($notes as $note) {
            if (! $note->evaluation || ! $note->evaluation->matiere) {
                continue; // Skip notes without evaluation or matière
            }

            $etudiantId = $note->etudiant_id;
            $matiereId = $note->evaluation->matiere_id;

            if (! isset($notesByStudentMatiere[$etudiantId])) {
                $notesByStudentMatiere[$etudiantId] = [];
            }

            if (! isset($notesByStudentMatiere[$etudiantId][$matiereId])) {
                $notesByStudentMatiere[$etudiantId][$matiereId] = [
                    'sum' => 0,
                    'coeffSum' => 0,
                    'matiere' => $note->evaluation->matiere,
                ];
            }

            // CORRECTION : Ajout du coefficient de l'évaluation
            $evaluationCoefficient = $note->evaluation->coefficient ?? 1;
            $normalized = ($note->valeur / $note->evaluation->bareme) * 20;

            $notesByStudentMatiere[$etudiantId][$matiereId]['sum'] += $normalized * $evaluationCoefficient;
            $notesByStudentMatiere[$etudiantId][$matiereId]['coeffSum'] += $evaluationCoefficient;
        }

        // Calculate averages for each student and matière
        $resultats = [];

        foreach ($students as $student) {
            $moyenne = 0;
            $totalPoints = 0;
            $totalCoefficients = 0;

            if (isset($notesByStudentMatiere[$student->id])) {
                $studentMatieres = $notesByStudentMatiere[$student->id];

                foreach ($studentMatieres as $matiereId => $matiereData) {
                    if ($matiereData['coeffSum'] > 0) {
                        // Calcul de la moyenne de la matière
                        $matiereMoyenne = $matiereData['sum'] / $matiereData['coeffSum'];

                        // Récupération du coefficient de la matière dans la classe
                        $matiereCoefficient = $this->bulletinService->getCoefficientForCombination(
                            $matiereId,
                            $classe->id,
                            $annee_universitaire_id
                        );

                        // Application du coefficient de la matière
                        $totalPoints += $matiereMoyenne * $matiereCoefficient;
                        $totalCoefficients += $matiereCoefficient;
                    }
                }

                if ($totalCoefficients > 0) {
                    $moyenne = $totalPoints / $totalCoefficients;
                }
            }

            $resultats[] = [
                'etudiant' => $student,
                'moyenne' => $moyenne,
                'total_coefficients' => $totalCoefficients,
                'notes_count' => $notes->where('etudiant_id', $student->id)->count(),
            ];
        }

        // Calcul de l'assiduité si le setting est activé
        $afficherNoteAssiduite = \App\Helpers\SettingsHelper::get('bulletin_show_attendance_note', '1') === '1';
        $anneeObj = ESBTPAnneeUniversitaire::find($annee_universitaire_id);

        if ($afficherNoteAssiduite && $anneeObj) {
            foreach ($resultats as &$r) {
                $absences = $this->absenceService->calculerDetailAbsences(
                    $r['etudiant']->id,
                    $classe->id,
                    $anneeObj->date_debut ?? null,
                    $anneeObj->date_fin ?? null,
                    $annee_universitaire_id,
                    $periode ?: 'annuel'
                );
                $noteAssid = $this->bulletinService->resolveAttendanceNote($absences['justifiees'] ?? 0, $absences['non_justifiees'] ?? 0);
                $r['note_assiduite'] = $noteAssid;
                $r['moyenne_avec_assiduite'] = $r['moyenne'] + $noteAssid;
            }
            unset($r);
        }

        if (! $semestre) {
            $semesterWeights = $this->bulletinService->getSemesterWeights();

            foreach ($resultats as &$r) {
                $semestre1 = $this->bulletinService->getAlignedBulletinAverageForPeriode(
                    $r['etudiant']->id,
                    $classe->id,
                    $annee_universitaire_id,
                    'semestre1',
                    'annuel',
                    0,
                    0
                );
                $semestre2 = $this->bulletinService->getAlignedBulletinAverageForPeriode(
                    $r['etudiant']->id,
                    $classe->id,
                    $annee_universitaire_id,
                    'semestre2',
                    'annuel',
                    0,
                    0
                );
                $annuelle = $this->bulletinService->calculateAnnualAverage($semestre1, $semestre2, $semesterWeights);
                $displayAverage = $annuelle ?? $semestre1 ?? $semestre2 ?? null;

                $r['moyenne'] = $displayAverage ?? 0;
                $r['moyenne_avec_assiduite'] = $displayAverage ?? 0;
                $r['annual_state'] = $annuelle !== null
                    ? 'annual_complete'
                    : (($semestre1 !== null || $semestre2 !== null) ? 'annual_incomplete' : 'no_data');
            }
            unset($r);
        }

        // Trier par moyenne (avec assiduité si dispo) décroissante
        usort($resultats, function ($a, $b) {
            $avgA = $a['moyenne_avec_assiduite'] ?? $a['moyenne'];
            $avgB = $b['moyenne_avec_assiduite'] ?? $b['moyenne'];
            return $avgB <=> $avgA;
        });

        // Define annee_id for view consistency
        $annee_id = $annee_universitaire_id;

        // Récupérer l'objet année universitaire pour la vue
        $anneeUniversitaire = ESBTPAnneeUniversitaire::find($annee_universitaire_id);

        // Utiliser le bon nom de variable pour compatibilité avec la vue
        $anneesUniversitaires = $annees_universitaires;
        $bulletinConsistencyByStudent = [];
        $bulletinConsistencySummary = [
            'official' => 0,
            'stale' => 0,
        ];

        if (in_array($periode, ['semestre1', 'semestre2'], true) && $students->isNotEmpty()) {
            $bulletinConsistencyByStudent = $this->bulletinConsistencyService->getSnapshotsForStudents(
                $students->pluck('id')->all(),
                $classe->id,
                $annee_universitaire_id,
                $periode
            );

            foreach ($bulletinConsistencyByStudent as $snapshot) {
                if (! ($snapshot['official_bulletin_exists'] ?? false)) {
                    continue;
                }

                if ($snapshot['has_divergence'] ?? false) {
                    $bulletinConsistencySummary['stale']++;
                    continue;
                }

                $bulletinConsistencySummary['official']++;
            }
        }

        return view('esbtp.resultats.classe', compact(
            'classe',
            'students',
            'notes',
            'semestre',
            'periode',
            'periodes',
            'annee_universitaire_id',
            'annee_id',
            'anneesUniversitaires',
            'anneeUniversitaire',
            'resultats',
            'include_all_statuses',
            'afficherNoteAssiduite',
            'bulletinConsistencyByStudent',
            'bulletinConsistencySummary'
        ));
    }

    /**
     * Affiche les résultats détaillés d'un étudiant spécifique
     *
     * @param  ESBTPEtudiant  $etudiant
     * @return \Illuminate\Http\Response
     */
    public function resultatEtudiant(ResultatsFilterRequest $request, $id)
    {
        // Gérer les deux paramètres: semestre et periode (compatibilité)
        $requestedClasseId = $request->filled('classe_id') ? (int) $request->input('classe_id') : null;
        $annee_universitaire_id = $request->annee_universitaire_id;

        // CORRECTION: Conversion du format du semestre pour compatibilité avec le format attendu
        // Gérer les formats : 1, 2, semestre1, semestre2
        $normalizedPeriode = $this->normalizeBtsPeriode($request->input('periode', $request->input('semestre')));
        $periode = $normalizedPeriode['periode'];
        $semestre = $normalizedPeriode['semestre'];

        \Log::debug('Valeurs des variables pour la génération de PDF:', [
            'semestre' => $semestre,
            'periode' => $periode,
            'annee_universitaire_id' => $annee_universitaire_id,
        ]);

        $include_all_statuses = $request->boolean('include_all_statuses');

        // Get current academic year if not specified
        if (! $annee_universitaire_id) {
            $annee_universitaire_id = ESBTPAnneeUniversitaire::where('is_active', true)->first()->id ?? null;
        }

        // For view compatibility
        $annee_id = $annee_universitaire_id;

        $etudiant = ESBTPEtudiant::with('user')->findOrFail($id);

        // Get inscription for the student in the specified academic year
        $inscriptionQuery = $etudiant->inscriptions()
            ->where('annee_universitaire_id', $annee_universitaire_id);

        if (! $include_all_statuses) {
            $inscriptionQuery->where('status', 'active');
        }

        $inscriptions = (clone $inscriptionQuery)
            ->orderByDesc('date_inscription')
            ->orderByDesc('id')
            ->get();
        $inscription = $requestedClasseId
            ? $inscriptions->firstWhere('classe_id', $requestedClasseId)
            : $inscriptions->first();

        $classe_id = $requestedClasseId ?? $inscription?->classe_id;
        if (! $classe_id && $inscription) {
            $classe_id = $inscription->classe_id;
        }
        $classe = $classe_id ? ESBTPClasse::with(['filiere', 'niveau'])->find($classe_id) : null;
        // Get the academic year object for display
        $anneeUniversitaire = ESBTPAnneeUniversitaire::find($annee_universitaire_id);
        // Get all active classes for the filter dropdown
        $classes = ESBTPClasse::where('is_active', true)->orderBy('name')->get();
        $anneesUniversitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();
        $periodes = [
            (object) ['id' => 'annuel', 'code' => 'annuel', 'nom' => 'Annuel'],
            (object) ['id' => '1', 'code' => 'semestre1', 'nom' => 'Semestre 1'],
            (object) ['id' => '2', 'code' => 'semestre2', 'nom' => 'Semestre 2'],
        ];

        // Get notes for the student
        $notesQuery = ESBTPNote::where('etudiant_id', $id)
            ->with(['evaluation', 'evaluation.matiere', 'matiere']);

        $notesQuery->whereHas('evaluation', function ($query) use ($annee_universitaire_id, $classe_id, $periode) {
            if ($annee_universitaire_id) {
                $query->where('annee_universitaire_id', $annee_universitaire_id);
            }
            if ($classe_id) {
                $query->where('classe_id', $classe_id);
            }
            if ($periode !== 'annuel') {
                $query->whereIn('periode', [$periode, $periode === 'semestre1' ? '1' : '2']);
            }
        });

        // Si un semestre est spécifié, filtrer par ce semestre
        if ($semestre) {
            \Log::info('Filtrage par semestre:', ['semestre' => $semestre]);
            $notesQuery->where(function ($q) use ($semestre, $periode) {
                $q->where('semestre', $semestre)
                    ->orWhere('semestre', $periode)
                    ->orWhereHas('evaluation', function ($query) use ($semestre) {
                        $query->where('periode', 'semestre'.$semestre)
                            ->orWhere('periode', $semestre);
                    });
            });
        }

        $notes = $notesQuery->get();

        \Log::info('Student Result Notes', [
            'student_id' => $id,
            'semestre' => $semestre ? $semestre : 'Toutes les périodes',
            'include_all_statuses' => $include_all_statuses,
            'notes_count' => $notes->count(),
        ]);

        // Group notes by matière (subject)
        $notesByMatiere = [];
        $totalPoints = 0;
        $totalCoefficients = 0;
        $nonNumericNotes = 0;
        $classeMatieresIds = [];
        if ($classe) {
            $classeMatieresIds = \App\Models\ESBTPMatiere::with(['filieres:id', 'niveaux:id'])
                ->where('is_active', true)
                ->get()
                ->filter(function ($matiere) use ($classe) {
                    return $matiere->filieres->pluck('id')->contains($classe->filiere_id)
                        && $matiere->niveaux->pluck('id')->contains($classe->niveau_etude_id);
                })
                ->pluck('id')
                ->all();
        }

        foreach ($notes as $note) {
            if (! $note->evaluation || (! $note->matiere && ! $note->evaluation->matiere)) {
                \Log::warning('Note without evaluation or matière', ['note_id' => $note->id]);

                continue; // Skip notes without evaluation or matière
            }

            // CORRECTION: Prioriser l'ID de matière stocké directement sur la note si disponible
            $matiere_id = $note->matiere_id;
            if (! $matiere_id && $note->evaluation && $note->evaluation->matiere) {
                $matiere_id = $note->evaluation->matiere->id;
            }

            // Skip if we still can't determine the matiere_id
            if (! $matiere_id) {
                \Log::warning('Cannot determine matiere_id for note', ['note_id' => $note->id]);

                continue;
            }

            // Utiliser la matière déjà eager-loaded via evaluation.matiere
            $matiere = $note->matiere ?: $note->evaluation?->matiere;
            if (! $matiere) {
                \Log::warning("Matiere with ID {$matiere_id} not found for note ID {$note->id} - skipping note");

                continue;
            }

            // Initialize if this is the first note for this matière
            if (! isset($notesByMatiere[$matiere_id])) {
                $notesByMatiere[$matiere_id] = [
                    'matiere' => $matiere, // Use the freshly retrieved matiere
                    'notes' => [],
                    'calculations' => [], // Add storage for calculations
                    'total_points' => 0,
                    'total_coefficients' => 0,
                    'moyenne' => 0,
                    'origin' => in_array($matiere_id, $classeMatieresIds, true) ? 'classe' : 'notes',
                ];
                \Log::debug("Initialized new entry in notesByMatiere for matiere {$matiere->name} (ID: {$matiere->id})");
            }

            // CORRECTION AMÉLIORÉE: Vérification supplémentaire pour s'assurer que nous traitons la bonne note
            \Log::debug("Note {$note->id} VALUE CHECK: note field = {$note->note}, valeur field = {$note->valeur}");

            // Only use notes with evaluations that have a valid bareme
            if ($note->evaluation->bareme > 0) {
                // CORRECTION AMÉLIORÉE: Accès direct aux valeurs numériques pour éviter tout problème de
                // conversion ou de référence. Utiliser la fonction floatval pour s'assurer que nous avons une valeur numérique.
                $noteValue = is_numeric($note->note) ? floatval($note->note) : (is_numeric($note->valeur) ? floatval($note->valeur) : 0);
                $bareme = $note->evaluation->bareme > 0 ? floatval($note->evaluation->bareme) : 20;

                if ($noteValue === 'Absent' || ! is_numeric($noteValue)) {
                    $normalized = 0;
                    $nonNumericNotes++;
                } else {
                    $normalized = ($noteValue / $bareme) * 20;
                }

                $coefficient = $note->evaluation->coefficient ? floatval($note->evaluation->coefficient) : 1;
                $ponderation = $normalized * $coefficient;

                \Log::debug("CALCULATION for note {$note->id}: noteValue={$noteValue}, coefficient={$coefficient}, bareme={$bareme} => ponderation={$ponderation}");

                // CORRECTION AMÉLIORÉE: Ajouter explicitement les valeurs aux tableaux en utilisant des structures claires
                // Cela évite tout problème de référence ou de partage d'objets en mémoire
                $noteRef = [
                    'id' => $note->id,
                    'value' => $noteValue,
                    'coefficient' => $coefficient,
                    'ponderation' => $ponderation,
                    'normalized' => $normalized,
                ];

                // Store both the calculation structure AND the original note object to maintain view compatibility
                $notesByMatiere[$matiere_id]['notes'][] = $note; // Keep the full note object for the view
                $notesByMatiere[$matiere_id]['calculations'][] = $noteRef; // Store calculations separately
                $notesByMatiere[$matiere_id]['total_points'] += $ponderation;
                $notesByMatiere[$matiere_id]['total_coefficients'] += $coefficient;
            }
        }

        // Calculate average for each matière and overall weighted average
        $moyenneGenerale = 0;
        $countValidMatieres = 0;

        foreach ($notesByMatiere as $matiere_id => &$matiereData) {
            if ($matiereData['total_coefficients'] > 0) {
                $matiereData['moyenne'] = $matiereData['total_points'] / $matiereData['total_coefficients'];

                // For overall average, we treat each matière equally
                // You might want to adjust this to use matière coefficients
                $moyenneGenerale += $matiereData['moyenne'];
                $countValidMatieres++;
            }
        }

        // Calculate the overall moyenne générale
        $moyenneGenerale = $countValidMatieres > 0 ? $moyenneGenerale / $countValidMatieres : 0;

        // NOUVELLE LOGIQUE: Intégrer les moyennes manuelles depuis esbtp_resultats (même logique que previewMoyennes)
        $resultats = \App\Models\ESBTPResultat::where('etudiant_id', $id)
            ->when($classe_id, function ($query) use ($classe_id) {
                return $query->where('classe_id', $classe_id);
            })
            ->when($periode !== 'annuel', function ($query) use ($periode) {
                return $query->where('periode', $periode);
            })
            ->when($annee_universitaire_id, function ($query) use ($annee_universitaire_id) {
                return $query->where('annee_universitaire_id', $annee_universitaire_id);
            })
            ->with('matiere')
            ->get();

        // Intégrer les moyennes manuelles (elles l'emportent sur les calculées - priorité Manuel)
        $moyenneGeneraleRecalculee = 0;
        $countMatieresFinales = 0;

        foreach ($resultats as $resultat) {
            if (! $resultat->matiere) {
                continue;
            }

            $matiere_id = $resultat->matiere_id;

            // Si la matière n'existe pas encore dans notesByMatiere, la créer
            if (! isset($notesByMatiere[$matiere_id])) {
                $notesByMatiere[$matiere_id] = [
                    'matiere' => $resultat->matiere,
                    'notes' => [],
                    'calculations' => [],
                    'total_points' => 0,
                    'total_coefficients' => $resultat->coefficient,
                    'moyenne' => 0,
                ];
            }

            // Les moyennes manuelles écrasent toujours les calculées
            $notesByMatiere[$matiere_id]['moyenne'] = $resultat->moyenne;
            $notesByMatiere[$matiere_id]['source'] = 'manuelle';
            $notesByMatiere[$matiere_id]['total_coefficients'] = $resultat->coefficient;
        }

        // Marquer les moyennes calculées qui n'ont pas été écrasées
        foreach ($notesByMatiere as $matiere_id => &$matiereData) {
            if (! isset($matiereData['source'])) {
                $matiereData['source'] = 'calculee';
            }
        }

        // Recalculer la moyenne générale PONDÉRÉE (cohérent avec BulletinService::calculerMoyennePonderee)
        $sommePoints = 0;
        $sommeCoefs = 0;
        foreach ($notesByMatiere as $matiere_id => $matiereData) {
            if ($matiereData['moyenne'] > 0 && $matiereData['total_coefficients'] > 0) {
                $sommePoints += $matiereData['moyenne'] * $matiereData['total_coefficients'];
                $sommeCoefs += $matiereData['total_coefficients'];
            }
        }
        $moyenneGenerale = $sommeCoefs > 0 ? $sommePoints / $sommeCoefs : 0;

        \Log::info('Student Result Calculations', [
            'student_id' => $id,
            'matieres_count' => count($notesByMatiere),
            'moyenne_generale' => $moyenneGenerale,
            'non_numeric_notes' => $nonNumericNotes,
        ]);

        // Calcul de la note d'assiduité (cohérent avec le bulletin PDF)
        $afficherNoteAssiduite = \App\Helpers\SettingsHelper::get('bulletin_show_attendance_note', '1') === '1';
        $noteAssiduite = 0;
        $moyenneAvecAssiduite = $moyenneGenerale;

        if ($afficherNoteAssiduite && $classe && $anneeUniversitaire) {
            $absences = $this->absenceService->calculerDetailAbsences(
                $etudiant->id,
                $classe->id,
                $anneeUniversitaire->date_debut ?? null,
                $anneeUniversitaire->date_fin ?? null,
                $anneeUniversitaire->id,
                $periode ?: 'annuel'
            );
            $noteAssiduite = $this->bulletinService->resolveAttendanceNote($absences['justifiees'] ?? 0, $absences['non_justifiees'] ?? 0);
            $moyenneAvecAssiduite = $moyenneGenerale + $noteAssiduite;
        }

        $semesterWeights = $this->bulletinService->getSemesterWeights();

        // Moyennes semestrielles incluant l'assiduité (via bulletin ou fallback)
        $moyenneSemestre1 = $this->bulletinService->getAlignedBulletinAverageForPeriode(
            $id, $classe_id ?? 0, $annee_universitaire_id ?? 0,
            'semestre1', $periode, $moyenneAvecAssiduite, $noteAssiduite
        );
        $moyenneSemestre2 = $this->bulletinService->getAlignedBulletinAverageForPeriode(
            $id, $classe_id ?? 0, $annee_universitaire_id ?? 0,
            'semestre2', $periode, $moyenneAvecAssiduite, $noteAssiduite
        );
        $moyenneAnnuelle = $this->bulletinService->calculateAnnualAverage($moyenneSemestre1, $moyenneSemestre2, $semesterWeights);
        $detailUiState = $this->buildAnnualDetailUiState($periode, $moyenneSemestre1, $moyenneSemestre2, $moyenneAnnuelle);
        $bulletinWorkflowPeriode = $detailUiState['bulletin_workflow_periode'];
        $bulletinWorkflowPeriodeLabel = $detailUiState['bulletin_workflow_periode_label'];
        $bulletinConsistency = $classe
            ? $this->bulletinConsistencyService->getSnapshot(
                $etudiant->id,
                $classe->id,
                $annee_universitaire_id,
                $bulletinWorkflowPeriode
            )
            : null;

        if ($bulletinConsistency && in_array($periode, ['semestre1', 'semestre2'], true)) {
            $moyenneGenerale = $bulletinConsistency['current_recomputed_raw_total'] ?? $moyenneGenerale;
            $noteAssiduite = $bulletinConsistency['current_recomputed_note_assiduite'] ?? $noteAssiduite;
            $moyenneAvecAssiduite = $bulletinConsistency['current_recomputed_effective_total'] ?? $moyenneAvecAssiduite;

            if ($periode === 'semestre1') {
                $moyenneSemestre1 = $moyenneAvecAssiduite;
            } else {
                $moyenneSemestre2 = $moyenneAvecAssiduite;
            }

            $notesByMatiere = $this->mapConsistencySubjectsToDetailNotes($bulletinConsistency['current_subjects'] ?? [], $notes);
            $detailUiState = $this->buildAnnualDetailUiState($periode, $moyenneSemestre1, $moyenneSemestre2, $moyenneAnnuelle);
        } elseif ($bulletinConsistency && ($detailUiState['state'] ?? null) === 'annual_incomplete') {
            $notesByMatiere = $this->overlayConsistencySubjectLabels(
                $notesByMatiere,
                $bulletinConsistency['current_subjects'] ?? []
            );
        }

        return view('esbtp.resultats.etudiant', compact(
            'etudiant',
            'classe',
            'classe_id',
            'anneeUniversitaire',
            'notes',
            'notesByMatiere',
            'moyenneGenerale',
            'moyenneAvecAssiduite',
            'noteAssiduite',
            'afficherNoteAssiduite',
            'semestre',
            'periode',
            'annee_universitaire_id',
            'annee_id',
            'classes',
            'anneesUniversitaires',
            'periodes',
            'moyenneSemestre1',
            'moyenneSemestre2',
            'moyenneAnnuelle',
            'semesterWeights',
            'include_all_statuses',
            'detailUiState',
            'bulletinWorkflowPeriode',
            'bulletinWorkflowPeriodeLabel',
            'bulletinConsistency'
        ));
    }

    /**
     * Load students with lazy loading pagination for AJAX requests
     */
    public function loadEtudiants(Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 50);
        $classe_id = $request->get('classe_id');
        $semestre = $request->get('semestre');
        $detail_periode = $semestre ? 'semestre'.$semestre : 'annuel';
        $annee_universitaire_id = $request->get('annee_universitaire_id');
        $include_all_statuses = $request->get('include_all_statuses', true);

        try {
            // Get students query based on the same logic as resultats method
            $studentsQuery = $this->bulletinService->buildEtudiantsQuery($classe_id, $annee_universitaire_id, $include_all_statuses);
            $total = (clone $studentsQuery)->count();
            $etudiants = (clone $studentsQuery)->skip(($page - 1) * $perPage)->take($perPage)->get();
            $studentIds = (clone $studentsQuery)->pluck('id');
            $kpis = $this->bulletinService->computeResultatsKpis($studentIds, $classe_id, $annee_universitaire_id, $semestre);

            // Calculate moyennes, rangs, etc. for these students
            $moyennes = [];
            $rangs = [];
            $bulletins = [];
            $annualValueStatuses = [];
            $notes = collect([]);

            if ($etudiants->count() > 0) {
                $student_ids = $etudiants->pluck('id')->toArray();

                // Get notes for these students
                $notesQuery = ESBTPNote::whereIn('etudiant_id', $student_ids)
                    ->with(['etudiant', 'etudiant.user', 'evaluation', 'evaluation.classe', 'evaluation.matiere']);

                if ($classe_id) {
                    $notesQuery->whereHas('evaluation', function ($query) use ($classe_id) {
                        $query->where('classe_id', $classe_id);
                    });
                }

                if ($annee_universitaire_id) {
                    $notesQuery->whereHas('evaluation', function ($query) use ($annee_universitaire_id) {
                        $query->where('annee_universitaire_id', $annee_universitaire_id);
                    });
                }

                if ($semestre) {
                    $notesQuery->whereHas('evaluation', function ($query) use ($semestre) {
                        $query->where('periode', 'like', 'semestre'.$semestre.'%');
                    });
                }

                $notes = $notesQuery->get();

                // Calculate stats for these students
                if (! $semestre) {
                    // Mode Annuel : calculer la moyenne annuelle pondérée S1/S2 pour chaque étudiant
                    $weights = $this->bulletinService->getSemesterWeights();

                    // Pré-charger les inscriptions pour éviter N+1
                    $inscriptionMap = collect();
                    if (!$classe_id) {
                        $inscriptionMap = \App\Models\ESBTPInscription::query()
                            ->whereIn('etudiant_id', $etudiants->pluck('id'))
                            ->where('annee_universitaire_id', $annee_universitaire_id)
                            ->orderByDesc('date_inscription')
                            ->get()
                            ->unique('etudiant_id')
                            ->keyBy('etudiant_id');
                    }

                    foreach ($etudiants as $etudiant) {
                        $etudiantClasseId = $classe_id ?: ($inscriptionMap[$etudiant->id]->classe_id ?? null);
                        if (!$etudiantClasseId) {
                            continue; // Pas de classe trouvée, skip cet étudiant
                        }

                        $annualAttendanceNote = $this->bulletinService->calculateEffectiveAttendanceNoteForStudent(
                            $etudiant->id,
                            $etudiantClasseId,
                            $annee_universitaire_id ?? 0,
                            'annuel'
                        );

                        try {
                            $s1 = $this->bulletinService->getAlignedBulletinAverageForPeriode(
                                $etudiant->id, $etudiantClasseId, $annee_universitaire_id ?? 0,
                                'semestre1', 'annuel', 0, $annualAttendanceNote
                            );
                            $s2 = $this->bulletinService->getAlignedBulletinAverageForPeriode(
                                $etudiant->id, $etudiantClasseId, $annee_universitaire_id ?? 0,
                                'semestre2', 'annuel', 0, $annualAttendanceNote
                            );
                        } catch (\RuntimeException $e) {
                            // Coefficient manquant pour cet étudiant, skip
                            continue;
                        }
                        $annual = $this->bulletinService->calculateAnnualAverage($s1, $s2, $weights);
                        if ($annual !== null) {
                            $moyennes[$etudiant->id] = round($annual, 2);
                            $annualValueStatuses[$etudiant->id] = [
                                'state' => 'annual_complete',
                                'label' => null,
                            ];
                        } elseif ($s1 !== null) {
                            $moyennes[$etudiant->id] = round($s1, 2);
                            $annualValueStatuses[$etudiant->id] = [
                                'state' => 'annual_incomplete',
                                'label' => 'Provisoire · S1 seulement',
                            ];
                        } elseif ($s2 !== null) {
                            $moyennes[$etudiant->id] = round($s2, 2);
                            $annualValueStatuses[$etudiant->id] = [
                                'state' => 'annual_incomplete',
                                'label' => 'Provisoire · S2 seulement',
                            ];
                        }
                    }
                    // Calculer les rangs
                    if (count($moyennes) > 0) {
                        arsort($moyennes);
                        $rank = 1;
                        foreach (array_keys($moyennes) as $etudiantId) {
                            $rangs[$etudiantId] = $rank++;
                        }
                    }
                } else {
                    // Mode Semestre : calcul standard par notes (filtré par période)
                    $this->bulletinService->calculateStudentStatsFixed($etudiants, $notes, $moyennes, $rangs, $classe_id, $annee_universitaire_id, $semestre);

                    // Ajouter l'assiduité si activée
                    $showAssid = \App\Helpers\SettingsHelper::get('bulletin_show_attendance_note', '1') === '1';
                    if ($showAssid && $annee_universitaire_id) {
                        $anneeObj = ESBTPAnneeUniversitaire::find($annee_universitaire_id);
                        if ($anneeObj) {
                            $periodeForManual = $semestre ? 'semestre'.$semestre : 'annuel';
                            foreach ($moyennes as $etudiantId => &$moy) {
                                $abs = $this->absenceService->calculerDetailAbsences(
                                    $etudiantId,
                                    $classe_id ?? 0,
                                    $anneeObj->date_debut ?? null,
                                    $anneeObj->date_fin ?? null,
                                    $annee_universitaire_id,
                                    $periodeForManual
                                );
                                $noteAssid = $this->bulletinService->resolveAttendanceNote($abs['justifiees'] ?? 0, $abs['non_justifiees'] ?? 0);
                                $moy += $noteAssid;
                            }
                            unset($moy);
                            // Re-trier et recalculer les rangs
                            arsort($moyennes);
                            $rank = 1;
                            $rangs = [];
                            foreach (array_keys($moyennes) as $eid) {
                                $rangs[$eid] = $rank++;
                            }
                        }
                    }
                }

                // Get bulletins
                $this->bulletinService->getStudentBulletins($etudiants, $classe_id, $annee_universitaire_id, $semestre, $bulletins);
            }

            // Determine which template to use
            $classe = $classe_id ? ESBTPClasse::find($classe_id) : null;
            $viewData = compact('etudiants', 'moyennes', 'rangs', 'bulletins', 'classe', 'annualValueStatuses') + [
                'annee_id' => $annee_universitaire_id,
                'detail_periode' => $detail_periode,
                'include_all_statuses' => (bool) $include_all_statuses,
                'attendanceNoteEnabled' => $this->bulletinService->isAttendanceNoteEnabled(),
            ];

            if ((int) $page === 1) {
                $html = view('esbtp.resultats.partials.liste-etudiants', $viewData)->render();
            } else {
                $html = view('esbtp.resultats.partials.lignes-etudiants', $viewData)->render();
            }

            $hasMore = ($page * $perPage) < $total;

            return response()->json([
                'html' => $html,
                'total' => $total,
                'current_page' => (int) $page,
                'has_more' => $hasMore,
                'loaded_count' => $etudiants->count(),
                'kpis' => $kpis,
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur lors du chargement lazy des étudiants', [
                'error' => $e->getMessage(),
                'page' => $page,
                'classe_id' => $classe_id,
            ]);

            return response()->json([
                'error' => 'Erreur lors du chargement des étudiants',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Affiche l'interface d'édition groupée des résultats d'une classe
     *
     * @param  ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function editResultatsClasse(ResultatsFilterRequest $request, $id)
    {
        $semestre = $request->semestre;
        $periode = $semestre ? 'semestre'.$semestre : null;
        $annee_universitaire_id = $request->annee_universitaire_id;
        $include_all_statuses = $request->has('include_all_statuses');

        // Get current academic year if not specified
        if (! $annee_universitaire_id) {
            $annee_universitaire_id = ESBTPAnneeUniversitaire::where('is_current', true)->first()->id ?? null;
        }

        $classe_id = $id;
        $classe = ESBTPClasse::with(['matieres' => function ($query) {
            $query->withPivot('coefficient');
        }, 'filiere', 'niveau'])->findOrFail($classe_id);

        // Get students through inscriptions
        $studentsQuery = ESBTPEtudiant::whereHas('inscriptions', function ($query) use ($classe_id, $annee_universitaire_id, $include_all_statuses) {
            $query->where('classe_id', $classe_id)
                ->where('annee_universitaire_id', $annee_universitaire_id);

            if (! $include_all_statuses) {
                $query->where('status', 'active');
            }
        })->with('user');

        $students = $studentsQuery->get()->sortBy(function ($student) {
            return $student->nom.' '.$student->prenoms;
        })->values();

        // Get enseignants from planning général based on class filiere + niveau combination
        // Récupérer les planifications pour la combinaison filière + niveau de cette classe
        $planifications = \DB::table('esbtp_planifications_academiques')
            ->where('filiere_id', $classe->filiere_id)
            ->where('niveau_etude_id', $classe->niveau_etude_id)
            ->where('annee_universitaire_id', $annee_universitaire_id)
            ->pluck('id');

        // Récupérer les enseignants assignés dans ces planifications (via planning général)
        $enseignantIdsFromPlanning = \DB::table('esbtp_planification_teachers')
            ->whereIn('planification_id', $planifications)
            ->pluck('teacher_id')
            ->unique();

        // Récupérer les enseignants avec leurs infos complètes
        $enseignants = \App\Models\ESBTPTeacher::with('user')
            ->whereIn('id', $enseignantIdsFromPlanning)
            ->where('is_active', true)
            ->get();

        // Fallback: si aucun enseignant trouvé dans planning général, prendre tous les actifs
        if ($enseignants->isEmpty()) {
            \Log::warning('Aucun enseignant trouvé dans planning général pour classe', [
                'classe_id' => $classe_id,
                'filiere_id' => $classe->filiere_id,
                'niveau_id' => $classe->niveau_etude_id,
            ]);
            $enseignants = \App\Models\ESBTPTeacher::with('user')->where('is_active', true)->get();
        }

        // Get all matieres for the class based on filiere + niveau combination
        $classeFiliereId = $classe->filiere_id;
        $classeNiveauId = $classe->niveau_etude_id;

        $matieres = ESBTPMatiere::with(['filieres:id,name,code', 'niveaux:id,name,code'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->filter(function (ESBTPMatiere $matiere) use ($classeFiliereId, $classeNiveauId) {
                if (! $classeFiliereId || ! $classeNiveauId) {
                    return false;
                }

                return $matiere->filieres->pluck('id')->contains($classeFiliereId)
                    && $matiere->niveaux->pluck('id')->contains($classeNiveauId);
            })
            ->values()
            ->map(function (ESBTPMatiere $matiere) use ($classe, $annee_universitaire_id) {
                // Récupérer le coefficient SANS lancer d'exception
                try {
                    $coefficient = $this->bulletinService->getCoefficientForCombination(
                        $matiere->id,
                        $classe->id,
                        $annee_universitaire_id
                    );
                } catch (\RuntimeException $exception) {
                    // Fallback: utiliser 1 comme valeur par défaut au lieu de bloquer
                    $coefficient = 1;
                    \Log::info("Coefficient manquant pour matière {$matiere->id} dans editResultatsClasse, utilisation du défaut: 1");
                }

                $matiere->pivot = (object) [
                    'coefficient' => $coefficient,
                ];

                return $matiere;
            });

        // Get existing resultats for this class/periode/annee
        $resultats = \App\Models\ESBTPResultat::where('classe_id', $classe_id)
            ->when($periode, function ($query) use ($periode) {
                return $query->where('periode', $periode);
            })
            ->when($annee_universitaire_id, function ($query) use ($annee_universitaire_id) {
                return $query->where('annee_universitaire_id', $annee_universitaire_id);
            })
            ->with(['etudiant', 'matiere'])
            ->get()
            ->groupBy('etudiant_id');

        // NOUVELLE LOGIQUE: Calculer les moyennes automatiques depuis les évaluations pour chaque étudiant
        $moyennesCalculees = [];
        foreach ($students as $student) {
            $moyennesCalculees[$student->id] = $this->bulletinService->calculateMoyennesForStudent(
                $student->id,
                $classe_id,
                $periode,
                $annee_universitaire_id,
                $matieres
            );
        }

        // Get absences from bulletins table (not esbtp_absences which is for cours tracking)
        $absences = ESBTPBulletin::whereIn('etudiant_id', $students->pluck('id'))
            ->where('classe_id', $classe_id)
            ->when($periode, function ($query) use ($periode) {
                return $query->where('periode', $periode);
            })
            ->when($annee_universitaire_id, function ($query) use ($annee_universitaire_id) {
                return $query->where('annee_universitaire_id', $annee_universitaire_id);
            })
            ->get()
            ->keyBy('etudiant_id');

        // Périodes disponibles
        $periodes = [
            'semestre1' => 'Premier Semestre',
            'semestre2' => 'Deuxième Semestre',
        ];

        // Récupérer toutes les années universitaires
        $annees_universitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();
        $anneeUniversitaire = ESBTPAnneeUniversitaire::find($annee_universitaire_id);

        // KPIs
        $kpis = [
            'total_students' => $students->count(),
            'total_matieres' => $matieres->count(),
            'total_resultats' => $resultats->sum(function ($group) {
                return $group->count();
            }),
            'completion_rate' => $students->count() > 0 && $matieres->count() > 0
                ? round(($resultats->sum(function ($group) {
                    return $group->count();
                }) / ($students->count() * $matieres->count())) * 100, 1)
                : 0,
        ];

        // Récupérer les enseignants spécifiques à chaque matière depuis planning général
        $enseignantsParMatiere = [];
        foreach ($matieres as $matiere) {
            // Récupérer la planification pour cette matière + combinaison classe
            $planification = \DB::table('esbtp_planifications_academiques')
                ->where('matiere_id', $matiere->id)
                ->where('filiere_id', $classe->filiere_id)
                ->where('niveau_etude_id', $classe->niveau_etude_id)
                ->where('annee_universitaire_id', $annee_universitaire_id)
                ->first();

            if ($planification) {
                // Récupérer les enseignants assignés dans cette planification
                $enseignantIds = \DB::table('esbtp_planification_teachers')
                    ->where('planification_id', $planification->id)
                    ->pluck('teacher_id');

                // Récupérer les enseignants avec leurs infos complètes
                $enseignantsMatiere = \App\Models\ESBTPTeacher::with('user')
                    ->whereIn('id', $enseignantIds)
                    ->where('is_active', true)
                    ->get();

                $enseignantsParMatiere[$matiere->id] = $enseignantsMatiere;
            } else {
                // Fallback: utiliser tous les enseignants si planning non configuré
                $enseignantsParMatiere[$matiere->id] = $enseignants;
            }
        }

        // Récupérer les professeurs déjà assignés depuis les bulletins pour pré-remplir le modal
        $professeursGroupes = [];

        // Prendre un bulletin exemple pour cette classe/période (peu importe l'étudiant car professeurs identiques)
        $sampleBulletin = ESBTPBulletin::where('classe_id', $classe_id)
            ->when($periode, function ($query) use ($periode) {
                return $query->where('periode', $periode);
            })
            ->when($annee_universitaire_id, function ($query) use ($annee_universitaire_id) {
                return $query->where('annee_universitaire_id', $annee_universitaire_id);
            })
            ->whereNotNull('professeurs')
            ->first();

        if ($sampleBulletin && $sampleBulletin->professeurs) {
            // Décode le JSON: {"matiere_id": "Teacher Name", ...}
            $professeursJson = json_decode($sampleBulletin->professeurs, true) ?: [];

            \Log::info('📋 Professeurs déjà assignés récupérés depuis bulletin', [
                'bulletin_id' => $sampleBulletin->id,
                'professeurs_json' => $professeursJson,
            ]);

            // Mapper les noms vers les IDs des enseignants
            foreach ($professeursJson as $matiereId => $teacherName) {
                // Ignorer les valeurs null ou vides
                if (empty($teacherName)) {
                    continue;
                }

                // Chercher l'enseignant par nom dans la liste des enseignants disponibles pour cette matière
                $enseignantsDeMatiere = $enseignantsParMatiere[$matiereId] ?? collect();

                $foundTeacher = $enseignantsDeMatiere->first(function ($enseignant) use ($teacherName) {
                    return $enseignant->user && $enseignant->user->name === $teacherName;
                });

                if ($foundTeacher) {
                    $professeursGroupes[$matiereId] = $foundTeacher->id;
                    \Log::debug("✅ Mapped matiere {$matiereId}: '{$teacherName}' → teacher_id {$foundTeacher->id}");
                } else {
                    \Log::warning("⚠️ Could not find teacher_id for matiere {$matiereId}: '{$teacherName}'");
                }
            }

            \Log::info('🔄 Mapping professeurs groupés final', [
                'professeursGroupes' => $professeursGroupes,
            ]);
        } else {
            \Log::info('ℹ️ Aucun bulletin avec professeurs trouvé pour pré-remplir le modal', [
                'classe_id' => $classe_id,
                'periode' => $periode,
                'annee_universitaire_id' => $annee_universitaire_id,
            ]);
        }

        $attendanceNoteRules = $this->bulletinService->getAttendanceNoteSettings();
        $attendanceNoteEnabled = $this->bulletinService->isAttendanceNoteEnabled();

        return view('esbtp.resultats.classe-edit', compact(
            'classe',
            'students',
            'matieres',
            'enseignants',
            'enseignantsParMatiere',
            'professeursGroupes',
            'resultats',
            'absences',
            'semestre',
            'periode',
            'periodes',
            'annee_universitaire_id',
            'annees_universitaires',
            'anneeUniversitaire',
            'include_all_statuses',
            'kpis',
            'moyennesCalculees',
            'attendanceNoteRules',
            'attendanceNoteEnabled'
        ));
    }

    /**
     * Récupérer les moyennes existantes pour les étudiants sélectionnés
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMoyennes(GetMoyennesRequest $request)
    {
        $periode = $request->semestre ? 'semestre'.$request->semestre : null;

        $query = ESBTPResultat::where('classe_id', $request->classe_id)
            ->where('annee_universitaire_id', $request->annee_universitaire_id)
            ->when($periode, function ($query) use ($periode) {
                return $query->where('periode', $periode);
            })
            ->whereIn('etudiant_id', $request->etudiant_ids);

        // Handle single matiere_id or multiple matiere_ids
        if ($request->has('matiere_id') && $request->matiere_id) {
            $query->where('matiere_id', $request->matiere_id);
        } elseif ($request->has('matiere_ids') && ! empty($request->matiere_ids)) {
            $query->whereIn('matiere_id', $request->matiere_ids);
        }

        $resultats = $query->get();

        // NOUVELLE LOGIQUE: Calculer les moyennes automatiques pour enrichir les résultats
        // Récupérer les matières concernées
        $matiereIds = $request->has('matiere_id') && $request->matiere_id
            ? [$request->matiere_id]
            : ($request->has('matiere_ids') ? $request->matiere_ids : []);

        $matieres = ! empty($matiereIds)
            ? ESBTPMatiere::whereIn('id', $matiereIds)->get()
            : ESBTPMatiere::where('is_active', true)->get();

        // Calculer les moyennes pour chaque étudiant
        $moyennesCalculees = [];
        foreach ($request->etudiant_ids as $etudiantId) {
            $moyennesCalculees[$etudiantId] = $this->bulletinService->calculateMoyennesForStudent(
                $etudiantId,
                $request->classe_id,
                $periode,
                $request->annee_universitaire_id,
                $matieres
            );
        }

        // Enrichir les résultats avec les moyennes calculées et la source
        $resultatsEnriched = [];
        foreach ($resultats as $resultat) {
            $resultatArray = $resultat->toArray();
            $matiereId = $resultat->matiere_id;
            $etudiantId = $resultat->etudiant_id;

            // Ajouter la moyenne calculée et la source
            if (isset($moyennesCalculees[$etudiantId][$matiereId])) {
                $resultatArray['moyenne_calculee'] = $moyennesCalculees[$etudiantId][$matiereId]['moyenne'];
                $resultatArray['source'] = $moyennesCalculees[$etudiantId][$matiereId]['source'];
            } else {
                $resultatArray['moyenne_calculee'] = null;
                $resultatArray['source'] = 'manuelle';
            }

            $resultatsEnriched[] = $resultatArray;
        }

        // Ajouter les moyennes calculées pour les matières sans résultat existant
        foreach ($request->etudiant_ids as $etudiantId) {
            if (! isset($moyennesCalculees[$etudiantId])) {
                continue;
            }

            foreach ($moyennesCalculees[$etudiantId] as $matiereId => $moyenneData) {
                // Vérifier si ce couple (etudiant, matiere) existe déjà dans les résultats
                $exists = collect($resultatsEnriched)->contains(function ($r) use ($etudiantId, $matiereId) {
                    return $r['etudiant_id'] == $etudiantId && $r['matiere_id'] == $matiereId;
                });

                if (! $exists && $moyenneData['moyenne'] !== null) {
                    // Ajouter un résultat virtuel avec moyenne calculée
                    $resultatsEnriched[] = [
                        'id' => null,
                        'etudiant_id' => $etudiantId,
                        'matiere_id' => $matiereId,
                        'classe_id' => $request->classe_id,
                        'annee_universitaire_id' => $request->annee_universitaire_id,
                        'periode' => $periode,
                        'moyenne' => $moyenneData['moyenne'], // Pré-remplir avec moyenne calculée
                        'moyenne_calculee' => $moyenneData['moyenne'],
                        'source' => $moyenneData['source'],
                        'coefficient' => null,
                        'rang' => null,
                        'appreciation' => null,
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'resultats' => $resultatsEnriched,
        ]);
    }

    /**
     * Récupérer les absences existantes pour les étudiants sélectionnés
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAbsences(GetAbsencesRequest $request)
    {
        $periode = $request->semestre ? 'semestre'.$request->semestre : null;

        // Sélectionner seulement les colonnes de la table, pas les accessors
        // pour éviter l'erreur "Call to a member function getRelationExistenceQuery() on null"
        $bulletins = ESBTPBulletin::select([
            'id',
            'etudiant_id',
            'classe_id',
            'annee_universitaire_id',
            'periode',
            'absences_justifiees',
            'absences_non_justifiees',
            'total_absences',
        ])
            ->where('classe_id', $request->classe_id)
            ->where('annee_universitaire_id', $request->annee_universitaire_id)
            ->when($periode, function ($query) use ($periode) {
                return $query->where('periode', $periode);
            })
            ->whereIn('etudiant_id', $request->etudiant_ids)
            ->get();

        return response()->json([
            'success' => true,
            'bulletins' => $bulletins,
        ]);
    }

    /**
     * Récupère le coefficient d'une matière pour une combinaison filiere + niveau + année
     */
    /**
     * Récupérer le coefficient d'une matière pour une classe (AJAX)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMatiereCoefficient(Request $request)
    {

        try {
            $classe = ESBTPClasse::findOrFail($request->classe_id);
            $anneeUniversitaire = ESBTPAnneeUniversitaire::where('is_current', true)->first();

            if (!$anneeUniversitaire) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année universitaire active trouvée.',
                    'coefficient' => 1,
                    'is_configured' => false
                ]);
            }

            // Récupérer le coefficient SANS lancer d'exception
            $cacheKey = $request->matiere_id . '|' . $request->classe_id . '|' . $anneeUniversitaire->id;

            // Utiliser le cache si disponible
            if (isset($this->coefficientCache[$cacheKey])) {
                $coefficient = $this->coefficientCache[$cacheKey];
                $isConfigured = true;
            } else {
                // Requête directe SANS exception
                $coefficient = ESBTPMatiereCoefficient::where('matiere_id', $request->matiere_id)
                    ->where('filiere_id', $classe->filiere_id)
                    ->where('niveau_etude_id', $classe->niveau_etude_id)
                    ->where('annee_universitaire_id', $anneeUniversitaire->id)
                    ->value('coefficient');

                if ($coefficient !== null) {
                    $this->coefficientCache[$cacheKey] = (float) $coefficient;
                    $isConfigured = true;
                } else {
                    $coefficient = 1; // Fallback sans exception
                    $isConfigured = false;
                }
            }

            return response()->json([
                'success' => true,
                'coefficient' => $coefficient,
                'is_configured' => $isConfigured,
                'message' => $isConfigured 
                    ? 'Coefficient trouvé dans la configuration' 
                    : 'Aucun coefficient configuré pour cette combinaison. Valeur par défaut: 1'
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur récupération coefficient matière: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du coefficient: ' . $e->getMessage(),
                'coefficient' => 1,
                'is_configured' => false
            ], 500);
        }
    }

    /**
     * Mise à jour groupée des moyennes par matière
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdateMoyennes(BulkUpdateMoyennesRequest $request)
    {

        \DB::beginTransaction();
        try {
            $updated = 0;
            $created = 0;

            // Convertir semestre en periode
            $periode = 'semestre'.$request->semestre;

            foreach ($request->moyennes as $moyenneData) {
                if (! isset($moyenneData['moyenne']) || $moyenneData['moyenne'] === null || $moyenneData['moyenne'] === '') {
                    continue;
                }

                // Construire les conditions de recherche (toujours avec periode)
                $conditions = [
                    'etudiant_id' => $moyenneData['etudiant_id'],
                    'classe_id' => $request->classe_id,
                    'matiere_id' => $moyenneData['matiere_id'],
                    'periode' => $periode,
                    'annee_universitaire_id' => $request->annee_universitaire_id,
                ];

                // Pas besoin de champ 'type' - la présence dans esbtp_resultats suffit
                // Le système détecte automatiquement "manuel" si le résultat existe en BDD
                $resultat = \App\Models\ESBTPResultat::updateOrCreate(
                    $conditions,
                    [
                        'moyenne' => $moyenneData['moyenne'],
                        'coefficient' => $moyenneData['coefficient'] ?? 1,
                    ]
                );

                if ($resultat->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            }

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => "✅ Moyennes mises à jour avec succès. ($created créées, $updated modifiées)",
                'stats' => [
                    'created' => $created,
                    'updated' => $updated,
                ],
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('❌ Erreur bulk update moyennes: '.$e->getMessage(), [
                'request_data' => $request->all(),
                'exception' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour des moyennes: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mise à jour groupée des professeurs
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdateProfesseurs(BulkUpdateProfesseursRequest $request)
    {
        \Log::info('🔵 bulkUpdateProfesseurs - START', [
            'request_data' => $request->all(),
        ]);

        \DB::beginTransaction();
        try {
            // Créer un tableau associatif matiere_id => teacher_name
            $professeursMap = [];
            foreach ($request->professeurs as $profData) {
                if (isset($profData['enseignant_id']) && $profData['enseignant_id']) {
                    // Récupérer le nom du professeur depuis esbtp_teachers
                    $teacher = \App\Models\ESBTPTeacher::with('user')->find($profData['enseignant_id']);
                    if ($teacher && $teacher->user) {
                        $professeursMap[$profData['matiere_id']] = $teacher->user->name;
                        \Log::info('✅ Teacher found', [
                            'matiere_id' => $profData['matiere_id'],
                            'teacher_id' => $profData['enseignant_id'],
                            'teacher_name' => $teacher->user->name,
                        ]);
                    }
                }
            }

            \Log::info('📋 professeursMap created', [
                'count' => count($professeursMap),
                'data' => $professeursMap,
            ]);

            // Récupérer TOUS les étudiants actifs de la classe
            $etudiants = \App\Models\ESBTPEtudiant::whereHas('inscriptions', function ($q) use ($request) {
                $q->where('classe_id', $request->classe_id)
                    ->where('annee_universitaire_id', $request->annee_universitaire_id)
                    ->where('status', 'active');
            })->get();

            $updated = 0;
            $created = 0;

            foreach ($etudiants as $etudiant) {
                // Récupérer ou créer le bulletin pour cet étudiant
                $bulletin = \App\Models\ESBTPBulletin::firstOrCreate(
                    [
                        'etudiant_id' => $etudiant->id,
                        'classe_id' => $request->classe_id,
                        'periode' => $request->periode,
                        'annee_universitaire_id' => $request->annee_universitaire_id,
                    ],
                    [
                        'professeurs' => json_encode($professeursMap),
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]
                );

                // Si le bulletin existait déjà, fusionner les professeurs
                if (! $bulletin->wasRecentlyCreated) {
                    $professeursExistants = json_decode($bulletin->professeurs, true) ?: [];

                    // FIX: S'assurer que c'est un tableau associatif (objet JSON), pas un tableau indexé
                    // Si $professeursExistants est un tableau indexé [0=>val1, 1=>val2], le convertir en objet vide
                    if (array_keys($professeursExistants) === range(0, count($professeursExistants) - 1)) {
                        // C'est un tableau indexé, réinitialiser
                        \Log::warning('⚠️ Professeurs existants était un tableau indexé, réinitialisation', [
                            'bulletin_id' => $bulletin->id,
                            'before' => $professeursExistants,
                        ]);
                        $professeursExistants = [];
                    }

                    // Fusion en préservant les clés matiere_id
                    $professeursFusionnes = $professeursExistants + $professeursMap;
                    // array_replace pour que les nouvelles valeurs écrasent les anciennes
                    $professeursFusionnes = array_replace($professeursExistants, $professeursMap);

                    // Encoder en JSON - préserver format objet même si toutes les clés sont nulles
                    $professeursJson = json_encode($professeursFusionnes);
                    // Si c'est un tableau vide [], le forcer en objet {}
                    if ($professeursJson === '[]') {
                        $professeursJson = '{}';
                    }

                    \Log::info('📝 Updating bulletin', [
                        'bulletin_id' => $bulletin->id,
                        'etudiant_id' => $etudiant->id,
                        'before' => $bulletin->professeurs,
                        'after' => $professeursJson,
                        'before_type' => gettype(json_decode($bulletin->professeurs)),
                        'after_type' => gettype(json_decode($professeursJson)),
                    ]);

                    // FORCE update avec Query Builder au lieu d'Eloquent pour éviter le cache
                    $affected = \DB::table('esbtp_bulletins')
                        ->where('id', $bulletin->id)
                        ->whereNull('archived_at')
                        ->update([
                            'professeurs' => $professeursJson,
                            'updated_by' => auth()->id(),
                            'updated_at' => now(),
                        ]);

                    \Log::info('📝 Update result', [
                        'bulletin_id' => $bulletin->id,
                        'affected_rows' => $affected,
                        'professeurs_json_length' => strlen($professeursJson),
                    ]);

                    $updated++;
                } else {
                    \Log::info('🆕 Created bulletin', [
                        'bulletin_id' => $bulletin->id,
                        'etudiant_id' => $etudiant->id,
                        'professeurs' => $bulletin->professeurs,
                    ]);
                    $created++;
                }
            }

            \DB::commit();

            \Log::info('✅ bulkUpdateProfesseurs - SUCCESS', [
                'created' => $created,
                'updated' => $updated,
                'total_students' => $etudiants->count(),
                'professeurs_count' => count($professeursMap),
            ]);

            return response()->json([
                'success' => true,
                'message' => '✅ Professeurs assignés avec succès pour '.count($professeursMap)." matière(s) - $created bulletin(s) créé(s), $updated bulletin(s) mis à jour",
                'stats' => [
                    'created_bulletins' => $created,
                    'updated_bulletins' => $updated,
                    'total_students' => $etudiants->count(),
                    'updated_matieres' => count($professeursMap),
                ],
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('❌ Erreur bulk update professeurs: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour des professeurs: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mise à jour groupée des absences
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdateAbsences(BulkUpdateAbsencesRequest $request)
    {
        \DB::beginTransaction();
        try {
            $updated = 0;
            $created = 0;
            $periode = 'semestre'.$request->semestre;

            foreach ($request->absences as $absenceData) {
                $justifiees = floatval($absenceData['absences_justifiees'] ?? 0);
                $nonJustifiees = floatval($absenceData['absences_non_justifiees'] ?? 0);

                if ($justifiees == 0 && $nonJustifiees == 0) {
                    continue;
                }

                // Calculer la note d'assiduité selon le barème
                $noteAssiduite = $this->bulletinService->resolveAttendanceNote($justifiees, $nonJustifiees);

                // Create or update bulletin avec les absences
                $bulletin = ESBTPBulletin::updateOrCreate(
                    [
                        'etudiant_id' => $absenceData['etudiant_id'],
                        'classe_id' => $request->classe_id,
                        'periode' => $periode,
                        'annee_universitaire_id' => $request->annee_universitaire_id,
                    ],
                    [
                        'absences_justifiees' => $justifiees,
                        'absences_non_justifiees' => $nonJustifiees,
                        'total_absences' => $justifiees + $nonJustifiees,
                        'note_assiduite' => $noteAssiduite,
                        'absences_type' => 'manuel',
                        'updated_by' => \Auth::id(),
                    ]
                );

                if ($bulletin->wasRecentlyCreated) {
                    $bulletin->created_by = \Auth::id();
                    $bulletin->save();
                    $created++;
                } else {
                    $updated++;
                }
            }

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => "✅ Absences mises à jour avec succès. ($created bulletins créés, $updated modifiés)",
                'stats' => [
                    'created' => $created,
                    'updated' => $updated,
                ],
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('❌ Erreur bulk update absences: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour des absences: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mise à jour groupée des matières (configuration)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdateMatieres(BulkUpdateMatieresRequest $request)
    {
        \DB::beginTransaction();
        try {
            $classe = ESBTPClasse::findOrFail($request->classe_id);
            $updated = 0;

            foreach ($request->matieres as $matiereData) {
                // Update pivot table coefficient
                $classe->matieres()->updateExistingPivot(
                    $matiereData['matiere_id'],
                    ['coefficient' => $matiereData['coefficient']]
                );
                $updated++;
            }

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => "✅ Configuration des matières mise à jour avec succès. ($updated matières modifiées)",
                'stats' => [
                    'updated' => $updated,
                ],
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('❌ Erreur bulk update matières: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour des matières: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mise à jour groupée des coefficients des matières
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdateCoefficients(BulkUpdateCoefficientsRequest $request)
    {
        \DB::beginTransaction();
        try {
            $classe = ESBTPClasse::findOrFail($request->classe_id);
            $updated = 0;

            foreach ($request->coefficients as $matiereId => $coefficient) {
                // Update pivot table coefficient
                $classe->matieres()->updateExistingPivot(
                    $matiereId,
                    ['coefficient' => floatval($coefficient)]
                );
                $updated++;
            }

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => "✅ Coefficients mis à jour avec succès. ($updated matières modifiées)",
                'stats' => [
                    'updated' => $updated,
                ],
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('❌ Erreur bulk update coefficients: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour des coefficients: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mise à jour groupée de la configuration des matières (coefficients + types d'enseignement)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdateMatieresConfig(BulkUpdateMatieresConfigRequest $request)
    {
        \DB::beginTransaction();
        try {
            $classe = ESBTPClasse::findOrFail($request->classe_id);
            $periode = 'semestre'.$request->semestre;
            $updatedCoeff = 0;
            $updatedTypes = 0;

            // Mise à jour des coefficients dans la table pivot esbtp_classe_matiere
            if ($request->has('coefficients') && is_array($request->coefficients)) {
                foreach ($request->coefficients as $matiereId => $coefficient) {
                    $classe->matieres()->updateExistingPivot(
                        $matiereId,
                        ['coefficient' => floatval($coefficient)]
                    );
                    $updatedCoeff++;
                }
            }

            // Mise à jour des types d'enseignement dans esbtp_config_matiere (même logique que saveConfigMatieresTypeFormation)
            if ($request->has('matiere_types') && is_array($request->matiere_types)) {
                foreach ($request->matiere_types as $matiereId => $type) {
                    if ($type === 'none') {
                        // Si "none", on supprime la config
                        ESBTPConfigMatiere::where([
                            'matiere_id' => $matiereId,
                            'classe_id' => $request->classe_id,
                            'periode' => $periode,
                            'annee_universitaire_id' => $request->annee_universitaire_id,
                        ])->forceDelete();

                        continue;
                    }

                    // Sinon on crée/met à jour la config avec type général ou technique
                    ESBTPConfigMatiere::withTrashed()->updateOrCreate(
                        [
                            'matiere_id' => $matiereId,
                            'classe_id' => $request->classe_id,
                            'periode' => $periode,
                            'annee_universitaire_id' => $request->annee_universitaire_id,
                        ],
                        [
                            'config' => json_encode(['type' => $type]),
                            'created_by' => \Auth::id(),
                            'updated_by' => \Auth::id(),
                            'deleted_at' => null,
                        ]
                    );
                    $updatedTypes++;
                }
            }

            \DB::commit();

            $messages = [];
            if ($updatedCoeff > 0) {
                $messages[] = "$updatedCoeff coefficient(s) modifié(s)";
            }
            if ($updatedTypes > 0) {
                $messages[] = "$updatedTypes type(s) d'enseignement configuré(s)";
            }

            $message = count($messages) > 0
                ? '✅ Configuration mise à jour : '.implode(', ', $messages)
                : '✅ Configuration mise à jour avec succès';

            return response()->json([
                'success' => true,
                'message' => $message,
                'stats' => [
                    'coefficients_updated' => $updatedCoeff,
                    'types_updated' => $updatedTypes,
                ],
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('❌ Erreur bulk update config matières: '.$e->getMessage(), [
                'request_data' => $request->all(),
                'exception' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la configuration: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Détermine la mention en fonction de la moyenne
     *
     * @param  float  $moyenne
     * @return string
     */
    /**
     * Calcule la moyenne générale d'un étudiant pour une classe, période et année universitaire données
     *
     * @param  int  $etudiant_id
     * @param  int  $classe_id
     * @param  string  $periode
     * @param  int  $annee_universitaire_id
     * @return float
     */
    /**
     * Prévisualise les moyennes d'un étudiant pour une classe, période et année universitaire données
     * Permet de modifier les moyennes avant génération du bulletin.
     *
     * @return \Illuminate\Http\Response
     */
    public function previewMoyennes(Request $request)
    {
        // Vérifier les permissions
        if (! Auth::check() || ! auth()->user()->can('resultats.export')) {
            abort(403, 'Vous n\'avez pas les permissions nécessaires pour modifier les moyennes.');
        }

        // Validation déjà faite par PreviewMoyennesRequest si besoin

        try {
            $etudiantId = $request->etudiant_id;
            $classeId = $request->classe_id;
            $periode = $request->periode;
            $anneeUniversitaireId = $request->annee_universitaire_id;

            // Si la période est vide, utiliser semestre1 comme valeur par défaut
            if (empty($periode)) {
                $periode = 'semestre1';
            }

            // Normaliser la période si nécessaire
            if ($periode == '1') {
                $periode = 'semestre1';
                $periodePourBDD = 'semestre1';
            } elseif ($periode == '2') {
                $periode = 'semestre2';
                $periodePourBDD = 'semestre2';
            } elseif (in_array($periode, ['semestre1', 'semestre2', 'annuel'])) {
                $periodePourBDD = $periode;
            } else {
                // Utiliser semestre1 comme valeur par défaut si la période n'est pas reconnue
                $periode = 'semestre1';
                $periodePourBDD = 'semestre1';
            }

            // Récupérer l'étudiant, la classe et l'année universitaire
            $etudiant = \App\Models\ESBTPEtudiant::findOrFail($etudiantId);
            $classe = \App\Models\ESBTPClasse::with('matieres')->findOrFail($classeId);
            $anneeUniversitaire = \App\Models\ESBTPAnneeUniversitaire::findOrFail($anneeUniversitaireId);

            // MODIFIÉ: Récupérer les notes de l'étudiant avec une requête plus flexible, similaire à resultatEtudiant
            // Récupérer toutes les notes de l'étudiant d'abord
            $notesQuery = \App\Models\ESBTPNote::where('etudiant_id', $etudiantId)
                ->with(['evaluation.matiere', 'matiere']);

            // Filtrer par période (semestre)
            $notesQuery->where(function ($q) use ($periodePourBDD) {
                $q->where('semestre', $periodePourBDD)
                    ->orWhereHas('evaluation', function ($query) use ($periodePourBDD) {
                        $query->where('periode', $periodePourBDD);
                    });
            });

            // MODIFIÉ: Utilisation du scope byClasse pour filtrer les notes par classe
            // Cela limite les notes aux évaluations de la classe spécifique demandée
            $notesQuery->byClasse($classeId);

            // MODIFIÉ: Filtrage par année universitaire pour inclure aussi l'année précédente
            // Utiliser le scope byAnneeUniversitaireWithPrevious qui permet de récupérer les notes
            // des évaluations de l'année courante (anneeUniversitaireId) ET de l'année précédente (anneeUniversitaireId-1)
            $notesQuery->byAnneeUniversitaireWithPrevious($anneeUniversitaireId);

            // Log pour le débogage - voir quelles notes sont récupérées
            \Log::debug("Notes query for student {$etudiantId}, class {$classeId}, period {$periodePourBDD}, year {$anneeUniversitaireId}");

            $notes = $notesQuery->get();

            // Log des notes récupérées
            foreach ($notes as $note) {
                \Log::debug("Note ID: {$note->id}, Value: {$note->note}, Evaluation ID: {$note->evaluation_id}, Evaluation Year: {$note->evaluation->annee_universitaire_id}, Matiere ID: {$note->evaluation->matiere_id}");
            }

            // Si aucune note n'est trouvée, vérifier s'il existe des notes dans l'année précédente uniquement
            if ($notes->isEmpty()) {
                \Log::debug('No notes found for current criteria. Checking previous year explicitly.');
                $prevYearId = $anneeUniversitaireId - 1;

                $prevNotesQuery = \App\Models\ESBTPNote::query()
                    ->where('etudiant_id', $etudiantId)
                    ->withValidEvaluation()
                    ->whereHas('evaluation', function ($query) use ($periodePourBDD, $classeId, $prevYearId) {
                        $query->where('classe_id', $classeId);
                        if ($periodePourBDD != 'annuel') {
                            $query->where('periode', $periodePourBDD);
                        }
                        $query->where('annee_universitaire_id', $prevYearId);
                    });

                $prevNotes = $prevNotesQuery->get();

                if ($prevNotes->isNotEmpty()) {
                    \Log::debug("Found notes in previous year {$prevYearId}");
                    $notes = $prevNotes;
                }
            }

            // Organiser les notes par matière
            $notesByMatiere = [];
            foreach ($notes as $note) {
                if (! $note->evaluation) {
                    \Log::debug("Skipping note ID {$note->id} - no evaluation");

                    continue;
                }
                $matiere = $note->evaluation->matiere;
                if (! $matiere) {
                    \Log::debug("Skipping note ID {$note->id} - no matiere for evaluation {$note->evaluation_id}");

                    continue;
                }

                $matiereId = $matiere->id;
                if (! isset($notesByMatiere[$matiereId])) {
                    $notesByMatiere[$matiereId] = [
                        'matiere' => $matiere,
                        'notes' => [],
                        'total_points' => 0,
                        'total_coefficients' => 0,
                        'moyenne' => 0,
                    ];
                }

                $notesByMatiere[$matiereId]['notes'][] = $note;
            }

            // Récupérer les résultats existants pour cet étudiant (exclure les soft-deleted)
            // Les soft-deleted doivent être définitivement supprimés avec forceDelete()
            $resultats = \App\Models\ESBTPResultat::where('etudiant_id', $etudiantId)
                ->where('classe_id', $classeId)
                ->where('periode', $periodePourBDD)
                ->where('annee_universitaire_id', $anneeUniversitaireId)
                ->with('matiere')
                ->get();

            // Préparer les données des résultats pour l'affichage et l'édition
            $resultatsData = [];
            foreach ($resultats as $resultat) {
                // Vérifier si la relation matiere existe
                if (! $resultat->matiere) {
                    // Si la relation n'existe pas, essayer de récupérer la matière directement
                    $matiere = \App\Models\ESBTPMatiere::find($resultat->matiere_id);

                    // Si la matière n'existe toujours pas, ignorer ce résultat
                    if (! $matiere) {
                        continue;
                    }
                } else {
                    $matiere = $resultat->matiere;
                }

                $resultatsData[$resultat->matiere_id] = [
                    'id' => $resultat->id,
                    'matiere' => $matiere,
                    'moyenne' => $resultat->moyenne,
                    'coefficient' => $this->bulletinService->getCoefficientForCombination(
                        $resultat->matiere_id,
                        $classeId,
                        $anneeUniversitaireId
                    ),
                    'rang' => $resultat->rang,
                    'appreciation' => $resultat->appreciation,
                ];
            }

            // Récupérer filière et niveau de la classe pour filtrer les matières
            $classeFiliereIdForNotes = $classe->filiere_id;
            $classeNiveauIdForNotes = $classe->niveau_etude_id;

            // Si des moyennes calculées n'ont pas de résultat correspondant, les ajouter
            // MAIS seulement si la matière correspond à la combinaison filière+niveau de la classe

            // Preload toutes les matières manquantes en une seule requête (évite N×3 requêtes)
            $missingMatiereIds = array_keys(array_diff_key($notesByMatiere, $resultatsData));
            $missingMatieres = $missingMatiereIds
                ? \App\Models\ESBTPMatiere::with(['filieres', 'niveaux'])->whereIn('id', $missingMatiereIds)->get()->keyBy('id')
                : collect();

            foreach ($notesByMatiere as $matiereId => $matiereData) {
                if (! isset($resultatsData[$matiereId])) {
                    $matiere = $missingMatieres->get($matiereId);

                    if (! $matiere) {
                        \Log::warning("Matiere with ID {$matiereId} not found when adding calculated averages - skipping");

                        continue; // Ignorer cette entrée si la matière n'existe pas
                    }

                    // Vérifier que la matière correspond à la combinaison filière+niveau de la classe
                    if (! $classeFiliereIdForNotes || ! $classeNiveauIdForNotes) {
                        \Log::warning("Classe {$classeId} missing filiere_id or niveau_etude_id - skipping matiere {$matiereId}");

                        continue;
                    }

                    $matchesFiliere = $matiere->filieres->pluck('id')->contains($classeFiliereIdForNotes);
                    $matchesNiveau = $matiere->niveaux->pluck('id')->contains($classeNiveauIdForNotes);

                    if (! $matchesFiliere || ! $matchesNiveau) {
                        \Log::debug("Matiere {$matiereId} ({$matiere->name}) skipped - does not match classe filiere/niveau combination");

                        continue; // Ignorer les matières qui ne correspondent pas à la combinaison
                    }

                    $resultatsData[$matiereId] = [
                        'id' => null,
                        'matiere' => $matiere, // Utiliser l'objet matière fraîchement récupéré
                        'moyenne' => $matiereData['moyenne'],
                        'coefficient' => $this->bulletinService->getCoefficientForCombination(
                            $matiereId,
                            $classeId,
                            $anneeUniversitaireId
                        ),
                        'rang' => null,
                        'appreciation' => null,
                    ];
                }
            }

            // Calculer la moyenne pour chaque matière
            foreach ($notesByMatiere as $matiereId => &$matiereData) {
                $totalPoints = 0;
                $totalCoefficients = 0;

                foreach ($matiereData['notes'] as $note) {
                    if ($note->evaluation && $note->evaluation->bareme > 0) {
                        $noteValue = is_numeric($note->note) ? floatval($note->note) : (is_numeric($note->valeur) ? floatval($note->valeur) : 0);
                        $bareme = floatval($note->evaluation->bareme);
                        $coefficient = $note->evaluation->coefficient ? floatval($note->evaluation->coefficient) : 1;

                        $normalized = ($noteValue / $bareme) * 20;
                        $totalPoints += $normalized * $coefficient;
                        $totalCoefficients += $coefficient;
                    }
                }

                $matiereData['total_points'] = $totalPoints;
                $matiereData['total_coefficients'] = $totalCoefficients;
                $matiereData['moyenne'] = $totalCoefficients > 0 ? $totalPoints / $totalCoefficients : 0;

            }

            // NOUVELLE LOGIQUE: Récupérer les matières basées sur la combinaison filière + niveau de la classe
            // même si l'étudiant n'a aucune évaluation/note
            $classeFiliereId = $classe->filiere_id;
            $classeNiveauId = $classe->niveau_etude_id;

            // Plus de blocage pour coefficients manquants - utiliser fallback = 1
            $toutesLesMatieres = \App\Models\ESBTPMatiere::with(['filieres:id,name,code', 'niveaux:id,name,code'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->filter(function ($matiere) use ($classeFiliereId, $classeNiveauId) {
                    if (! $classeFiliereId || ! $classeNiveauId) {
                        return false;
                    }

                    return $matiere->filieres->pluck('id')->contains($classeFiliereId)
                        && $matiere->niveaux->pluck('id')->contains($classeNiveauId);
                })
                ->values();

            // Ajouter les matières de la classe qui n'ont pas encore de résultats
            foreach ($toutesLesMatieres as $matiere) {
                if (! isset($resultatsData[$matiere->id])) {
                    // Vérifier si cette matière a des moyennes calculées depuis les évaluations
                    $moyenneCalculee = isset($notesByMatiere[$matiere->id]) ? $notesByMatiere[$matiere->id]['moyenne'] : null;
                    
                    // Récupérer le coefficient avec fallback = 1 si non configuré
                    try {
                        $coefficientCalcule = $this->bulletinService->getCoefficientForCombination(
                            $matiere->id,
                            $classe->id,
                            $anneeUniversitaire->id
                        );
                    } catch (\RuntimeException $exception) {
                        $coefficientCalcule = 1; // Fallback au lieu de bloquer
                    }

                    $resultatsData[$matiere->id] = [
                        'id' => null, // Nouveau résultat à créer
                        'matiere' => $matiere,
                        'moyenne' => $moyenneCalculee, // null si pas d'évaluations
                        'coefficient' => $coefficientCalcule,
                        'rang' => null,
                        'appreciation' => null,
                        'source' => $moyenneCalculee !== null ? 'calculee' : 'manuelle',
                    ];
                } else {
                    // Marquer la source des résultats existants
                    $moyenneCalculee = isset($notesByMatiere[$matiere->id]) ? $notesByMatiere[$matiere->id]['moyenne'] : null;
                    $resultatsData[$matiere->id]['source'] = $moyenneCalculee !== null ? 'calculee' : 'manuelle';
                }
            }

            // Trier les matières par nom pour un affichage cohérent
            uasort($resultatsData, function ($a, $b) {
                return strcasecmp($a['matiere']->name, $b['matiere']->name);
            });

            // Afficher la vue de prévisualisation des moyennes
            return view('esbtp.resultats.moyennes-preview', compact(
                'etudiant',
                'classe',
                'periode',
                'anneeUniversitaire',
                'notesByMatiere',
                'resultatsData'
            ));
        } catch (\RuntimeException $exception) {
            $periodeParam = isset($periode) ? str_replace('semestre', '', $periode) : '1';
            $redirectUrl = route('esbtp.resultats.etudiant', ['etudiant' => $etudiantId])
                . '?classe_id=' . ($classeId ?? '')
                . '&annee_universitaire_id=' . ($anneeUniversitaireId ?? '')
                . '&periode=' . $periodeParam
                . '&open_coeff_modal=1';

            return redirect($redirectUrl)
                ->with('error', $exception->getMessage().' Configurez les coefficients avant de continuer.');
        }
    }

    /**
     * Met à jour les moyennes des étudiants
     *
     * @return \Illuminate\Http\Response
     */
    public function updateMoyennes(UpdateMoyennesRequest $request)
    {
        // Vérifier les permissions
        if (! Auth::check() || ! auth()->user()->can('resultats.export')) {
            abort(403, 'Vous n\'avez pas les permissions nécessaires pour modifier les moyennes.');
        }

        $etudiantId = $request->etudiant_id;
        $classeId = $request->classe_id;
        $periode = $request->periode;
        $anneeUniversitaireId = $request->annee_universitaire_id;

        // Normaliser la période si nécessaire
        if ($periode == '1') {
            $periodePourBDD = 'semestre1';
        } elseif ($periode == '2') {
            $periodePourBDD = 'semestre2';
        } elseif (in_array($periode, ['semestre1', 'semestre2', 'annuel'])) {
            $periodePourBDD = $periode;
        } else {
            // Utiliser semestre1 comme valeur par défaut si la période n'est pas reconnue
            $periodePourBDD = 'semestre1';
        }

        // Récupérer l'étudiant, la classe et l'année universitaire
        $etudiant = \App\Models\ESBTPEtudiant::findOrFail($etudiantId);
        $classe = \App\Models\ESBTPClasse::findOrFail($classeId);
        $anneeUniversitaire = \App\Models\ESBTPAnneeUniversitaire::findOrFail($anneeUniversitaireId);

        // Traiter chaque résultat (si présents)
        if ($request->has('resultats') && is_array($request->resultats)) {
            foreach ($request->resultats as $resultatData) {
                $matiereId = $resultatData['matiere_id'];
                $moyenne = $resultatData['moyenne'];
                // Récupérer le coefficient depuis le formulaire (priorité haute)
                $coefficient = isset($resultatData['coefficient']) && $resultatData['coefficient'] > 0 
                    ? floatval($resultatData['coefficient']) 
                    : null;
                
                // Si pas de coefficient dans le formulaire, essayer de récupérer depuis la matière
                if ($coefficient === null) {
                    try {
                        $coefficient = $this->bulletinService->getCoefficientForCombination(
                            $matiereId,
                            $classeId,
                            $anneeUniversitaireId
                        );
                    } catch (\RuntimeException $exception) {
                        // Fallback: utiliser 1 comme valeur par défaut au lieu de bloquer
                        $coefficient = 1;
                        \Log::warning("Coefficient manquant pour matière {$matiereId}, utilisation du défaut: 1");
                    }
                }
                $appreciation = $resultatData['appreciation'] ?? null;
                $resultatId = $resultatData['id'] ?? null;

                // Si un ID de résultat est fourni, mettre à jour le résultat existant
                if ($resultatId) {
                    $resultat = \App\Models\ESBTPResultat::find($resultatId);
                    if ($resultat) {
                        $resultat->update([
                            'moyenne' => $moyenne,
                            'coefficient' => $coefficient,
                            'appreciation' => $appreciation,
                        ]);

                        continue;
                    }
                }

                // Sinon, créer un nouveau résultat
                \App\Models\ESBTPResultat::create([
                    'etudiant_id' => $etudiantId,
                    'classe_id' => $classeId,
                    'matiere_id' => $matiereId,
                    'periode' => $periodePourBDD,
                    'annee_universitaire_id' => $anneeUniversitaireId,
                    'moyenne' => $moyenne,
                    'coefficient' => $coefficient,
                    'appreciation' => $appreciation,
                ]);
            }
        }

        // NOUVELLE LOGIQUE: Traiter les nouvelles matières ajoutées dynamiquement
        if ($request->has('nouvelles_matieres') && is_array($request->nouvelles_matieres)) {
            foreach ($request->nouvelles_matieres as $nouvelleMatiereData) {
                $matiereType = $nouvelleMatiereData['matiere_type'];
                $moyenne = $nouvelleMatiereData['moyenne'];
                $coefficient = null;
                $appreciation = $nouvelleMatiereData['appreciation'] ?? null;

                if ($matiereType === 'existante') {
                    // Utiliser une matière existante
                    $matiereId = $nouvelleMatiereData['matiere_existante_id'];
                    $matiere = \App\Models\ESBTPMatiere::findOrFail($matiereId);

                    // Récupérer le coefficient depuis le formulaire (priorité haute)
                    $coefficient = isset($nouvelleMatiereData['coefficient']) && $nouvelleMatiereData['coefficient'] > 0 
                        ? floatval($nouvelleMatiereData['coefficient']) 
                        : null;
                    
                    // Si pas de coefficient dans le formulaire, essayer de récupérer depuis la matière
                    if ($coefficient === null) {
                        try {
                            $coefficient = $this->bulletinService->getCoefficientForCombination(
                                $matiereId,
                                $classeId,
                                $anneeUniversitaireId
                            );
                        } catch (\RuntimeException $exception) {
                            // Fallback: utiliser 1 comme valeur par défaut au lieu de bloquer
                            $coefficient = 1;
                            \Log::warning("Coefficient manquant pour matière existante {$matiereId}, utilisation du défaut: 1");
                        }
                    }

                    // Associer la matière à la classe si ce n'est pas déjà fait
                    if (! $classe->matieres->contains($matiere->id)) {
                        $classe->matieres()->attach($matiere->id);
                    }
                } elseif ($matiereType === 'nouvelle') {
                    // Créer une nouvelle matière
                    $nomMatiere = $nouvelleMatiereData['nom_nouvelle'];
                    $coefficient = $nouvelleMatiereData['coefficient'];
                    $matiere = \App\Models\ESBTPMatiere::firstOrCreate(
                        ['name' => $nomMatiere],
                        [
                            'code' => strtoupper(substr($nomMatiere, 0, 3)).'_'.time(),
                            'description' => 'Matière ajoutée manuellement via le bulletin',
                            'coefficient' => $coefficient,
                            'type_formation' => 'generale',
                            'is_active' => true,
                        ]
                    );

                    ESBTPMatiereCoefficient::updateOrCreate([
                        'matiere_id' => $matiere->id,
                        'filiere_id' => $classe->filiere_id,
                        'niveau_etude_id' => $classe->niveau_etude_id,
                        'annee_universitaire_id' => $anneeUniversitaireId,
                    ], [
                        'coefficient' => $coefficient,
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]);

                    // Associer la matière à la classe
                    if (! $classe->matieres->contains($matiere->id)) {
                        $classe->matieres()->attach($matiere->id);
                    }
                } else {
                    continue; // Type invalide, ignorer
                }

                // Créer le résultat pour cette matière
                \App\Models\ESBTPResultat::create([
                    'etudiant_id' => $etudiantId,
                    'classe_id' => $classeId,
                    'matiere_id' => $matiere->id,
                    'periode' => $periodePourBDD,
                    'annee_universitaire_id' => $anneeUniversitaireId,
                    'moyenne' => $moyenne,
                    'coefficient' => $coefficient,
                    'appreciation' => $appreciation,
                ]);
            }
        }

        // Rediriger vers la page des résultats de l'étudiant
        return redirect()->route('esbtp.resultats.etudiant', [
            'etudiant' => $etudiantId,
            'classe_id' => $classeId,
            'periode' => $periode, // Utiliser la période originale pour la redirection
            'annee_universitaire_id' => $anneeUniversitaireId,
        ])->with('success', 'Les moyennes ont été mises à jour avec succès.');
    }

    /**
     * Supprime une moyenne manuelle d'un étudiant
     *
     * @return \Illuminate\Http\Response
     */
    public function deleteMoyenne(DeleteMoyenneRequest $request)
    {
        // Vérifier les permissions
        if (! Auth::check() || ! auth()->user()->can('resultats.export')) {
            abort(403, 'Vous n\'avez pas les permissions nécessaires pour supprimer les moyennes.');
        }

        $etudiantId = $request->etudiant_id;
        $classeId = $request->classe_id;
        $matiereId = $request->matiere_id;
        $periode = $request->periode;
        $anneeUniversitaireId = $request->annee_universitaire_id;

        // Normaliser la période
        $periodePourBDD = in_array($periode, ['semestre1', 'semestre2', 'annuel']) ? $periode : 'semestre1';

        try {
            // Rechercher le résultat (inclure les soft deletes au cas où)
            $resultat = \App\Models\ESBTPResultat::withTrashed()->where([
                'etudiant_id' => $etudiantId,
                'classe_id' => $classeId,
                'matiere_id' => $matiereId,
                'periode' => $periodePourBDD,
                'annee_universitaire_id' => $anneeUniversitaireId,
            ])->first();

            if ($resultat) {
                $matiereName = $resultat->matiere->name ?? 'Inconnue';

                // Utiliser forceDelete() pour supprimer définitivement l'enregistrement
                // car nous utilisons SoftDeletes mais voulons une suppression permanente
                $resultat->forceDelete();

                return redirect()->back()->with('success', "La moyenne de la matière \"{$matiereName}\" a été supprimée définitivement.");
            } else {
                return redirect()->back()->with('error', 'Moyenne non trouvée ou déjà supprimée.');
            }

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression de la moyenne: '.$e->getMessage());

            return redirect()->back()->with('error', 'Une erreur est survenue lors de la suppression.');
        }
    }

    /**
     * Prévisualise le bulletin depuis la page résultats étudiant
     */
    public function previewBulletinEtudiantNew(Request $request, $etudiantId)
    {
        try {
            // Validation des paramètres
            $validator = Validator::make($request->all(), [
                'classe_id' => 'required|exists:esbtp_classes,id',
                'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
                'periode' => 'nullable|in:semestre1,semestre2,1,2',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            // Déterminer la période
            $periode = $request->periode ?? 'semestre1';
            if (in_array($periode, ['1', '2'])) {
                $periode = 'semestre'.$periode;
            }

            // Utiliser le service pour générer les données
            $donnees = $this->bulletinService->genererDonneesBulletin(
                $etudiantId,
                $request->classe_id,
                $request->annee_universitaire_id,
                $periode
            );

            // Ajouter le logo (la photo étudiant est déjà dans $donnees via le service)
            $logoBase64 = $this->bulletinService->prepareLogoBase64($donnees['settings']['school_logo'] ?? null);
            $donnees['logoBase64'] = $logoBase64;

            return view($this->bulletinService->getBulletinTemplateView(), $donnees);

        } catch (CoefficientMissingException $e) {
            $context = $this->buildCoefficientIssueContext($e->getContext(), $request);
            $message = $this->formatCoefficientIssueMessage($context);

            return redirect()->back()
                ->with('error', $message)
                ->with('coefficient_missing_context', $context);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Configuration bulletin manquante')) {
                $bulletin = ESBTPBulletin::where('etudiant_id', $etudiantId)
                    ->where('classe_id', $request->classe_id)
                    ->where('periode', $periode)
                    ->where('annee_universitaire_id', $request->annee_universitaire_id)
                    ->first();

                if ($bulletin && $bulletin->config_matieres && ! $bulletin->professeurs) {
                    return redirect()->route('esbtp.bulletins.edit-professeurs', [
                        'etudiant_id' => $etudiantId,
                        'classe_id' => $request->classe_id,
                        'periode' => $periode,
                        'annee_universitaire_id' => $request->annee_universitaire_id,
                    ])->with('error', 'Professeurs manquants. Veuillez les configurer avant la prévisualisation.');
                }

                $configMatieresUrl = route('esbtp.bulletins.config-matieres', [
                    'classe_id' => $request->classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $request->annee_universitaire_id,
                    'bulletin' => $etudiantId,
                ]);

                return redirect($configMatieresUrl)->with('error', $e->getMessage());
            }

            return redirect()->back()->with('error', 'Erreur lors de la génération de la preview : '.$e->getMessage());
        }
    }
    private function normalizeBtsPeriode(?string $rawPeriode): array
    {
        return match ($rawPeriode) {
            '1', 'semestre1' => ['periode' => 'semestre1', 'semestre' => '1'],
            '2', 'semestre2' => ['periode' => 'semestre2', 'semestre' => '2'],
            'annuel', '', null => ['periode' => 'annuel', 'semestre' => null],
            default => ['periode' => 'semestre1', 'semestre' => '1'],
        };
    }

    private function buildAnnualDetailUiState(string $periode, ?float $moyenneSemestre1, ?float $moyenneSemestre2, ?float $moyenneAnnuelle): array
    {
        $hasSemestre1 = $moyenneSemestre1 !== null;
        $hasSemestre2 = $moyenneSemestre2 !== null;
        $annualComplete = $periode === 'annuel' && $hasSemestre1 && $hasSemestre2 && $moyenneAnnuelle !== null;
        $annualIncomplete = $periode === 'annuel' && ! $annualComplete && ($hasSemestre1 || $hasSemestre2);
        $primarySemester = $hasSemestre1 ? 'semestre1' : ($hasSemestre2 ? 'semestre2' : null);
        $primarySemesterLabel = $primarySemester === 'semestre2' ? 'Semestre 2' : 'Semestre 1';
        $primaryAverage = $primarySemester === 'semestre2' ? $moyenneSemestre2 : $moyenneSemestre1;

        return [
            'state' => $annualComplete ? 'annual_complete' : ($annualIncomplete ? 'annual_incomplete' : 'standard'),
            'has_semestre1' => $hasSemestre1,
            'has_semestre2' => $hasSemestre2,
            'primary_semester' => $primarySemester,
            'primary_semester_label' => $primarySemester ? $primarySemesterLabel : null,
            'primary_average' => $primaryAverage,
            'display_average' => $periode === 'annuel'
                ? ($annualComplete ? $moyenneAnnuelle : $primaryAverage)
                : ($periode === 'semestre2' ? $moyenneSemestre2 : $moyenneSemestre1),
            'bulletin_workflow_periode' => $periode === 'annuel' ? ($primarySemester ?? 'semestre1') : $periode,
            'bulletin_workflow_periode_label' => $periode === 'annuel'
                ? ($primarySemesterLabel ?? 'Semestre 1')
                : ($periode === 'semestre2' ? 'Semestre 2' : 'Semestre 1'),
        ];
    }

    private function mapConsistencySubjectsToDetailNotes(array $subjects, Collection $notes): array
    {
        $mapped = [];

        foreach ($subjects as $subject) {
            $matiereId = $subject['matiere_id'] ?? null;
            if (! $matiereId) {
                continue;
            }

            $notesForSubject = $notes->filter(function ($note) use ($matiereId, $subject) {
                $noteMatiereId = $note->matiere_id ?: $note->evaluation?->matiere?->id;
                if ($noteMatiereId !== $matiereId) {
                    return false;
                }

                $evaluationIds = collect($subject['evaluations'] ?? [])->pluck('evaluation_id')->filter()->all();
                if (empty($evaluationIds)) {
                    return true;
                }

                return in_array($note->evaluation_id, $evaluationIds, true);
            })->values();

            $matiereModel = $notesForSubject->first()?->evaluation?->matiere;
            if (! $matiereModel) {
                $matiereModel = (object) [
                    'id' => $matiereId,
                    'name' => $subject['matiere'] ?? 'Matière inconnue',
                    'code' => null,
                ];
            }

            $mapped[$matiereId] = [
                'matiere' => $matiereModel,
                'notes' => $notesForSubject->all(),
                'calculations' => [],
                'total_points' => 0,
                'total_coefficients' => (float) ($subject['coefficient'] ?? 0),
                'moyenne' => (float) ($subject['moyenne'] ?? 0),
                'origin' => 'notes',
                'source' => ($subject['source'] ?? 'calculee') === 'manuelle' ? 'manuelle' : 'calculee',
            ];
        }

        return $mapped;
    }

    private function overlayConsistencySubjectLabels(array $notesByMatiere, array $subjects): array
    {
        foreach ($subjects as $subject) {
            $matiereId = $subject['matiere_id'] ?? null;
            if (! $matiereId || ! isset($notesByMatiere[$matiereId])) {
                continue;
            }

            $notesByMatiere[$matiereId]['matiere'] = (object) [
                'id' => $matiereId,
                'name' => $subject['matiere'] ?? ($notesByMatiere[$matiereId]['matiere']->name ?? 'Matière inconnue'),
                'code' => $notesByMatiere[$matiereId]['matiere']->code ?? null,
            ];
        }

        return $notesByMatiere;
    }
}
