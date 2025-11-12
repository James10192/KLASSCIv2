<?php

namespace App\Http\Controllers;

use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPMatiere;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class ESBTPClasseController extends Controller
{
    /**
     * Affiche la liste des classes.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $startMicrotime = microtime(true);
        $startTimestamp = now()->toIso8601String();
        $baseLogContext = [
            'timestamp' => $startTimestamp,
            'url' => $request->fullUrl(),
            'query' => $request->query(),
            'user_id' => optional($request->user())->id,
        ];
        \Log::info('ESBTPClasseController@index start', $baseLogContext);

        $user = Auth::user();

        // Récupérer l'année universitaire courante pour l'affichage
        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $anneeAcademique = $anneeCourante ? $anneeCourante->name : date('Y') . '-' . (date('Y') + 1);

        // Construction de la requête avec filtres
        $query = ESBTPClasse::with(['filiere', 'niveau', 'annee']);

        // Filtres disponibles
        if ($request->filled('filiere_id')) {
            $query->where('filiere_id', $request->filiere_id);
        }

        if ($request->filled('niveau_id')) {
            $query->where('niveau_etude_id', $request->niveau_id);
        }


        if ($request->filled('statut')) {
            $query->where('is_active', $request->statut === 'active');
        }

        if ($request->filled('capacite')) {
            if ($request->capacite === 'disponible') {
                $query->whereRaw('places_totales > (SELECT COUNT(*) FROM esbtp_inscriptions WHERE esbtp_inscriptions.classe_id = esbtp_classes.id AND esbtp_inscriptions.status != "annulée")');
            } elseif ($request->capacite === 'pleine') {
                $query->whereRaw('places_totales <= (SELECT COUNT(*) FROM esbtp_inscriptions WHERE esbtp_inscriptions.classe_id = esbtp_classes.id AND esbtp_inscriptions.status != "annulée")');
            }
        }

        // Recherche par nom ou code
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('code', 'like', $search);
            });
        }

        \Log::info('ESBTPClasseController@index processing', array_merge($baseLogContext, [
            'has_search' => $request->filled('search'),
            'filters' => [
                'filiere_id' => $request->input('filiere_id'),
                'niveau_id' => $request->input('niveau_id'),
                'statut' => $request->input('statut'),
                'capacite' => $request->input('capacite'),
            ],
        ]));

        // Utiliser get() pour charger toutes les classes d'un coup
        $allClasses = $query->get();

        // Pour le chargement progressif via AJAX
        $perPage = 12;
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $perPage;

        // Simuler la pagination manuelle
        $classes = $allClasses->slice($offset, $perPage)->values();
        $hasMore = $allClasses->count() > ($offset + $perPage);
        $totalCount = $allClasses->count();

        // Données pour les filtres
        $filieres = ESBTPFiliere::where('is_active', true)->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->get();

        // Calculer les KPI globaux sur TOUTES les classes actives (pas seulement celles filtrées)
        // En tenant compte uniquement des inscriptions de l'année courante
        $kpiQuery = ESBTPClasse::where('is_active', true);

        // Charger les relations avec comptage des étudiants de l'année courante
        if ($anneeCourante) {
            $kpiQuery->withCount([
                'inscriptions as nombre_etudiants_annee_courante' => function($q) use ($anneeCourante) {
                    $q->where('annee_universitaire_id', $anneeCourante->id)
                      ->where('status', 'active');
                }
            ]);
        }

        $allActiveClasses = $kpiQuery->get();

        // Calculer les statistiques globales
        $kpiStats = [
            'totalClasses' => $allActiveClasses->count(),
            'classesActives' => $allActiveClasses->where('is_active', true)->count(),
            'totalEtudiants' => $anneeCourante
                ? $allActiveClasses->sum('nombre_etudiants_annee_courante')
                : $allActiveClasses->sum('nombre_etudiants'),
            'totalPlaces' => $allActiveClasses->sum('places_totales'),
        ];

        $kpiStats['placesDisponibles'] = $kpiStats['totalPlaces'] - $kpiStats['totalEtudiants'];
        $kpiStats['tauxOccupation'] = $kpiStats['totalPlaces'] > 0
            ? round(($kpiStats['totalEtudiants'] / $kpiStats['totalPlaces']) * 100, 1)
            : 0;

        $duration = round((microtime(true) - $startMicrotime) * 1000, 2);
        \Log::info('ESBTPClasseController@index completed', array_merge($baseLogContext, [
            'duration_ms' => $duration,
            'results_count' => $totalCount,
            'page' => $page,
            'has_more' => $hasMore,
            'kpi_stats' => $kpiStats,
        ]));

        // Support AJAX pour "Charger plus"
        if ($request->ajax()) {
            $html = view('esbtp.classes.partials.items', compact('classes'))->render();
            return response()->json([
                'html' => $html,
                'hasMore' => $hasMore,
                'currentPage' => $page,
                'total' => $totalCount,
            ]);
        }

        // Different view rendering based on user role
        if ($user->hasRole('etudiant')) {
            // For students - read-only view
            return view('esbtp.classes.student_index', compact('classes', 'anneeAcademique', 'anneeCourante', 'filieres', 'niveaux', 'hasMore', 'totalCount', 'kpiStats'));
        } else {
            // For admin and secretary - full functionality view
            return view('esbtp.classes.index', compact('classes', 'anneeAcademique', 'anneeCourante', 'filieres', 'niveaux', 'hasMore', 'totalCount', 'kpiStats'));
        }
    }

    /**
     * Affiche le formulaire de création d'une nouvelle classe.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $filieres = ESBTPFiliere::where('is_active', true)->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->get();
        $annees = ESBTPAnneeUniversitaire::where('is_active', true)->get();

        return view('esbtp.classes.create', compact('filieres', 'niveaux', 'annees'));
    }

    /**
     * Enregistre une nouvelle classe dans la base de données.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Valider les données du formulaire
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:esbtp_classes,code',
            'filiere_id' => 'required|exists:esbtp_filieres,id',
            'niveau_etude_id' => 'required|exists:esbtp_niveau_etudes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'places_totales' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Ajouter les champs de traçabilité
        $validatedData['created_by'] = Auth::id();
        $validatedData['updated_by'] = Auth::id();

        // Créer la nouvelle classe
        $classe = ESBTPClasse::create($validatedData);

        // Récupérer les matières associées aux niveaux sélectionnés
        $matieres = ESBTPMatiere::whereHas('niveaux', function ($query) use ($request) {
            $query->where('esbtp_niveau_etudes.id', $request->niveau_etude_id);
        })->get();

        // Associer les matières à la classe avec leurs coefficients et heures par défaut
        foreach ($matieres as $matiere) {
            $classe->matieres()->attach($matiere->id, [
                'coefficient' => $matiere->coefficient_default,
                'total_heures' => $matiere->total_heures_default,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        return redirect()->route('esbtp.classes.index')
            ->with('success', 'La classe a été créée avec succès.');
    }

    /**
     * Affiche les détails d'une classe spécifique.
     *
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function show(ESBTPClasse $classe)
    {
        $user = Auth::user();
        
        // Récupérer l'année universitaire courante
        $anneeCourante = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
        
        // Charger les relations de base
        $classe->load(['filiere', 'niveau', 'annee', 'matieres', 'emploisDuTemps']);

        $classeFiliereId = $classe->filiere_id;
        $classeNiveauId = $classe->niveau_etude_id;

        $combinationMatieres = ESBTPMatiere::with(['filieres:id,name,code', 'niveaux:id,name,code'])
            ->where('is_active', true)
            ->when($classeFiliereId, function ($query) use ($classeFiliereId) {
                $query->whereHas('filieres', function ($q) use ($classeFiliereId) {
                    $q->where('esbtp_filieres.id', $classeFiliereId);
                });
            })
            ->when($classeNiveauId, function ($query) use ($classeNiveauId) {
                $query->whereHas('niveaux', function ($q) use ($classeNiveauId) {
                    $q->where('esbtp_niveau_etudes.id', $classeNiveauId);
                });
            })
            ->orderBy('name')
            ->get()
            ->map(function (ESBTPMatiere $matiere) {
                $matiere->setAttribute('classe_coefficient', $matiere->coefficient ?? $matiere->coefficient_default ?? 1);
                return $matiere;
            });
        
        // Charger les étudiants et inscriptions FILTRÉS par année courante
        if ($anneeCourante) {
            $classe->load([
                'etudiants' => function ($query) use ($anneeCourante, $classe) {
                    $query->distinct()
                          ->whereHas('inscriptions', function ($inscriptionQuery) use ($anneeCourante, $classe) {
                              $inscriptionQuery->where('annee_universitaire_id', $anneeCourante->id)
                                               ->where('status', 'active')
                                               ->where('classe_id', $classe->id);
                          });
                },
                'inscriptions' => function ($query) use ($anneeCourante) {
                    $query->where('annee_universitaire_id', $anneeCourante->id)
                          ->where('status', 'active')
                          ->with('etudiant');
                }
            ]);
        } else {
            // Si aucune année courante définie, charger normalement (éviter les erreurs)
            $classe->load(['etudiants', 'inscriptions']);
        }

        // Préparer l'année académique pour l'affichage
        $anneeAcademique = $anneeCourante ? $anneeCourante->name : date('Y') . '-' . (date('Y') + 1);

        // Different view rendering based on user role
        if ($user->hasRole('etudiant')) {
            // For students - read-only view
            return view('esbtp.classes.student_show', compact('classe', 'anneeCourante', 'anneeAcademique', 'combinationMatieres'));
        } else {
            // For admin and secretary - full functionality view
            return view('esbtp.classes.show', compact('classe', 'anneeCourante', 'anneeAcademique', 'combinationMatieres'));
        }
    }

    /**
     * Affiche le formulaire de modification d'une classe existante.
     *
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function edit(ESBTPClasse $classe)
    {
        $filieres = ESBTPFiliere::where('is_active', true)->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->get();
        $annees = ESBTPAnneeUniversitaire::where('is_active', true)->get();

        return view('esbtp.classes.edit', compact('classe', 'filieres', 'niveaux', 'annees'));
    }

    /**
     * Met à jour la classe spécifiée dans la base de données.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ESBTPClasse $classe)
    {
        // Valider les données du formulaire
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:esbtp_classes,code,' . $classe->id,
            'filiere_id' => 'required|exists:esbtp_filieres,id',
            'niveau_etude_id' => 'required|exists:esbtp_niveau_etudes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'places_totales' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Mettre à jour les champs de traçabilité
        $validatedData['updated_by'] = Auth::id();

        // Mettre à jour la classe
        $classe->update($validatedData);

        // Si le niveau a changé, mettre à jour les matières
        if ($classe->isDirty('niveau_etude_id')) {
            // Récupérer les matières associées au niveau sélectionné
            $matieres = ESBTPMatiere::whereHas('niveaux', function ($query) use ($request) {
                $query->where('esbtp_niveau_etudes.id', $request->niveau_etude_id);
            })->get();

            // Réinitialiser les matières associées à la classe
            $classe->matieres()->detach();

            // Associer les nouvelles matières à la classe
            foreach ($matieres as $matiere) {
                $classe->matieres()->attach($matiere->id, [
                    'coefficient' => $matiere->coefficient_default,
                    'total_heures' => $matiere->total_heures_default,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        // Récupérer et valider le return_url
        $returnUrl = $this->validateReturnUrl($request->input('return_url'));

        return redirect($returnUrl)
            ->with('success', 'La classe a été mise à jour avec succès.');
    }

    /**
     * Valide et nettoie l'URL de retour pour éviter les attaques d'open redirect.
     *
     * @param  string|null  $url
     * @return string
     */
    private function validateReturnUrl($url)
    {
        // Si pas d'URL fournie, retourner la page show de la classe par défaut (Option B)
        if (!$url) {
            return route('esbtp.classes.show', ['classe' => request()->route('classe')->id]);
        }

        // Parser l'URL fournie
        $parsedUrl = parse_url($url);

        // Si l'URL n'est pas valide, retourner le fallback
        if ($parsedUrl === false) {
            return route('esbtp.classes.show', ['classe' => request()->route('classe')->id]);
        }

        // Vérifier que l'URL est interne (pas de domaine externe)
        if (isset($parsedUrl['host'])) {
            $appUrl = parse_url(config('app.url'));

            // Si l'URL a un host différent de notre app, c'est une tentative de redirect externe
            if ($parsedUrl['host'] !== ($appUrl['host'] ?? '')) {
                \Log::warning('Tentative de redirect externe bloquée', [
                    'url' => $url,
                    'host' => $parsedUrl['host'],
                    'expected_host' => $appUrl['host'] ?? '',
                    'user_id' => auth()->id()
                ]);

                return route('esbtp.classes.show', ['classe' => request()->route('classe')->id]);
            }
        }

        // URL valide et interne, on la retourne
        return $url;
    }

    /**
     * Supprime la classe spécifiée de la base de données.
     *
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function destroy(ESBTPClasse $classe)
    {
        // Vérifier si des étudiants sont inscrits dans cette classe
        if ($classe->inscriptions()->count() > 0) {
            return redirect()->route('esbtp.classes.index')
                ->with('error', 'Impossible d\'archiver cette classe car elle contient encore des étudiants inscrits pour l\'année en cours.');
        }

        // Détacher toutes les matières
        $classe->matieres()->detach();

        // Supprimer la classe
        $classe->delete();

        return redirect()->route('esbtp.classes.index')
            ->with('success', 'La classe a été archivée avec succès. L\'historique des inscriptions est préservé.');
    }

    /**
     * Affiche la page de gestion des matières associées à une classe.
     *
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function matieres(ESBTPClasse $classe)
    {
        $classeFiliereId = $classe->filiere_id;
        $classeNiveauId = $classe->niveau_etude_id;

        $matieres = ESBTPMatiere::with(['filieres:id,name,code', 'niveaux:id,name,code'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->filter(function (ESBTPMatiere $matiere) use ($classeFiliereId, $classeNiveauId) {
                if (!$classeFiliereId || !$classeNiveauId) {
                    return false;
                }
                return $matiere->filieres->pluck('id')->contains($classeFiliereId)
                    && $matiere->niveaux->pluck('id')->contains($classeNiveauId);
            })
            ->values()
            ->map(function (ESBTPMatiere $matiere) {
                $matiere->setAttribute('matches_combination', true);
                $matiere->setAttribute('classe_coefficient', $matiere->coefficient ?? $matiere->coefficient_default ?? 1);
                return $matiere;
            });

        $availableMatieres = ESBTPMatiere::with(['filieres:id,name,code', 'niveaux:id,name,code'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->filter(function (ESBTPMatiere $matiere) use ($classeFiliereId, $classeNiveauId) {
                if (!$classeFiliereId || !$classeNiveauId) {
                    return false;
                }

                $hasFiliere = $matiere->filieres->pluck('id')->contains($classeFiliereId);
                $hasNiveau = $matiere->niveaux->pluck('id')->contains($classeNiveauId);

                return !($hasFiliere && $hasNiveau);
            })
            ->values()
            ->map(function (ESBTPMatiere $matiere) {
                $matiere->setAttribute('matches_combination', false);
                $matiere->setAttribute('classe_coefficient', $matiere->coefficient ?? $matiere->coefficient_default ?? 1);
                return $matiere;
            });

        $stats = [
            'used_by_class' => $matieres->count(),
            'suggested_total' => $matieres->count(),
            'suggested_available' => 0,
            'catalog_available' => $availableMatieres->count(),
        ];

        $filieres = ESBTPFiliere::where('is_active', true)->orderBy('name')->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->orderBy('name')->get();

        return view('esbtp.classes.matieres', [
            'classe' => $classe,
            'matieres' => $matieres,
            'availableMatieres' => $availableMatieres,
            'stats' => $stats,
            'filieres' => $filieres,
            'niveaux' => $niveaux,
        ]);
    }

    /**
     * Met à jour les matières et leurs coefficients pour une classe.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function updateMatieres(Request $request, ESBTPClasse $classe)
    {
        // Valider les données du formulaire
        $request->validate([
            'matiere_ids' => 'nullable|array',
            'matiere_ids.*' => 'exists:esbtp_matieres,id',
            'coefficients' => 'nullable|array',
            'coefficients.*' => 'numeric|min:0',
            'heures' => 'nullable|array',
            'heures.*' => 'integer|min:0',
        ]);

        // Réinitialiser les matières existantes
        $classe->matieres()->detach();

        // Récupérer les IDs des matières sélectionnées
        $matiereIds = $request->input('matiere_ids', []);

        // Ajouter les matières sélectionnées avec leurs coefficients et heures
        foreach ($matiereIds as $matiereId) {
            $classe->matieres()->attach($matiereId, [
                'coefficient' => $request->input("coefficients.{$matiereId}", 1),
                'total_heures' => $request->input("heures.{$matiereId}", 0),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('esbtp.classes.show', ['classe' => $classe->id])
            ->with('success', 'Les matières ont été mises à jour avec succès.');
    }

    /**
     * Récupère les matières d'une classe pour l'API JavaScript.
     *
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function getMatieresForApi(ESBTPClasse $classe)
    {
        try {
            \Log::info('API matières appelée pour la classe ID: ' . $classe->id);
            \Log::info('Classe: ' . ($classe->name ?? 'N/A') . ', Filière ID: ' . ($classe->filiere_id ?? 'N/A') . ', Niveau ID: ' . ($classe->niveau_etude_id ?? 'N/A'));

            // Méthode 1: Matières directement liées à la classe via table pivot
            $matieres = $classe->matieres()->where('esbtp_matieres.is_active', true)->get();
            \Log::info('Matières directement liées: ' . $matieres->count());

            // Méthode 2: Recherche par relations many-to-many filière et niveau
            if ($matieres->isEmpty()) {
                \Log::info('Recherche par relations many-to-many...');
                $query = \App\Models\ESBTPMatiere::where('is_active', true);

                if ($classe->filiere_id) {
                    $query->whereHas('filieres', function($q) use ($classe) {
                        $q->where('esbtp_filieres.id', $classe->filiere_id);
                    });
                }

                if ($classe->niveau_etude_id) {
                    $query->whereHas('niveaux', function($q) use ($classe) {
                        $q->where('esbtp_niveau_etudes.id', $classe->niveau_etude_id);
                    });
                }

                $matieres = $query->get();
                \Log::info('Matières trouvées par relations many-to-many: ' . $matieres->count());
            }

            // Méthode 3: Recherche par colonnes directes (deprecated mais peut être utilisé)
            if ($matieres->isEmpty()) {
                \Log::info('Recherche par colonnes directes...');
                $query = \App\Models\ESBTPMatiere::where('is_active', true);

                if ($classe->filiere_id) {
                    $query->where('filiere_id', $classe->filiere_id);
                }

                if ($classe->niveau_etude_id) {
                    $query->where('niveau_etude_id', $classe->niveau_etude_id);
                }

                $matieres = $query->get();
                \Log::info('Matières trouvées par colonnes directes: ' . $matieres->count());
            }

            // Méthode 4: Toutes les matières actives comme fallback
            if ($matieres->isEmpty()) {
                \Log::info('Fallback: toutes les matières actives...');
                $matieres = \App\Models\ESBTPMatiere::where('is_active', true)->get();
                \Log::info('Toutes les matières actives: ' . $matieres->count());
            }

            // Si encore vide, toutes les matières
            if ($matieres->isEmpty()) {
                \Log::info('Fallback final: toutes les matières...');
                $matieres = \App\Models\ESBTPMatiere::all();
                \Log::info('Toutes les matières: ' . $matieres->count());
            }

            // Formatage pour l'API
            $formattedMatieres = $matieres->map(function ($matiere) {
                return [
                    'id' => $matiere->id,
                    'name' => $matiere->nom ?? $matiere->name ?? 'Matière ' . $matiere->id,
                    'code' => $matiere->code ?? '',
                    'coefficient' => $matiere->coefficient ?? 1
                ];
            });

            \Log::info('Total matières renvoyées: ' . $formattedMatieres->count());
            return response()->json($formattedMatieres);

        } catch (\Exception $e) {
            \Log::error('Erreur dans getMatieresForApi: ' . $e->getMessage());
            \Log::error('Trace: ' . $e->getTraceAsString());

            return response()->json([
                'error' => 'Erreur lors de la récupération des matières',
                'message' => $e->getMessage(),
                'debug' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Get subjects for a specific class.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMatieres($id)
    {
        try {
            $classe = ESBTPClasse::findOrFail($id);
            $matieres = $classe->matieres()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']);

            return response()->json($matieres);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la récupération des matières'], 500);
        }
    }

    /**
     * Récupère les détails d'une classe pour l'API JavaScript.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getClasseById($id)
    {
        try {
            $classe = ESBTPClasse::with(['filiere', 'niveau', 'anneeUniversitaire'])
                ->findOrFail($id);

            return response()->json($classe);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération de la classe: ' . $e->getMessage());
            return response()->json(['error' => 'Classe non trouvée'], 404);
        }
    }

    /**
     * Returns all active classes for API.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexApi()
    {
        $classes = ESBTPClasse::with(['filiere', 'niveau', 'annee'])
            ->where('is_active', true)
            ->get();
        return response()->json($classes);
    }

    /**
     * Récupère le nombre de places disponibles pour une classe.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailablePlaces($id)
    {
        try {
            \Log::info("Début de getAvailablePlaces pour la classe ID: {$id}");
            
            // Trouver l'année universitaire active
            $anneeActive = \App\Models\ESBTPAnneeUniversitaire::where('is_active', true)->first();
            if (!$anneeActive) {
                \Log::error("Aucune année universitaire active trouvée");
                return response()->json(['error' => 'Aucune année universitaire active.'], 400);
            }

            $classe = ESBTPClasse::find($id);

            if (!$classe) {
                \Log::error("Classe non trouvée pour l'ID: {$id}");
                return response()->json(['error' => 'Classe non trouvée.'], 404);
            }
            
            \Log::info("Classe trouvée: {$classe->name}");

            $capacity = $classe->places_totales ?? 0;
            \Log::info("Capacité (places_totales) lue: {$capacity}");

            // Compter seulement les inscriptions de l'année universitaire active
            $inscriptions_count = $classe->inscriptions()
                ->where('annee_universitaire_id', $anneeActive->id)
                ->where('status', '!=', 'annulée')
                ->count();
            \Log::info("Nombre d'inscriptions pour l'année active {$anneeActive->name}: {$inscriptions_count}");

            $availablePlaces = $capacity - $inscriptions_count;
            \Log::info("Calcul des places disponibles: {$capacity} - {$inscriptions_count} = {$availablePlaces}");

            $responseData = [
                'available_places' => $availablePlaces,
                'capacity' => $capacity,
                'inscriptions_count' => $inscriptions_count,
            ];

            \Log::info("Réponse JSON envoyée: " . json_encode($responseData));

            return response()->json($responseData);
        } catch (\Exception $e) {
            \Log::error("Erreur dans getAvailablePlaces pour la classe ID {$id}: " . $e->getMessage());
            return response()->json(['error' => 'Une erreur est survenue lors de la récupération des données.'], 500);
        }
    }

    /**
     * Récupère les étudiants d'une classe pour l'API JavaScript.
     *
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function getEtudiants(ESBTPClasse $classe)
    {
        $etudiants = $classe->etudiants()
            ->select('id', 'nom', 'prenoms', 'matricule')
            ->where('is_active', true)
            ->orderBy('nom')
            ->orderBy('prenoms')
            ->get();

        return response()->json([
            'success' => true,
            'etudiants' => $etudiants
        ]);
    }

    /**
     * Récupère la configuration matricule pour le niveau d'études d'une classe
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getNiveauConfig($id)
    {
        try {
            $classe = ESBTPClasse::with('niveau')->findOrFail($id);

            if (!$classe->niveau) {
                return response()->json([
                    'success' => false,
                    'message' => 'Niveau d\'études non trouvé pour cette classe',
                    'niveau_config' => null
                ]);
            }

            // Rechercher la configuration matricule pour ce niveau
            $currentEtablissementId = \App\Models\ESBTPSystemSetting::getCurrentEtablissementId();

            // Mapper le type de niveau vers le code de configuration matricule
            $niveauType = $classe->niveau->type ?? null;
            $configCode = null;

            if ($niveauType) {
                $configCode = strtoupper($niveauType); // BTS, Licence, Master, etc.

                // Normaliser certains types si nécessaire
                if (strtolower($niveauType) === 'licence') {
                    $configCode = 'LICENCE';
                } elseif (strtolower($niveauType) === 'bts') {
                    $configCode = 'BTS';
                }
            }

            if (!$configCode) {
                return response()->json([
                    'success' => true,
                    'niveau_config' => null,
                    'message' => 'Type de niveau non défini pour cette classe'
                ]);
            }

            $matriculeConfig = \App\Models\ESBTPMatriculeConfig::where('etablissement_id', $currentEtablissementId)
                ->where('niveau_etude_code', $configCode)
                ->where('is_active', true)
                ->first();

            if (!$matriculeConfig) {
                return response()->json([
                    'success' => true,
                    'niveau_config' => null,
                    'message' => 'Configuration matricule non trouvée pour ce niveau'
                ]);
            }

            return response()->json([
                'success' => true,
                'niveau_config' => [
                    'id' => $matriculeConfig->id,
                    'code' => $matriculeConfig->niveau_etude_code,
                    'nom' => $matriculeConfig->niveau_etude_name,
                    'prefixe' => $matriculeConfig->prefixe,
                    'annee_format' => $matriculeConfig->annee_format,
                    'etablissement_code' => $matriculeConfig->etablissement_code
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
                'niveau_config' => null
            ], 500);
        }
    }

    /**
     * Affiche la liste d'appel pour une classe (preview web)
     *
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function listeAppel(ESBTPClasse $classe)
    {
        $classe->load(['filiere', 'niveau', 'annee']);

        // Récupérer l'année universitaire courante
        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        $etudiants = $classe->inscriptions()
            ->with(['etudiant'])
            ->where('status', 'active')
            ->when($anneeCourante, function($query) use ($anneeCourante) {
                return $query->where('annee_universitaire_id', $anneeCourante->id);
            })
            ->get()
            ->map(function($inscription) {
                return $inscription->etudiant;
            })
            ->filter()
            // Trier alpha et réindexer pour obtenir 1, 2, 3... dans la vue
            ->sortBy(function($etudiant) {
                return Str::lower($etudiant->nom . ' ' . $etudiant->prenoms);
            })
            ->values();

        // Récupérer les paramètres de l'établissement
        $etablissement = [
            'nom' => Setting::get('school_name', 'ESBTP-yAKRO'),
            'adresse' => Setting::get('school_address', ''),
            'telephone' => Setting::get('school_phone', ''),
            'email' => Setting::get('school_email', ''),
            'logo' => Setting::get('school_logo', '')
        ];

        return view('esbtp.classes.liste-appel', compact('classe', 'etudiants', 'anneeCourante', 'etablissement'));
    }

    /**
     * Génère le PDF de la liste d'appel pour une classe
     *
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function listeAppelPDF(ESBTPClasse $classe)
    {
        $classe->load(['filiere', 'niveau', 'annee']);

        // Récupérer l'année universitaire courante
        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        $etudiants = $classe->inscriptions()
            ->with(['etudiant'])
            ->where('status', 'active')
            ->when($anneeCourante, function($query) use ($anneeCourante) {
                return $query->where('annee_universitaire_id', $anneeCourante->id);
            })
            ->get()
            ->map(function($inscription) {
                return $inscription->etudiant;
            })
            ->filter()
            // Tri identique à la version web afin de conserver la numérotation
            ->sortBy(function($etudiant) {
                return Str::lower($etudiant->nom . ' ' . $etudiant->prenoms);
            })
            ->values();

        // Récupérer les paramètres de l'établissement
        $etablissement = [
            'nom' => Setting::get('school_name', 'ESBTP-yAKRO'),
            'adresse' => Setting::get('school_address', ''),
            'telephone' => Setting::get('school_phone', ''),
            'email' => Setting::get('school_email', ''),
            'logo' => Setting::get('school_logo', '')
        ];

        $pdf = PDF::loadView('esbtp.classes.liste-appel-pdf', compact('classe', 'etudiants', 'anneeCourante', 'etablissement'));

        $filename = 'liste-appel-' . Str::slug($classe->name) . '-' . date('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Affiche la liste complète des étudiants pour une classe (preview web)
     *
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function listeComplete(ESBTPClasse $classe)
    {
        $classe->load(['filiere', 'niveau', 'annee']);

        // Récupérer l'année universitaire courante
        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        $etudiants = $classe->inscriptions()
            ->with(['etudiant'])
            ->where('status', 'active')
            ->when($anneeCourante, function($query) use ($anneeCourante) {
                return $query->where('annee_universitaire_id', $anneeCourante->id);
            })
            ->get()
            ->map(function($inscription) {
                return $inscription->etudiant;
            })
            ->filter()
            ->sortBy(['nom', 'prenoms']);

        // Récupérer les paramètres de l'établissement
        $etablissement = [
            'nom' => Setting::get('school_name', 'ESBTP-yAKRO'),
            'adresse' => Setting::get('school_address', ''),
            'telephone' => Setting::get('school_phone', ''),
            'email' => Setting::get('school_email', ''),
            'logo' => Setting::get('school_logo', '')
        ];

        return view('esbtp.classes.liste-complete', compact('classe', 'etudiants', 'anneeCourante', 'etablissement'));
    }

    /**
     * Génère le PDF de la liste complète des étudiants pour une classe
     *
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function listeCompletePDF(ESBTPClasse $classe)
    {
        $classe->load(['filiere', 'niveau', 'annee']);

        // Récupérer l'année universitaire courante
        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        $etudiants = $classe->inscriptions()
            ->with(['etudiant'])
            ->where('status', 'active')
            ->when($anneeCourante, function($query) use ($anneeCourante) {
                return $query->where('annee_universitaire_id', $anneeCourante->id);
            })
            ->get()
            ->map(function($inscription) {
                return $inscription->etudiant;
            })
            ->filter()
            ->sortBy(['nom', 'prenoms']);

        // Récupérer les paramètres de l'établissement
        $etablissement = [
            'nom' => Setting::get('school_name', 'ESBTP-yAKRO'),
            'adresse' => Setting::get('school_address', ''),
            'telephone' => Setting::get('school_phone', ''),
            'email' => Setting::get('school_email', ''),
            'logo' => Setting::get('school_logo', '')
        ];

        $pdf = PDF::loadView('esbtp.classes.liste-complete-pdf', compact('classe', 'etudiants', 'anneeCourante', 'etablissement'));

        $filename = 'liste-complete-' . Str::slug($classe->name) . '-' . date('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Génère le fichier Excel de la liste complète des étudiants pour une classe
     *
     * @param  \App\Models\ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function listeCompleteExcel(ESBTPClasse $classe)
    {
        $classe->load(['filiere', 'niveau', 'annee']);

        // Récupérer l'année universitaire courante
        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        $etudiants = $classe->inscriptions()
            ->with(['etudiant'])
            ->where('status', 'active')
            ->when($anneeCourante, function($query) use ($anneeCourante) {
                return $query->where('annee_universitaire_id', $anneeCourante->id);
            })
            ->get()
            ->map(function($inscription) {
                return $inscription->etudiant;
            })
            ->filter()
            ->sortBy(['nom', 'prenoms']);

        // Récupérer les paramètres de l'établissement
        $etablissement = [
            'nom' => Setting::get('school_name', 'ESBTP-yAKRO'),
            'adresse' => Setting::get('school_address', ''),
            'telephone' => Setting::get('school_phone', ''),
            'email' => Setting::get('school_email', ''),
            'logo' => Setting::get('school_logo', '')
        ];

        $filename = 'liste-complete-' . Str::slug($classe->name) . '-' . date('Y-m-d') . '.xlsx';

        return Excel::download(new \App\Exports\ClasseEtudiantsExport($classe, $etudiants, $anneeCourante, $etablissement), $filename);
    }

    /**
     * Récupérer toutes les classes filtrées (pour les exports)
     */
    private function getAllFilteredClasses(Request $request)
    {
        $query = ESBTPClasse::with(['filiere', 'niveau', 'annee']);

        // Appliquer les mêmes filtres que dans index()
        if ($request->filled('filiere_id')) {
            $query->where('filiere_id', $request->filiere_id);
        }

        if ($request->filled('niveau_id')) {
            $query->where('niveau_etude_id', $request->niveau_id);
        }

        if ($request->filled('statut')) {
            $query->where('is_active', $request->statut === 'active');
        }

        if ($request->filled('capacite')) {
            if ($request->capacite === 'disponible') {
                $query->whereRaw('places_totales > (SELECT COUNT(*) FROM esbtp_inscriptions WHERE esbtp_inscriptions.classe_id = esbtp_classes.id AND esbtp_inscriptions.status != "annulée")');
            } elseif ($request->capacite === 'pleine') {
                $query->whereRaw('places_totales <= (SELECT COUNT(*) FROM esbtp_inscriptions WHERE esbtp_inscriptions.classe_id = esbtp_classes.id AND esbtp_inscriptions.status != "annulée")');
            }
        }

        // Recherche par nom ou code
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('code', 'like', $search);
            });
        }

        // Récupérer TOUS les résultats (pas de pagination)
        return $query->orderBy('name')->get();
    }

    /**
     * Exporter les classes au format Excel (XLSX)
     */
    public function exportExcel(Request $request)
    {
        try {
            // Récupérer TOUTES les classes filtrées
            $classes = $this->getAllFilteredClasses($request);

            // Récupérer l'année courante
            $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

            // Préparer les filtres pour l'export
            $filters = [
                'search' => $request->input('search'),
                'filiere_id' => $request->input('filiere_id'),
                'niveau_id' => $request->input('niveau_id'),
                'statut' => $request->input('statut'),
                'capacite' => $request->input('capacite'),
            ];

            // Créer l'export
            $export = new \App\Exports\ClassesExport($classes, $anneeCourante, $filters);

            // Générer le nom du fichier
            $filename = 'classes_' . now()->format('Y-m-d_His') . '.xlsx';

            \Log::info('Export Excel classes généré', [
                'user_id' => auth()->id(),
                'total_classes' => $classes->count(),
                'filename' => $filename
            ]);

            return Excel::download($export, $filename);

        } catch (\Exception $e) {
            \Log::error('Erreur export Excel classes: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Erreur lors de l\'export Excel: ' . $e->getMessage());
        }
    }

    /**
     * Exporter les classes au format CSV
     */
    public function exportCsv(Request $request)
    {
        try {
            // Récupérer TOUTES les classes filtrées
            $classes = $this->getAllFilteredClasses($request);

            // Récupérer l'année courante
            $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

            // Préparer les filtres
            $filters = [
                'search' => $request->input('search'),
                'filiere_id' => $request->input('filiere_id'),
                'niveau_id' => $request->input('niveau_id'),
                'statut' => $request->input('statut'),
                'capacite' => $request->input('capacite'),
            ];

            // Créer l'export
            $export = new \App\Exports\ClassesExport($classes, $anneeCourante, $filters);

            // Générer le nom du fichier
            $filename = 'classes_' . now()->format('Y-m-d_His') . '.csv';

            \Log::info('Export CSV classes généré', [
                'user_id' => auth()->id(),
                'total_classes' => $classes->count(),
                'filename' => $filename
            ]);

            return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::CSV, [
                'Content-Type' => 'text/csv',
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur export CSV classes: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Erreur lors de l\'export CSV: ' . $e->getMessage());
        }
    }

    /**
     * Exporter les classes au format PDF
     */
    public function exportPdf(Request $request)
    {
        try {
            // Récupérer TOUTES les classes filtrées
            $classes = $this->getAllFilteredClasses($request);

            // Récupérer l'année courante
            $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

            // Préparer les filtres
            $filters = [
                'search' => $request->input('search'),
                'filiere_id' => $request->input('filiere_id'),
                'niveau_id' => $request->input('niveau_id'),
                'statut' => $request->input('statut'),
                'capacite' => $request->input('capacite'),
            ];

            // Récupérer les paramètres de l'école
            $settings = [
                'nom' => Setting::get('school_name', 'ESBTP-yAKRO'),
                'adresse' => Setting::get('school_address', ''),
                'telephone' => Setting::get('school_phone', ''),
                'email' => Setting::get('school_email', ''),
                'logo' => Setting::get('school_logo', '')
            ];

            \Log::info('Export PDF classes généré', [
                'user_id' => auth()->id(),
                'total_classes' => $classes->count(),
            ]);

            // Générer le PDF
            $pdf = PDF::loadView('esbtp.classes.export-pdf', [
                'classes' => $classes,
                'anneeCourante' => $anneeCourante,
                'filters' => $filters,
                'settings' => $settings,
                'dateExport' => now()
            ]);

            // Télécharger le PDF
            $filename = 'classes_' . now()->format('Y-m-d_His') . '.pdf';
            return $pdf->download($filename);

        } catch (\Exception $e) {
            \Log::error('Erreur export PDF classes: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Erreur lors de l\'export PDF: ' . $e->getMessage());
        }
    }
}
