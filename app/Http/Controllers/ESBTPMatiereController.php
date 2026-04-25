<?php

namespace App\Http\Controllers;

use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class ESBTPMatiereController extends Controller
{
    /**
     * Affiche la liste des matières.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $listing = $this->prepareMatieresListing($request);

        $filieres = ESBTPFiliere::where('is_active', true)->orderBy('name')->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)
            ->whereNotIn('type', ['Licence', 'Master', 'Doctorat']) // Exclure niveaux LMD
            ->orderBy('name')->get();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('esbtp.matieres.partials.results', [
                    'matieres' => $listing['matieres'],
                ])->render(),
                'url' => $request->fullUrl(),
                'summary' => $listing['summary'],
                'kpis' => $listing['kpis'],
            ]);
        }

        return view('esbtp.matieres.index', [
            'matieres' => $listing['matieres'],
            'filieres' => $filieres,
            'niveaux' => $niveaux,
            'summary' => $listing['summary'],
            'kpis' => $listing['kpis'],
            'filters' => [
                'search' => $request->input('search', ''),
                'filiere_filter' => $request->input('filiere_filter'),
                'niveau_filter' => $request->input('niveau_filter'),
                'statut_filter' => $request->input('statut_filter'),
                'coefficient_min' => $request->input('coefficient_min'),
                'coefficient_max' => $request->input('coefficient_max'),
                'heures_min' => $request->input('heures_min'),
                'heures_max' => $request->input('heures_max'),
            ],
        ]);
    }

    /**
     * Rafraîchit la liste des matières sans recharger toute la page.
     */
    public function refresh(Request $request)
    {
        $listing = $this->prepareMatieresListing($request);

        $navUrl = route('esbtp.matieres.index');
        if ($request->getQueryString()) {
            $navUrl .= '?'.$request->getQueryString();
        }

        return response()->json([
            'html' => view('esbtp.matieres.partials.results', [
                'matieres' => $listing['matieres'],
            ])->render(),
            'url' => $navUrl,
            'summary' => $listing['summary'],
            'kpis' => $listing['kpis'],
        ]);
    }

    /**
     * Rafraîchit uniquement la ligne d'une matière.
     */
    public function refreshLigne(ESBTPMatiere $matiere)
    {
        try {
            $matiere->load([
                'filieres:id,name,code',
                'niveaux:id,name,code',
                'liaisonsFilieresNiveaux.filiere:id,name,code',
                'liaisonsFilieresNiveaux.niveauEtude:id,name,code',
            ]);

            return response()->json([
                'success' => true,
                'html' => view('esbtp.matieres.partials.matiere-row', [
                    'matiere' => $matiere,
                ])->render(),
                'matiere_id' => $matiere->id,
            ]);
        } catch (\Throwable $throwable) {
            \Log::error('Erreur lors du rafraîchissement de la matière', [
                'matiere_id' => $matiere->id,
                'error' => $throwable->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Impossible de rafraîchir la matière demandée.',
            ], 500);
        }
    }

    /**
     * Prépare la requête paginée des matières avec les filtres applicables.
     *
     * @return array{matieres:\Illuminate\Contracts\Pagination\LengthAwarePaginator,summary:array<string,int|null>}
     */
    private function prepareMatieresListing(Request $request): array
    {
        $search = trim((string) $request->input('search'));
        $filiere = $request->input('filiere_filter');
        $niveau = $request->input('niveau_filter');
        $statut = $request->input('statut_filter');
        $coefficientMin = $request->input('coefficient_min');
        $coefficientMax = $request->input('coefficient_max');
        $heuresMin = $request->input('heures_min');
        $heuresMax = $request->input('heures_max');
        $perPage = (int) $request->input('per_page', 15);

        $totalHeuresExpression = 'COALESCE(heures_cm, 0) + COALESCE(heures_td, 0) + COALESCE(heures_tp, 0) + COALESCE(heures_stage, 0) + COALESCE(heures_perso, 0)';

        $query = ESBTPMatiere::query()
            ->whereNull('unite_enseignement_id') // Exclure les ECUE LMD (gérés dans ue.index)
            ->with([
                'filieres:id,name,code',
                'niveaux:id,name,code',
                'liaisonsFilieresNiveaux.filiere:id,name,code',
                'liaisonsFilieresNiveaux.niveauEtude:id,name,code',
            ])
            ->orderBy('name');

        if ($filiere) {
            $query->whereHas('filieres', function ($q) use ($filiere) {
                $q->where('esbtp_filieres.id', $filiere);
            });
        }

        if ($niveau) {
            $query->whereHas('niveaux', function ($q) use ($niveau) {
                $q->where('esbtp_niveau_etudes.id', $niveau);
            });
        }

        if ($statut !== null && $statut !== '') {
            $query->where('is_active', $statut === '1');
        }

        if ($coefficientMin !== null && $coefficientMin !== '') {
            $query->where('coefficient', '>=', (float) $coefficientMin);
        }

        if ($coefficientMax !== null && $coefficientMax !== '') {
            $query->where('coefficient', '<=', (float) $coefficientMax);
        }

        if ($heuresMin !== null && $heuresMin !== '') {
            $query->whereRaw("{$totalHeuresExpression} >= ?", [(float) $heuresMin]);
        }

        if ($heuresMax !== null && $heuresMax !== '') {
            $query->whereRaw("{$totalHeuresExpression} <= ?", [(float) $heuresMax]);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';

                $q->where('name', 'like', $like)
                    ->orWhere('code', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('filieres', function ($filieresQuery) use ($like) {
                        $filieresQuery->where('name', 'like', $like)
                            ->orWhere('code', 'like', $like);
                    })
                    ->orWhereHas('niveaux', function ($niveauxQuery) use ($like) {
                        $niveauxQuery->where('name', 'like', $like)
                            ->orWhere('code', 'like', $like);
                    });
            });
        }

        // KPIs calculés sur la base filtrée (cohérent avec inscriptions/paiements premium).
        // Cloner avant paginate, sans les eager-loads ni l'order by (le ORDER BY casse les
        // agrégats SUM() sur MariaDB strict mode).
        $kpiBase = (clone $query)->withoutEagerLoads()->reorder();

        $kpis = [
            'total'           => (clone $kpiBase)->count(),
            'actifs'          => (clone $kpiBase)->where('is_active', true)->count(),
            'avec_liaisons'   => (clone $kpiBase)->whereHas('liaisonsFilieresNiveaux')->count(),
            'heures_totales'  => (int) (clone $kpiBase)->sum(\DB::raw($totalHeuresExpression)),
        ];
        $kpis['inactifs']     = $kpis['total'] - $kpis['actifs'];
        $kpis['sans_liaison'] = $kpis['total'] - $kpis['avec_liaisons'];

        $matieres = $query->paginate($perPage > 0 ? $perPage : 15)->withQueryString();

        return [
            'matieres' => $matieres,
            'summary' => [
                'total' => $matieres->total(),
                'page' => $matieres->currentPage(),
                'per_page' => $matieres->perPage(),
                'from' => $matieres->firstItem(),
                'to' => $matieres->lastItem(),
            ],
            'kpis' => $kpis,
        ];
    }

    /**
     * Affiche le formulaire de création d'une nouvelle matière.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $this->authorize('create', ESBTPMatiere::class);

        $filieres = ESBTPFiliere::where('is_active', true)->get();
        $niveauxEtudes = ESBTPNiveauEtude::whereNotIn('type', ['Licence', 'Master', 'Doctorat'])->get();
        $unitesEnseignement = collect(); // Collection vide temporaire

        // Récupérer les paramètres de pré-sélection depuis l'URL
        $preselectedFiliereId = $request->get('filiere_id');
        $preselectedNiveauId = $request->get('niveau_id');

        return view('esbtp.matieres.create', compact(
            'filieres',
            'niveauxEtudes',
            'unitesEnseignement',
            'preselectedFiliereId',
            'preselectedNiveauId'
        ));
    }

    /**
     * Enregistre une nouvelle matière dans la base de données.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Valider les données du formulaire
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:esbtp_matieres,code',
            'description' => 'nullable|string',
            'coefficient' => 'nullable|numeric|min:0',
            'niveau_etude_id' => 'nullable|exists:esbtp_niveau_etudes,id',
            'filiere_id' => 'nullable|exists:esbtp_filieres,id',
            'filieres' => 'nullable|array',
            'filieres.*' => 'exists:esbtp_filieres,id',
            'niveaux' => 'nullable|array',
            'niveaux.*' => 'exists:esbtp_niveau_etudes,id',
            'liaisons' => 'nullable|array',
            'liaisons.*.filiere_id' => 'required_with:liaisons|exists:esbtp_filieres,id',
            'liaisons.*.niveau_id' => 'required_with:liaisons|exists:esbtp_niveau_etudes,id',
            'type_formation' => 'required|in:generale,technologique_professionnelle',
            'couleur' => 'nullable|string|max:50',
            'is_active' => 'required|boolean',
        ]);

        // Auto-generate code from name if not provided
        if (empty($validatedData['code'])) {
            $baseName = strtoupper(trim($validatedData['name']));
            $baseCode = implode('', array_map(fn($w) => substr($w, 0, 3), preg_split('/\s+/', $baseName)));
            $code = $baseCode;
            $i = 1;
            while (ESBTPMatiere::where('code', $code)->exists()) {
                $code = $baseCode . $i++;
            }
            $validatedData['code'] = $code;
        }

        // Default coefficient if not provided
        if (!isset($validatedData['coefficient']) || $validatedData['coefficient'] === null) {
            $validatedData['coefficient'] = 1;
        }

        // Ajouter l'identifiant de l'utilisateur courant
        $validatedData['created_by'] = Auth::id();
        $validatedData['updated_by'] = Auth::id();

        // Créer la nouvelle matière
        $matiere = ESBTPMatiere::create($validatedData);

        // Gérer les liaisons multiple ou simples
        $filiereIds = [];
        $niveauIds = [];

        // Priorité à la multi-sélection si elle existe
        if ($request->has('filieres') && is_array($request->filieres)) {
            $filiereIds = $request->filieres;
        } elseif ($request->has('filiere_id') && $request->filiere_id) {
            $filiereIds = [$request->filiere_id];
        }

        if ($request->has('niveaux') && is_array($request->niveaux)) {
            $niveauIds = $request->niveaux;
        } elseif ($request->has('niveau_etude_id') && $request->niveau_etude_id) {
            $niveauIds = [$request->niveau_etude_id];
        }

        // Attacher les filières (mode legacy — cartésien)
        if (! empty($filiereIds)) {
            $matiere->filieres()->attach($filiereIds);
        }

        // Attacher les niveaux d'études (mode legacy — cartésien)
        if (! empty($niveauIds)) {
            $matiere->niveaux()->attach($niveauIds);
        }

        // Mode liaisons précises (filière × niveau pairs from create form)
        if ($request->has('liaisons') && is_array($request->liaisons)) {
            $seen = [];
            foreach ($request->liaisons as $liaison) {
                $key = ($liaison['filiere_id'] ?? 0) . '_' . ($liaison['niveau_id'] ?? 0);
                if (isset($seen[$key])) continue;
                $seen[$key] = true;

                \App\Models\ESBTPMatiereFilierNiveau::create([
                    'matiere_id'      => $matiere->id,
                    'filiere_id'      => $liaison['filiere_id'],
                    'niveau_etude_id' => $liaison['niveau_id'],
                ]);

                // Also attach to pivot tables for compatibility
                $matiere->filieres()->syncWithoutDetaching([$liaison['filiere_id']]);
                $matiere->niveaux()->syncWithoutDetaching([$liaison['niveau_id']]);
            }
        }

        // Rediriger avec un message de succès
        return redirect()->route('esbtp.matieres.index')
            ->with('success', 'La matière a été créée avec succès.');
    }

    /**
     * Affiche les détails d'une matière spécifique.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(ESBTPMatiere $matiere)
    {
        // Charger les relations
        $matiere->load(['filieres', 'niveaux', 'createdBy', 'updatedBy', 'enseignants']);

        // Récupérer l'année universitaire courante
        $anneeUniversitaireCourante = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Récupérer les évaluations de cette matière pour l'année courante uniquement
        $evaluationsQuery = $matiere->evaluations()->with(['classe', 'enseignant']);
        if ($anneeUniversitaireCourante) {
            $evaluationsQuery->where('annee_universitaire_id', $anneeUniversitaireCourante->id);
        }
        $evaluations = $evaluationsQuery->orderBy('date_evaluation', 'desc')->get();

        // Récupérer les données de planification académique pour cette matière
        $enseignantsAssignes = collect();
        $parametresPlanning = [];
        $planifications = collect(); // Initialiser comme collection vide

        if ($anneeUniversitaireCourante) {
            // Récupérer toutes les planifications pour cette matière et année
            // en ciblant toutes les combinaisons filière/niveau configurées
            $planifications = \App\Models\ESBTPPlanificationAcademique::where('matiere_id', $matiere->id)
                ->where('annee_universitaire_id', $anneeUniversitaireCourante->id)
                ->with(['enseignantPrincipal', 'filiere', 'niveauEtude'])
                ->get();

            // Calculer les totaux des volumes horaires depuis la planification
            $totalVolumeHoraire = $planifications->sum('volume_horaire_total');
            $totalHeuresCM = $planifications->sum('volume_horaire_cm');
            $totalHeuresTD = $planifications->sum('volume_horaire_td');
            $totalHeuresTP = $planifications->sum('volume_horaire_tp');
            $totalHeuresStage = $planifications->sum('heures_stage');
            $totalHeuresPerso = $planifications->sum('heures_perso');

            $parametresPlanning = [
                'volume_horaire_total' => $totalVolumeHoraire,
                'heures_cm' => $totalHeuresCM,
                'heures_td' => $totalHeuresTD,
                'heures_tp' => $totalHeuresTP,
                'heures_stage' => $totalHeuresStage,
                'heures_perso' => $totalHeuresPerso,
                'planifications_count' => $planifications->count(),
            ];

            foreach ($planifications as $planification) {
                // Récupérer les enseignants assignés via la table de liaison esbtp_planification_teachers
                $enseignantsLies = \Illuminate\Support\Facades\DB::table('esbtp_planification_teachers')
                    ->join('esbtp_teachers', 'esbtp_planification_teachers.teacher_id', '=', 'esbtp_teachers.id')
                    ->join('users', 'esbtp_teachers.user_id', '=', 'users.id')
                    ->where('esbtp_planification_teachers.planification_id', $planification->id)
                    ->select('users.*', 'esbtp_teachers.id as teacher_id')
                    ->get();

                foreach ($enseignantsLies as $enseignant) {
                    $enseignantsAssignes->push([
                        'enseignant' => (object) $enseignant,
                        'filiere' => $planification->filiere,
                        'niveau' => $planification->niveauEtude,
                        'planification_id' => $planification->id,
                        'volume_horaire' => $planification->volume_horaire_total,
                    ]);
                }

                // Fallback : si pas d'enseignant dans la table de liaison, essayer enseignant_principal_id
                if ($enseignantsLies->isEmpty() && $planification->enseignantPrincipal) {
                    $enseignantsAssignes->push([
                        'enseignant' => $planification->enseignantPrincipal,
                        'filiere' => $planification->filiere,
                        'niveau' => $planification->niveauEtude,
                        'planification_id' => $planification->id,
                        'volume_horaire' => $planification->volume_horaire_total,
                    ]);
                }
            }
        }

        // Récupérer les séances de cours pour cette matière depuis les emplois du temps actifs de l'année courante
        $seances = collect();
        if ($anneeUniversitaireCourante) {
            $seances = \App\Models\ESBTPSeanceCours::where('matiere_id', $matiere->id)
                ->whereHas('emploiTemps', function ($query) use ($anneeUniversitaireCourante) {
                    $query->where('annee_universitaire_id', $anneeUniversitaireCourante->id)
                        ->where('is_current', true);
                })
                ->with(['emploiTemps.classe', 'teacher'])
                ->orderBy('jour')
                ->orderBy('heure_debut')
                ->get();
        }

        $enseignantsParPlanification = $enseignantsAssignes
            ->groupBy('planification_id')
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'filiere' => $first['filiere'],
                    'niveau' => $first['niveau'],
                    'volume_horaire' => $first['volume_horaire'],
                    'enseignants' => $items->pluck('enseignant')->unique('id')->values(),
                ];
            })
            ->values();

        return view('esbtp.matieres.show', compact('matiere', 'evaluations', 'enseignantsAssignes', 'enseignantsParPlanification', 'anneeUniversitaireCourante', 'parametresPlanning', 'seances', 'planifications'));
    }

    /**
     * Affiche le formulaire de modification d'une matière.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(ESBTPMatiere $matiere)
    {
        // $this->authorize('update', $matiere); // Temporairement désactivé pour test

        $filieres = ESBTPFiliere::where('is_active', true)->get();
        $niveauxEtudes = ESBTPNiveauEtude::whereNotIn('type', ['Licence', 'Master', 'Doctorat'])->get();
        $unitesEnseignement = collect(); // Collection vide temporaire

        return view('esbtp.matieres.edit', compact('matiere', 'filieres', 'niveauxEtudes', 'unitesEnseignement'));
    }

    /**
     * Met à jour la matière spécifiée dans la base de données.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ESBTPMatiere $matiere)
    {
        // Valider les données du formulaire
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:esbtp_matieres,code,'.$matiere->id,
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'coefficient' => 'required|numeric|min:0',
            'niveau_etude_id' => 'nullable|exists:esbtp_niveau_etudes,id',
            'filiere_id' => 'nullable|exists:esbtp_filieres,id',
            'type_formation' => 'nullable|in:generale,technologique_professionnelle',
            'couleur' => 'nullable|string|max:50',
            'is_active' => 'required|boolean',
        ]);

        // Ajouter l'identifiant de l'utilisateur courant
        $validatedData['updated_by'] = Auth::id();

        // Mettre à jour la matière
        $matiere->update($validatedData);

        // Synchroniser les filières
        if ($request->has('filiere_id')) {
            $matiere->filieres()->sync($request->filiere_id);
        } else {
            $matiere->filieres()->detach();
        }

        // Synchroniser les niveaux d'études
        if ($request->has('niveau_etude_id')) {
            $matiere->niveaux()->sync($request->niveau_etude_id);
        } else {
            $matiere->niveaux()->detach();
        }

        // Rediriger avec un message de succès
        return redirect()->route('esbtp.matieres.index')
            ->with('success', 'La matière a été mise à jour avec succès.');
    }

    /**
     * Supprime la matière spécifiée de la base de données.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(ESBTPMatiere $matiere)
    {
        // Détacher toutes les relations
        $matiere->filieres()->detach();
        $matiere->niveaux()->detach();
        $matiere->classes()->detach();
        $matiere->enseignants()->detach();

        // Supprimer la matière
        $matiere->delete();

        // Rediriger avec un message de succès
        return redirect()->route('esbtp.matieres.index')
            ->with('success', 'La matière a été supprimée avec succès.');
    }

    /**
     * Affiche le formulaire pour attacher des matières à une classe
     *
     * @return \Illuminate\Http\Response
     */
    public function showAttachForm()
    {
        return view('esbtp.matieres.attach-to-classe');
    }

    /**
     * Associe des matières à une classe spécifique (méthode utilitaire)
     *
     * @return \Illuminate\Http\Response
     */
    public function attachToClasse(Request $request)
    {
        $validated = $request->validate([
            'classe_id' => 'required|exists:esbtp_classes,id',
            'matieres' => 'required|array',
            'matieres.*' => 'exists:esbtp_matieres,id',
        ]);

        $classe = \App\Models\ESBTPClasse::findOrFail($validated['classe_id']);

        // Préparation des données pour l'attachement
        $matieresData = [];
        foreach ($validated['matieres'] as $matiereId) {
            $matiere = \App\Models\ESBTPMatiere::findOrFail($matiereId);
            $matieresData[$matiereId] = [
                'coefficient' => $matiere->coefficient_default ?? 1.0,
                'total_heures' => $matiere->total_heures_default ?? 30,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Attacher les matières à la classe
        $classe->matieres()->attach($matieresData);

        return redirect()->route('esbtp.classes.matieres', ['classe' => $classe->id])
            ->with('success', count($matieresData).' matière(s) ajoutée(s) à la classe avec succès.');
    }

    /**
     * Renvoie la liste des matières au format JSON pour les appels AJAX
     *
     * @return \Illuminate\Http\Response
     */
    public function getMatieresJson()
    {
        try {
            \Log::info('Méthode getMatieresJson appelée');

            // Log whether the model exists and is accessible
            try {
                $matieresCount = \App\Models\ESBTPMatiere::count();
                \Log::info('Test de connexion à la table des matières réussi. Nombre total de matières (toutes): '.$matieresCount);
            } catch (\Exception $dbEx) {
                \Log::error('Erreur lors de l\'accès à la table des matières: '.$dbEx->getMessage());
            }

            // Vérifier si la colonne is_active existe
            $hasIsActiveColumn = Schema::hasColumn('esbtp_matieres', 'is_active');

            // Construire la requête en fonction de la disponibilité de la colonne
            $query = \App\Models\ESBTPMatiere::query();
            if ($hasIsActiveColumn) {
                $query->where('is_active', true);
            }

            $matieres = $query->select('id', 'name', 'code', 'coefficient')
                ->orderBy('name')
                ->get();

            \Log::info('Nombre de matières trouvées: '.$matieres->count());

            if ($matieres->isEmpty()) {
                \Log::warning('Aucune matière active trouvée');

                return response()->json([]);
            }

            $formatted = $matieres->map(function ($matiere) {
                return [
                    'id' => $matiere->id,
                    'name' => $matiere->name ?? $matiere->nom ?? 'Matière '.$matiere->id,
                    'code' => $matiere->code ?? '',
                    'coefficient' => $matiere->coefficient ?? 1,
                ];
            });

            return response()->json($formatted);
        } catch (\Exception $e) {
            \Log::error('Erreur dans getMatieresJson: '.$e->getMessage());

            return response()->json(['error' => 'Une erreur est survenue lors de la récupération des matières'], 500);
        }
    }

    /**
     * Renvoie toutes les matières actives en format JSON
     *
     * @return \Illuminate\Http\Response
     */
    public function getAllMatieresJson()
    {
        $matieres = \App\Models\ESBTPMatiere::where('is_active', true)->get();

        $formattedMatieres = $matieres->map(function ($matiere) {
            return [
                'id' => $matiere->id,
                'name' => $matiere->name ?? $matiere->nom ?? 'Matière '.$matiere->id,
                'code' => $matiere->code ?? '',
                'coefficient' => $matiere->coefficient ?? 1,
            ];
        });

        return response()->json($formattedMatieres);
    }

    /**
     * Supprime plusieurs matières en masse.
     *
     * @return \Illuminate\Http\Response
     */
    public function bulkDelete(Request $request)
    {
        // Valider les données
        $request->validate([
            'matieres' => 'required|array',
            'matieres.*' => 'exists:esbtp_matieres,id',
        ]);

        $count = 0;

        // Supprimer chaque matière
        foreach ($request->matieres as $id) {
            $matiere = ESBTPMatiere::find($id);

            if ($matiere) {
                // Vérifier si la matière peut être supprimée (pas de dépendances)
                $canDelete = true;

                // Ajouter ici des vérifications supplémentaires si nécessaire
                // Par exemple, vérifier si la matière est utilisée dans des emplois du temps, des évaluations, etc.

                if ($canDelete) {
                    $matiere->delete();
                    $count++;
                }
            }
        }

        if ($count > 0) {
            return redirect()->route('esbtp.matieres.index')
                ->with('success', $count.' matière(s) supprimée(s) avec succès.');
        } else {
            return redirect()->route('esbtp.matieres.index')
                ->with('error', 'Aucune matière n\'a pu être supprimée. Vérifiez qu\'elles ne sont pas utilisées ailleurs.');
        }
    }

    /**
     * Affiche l'interface d'attachement des matières aux classes.
     *
     * @return \Illuminate\Http\Response
     */
    public function attachToClasses(Request $request)
    {
        $selectedMatieres = collect();
        if ($request->has('matieres')) {
            $matiereIds = explode(',', $request->matieres);
            $selectedMatieres = ESBTPMatiere::whereIn('id', $matiereIds)->get();
        }

        $matieres = ESBTPMatiere::with(['filieres', 'niveaux'])->get();
        $classes = ESBTPClasse::with(['filiere', 'niveau'])->get();
        $filieres = ESBTPFiliere::where('is_active', true)->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->get();

        return view('esbtp.matieres.attach-to-classes', compact('matieres', 'classes', 'filieres', 'niveaux', 'selectedMatieres'));
    }

    /**
     * Attache les matières sélectionnées aux classes sélectionnées.
     *
     * @return \Illuminate\Http\Response
     */
    public function processAttachToClasses(Request $request)
    {
        $request->validate([
            'matiere_ids' => 'required|array',
            'matiere_ids.*' => 'exists:esbtp_matieres,id',
            'classe_ids' => 'required|array',
            'classe_ids.*' => 'exists:esbtp_classes,id',
            'coefficient' => 'required|numeric|min:0',
            'total_heures' => 'required|integer|min:0',
        ]);

        $matiereIds = $request->matiere_ids;
        $classeIds = $request->classe_ids;
        $coefficient = $request->coefficient;
        $totalHeures = $request->total_heures;

        foreach ($classeIds as $classeId) {
            $classe = ESBTPClasse::find($classeId);
            foreach ($matiereIds as $matiereId) {
                $classe->matieres()->syncWithoutDetaching([
                    $matiereId => [
                        'coefficient' => $coefficient,
                        'total_heures' => $totalHeures,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ]);
            }
        }

        return redirect()->back()->with('success', 'Les matières ont été attachées aux classes avec succès.');
    }

    /**
     * Récupère les liaisons existantes d'une matière (filières et niveaux).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLiaisons(ESBTPMatiere $matiere)
    {
        try {
            $liaisons = \App\Models\ESBTPMatiereFilierNiveau::where('matiere_id', $matiere->id)
                ->get(['filiere_id', 'niveau_etude_id'])
                ->map(fn($l) => ['filiere_id' => $l->filiere_id, 'niveau_id' => $l->niveau_etude_id])
                ->values()
                ->toArray();

            return response()->json([
                'success' => true,
                'liaisons' => $liaisons,
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des liaisons: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des liaisons',
            ], 500);
        }
    }

    /**
     * Met à jour les liaisons d'une matière avec les combinaisons filière+niveau sélectionnées.
     * Format attendu : { "liaisons": [ {"filiere_id": 1, "niveau_id": 1}, ... ] }
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateLiaisons(Request $request, ESBTPMatiere $matiere)
    {
        try {
            $validated = $request->validate([
                'liaisons'             => 'array',
                'liaisons.*.filiere_id' => 'required|exists:esbtp_filieres,id',
                'liaisons.*.niveau_id'  => 'required|exists:esbtp_niveau_etudes,id',
            ]);

            $liaisons = $validated['liaisons'] ?? [];

            // Supprimer toutes les liaisons existantes pour cette matière
            \App\Models\ESBTPMatiereFilierNiveau::where('matiere_id', $matiere->id)->delete();

            // Réinsérer les nouvelles combinaisons (dédoublonnées)
            $seen = [];
            foreach ($liaisons as $liaison) {
                $key = $liaison['filiere_id'].'_'.$liaison['niveau_id'];
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                \App\Models\ESBTPMatiereFilierNiveau::create([
                    'matiere_id'      => $matiere->id,
                    'filiere_id'      => $liaison['filiere_id'],
                    'niveau_etude_id' => $liaison['niveau_id'],
                ]);
            }

            $count = count($seen);
            $message = $count > 0
                ? "Liaisons mises à jour avec succès ! {$count} combinaison(s) configurée(s)."
                : 'Liaisons mises à jour avec succès ! Toutes les liaisons ont été supprimées.';

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides: '.implode(', ', $e->validator->errors()->all()),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour des liaisons: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde des liaisons',
            ], 500);
        }
    }

    /**
     * Récupère les statistiques de liaisons pour une matière.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatistiquesLiaisons(ESBTPMatiere $matiere)
    {
        try {
            $matiere->load(['filieres', 'niveaux', 'classes']);

            $stats = [
                'filieres_count' => $matiere->filieres->count(),
                'niveaux_count' => $matiere->niveaux->count(),
                'classes_count' => $matiere->classes->count(),
                'combinations_count' => $matiere->filieres->count() * $matiere->niveaux->count(),
                'filieres_names' => $matiere->filieres->pluck('name')->toArray(),
                'niveaux_names' => $matiere->niveaux->pluck('name')->toArray(),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des statistiques: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
            ], 500);
        }
    }

    /**
     * Récupère TOUTES les matières actives pour les assigner à une combinaison.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableForCombination(Request $request)
    {
        try {
            $filiereId = $request->get('filiere_id');
            $niveauId = $request->get('niveau_id');

            if (! $filiereId || ! $niveauId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Les IDs filière et niveau sont requis',
                ], 400);
            }

            // Récupérer les IDs des matières déjà liées en une seule requête
            $linkedMatiereIds = \App\Models\ESBTPMatiereFilierNiveau::matiereIdsForCombo($filiereId, $niveauId)->toArray();

            // Récupérer TOUTES les matières actives
            $matieres = ESBTPMatiere::where('is_active', true)
                ->select('id', 'name', 'code', 'description', 'coefficient', 'heures_cm', 'heures_td', 'heures_tp')
                ->orderBy('name')
                ->get()
                ->map(function ($matiere) use ($linkedMatiereIds) {
                    $matiere->total_heures = $matiere->heures_cm + $matiere->heures_td + $matiere->heures_tp;
                    $matiere->is_already_linked = in_array($matiere->id, $linkedMatiereIds);
                    return $matiere;
                });

            return response()->json([
                'success' => true,
                'matieres' => $matieres,
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des matières: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des matières',
            ], 500);
        }
    }

    /**
     * Ajoute des matières à une ou plusieurs combinaisons filière/niveau.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function addToCombination(Request $request)
    {
        $request->validate([
            'matiere_ids' => 'required|array',
            'matiere_ids.*' => 'exists:esbtp_matieres,id',
            'combinations' => 'required|array|min:1',
            'combinations.*.filiere_id' => 'required|exists:esbtp_filieres,id',
            'combinations.*.niveau_id' => 'required|exists:esbtp_niveau_etudes,id',
        ]);

        try {
            $matiereIds = $request->matiere_ids;
            $combinations = $request->combinations;
            $addedCount = 0;

            \DB::transaction(function () use ($matiereIds, $combinations, &$addedCount) {
                $matieres = ESBTPMatiere::whereIn('id', $matiereIds)->get()->keyBy('id');

                foreach ($matiereIds as $matiereId) {
                    $matiere = $matieres->get($matiereId);
                    if (!$matiere) continue;

                    foreach ($combinations as $combo) {
                        \App\Models\ESBTPMatiereFilierNiveau::firstOrCreate([
                            'matiere_id' => $matiereId,
                            'filiere_id' => $combo['filiere_id'],
                            'niveau_etude_id' => $combo['niveau_id'],
                        ]);
                        $matiere->filieres()->syncWithoutDetaching([$combo['filiere_id']]);
                        $matiere->niveaux()->syncWithoutDetaching([$combo['niveau_id']]);
                    }
                    $addedCount++;
                }
            });

            return response()->json([
                'success' => true,
                'message' => "{$addedCount} matière(s) ajoutée(s) avec succès aux combinaisons sélectionnées.",
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'ajout des matières: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout des matières',
            ], 500);
        }
    }

    /**
     * API pour récupérer la liste des matières
     * Utilisée pour les dropdowns et sélections AJAX
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiList(Request $request)
    {
        try {
            $query = ESBTPMatiere::where('is_active', true);

            // Filtrer par terme de recherche si fourni
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Filtrer par filière si fournie
            if ($request->has('filiere_id') && $request->filiere_id) {
                $query->whereHas('filieres', function ($q) use ($request) {
                    $q->where('esbtp_filieres.id', $request->filiere_id);
                });
            }

            // Filtrer par niveau si fourni
            if ($request->has('niveau_id') && $request->niveau_id) {
                $query->whereHas('niveaux', function ($q) use ($request) {
                    $q->where('esbtp_niveau_etudes.id', $request->niveau_id);
                });
            }

            $matieres = $query->select('id', 'name', 'code', 'description', 'coefficient')
                ->orderBy('name')
                ->get();

            return response()->json($matieres);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des matières via API: '.$e->getMessage());

            return response()->json([
                'error' => 'Erreur lors du chargement des matières',
            ], 500);
        }
    }

    /**
     * Associe un enseignant à une matière via la planification académique
     */
    public function associateEnseignant(Request $request, ESBTPMatiere $matiere)
    {
        return back()->with('info', 'Pour associer un enseignant à cette matière, veuillez utiliser le module Planning Général. Cela permet une gestion centralisée et cohérente des affectations.')
            ->with('planning_link', route('esbtp.planning-general.repartition-matieres'));
    }

    /**
     * Dissocie un enseignant d'une matière via la planification académique
     */
    public function dissociateEnseignant(Request $request, ESBTPMatiere $matiere)
    {
        return back()->with('info', 'Pour modifier les affectations d\'enseignants, veuillez utiliser le module Planning Général. Cela garantit la cohérence avec la planification académique.')
            ->with('planning_link', route('esbtp.planning-general.repartition-matieres'));
    }
}
