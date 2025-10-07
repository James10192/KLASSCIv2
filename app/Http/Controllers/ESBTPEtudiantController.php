<?php

namespace App\Http\Controllers;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\User;
use App\Services\ESBTPInscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Arr;
use Spatie\Permission\Models\Role;
use App\Models\ESBTPParent;
use App\Models\ESBTPInscription;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\InscriptionWorkflowService;
use App\Services\ClasseManagementService;

class ESBTPEtudiantController extends Controller
{
    protected $inscriptionService;
    protected $inscriptionWorkflowService;
    protected $classeManagementService;

    /**
     * Constructeur avec injection du service d'inscription
     */
    public function __construct(
        ESBTPInscriptionService $inscriptionService,
        InscriptionWorkflowService $inscriptionWorkflowService,
        ClasseManagementService $classeManagementService
    ) {
        $this->inscriptionService = $inscriptionService;
        $this->inscriptionWorkflowService = $inscriptionWorkflowService;
        $this->classeManagementService = $classeManagementService;
        $this->middleware('auth');
        $this->middleware('permission:view_students', ['only' => ['index', 'show']]);
        $this->middleware('permission:create_students', ['only' => ['create', 'store']]);
        $this->middleware('permission:edit_students', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete_students', ['only' => ['destroy']]);
    }

    /**
     * Afficher la liste des étudiants.
     */
    public function index(Request $request)
    {
        // Récupérer les filtres de recherche
        $search = $request->input('search');
        $filiere = $request->input('filiere');
        $niveau = $request->input('niveau');
        $annee = $request->input('annee');
        $status = $request->input('status');

        // Construire la requête avec les filtres
        $query = ESBTPEtudiant::query()
            ->with(['user', 'inscriptions' => function($q) {
                $q->with(['filiere', 'niveau', 'classe', 'anneeUniversitaire']);
            }]);

        // Appliquer les filtres
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('matricule', 'like', "%{$search}%")
                  ->orWhere('nom', 'like', "%{$search}%")
                  ->orWhere('prenoms', 'like', "%{$search}%")
                  ->orWhere('telephone', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('statut', $status);
        }

        if ($filiere || $niveau || $annee) {
            $query->whereHas('inscriptions', function($q) use ($filiere, $niveau, $annee) {
                if ($filiere) {
                    $q->where('filiere_id', $filiere);
                }
                if ($niveau) {
                    $q->where('niveau_id', $niveau);
                }
                if ($annee) {
                    $q->where('annee_universitaire_id', $annee);
                }
            });
        }

        // Récupérer les étudiants paginés
        $etudiants = $query->latest()->paginate(15);

        // Récupérer les listes pour les filtres
        $filieres = ESBTPFiliere::where('is_active', true)->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->get();
        $annees = ESBTPAnneeUniversitaire::orderBy('start_date', 'desc')->get();

        return view('esbtp.etudiants.index', compact(
            'etudiants',
            'filieres',
            'niveaux',
            'annees',
            'search',
            'filiere',
            'niveau',
            'annee',
            'status'
        ));
    }

    /**
     * Afficher le formulaire de création d'étudiant.
     */
    public function create()
    {
        // Rediriger vers la page d'inscription qui est plus complète
        return redirect()->route('esbtp.inscriptions.create')
            ->with('info', 'Veuillez utiliser le formulaire d\'inscription pour ajouter un nouvel étudiant.');

        // Code commenté - anciennes données pour le formulaire
        /*
        $filieres = ESBTPFiliere::where('is_active', true)->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->get();
        $annees = ESBTPAnneeUniversitaire::orderBy('start_date', 'desc')->get();
        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        return view('esbtp.etudiants.create', compact(
            'filieres',
            'niveaux',
            'annees',
            'anneeEnCours'
        ));
        */
    }

    /**
     * Enregistrer un nouvel étudiant.
     */
    public function store(Request $request)
    {
        // Ajout de logs pour déboguer
        \Illuminate\Support\Facades\Log::info('Tentative de création d\'un étudiant', ['request' => $request->all()]);

        // Validation des données de base de l'étudiant
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenoms' => 'required|string|max:255',
            'genre' => 'required|in:M,F',
            'date_naissance' => 'nullable|date',
            'telephone' => 'required|string|max:20',
            'email_personnel' => 'required|email|max:255',
            'ville' => 'nullable|string|max:255',
            'commune' => 'nullable|string|max:255',
            'photo' => 'nullable|image|max:2048',

            // Données pour l'inscription
            'filiere_id' => 'required|exists:esbtp_filieres,id',
            'niveau_etude_id' => 'required|exists:esbtp_niveau_etudes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'classe_id' => 'nullable|exists:esbtp_classes,id',
            'date_admission' => 'required|date',
            'statut' => 'required|in:actif,inactif',

            // Validation conditionnelle des données des parents
            'parents.*.nom' => 'required_without:parents.*.parent_id|string|max:255|nullable',
            'parents.*.prenoms' => 'required_without:parents.*.parent_id|string|max:255|nullable',
            'parents.*.relation' => 'required_without:parents.*.parent_id|string|max:50|nullable',
            'parents.*.telephone' => 'required_without:parents.*.parent_id|string|max:20|nullable',
            'parents.*.parent_id' => 'nullable|exists:esbtp_parents,id',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Ajout de logs pour déboguer
            \Illuminate\Support\Facades\Log::info('Début de la transaction pour la création d\'un étudiant');

            // Préparer les données de l'étudiant
            $etudiantData = [
                'nom' => $request->nom,
                'prenoms' => $request->prenoms,
                'sexe' => $request->genre,
                'date_naissance' => $request->date_naissance,
                'telephone' => $request->telephone,
                'email_personnel' => $request->email_personnel,
                'adresse' => ($request->ville && $request->commune) ? $request->ville . ', ' . $request->commune : null,
                'statut' => $request->statut ?? 'actif',
                'creer_compte_utilisateur' => $request->create_account ? true : false,
            ];

            // Gérer la photo si présente
            if ($request->hasFile('photo')) {
                $etudiantData['photo_file'] = $request->file('photo');
            }

            // Préparer les données d'inscription
            $inscriptionData = [
                'filiere_id' => $request->filiere_id,
                'niveau_id' => $request->niveau_etude_id,
                'annee_universitaire_id' => $request->annee_universitaire_id,
                'classe_id' => $request->classe_id,
                'date_inscription' => $request->date_admission,
                'type_inscription' => 'première_inscription',
                'status' => 'en_attente',
                'montant_scolarite' => 0, // À définir plus tard
                'frais_inscription' => 0, // À définir plus tard
            ];

            // Préparer les données des parents
            $parentsData = [];
            if ($request->has('parents')) {
                foreach ($request->parents as $index => $parentData) {
                    // Vérifier si c'est un parent existant
                    if (isset($parentData['parent_id']) && !empty($parentData['parent_id'])) {
                        $parentsData[] = [
                            'id' => $parentData['parent_id'],
                            'relation' => $parentData['relation'] ?? 'tuteur',
                            'is_tuteur' => $index === 0 ? true : false,
                        ];
                        continue;
                    }

                    // Vérifier si c'est un nouveau parent valide
                    if (!empty($parentData['nom']) && !empty($parentData['prenoms']) && !empty($parentData['telephone'])) {
                        $parentsData[] = [
                            'nom' => $parentData['nom'],
                            'prenoms' => $parentData['prenoms'],
                            'relation' => $parentData['relation'] ?? 'tuteur',
                            'is_tuteur' => $index === 0 ? true : false,
                            'telephone' => $parentData['telephone'],
                            'email' => $parentData['email'] ?? null,
                            'profession' => $parentData['profession'] ?? null,
                            'adresse' => $parentData['adresse'] ?? null,
                            'creer_compte_utilisateur' => false, // Ne pas créer de compte pour les parents par défaut
                        ];
                    }
                }
            }

            // Appeler le service pour créer l'inscription
            $parentData = !empty($parentsData) ? $parentsData[0] : null;

            // Ajout de logs pour déboguer
            \Illuminate\Support\Facades\Log::info('Appel au service d\'inscription', [
                'etudiantData' => $etudiantData,
                'inscriptionData' => $inscriptionData,
                'parentData' => $parentData
            ]);

            $result = $this->inscriptionService->createInscription(
                $etudiantData,
                $inscriptionData,
                $parentData,
                null, // Pas de paiement initial pour le moment
                Auth::id()
            );

            // Ajouter les parents supplémentaires si nécessaire
            if ($result['success'] && count($parentsData) > 1) {
                $etudiant = $result['etudiant'];

                for ($i = 1; $i < count($parentsData); $i++) {
                    $parentData = $parentsData[$i];

                    // Si c'est un parent existant
                    if (isset($parentData['id'])) {
                        $etudiant->parents()->attach($parentData['id'], [
                            'relation' => $parentData['relation'],
                            'is_tuteur' => $parentData['is_tuteur'],
                        ]);
                    }
                    // Si c'est un nouveau parent
                    else {
                        $parent = $this->inscriptionService->createOrUpdateParent($parentData, Auth::id());
                        $etudiant->parents()->attach($parent->id, [
                            'relation' => $parentData['relation'],
                            'is_tuteur' => $parentData['is_tuteur'],
                        ]);
                    }
                }
            }

            DB::commit();

            // Ajout de logs pour déboguer
            \Illuminate\Support\Facades\Log::info('Transaction commitée avec succès', ['result' => $result]);

            if ($result['success']) {
                $etudiant = $result['etudiant'];

                // Préparer l'affichage des informations du compte généré
                $accountInfo = null;
                if (isset($etudiant->user_id) && $etudiant->user_id) {
                    $user = User::find($etudiant->user_id);
                    $accountInfo = [
                        'username' => $user->username,
                        'password' => $etudiantData['password_generated'] ?? null,
                    ];
                }

                return redirect()
                    ->route('esbtp.etudiants.show', $etudiant->id)
                    ->with('success', 'Étudiant inscrit avec succès!')
                    ->with('account_info', $accountInfo);
            } else {
                throw new \Exception($result['message']);
            }

        } catch (\Exception $e) {
            DB::rollBack();

            // Ajout de logs pour déboguer
            \Illuminate\Support\Facades\Log::error('Erreur lors de la création d\'un étudiant', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Erreur lors de l\'inscription: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Afficher les détails d'un étudiant.
     */
    public function show(ESBTPEtudiant $etudiant)
    {
        // Charger les relations nécessaires avec optimisation
        $etudiant->load([
            'user',
            'parents' => function($q) {
                $q->with('etudiants'); // Pour afficher les autres étudiants du même parent
            },
            'inscriptions' => function($q) {
                $q->with(['filiere', 'niveauEtude', 'classe', 'anneeUniversitaire'])
                  ->orderBy('created_at', 'desc');
            },
            'paiements' => function($q) {
                $q->with(['inscription', 'fraisCategory', 'categorie', 'validatedBy'])
                  ->orderBy('date_paiement', 'desc');
            }
        ]);

        // Récupérer les reliquats de l'étudiant
        $inscriptionIds = $etudiant->inscriptions->pluck('id');

        // Reliquats entrants (provenant d'inscriptions précédentes)
        $reliquatsEntrants = \App\Models\ESBTPReliquatDetail::whereIn('inscription_destination_id', $inscriptionIds)
            ->with(['inscriptionSource.anneeUniversitaire', 'fraisSubscription.fraisCategory', 'fraisSubscription.selectedOption'])
            ->actifs()
            ->get();

        // Reliquats sortants (transférés vers des inscriptions futures)
        $reliquatsSortants = \App\Models\ESBTPReliquatDetail::whereIn('inscription_source_id', $inscriptionIds)
            ->with(['inscriptionDestination.anneeUniversitaire', 'fraisSubscription.fraisCategory', 'fraisSubscription.selectedOption'])
            ->get();

        // Calculer quelques statistiques utiles
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

        return view('esbtp.etudiants.show', compact('etudiant', 'statistiques', 'reliquatsEntrants', 'reliquatsSortants'));
    }

    /**
     * Afficher le formulaire de modification d'un étudiant.
     */
    public function edit(ESBTPEtudiant $etudiant)
    {
        // Charger les relations nécessaires
        $etudiant->load(['user', 'parents', 'inscriptions.filiere', 'inscriptions.niveau', 'inscriptions.classe']);

        // Récupérer les données pour les selects
        $filieres = ESBTPFiliere::where('is_active', true)->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->get();
        $classes = ESBTPClasse::where('is_active', true)->get();
        $annees = ESBTPAnneeUniversitaire::orderBy('start_date', 'desc')->get();

        // Logging pour debug
        \Log::info('Étudiant chargé pour édition', [
            'id' => $etudiant->id,
            'email_personnel' => $etudiant->email_personnel,
            'email_personnelArray' => $etudiant['email_personnel'] ?? null,
            'sexe' => $etudiant->sexe,
            'sexeArray' => $etudiant['sexe'] ?? null,
            'genre' => $etudiant->genre,
            'all_attributes' => $etudiant->getAttributes(),
        ]);

        return view('esbtp.etudiants.edit', compact(
            'etudiant',
            'filieres',
            'niveaux',
            'classes',
            'annees'
        ));
    }

    /**
     * Mettre à jour un étudiant.
     */
    public function update(Request $request, ESBTPEtudiant $etudiant)
    {
        // Vérifier le token de prévention de double soumission
        $submittedToken = $request->input('form_submit_token');
        $sessionToken = session('form_submit_token');

        if ($sessionToken && $submittedToken === $sessionToken) {
            \Log::warning('Tentative de double soumission détectée', [
                'session_id' => session()->getId(),
                'etudiant_id' => $etudiant->id
            ]);
            return redirect()
                ->back()
                ->with('warning', 'Cette mise à jour a déjà été soumise. Veuillez rafraîchir la page pour effectuer une nouvelle modification.');
        }

        // Stocker le token en session
        session(['form_submit_token' => $submittedToken]);

        // Logging des données reçues pour debug
        \Log::info('Début de la requête de mise à jour', [
            'request_method' => $request->method(),
            'request_url' => $request->url(),
            'request_headers' => $request->headers->all(),
            'session_id' => session()->getId(),
            'input_size' => strlen(json_encode($request->all())),
            'has_file' => $request->hasFile('photo'),
            'file_size' => $request->hasFile('photo') ? $request->file('photo')->getSize() : 0
        ]);

        \Log::info('Données reçues pour mise à jour étudiant', [
            'id' => $etudiant->id,
            'requestData' => $request->all(),
            'currentEmail' => $etudiant->email_personnel,
            'currentSexe' => $etudiant->sexe,
            'currentGenre' => $etudiant->genre,
            'ville' => $request->input('ville'),
            'commune' => $request->input('commune'),
            'etudiantVille' => $etudiant->ville,
            'etudiantCommune' => $etudiant->commune,
        ]);

        \Log::info('Payload update etudiant', [
            'etudiant_id' => $etudiant->id,
            'input' => $request->all()
        ]);

        // Validation des données - Exclus les champs non modifiables
        $validator = Validator::make($request->all(), [
            'telephone' => 'nullable|string|max:20',
            'email_personnel' => 'nullable|email|max:255',
            'photo' => 'nullable|image|max:2048',
            'statut' => 'required|in:actif,inactif,diplômé,abandon,exclu',
            'ville' => 'nullable|string|max:255',
            'commune' => 'nullable|string|max:255',
            'adresse' => 'nullable|string',
            // Les champs suivants sont en readonly et ne doivent pas être validés comme requis
            'matricule' => 'nullable|string|max:255',
            'nom' => 'nullable|string|max:255',
            'prenoms' => 'nullable|string|max:255',
            'sexe' => 'nullable|in:M,F',
            'date_naissance' => 'nullable|date',
            'lieu_naissance' => 'nullable|string|max:255',
            'ville_naissance' => 'nullable|string|max:255',
            'commune_naissance' => 'nullable|string|max:255',
            'nationalite' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            \Log::error('Validation échouée lors de la mise à jour de l\'étudiant', [
                'errors' => $validator->errors()->toArray(),
                'session_id' => session()->getId()
            ]);

            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();
            \Log::info('Début de la transaction de mise à jour pour l\'étudiant ' . $etudiant->id);

            // Extraire les données ville et commune spécifiquement pour s'assurer qu'elles sont correctement définies
            $ville = $request->input('ville');
            $commune = $request->input('commune');

            // Mettre à jour les champs ville et commune explicitement pour s'assurer qu'ils sont correctement enregistrés
            $etudiant->ville = !empty($ville) ? (string)$ville : null;
            $etudiant->commune = !empty($commune) ? (string)$commune : null;

            // Mettre à jour les autres données de l'étudiant
            $etudiantData = $request->only([
                'nom', 'prenoms', 'sexe', 'date_naissance', 'lieu_naissance',
                'ville_naissance', 'commune_naissance',
                'nationalite', 'adresse', 'telephone', 'email_personnel', 'statut'
            ]);

            \Log::info('Données à mettre à jour', [
                'etudiantData' => $etudiantData,
                'ville' => $etudiant->ville,
                'commune' => $etudiant->commune,
                'session_id' => session()->getId()
            ]);

            // Gérer la photo si présente
            if ($request->hasFile('photo')) {
                \Log::info('Traitement de la photo', [
                    'original_name' => $request->file('photo')->getClientOriginalName(),
                    'size' => $request->file('photo')->getSize(),
                    'mime_type' => $request->file('photo')->getMimeType()
                ]);

                // Supprimer l'ancienne photo si elle existe
                if ($etudiant->photo && Storage::exists(str_replace('/storage', 'public', $etudiant->photo))) {
                    Storage::delete(str_replace('/storage', 'public', $etudiant->photo));
                }

                $photoPath = $request->file('photo')->store('public/etudiants/photos');
                $etudiantData['photo'] = Storage::url($photoPath);
            }

            // Mettre à jour l'étudiant
            $etudiant->fill($etudiantData);
            $etudiant->updated_by = Auth::id();

            \Log::info('État de l\'étudiant avant sauvegarde', [
                'ville' => $etudiant->ville,
                'commune' => $etudiant->commune,
                'nom' => $etudiant->nom,
                'prenoms' => $etudiant->prenoms,
                'attributes' => $etudiant->getAttributes(),
                'session_id' => session()->getId()
            ]);

            // Forcer un update sans détection de modification
            $result = $etudiant->save(['timestamps' => true]);
            \Log::info('Résultat de la sauvegarde', [
                'success' => $result,
                'session_id' => session()->getId()
            ]);

            // Vérification post-sauvegarde
            $updatedEtudiant = ESBTPEtudiant::find($etudiant->id);
            \Log::info('État de l\'étudiant après sauvegarde', [
                'ville' => $updatedEtudiant->ville,
                'commune' => $updatedEtudiant->commune,
                'attributes' => $updatedEtudiant->getAttributes(),
                'session_id' => session()->getId()
            ]);

            // Mettre à jour l'utilisateur associé
            if ($etudiant->user_id) {
                $user = User::find($etudiant->user_id);
                if ($user) {
                    $user->name = $etudiantData['prenoms'] . ' ' . $etudiantData['nom'];
                    $user->email = $etudiantData['email_personnel'] ?? $user->email;
                    $user->is_active = ($etudiantData['statut'] === 'actif');
                    $user->save();
                }
            }

            // Gestion des parents (maximum 2)
            $syncParents = [];
            $existingParentsInput = $request->input('parents', []);

            foreach ($existingParentsInput as $parentInput) {
                $parentId = (int) Arr::get($parentInput, 'id');
                if (!$parentId) {
                    continue;
                }

                $parent = ESBTPParent::find($parentId);
                if (!$parent) {
                    continue;
                }

                $parent->fill([
                    'nom' => Arr::get($parentInput, 'nom', $parent->nom),
                    'prenoms' => Arr::get($parentInput, 'prenoms', $parent->prenoms),
                    'telephone' => Arr::get($parentInput, 'telephone', $parent->telephone),
                    'email' => Arr::get($parentInput, 'email', $parent->email),
                    'profession' => Arr::get($parentInput, 'profession', $parent->profession),
                    'adresse' => Arr::get($parentInput, 'adresse', $parent->adresse),
                ]);
                $parent->updated_by = Auth::id();
                $parent->save();

                $syncParents[$parent->id] = [
                    'relation' => Arr::get($parentInput, 'relation', 'Autre'),
                    'is_tuteur' => Arr::has($parentInput, 'is_tuteur') ? 1 : 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $newParentInput = $request->input('new_parent', []);
            $newParentHasData = collect($newParentInput)->only([
                'nom', 'prenoms', 'telephone', 'email', 'profession', 'adresse', 'relation'
            ])->filter(fn($value) => filled($value))->isNotEmpty();

            if ($newParentHasData && filled(Arr::get($newParentInput, 'nom')) && filled(Arr::get($newParentInput, 'prenoms'))) {
                $existingParentId = (int) Arr::get($newParentInput, 'parent_id');

                if ($existingParentId) {
                    $parent = ESBTPParent::find($existingParentId);
                    if ($parent) {
                        $syncParents[$parent->id] = [
                            'relation' => Arr::get($newParentInput, 'relation', 'Autre'),
                            'is_tuteur' => Arr::has($newParentInput, 'is_tuteur') ? 1 : 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                } else {
                    $parent = ESBTPParent::create([
                        'nom' => Arr::get($newParentInput, 'nom'),
                        'prenoms' => Arr::get($newParentInput, 'prenoms'),
                        'telephone' => Arr::get($newParentInput, 'telephone'),
                        'email' => Arr::get($newParentInput, 'email'),
                        'profession' => Arr::get($newParentInput, 'profession'),
                        'adresse' => Arr::get($newParentInput, 'adresse'),
                        'created_by' => Auth::id(),
                    ]);

                    $syncParents[$parent->id] = [
                        'relation' => Arr::get($newParentInput, 'relation', 'Autre'),
                        'is_tuteur' => Arr::has($newParentInput, 'is_tuteur') ? 1 : 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (count($syncParents) > 2) {
                $syncParents = array_slice($syncParents, 0, 2, true);
            }

            $etudiant->parents()->sync($syncParents);
            \Log::info('Parents synchronisés pour etudiant', [
                'etudiant_id' => $etudiant->id,
                'sync_payload' => $syncParents,
                'total_parents' => $etudiant->parents()->count()
            ]);

            \Log::info('Synchronisation parents', [
                'sync_payload' => $syncParents
            ]);

            DB::commit();
            \Log::info('Transaction de mise à jour terminée avec succès', [
                'etudiant_id' => $etudiant->id,
                'session_id' => session()->getId(),
                'redirect_to' => route('esbtp.etudiants.show', $etudiant->id)
            ]);

            return redirect()
                ->route('esbtp.etudiants.show', $etudiant->id)
                ->with('success', 'Informations de l\'étudiant mises à jour avec succès!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur lors de la mise à jour de l\'étudiant', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'session_id' => session()->getId()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la mise à jour: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Supprimer un étudiant.
     */
    public function destroy(ESBTPEtudiant $etudiant)
    {
        try {
            // Vérifier si l'étudiant peut être supprimé
            $hasInscriptions = $etudiant->inscriptions()->exists();

            if ($hasInscriptions) {
                return redirect()
                    ->back()
                    ->with('error', 'Impossible de supprimer cet étudiant car il a des inscriptions. Vous pouvez le désactiver à la place.');
            }

            DB::beginTransaction();

            // Supprimer l'utilisateur associé
            if ($etudiant->user_id) {
                User::destroy($etudiant->user_id);
            }

            // Supprimer la photo
            if ($etudiant->photo && Storage::exists(str_replace('/storage', 'public', $etudiant->photo))) {
                Storage::delete(str_replace('/storage', 'public', $etudiant->photo));
            }

            // Supprimer l'étudiant
            $etudiant->delete();

            DB::commit();

            return redirect()
                ->route('esbtp.etudiants.index')
                ->with('success', 'Étudiant supprimé avec succès!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    /**
     * Générer un nouveau mot de passe pour l'étudiant.
     */
    public function resetPassword(ESBTPEtudiant $etudiant)
    {
        try {
            if (!$etudiant->user_id) {
                return redirect()
                    ->back()
                    ->with('error', 'Cet étudiant n\'a pas de compte utilisateur.');
            }

            $user = User::find($etudiant->user_id);
            if (!$user) {
                return redirect()
                    ->back()
                    ->with('error', 'Compte utilisateur introuvable.');
            }

            // Générer un nouveau mot de passe simple
            // 6 caractères: 4 lettres majuscules + 2 chiffres
            $lettres = 'ABCDEFGHJKLMNPQRSTUVWXYZ'; // Lettres sans I et O pour éviter la confusion
            $chiffres = '23456789'; // Chiffres sans 0 et 1 pour éviter la confusion

            $newPassword = '';
            // Ajouter 4 lettres aléatoires
            for ($i = 0; $i < 4; $i++) {
                $newPassword .= $lettres[rand(0, strlen($lettres) - 1)];
            }
            // Ajouter 2 chiffres aléatoires
            for ($i = 0; $i < 2; $i++) {
                $newPassword .= $chiffres[rand(0, strlen($chiffres) - 1)];
            }

            // Mettre à jour le mot de passe
            $user->password = Hash::make($newPassword);
            $user->save();

            return redirect()
                ->back()
                ->with('success', 'Mot de passe réinitialisé avec succès!')
                ->with('new_password', $newPassword);

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la réinitialisation du mot de passe: ' . $e->getMessage());
        }
    }

    /**
     * Obtenir les classes disponibles pour une filière, un niveau et une année donnés.
     */
    public function getClasses(Request $request)
    {
        $filiereId = $request->input('filiere_id');
        $niveauId = $request->input('niveau_id') ?? $request->input('niveau_etude_id');
        $anneeId = $request->input('annee_id') ?? $request->input('annee_universitaire_id');

        // Ajouter des logs pour debug
        \Illuminate\Support\Facades\Log::info('Récupération des classes (Etudiant)', [
            'filiere_id' => $filiereId,
            'niveau_id' => $niveauId,
            'annee_id' => $anneeId,
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

        // Log pour vérifier la requête SQL générée
        \Illuminate\Support\Facades\Log::info('Requête SQL pour les classes (Etudiant)', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings()
        ]);

        $classes = $query->get();

        // Log pour vérifier les résultats
        \Illuminate\Support\Facades\Log::info('Classes trouvées (Etudiant)', [
            'count' => $classes->count(),
            'first_few' => $classes->take(3)
        ]);

        return response()->json($classes);
    }

    /**
     * Affiche la liste des étudiants sous forme de données JSON (pour les selects dynamiques).
     */
    public function getEtudiants(Request $request)
    {
        $search = $request->input('q');
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);

        $query = ESBTPEtudiant::query()
            ->select('id', 'matricule', 'nom', 'prenoms')
            ->where('statut', 'actif');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('matricule', 'like', "%{$search}%")
                  ->orWhere('nom', 'like', "%{$search}%")
                  ->orWhere('prenoms', 'like', "%{$search}%");
            });
        }

        $total = $query->count();
        $etudiants = $query->skip(($page - 1) * $limit)
            ->take($limit)
            ->get()
            ->map(function($etudiant) {
                return [
                    'id' => $etudiant->id,
                    'text' => "{$etudiant->matricule} - {$etudiant->prenoms} {$etudiant->nom}"
                ];
            });

        return response()->json([
            'results' => $etudiants,
            'pagination' => [
                'more' => ($page * $limit) < $total
            ]
        ]);
    }

    /**
     * Recherche d'étudiants pour le formulaire de paiement (API).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        $search = $request->input('q');
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);

        $query = ESBTPEtudiant::with('user')
            ->where('status', 'actif')
            ->whereHas('inscriptions', function($q) {
                $q->where('status', 'active');
            });

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('matricule', 'like', "%{$search}%")
                  ->orWhereHas('user', function($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $total = $query->count();
        $etudiants = $query->skip(($page - 1) * $limit)
            ->take($limit)
            ->get()
            ->map(function($etudiant) {
                return [
                    'id' => $etudiant->id,
                    'text' => "{$etudiant->matricule} - {$etudiant->user->name}"
                ];
            });

        return response()->json([
            'results' => $etudiants,
            'pagination' => [
                'more' => ($page * $limit) < $total
            ]
        ]);
    }

    /**
     * Récupérer les inscriptions actives d'un étudiant (API).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getInscriptions(Request $request)
    {
        $etudiantId = $request->input('etudiant_id');

        if (!$etudiantId) {
            return response()->json([]);
        }

        $etudiant = ESBTPEtudiant::findOrFail($etudiantId);

        $inscriptions = $etudiant->inscriptions()
            ->with(['filiere', 'niveauEtude', 'anneeUniversitaire'])
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($inscription) {
                return [
                    'id' => $inscription->id,
                    'filiere' => $inscription->filiere->name,
                    'niveau' => $inscription->niveauEtude->name,
                    'annee' => $inscription->anneeUniversitaire->libelle,
                    'montant_scolarite' => $inscription->montant_scolarite,
                    'frais_inscription' => $inscription->frais_inscription,
                ];
            });

        return response()->json($inscriptions);
    }

    /**
     * Affiche le profil de l'étudiant connecté.
     *
     * @return \Illuminate\Http\Response
     */
    public function profile()
    {
        // Vérifier si l'utilisateur est un étudiant
        $user = auth()->user();

        // Rediriger les non-étudiants vers leur profil approprié
        if (!$user->hasRole('etudiant')) {
            // Pour les administrateurs et secrétaires
            return redirect()->route('dashboard')->with('warning', 'Vous n\'avez pas accès au profil étudiant.');
        }

        $etudiant = $user->etudiant;

        // Si l'étudiant n'existe pas, afficher une erreur
        if (!$etudiant) {
            return redirect()->route('dashboard')->with('error', 'Profil étudiant non trouvé.');
        }

        // Charger l'inscription active en premier
        $inscription = ESBTPInscription::with(['filiere', 'niveau', 'classe', 'anneeUniversitaire', 'paiements'])
            ->where('etudiant_id', $etudiant->id)
            ->where('status', 'active')
            ->whereHas('anneeUniversitaire', function($query) {
                $query->where('is_active', true);
            })
            ->latest('date_inscription')
            ->first();

        // Charger l'historique des inscriptions
        $etudiant->load(['inscriptions' => function($query) {
            $query->with(['filiere', 'niveau', 'classe', 'anneeUniversitaire'])
                  ->orderBy('date_inscription', 'desc');
        }]);

        return view('esbtp.etudiants.profile', compact('etudiant', 'inscription'));
    }

    /**
     * Recherche des parents pour le formulaire d'ajout d'étudiant (API pour Select2)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchParents(Request $request)
    {
        \Log::info('Recherche de parents - Début', [
            'request' => $request->all(),
            'headers' => $request->headers->all()
        ]);

        $search = $request->input('q', '');
        $page = $request->input('page', 1);
        $perPage = 10;

        \Log::info('Paramètres de recherche', [
            'search' => $search,
            'page' => $page,
            'perPage' => $perPage
        ]);

        // Si la recherche est trop courte, renvoyer un résultat vide
        if (strlen($search) < 2) {
            \Log::info('Recherche trop courte');
            return response()->json([
                'items' => [],
                'pagination' => ['more' => false]
            ]);
        }

        try {
            // Rechercher les parents correspondant à la requête
            $query = ESBTPParent::where(function($query) use ($search) {
                $query->where('nom', 'like', "%{$search}%")
                      ->orWhere('prenoms', 'like', "%{$search}%")
                      ->orWhere('telephone', 'like', "%{$search}%");
            })
            ->select('id', 'nom', 'prenoms', 'telephone');

            \Log::info('Requête SQL', [
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);

            $parents = $query->skip(($page - 1) * $perPage)
                            ->take($perPage + 1)
                            ->get();

            \Log::info('Résultats de la recherche', [
                'count' => $parents->count(),
                'parents' => $parents->toArray()
            ]);

            $hasMorePages = $parents->count() > $perPage;

            if ($hasMorePages) {
                $parents = $parents->take($perPage);
            }

            // Formater les résultats pour Select2
            $formattedParents = $parents->map(function($parent) {
                return [
                    'id' => $parent->id,
                    'nom' => $parent->nom,
                    'prenoms' => $parent->prenoms,
                    'telephone' => $parent->telephone,
                    'text' => $parent->nom . ' ' . $parent->prenoms . ' (' . $parent->telephone . ')'
                ];
            });

            $response = [
                'items' => $formattedParents,
                'pagination' => ['more' => $hasMorePages]
            ];

            \Log::info('Réponse finale', $response);

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la recherche des parents', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Une erreur est survenue lors de la recherche des parents',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Prévisualiser un certificat de scolarité pour un étudiant.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function previewCertificat($id)
    {
        // Récupérer l'étudiant avec toutes ses inscriptions
        $etudiant = ESBTPEtudiant::with([
            'inscriptions.anneeUniversitaire',
            'inscriptions.classe',
            'inscriptions.filiere',
            'inscriptions.niveauEtude',
        ])->findOrFail($id);

        // Récupérer toutes les inscriptions ordonnées par année
        $inscriptions = $etudiant->inscriptions()
            ->with(['anneeUniversitaire', 'classe', 'filiere', 'niveauEtude'])
            ->orderBy('annee_universitaire_id', 'asc')
            ->get();

        if ($inscriptions->isEmpty()) {
            return back()->with('error', 'Aucune inscription trouvée pour cet étudiant.');
        }

        // Récupérer les paramètres de l'école
        $settings = \App\Helpers\SettingsHelper::getSchoolInfo();

        return view('esbtp.etudiants.certificat-preview', [
            'etudiant' => $etudiant,
            'inscriptions' => $inscriptions,
            'settings' => $settings
        ]);
    }

    /**
     * Générer un certificat de scolarité pour un étudiant.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function genererCertificat($id)
    {
        try {
            // Récupérer l'étudiant avec toutes ses inscriptions
            $etudiant = ESBTPEtudiant::with([
                'inscriptions.anneeUniversitaire',
                'inscriptions.classe',
                'inscriptions.filiere',
                'inscriptions.niveauEtude',
            ])->findOrFail($id);

            // Récupérer toutes les inscriptions ordonnées par année
            $inscriptions = $etudiant->inscriptions()
                ->with(['anneeUniversitaire', 'classe', 'filiere', 'niveauEtude'])
                ->orderBy('annee_universitaire_id', 'asc')
                ->get();

            if ($inscriptions->isEmpty()) {
                return back()->with('error', 'Aucune inscription trouvée pour cet étudiant.');
            }

            // Récupérer les paramètres de l'école
            $settings = \App\Helpers\SettingsHelper::getSchoolInfo();

            // Générer le PDF
            $pdf = Pdf::loadView('esbtp.etudiants.certificat', [
                'etudiant' => $etudiant,
                'inscriptions' => $inscriptions,
                'settings' => $settings
            ]);

            // Configuration du PDF
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
                'margin-top' => 10,
                'margin-right' => 10,
                'margin-bottom' => 15,
                'margin-left' => 10,
            ]);

            // Nom du fichier
            $filename = 'certificat_scolarite_' . strtolower($etudiant->nom) . '_' . strtolower($etudiant->prenom) . '_' . date('Y-m-d') . '.pdf';

            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            \Log::error('Erreur génération certificat scolarité: ' . $e->getMessage(), [
                'etudiant_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Erreur lors de la génération du certificat: ' . $e->getMessage());
        }
    }

    /**
     * Récupère les configurations pour les certificats de scolarité
     * 
     * @return array
     */
    public function getCertificatSettings()
    {
        $settings = [
            // Informations de l'établissement
            'school_name' => \App\Helpers\SettingsHelper::get('school_name', 'École Spéciale du Bâtiment et des Travaux Publics'),
            'school_address' => \App\Helpers\SettingsHelper::get('school_address', 'BP 2541 Yamoussoukro'),
            'school_phone' => \App\Helpers\SettingsHelper::get('school_phone', '30 64 39 93'),
            'school_email' => \App\Helpers\SettingsHelper::get('school_email', 'esbtp@aviso.ci'),
            'school_city' => \App\Helpers\SettingsHelper::get('school_city', 'Yamoussoukro'),
            'school_country' => \App\Helpers\SettingsHelper::get('school_country', 'Côte d\'Ivoire'),
            
            // Responsable
            'director_name' => \App\Helpers\SettingsHelper::get('director_name', ''),
            'director_title' => \App\Helpers\SettingsHelper::get('director_title', 'Le Directeur'),
            
            // Logo
            'show_logo' => \App\Helpers\SettingsHelper::get('certificat_show_logo', '1') === '1',
            'school_logo' => \App\Helpers\SettingsHelper::get('school_logo'),
        ];

        // Préparer le logo en base64 si activé
        if ($settings['show_logo'] && $settings['school_logo']) {
            $settings['logo_base64'] = $this->prepareLogoBase64($settings['school_logo']);
        }

        return $settings;
    }

    /**
     * Prépare le logo en base64 pour les PDFs
     * 
     * @param string $logoPath
     * @return string|null
     */
    private function prepareLogoBase64($logoPath)
    {
        if (!$logoPath) {
            return null;
        }

        $paths = [
            storage_path('app/public/' . $logoPath),
            public_path($logoPath),
            public_path('images/LOGO-KLASSCI-PNG.png'),
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                $imageData = file_get_contents($path);
                $extension = pathinfo($path, PATHINFO_EXTENSION);
                return 'data:image/' . $extension . ';base64,' . base64_encode($imageData);
            }
        }

        return null;
    }

    /**
     * Afficher la vue fusionnée des étudiants et inscriptions.
     */
    public function indexFusionne(Request $request)
    {
        // Récupérer les filtres de recherche pour les étudiants
        $searchEtudiants = $request->input('search_etudiants');
        $filiereEtudiants = $request->input('filiere_etudiants');
        $niveauEtudiants = $request->input('niveau_etudiants');
        $anneeEtudiants = $request->input('annee_etudiants');
        $statusEtudiants = $request->input('status_etudiants');

        // Construire la requête pour les étudiants
        $queryEtudiants = ESBTPEtudiant::query()
            ->with(['user', 'inscriptions' => function($q) {
                $q->with(['filiere', 'niveau', 'classe', 'anneeUniversitaire']);
            }]);

        // Appliquer les filtres pour les étudiants
        if ($searchEtudiants) {
            $queryEtudiants->where(function($q) use ($searchEtudiants) {
                $q->where('matricule', 'like', "%{$searchEtudiants}%")
                  ->orWhere('nom', 'like', "%{$searchEtudiants}%")
                  ->orWhere('prenoms', 'like', "%{$searchEtudiants}%")
                  ->orWhere('telephone', 'like', "%{$searchEtudiants}%");
            });
        }

        if ($statusEtudiants) {
            $queryEtudiants->where('statut', $statusEtudiants);
        }

        if ($filiereEtudiants || $niveauEtudiants || $anneeEtudiants) {
            $queryEtudiants->whereHas('inscriptions', function($q) use ($filiereEtudiants, $niveauEtudiants, $anneeEtudiants) {
                if ($filiereEtudiants) {
                    $q->where('filiere_id', $filiereEtudiants);
                }
                if ($niveauEtudiants) {
                    $q->where('niveau_id', $niveauEtudiants);
                }
                if ($anneeEtudiants) {
                    $q->where('annee_universitaire_id', $anneeEtudiants);
                }
            });
        }

        // Récupérer les étudiants paginés
        $etudiants = $queryEtudiants->latest()->paginate(15, ['*'], 'page_etudiants');

        // Récupérer les filtres de recherche pour les inscriptions
        $searchInscriptions = $request->input('search_inscriptions');
        $filiereInscriptions = $request->input('filiere_inscriptions');
        $niveauInscriptions = $request->input('niveau_inscriptions');
        $anneeInscriptions = $request->input('annee_inscriptions');
        $statusInscriptions = $request->input('status_inscriptions', 'active');

        // Construire la requête pour les inscriptions
        $queryInscriptions = ESBTPInscription::query()
            ->with(['etudiant', 'filiere', 'niveau', 'classe', 'anneeUniversitaire']);

        // Appliquer les filtres pour les inscriptions
        if ($searchInscriptions) {
            $queryInscriptions->whereHas('etudiant', function($q) use ($searchInscriptions) {
                $q->where('matricule', 'like', "%{$searchInscriptions}%")
                  ->orWhere('nom', 'like', "%{$searchInscriptions}%")
                  ->orWhere('prenoms', 'like', "%{$searchInscriptions}%");
            });
        }

        if ($filiereInscriptions) {
            $queryInscriptions->where('filiere_id', $filiereInscriptions);
        }

        if ($niveauInscriptions) {
            $queryInscriptions->where('niveau_id', $niveauInscriptions);
        }

        if ($anneeInscriptions) {
            $queryInscriptions->where('annee_universitaire_id', $anneeInscriptions);
        } else {
            // Par défaut, filtrer par année en cours
            $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
            if ($anneeEnCours) {
                $queryInscriptions->where('annee_universitaire_id', $anneeEnCours->id);
            }
        }

        if ($statusInscriptions) {
            $queryInscriptions->where('status', $statusInscriptions);
        }

        // Récupérer les inscriptions paginées
        $inscriptions = $queryInscriptions->latest()->paginate(15, ['*'], 'page_inscriptions');

        // Récupérer les listes pour les filtres
        $filieres = ESBTPFiliere::where('is_active', true)->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->get();
        $annees = ESBTPAnneeUniversitaire::orderBy('start_date', 'desc')->get();
        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        return view('esbtp.etudiants.index_fusionne', compact(
            'etudiants',
            'inscriptions',
            'filieres',
            'niveaux',
            'annees',
            'anneeEnCours',
            'searchEtudiants',
            'filiereEtudiants',
            'niveauEtudiants',
            'anneeEtudiants',
            'statusEtudiants',
            'searchInscriptions',
            'filiereInscriptions',
            'niveauInscriptions',
            'anneeInscriptions',
            'statusInscriptions'
        ));
    }

    /**
     * Create a new student from a validated inscription.
     * This method is intended to be called by the InscriptionWorkflowService.
     *
     * @param ESBTPInscription $inscription
     * @return ESBTPEtudiant
     */
    public function createFromInscription(ESBTPInscription $inscription)
    {
        // TODO: Implement the logic to create a student from an inscription.
        // This will involve mapping fields from the inscription to the student,
        // creating a user account if necessary, and saving the new student.

        $etudiant = new ESBTPEtudiant();
        // Map fields from $inscription to $etudiant
        // ...

        $etudiant->save();

        return $etudiant;
    }

    /**
     * Assign a student to a class.
     * This method is intended to be called by the ClasseManagementService.
     *
     * @param ESBTPEtudiant $etudiant
     * @param ESBTPClasse $classe
     * @return bool
     */
    public function assignClasse(ESBTPEtudiant $etudiant, ESBTPClasse $classe)
    {
        // Logique pour assigner un étudiant à une classe
        $this->classeManagementService->assignToClass($etudiant, $classe);

        return redirect()->route('esbtp.etudiants.show', $etudiant->id)
            ->with('success', 'Étudiant assigné à la classe avec succès.');
    }

    /**
     * Get available places for a given class.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailablePlaces($id)
    {
        $classe = ESBTPClasse::find($id);
        if (!$classe) {
            return response()->json([
                'available_places' => 0,
                'capacity' => 0
            ], 404);
        }
        
        $availablePlaces = $this->classeManagementService->getAvailablePlaces($id);

        return response()->json([
            'available_places' => $availablePlaces,
            'capacity' => $classe->places_totales ?? 0
        ]);
    }

    /**
     * Validate inscription data via AJAX.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateInscription(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenoms' => 'required|string|max:255',
            'genre' => 'required|in:M,F',
            'date_naissance' => 'nullable|date',
            'telephone' => 'required|string|max:20',
            'email_personnel' => 'required|email|max:255',
            'ville' => 'nullable|string|max:255',
            'commune' => 'nullable|string|max:255',
            'photo' => 'nullable|image|max:2048',
            'filiere_id' => 'required|exists:esbtp_filieres,id',
            'niveau_etude_id' => 'required|exists:esbtp_niveau_etudes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'classe_id' => 'nullable|exists:esbtp_classes,id',
            'date_admission' => 'required|date',
            'statut' => 'required|in:actif,inactif',
            'parents.*.nom' => 'required_without:parents.*.parent_id|string|max:255|nullable',
            'parents.*.prenoms' => 'required_without:parents.*.parent_id|string|max:255|nullable',
            'parents.*.relation' => 'required_without:parents.*.parent_id|string|max:50|nullable',
            'parents.*.telephone' => 'required_without:parents.*.parent_id|string|max:20|nullable',
            'parents.*.parent_id' => 'nullable|exists:esbtp_parents,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        return response()->json(['success' => true]);
    }

    /**
     * API: Rechercher des étudiants pour Select2
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchForApi(Request $request)
    {
        $query = $request->get('q', '');
        $page = $request->get('page', 1);
        $perPage = 10;

        if (strlen($query) < 3) {
            return response()->json([
                'results' => [],
                'pagination' => ['more' => false]
            ]);
        }

        $etudiants = ESBTPEtudiant::with('user')
            ->whereHas('user', function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%");
            })
            ->orWhere('matricule', 'like', "%{$query}%")
            ->orderBy('matricule')
            ->skip(($page - 1) * $perPage)
            ->take($perPage + 1) // +1 pour savoir s'il y a plus de résultats
            ->get();

        $hasMore = $etudiants->count() > $perPage;
        if ($hasMore) {
            $etudiants = $etudiants->take($perPage);
        }

        $results = $etudiants->map(function($etudiant) {
            return [
                'id' => $etudiant->id,
                'text' => "{$etudiant->matricule} - {$etudiant->user->name}"
            ];
        });

        return response()->json([
            'results' => $results,
            'pagination' => [
                'more' => $hasMore
            ]
        ]);
    }

    /**
     * API: Récupérer les inscriptions d'un étudiant
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInscriptionsForApi(Request $request)
    {
        $etudiantId = $request->get('etudiant_id');
        
        if (!$etudiantId) {
            return response()->json([]);
        }

        $inscriptions = ESBTPInscription::with(['filiere', 'niveauEtude', 'anneeUniversitaire'])
            ->where('etudiant_id', $etudiantId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($inscription) {
                return [
                    'id' => $inscription->id,
                    'filiere' => $inscription->filiere->name ?? 'Non définie',
                    'niveau' => $inscription->niveauEtude->name ?? 'Non défini',
                    'annee' => $inscription->anneeUniversitaire->name ?? 'Non définie'
                ];
            });

        return response()->json($inscriptions);
    }

    /**
     * API: Récupérer les soldes d'un étudiant par catégorie de frais
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSoldesForApi(Request $request)
    {
        try {
            $etudiantId = $request->get('etudiant_id');
            
            if (!$etudiantId) {
                return response()->json([
                    'categories' => [],
                    'total_paid' => 0,
                    'total_due' => 0
                ]);
            }

            $etudiant = ESBTPEtudiant::with(['inscriptions', 'paiements'])->find($etudiantId);
        
        if (!$etudiant) {
            return response()->json([
                'academic' => ['total' => 0, 'paid' => 0, 'remaining' => 0],
                'service' => ['total' => 0, 'paid' => 0, 'remaining' => 0],
                'administrative' => ['total' => 0, 'paid' => 0, 'remaining' => 0]
            ]);
        }

        // Récupérer l'inscription active (année en cours)
        $inscriptionActive = $etudiant->inscriptions()
            ->whereHas('anneeUniversitaire', function($q) {
                $q->where('is_current', true);
            })
            ->with(['classe.fraisClasseConfigurations.fraisCategory'])
            ->first();

        $soldes = [
            'academic' => ['total' => 0, 'paid' => 0, 'remaining' => 0, 'categories' => []],
            'service' => ['total' => 0, 'paid' => 0, 'remaining' => 0, 'categories' => []],
            'administrative' => ['total' => 0, 'paid' => 0, 'remaining' => 0, 'categories' => []]
        ];

        if ($inscriptionActive && $inscriptionActive->classe) {
            // Calculer les frais totaux par catégorie basés sur la configuration de la classe
            foreach ($inscriptionActive->classe->fraisClasseConfigurations as $config) {
                $category = $config->fraisCategory;
                if ($category) {
                    $categoryType = $category->category_type ?? 'academic';
                    
                    if (isset($soldes[$categoryType])) {
                        $soldes[$categoryType]['total'] += $config->montant;
                        $soldes[$categoryType]['categories'][] = [
                            'id' => $category->id,
                            'name' => $category->name,
                            'amount' => $config->montant,
                            'is_mandatory' => $category->is_mandatory
                        ];
                    }
                }
            }
        }

        // Calculer les paiements effectués par catégorie
        $paiementsValides = $etudiant->paiements()
            ->where('status', 'validé')
            ->whereHas('inscription', function($q) {
                $q->whereHas('anneeUniversitaire', function($subQ) {
                    $subQ->where('is_current', true);
                });
            })
            ->with('fraisCategory')
            ->get();

        foreach ($paiementsValides as $paiement) {
            if ($paiement->fraisCategory) {
                $categoryType = $paiement->fraisCategory->category_type ?? 'academic';
                if (isset($soldes[$categoryType])) {
                    $soldes[$categoryType]['paid'] += $paiement->montant;
                }
            }
        }

        // Calculer les montants restants
        foreach ($soldes as $type => &$solde) {
            $solde['remaining'] = max(0, $solde['total'] - $solde['paid']);
            $solde['percentage'] = $solde['total'] > 0 ? round(($solde['paid'] / $solde['total']) * 100, 1) : 0;
        }

        return response()->json($soldes);
        } catch (\Exception $e) {
            \Log::error('Erreur dans getSoldesForApi: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erreur lors de la récupération des soldes',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Prévisualiser une attestation de fréquentation pour un étudiant.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function previewAttestationFrequentation($id)
    {
        // Récupérer l'étudiant avec ses inscriptions
        $etudiant = ESBTPEtudiant::with([
            'inscriptions.anneeUniversitaire',
            'inscriptions.classe',
            'inscriptions.filiere',
            'inscriptions.niveauEtude',
        ])->findOrFail($id);

        // Récupérer l'inscription la plus récente ou active
        $inscription = $etudiant->inscriptions()
            ->with(['anneeUniversitaire', 'classe', 'filiere', 'niveauEtude'])
            ->orderBy('annee_universitaire_id', 'desc')
            ->first();

        if (!$inscription) {
            return back()->with('error', 'Aucune inscription trouvée pour cet étudiant.');
        }

        return view('esbtp.etudiants.attestation-frequentation-preview', [
            'etudiant' => $etudiant,
            'inscription' => $inscription
        ]);
    }

    /**
     * Générer une attestation de fréquentation pour un étudiant.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function genererAttestationFrequentation($id)
    {
        try {
            // Récupérer l'étudiant avec ses inscriptions
            $etudiant = ESBTPEtudiant::with([
                'inscriptions.anneeUniversitaire',
                'inscriptions.classe',
                'inscriptions.filiere',
                'inscriptions.niveauEtude',
            ])->findOrFail($id);

            // Récupérer l'inscription la plus récente ou active
            $inscription = $etudiant->inscriptions()
                ->with(['anneeUniversitaire', 'classe', 'filiere', 'niveauEtude'])
                ->orderBy('annee_universitaire_id', 'desc')
                ->first();

            if (!$inscription) {
                return back()->with('error', 'Aucune inscription trouvée pour cet étudiant.');
            }

            // Récupérer les paramètres de l'école
            $settings = \App\Helpers\SettingsHelper::getSchoolInfo();

            // Générer le PDF
            $pdf = Pdf::loadView('esbtp.etudiants.attestation-frequentation', [
                'etudiant' => $etudiant,
                'inscription' => $inscription,
                'settings' => $settings
            ]);

            // Configuration du PDF
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
                'margin-top' => 10,
                'margin-right' => 10,
                'margin-bottom' => 15,
                'margin-left' => 10,
            ]);

            // Nom du fichier
            $filename = 'attestation_frequentation_' . strtolower($etudiant->nom) . '_' . strtolower($etudiant->prenom) . '_' . date('Y-m-d') . '.pdf';

            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            \Log::error('Erreur génération attestation fréquentation: ' . $e->getMessage(), [
                'etudiant_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Erreur lors de la génération de l\'attestation: ' . $e->getMessage());
        }
    }

    /**
     * Mettre à jour la photo de profil d'un étudiant
     */
    public function updatePhoto(Request $request, ESBTPEtudiant $etudiant)
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Le fichier doit être une image valide (jpeg, png, jpg, gif) de maximum 5MB.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Supprimer l'ancienne photo si elle existe
            if ($etudiant->photo && Storage::disk('public')->exists($etudiant->photo)) {
                Storage::disk('public')->delete($etudiant->photo);
            }

            // Stocker la nouvelle photo
            $photo = $request->file('photo');
            $filename = 'etudiant_' . $etudiant->id . '_' . time() . '.' . $photo->getClientOriginalExtension();
            $path = $photo->storeAs('photos', $filename, 'public');

            // Mettre à jour le chemin de la photo dans la base de données
            $etudiant->update(['photo' => $path]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Photo mise à jour avec succès',
                'photo_url' => asset('storage/' . $path)
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            \Log::error('Erreur upload photo étudiant: ' . $e->getMessage(), [
                'etudiant_id' => $etudiant->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la photo: ' . $e->getMessage()
            ], 500);
        }
    }
}
