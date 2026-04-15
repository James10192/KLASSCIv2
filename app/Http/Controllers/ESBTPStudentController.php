<?php

namespace App\Http\Controllers;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPDepartment;
use App\Models\ESBTPCycle;
use App\Models\ESBTPClass;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\FuzzyNameMatcher;
use App\Services\EtudiantDossierService;
use App\Exports\EtudiantsExport;
use App\Helpers\SettingsHelper;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class ESBTPStudentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view_students', ['only' => ['index', 'show', 'genererCertificat', 'exportExcel', 'exportPdf']]);
        $this->middleware('permission:create_students', ['only' => ['create', 'store']]);
        $this->middleware('permission:edit_students', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete_students', ['only' => ['destroy']]);
    }

    public function index(Request $request, FuzzyNameMatcher $matcher)
    {
        $startMicrotime = microtime(true);
        $startTimestamp = now()->toIso8601String();
        $baseLogContext = [
            'timestamp' => $startTimestamp,
            'url' => $request->fullUrl(),
            'query' => $request->query(),
            'user_id' => optional($request->user())->id,
        ];
        \Log::info('ESBTPStudentController@index start', $baseLogContext);

        // Récupérer l'année universitaire courante
        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Récupérer les filtres de recherche
        $search = $request->input('search');
        $filiere = $request->input('filiere');
        $niveau = $request->input('niveau');
        $annee = $request->input('annee');
        $status = $request->input('status');
        $classe = $request->input('classe');
        $affectationStatus = $request->input('affectation_status');
        $inscritAnneeCourante = $request->input('inscrit_annee_courante');
        $estTransfert = $request->input('est_transfert');

        $baseQuery = ESBTPEtudiant::query()
            ->with(['user', 'inscriptions' => function ($q) {
                $q->with(['filiere', 'niveau', 'classe', 'anneeUniversitaire']);
            }]);

        // Charger l'inscription de l'année courante pour afficher le statut d'affectation
        if ($anneeCourante) {
            $baseQuery->with(['inscriptions' => function ($q) use ($anneeCourante) {
                $q->where('annee_universitaire_id', $anneeCourante->id)
                  ->with(['filiere', 'niveau', 'classe', 'anneeUniversitaire']);
            }]);
        }

        if ($status) {
            $baseQuery->where('statut', $status);
        }

        if ($filiere || $niveau || $annee || $classe) {
            $baseQuery->whereHas('inscriptions', function ($q) use ($filiere, $niveau, $annee, $classe) {
                if ($filiere) {
                    $q->where('filiere_id', $filiere);
                }
                if ($niveau) {
                    $q->where('niveau_id', $niveau);
                }
                if ($annee) {
                    $q->where('annee_universitaire_id', $annee);
                }
                if ($classe) {
                    $q->where('classe_id', $classe);
                }
            });
        }

        // Filtre par statut d'affectation (uniquement pour l'année courante avec workflow terminé)
        if ($affectationStatus && $anneeCourante) {
            $baseQuery->whereHas('inscriptions', function ($q) use ($affectationStatus, $anneeCourante) {
                $q->where('annee_universitaire_id', $anneeCourante->id)
                  ->where('workflow_step', 'etudiant_cree')
                  ->where('affectation_status', $affectationStatus);
            });
        }

        // Filtre par inscription validée dans l'année courante (workflow terminé)
        if ($inscritAnneeCourante !== null && $inscritAnneeCourante !== '' && $anneeCourante) {
            if ($inscritAnneeCourante == 'validee') {
                // Inscription validée (workflow_step = etudiant_cree) dans l'année courante
                $baseQuery->whereHas('inscriptions', function ($q) use ($anneeCourante) {
                    $q->where('annee_universitaire_id', $anneeCourante->id)
                      ->where('workflow_step', 'etudiant_cree');
                });
            } elseif ($inscritAnneeCourante == 'en_attente') {
                // Inscription en cours (workflow pas terminé) dans l'année courante
                $baseQuery->whereHas('inscriptions', function ($q) use ($anneeCourante) {
                    $q->where('annee_universitaire_id', $anneeCourante->id)
                      ->where('workflow_step', '!=', 'etudiant_cree');
                });
            } elseif ($inscritAnneeCourante == 'absente') {
                // Aucune inscription dans l'année courante
                $baseQuery->whereDoesntHave('inscriptions', function ($q) use ($anneeCourante) {
                    $q->where('annee_universitaire_id', $anneeCourante->id);
                });
            }
        }

        // Filtre par transfert (uniquement sur les inscriptions de type "première_inscription")
        if ($estTransfert !== null && $estTransfert !== '') {
            $baseQuery->whereHas('inscriptions', function ($q) use ($estTransfert) {
                $q->where('type_inscription', 'première_inscription')
                  ->where('est_transfert', $estTransfert == '1' ? true : false);
            });
        }

        $perPage = 15;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        \Log::info('ESBTPStudentController@index processing', array_merge($baseLogContext, [
            'has_search' => (bool) $search,
            'filters' => [
                'filiere' => $filiere,
                'niveau' => $niveau,
                'annee' => $annee,
                'classe' => $classe,
                'status' => $status,
                'affectation_status' => $affectationStatus,
                'inscrit_annee_courante' => $inscritAnneeCourante,
                'est_transfert' => $estTransfert,
            ],
            'page' => $currentPage,
            'per_page' => $perPage,
        ]));

        $escapeLike = static fn (string $value): string => str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            $value
        );

        if ($search) {
            $candidatesQuery = clone $baseQuery;

            // Tokenize search term (without normalization - keep hyphens intact)
            $searchTokens = collect(preg_split('/[\s,]+/u', $search ?: '', -1, PREG_SPLIT_NO_EMPTY))
                ->map(fn ($token) => trim($token))
                ->filter();

            $candidatesQuery->where(function ($q) use ($search, $searchTokens, $escapeLike) {
                $escapedSearch = $escapeLike($search);
                $likeSearch = "%{$escapedSearch}%";

                // Simple CONCAT search (no REPLACE - keep hyphens)
                $q->where('matricule', 'like', $likeSearch)
                  ->orWhere('nom', 'like', $likeSearch)
                  ->orWhere('prenoms', 'like', $likeSearch)
                  ->orWhere('telephone', 'like', $likeSearch)
                  ->orWhere('email_personnel', 'like', $likeSearch)
                  ->orWhereRaw("CONCAT_WS(' ', prenoms, nom) LIKE ?", [$likeSearch])
                  ->orWhereRaw("CONCAT_WS(' ', nom, prenoms) LIKE ?", [$likeSearch]);

                if ($searchTokens->isNotEmpty()) {
                    $q->orWhere(function ($subQuery) use ($searchTokens, $escapeLike) {
                        foreach ($searchTokens as $token) {
                            $escapedToken = $escapeLike($token);
                            $likeToken = "%{$escapedToken}%";
                            $subQuery->orWhere('nom', 'like', $likeToken)
                                     ->orWhere('prenoms', 'like', $likeToken)
                                     ->orWhere('matricule', 'like', $likeToken)
                                     ->orWhere('email_personnel', 'like', $likeToken)
                                     ->orWhere('telephone', 'like', $likeToken);
                        }
                    });
                }
            });

            $candidates = $candidatesQuery
                ->limit(500)
                ->get();

            \Log::info('SEARCH_DEBUG', [
                'search' => $search,
                'candidates_count' => $candidates->count(),
                'first_5_candidates' => $candidates->take(5)->map(fn ($e) => $e->nom . ' | ' . $e->prenoms)->toArray(),
            ]);

            $scored = $matcher->match($search, $candidates, function ($etudiant) {
                return [
                    'matricule' => $etudiant->matricule,
                    'nom' => $etudiant->nom,
                    'prenoms' => $etudiant->prenoms,
                    'full_name' => trim(($etudiant->prenoms ?? '') . ' ' . ($etudiant->nom ?? '')),
                    'reverse_full_name' => trim(($etudiant->nom ?? '') . ' ' . ($etudiant->prenoms ?? '')),
                    'telephone' => $etudiant->telephone,
                    'email' => $etudiant->email_personnel ?: $etudiant->email,
                ];
            }, [
                'threshold' => 35,
                'limit' => 150,
                'boosts' => [
                    'matricule' => 20,
                    'full_name' => 8,
                    'reverse_full_name' => 8,
                ],
            ]);

            \Log::info('SEARCH_DEBUG_SCORED', [
                'scored_count' => $scored->count(),
                'top_5_scores' => $scored->take(5)->map(function ($e) {
                    $s = isset($e->fuzzy_score) ? $e->fuzzy_score : 'NOT_SET';
                    return ($e->nom ?? '?') . ' | ' . ($e->prenoms ?? '?') . ' => ' . $s;
                })->toArray(),
            ]);

            $scored = $scored->filter(function ($item) {
                $score = is_array($item)
                    ? ($item['fuzzy_score'] ?? null)
                    : (isset($item->fuzzy_score) ? $item->fuzzy_score : null);
                return $score !== null && $score >= 80;
            })->values();

            \Log::info('SEARCH_DEBUG_FILTERED', [
                'after_filter_count' => $scored->count(),
            ]);

            $total = $scored->count();
            $items = $scored->forPage($currentPage, $perPage)->values();

            $etudiants = new LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $currentPage,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );
            $etudiants->appends($request->query());
        } else {
            // Gestion du tri
            $sortColumn = $request->input('sort', 'created_at');
            $sortOrder = $request->input('order', 'desc');

            // Mapping des colonnes frontend vers les colonnes DB
            $columnMapping = [
                'nom' => 'nom',
                'prenom' => 'prenom',
                'matricule' => 'matricule',
                'email' => 'email',
                'telephone' => 'telephone',
                'date' => 'date_naissance',
                'statut' => 'statut',
                'created_at' => 'created_at',
            ];

            // Valider la colonne de tri
            $sortColumn = $columnMapping[$sortColumn] ?? 'created_at';
            $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? strtolower($sortOrder) : 'desc';

            // Appliquer le tri
            $baseQuery->orderBy($sortColumn, $sortOrder);

            $etudiants = $baseQuery->paginate($perPage)->appends($request->query());
        }

        // Récupérer les listes pour les filtres
        $filieres = ESBTPFiliere::where('is_active', true)->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->get();
        $annees = ESBTPAnneeUniversitaire::orderBy('start_date', 'desc')->get();
        $classes = ESBTPClasse::where('is_active', true)
            ->with(['filiere', 'niveauEtude'])
            ->orderBy('name')
            ->get();

        \Log::info('ESBTPStudentController@index completed', array_merge($baseLogContext, [
            'timestamp' => now()->toIso8601String(),
            'total' => $etudiants->total(),
            'page' => $etudiants->currentPage(),
            'per_page' => $etudiants->perPage(),
            'duration_ms' => round((microtime(true) - $startMicrotime) * 1000, 2),
        ]));

        if ($request->ajax()) {
            \Log::info('ESBTPStudentController@index returning AJAX response', array_merge($baseLogContext, [
                'timestamp' => now()->toIso8601String(),
                'duration_ms' => round((microtime(true) - $startMicrotime) * 1000, 2),
            ]));
            return response()->json([
                'html' => view('esbtp.etudiants.partials.results', [
                    'etudiants' => $etudiants,
                ])->render(),
                'url' => $request->fullUrl(),
            ]);
        }

        \Log::info('ESBTPStudentController@index returning view', array_merge($baseLogContext, [
            'timestamp' => now()->toIso8601String(),
            'duration_ms' => round((microtime(true) - $startMicrotime) * 1000, 2),
        ]));

        return view('esbtp.etudiants.index', compact(
            'etudiants',
            'filieres',
            'niveaux',
            'annees',
            'anneeCourante',
            'search',
            'filiere',
            'niveau',
            'annee',
            'classe',
            'classes',
            'status',
            'affectationStatus',
            'inscritAnneeCourante',
            'estTransfert'
        ));
    }

    public function create()
    {
        return redirect()->route('esbtp.inscriptions.create')
            ->with('info', 'Veuillez utiliser le formulaire d\'inscription pour ajouter un nouvel étudiant.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'matricule' => 'required|string|unique:esbtp_etudiants,matricule',
            'nom' => 'required|string|max:255',
            'prenoms' => 'required|string|max:255',
            'sexe' => 'required|in:M,F',
            'date_naissance' => 'required|date',
            'lieu_naissance' => 'required|string|max:255',
            'nationalite' => 'required|string|max:255',
            'adresse' => 'required|string|max:255',
            'telephone' => 'required|string|max:20',
            'email_personnel' => 'required|email|max:255',
            'statut' => 'required|in:actif,inactif'
        ]);

        ESBTPEtudiant::create($validated);

        return redirect()->route('esbtp.etudiants.index')->with('success', 'Étudiant créé avec succès.');
    }

    public function show(ESBTPEtudiant $etudiant, EtudiantDossierService $dossierService)
    {
        $etudiant->load([
            'user',
            'parents' => fn($q) => $q->with('etudiants'),
            'inscriptions' => fn($q) => $q->with([
                    'filiere', 'niveauEtude', 'anneeUniversitaire',
                    'paiements', 'fraisSubscriptions.fraisCategory',
                    'classe' => fn($cq) => $cq->with(['filiere', 'niveauEtude']),
                ])
                ->orderByDesc('created_at'),
            'paiements' => fn($q) => $q->with(['inscription', 'fraisCategory', 'categorie', 'validatedBy'])
                                       ->orderByDesc('date_paiement'),
            'absences',
            'documents' => fn($q) => $q->with('uploadedBy')->orderByDesc('created_at'),
        ]);

        $dossier       = $dossierService->buildDossier($etudiant);
        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // ── Reliquats ──
        $inscriptionIds = $etudiant->inscriptions->pluck('id');
        $reliquatsEntrants = \App\Models\ESBTPReliquatDetail::whereIn('inscription_destination_id', $inscriptionIds)
            ->with(['inscriptionSource.anneeUniversitaire', 'fraisSubscription.fraisCategory', 'fraisSubscription.selectedOption'])
            ->actifs()
            ->get();
        $reliquatsSortants = \App\Models\ESBTPReliquatDetail::whereIn('inscription_source_id', $inscriptionIds)
            ->with(['inscriptionDestination.anneeUniversitaire', 'fraisSubscription.fraisCategory', 'fraisSubscription.selectedOption'])
            ->get();

        // ── Statistiques ──
        $statistiques = [
            'total_paiements' => $etudiant->paiements->sum('montant'),
            'paiements_valides' => $etudiant->paiements->where('status', 'validé')->sum('montant'),
            'paiements_en_attente' => $etudiant->paiements->where('status', 'en_attente')->sum('montant'),
            'nombre_paiements' => $etudiant->paiements->count(),
            'inscription_active' => $etudiant->inscriptions->where('status', 'active')->first(),
            'derniere_inscription' => $etudiant->inscriptions->first(),
            'total_reliquats_entrants' => $reliquatsEntrants->sum('solde_restant'),
            'total_reliquats_sortants' => $reliquatsSortants->sum('solde_restant'),
            'nombre_reliquats_actifs' => $reliquatsEntrants->where('statut', 'actif')->count(),
        ];

        $categoriesfrais = \App\Models\ESBTPFraisCategory::where('is_active', true)->orderBy('name')->get();

        // ── Détection LMD ──
        $isLMD = false;
        $bulletinLMD = null;
        $parcours = null;
        $lmdCredits = null;

        $inscActive = $anneeCourante
            ? $etudiant->inscriptions->first(fn($i) => $i->annee_universitaire_id === $anneeCourante->id && $i->status === 'active')
            : null;
        // Classe courante = UNIQUEMENT si inscrit dans l'année courante
        $classeCourante = $inscActive?->classe;

        $bulletinsLMD = collect();
        $lmdMoyenneAnnuelle = null;

        // ── isLMD = inscrit cette année dans classe LMD (pour KPIs courants) ──
        if ($classeCourante && $classeCourante->isLMD()) {
            $isLMD = true;
            $parcours = $classeCourante->parcours?->load('mention.domaine');

            // Bulletins LMD de cette classe pour L'ANNÉE COURANTE uniquement
            $bulletinsLMD = \App\Models\ESBTPLMDBulletin::where('etudiant_id', $etudiant->id)
                ->where('classe_id', $classeCourante->id)
                ->where('annee_universitaire_id', $anneeCourante->id)
                ->with(['resultatsUEs.uniteEnseignement', 'resultatsECUEs.matiere', 'deliberation'])
                ->orderBy('semestre')
                ->get();

            $bulletinLMD = $bulletinsLMD->last();

            // Moyenne annuelle pondérée par crédits
            $bulletinsAvecMoyenne = $bulletinsLMD->filter(fn($b) => $b->moyenne_generale > 0);
            if ($bulletinsAvecMoyenne->count() > 1) {
                $totalCredits = $bulletinsAvecMoyenne->sum('credits_totaux');
                $lmdMoyenneAnnuelle = $totalCredits > 0
                    ? round($bulletinsAvecMoyenne->sum(fn($b) => $b->moyenne_generale * $b->credits_totaux) / $totalCredits, 2)
                    : round($bulletinsAvecMoyenne->avg('moyenne_generale'), 2);
            } elseif ($bulletinsAvecMoyenne->count() === 1) {
                $lmdMoyenneAnnuelle = round($bulletinsAvecMoyenne->first()->moyenne_generale, 2);
            }
        }

        // ── Crédits CECT cumulés = TOUTES les inscriptions LMD (capitalisés à vie) ──
        $allLmdInscs = $etudiant->inscriptions->filter(fn($i) => optional($i->classe)->systeme_academique === 'LMD');
        if ($allLmdInscs->count()) {
            $allLmdClasseIds = $allLmdInscs->pluck('classe_id')->unique()->filter();
            $allLmdBulletins = \App\Models\ESBTPLMDBulletin::where('etudiant_id', $etudiant->id)
                ->whereIn('classe_id', $allLmdClasseIds)
                ->get();

            // Parcours : depuis l'inscription LMD la plus récente (même si pas année courante)
            if (!$parcours) {
                $lastLmdInsc = $allLmdInscs->first(); // déjà triée desc par created_at
                $parcours = $lastLmdInsc?->classe?->parcours?->load('mention.domaine');
            }

            $lmdCredits = [
                'capitalises' => $allLmdBulletins->count() ? $allLmdBulletins->sum('credits_capitalises') : null,
                'totaux' => $allLmdBulletins->count() ? ($allLmdBulletins->sum('credits_totaux') ?: 30) : null,
                'semestres' => $classeCourante ? $classeCourante->getSemestresLMD() : [],
            ];
        }

        return view('esbtp.etudiants.show', compact(
            'etudiant', 'dossier', 'anneeCourante',
            'isLMD', 'bulletinLMD', 'bulletinsLMD', 'lmdMoyenneAnnuelle', 'parcours', 'lmdCredits',
            'statistiques', 'reliquatsEntrants', 'reliquatsSortants', 'categoriesfrais'
        ));
    }

    public function edit(Request $request, ESBTPEtudiant $etudiant)
    {
        // Charger les relations nécessaires
        $etudiant->load(['user', 'parents', 'inscriptions.filiere', 'inscriptions.niveau', 'inscriptions.classe']);

        // Récupérer les données pour les selects
        $filieres = ESBTPFiliere::where('is_active', true)->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->get();
        $classes = ESBTPClasse::where('is_active', true)->get();
        $annees = ESBTPAnneeUniversitaire::orderBy('start_date', 'desc')->get();

        // Récupérer l'inscription la plus récente pour la génération de matricule
        // Le JS edit-form-scripts.blade.php en a besoin pour appeler /matricule-config/generate
        $niveauEtudeCode = null;
        $filiereIdForMatricule = null;
        $inscriptionRecente = $etudiant->inscriptions()
            ->with(['classe.niveauEtude', 'filiere'])
            ->orderByDesc('created_at')
            ->first();

        if ($inscriptionRecente) {
            // Chemin : inscription → classe → niveauEtude → code
            if ($inscriptionRecente->classe && $inscriptionRecente->classe->niveauEtude) {
                $niveauEtudeCode = $inscriptionRecente->classe->niveauEtude->code;
            }
            $filiereIdForMatricule = $inscriptionRecente->filiere_id;
        }

        if ($request->boolean('embedded')) {
            return view('esbtp.etudiants.embed.edit', compact(
                'etudiant', 'niveauEtudeCode', 'filiereIdForMatricule', 'inscriptionRecente'
            ));
        }

        return view('esbtp.etudiants.edit', compact(
            'etudiant',
            'filieres',
            'niveaux',
            'classes',
            'annees',
            'niveauEtudeCode',
            'filiereIdForMatricule',
            'inscriptionRecente'
        ));
    }

    public function update(Request $request, ESBTPEtudiant $etudiant)
    {
        // Déléguer à la version enrichie du contrôleur pour conserver l'unique flux (parents, photo, logs, etc.)
        return app(ESBTPEtudiantController::class)->update($request, $etudiant);
    }

    public function destroy(ESBTPEtudiant $etudiant)
    {
        // Vérifier les permissions
        if (!auth()->user()->can('delete_students')) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas les permissions pour supprimer des étudiants.'
            ], 403);
        }

        try {
            $keepUser = request()->input('keep_user', false);

            // Utiliser la commande Artisan pour une suppression complète et sécurisée
            $exitCode = \Artisan::call('esbtp:delete-student', [
                'identifier' => $etudiant->id,
                '--force' => true,
                '--keep-user' => $keepUser
            ]);

            if ($exitCode === 0) {
                // Succès
                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Étudiant supprimé avec succès.',
                        'redirect' => route('esbtp.etudiants.index')
                    ]);
                }

                return redirect()->route('esbtp.etudiants.index')
                    ->with('success', 'Étudiant supprimé avec succès.');
            } else {
                throw new \Exception('La commande de suppression a échoué.');
            }

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression d\'étudiant', [
                'etudiant_id' => $etudiant->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    public function restore($id)
    {
        try {
            ESBTPEtudiant::withTrashed()->findOrFail($id)->restore();
            return redirect()->route('esbtp.etudiants.index')
                ->with('success', 'Étudiant restauré avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la restauration: ' . $e->getMessage());
        }
    }

    public function genererCertificat(ESBTPEtudiant $etudiant)
    {
        // Charger les relations nécessaires
        $etudiant->load([
            'inscriptions' => function($q) {
                $q->with(['filiere', 'niveau', 'classe', 'anneeUniversitaire'])
                  ->orderBy('date_inscription', 'desc')
                  ->first();
            }
        ]);

        // Vérifier si l'étudiant a une inscription active
        if (!$etudiant->inscriptions->count()) {
            return back()->with('error', 'Aucune inscription trouvée pour cet étudiant.');
        }

        $inscription = $etudiant->inscriptions->first();

        // Générer le PDF
        $pdf = PDF::loadView('esbtp.etudiants.certificat', compact('etudiant', 'inscription'));

        // Retourner le PDF pour téléchargement
        return $pdf->download('certificat_scolarite_' . Str::slug($etudiant->nom_complet) . '.pdf');
    }

    /**
     * Récupérer toutes les inscriptions d'un étudiant (pour le modal d'édition rapide)
     */
    public function getAllInscriptions(Request $request, ESBTPEtudiant $etudiant)
    {
        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $currentYearId = $anneeCourante->id ?? null;

        $inscriptions = $etudiant->inscriptions()
            ->with(['filiere', 'niveau', 'classe', 'anneeUniversitaire'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($inscription) use ($currentYearId) {
                $anneeLabel = $inscription->anneeUniversitaire->name
                    ?? $inscription->anneeUniversitaire->libelle
                    ?? 'Année non renseignée';

                return [
                    'id' => $inscription->id,
                    'annee' => $anneeLabel,
                    'classe' => $inscription->classe->name ?? 'Non assignée',
                    'filiere' => $inscription->filiere->name ?? null,
                    'niveau' => $inscription->niveau->name ?? null,
                    'status' => $inscription->status,
                    'affectation_status' => $inscription->affectation_status,
                    'type' => $inscription->type_inscription,
                    'is_current_year' => $currentYearId && $inscription->annee_universitaire_id == $currentYearId,
                    'date_label' => optional($inscription->date_inscription)->format('d/m/Y'),
                    'date_value' => optional($inscription->date_inscription)->format('Y-m-d'),
                    'workflow_step' => $inscription->workflow_step,
                    'paiement_validation_id' => $inscription->paiement_validation_id,
                    'edit_url' => route('esbtp.inscriptions.edit', ['inscription' => $inscription->id, 'embedded' => 1]),
                    'validate_url' => route('esbtp.inscriptions.valider-definitivement', ['inscription' => $inscription->id]),
                ];
            });

        return response()->json([
            'success' => true,
            'inscriptions' => $inscriptions
        ]);
    }

    /**
     * Build filtered query for exports.
     * Supports both single-value (from page filters) and array (from export modal) params.
     */
    protected function buildExportQuery(Request $request)
    {
        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        $query = \App\Models\ESBTPInscription::query()
            ->with(['etudiant', 'filiere', 'niveau', 'classe', 'anneeUniversitaire'])
            ->whereHas('etudiant', function ($q) use ($request) {
                if ($request->filled('status')) {
                    $q->where('statut', $request->input('status'));
                }
                if ($request->filled('search')) {
                    $search = '%' . $request->input('search') . '%';
                    $q->where(function ($sub) use ($search) {
                        $sub->where('matricule', 'like', $search)
                            ->orWhere('nom', 'like', $search)
                            ->orWhere('prenoms', 'like', $search);
                    });
                }
            });

        // classes[] is the primary filter from the export modal (single source of truth).
        // When classes[] is provided, skip filiere/niveau filters (they are redundant).
        if ($request->filled('classes')) {
            $ids = array_filter((array) $request->input('classes'));
            if (!empty($ids)) {
                $query->whereIn('classe_id', $ids);
            }
        } else {
            // Fallback: page-level single-select filters
            if ($request->filled('filiere')) {
                $query->where('filiere_id', $request->input('filiere'));
            }
            if ($request->filled('niveau')) {
                $query->where('niveau_id', $request->input('niveau'));
            }
            if ($request->filled('classe')) {
                $query->where('classe_id', $request->input('classe'));
            }
        }

        if ($request->filled('annee')) {
            $query->where('annee_universitaire_id', $request->input('annee'));
        } elseif ($anneeCourante) {
            $query->where('annee_universitaire_id', $anneeCourante->id);
        }

        if ($request->filled('affectation_status') && $anneeCourante) {
            $query->where('affectation_status', $request->input('affectation_status'));
        }

        if ($request->filled('inscrit_annee_courante') && $anneeCourante) {
            $val = $request->input('inscrit_annee_courante');
            if ($val === 'validee') {
                $query->where('workflow_step', 'etudiant_cree');
            } elseif ($val === 'en_attente') {
                $query->where('workflow_step', '!=', 'etudiant_cree');
            }
        }

        $query->where('status', 'active');

        return $query->orderBy('esbtp_inscriptions.id');
    }

    /**
     * Prepare export data collection from filtered inscriptions.
     */
    protected function getExportData(Request $request): \Illuminate\Support\Collection
    {
        $inscriptions = $this->buildExportQuery($request)->get();

        return $inscriptions->map(function ($inscription) {
            return [
                'etudiant' => $inscription->etudiant,
                'inscription' => $inscription,
            ];
        });
    }

    /**
     * Export student list to Excel.
     */
    public function exportExcel(Request $request)
    {
        try {
            $data = $this->getExportData($request);
            $groupBy = $request->input('group_by');

            $export = new EtudiantsExport($data, $groupBy);

            $suffix = $groupBy ? "-par-{$groupBy}" : '';
            $filename = 'etudiants' . $suffix . '-' . now()->format('Y-m-d') . '.xlsx';

            \Log::info('Export Excel étudiants', [
                'user_id' => auth()->id(),
                'total' => $data->count(),
                'group_by' => $groupBy,
                'filters' => $request->only(['filiere', 'niveau', 'classe', 'annee', 'status']),
            ]);

            return Excel::download($export, $filename);
        } catch (\Exception $e) {
            \Log::error('Erreur export Excel étudiants: ' . $e->getMessage(), ['trace' => config('app.debug') ? $e->getTraceAsString() : null]);

            return redirect()->back()->with('error', "Erreur lors de l'export Excel : " . $e->getMessage());
        }
    }

    /**
     * Export student list to PDF (with FPDI chunk+merge for large datasets).
     */
    public function exportPdf(Request $request)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        try {
            $data = $this->getExportData($request);
            $groupBy = $request->input('group_by');
            $totalEtudiants = $data->count();

            $schoolInfo = SettingsHelper::getSchoolInfo();
            $etablissement = [
                'nom' => $schoolInfo['name'] ?? 'KLASSCI',
                'adresse' => $schoolInfo['address'] ?? '',
                'telephone' => $schoolInfo['phone'] ?? '',
                'email' => $schoolInfo['email'] ?? '',
                'logo' => $schoolInfo['logo'] ?? '',
            ];

            // Build active filters description
            $filterLabels = [];

            if ($request->filled('classes')) {
                $names = ESBTPClasse::whereIn('id', (array) $request->input('classes'))->pluck('name');
                if ($names->isNotEmpty()) {
                    $filterLabels[] = 'Classe(s) : ' . $names->join(', ');
                }
            } elseif ($request->filled('classe')) {
                $classe = ESBTPClasse::find($request->input('classe'));
                if ($classe) {
                    $filterLabels[] = 'Classe : ' . $classe->name;
                }
            }
            if ($request->filled('filiere')) {
                $filiere = ESBTPFiliere::find($request->input('filiere'));
                if ($filiere) {
                    $filterLabels[] = 'Filière : ' . $filiere->name;
                }
            }
            if ($request->filled('niveau')) {
                $niveau = ESBTPNiveauEtude::find($request->input('niveau'));
                if ($niveau) {
                    $filterLabels[] = 'Niveau : ' . $niveau->name;
                }
            }
            if ($request->filled('annee')) {
                $annee = ESBTPAnneeUniversitaire::find($request->input('annee'));
                if ($annee) {
                    $filterLabels[] = 'Année : ' . $annee->name;
                }
            }
            if ($request->filled('status')) {
                $filterLabels[] = 'Statut : ' . ucfirst($request->input('status'));
            }

            // Group data if requested
            if ($groupBy) {
                $groups = $data->groupBy(function ($item) use ($groupBy) {
                    $inscription = $item['inscription'] ?? null;
                    return match ($groupBy) {
                        'classe' => $inscription?->classe?->name ?? 'Sans classe',
                        'filiere' => $inscription?->filiere?->name ?? 'Sans filière',
                        'niveau' => $inscription?->niveau?->name ?? 'Sans niveau',
                        'filiere_niveau' => ($inscription?->filiere?->name ?? 'Sans filière') . ' — ' . ($inscription?->niveau?->name ?? 'Sans niveau'),
                        default => 'Tous',
                    };
                });
            } else {
                $groups = collect(['Tous les étudiants' => $data]);
            }

            $suffix = $groupBy ? "-par-{$groupBy}" : '';
            $filename = 'etudiants' . $suffix . '-' . now()->format('Y-m-d') . '.pdf';

            $pdfOptions = [
                'dpi' => 72,
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'isFontSubsettingEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ];

            \Log::info('Export PDF étudiants', [
                'user_id' => auth()->id(),
                'total' => $totalEtudiants,
                'group_by' => $groupBy,
            ]);

            // Small dataset: direct DomPDF render
            if ($totalEtudiants <= 500) {
                $pdf = PDF::loadView('esbtp.etudiants.export-pdf', [
                    'groups' => $groups,
                    'totalEtudiants' => $totalEtudiants,
                    'etablissement' => $etablissement,
                    'filterLabels' => $filterLabels,
                    'groupBy' => $groupBy,
                ]);
                $pdf->setPaper('a4', 'landscape')->setOptions($pdfOptions);

                return $pdf->download($filename);
            }

            // Large dataset: chunk + merge with FPDI
            $chunkSize = 200;
            $tempDir = storage_path('app/temp');
            if (! is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Flatten all items for chunking (preserve group info per item)
            $allItems = collect();
            foreach ($groups as $groupName => $items) {
                foreach ($items as $item) {
                    $allItems->push(array_merge($item, ['_group' => $groupName]));
                }
            }

            $chunks = $allItems->chunk($chunkSize);
            $tempFiles = [];
            $totalChunks = $chunks->count();

            foreach ($chunks as $chunkIndex => $chunk) {
                $isFirstChunk = ($chunkIndex === 0);
                $isLastChunk = ($chunkIndex === $totalChunks - 1);
                $rowOffset = $chunkIndex * $chunkSize;

                // Re-group chunk items
                $chunkGroups = $chunk->groupBy('_group')->map(function ($items) {
                    return $items->map(function ($item) {
                        unset($item['_group']);
                        return $item;
                    });
                });

                $chunkPdf = PDF::loadView('esbtp.etudiants.export-pdf', [
                    'groups' => $chunkGroups,
                    'totalEtudiants' => $totalEtudiants,
                    'etablissement' => $etablissement,
                    'filterLabels' => $filterLabels,
                    'groupBy' => $groupBy,
                    'isFirstChunk' => $isFirstChunk,
                    'isLastChunk' => $isLastChunk,
                    'rowOffset' => $rowOffset,
                    'chunkIndex' => $chunkIndex,
                ]);
                $chunkPdf->setPaper('a4', 'landscape')->setOptions($pdfOptions);

                $tempPath = $tempDir . '/etudiants_chunk_' . uniqid() . '_' . $chunkIndex . '.pdf';
                file_put_contents($tempPath, $chunkPdf->output());
                $tempFiles[] = $tempPath;

                unset($chunkPdf);
            }

            // Merge all chunks with FPDI
            $merger = new \setasign\Fpdi\Fpdi();
            $merger->SetAutoPageBreak(false);

            foreach ($tempFiles as $file) {
                $pageCount = $merger->setSourceFile($file);
                for ($p = 1; $p <= $pageCount; $p++) {
                    $tpl = $merger->importPage($p);
                    $size = $merger->getTemplateSize($tpl);
                    $merger->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $merger->useTemplate($tpl, 0, 0, $size['width'], $size['height']);
                }
            }

            $finalPath = $tempDir . '/etudiants_final_' . uniqid() . '.pdf';
            $merger->Output('F', $finalPath);
            unset($merger);

            // Cleanup temp chunk files
            foreach ($tempFiles as $file) {
                @unlink($file);
            }

            return response()->download($finalPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            \Log::error('Erreur export PDF étudiants: ' . $e->getMessage(), ['trace' => config('app.debug') ? $e->getTraceAsString() : null]);

            return redirect()->back()->with('error', "Erreur lors de l'export PDF : " . $e->getMessage());
        }
    }
}
