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
use PDF;

class ESBTPStudentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view_students', ['only' => ['index', 'show', 'genererCertificat']]);
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
            ])->filter(function ($item) {
                $score = null;
                if (is_array($item)) {
                    $score = $item['fuzzy_score'] ?? null;
                } elseif (is_object($item) && property_exists($item, 'fuzzy_score')) {
                    $score = $item->fuzzy_score;
                }
                return $score === null || $score >= 80;
            })->values();

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
            'inscriptions' => fn($q) => $q->with(['filiere', 'niveauEtude', 'classe', 'anneeUniversitaire'])
                                          ->orderByDesc('created_at'),
            'paiements' => fn($q) => $q->with(['fraisCategory', 'validatedBy'])
                                       ->orderByDesc('date_paiement'),
        ]);

        $dossier       = $dossierService->buildDossier($etudiant);
        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        return view('esbtp.etudiants.show', compact('etudiant', 'dossier', 'anneeCourante'));
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
}
