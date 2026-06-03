<?php

namespace App\Http\Controllers;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereCoefficient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ESBTPEvaluationController extends Controller
{
    /**
     * Affiche la liste des évaluations.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Récupérer l'année universitaire courante
        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $anneeAcademique = $anneeCourante ? $anneeCourante->name : date('Y').'-'.(date('Y') + 1);
        $anneeUniversitaire = $anneeCourante;

        $query = ESBTPEvaluation::with(['classe', 'matiere', 'createdBy'])
            ->withCount('notes')
            ->orderBy('date_evaluation', 'desc');

        // Filtrer par année universitaire courante
        if ($anneeCourante) {
            $query->where('annee_universitaire_id', $anneeCourante->id);
        }

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('titre', 'like', '%'.$search.'%')
                    ->orWhereHas('classe', function ($classeQuery) use ($search) {
                        $classeQuery->where('name', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('matiere', function ($matiereQuery) use ($search) {
                        $matiereQuery->where('name', 'like', '%'.$search.'%');
                    });
            });
        }

        // Filtres
        if ($request->filled('classe_id')) {
            $query->where('classe_id', $request->input('classe_id'));
        }

        if ($request->filled('matiere_id')) {
            $query->where('matiere_id', $request->input('matiere_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('date_debut')) {
            $query->where('date_evaluation', '>=', $request->input('date_debut'));
        }

        if ($request->filled('date_fin')) {
            $query->where('date_evaluation', '<=', $request->input('date_fin'));
        }

        // Paginer les résultats
        $perPage = (int) $request->input('per_page', 15);
        $evaluations = $query->paginate($perPage)->appends($request->query());

        // Synchroniser les statuts automatiques pour les évaluations visibles
        $evaluations->each(function (ESBTPEvaluation $evaluation) {
            $evaluation->syncAutomaticStatus();
            $evaluation->loadMissing(['classe', 'matiere', 'createdBy']);
        });

        // Statistiques pour l'année courante uniquement
        $statsQuery = ESBTPEvaluation::query();
        if ($anneeCourante) {
            $statsQuery->where('annee_universitaire_id', $anneeCourante->id);
        }

        $totalEvaluations = (clone $statsQuery)->count();
        $evaluationsPubliees = (clone $statsQuery)->where('is_published', true)->count();
        $examens = (clone $statsQuery)->where('type', 'examen')->count();
        $devoirs = (clone $statsQuery)->where('type', 'devoir')->count();

        // Récupération des classes pour le filtre
        $classes = ESBTPClasse::where('is_active', true)->orderBy('name')->get();

        // Récupération des matières pour le filtre
        $matieres = ESBTPMatiere::orderBy('name')->get();

        // Récupération des types d'évaluation pour le filtre
        $types = ESBTPEvaluation::select('type')->distinct()->pluck('type');

        $filters = [
            'classe_id' => $request->input('classe_id'),
            'matiere_id' => $request->input('matiere_id'),
            'type' => $request->input('type'),
            'search' => $search,
            'date_debut' => $request->input('date_debut'),
            'date_fin' => $request->input('date_fin'),
            'per_page' => $perPage,
        ];

        $summary = [
            'counts' => [
                'total' => $totalEvaluations,
                'published' => $evaluationsPubliees,
                'examens' => $examens,
                'devoirs' => $devoirs,
                'filtered' => $evaluations->total(),
            ],
            'pagination' => [
                'first_item' => $evaluations->firstItem(),
                'last_item' => $evaluations->lastItem(),
                'total' => $evaluations->total(),
            ],
        ];

        // Pour les rôles non-enseignants, récupérer les évaluations pour la gestion des liens externes
        $evaluationsForExternalLinks = collect();
        $currentUser = \Auth::user();
        if (! $currentUser->hasAnyPermission(['identity.teach', 'identity.student'])) {
            $evaluationsForExternalLinks = ESBTPEvaluation::with(['classe', 'matiere'])
                ->where('is_published', true)
                ->whereDoesntHave('notes')
                ->whereNull('enseignant_id') // Sans enseignant assigné
                ->where(function ($query) {
                    $query->whereNull('token_saisie_externe') // Pas de token généré
                        ->orWhere('token_expire_at', '<', now()); // Token expiré
                })
                ->orderBy('date_evaluation', 'desc')
                ->get();
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html' => view('esbtp.evaluations.partials.results', [
                    'evaluations' => $evaluations,
                ])->render(),
                'summary' => $summary,
                'url' => $request->fullUrl(),
            ]);
        }

        return view('esbtp.evaluations.index', compact(
            'evaluations',
            'classes',
            'matieres',
            'types',
            'totalEvaluations',
            'evaluationsPubliees',
            'examens',
            'devoirs',
            'evaluationsForExternalLinks',
            'anneeAcademique',
            'anneeCourante',
            'anneeUniversitaire',
            'filters',
            'summary'
        ));
    }

    /**
     * Rafraîchit la ligne d'une évaluation pour un rendu AJAX.
     */
    public function refreshRow(ESBTPEvaluation $evaluation)
    {
        try {
            $evaluation->load(['classe', 'matiere', 'createdBy'])
                ->loadCount('notes');
            $evaluation->syncAutomaticStatus();

            return response()->json([
                'success' => true,
                'html' => view('esbtp.evaluations.partials.evaluation-row', [
                    'evaluation' => $evaluation,
                ])->render(),
            ]);
        } catch (\Throwable $throwable) {
            \Log::error('Erreur lors du rafraîchissement de l\'évaluation', [
                'evaluation_id' => $evaluation->id,
                'error' => $throwable->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Impossible de rafraîchir l\'évaluation demandée.',
            ], 500);
        }
    }

    /**
     * Annule une évaluation (statut -> annulé, dépublication).
     */
    public function cancel(Request $request, ESBTPEvaluation $evaluation)
    {
        if ($evaluation->status === ESBTPEvaluation::STATUS_CANCELLED) {
            return $this->evaluationActionResponse(
                $request,
                $evaluation,
                'Cette évaluation est déjà annulée.',
                'cancel'
            );
        }

        $evaluation->status = ESBTPEvaluation::STATUS_CANCELLED;
        $evaluation->is_published = false;
        $evaluation->updated_by = Auth::id();
        $evaluation->save();

        return $this->evaluationActionResponse(
            $request,
            $evaluation,
            'Évaluation annulée avec succès.',
            'cancel'
        );
    }

    /**
     * Réactive une évaluation annulée.
     */
    public function restore(Request $request, ESBTPEvaluation $evaluation)
    {
        $publish = $request->boolean('publish', true);

        $evaluation->is_published = $publish;
        $evaluation->status = $evaluation->determineAutomaticStatus(null, false);
        $evaluation->updated_by = Auth::id();
        $evaluation->save();

        return $this->evaluationActionResponse(
            $request,
            $evaluation,
            'Évaluation réactivée avec succès.',
            'restore'
        );
    }

