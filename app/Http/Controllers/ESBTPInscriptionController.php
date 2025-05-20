<?php

namespace App\Http\Controllers;

use App\Models\ESBTPInscription;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPParent;
use App\Services\ESBTPInscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\ESBTP\FeeCategory;
use App\Models\ESBTP\Fee;

class ESBTPInscriptionController extends Controller
{
    protected $inscriptionService;

    /**
     * Constructeur avec injection du service d'inscription
     */
    public function __construct(ESBTPInscriptionService $inscriptionService)
    {
        $this->inscriptionService = $inscriptionService;
        $this->middleware('auth');
        $this->middleware('permission:inscriptions.view', ['only' => ['index', 'show']]);
        $this->middleware('permission:inscriptions.create', ['only' => ['create', 'store']]);
        $this->middleware('permission:inscriptions.edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:inscriptions.delete', ['only' => ['destroy']]);
        $this->middleware('permission:inscriptions.validate', ['only' => ['valider', 'annuler']]);
    }

    /**
     * Afficher la liste des inscriptions.
     */
    public function index(Request $request)
    {
        // Récupérer les filtres de recherche
        $search = $request->input('search');
        $filiere = $request->input('filiere');
        $niveau = $request->input('niveau');
        $annee = $request->input('annee');
        $status = $request->input('status', 'active');

        // Construire la requête avec les filtres
        $query = ESBTPInscription::query()
            ->with(['etudiant', 'filiere', 'niveau', 'classe', 'anneeUniversitaire']);

        // Appliquer les filtres
        if ($search) {
            $query->whereHas('etudiant', function($q) use ($search) {
                $q->where('matricule', 'like', "%{$search}%")
                  ->orWhere('nom', 'like', "%{$search}%")
                  ->orWhere('prenoms', 'like', "%{$search}%");
            });
        }

        if ($filiere) {
            $query->where('filiere_id', $filiere);
        }

        if ($niveau) {
            $query->where('niveau_id', $niveau);
        }

        if ($annee) {
            $query->where('annee_universitaire_id', $annee);
        } else {
            // Par défaut, filtrer par année en cours
            $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
            if ($anneeEnCours) {
                $query->where('annee_universitaire_id', $anneeEnCours->id);
            }
        }

        if ($status) {
            $query->where('status', $status);
        }

        // Récupérer les inscriptions paginées
        $inscriptions = $query->latest()->paginate(15);

        // Récupérer les listes pour les filtres
        $filieres = ESBTPFiliere::where('is_active', true)->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->get();
        $annees = ESBTPAnneeUniversitaire::orderBy('start_date', 'desc')->get();
        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Calculer les statistiques
        $statsQuery = ESBTPInscription::query();

        if ($filiere) {
            $statsQuery->where('filiere_id', $filiere);
        }

        if ($niveau) {
            $statsQuery->where('niveau_id', $niveau);
        }

        if ($annee) {
            $statsQuery->where('annee_universitaire_id', $annee);
        } elseif ($anneeEnCours) {
            $statsQuery->where('annee_universitaire_id', $anneeEnCours->id);
        }

        $stats = [
            'total' => $statsQuery->count(),
            'actives' => (clone $statsQuery)->where('status', 'active')->count(),
            'en_attente' => (clone $statsQuery)->where('status', 'en_attente')->count(),
            'annulees' => (clone $statsQuery)->where('status', 'annulée')->count(),
            'terminees' => (clone $statsQuery)->where('status', 'terminée')->count(),
        ];

        return view('esbtp.inscriptions.index', compact(
            'inscriptions',
            'filieres',
            'niveaux',
            'annees',
            'search',
            'filiere',
            'niveau',
            'annee',
            'status',
            'stats',
            'anneeEnCours'
        ));
    }

    /**
     * Afficher le formulaire de création d'inscription.
     */
    public function create()
    {
        $filieres = ESBTPFiliere::where('is_active', true)->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->get();
        $annees = ESBTPAnneeUniversitaire::where('is_active', true)->get();
        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_active', true)->first();

        // Renommer les variables pour les utiliser dans le modal
        $anneeUniversitaires = $annees;
        $niveauEtudes = $niveaux;

        // Charger les catégories de frais optionnels
        $optionalFeeCategories = FeeCategory::where('is_active', true)->where('is_mandatory', false)->get();
        // Charger les catégories de frais obligatoires
        $mandatoryFeeCategories = FeeCategory::where('is_active', true)->where('is_mandatory', true)->get();

        return view('esbtp.inscriptions.create', compact(
            'filieres', 'niveaux', 'annees', 'anneeEnCours',
            'anneeUniversitaires', 'niveauEtudes',
            'optionalFeeCategories', 'mandatoryFeeCategories'
        ));
    }

