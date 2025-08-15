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
    public function index()
    {
        $query = ESBTPEvaluation::with(['classe', 'matiere', 'createdBy'])
            ->orderBy('date_evaluation', 'desc');

        // Filtres
        if (request()->has('classe_id') && request('classe_id') != '') {
            $query->where('classe_id', request('classe_id'));
        }

        if (request()->has('matiere_id') && request('matiere_id') != '') {
            $query->where('matiere_id', request('matiere_id'));
        }

        if (request()->has('type') && request('type') != '') {
            $query->where('type', request('type'));
        }

        if (request()->has('is_published') && request('is_published') != '') {
            $query->where('is_published', request('is_published'));
        }

        if (request()->has('date_debut') && request('date_debut') != '') {
            $query->where('date_evaluation', '>=', request('date_debut'));
        }

        if (request()->has('date_fin') && request('date_fin') != '') {
            $query->where('date_evaluation', '<=', request('date_fin'));
        }

        // Paginer les résultats
        $evaluations = $query->paginate(15);

        // Statistiques
        $totalEvaluations = ESBTPEvaluation::count();
        $evaluationsPubliees = ESBTPEvaluation::where('is_published', true)->count();
        $examens = ESBTPEvaluation::where('type', 'examen')->count();
        $devoirs = ESBTPEvaluation::where('type', 'devoir')->count();

        // Récupération des classes pour le filtre
        $classes = ESBTPClasse::where('is_active', true)->orderBy('name')->get();

        // Récupération des matières pour le filtre
        $matieres = ESBTPMatiere::orderBy('name')->get();

        // Récupération des types d'évaluation pour le filtre
        $types = ESBTPEvaluation::select('type')->distinct()->pluck('type');

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

        return view('esbtp.evaluations.index', compact(
            'evaluations',
            'classes',
            'matieres',
            'types',
            'totalEvaluations',
            'evaluationsPubliees',
            'examens',
            'devoirs',
            'evaluationsForExternalLinks'
        ));
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
        \Log::info('Début de la méthode store');
        \Log::info('Données reçues:', $request->all());

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
            'periode' => 'required|string|max:50',
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
            \Log::warning('Validation échouée:', $validator->errors()->toArray());
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Récupérer l'année universitaire active
            $anneeUniversitaire = ESBTPAnneeUniversitaire::where('is_active', true)->first();

            if (!$anneeUniversitaire) {
                \Log::error('Aucune année universitaire active trouvée');
                return redirect()->back()
                    ->with('error', 'Aucune année universitaire active n\'a été trouvée. Veuillez en créer une avant d\'ajouter une évaluation.')
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

        // Récupérer les étudiants qui n'ont pas encore de note pour cette évaluation
        $etudiantsAvecNote = $evaluation->notes->pluck('etudiant_id')->toArray();
        $etudiantsSansNote = ESBTPEtudiant::whereHas('inscriptions', function($query) use ($evaluation) {
                $query->where('classe_id', $evaluation->classe_id);
            })
            ->whereNotIn('id', $etudiantsAvecNote)
            ->orderBy('nom')
            ->get();

        return view('esbtp.evaluations.show', compact('evaluation', 'etudiantsSansNote'));
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
            'periode' => 'required|string|max:50',
        ], [
            'titre.required' => 'Le titre est obligatoire',
            'type.required' => 'Le type d\'évaluation est obligatoire',
            'date_evaluation.required' => 'La date est obligatoire',
            'classe_id.required' => 'La classe est obligatoire',
            'matiere_id.required' => 'La matière est obligatoire',
            'coefficient.required' => 'Le coefficient est obligatoire',
            'bareme.required' => 'Le barème est obligatoire',
        ]);

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
    public function destroy(ESBTPEvaluation $evaluation)
    {
        try {
            $evaluation->delete();
            return redirect()->route('esbtp.evaluations.index')->with('success', 'Évaluation supprimée avec succès.');
        } catch (\Exception $e) {
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

        $evaluations = ESBTPEvaluation::with(['matiere', 'classe'])
            ->forStudent($student->id)
            ->orderBy('date_evaluation', 'desc')
            ->get()
            ->groupBy('type');

        return view('etudiants.evaluations', compact('evaluations'));
    }

    public function updateStatus(Request $request, ESBTPEvaluation $evaluation)
    {
        \Log::info('Début updateStatus', [
            'request_method' => $request->method(),
            'request_all' => $request->all(),
            'evaluation_id' => $evaluation->id
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

            \Log::info('Statut mis à jour avec succès', [
                'evaluation_id' => $evaluation->id,
                'new_status' => $validated['status']
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Statut mis à jour avec succès',
                    'evaluation' => $evaluation
                ]);
            }

            return redirect()->back()->with('success', 'Statut mis à jour avec succès');
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

    public function togglePublished(ESBTPEvaluation $evaluation)
    {
        try {
            $evaluation->update([
                'is_published' => !$evaluation->is_published,
                'updated_by' => Auth::id()
            ]);

            $message = $evaluation->is_published
                ? 'Évaluation publiée avec succès.'
                : 'Évaluation dépubliée avec succès.';

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Une erreur est survenue lors de la modification de la publication.');
        }
    }

    public function toggleNotesPublished(ESBTPEvaluation $evaluation)
    {
        if (!$evaluation->canPublishNotes() && !$evaluation->notes_published) {
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

            return back()->with('success', $message);
        } catch (\Exception $e) {
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
}