    /**
     * Génère une réponse adaptée (JSON ou redirect) après une action sur l'évaluation.
     */
    protected function evaluationActionResponse(Request $request, ESBTPEvaluation $evaluation, string $message, string $action = 'update', int $status = 200)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'action' => $action,
                'evaluation_id' => $evaluation->id,
            ], $status);
        }

        return redirect()->route('esbtp.evaluations.index')->with('success', $message);
    }

    /**
     * Affiche le formulaire de création d'une évaluation.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        // Get the matiere_id and classe_id from the request
        $matiere_id = $request->input('matiere_id');
        $classe_id = $request->input('classe_id');

        // Suppression du bloc de redirection qui empêche la présélection de la matière
        // if ($matiere_id) {
        //     $matiere = ESBTPMatiere::findOrFail($matiere_id);
        //     $evaluationsCount = ESBTPEvaluation::where('matiere_id', $matiere_id)->count();
        //
        //     // If evaluations exist, redirect to the evaluations list filtered by this subject
        //     if ($evaluationsCount > 0) {
        //         return redirect()->route('esbtp.evaluations.index', ['matiere_id' => $matiere_id])
        //             ->with('info', 'Il existe déjà des évaluations pour cette matière. Vous pouvez en ajouter une nouvelle ici.');
        //     }
        // }

        $anneeUniversitaire = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $classes = ESBTPClasse::where('is_active', true)->orderBy('name')->get();
        $matieres = ESBTPMatiere::where('is_active', true)->orderBy('name')->get();
        $types = ESBTPEvaluation::getTypes();

        // Récupérer la liste des enseignants pour l'assignation (seulement pour les non-enseignants)
        $enseignants = collect();
        $currentUser = \Auth::user();
        if (! $currentUser->can('identity.teach')) {
            $enseignants = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['teacher', 'enseignant']);
            })->with('roles:id,name')->orderBy('name')->get();
        }

        // Prepare subjects for JavaScript
        $matieresJson = $matieres->map(function ($matiere) {
            return [
                'id' => $matiere->id,
                'name' => $matiere->nom ?? $matiere->name ?? 'Matière '.$matiere->id,
                'code' => $matiere->code ?? '',
                'coefficient' => $matiere->coefficient ?? 1,
            ];
        });

        return view('esbtp.evaluations.create', compact('classes', 'matieres', 'matieresJson', 'matiere_id', 'classe_id', 'types', 'enseignants', 'anneeUniversitaire'));
    }

    /**
     * Enregistre une nouvelle évaluation.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $isEmbedRequest = $request->boolean('embed') || $request->ajax() || $request->wantsJson();

        // Garde-fou critique : bareme strictement > 0 (sinon division par zéro
        // dans ESBTPNote::getNoteVingtAttribute), coefficient strictement borné.
        $validator = \Validator::make($request->all(), [
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|string|in:devoir,examen,projet,tp,controle,quiz,oral,cc',
            'date_evaluation' => 'required|date',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i|after:heure_debut',
            'classe_id' => 'required|exists:esbtp_classes,id',
            'matiere_id' => 'required|exists:esbtp_matieres,id',
            'bareme' => 'required|numeric|min:0.1|max:100',
            'coefficient' => 'required|numeric|min:0.1|max:10',
            'duree_minutes' => 'nullable|integer|min:1|max:480',
            'is_published' => 'nullable|boolean',
            'periode' => 'required|in:1,2,3,4,5,6,7,8,9,10,semestre1,semestre2,semestre3,semestre4,semestre5,semestre6,semestre7,semestre8,semestre9,semestre10,Semestre 1,Semestre 2,Semestre 3,Semestre 4,Semestre 5,Semestre 6,Semestre 7,Semestre 8,Semestre 9,Semestre 10',
        ], [
            'titre.required' => 'Le titre est obligatoire',
            'type.required' => 'Le type d\'évaluation est obligatoire',
            'type.in' => 'Le type d\'évaluation doit être valide',
            'date_evaluation.required' => 'La date est obligatoire',
            'date_evaluation.date' => 'Le format de la date est invalide',
            'classe_id.required' => 'La classe est obligatoire',
            'classe_id.exists' => 'La classe sélectionnée n\'existe pas',
            'matiere_id.required' => 'La matière est obligatoire',
            'matiere_id.exists' => 'La matière sélectionnée n\'existe pas',
            'bareme.required' => 'Le barème est obligatoire',
            'bareme.min' => 'Le barème doit être strictement supérieur à zéro.',
            'bareme.max' => 'Le barème ne peut pas dépasser 100.',
            'coefficient.required' => 'Le coefficient de l\'évaluation est obligatoire',
            'coefficient.numeric' => 'Le coefficient doit être un nombre',
            'coefficient.min' => 'Le coefficient doit être strictement supérieur à zéro.',
            'coefficient.max' => 'Le coefficient ne peut pas dépasser 10',
            'duree_minutes.max' => 'La durée ne peut pas dépasser 480 minutes (8h).',
        ]);

        if ($validator->fails()) {
            \Log::warning('ESBTPEvaluation@store validation failed', [
                'errors' => $validator->errors()->toArray(),
                'user_id' => auth()->id(),
            ]);

            if ($isEmbedRequest) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Récupérer l'année universitaire courante
            $anneeUniversitaire = ESBTPAnneeUniversitaire::where('is_current', true)->first();

            if (! $anneeUniversitaire) {
                \Log::error('Aucune année universitaire courante trouvée');

                return redirect()->back()
                    ->with('error', 'Aucune année universitaire courante n\'a été trouvée. Veuillez en créer une avant d\'ajouter une évaluation.')
                    ->withInput();
            }

            $startAt = Carbon::createFromFormat('Y-m-d H:i', $request->date_evaluation.' '.$request->heure_debut);
            $endAt = Carbon::createFromFormat('Y-m-d H:i', $request->date_evaluation.' '.$request->heure_fin);
            if ($endAt->lessThanOrEqualTo($startAt)) {
                $endAt = $endAt->addDay();
            }
            $calculatedDuration = $endAt->diffInMinutes($startAt);

$evaluation = new ESBTPEvaluation;
            $evaluation->titre = $request->titre;
            $evaluation->description = $request->description;
            $evaluation->type = $request->type;
            $evaluation->date_evaluation = $startAt;
            
            // Récupérer le coefficient depuis le formulaire (priorité haute)
            $coefficient = $request->input('coefficient');
            if (empty($coefficient) || $coefficient <= 0) {
                // Si pas de coefficient dans le formulaire, essayer de récupérer depuis la matière
                $coefficient = $this->getCoefficientForCombination($request->classe_id, $request->matiere_id, $anneeUniversitaire->id);
                if ($coefficient === null) {
                    // Fallback: utiliser 1 comme valeur par défaut au lieu de bloquer
                    $coefficient = 1;
                }
            }
            $evaluation->coefficient = (float) $coefficient;
            $evaluation->bareme = $request->bareme;
            $evaluation->duree_minutes = $request->filled('duree_minutes')
                ? (int) $request->duree_minutes
                : $calculatedDuration;
            if ($evaluation->duree_minutes <= 0) {
                $evaluation->duree_minutes = $calculatedDuration;
            }
            $evaluation->classe_id = $request->classe_id;
            $evaluation->matiere_id = $request->matiere_id;
            $evaluation->created_by = \Auth::id();
            $evaluation->is_published = $request->has('is_published') ? 1 : 0;
            $evaluation->status = $evaluation->is_published
                ? $evaluation->determineAutomaticStatus(null, false)
                : ESBTPEvaluation::STATUS_DRAFT;

            // Auto-assigner l'enseignant si c'est un rôle enseignant qui crée l'évaluation
            $user = \Auth::user();
            if ($user->can('identity.teach')) {
                $evaluation->enseignant_id = \Auth::id();
            } else {
                // Pour les autres rôles, utiliser l'assignation du formulaire
                if ($request->has('enseignant_id') && $request->enseignant_id) {
                    $evaluation->enseignant_id = $request->enseignant_id;
                } elseif ($request->has('enseignant_externe_nom') && $request->enseignant_externe_nom) {
                    $evaluation->enseignant_externe_nom = $request->enseignant_externe_nom;
                    if ($request->has('generer_lien_externe') && $request->generer_lien_externe) {
                        $evaluation->token_saisie_externe = \Str::random(64);
                        $evaluation->token_expire_at = now()->addDays(30);
                    }
                }
            }

            // Ajouter les valeurs par défaut pour les champs manquants
            $evaluation->periode = $request->periode ?? 'semestre1'; // Valeur par défaut pour periode
            $evaluation->annee_universitaire_id = $request->annee_universitaire_id ?? $anneeUniversitaire->id;

            \Log::info('Tentative de sauvegarde de l\'évaluation:', [
                'titre' => $evaluation->titre,
                'matiere_id' => $evaluation->matiere_id,
                'classe_id' => $evaluation->classe_id,
                'date_evaluation' => $evaluation->date_evaluation,
                'created_by' => $evaluation->created_by,
                'duree_minutes' => $evaluation->duree_minutes,
                'is_published' => $evaluation->is_published,
                'periode' => $evaluation->periode,
                'annee_universitaire_id' => $evaluation->annee_universitaire_id,
            ]);

            // Vérifier que la classe et la matière existent
            $classe = ESBTPClasse::find($evaluation->classe_id);
            $matiere = ESBTPMatiere::find($evaluation->matiere_id);

            \Log::info('Vérification de la classe et de la matière:', [
                'classe_exists' => $classe ? 'oui' : 'non',
                'classe_nom' => $classe ? ($classe->nom ?? $classe->name ?? 'N/A') : 'N/A',
                'matiere_exists' => $matiere ? 'oui' : 'non',
                'matiere_nom' => $matiere ? ($matiere->nom ?? $matiere->name ?? 'N/A') : 'N/A',
            ]);

            // Log aussi les attributs du modèle avant sauvegarde
            \Log::info('Attributs du modèle avant sauvegarde:', $evaluation->getAttributes());

            $evaluation->save();

            \Log::info('Évaluation créée avec succès. ID: '.$evaluation->id);

            $successMessage = 'L\'évaluation a été créée avec succès';
            $successMessagePlain = 'L\'évaluation a été créée avec succès';

            // Si un lien externe a été généré, l'ajouter au message
            if ($evaluation->token_saisie_externe) {
                $externalLink = route('external-grading.show', $evaluation->token_saisie_externe);
                $successMessage .= '. <br><strong>Lien de saisie externe généré :</strong><br>';
                $successMessage .= '<div class="mt-2"><input type="text" class="form-control" value="'.$externalLink.'" onclick="this.select()" readonly></div>';
                $successMessage .= '<small class="text-muted">Copiez ce lien et envoyez-le à l\'enseignant externe. Le lien expire le '.$evaluation->token_expire_at->format('d/m/Y à H:i').'</small>';
            }

            if ($isEmbedRequest) {
                return response()->json([
                    'success' => true,
                    'message' => $successMessagePlain,
                    'evaluation' => [
                        'id' => $evaluation->id,
                        'titre' => $evaluation->titre,
                        'bareme' => $evaluation->bareme,
                        'coefficient' => $evaluation->coefficient,
                        'type' => $evaluation->type,
                    ],
                ]);
            }

            return redirect()->route('esbtp.evaluations.index')
                ->with('success', $successMessage);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la création de l\'évaluation: '.$e->getMessage());
            \Log::error('Trace: '.$e->getTraceAsString());

            if ($isEmbedRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Une erreur est survenue lors de la création de l\'évaluation.',
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la création de l\'évaluation: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Affiche les détails d'une évaluation spécifique.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(ESBTPEvaluation $evaluation)
    {
        $evaluation->load(['classe', 'matiere', 'createdBy', 'updatedBy', 'notes.etudiant']);

        // Récupérer l'année universitaire courante
        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Récupérer tous les étudiants avec inscriptions actives sur l'année courante
        // ET workflow_step = etudiant_cree (exclut les pré-inscriptions / prospects).
        $etudiantsAnneeCourante = ESBTPEtudiant::whereHas('inscriptions', function ($query) use ($evaluation, $anneeCourante) {
            $query->where('classe_id', $evaluation->classe_id)
                ->where('status', 'active')
                ->where('workflow_step', 'etudiant_cree');
            if ($anneeCourante) {
                $query->where('annee_universitaire_id', $anneeCourante->id);
            }
        })
            ->orderBy('nom')
            ->get();

        // Filtrer les notes pour ne garder que celles des étudiants de l'année courante
        $etudiantsAnneeCouranteIds = $etudiantsAnneeCourante->pluck('id');
        $notesAnneeCourante = $evaluation->notes->whereIn('etudiant_id', $etudiantsAnneeCouranteIds);
        $etudiantsAvecNote = $notesAnneeCourante->pluck('etudiant_id')->toArray();

        // Récupérer les étudiants de l'année courante qui n'ont pas encore de note
        $etudiantsSansNote = $etudiantsAnneeCourante->whereNotIn('id', $etudiantsAvecNote);

        return view('esbtp.evaluations.show', compact('evaluation', 'etudiantsSansNote', 'notesAnneeCourante'));
    }

    /**
     * Affiche le formulaire de modification d'une évaluation.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(ESBTPEvaluation $evaluation)
    {
        $classes = ESBTPClasse::where('is_active', true)->orderBy('name')->get();
        $matieres = ESBTPMatiere::orderBy('name')->get();
        $types = ESBTPEvaluation::getTypes();
        $dateEval = $evaluation->date_evaluation ? Carbon::parse($evaluation->date_evaluation) : null;
        $heureDebut = $dateEval?->format('H:i');
        $heureFin = null;
        if ($dateEval) {
            $minutes = $evaluation->duree_minutes ?? 0;
            if ($minutes <= 0) {
                $minutes = 120;
            }
            $heureFin = $dateEval->copy()->addMinutes($minutes)->format('H:i');
        }

        return view('esbtp.evaluations.edit', compact('evaluation', 'classes', 'matieres', 'types', 'heureDebut', 'heureFin'));
    }

    /**
     * Met à jour une évaluation spécifique.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ESBTPEvaluation $evaluation)
    {
        try {
            // Garde-fou critique : bareme strictement > 0 (sinon division par zéro
            // dans ESBTPNote::getNoteVingtAttribute), coefficient strictement borné.
            $request->validate([
                'titre' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'type' => 'required|in:devoir,examen,projet,tp,controle,quiz,oral,cc',
                'date_evaluation' => 'required|date',
                'heure_debut' => 'required|date_format:H:i',
                'heure_fin' => 'required|date_format:H:i|after:heure_debut',
                'classe_id' => 'required|exists:esbtp_classes,id',
                'matiere_id' => 'required|exists:esbtp_matieres,id',
                'bareme' => 'required|numeric|min:0.1|max:100',
                'coefficient' => 'nullable|numeric|min:0.1|max:10',
                'duree_minutes' => 'nullable|integer|min:1|max:480',
                'periode' => 'required|in:1,2,3,4,5,6,7,8,9,10,semestre1,semestre2,semestre3,semestre4,semestre5,semestre6,semestre7,semestre8,semestre9,semestre10,Semestre 1,Semestre 2,Semestre 3,Semestre 4,Semestre 5,Semestre 6,Semestre 7,Semestre 8,Semestre 9,Semestre 10',
            ], [
                'titre.required' => 'Le titre est obligatoire',
                'type.required' => 'Le type d\'évaluation est obligatoire',
                'date_evaluation.required' => 'La date est obligatoire',
                'classe_id.required' => 'La classe est obligatoire',
                'matiere_id.required' => 'La matière est obligatoire',
                'bareme.required' => 'Le barème est obligatoire',
                'bareme.min' => 'Le barème doit être strictement supérieur à zéro.',
                'bareme.max' => 'Le barème ne peut pas dépasser 100.',
                'coefficient.min' => 'Le coefficient doit être strictement supérieur à zéro.',
                'coefficient.max' => 'Le coefficient ne peut pas dépasser 10.',
                'duree_minutes.max' => 'La durée ne peut pas dépasser 480 minutes (8h).',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('ESBTPEvaluation@update validation failed', [
                'errors' => $e->errors(),
                'evaluation_id' => $evaluation->id,
                'user_id' => auth()->id(),
            ]);
            throw $e; // Re-lancer l'exception pour que Laravel la gère normalement
        }

        try {
            $hasNotes = $evaluation->notes()->count() > 0;

            // Si l'évaluation a déjà des notes et que l'utilisateur essaie de changer la classe ou la matière
            if ($hasNotes && ($evaluation->classe_id != $request->classe_id || $evaluation->matiere_id != $request->matiere_id)) {
                return redirect()->back()
                    ->with('error', 'Impossible de modifier la classe ou la matière car des notes sont déjà associées à cette évaluation')
                    ->withInput();
            }

            $startAt = Carbon::createFromFormat('Y-m-d H:i', $request->date_evaluation.' '.$request->heure_debut);
            $endAt = Carbon::createFromFormat('Y-m-d H:i', $request->date_evaluation.' '.$request->heure_fin);
            if ($endAt->lessThanOrEqualTo($startAt)) {
                $endAt = $endAt->addDay();
            }
            $calculatedDuration = $endAt->diffInMinutes($startAt);

$evaluation->titre = $request->titre;
            $evaluation->description = $request->description;
            $evaluation->type = $request->type;
            $evaluation->date_evaluation = $startAt;
            $anneeUniversitaire = ESBTPAnneeUniversitaire::where('is_current', true)->first();
            
            // Récupérer le coefficient depuis le formulaire (priorité haute)
            $coefficient = $request->input('coefficient');
            if (empty($coefficient) || $coefficient <= 0) {
                // Si pas de coefficient dans le formulaire, essayer de récupérer depuis la matière
                $coefficient = $this->getCoefficientForCombination($request->classe_id, $request->matiere_id, $anneeUniversitaire?->id);
                if ($coefficient === null) {
                    // Fallback: utiliser 1 comme valeur par défaut au lieu de bloquer
                    $coefficient = 1;
                }
            }
            $evaluation->coefficient = (float) $coefficient;
            $evaluation->bareme = $request->bareme;
            $evaluation->duree_minutes = $request->filled('duree_minutes')
                ? (int) $request->duree_minutes
                : $calculatedDuration;
            if ($evaluation->duree_minutes <= 0) {
                $evaluation->duree_minutes = $calculatedDuration;
            }

            // Mettre à jour la classe et la matière uniquement s'il n'y a pas de notes
            if (! $hasNotes) {
                $evaluation->classe_id = $request->classe_id;
                $evaluation->matiere_id = $request->matiere_id;
            }

            $evaluation->updated_by = Auth::id();
            if ($evaluation->status !== ESBTPEvaluation::STATUS_CANCELLED) {
                $evaluation->status = $evaluation->is_published
                    ? $evaluation->determineAutomaticStatus(null, false)
                    : ESBTPEvaluation::STATUS_DRAFT;
            }
            $evaluation->save();

            return redirect()->route('esbtp.evaluations.show', $evaluation)
                ->with('success', 'L\'évaluation a été mise à jour avec succès');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la mise à jour de l\'évaluation: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Quick edit (titre + barème + coefficient seulement).
     * Utilisé par le modal de saisie de notes (PR #4 — édition rapide depuis l'en-tête de colonne).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function quickUpdate(Request $request, ESBTPEvaluation $evaluation)
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'bareme' => 'required|numeric|min:1|max:100',
            'coefficient' => 'required|numeric|min:0.1|max:10',
        ], [
            'titre.required' => 'Le titre est obligatoire.',
            'bareme.required' => 'Le barème est obligatoire.',
            'bareme.min' => 'Le barème doit être au moins 1.',
            'bareme.max' => 'Le barème ne peut pas dépasser 100.',
            'coefficient.required' => 'Le coefficient est obligatoire.',
            'coefficient.min' => 'Le coefficient doit être au moins 0,1.',
            'coefficient.max' => 'Le coefficient ne peut pas dépasser 10.',
        ]);

        try {
            $evaluation->fill([
                'titre' => trim($validated['titre']),
                'bareme' => (float) $validated['bareme'],
                'coefficient' => (float) $validated['coefficient'],
                'updated_by' => Auth::id(),
            ]);
            $evaluation->save();

            return response()->json([
                'success' => true,
                'evaluation' => [
                    'id' => $evaluation->id,
                    'titre' => $evaluation->titre,
                    'bareme' => (float) $evaluation->bareme,
                    'coefficient' => (float) $evaluation->coefficient,
                ],
                'message' => 'Évaluation mise à jour.',
            ]);
        } catch (\Throwable $e) {
            \Log::error('quickUpdate evaluation failed', [
                'evaluation_id' => $evaluation->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'évaluation.',
            ], 500);
        }
    }

    /**
     * Supprime une évaluation spécifique.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, ESBTPEvaluation $evaluation)
    {
        try {
            if (! $evaluation->isDeletable()) {
                $message = 'Cette évaluation ne peut pas être supprimée dans son état actuel.';

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                    ], 422);
                }

                return back()->with('error', $message);
            }

            $evaluationId = $evaluation->id;
            $evaluation->delete();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'deleted' => true,
                    'evaluation_id' => $evaluationId,
                    'message' => 'Évaluation supprimée avec succès.',
                ]);
            }

            return redirect()->route('esbtp.evaluations.index')->with('success', 'Évaluation supprimée avec succès.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la suppression: '.$e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Erreur lors de la suppression: '.$e->getMessage());
        }
    }

    /**
     * Affiche les examens de l'étudiant connecté.
     *
     * @return \Illuminate\Http\Response
     */
    public function etudiant(Request $request)
    {
        // Récupérer l'utilisateur connecté
        $user = Auth::user();

        // Récupérer l'étudiant associé à l'utilisateur
        $etudiant = $user->etudiant;

        if (! $etudiant) {
            return redirect()->route('dashboard')
                ->with('error', 'Votre compte utilisateur n\'est pas associé à un étudiant.');
        }

        // Récupérer la classe de l'étudiant
        $inscription = $etudiant->inscriptions()->where('statut', 'active')->first();

        if (! $inscription || ! $inscription->classe) {
            return redirect()->route('dashboard')
                ->with('error', 'Vous n\'êtes inscrit dans aucune classe pour le moment.');
        }

        $classe = $inscription->classe;

        // Récupérer les paramètres de filtre
        $anneeId = $request->input('annee_universitaire_id',
            ESBTPAnneeUniversitaire::where('is_current', true)->first()->id ?? null);
        $periode = $request->input('periode');
        $statut = $request->input('statut');

        // Initialiser la requête pour récupérer les évaluations
        $query = ESBTPEvaluation::with(['matiere', 'classe'])
            ->where('classe_id', $classe->id);

        // Filtrer par année universitaire
        if ($anneeId) {
            $query->where('annee_universitaire_id', $anneeId);
        }

        // Filtrer par période
        if ($periode) {
            $query->where('periode', $periode);
        }

        // Filtrer par statut
        if ($statut) {
            if ($statut === 'passees') {
                $query->where('date_evaluation', '<', now());
            } elseif ($statut === 'a_venir') {
                $query->where('date_evaluation', '>=', now());
            }
        }

        // Récupérer les évaluations paginées
        $evaluations = $query->orderBy('date_evaluation', 'asc')->paginate(10);

        // Récupérer toutes les années universitaires pour le filtre
        $anneesUniversitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();

        // Compter les évaluations passées et à venir
        $evaluationsPassees = ESBTPEvaluation::where('classe_id', $classe->id)
            ->where('date_evaluation', '<', now())->count();

        $evaluationsAVenir = ESBTPEvaluation::where('classe_id', $classe->id)
            ->where('date_evaluation', '>=', now())->count();

        // Prochaine évaluation
        $prochaineEvaluation = ESBTPEvaluation::where('classe_id', $classe->id)
            ->where('date_evaluation', '>=', now())
            ->orderBy('date_evaluation', 'asc')
            ->first();

        return view('esbtp.evaluations.etudiant', compact(
            'etudiant',
            'classe',
            'evaluations',
            'anneesUniversitaires',
            'anneeId',
            'periode',
            'statut',
            'evaluationsPassees',
            'evaluationsAVenir',
            'prochaineEvaluation'
        ));
    }

    public function studentEvaluations()
    {
        $student = Auth::user()->etudiant;

        if (! $student) {
            return redirect()->route('dashboard')
                ->with('error', 'Accès non autorisé.');
        }

        // 1. Récupérer l'année universitaire courante
        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        if (! $anneeCourante) {
            return view('etudiants.evaluations', [
                'evaluations' => collect([]),
                'anneeCourante' => null,
                'inscription' => null,
            ]);
        }

        // 2. Vérifier si l'étudiant a une inscription active pour l'année courante
        $inscription = $student->inscriptions()
            ->where('status', 'active')
            ->where('annee_universitaire_id', $anneeCourante->id)
            ->with(['classe.filiere', 'classe.niveauEtude', 'anneeUniversitaire'])
            ->first();

        if (! $inscription) {
            return view('etudiants.evaluations', [
                'evaluations' => collect([]),
                'anneeCourante' => $anneeCourante,
                'inscription' => null,
            ])->with('warning', 'Vous n\'avez pas d\'inscription active pour l\'année en cours. Veuillez contacter l\'administration.');
        }

        // 3. Récupérer les évaluations de l'année courante uniquement
        $evaluations = ESBTPEvaluation::with(['matiere', 'classe'])
            ->forStudent($student->id)
            ->where('is_published', true)
            ->where('annee_universitaire_id', $anneeCourante->id)
            ->whereIn('status', ['scheduled', 'in_progress', 'completed'])
            ->orderBy('date_evaluation', 'desc')
            ->get()
            ->groupBy('type');

        return view('etudiants.evaluations', compact('evaluations', 'anneeCourante', 'inscription'));
    }

    public function updateStatus(Request $request, ESBTPEvaluation $evaluation)
    {
        \Log::critical('🚨 UPDATE STATUS CALLED - Request received!', [
            'request_method' => $request->method(),
            'request_all' => $request->all(),
            'evaluation_id' => $evaluation->id,
            'url' => $request->fullUrl(),
            'user_id' => auth()->id(),
        ]);

        try {
            $validated = $request->validate([
                'status' => 'required|in:'.implode(',', [
                    ESBTPEvaluation::STATUS_DRAFT,
                    ESBTPEvaluation::STATUS_SCHEDULED,
                    ESBTPEvaluation::STATUS_IN_PROGRESS,
                    ESBTPEvaluation::STATUS_COMPLETED,
                    ESBTPEvaluation::STATUS_CANCELLED,
                ]),
            ]);

            $evaluation->update($validated);

            // Logique automatique de publication
            if ($validated['status'] === 'scheduled' && ! $evaluation->is_published) {
                $evaluation->update(['is_published' => true]);
                \Log::info('Évaluation automatiquement publiée lors de la planification', [
                    'evaluation_id' => $evaluation->id,
                ]);
            } elseif ($validated['status'] === 'cancelled') {
                $evaluation->update(['is_published' => false]);
                \Log::info('Évaluation automatiquement dépubliée lors de l\'annulation', [
                    'evaluation_id' => $evaluation->id,
                ]);
            }

            \Log::info('Statut mis à jour avec succès', [
                'evaluation_id' => $evaluation->id,
                'new_status' => $validated['status'],
                'is_published' => $evaluation->fresh()->is_published,
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Statut mis à jour avec succès',
                    'evaluation' => $evaluation,
                ]);
            }

            $statusLabels = [
                'draft' => 'Brouillon',
                'scheduled' => 'Planifiée',
                'in_progress' => 'En cours',
                'completed' => 'Terminée',
                'cancelled' => 'Annulée',
            ];

            $statusLabel = $statusLabels[$validated['status']] ?? $validated['status'];
            $message = "Statut de l'évaluation \"{$evaluation->titre}\" mis à jour : {$statusLabel}";

            if ($validated['status'] === 'scheduled' && $evaluation->fresh()->is_published) {
                $message .= ' (automatiquement publiée pour les étudiants)';
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour du statut', [
                'evaluation_id' => $evaluation->id,
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la mise à jour du statut',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Erreur lors de la mise à jour du statut');
        }
    }

    public function togglePublished(Request $request, ESBTPEvaluation $evaluation)
    {
        try {
            $isPublished = ! $evaluation->is_published;

            $evaluation->update([
                'is_published' => $isPublished,
                'notes_published' => $isPublished ? $evaluation->notes_published : false,
                'updated_by' => Auth::id(),
            ]);

            if ($evaluation->status !== ESBTPEvaluation::STATUS_CANCELLED) {
                $evaluation->syncAutomaticStatus();
            }

            $message = $evaluation->is_published
                ? 'Évaluation publiée avec succès.'
                : 'Évaluation dépubliée avec succès. Les notes ont été masquées.';

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'evaluation_id' => $evaluation->id,
                    'is_published' => (bool) $evaluation->is_published,
                ]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Une erreur est survenue lors de la modification de la publication.',
                ], 500);
            }

            return back()->with('error', 'Une erreur est survenue lors de la modification de la publication.');
        }
    }

    public function toggleNotesPublished(Request $request, ESBTPEvaluation $evaluation)
    {
        if (! $evaluation->canPublishNotes() && ! $evaluation->notes_published) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Les notes ne peuvent pas être publiées pour cette évaluation.',
                ], 422);
            }

            return back()->with('error', 'Les notes ne peuvent pas être publiées pour cette évaluation.');
        }

        try {
            if (! $evaluation->is_published && ! $evaluation->notes_published) {
                $evaluation->is_published = true;
                $evaluation->updated_by = Auth::id();
                if ($evaluation->status !== ESBTPEvaluation::STATUS_CANCELLED) {
                    $evaluation->syncAutomaticStatus();
                }
            }

            $evaluation->update([
                'notes_published' => ! $evaluation->notes_published,
                'updated_by' => Auth::id(),
            ]);

            $message = $evaluation->notes_published
                ? 'Notes publiées avec succès.'
                : 'Notes dépubliées avec succès.';

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'evaluation_id' => $evaluation->id,
                    'notes_published' => (bool) $evaluation->notes_published,
                ]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Une erreur est survenue lors de la modification de la publication des notes.',
                ], 500);
            }

            return back()->with('error', 'Une erreur est survenue lors de la modification de la publication des notes.');
        }
    }

    /**
     * Télécharge le PDF de l'évaluation (attachment).
     */
    public function generatePdf(ESBTPEvaluation $evaluation)
    {
        try {
            [$pdf, $filename] = $this->buildEvaluationPdf($evaluation);

            return $pdf->download($filename);
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la génération du PDF : '.$e->getMessage());
        }
    }

    /**
     * Aperçu inline du PDF de l'évaluation.
     */
    public function previewPdf(ESBTPEvaluation $evaluation)
    {
        try {
            [$pdf, $filename] = $this->buildEvaluationPdf($evaluation);

            return $pdf->stream($filename);
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la prévisualisation du PDF : '.$e->getMessage());
        }
    }

    /**
     * Construit le PDF d'une évaluation avec les notes des étudiants.
     * Retourne [PDF, filename].
     */
    private function buildEvaluationPdf(ESBTPEvaluation $evaluation): array
    {
        $evaluation->load(['classe', 'matiere', 'notes.etudiant']);

        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        $etudiants = ESBTPEtudiant::whereHas('inscriptions', function ($query) use ($evaluation, $anneeCourante) {
            $query->where('classe_id', $evaluation->classe_id)
                ->where('status', 'active')
                ->where('workflow_step', 'etudiant_cree');
            if ($anneeCourante) {
                $query->where('annee_universitaire_id', $anneeCourante->id);
            }
        })
            ->with(['notes' => function ($query) use ($evaluation) {
                $query->where('evaluation_id', $evaluation->id);
            }])
            ->orderBy('nom')
            ->get();

        $notesByEtudiant = $evaluation->notes
            ->whereIn('etudiant_id', $etudiants->pluck('id'))
            ->keyBy('etudiant_id');

        $etablissement = [
            'nom' => \App\Models\Setting::get('school_name', 'KLASSCI'),
            'adresse' => \App\Models\Setting::get('school_address', ''),
            'telephone' => \App\Models\Setting::get('school_phone', ''),
            'email' => \App\Models\Setting::get('school_email', ''),
            'logo' => \App\Models\Setting::get('school_logo', ''),
        ];

        $isBlank = false;
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'esbtp.notes.saisie-rapide-pdf',
            compact('evaluation', 'etudiants', 'anneeCourante', 'etablissement', 'notesByEtudiant', 'isBlank')
        );
        $pdf->setPaper('A4', 'portrait');

        $filename = 'evaluation_'.Str::slug($evaluation->titre).'_'.$evaluation->date_evaluation->format('d-m-Y').'.pdf';

        return [$pdf, $filename];
    }

    /**
     * Génère un lien externe temporaire pour une évaluation
     */
    public function generateExternalLink(Request $request, ESBTPEvaluation $evaluation)
    {
        $request->validate([
            'duree_heures' => 'required|integer|min:1|max:168', // Max 7 jours
            'enseignant_externe_nom' => 'nullable|string|max:255',
        ]);

        try {
            $evaluation->update([
                'token_saisie_externe' => \Str::random(64),
                'token_expire_at' => now()->addHours($request->duree_heures),
                'enseignant_externe_nom' => $request->enseignant_externe_nom,
            ]);

            $externalLink = route('external-grading.show', $evaluation->token_saisie_externe);

            return response()->json([
                'success' => true,
                'link' => $externalLink,
                'expires_at' => $evaluation->token_expire_at->format('d/m/Y à H:i'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du lien',
            ], 500);
        }
    }

    /**
     * Révoque un lien externe
     */
    public function revokeExternalLink(ESBTPEvaluation $evaluation)
    {
        try {
            $evaluation->update([
                'token_saisie_externe' => null,
                'token_expire_at' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Lien révoqué avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la révocation',
            ], 500);
        }
    }

    /**
     * Récupère les évaluations avec liens externes actifs
     */
    public function getActiveExternalLinks()
    {
        $evaluations = ESBTPEvaluation::whereNotNull('token_saisie_externe')
            ->where('token_expire_at', '>', now())
            ->with(['classe', 'matiere', 'createdBy'])
            ->orderBy('token_expire_at', 'asc')
            ->get()
            ->map(function ($eval) {
                return [
                    'id' => $eval->id,
                    'titre' => $eval->titre,
                    'classe' => $eval->classe->name ?? 'N/A',
                    'matiere' => $eval->matiere->name ?? 'N/A',
                    'enseignant_externe_nom' => $eval->enseignant_externe_nom,
                    'expires_at' => $eval->token_expire_at->format('d/m/Y H:i'),
                    'expires_in_hours' => round($eval->token_expire_at->diffInHours(now(), false), 1),
                    'link' => route('external-grading.show', $eval->token_saisie_externe),
                ];
            });

        return response()->json($evaluations);
    }

    /**
     * Charge les matières disponibles pour une classe via AJAX (combinaisons globales).
     * Pattern identique à attendances.create pour cohérence UX.
     *
     * @return \Illuminate\Http\Response
     */
    public function loadMatieres(Request $request)
    {
        \Log::info('📚 [AJAX] loadMatieres - Début', [
            'classe_id' => $request->input('classe_id'),
            'user_id' => \Auth::id(),
        ]);

        try {
            $classeId = $request->input('classe_id');

            if (! $classeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de classe manquant',
                ], 400);
            }

            $classe = ESBTPClasse::findOrFail($classeId);

            // Récupérer les matières disponibles via combinaisons STRICTES filière+niveau
            // (pivot esbtp_matiere_filiere_niveau). 2 whereHas séparés donnaient un OR-logic
            // sur les combinaisons → matière liée à GTP-1A OU GBAT-2A apparaissait à tort
            // comme liée à GTP-2A.
            $matieres = ESBTPMatiere::where('is_active', true)
                ->whereHas('liaisonsFilieresNiveaux', function ($q) use ($classe) {
                    $q->where('filiere_id', $classe->filiere_id)
                      ->where('niveau_etude_id', $classe->niveau_etude_id);
                })
                ->orderBy('name')
                ->get();

            \Log::info('✅ [AJAX] loadMatieres - Matières trouvées', [
                'classe_id' => $classeId,
                'classe_nom' => $classe->name,
                'filiere_id' => $classe->filiere_id,
                'niveau_id' => $classe->niveau_etude_id,
                'nb_matieres' => $matieres->count(),
            ]);

            // Générer les options HTML pour le select
            $options = '<option value="">-- Sélectionner une matière --</option>';
            foreach ($matieres as $matiere) {
                $matiereNom = $matiere->name ?? 'Matière '.$matiere->id;
                $matiereCode = $matiere->code ? ' ('.$matiere->code.')' : '';
                $options .= '<option value="'.$matiere->id.'">'.$matiereNom.$matiereCode.'</option>';
            }

            return response()->json([
                'success' => true,
                'options' => $options,
                'count' => $matieres->count(),
                'classe' => [
                    'id' => $classe->id,
                    'nom' => $classe->name,
                    'filiere' => $classe->filiere->name ?? 'N/A',
                    'niveau' => $classe->niveau->name ?? 'N/A',
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('❌ [AJAX] loadMatieres - Erreur', [
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des matières: '.$e->getMessage(),
            ], 500);
        }
    }

    public function coefficientsModal(Request $request)
    {
        try {
            if (! \Schema::hasTable('esbtp_matiere_coefficients')) {
                return response()->json([
                    'success' => false,
                    'message' => 'La table des coefficients est absente. Lancez la migration avant de configurer les coefficients.',
                ]);
            }

            $anneeUniversitaire = ESBTPAnneeUniversitaire::where('is_current', true)->first();
            if (! $anneeUniversitaire) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année universitaire courante trouvée.',
                ], 404);
            }

            $classes = ESBTPClasse::where('is_active', true)
                ->with(['filiere', 'niveau'])
                ->get();

            $combos = $classes
                ->filter(function ($classe) {
                    return $classe->filiere_id
                        && $classe->niveau_etude_id
                        && $classe->filiere
                        && $classe->niveau;
                })
                ->unique(function ($classe) {
                    return $classe->filiere_id.'-'.$classe->niveau_etude_id;
                })
                ->values();

            $cards = $combos->map(function ($classe) use ($anneeUniversitaire) {
                $filiere = $classe->filiere;
                $niveau = $classe->niveau;

                if (! $filiere || ! $niveau) {
                    return null;
                }

                $matieres = ESBTPMatiere::where('is_active', true)
                    ->whereHas('liaisonsFilieresNiveaux', function ($query) use ($filiere, $niveau) {
                        $query->where('filiere_id', $filiere->id)
                              ->where('niveau_etude_id', $niveau->id);
                    })
                    ->orderBy('name')
                    ->get();

                $coefficients = ESBTPMatiereCoefficient::where('filiere_id', $filiere->id)
                    ->where('niveau_etude_id', $niveau->id)
                    ->where('annee_universitaire_id', $anneeUniversitaire->id)
                    ->get()
                    ->keyBy('matiere_id');

                $configuredCount = 0;
                $matieresData = $matieres->map(function ($matiere) use ($coefficients, &$configuredCount) {
                    $coefficient = $coefficients[$matiere->id]->coefficient ?? null;
                    if ($coefficient !== null) {
                        $configuredCount++;
                    }

                    return [
                        'id' => $matiere->id,
                        'name' => $matiere->name,
                        'code' => $matiere->code,
                        'coefficient' => $coefficient,
                    ];
                });

                $totalCount = $matieres->count();
                $status = $totalCount === 0
                    ? 'empty'
                    : ($configuredCount === 0
                        ? 'missing'
                        : ($configuredCount === $totalCount ? 'complete' : 'partial'));

                return [
                    'filiere' => $filiere,
                    'niveau' => $niveau,
                    'matieres' => $matieresData,
                    'total' => $totalCount,
                    'configured' => $configuredCount,
                    'status' => $status,
                ];
            })->filter()->values();

            $html = view('esbtp.evaluations.partials.coefficients-modal', compact('cards', 'anneeUniversitaire'))->render();

            return response()->json([
                'success' => true,
                'html' => $html,
            ]);
        } catch (\Throwable $throwable) {
            \Log::error('Erreur chargement coefficients modal', [
                'error' => $throwable->getMessage(),
                'trace' => config('app.debug') ? $throwable->getTraceAsString() : null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur lors du chargement des coefficients.',
            ], 500);
        }
    }

    public function updateCoefficients(Request $request)
    {
        $validated = $request->validate([
            'filiere_id' => 'required|exists:esbtp_filieres,id',
            'niveau_etude_id' => 'required|exists:esbtp_niveau_etudes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'periode' => 'nullable|in:semestre1,semestre2',
            'coefficients' => 'required|array',
            'coefficients.*' => 'nullable|numeric|min:0.1',
        ]);

        // Sous-lot α : coefficients par-période (default S1 si absent)
        $periode = $validated['periode'] ?? 'semestre1';

        $saved = 0;
        foreach ($validated['coefficients'] as $matiereId => $value) {
            if ($value === null || $value === '') {
                ESBTPMatiereCoefficient::where('matiere_id', $matiereId)
                    ->where('filiere_id', $validated['filiere_id'])
                    ->where('niveau_etude_id', $validated['niveau_etude_id'])
                    ->where('annee_universitaire_id', $validated['annee_universitaire_id'])
                    ->where('periode', $periode)
                    ->delete();

                continue;
            }

            ESBTPMatiereCoefficient::updateOrCreate([
                'matiere_id' => $matiereId,
                'filiere_id' => $validated['filiere_id'],
                'niveau_etude_id' => $validated['niveau_etude_id'],
                'annee_universitaire_id' => $validated['annee_universitaire_id'],
                'periode' => $periode,
            ], [
                'coefficient' => $value,
                'updated_by' => Auth::id(),
                'created_by' => Auth::id(),
            ]);
            $saved++;
        }

        return response()->json([
            'success' => true,
            'message' => 'Coefficients enregistrés.',
            'saved' => $saved,
            'periode' => $periode,
        ]);
    }

    public function checkCoefficient(Request $request)
    {
        $validated = $request->validate([
            'classe_id' => 'required|exists:esbtp_classes,id',
            'matiere_id' => 'required|exists:esbtp_matieres,id',
        ]);

        $anneeUniversitaire = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        if (! $anneeUniversitaire) {
            return response()->json([
                'success' => false,
                'missing' => true,
                'message' => 'Aucune année universitaire courante trouvée.',
            ], 404);
        }

        $coefficient = $this->getCoefficientForCombination(
            $validated['classe_id'],
            $validated['matiere_id'],
            $anneeUniversitaire->id
        );

        if ($coefficient === null) {
            return response()->json([
                'success' => false,
                'missing' => true,
                'message' => 'Coefficient non configuré pour cette combinaison.',
                'config_url' => route('esbtp.evaluations.index', ['open_coefficients' => 1]),
            ]);
        }

        return response()->json([
            'success' => true,
            'coefficient' => $coefficient,
        ]);
    }

    private function getCoefficientForCombination($classeId, $matiereId, $anneeUniversitaireId)
    {
        $classe = ESBTPClasse::find($classeId);
        if (! $classe || ! $classe->filiere_id || ! $classe->niveau_etude_id) {
            return null;
        }

        $record = ESBTPMatiereCoefficient::where('matiere_id', $matiereId)
            ->where('filiere_id', $classe->filiere_id)
            ->where('niveau_etude_id', $classe->niveau_etude_id)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->first();

        return $record?->coefficient;
    }

    /**
     * Get evaluations for a specific class and subject (AJAX API)
     * Used in the new notes system for grid display
     *
     * @param Request $request
     * @param int $classId
     * @param int $matiereId
     * @return \Illuminate\Http\JsonResponse
     */
    public function byClassMatiere(Request $request, $classId, $matiereId)
    {
        try {
            \Log::info('📚 [API] byClassMatiere - Request received', [
                'class_id' => $classId,
                'matiere_id' => $matiereId,
                'user_id' => auth()->id(),
            ]);

            // Validate class exists
            $classe = ESBTPClasse::find($classId);
            if (!$classe) {
                return response()->json([
                    'success' => false,
                    'message' => 'Classe non trouvée.',
                ], 404);
            }

            // Validate subject exists
            $matiere = ESBTPMatiere::find($matiereId);
            if (!$matiere) {
                return response()->json([
                    'success' => false,
                    'message' => 'Matière non trouvée.',
                ], 404);
            }

            // Get current academic year
            $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();
            if (!$anneeCourante) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année universitaire active.',
                ], 400);
            }

            // Get evaluations for this class, subject, and academic year
            $evaluations = ESBTPEvaluation::with(['notes.etudiant'])
                ->where('classe_id', $classId)
                ->where('matiere_id', $matiereId)
                ->where('annee_universitaire_id', $anneeCourante->id)
                ->whereIn('status', ['scheduled', 'in_progress', 'completed'])
                ->orderBy('date_evaluation', 'desc')
                ->get()
                ->map(function ($evaluation) {
                    return [
                        'id' => $evaluation->id,
                        'titre' => $evaluation->titre,
                        'date_evaluation' => $evaluation->date_evaluation,
                        'bareme' => $evaluation->bareme,
                        'coefficient' => $evaluation->coefficient,
                        'type' => $evaluation->type,
                        'periode' => $evaluation->periode,
                        'notes' => $evaluation->notes->map(function ($note) {
                            return [
                                'id' => $note->id,
                                'etudiant_id' => $note->etudiant_id,
                                'note' => $note->note,
                                'is_absent' => $note->is_absent ?? false,
                                'observation' => $note->observation,
                            ];
                        })->keyBy('etudiant_id'),
                    ];
                });

            \Log::info('✅ [API] byClassMatiere - Success', [
                'class_id' => $classId,
                'class_name' => $classe->name,
                'matiere_id' => $matiereId,
                'matiere_name' => $matiere->name,
                'evaluation_count' => $evaluations->count(),
            ]);

            // Construire notesData au format attendu par le frontend :
            // { studentId: { evaluationId: noteValue, evaluationId_absent: true/false } }
            $notesMap = [];
            foreach ($evaluations as $eval) {
                foreach ($eval['notes'] as $etudiantId => $noteData) {
                    $notesMap[$etudiantId][$eval['id']] = $noteData['note'];
                    $notesMap[$etudiantId][$eval['id'] . '_absent'] = ($noteData['is_absent'] ?? false) ? true : false;
                }
            }

            return response()->json([
                'success' => true,
                'class' => [
                    'id' => $classe->id,
                    'name' => $classe->name,
                    'filiere' => $classe->filiere->name ?? null,
                    'niveau' => $classe->niveau->name ?? null,
                ],
                'matiere' => [
                    'id' => $matiere->id,
                    'name' => $matiere->name,
                    'code' => $matiere->code,
                ],
                'evaluations' => $evaluations,
                'notes' => $notesMap,
                'evaluation_count' => $evaluations->count(),
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ [API] byClassMatiere - Error', [
                'class_id' => $classId,
                'matiere_id' => $matiereId,
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des évaluations.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