    /**
     * Enregistrer une nouvelle inscription.
     */
    public function store(Request $request)
    {
        // Validation des données
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:100',
            'prenoms' => 'required|string|max:100',
            'sexe' => 'required|in:M,F',
            'date_naissance' => 'nullable|date',
            'lieu_naissance' => 'nullable|string|max:100',
            'telephone' => 'required|string|max:20',
            'email_personnel' => 'nullable|email|max:100',
            'ville' => 'nullable|string|max:100',
            'commune' => 'nullable|string|max:100',
            'photo' => 'nullable|image|max:2048',
            'matricule' => 'nullable|string|max:20|unique:esbtp_etudiants,matricule',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Log des données soumises pour débogage
            Log::info('Données reçues:', $request->all());

            DB::beginTransaction();

            // Récupérer les informations complètes de la classe sélectionnée
            $classe = ESBTPClasse::with(['filiere', 'niveau', 'annee'])
                ->findOrFail($request->classe_id);

            // Préparer les données de l'étudiant
            $etudiantData = [
                'nom' => $request->nom,
                'prenoms' => $request->prenoms,
                'sexe' => $request->sexe,
                'date_naissance' => $request->date_naissance,
                'lieu_naissance' => $request->lieu_naissance,
                'email_personnel' => $request->email_personnel,
                'telephone' => $request->telephone,
                'adresse' => $request->adresse,
                'ville' => $request->ville,
                'commune' => $request->commune,
                'statut' => 'actif',
                'creer_compte_utilisateur' => true,
                'matricule' => $request->matricule,
            ];

            // Ajouter un log pour déboguer
            \Log::info('Données de l\'étudiant', [
                'etudiantData' => $etudiantData,
                'matriculeFromRequest' => $request->matricule
            ]);

            // Ajouter un log supplémentaire pour les champs ville et commune
            \Log::info('Champs de résidence', [
                'ville' => $request->ville,
                'commune' => $request->commune,
                'lieu_naissance' => $request->lieu_naissance,
                'adresse' => $request->adresse
            ]);

            // Traiter la photo si fournie
            if ($request->hasFile('photo')) {
                $etudiantData['photo'] = $this->handlePhotoUpload($request->file('photo'));
            }

            // Préparer les données d'inscription
            $inscriptionData = [
                'date_inscription' => $request->date_inscription ?? now()->format('Y-m-d'),
                'classe_id' => $classe->id,
                'annee_universitaire_id' => $classe->annee_universitaire_id,
                'status' => 'en_attente',
                'filiere_id' => $classe->filiere_id,
                'niveau_id' => $classe->niveau_etude_id,
                'type_inscription' => 'première_inscription',
                'montant_scolarite' => $request->montant_scolarite ?? 0,
                'frais_inscription' => $request->frais_inscription ?? 0,
            ];

            // Si la classe a des relations filière et niveau, les ajouter aux données de l'étudiant
            if ($classe->filiere_id) {
                $etudiantData['filiere_id'] = $classe->filiere_id;
            }

            if ($classe->niveau_etude_id) {
                $etudiantData['niveau_etude_id'] = $classe->niveau_etude_id;
            }

            // Préparer les données de paiement
            $paiementData = null;
            if ($request->filled('montant_verse') && $request->montant_verse > 0) {
                $paiementData = [
                    'montant' => $request->montant_verse,
                    'methode' => $request->methode_paiement ?? 'Espèces',
                    'reference' => $request->reference_paiement,
                    'date' => $request->date_paiement ?? now(),
                    'commentaire' => $request->commentaire,
                ];
            }

            // Préparer les données des parents
            $parentsData = [];

            // Traiter les parents du formulaire
            if ($request->has('parents')) {
                foreach ($request->parents as $parent) {
                    if (isset($parent['type']) && $parent['type'] === 'existant' && !empty($parent['parent_id'])) {
                        // Parent existant sélectionné
                        $parentsData[] = [
                            'parent_id' => $parent['parent_id'],
                            'relation' => $parent['relation'] ?? 'Autre'
                        ];
                    }
                    elseif (isset($parent['type']) && $parent['type'] === 'nouveau' && !empty($parent['nom']) && !empty($parent['prenoms'])) {
                        // Nouveau parent
                        $parentsData[] = [
                            'nom' => $parent['nom'],
                            'prenoms' => $parent['prenoms'],
                            'email' => $parent['email'] ?? null,
                            'telephone' => $parent['telephone'] ?? null,
                            'profession' => $parent['profession'] ?? null,
                            'relation' => $parent['relation'] ?? 'Autre',
                            'adresse' => $parent['adresse'] ?? null
                        ];
                    }
                }
            }

            $selectedOptionals = $request->input('fee_optionals', []);

            // Vérifier que le paiement initial couvre le frais d'inscription obligatoire
            $mandatoryFeeCategories = FeeCategory::where('is_active', true)->where('is_mandatory', true)->get();
            \Log::info('Catégories obligatoires trouvées', ['categories' => $mandatoryFeeCategories]);
            $mandatoryTotal = $mandatoryFeeCategories->sum('amount');
            $initialPayment = $request->input('paiement_montant');
            if ($mandatoryTotal > 0 && (!$initialPayment || $initialPayment < $mandatoryTotal)) {
                return back()->withErrors(['paiement_montant' => 'Le paiement initial doit couvrir au moins le montant total des frais obligatoires ('.$mandatoryTotal.' FCFA).'])->withInput();
            }

            // Créer l'inscription
            $inscription = $this->inscriptionService->createInscription(
                $etudiantData,
                $inscriptionData,
                $parentsData,
                $paiementData,
                auth()->id(),
                $selectedOptionals
            );

            DB::commit();

            // Stocker les informations du compte dans la session
            if ($inscription && $inscription->etudiant && $inscription->etudiant->user) {
                $user = $inscription->etudiant->user;
                session()->flash('account_info', [
                    'username' => $user->username,
                    'password' => session('generated_password'),
                    'role' => 'Étudiant'
                ]);
            }

            return redirect()->route('esbtp.inscriptions.show', $inscription)
                ->with('success', 'Inscription enregistrée avec succès !');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur lors de l\'inscription: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de l\'inscription. Veuillez réessayer.')
                ->withInput();
        }
    }

    /**
     * Afficher les détails d'une inscription.
     */
    public function show(ESBTPInscription $inscription)
    {
        // Charger toutes les relations nécessaires, y compris payments
        $inscription->load([
            'etudiant.parents',
            'filiere',
            'niveau',
            'classe',
            'anneeUniversitaire',
            'paiements',
            'payments',
        ]);

        // Frais/échéances liés à l'inscription
        $fees = \App\Models\ESBTP\Fee::where('inscription_id', $inscription->id)->orderBy('due_date')->get();
        // Paiements validés liés à l'inscription
        $soldeRestant = $fees->sum(function($fee) {
            return $fee->amount - $fee->totalPaidAmount();
        });

        // Charger toutes les catégories de frais obligatoires actives
        $mandatoryFeeCategories = \App\Models\ESBTP\FeeCategory::where('is_active', true)->where('is_mandatory', true)->get();
        $mandatoryFeeCategoriesWithRules = [];
        foreach ($mandatoryFeeCategories as $category) {
            $rule = \App\Models\ESBTP\FeeCategoryRule::where('fee_category_id', $category->id)
                ->where('filiere_id', $inscription->filiere_id)
                ->where('niveau_id', $inscription->niveau_id)
                ->where(function($q) use ($inscription) {
                    $q->where('annee_universitaire_id', $inscription->annee_universitaire_id)
                      ->orWhereNull('annee_universitaire_id');
                })
                ->orderByRaw('annee_universitaire_id = ? desc', [$inscription->annee_universitaire_id]) // Priorité à la règle spécifique
                ->first();
            $mandatoryFeeCategoriesWithRules[] = [
                'category' => $category,
                'rule' => $rule
            ];
        }

        return view('esbtp.inscriptions.show', compact('inscription', 'fees', 'soldeRestant', 'mandatoryFeeCategoriesWithRules'));
    }

    /**
     * Afficher le formulaire de modification d'une inscription.
     */
    public function edit(ESBTPInscription $inscription)
    {
        // Vérifier si l'inscription peut être modifiée
        if ($inscription->status === 'terminée') {
            return redirect()
                ->route('esbtp.inscriptions.show', $inscription->id)
                ->with('error', 'Les inscriptions terminées ne peuvent pas être modifiées.');
        }

        // Charger les relations nécessaires
        $inscription->load(['etudiant', 'filiere', 'niveau', 'classe', 'anneeUniversitaire']);

        // Récupérer les données pour les selects
        $filieres = ESBTPFiliere::where('is_active', true)->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->get();
        $classes = ESBTPClasse::where('is_active', true)
            ->where('filiere_id', $inscription->filiere_id)
            ->where('niveau_etude_id', $inscription->niveau_id)
            ->where('annee_universitaire_id', $inscription->annee_universitaire_id)
            ->get();

        $annees = ESBTPAnneeUniversitaire::orderBy('start_date', 'desc')->get();

        return view('esbtp.inscriptions.edit', compact(
            'inscription',
            'filieres',
            'niveaux',
            'classes',
            'annees'
        ));
    }

    /**
     * Mettre à jour une inscription.
     */
    public function update(Request $request, ESBTPInscription $inscription)
    {
        // Vérifier si l'inscription peut être modifiée
        if ($inscription->status === 'terminée') {
            return redirect()
                ->route('esbtp.inscriptions.show', $inscription->id)
                ->with('error', 'Les inscriptions terminées ne peuvent pas être modifiées.');
        }

        // Validation des données
        $validator = Validator::make($request->all(), [
            'filiere_id' => 'required|exists:esbtp_filieres,id',
            'niveau_id' => 'required|exists:esbtp_niveau_etudes,id',
            'classe_id' => 'nullable|exists:esbtp_classes,id',
            'date_inscription' => 'required|date',
            'type_inscription' => 'required|in:première_inscription,réinscription,transfert',
            'montant_scolarite' => 'required|numeric|min:0',
            'frais_inscription' => 'required|numeric|min:0',
            'observations' => 'nullable|string',
            'status' => 'required|in:en_attente,active,annulée,terminée',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $data = $request->all();
            if ($inscription->status !== 'en_attente') {
                // Empêcher la modification de la classe
                unset($data['classe_id']);
                \Log::warning('Tentative de modification de la classe après validation', [
                    'inscription_id' => $inscription->id,
                    'user_id' => Auth::id()
                ]);
            }

            // Mettre à jour l'inscription
            $inscription->filiere_id = $data['filiere_id'];
            $inscription->niveau_id = $data['niveau_id'];
            $inscription->classe_id = $data['classe_id'];
            $inscription->date_inscription = $data['date_inscription'];
            $inscription->type_inscription = $data['type_inscription'];
            $inscription->montant_scolarite = $data['montant_scolarite'];
            $inscription->frais_inscription = $data['frais_inscription'];
            $inscription->observations = $data['observations'];

            // Mettre à jour le statut et les champs associés
            $nouveauStatut = $data['status'];
            $ancienStatut = $inscription->status;

            if ($nouveauStatut !== $ancienStatut) {
                $inscription->status = $nouveauStatut;

                if ($nouveauStatut === 'active' && $ancienStatut !== 'active') {
                    $inscription->date_validation = now();
                    $inscription->validated_by = Auth::id();
                }

                // Si l'inscription devient inactive ou annulée, mettre à jour l'étudiant si nécessaire
                if (in_array($nouveauStatut, ['annulée', 'terminée'])) {
                    $etudiant = $inscription->etudiant;
                    $autresInscriptionsActives = $etudiant->inscriptions()
            ->where('id', '!=', $inscription->id)
                        ->whereIn('status', ['active', 'en_attente'])
                        ->exists();

                    if (!$autresInscriptionsActives && $etudiant->statut === 'actif') {
                        if ($nouveauStatut === 'terminée') {
                            $etudiant->statut = 'diplômé';
                        } else {
                            $etudiant->statut = 'inactif';
                        }
                        $etudiant->save();
                    }
                }
            }

            $inscription->updated_by = Auth::id();
            $inscription->save();

            DB::commit();

            return redirect()
                ->route('esbtp.inscriptions.show', $inscription->id)
                ->with('success', 'Inscription mise à jour avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la mise à jour: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Valider une inscription.
     */
    public function valider(Request $request, ESBTPInscription $inscription)
    {
        try {
            $result = $this->inscriptionService->validerInscription($inscription->id, Auth::id());

            if ($result['success']) {
                // Récupérer l'étudiant et son compte utilisateur
                $etudiant = $inscription->etudiant;
                if ($etudiant && $etudiant->user_id) {
                    $user = User::find($etudiant->user_id);
                    if ($user) {
                        // Stocker les informations du compte dans la session
                        session()->flash('account_info', [
                            'username' => $user->username,
                            'password' => session('generated_password'),
                            'role' => 'Étudiant'
                        ]);
                    }
                }

                // Vérifier si c'est une redirection depuis une autre page (étudiants ou autre)
                $referer = $request->headers->get('referer');
                if ($referer) {
                    // Extraire l'URL de base et le chemin
                    $url = parse_url($referer);
                    $path = $url['path'] ?? '';

                    // Si la redirection vient d'une page d'étudiant, retourner à cette page
                    if (str_contains($path, '/esbtp/etudiants')) {
                        return redirect($referer)
                            ->with('success', 'Inscription validée avec succès.');
                    }
                }

                // Par défaut, rediriger vers la page de détails de l'inscription
                return redirect()
                    ->route('esbtp.inscriptions.show', $inscription->id)
                    ->with('success', 'Inscription validée avec succès.');
            } else {
                throw new \Exception($result['message']);
            }

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la validation: ' . $e->getMessage());
        }
    }

    /**
     * Annuler une inscription.
     */
    public function annuler(Request $request, ESBTPInscription $inscription)
    {
        $validator = Validator::make($request->all(), [
            'motif' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $motif = $request->input('motif');
            $result = $this->inscriptionService->annulerInscription($inscription->id, $motif, Auth::id());

            if ($result['success']) {
                return redirect()
                    ->route('esbtp.inscriptions.show', $inscription->id)
                    ->with('success', 'Inscription annulée avec succès.');
            } else {
                throw new \Exception($result['message']);
            }

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de l\'annulation: ' . $e->getMessage());
        }
    }

    /**
     * Générer un reçu d'inscription.
     */
    public function recu(ESBTPInscription $inscription)
    {
        // Charger les relations nécessaires
        $inscription->load([
            'etudiant.user',
            'filiere',
            'niveau',
            'classe',
            'anneeUniversitaire',
            'paiements' => function($q) {
                $q->where('status', 'validé')
                  ->orderBy('date_paiement', 'desc');
            },
            'createdBy'
        ]);

        $pdf = PDF::loadView('esbtp.inscriptions.recu', compact('inscription'));
        return $pdf->stream('recu_inscription_' . $inscription->numero_recu . '.pdf');
    }

    /**
     * Obtenir les classes disponibles pour une filière, un niveau et une année donnés.
     */
    public function getClasses(Request $request)
    {
        $filiereId = $request->input('filiere_id');
        $niveauId = $request->input('niveau_id') ?? $request->input('niveau_etude_id');
        $anneeId = $request->input('annee_id') ?? $request->input('annee_universitaire_id');
        $formationId = $request->input('formation_id');

        // Ajouter des logs pour debug
        \Illuminate\Support\Facades\Log::info('Récupération des classes (Inscription)', [
            'filiere_id' => $filiereId,
            'niveau_id' => $niveauId,
            'annee_id' => $anneeId,
            'formation_id' => $formationId,
            'request' => $request->all()
        ]);

        $query = ESBTPClasse::select(
                'esbtp_classes.*',
                'f.name as filiere_name',
                'n.name as niveau_name',
                'a.name as annee_name'
            )
            ->leftJoin('esbtp_filieres as f', 'esbtp_classes.filiere_id', '=', 'f.id')
            ->leftJoin('esbtp_niveau_etudes as n', 'esbtp_classes.niveau_etude_id', '=', 'n.id')
            ->leftJoin('esbtp_annee_universitaires as a', 'esbtp_classes.annee_universitaire_id', '=', 'a.id')
            ->where('esbtp_classes.is_active', true);

        // Appliquer les filtres seulement s'ils sont fournis
        if ($filiereId) {
            $query->where('esbtp_classes.filiere_id', $filiereId);
        }

        if ($niveauId) {
            $query->where('esbtp_classes.niveau_etude_id', $niveauId);
        }

        if ($anneeId) {
            $query->where('esbtp_classes.annee_universitaire_id', $anneeId);
        }

        if ($formationId) {
            $query->where('esbtp_classes.formation_id', $formationId);
        }

        // Log pour vérifier la requête SQL générée
        \Illuminate\Support\Facades\Log::info('Requête SQL pour les classes (Inscription)', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings()
        ]);

        $classes = $query->get();

        // Log pour vérifier les résultats
        \Illuminate\Support\Facades\Log::info('Classes trouvées (Inscription)', [
            'count' => $classes->count(),
            'first_few' => $classes->take(3)
        ]);

        return response()->json($classes);
    }

    /**
     * Supprimer une inscription.
     */
    public function destroy(ESBTPInscription $inscription)
    {
        try {
            $inscription->delete();

            return redirect()
                ->route('esbtp.inscriptions.index')
                ->with('success', 'Inscription supprimée avec succès.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    /**
     * Gère l'upload de la photo de l'étudiant.
     *
     * @param \Illuminate\Http\UploadedFile $photo
     * @return string
     */
    private function handlePhotoUpload($photo)
    {
        $filename = time() . '_' . Str::random(10) . '.' . $photo->getClientOriginalExtension();
        $photo->storeAs('public/photos/etudiants', $filename);
        return $filename;
    }
}
