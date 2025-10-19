<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPEtudiant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\ESBTPAnneeUniversitaire;
use App\Services\ESBTPPDFService;
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
        $anneeAcademique = $anneeCourante ? $anneeCourante->name : date('Y') . '-' . (date('Y') + 1);

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
                $subQuery->where('titre', 'like', '%' . $search . '%')
                    ->orWhereHas('classe', function ($classeQuery) use ($search) {
                        $classeQuery->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('matiere', function ($matiereQuery) use ($search) {
                        $matiereQuery->where('name', 'like', '%' . $search . '%');
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
        if (!$currentUser->hasRole(['teacher', 'enseignant', 'etudiant'])) {
            $evaluationsForExternalLinks = ESBTPEvaluation::with(['classe', 'matiere'])
                ->where('is_published', true)
                ->where(function($query) {
                    $query->whereNull('enseignant_id') // Sans enseignant assigné
                          ->orWhere(function($subQuery) {
                              $subQuery->whereNull('token_saisie_externe') // Pas de token généré
                                       ->orWhere('token_expire_at', '<', now()); // Token expiré
                          });
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
        // Get the matiere_id from the request
        $matiere_id = $request->input('matiere_id');

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

        $classes = ESBTPClasse::where('is_active', true)->orderBy('name')->get();
        $matieres = ESBTPMatiere::where('is_active', true)->orderBy('name')->get();
        $types = ESBTPEvaluation::getTypes();
        
        // Récupérer la liste des enseignants pour l'assignation (seulement pour les non-enseignants)
        $enseignants = collect();
        $currentUser = \Auth::user();
        if (!$currentUser->hasRole(['teacher', 'enseignant'])) {
            $enseignants = User::whereHas('roles', function($query) {
                $query->whereIn('name', ['teacher', 'enseignant']);
            })->orderBy('name')->get();
        }

        // Prepare subjects for JavaScript
        $matieresJson = $matieres->map(function ($matiere) {
            return [
                'id' => $matiere->id,
                'name' => $matiere->nom ?? $matiere->name ?? 'Matière ' . $matiere->id,
                'code' => $matiere->code ?? '',
                'coefficient' => $matiere->coefficient ?? 1
            ];
        });

        return view('esbtp.evaluations.create', compact('classes', 'matieres', 'matieresJson', 'matiere_id', 'types', 'enseignants'));
    }

    /**
     * Enregistre une nouvelle évaluation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        \Log::info('🔍 ESBTPEvaluation STORE - Début de la méthode store');
        \Log::info('🔍 ESBTPEvaluation STORE - Données reçues:', [
            'request_all' => $request->all(),
            'periode_value' => $request->periode,
            'periode_type' => gettype($request->periode),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => auth()->id()
        ]);

        // Log l'état de la classe ESBTPEvaluation
        \Log::info('Attributs attendus dans ESBTPEvaluation:', [
            'fillable' => (new \App\Models\ESBTPEvaluation())->getFillable(),
            'colonnes_table' => \Schema::getColumnListing('esbtp_evaluations')
        ]);

        $validator = \Validator::make($request->all(), [
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:devoir,examen,projet,tp,controle,quiz',
            'date_evaluation' => 'required|date',
            'classe_id' => 'required|exists:esbtp_classes,id',
            'matiere_id' => 'required|exists:esbtp_matieres,id',
            'coefficient' => 'required|numeric|min:0',
            'bareme' => 'required|numeric|min:0',
            'duree_minutes' => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
            'periode' => 'required|in:1,2,semestre1,semestre2,Semestre 1,Semestre 2',
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
            'coefficient.required' => 'Le coefficient est obligatoire',
            'bareme.required' => 'Le barème est obligatoire',
        ]);

        if ($validator->fails()) {
            \Log::error('❌ ESBTPEvaluation STORE - Validation échouée:', [
                'errors' => $validator->errors()->toArray(),
                'periode_value' => $request->periode,
                'periode_type' => gettype($request->periode),
                'request_all' => $request->all(),
            ]);
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Récupérer l'année universitaire courante
            $anneeUniversitaire = ESBTPAnneeUniversitaire::where('is_current', true)->first();

            if (!$anneeUniversitaire) {
                \Log::error('Aucune année universitaire courante trouvée');
                return redirect()->back()
                    ->with('error', 'Aucune année universitaire courante n\'a été trouvée. Veuillez en créer une avant d\'ajouter une évaluation.')
                    ->withInput();
            }

            $evaluation = new ESBTPEvaluation();
            $evaluation->titre = $request->titre;
            $evaluation->description = $request->description;
            $evaluation->type = $request->type;
            $evaluation->date_evaluation = $request->date_evaluation;
            $evaluation->coefficient = $request->coefficient;
            $evaluation->bareme = $request->bareme;
            $evaluation->duree_minutes = $request->duree_minutes;
            $evaluation->classe_id = $request->classe_id;
            $evaluation->matiere_id = $request->matiere_id;
            $evaluation->created_by = \Auth::id();
            $evaluation->is_published = $request->has('is_published') ? 1 : 0;
            $evaluation->status = $evaluation->is_published
                ? $evaluation->determineAutomaticStatus(null, false)
                : ESBTPEvaluation::STATUS_DRAFT;
            
            // Auto-assigner l'enseignant si c'est un rôle enseignant qui crée l'évaluation
            $user = \Auth::user();
            if ($user->hasRole(['teacher', 'enseignant'])) {
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
                'annee_universitaire_id' => $evaluation->annee_universitaire_id
            ]);

            // Vérifier que la classe et la matière existent
            $classe = ESBTPClasse::find($evaluation->classe_id);
            $matiere = ESBTPMatiere::find($evaluation->matiere_id);

            \Log::info('Vérification de la classe et de la matière:', [
                'classe_exists' => $classe ? 'oui' : 'non',
                'classe_nom' => $classe ? ($classe->nom ?? $classe->name ?? 'N/A') : 'N/A',
                'matiere_exists' => $matiere ? 'oui' : 'non',
                'matiere_nom' => $matiere ? ($matiere->nom ?? $matiere->name ?? 'N/A') : 'N/A'
            ]);

            // Log aussi les attributs du modèle avant sauvegarde
            \Log::info('Attributs du modèle avant sauvegarde:', $evaluation->getAttributes());

            $evaluation->save();

            \Log::info('Évaluation créée avec succès. ID: ' . $evaluation->id);

            $successMessage = 'L\'évaluation a été créée avec succès';
            
            // Si un lien externe a été généré, l'ajouter au message
            if ($evaluation->token_saisie_externe) {
                $externalLink = route('external-grading.show', $evaluation->token_saisie_externe);
                $successMessage .= '. <br><strong>Lien de saisie externe généré :</strong><br>';
                $successMessage .= '<div class="mt-2"><input type="text" class="form-control" value="' . $externalLink . '" onclick="this.select()" readonly></div>';
                $successMessage .= '<small class="text-muted">Copiez ce lien et envoyez-le à l\'enseignant externe. Le lien expire le ' . $evaluation->token_expire_at->format('d/m/Y à H:i') . '</small>';
            }

            return redirect()->route('esbtp.evaluations.index')
                ->with('success', $successMessage);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la création de l\'évaluation: ' . $e->getMessage());
            \Log::error('Trace: ' . $e->getTraceAsString());

            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la création de l\'évaluation: ' . $e->getMessage())
                ->withInput();
        }
        \Log::info('Fin de la méthode store');
    }

    /**
     * Affiche les détails d'une évaluation spécifique.
     *
     * @param  \App\Models\ESBTPEvaluation  $evaluation
     * @return \Illuminate\Http\Response
     */
    public function show(ESBTPEvaluation $evaluation)
    {
        $evaluation->load(['classe', 'matiere', 'createdBy', 'updatedBy', 'notes.etudiant']);

        // Récupérer l'année universitaire courante
        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Récupérer tous les étudiants avec inscriptions actives sur l'année courante
        $etudiantsAnneeCourante = ESBTPEtudiant::whereHas('inscriptions', function($query) use ($evaluation, $anneeCourante) {
                $query->where('classe_id', $evaluation->classe_id)
                      ->where('status', 'active');
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
     * @param  \App\Models\ESBTPEvaluation  $evaluation
     * @return \Illuminate\Http\Response
     */
    public function edit(ESBTPEvaluation $evaluation)
    {
        $classes = ESBTPClasse::where('is_active', true)->orderBy('name')->get();
        $matieres = ESBTPMatiere::orderBy('name')->get();
        $types = ESBTPEvaluation::getTypes();

        return view('esbtp.evaluations.edit', compact('evaluation', 'classes', 'matieres', 'types'));
    }

    /**
     * Met à jour une évaluation spécifique.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ESBTPEvaluation  $evaluation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ESBTPEvaluation $evaluation)
    {
        // Log détaillé pour diagnostiquer l'erreur de validation
        \Log::info('🔍 ESBTPEvaluation UPDATE - Données reçues', [
            'request_all' => $request->all(),
            'periode_value' => $request->periode,
            'periode_type' => gettype($request->periode),
            'evaluation_id' => $evaluation->id,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => auth()->id()
        ]);

        try {
            $request->validate([
                'titre' => 'required|string|max:255',
                'description' => 'nullable|string',
                'type' => 'required|in:devoir,examen,projet,tp,controle',
                'date_evaluation' => 'required|date',
                'classe_id' => 'required|exists:esbtp_classes,id',
                'matiere_id' => 'required|exists:esbtp_matieres,id',
                'coefficient' => 'required|numeric|min:0',
                'bareme' => 'required|numeric|min:0',
                'duree_minutes' => 'nullable|integer|min:0',
                'periode' => 'required|in:1,2,semestre1,semestre2,Semestre 1,Semestre 2',
            ], [
            'titre.required' => 'Le titre est obligatoire',
            'type.required' => 'Le type d\'évaluation est obligatoire',
            'date_evaluation.required' => 'La date est obligatoire',
            'classe_id.required' => 'La classe est obligatoire',
            'matiere_id.required' => 'La matière est obligatoire',
            'coefficient.required' => 'Le coefficient est obligatoire',
            'bareme.required' => 'Le barème est obligatoire',
        ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('❌ ESBTPEvaluation UPDATE - Erreur de validation', [
                'errors' => $e->errors(),
                'periode_value' => $request->periode,
                'periode_type' => gettype($request->periode),
                'request_all' => $request->all(),
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

            $evaluation->titre = $request->titre;
            $evaluation->description = $request->description;
            $evaluation->type = $request->type;
            $evaluation->date_evaluation = $request->date_evaluation;
            $evaluation->coefficient = $request->coefficient;
            $evaluation->bareme = $request->bareme;
            $evaluation->duree_minutes = $request->duree_minutes;

            // Mettre à jour la classe et la matière uniquement s'il n'y a pas de notes
            if (!$hasNotes) {
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
                ->with('error', 'Une erreur est survenue lors de la mise à jour de l\'évaluation: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Supprime une évaluation spécifique.
     *
     * @param  \App\Models\ESBTPEvaluation  $evaluation
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, ESBTPEvaluation $evaluation)
    {
        try {
            if (!$evaluation->isDeletable()) {
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
                    'message' => 'Erreur lors de la suppression: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    /**
     * Affiche les examens de l'étudiant connecté.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function etudiant(Request $request)
    {
        // Récupérer l'utilisateur connecté
        $user = Auth::user();

        // Récupérer l'étudiant associé à l'utilisateur
        $etudiant = $user->etudiant;

        if (!$etudiant) {
            return redirect()->route('dashboard')
                ->with('error', 'Votre compte utilisateur n\'est pas associé à un étudiant.');
        }

        // Récupérer la classe de l'étudiant
        $inscription = $etudiant->inscriptions()->where('statut', 'active')->first();

        if (!$inscription || !$inscription->classe) {
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

        if (!$student) {
            return redirect()->route('dashboard')
                ->with('error', 'Accès non autorisé.');
        }

        // 1. Récupérer l'année universitaire courante
        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        if (!$anneeCourante) {
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

        if (!$inscription) {
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
            'user_id' => auth()->id()
        ]);

        try {
            $validated = $request->validate([
                'status' => 'required|in:' . implode(',', [
                    ESBTPEvaluation::STATUS_DRAFT,
                    ESBTPEvaluation::STATUS_SCHEDULED,
                    ESBTPEvaluation::STATUS_IN_PROGRESS,
                    ESBTPEvaluation::STATUS_COMPLETED,
                    ESBTPEvaluation::STATUS_CANCELLED,
                ])
            ]);

            $evaluation->update($validated);

            // Logique automatique de publication
            if ($validated['status'] === 'scheduled' && !$evaluation->is_published) {
                $evaluation->update(['is_published' => true]);
                \Log::info('Évaluation automatiquement publiée lors de la planification', [
                    'evaluation_id' => $evaluation->id
                ]);
            } elseif ($validated['status'] === 'cancelled') {
                $evaluation->update(['is_published' => false]);
                \Log::info('Évaluation automatiquement dépubliée lors de l\'annulation', [
                    'evaluation_id' => $evaluation->id
                ]);
            }

            \Log::info('Statut mis à jour avec succès', [
                'evaluation_id' => $evaluation->id,
                'new_status' => $validated['status'],
                'is_published' => $evaluation->fresh()->is_published
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Statut mis à jour avec succès',
                    'evaluation' => $evaluation
                ]);
            }

            $statusLabels = [
                'draft' => 'Brouillon',
                'scheduled' => 'Planifiée',
                'in_progress' => 'En cours',
                'completed' => 'Terminée',
                'cancelled' => 'Annulée'
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
                'error' => $e->getMessage()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la mise à jour du statut',
                    'error' => $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Erreur lors de la mise à jour du statut');
        }
    }

    public function togglePublished(Request $request, ESBTPEvaluation $evaluation)
    {
        try {
            $evaluation->update([
                'is_published' => !$evaluation->is_published,
                'updated_by' => Auth::id()
            ]);

            if ($evaluation->status !== ESBTPEvaluation::STATUS_CANCELLED) {
                $evaluation->syncAutomaticStatus();
            }

            $message = $evaluation->is_published
                ? 'Évaluation publiée avec succès.'
                : 'Évaluation dépubliée avec succès.';

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
        if (!$evaluation->canPublishNotes() && !$evaluation->notes_published) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Les notes ne peuvent pas être publiées pour cette évaluation.',
                ], 422);
            }

            return back()->with('error', 'Les notes ne peuvent pas être publiées pour cette évaluation.');
        }

        try {
            $evaluation->update([
                'notes_published' => !$evaluation->notes_published,
                'updated_by' => Auth::id()
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
     * Génère un PDF de l'évaluation avec les notes des étudiants
     *
     * @param  \App\Models\ESBTPEvaluation  $evaluation
     * @return \Illuminate\Http\Response
     */
    public function generatePdf(ESBTPEvaluation $evaluation)
    {
        try {
            // Vérifier les autorisations
            $this->authorize('view', $evaluation);

            // Récupérer le service PDF
            $pdfService = app(ESBTPPDFService::class);

            // Générer le PDF
            $pdf = $pdfService->genererEvaluationPDF($evaluation);

            // Générer un nom de fichier basé sur le titre de l'évaluation
            $filename = 'evaluation_' . Str::slug($evaluation->titre) . '_' . $evaluation->date_evaluation->format('d-m-Y') . '.pdf';

            // Retourner le PDF en téléchargement
            return $pdf->download($filename);
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la génération du PDF : ' . $e->getMessage());
        }
    }

    /**
     * Génère un lien externe temporaire pour une évaluation
     */
    public function generateExternalLink(Request $request, ESBTPEvaluation $evaluation)
    {
        $request->validate([
            'duree_heures' => 'required|integer|min:1|max:168', // Max 7 jours
            'enseignant_externe_nom' => 'nullable|string|max:255'
        ]);

        try {
            $evaluation->update([
                'token_saisie_externe' => \Str::random(64),
                'token_expire_at' => now()->addHours($request->duree_heures),
                'enseignant_externe_nom' => $request->enseignant_externe_nom
            ]);

            $externalLink = route('external-grading.show', $evaluation->token_saisie_externe);
            
            return response()->json([
                'success' => true,
                'link' => $externalLink,
                'expires_at' => $evaluation->token_expire_at->format('d/m/Y à H:i')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du lien'
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
                'token_expire_at' => null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Lien révoqué avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la révocation'
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
                    'link' => route('external-grading.show', $eval->token_saisie_externe)
                ];
            });

        return response()->json($evaluations);
    }

    /**
     * Charge les matières disponibles pour une classe via AJAX (combinaisons globales).
     * Pattern identique à attendances.create pour cohérence UX.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function loadMatieres(Request $request)
    {
        \Log::info('📚 [AJAX] loadMatieres - Début', [
            'classe_id' => $request->input('classe_id'),
            'user_id' => \Auth::id()
        ]);

        try {
            $classeId = $request->input('classe_id');

            if (!$classeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de classe manquant'
                ], 400);
            }

            $classe = ESBTPClasse::findOrFail($classeId);

            // Récupérer les matières disponibles via combinaisons globales (filière + niveau)
            // Même logique que API LMS et classes/matieres.blade.php
            $matieres = ESBTPMatiere::where('is_active', true)
                ->whereHas('filieres', function ($q) use ($classe) {
                    $q->where('esbtp_filieres.id', $classe->filiere_id);
                })
                ->whereHas('niveaux', function ($q) use ($classe) {
                    $q->where('esbtp_niveau_etudes.id', $classe->niveau_etude_id);
                })
                ->orderBy('nom')
                ->get();

            \Log::info('✅ [AJAX] loadMatieres - Matières trouvées', [
                'classe_id' => $classeId,
                'classe_nom' => $classe->name,
                'filiere_id' => $classe->filiere_id,
                'niveau_id' => $classe->niveau_etude_id,
                'nb_matieres' => $matieres->count()
            ]);

            // Générer les options HTML pour le select
            $options = '<option value="">-- Sélectionner une matière --</option>';
            foreach ($matieres as $matiere) {
                $matiereNom = $matiere->nom ?? $matiere->name ?? 'Matière ' . $matiere->id;
                $matiereCode = $matiere->code ? ' (' . $matiere->code . ')' : '';
                $options .= '<option value="' . $matiere->id . '">' . $matiereNom . $matiereCode . '</option>';
            }

            return response()->json([
                'success' => true,
                'options' => $options,
                'count' => $matieres->count(),
                'classe' => [
                    'id' => $classe->id,
                    'nom' => $classe->name,
                    'filiere' => $classe->filiere->name ?? 'N/A',
                    'niveau' => $classe->niveau->name ?? 'N/A'
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('❌ [AJAX] loadMatieres - Erreur', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des matières: ' . $e->getMessage()
            ], 500);
        }
    }
}
