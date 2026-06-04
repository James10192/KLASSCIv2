<?php

namespace App\Http\Controllers;

use App\Models\ESBTPPaiement;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPAnneeUniversitaire;
use App\Http\Requests\Paiement\StorePaiementRequest;
use App\Http\Requests\Paiement\UpdatePaiementRequest;
use App\Services\PaymentFilterService;
use App\Services\PaymentStatsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Services\FuzzyNameMatcher;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;

class ESBTPPaiementController extends Controller
{
    use \App\Http\Controllers\Concerns\RespondsWithInlinePdf;

    protected PaymentFilterService $filterService;
    protected PaymentStatsService $statsService;

    /**
     * Constructeur du contrôleur.
     */
    public function __construct(PaymentFilterService $filterService, PaymentStatsService $statsService)
    {
        $this->filterService = $filterService;
        $this->statsService = $statsService;

        $this->middleware('auth');
        // Accepter soit `paiements.view` (voit tous), soit `paiements.view_own` (voit ses encaissements)
        $this->middleware('permission:paiements.view|paiements.view_own', ['only' => ['index', 'show', 'paiementsEtudiant']]);
        $this->middleware('permission:paiements.create', ['only' => ['create', 'store']]);
        $this->middleware('permission:paiements.edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:paiements.delete', ['only' => ['destroy']]);
        $this->middleware('permission:paiements.validate', ['only' => ['valider', 'rejeter', 'genererRecu']]);
    }

    /**
     * Affiche la liste des paiements.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
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
        Log::info('ESBTPPaiementController@index start', $baseLogContext);

        $data = $this->filterService->preparePaiementListing($request, $matcher, $baseLogContext, $startMicrotime, 'ESBTPPaiementController@index');

        $completionContext = array_merge($baseLogContext, [
            'timestamp' => now()->toIso8601String(),
            'total' => $data['summary']['total'],
            'page' => $data['summary']['page'],
            'per_page' => $data['summary']['per_page'],
            'duration_ms' => round((microtime(true) - $startMicrotime) * 1000, 2),
        ]);

        if ($request->ajax()) {
            Log::info('ESBTPPaiementController@index returning AJAX response', $completionContext);

            // Construire l'URL pour la navigation
            $navUrl = route('esbtp.paiements.index');
            if ($request->getQueryString()) {
                $navUrl .= '?' . $request->getQueryString();
            }

            return response()->json([
                'table' => view('esbtp.paiements.partials.table', [
                    'paiements' => $data['paiements'],
                ])->render(),
                'metrics_kpis' => view('esbtp.paiements.partials.metrics-kpis', [
                    'stats' => $data['stats'],
                ])->render(),
                'metrics_details' => view('esbtp.paiements.partials.metrics-details', [
                    'stats' => $data['stats'],
                ])->render(),
                'url' => $navUrl,  // URL navigable
                'summary' => $data['summary'],
                'last_updated_at' => optional($data['last_updated_at'])->toIso8601String(),
            ]);
        }

        Log::info('ESBTPPaiementController@index returning view', $completionContext);

        return view('esbtp.paiements.index', [
            'paiements' => $data['paiements'],
            'stats' => $data['stats'],
            'lastUpdatedAt' => $data['last_updated_at'],
        ]);
    }

    public function refresh(Request $request, FuzzyNameMatcher $matcher)
    {
        $startMicrotime = microtime(true);
        $startTimestamp = now()->toIso8601String();
        $baseLogContext = [
            'timestamp' => $startTimestamp,
            'url' => $request->fullUrl(),
            'query' => $request->query(),
            'user_id' => optional($request->user())->id,
        ];
        Log::info('ESBTPPaiementController@refresh start', $baseLogContext);

        $data = $this->filterService->preparePaiementListing($request, $matcher, $baseLogContext, $startMicrotime, 'ESBTPPaiementController@refresh');

        Log::info('ESBTPPaiementController@refresh returning AJAX response', array_merge($baseLogContext, [
            'timestamp' => now()->toIso8601String(),
            'total' => $data['summary']['total'],
            'page' => $data['summary']['page'],
            'per_page' => $data['summary']['per_page'],
            'duration_ms' => round((microtime(true) - $startMicrotime) * 1000, 2),
        ]));

        // Construire l'URL pour la navigation (remplacer /refresh par /paiements)
        $navUrl = route('esbtp.paiements.index');
        if ($request->getQueryString()) {
            $navUrl .= '?' . $request->getQueryString();
        }

        return response()->json([
            'table' => view('esbtp.paiements.partials.table', [
                'paiements' => $data['paiements'],
            ])->render(),
            'metrics_kpis' => view('esbtp.paiements.partials.metrics-kpis', [
                'stats' => $data['stats'],
            ])->render(),
            'metrics_details' => view('esbtp.paiements.partials.metrics-details', [
                'stats' => $data['stats'],
            ])->render(),
            'url' => $navUrl,  // URL navigable (pas /refresh)
            'summary' => $data['summary'],
            'last_updated_at' => optional($data['last_updated_at'])->toIso8601String(),
        ]);
    }

    /**
     * Vérifie s'il y a des changements dans les paiements (nouveau paiement, changement de statut)
     * sans charger toutes les données (requête ultra-légère pour polling)
     */
    public function checkForUpdates(Request $request)
    {
        $status = $request->input('status');
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');

        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $anneeId = $anneeEnCours?->id;

        // Construire une requête minimale avec les mêmes filtres
        $query = ESBTPPaiement::query();

        if ($status) {
            $query->where('status', $status);
        }

        if ($dateDebut) {
            $query->whereDate('date_paiement', '>=', $dateDebut);
        }

        if ($dateFin) {
            $query->whereDate('date_paiement', '<=', $dateFin);
        }

        if ($anneeId) {
            $query->whereHas('inscription', function ($q) use ($anneeId) {
                $q->where('annee_universitaire_id', $anneeId);
            });
        }

        // Récupérer seulement le count et le dernier updated_at/created_at
        $count = $query->count();
        $lastPaiement = $query->orderByDesc('updated_at')->first(['id', 'updated_at']);

        return response()->json([
            'count' => $count,
            'last_updated_at' => optional($lastPaiement)->updated_at?->toIso8601String(),
            'last_paiement_id' => optional($lastPaiement)->id,
        ]);
    }

    /**
     * Détermine le type de catégorie d'un paiement (nouveau système + fallback ancien).
     */
    private function determineCategoryType($paiement)
    {
        // D'abord essayer avec le nouveau système
        if ($paiement->fraisCategory) {
            return $paiement->fraisCategory->category_type ?? 'academic';
        }

        // Fallback sur l'ancien système
        if ($paiement->categorie) {
            return $this->mapOldCategoryToType($paiement->categorie->nom ?? '');
        }

        // Fallback basé sur le motif ou type_paiement
        if ($paiement->motif || $paiement->type_paiement) {
            return $this->inferCategoryFromMotif($paiement->motif ?? $paiement->type_paiement ?? '');
        }

        // Par défaut, considérer comme academic
        return 'academic';
    }

    /**
     * Mappe les anciennes catégories vers les nouveaux types.
     */
    private function mapOldCategoryToType($categoryName)
    {
        $name = strtolower($categoryName);

        if (str_contains($name, 'cantine') || str_contains($name, 'transport')) {
            return 'service';
        }

        if (str_contains($name, 'documentation') || str_contains($name, 'examen')) {
            return 'administrative';
        }

        return 'academic'; // inscription, scolarité par défaut
    }

    /**
     * Infère le type de catégorie à partir du motif.
     */
    private function inferCategoryFromMotif($motif)
    {
        $motif = strtolower($motif);

        if (str_contains($motif, 'cantine') || str_contains($motif, 'transport')) {
            return 'service';
        }

        if (str_contains($motif, 'documentation') || str_contains($motif, 'examen')) {
            return 'administrative';
        }

        return 'academic';
    }

    /**
     * Affiche le formulaire de création d'un paiement.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $etudiantId = $request->input('etudiant_id');
        $inscriptionId = $request->input('inscription_id');

        $etudiant = null;
        $inscription = null;

        // Si un étudiant est spécifié, récupérer ses informations
        if ($etudiantId) {
            $etudiant = ESBTPEtudiant::with(['user', 'inscriptions.anneeUniversitaire', 'inscriptions.filiere', 'inscriptions.niveauEtude'])
                ->findOrFail($etudiantId);

            // Si aucune inscription n'est spécifiée, prendre la plus récente
            if (!$inscriptionId && $etudiant->inscriptions->count() > 0) {
                $inscription = $etudiant->inscriptions->sortByDesc('created_at')->first();
            }
        }

        // Si une inscription est spécifiée, la récupérer
        if ($inscriptionId) {
            $inscription = ESBTPInscription::with(['etudiant.user', 'anneeUniversitaire', 'filiere', 'niveauEtude'])
                ->findOrFail($inscriptionId);

            // Si aucun étudiant n'est spécifié, prendre celui de l'inscription
            if (!$etudiant) {
                $etudiant = $inscription->etudiant;
            }
        }

        // Récupérer l'année universitaire en cours
        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Seuil "montant inhabituel" — au-delà, le caissier doit confirmer explicitement
        // Configurable par l'école via /esbtp/settings (tenant-level), default 500 000 FCFA
        $unusualAmountThreshold = (int) \App\Helpers\SettingsHelper::get('comptabilite.unusual_amount_threshold', 500000);

        return view('esbtp.paiements.create', compact('etudiant', 'inscription', 'anneeEnCours', 'unusualAmountThreshold'));
    }

    /**
     * Enregistre un nouveau paiement.
     *
     * @param  \App\Http\Requests\Paiement\StorePaiementRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePaiementRequest $request)
    {
        $validated = $request->validated();

        // LOG DÉTAILLÉ: Début de la requête de création de paiement
        $requestFingerprint = md5(json_encode([
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]));

        \Log::info('🔵 PAIEMENT STORE - Début de requête', [
            'timestamp' => now()->toIso8601String(),
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
            'fingerprint' => $requestFingerprint,
            'request_data' => $request->except(['_token']),
        ]);

        // Vérifier que l'étudiant correspond à l'inscription
        $inscription = ESBTPInscription::findOrFail($validated['inscription_id']);
        if ($inscription->etudiant_id != $validated['etudiant_id']) {
            \Log::warning('❌ PAIEMENT STORE - Étudiant ne correspond pas à l\'inscription', [
                'inscription_id' => $validated['inscription_id'],
                'etudiant_id_inscription' => $inscription->etudiant_id,
                'etudiant_id_fourni' => $validated['etudiant_id'],
            ]);
            return redirect()->back()->withErrors(['etudiant_id' => 'L\'étudiant ne correspond pas à l\'inscription sélectionnée.'])->withInput();
        }

        // PROTECTION BACKEND: Détecter les doublons récents (dernières 10 secondes)
        $timeWindow = now()->subSeconds(10);
        $duplicateCheck = ESBTPPaiement::where('inscription_id', $validated['inscription_id'])
            ->where('montant', $validated['montant'])
            ->where('frais_category_id', $validated['frais_category_id'])
            ->where('created_by', Auth::id())
            ->where('created_at', '>=', $timeWindow)
            ->orderByDesc('created_at')
            ->first();

        if ($duplicateCheck) {
            $timeDiff = now()->diffInSeconds($duplicateCheck->created_at);

            \Log::warning('⚠️ PAIEMENT STORE - DOUBLON DÉTECTÉ ET BLOQUÉ', [
                'duplicate_paiement_id' => $duplicateCheck->id,
                'duplicate_numero_recu' => $duplicateCheck->numero_recu,
                'time_diff_seconds' => $timeDiff,
                'inscription_id' => $validated['inscription_id'],
                'montant' => $validated['montant'],
                'frais_category_id' => $validated['frais_category_id'],
                'user_id' => Auth::id(),
                'fingerprint' => $requestFingerprint,
            ]);

            // Retourner un message de succès (ne pas alarmer l'utilisateur)
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Paiement enregistré avec succès. Numéro de reçu : ' . $duplicateCheck->numero_recu,
                    'duplicate_id' => $duplicateCheck->id,
                    'duplicate_numero_recu' => $duplicateCheck->numero_recu,
                ]);
            }

            return redirect()->route('esbtp.paiements.show', $duplicateCheck->id)
                ->with('success', 'Paiement enregistré avec succès. Numéro de reçu : ' . $duplicateCheck->numero_recu)
                ->with('duplicate_prevented', true);
        }

        \Log::info('✅ PAIEMENT STORE - Pas de doublon détecté, création du paiement', [
            'inscription_id' => $validated['inscription_id'],
            'montant' => $validated['montant'],
            'frais_category_id' => $validated['frais_category_id'],
        ]);

        try {
            DB::beginTransaction();

            // Récupérer la catégorie de frais pour définir le motif
            $fraisCategory = \App\Models\ESBTPFraisCategory::find($validated['frais_category_id']);

            // Générer un numéro de reçu
            $numeroRecu = ESBTPPaiement::genererNumeroRecu();

            // Créer le paiement
            $paiement = new ESBTPPaiement($validated);
            $paiement->numero_recu = $numeroRecu;
            $paiement->status = 'en_attente';
            $paiement->motif = $fraisCategory ? $fraisCategory->name : 'Paiement de frais'; // Pour compatibilité
            $paiement->created_by = Auth::id();
            $paiement->save();

            DB::commit();

            \Log::info('✅ PAIEMENT STORE - Paiement créé avec succès', [
                'paiement_id' => $paiement->id,
                'numero_recu' => $numeroRecu,
                'inscription_id' => $validated['inscription_id'],
                'montant' => $validated['montant'],
                'frais_category_id' => $validated['frais_category_id'],
                'user_id' => Auth::id(),
                'fingerprint' => $requestFingerprint,
            ]);

            // Envoyer notification aux super-admins si le paiement est en attente
            if ($paiement->status === 'en_attente') {
                try {
                    $notificationService = app(\App\Services\NotificationService::class);
                    $notificationService->notifyPaiementCreated($paiement, auth()->user());
                } catch (\Exception $e) {
                    Log::error('Erreur envoi notification paiement créé: ' . $e->getMessage());
                }
            }

            // Notifier les parents de la création du paiement
            try {
                $notificationService = app(\App\Services\NotificationService::class);
                $notificationService->notifyParentsPaiementValide($paiement);
            } catch (\Exception $e) {
                Log::error('Erreur envoi notification paiement aux parents: ' . $e->getMessage());
            }

            // Workflow event : notifie holders de paiements.validate (issue #298)
            \App\Support\WorkflowFlash::dispatch(
                'paiement.created',
                Auth::user(),
                ['paiement' => $paiement->id, 'inscription_id' => $paiement->inscription_id],
            );

            return redirect()->route('esbtp.paiements.show', $paiement->id)
                ->with('success', 'Paiement enregistré avec succès. Numéro de reçu : ' . $numeroRecu);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de l\'enregistrement du paiement : ' . $e->getMessage());

            return redirect()->back()
                ->withErrors(['error' => 'Une erreur est survenue lors de l\'enregistrement du paiement.'])
                ->withInput();
        }
    }

    /**
     * Affiche les détails d'un paiement.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // `creator:id,name` est l'alias canonique de `createdBy` (même FK created_by),
        // sélectionné en colonnes minimales pour réduire le payload.
        $paiement = ESBTPPaiement::with([
            'etudiant.user',
            'fraisCategory',
            'inscription.anneeUniversitaire',
            'inscription.filiere',
            'inscription.niveauEtude',
            'validatedBy',
            'creator:id,name',
            'updatedBy'
        ])->findOrFail($id);

        $this->authorize('view', $paiement);

        return view('esbtp.paiements.show', compact('paiement'));
    }

    /**
     * Affiche le formulaire de modification d'un paiement.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $paiement = ESBTPPaiement::findOrFail($id);
        $this->authorize('update', $paiement);

        // Vérifier que l'utilisateur a la permission de gérer les paiements
        if (!auth()->user()->can('paiements.manage')) {
            return redirect()->route('esbtp.paiements.show', $id)
                ->with('error', 'Seuls les super-administrateurs peuvent modifier les paiements.');
        }

        $paiement = ESBTPPaiement::with([
            'etudiant.user',
            'inscription.anneeUniversitaire',
            'inscription.filiere',
            'inscription.niveauEtude'
        ])->findOrFail($id);

        // Vérifier si le paiement peut être modifié
        if ($paiement->status === 'validé') {
            return redirect()->route('esbtp.paiements.show', $paiement->id)
                ->with('error', 'Ce paiement a déjà été validé et ne peut plus être modifié.');
        }

        // Charger toutes les catégories de frais actives pour le select "Catégorie"
        $feeCategories = \App\Models\ESBTPFraisCategory::active()->ordered()->get();

        // Essayer de retrouver la catégorie correspondant au motif actuel
        // (pour pré-sélectionner la bonne option même si frais_category_id est null/vide)
        $selectedCategoryId = $paiement->frais_category_id;

        if (!$selectedCategoryId && $paiement->motif) {
            // Si pas de frais_category_id, chercher par nom de motif
            $matchingCategory = $feeCategories->firstWhere('name', $paiement->motif);
            if ($matchingCategory) {
                $selectedCategoryId = $matchingCategory->id;
            }
        }

        return view('esbtp.paiements.edit', compact('paiement', 'feeCategories', 'selectedCategoryId'));
    }

    /**
     * Met à jour un paiement existant.
     *
     * @param  \App\Http\Requests\Paiement\UpdatePaiementRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePaiementRequest $request, $id)
    {
        // Vérifier que l'utilisateur a la permission de gérer les paiements
        if (!auth()->user()->can('paiements.manage')) {
            return redirect()->route('esbtp.paiements.show', $id)
                ->with('error', 'Seuls les super-administrateurs peuvent modifier les paiements.');
        }

        $paiement = ESBTPPaiement::findOrFail($id);

        // Vérifier si le paiement peut être modifié
        if ($paiement->status === 'validé') {
            return redirect()->route('esbtp.paiements.show', $paiement->id)
                ->with('error', 'Ce paiement a déjà été validé et ne peut plus être modifié.');
        }

        // S1.4 — Garde verrouillage de période comptable
        if ($block = $this->assertPeriodNotLocked($paiement)) {
            return redirect()->route('esbtp.paiements.show', $paiement->id)
                ->with('error', $block['message']);
        }

        $validated = $request->validated();

        try {
            DB::beginTransaction();

            // Récupérer la catégorie de frais pour mettre à jour le motif automatiquement
            $fraisCategory = \App\Models\ESBTPFraisCategory::find($validated['frais_category_id']);

            // Mettre à jour le paiement
            $paiement->fill($validated);
            $paiement->motif = $fraisCategory->name; // Synchroniser le motif avec la catégorie
            $paiement->updated_by = Auth::id();
            $paiement->save();

            DB::commit();

            return redirect()->route('esbtp.paiements.show', $paiement->id)
                ->with('success', 'Paiement mis à jour avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour du paiement : ' . $e->getMessage());

            return redirect()->back()
                ->withErrors(['error' => 'Une erreur est survenue lors de la mise à jour du paiement.'])
                ->withInput();
        }
    }


    /**
     * Prévisualise un reçu de paiement en HTML avant génération PDF.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function previewRecu($id)
    {
        $paiement = ESBTPPaiement::with([
            'etudiant.user',
            'inscription.anneeUniversitaire',
            'inscription.filiere',
            'inscription.niveauEtude',
            'fraisCategory',
            'validatedBy',
            'creator:id,name'
        ])->findOrFail($id);

        // Retourner la vue HTML pour prévisualisation
        return view('esbtp.paiements.preview', compact('paiement'));
    }

    /**
     * Génère un reçu de paiement au format PDF.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function genererRecu($id, ?Request $request = null)
    {
        $paiement = ESBTPPaiement::with([
            'etudiant.user',
            'inscription.anneeUniversitaire',
            'inscription.filiere',
            'inscription.niveauEtude',
            'fraisCategory',
            'validatedBy',
            'creator:id,name'
        ])->findOrFail($id);

        // Récupérer les paramètres depuis les settings comme pour les bulletins
        $settings = $this->getReceiptSettings();

        // Générer le PDF avec les settings
        $pdf = PDF::loadView('esbtp.paiements.recu', compact('paiement', 'settings'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'dpi' => 150,
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => false,
                'isFontSubsettingEnabled' => true,
            ]);

        // Définir le nom du fichier
        $filename = 'Recu_' . $paiement->numero_recu . '.pdf';

        return $this->respondWithPdf($pdf, $filename, $request);
    }

    /**
     * Récupère les paramètres pour les reçus depuis les settings.
     */
    public function getReceiptSettings()
    {
        $settings = [
            'school_name' => \App\Helpers\SettingsHelper::get('school_name', config('app.name', 'KLASSCI')),
            'school_address' => \App\Helpers\SettingsHelper::get('school_address', ''),
            'school_phone' => \App\Helpers\SettingsHelper::get('school_phone', ''),
            'school_email' => \App\Helpers\SettingsHelper::get('school_email', ''),
            'show_logo' => \App\Helpers\SettingsHelper::get('receipt_show_logo', '1') === '1',
        ];

        // Préparer le logo si nécessaire
        if ($settings['show_logo']) {
            $logoPath = \App\Helpers\SettingsHelper::get('school_logo');
            $settings['logo_base64'] = $this->prepareLogoBase64($logoPath);
        }

        return $settings;
    }

    /**
     * Prépare le logo en base64 pour les PDFs.
     */
    private function prepareLogoBase64($logoPath)
    {
        if (!$logoPath) {
            return null;
        }

        // Essayer différents chemins possibles
        $paths = [
            storage_path('app/public/' . $logoPath),
            public_path($logoPath),
            public_path('images/LOGO-KLASSCI-PNG.png'), // Fallback par défaut
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
     * Récupère les paiements d'un étudiant.
     *
     * @param  int  $etudiantId
     * @return \Illuminate\Http\Response
     */
    public function paiementsEtudiant($etudiantId)
    {
        $etudiant = ESBTPEtudiant::with(['user', 'inscriptions.anneeUniversitaire'])->findOrFail($etudiantId);

        $paiements = ESBTPPaiement::with(['inscription.anneeUniversitaire'])
            ->where('etudiant_id', $etudiantId)
            ->orderBy('date_paiement', 'desc')
            ->get();

        // Calculer le total des paiements validés
        $totalValide = $paiements->where('status', 'validé')->sum('montant');

        return view('esbtp.paiements.etudiant', compact('etudiant', 'paiements', 'totalValide'));
    }

    /**
     * Charger les étudiants par statut avec pagination AJAX
     */
    public function loadStudentsByStatut(Request $request, $statut)
    {
        try {
            $categoryId = $request->input('category_id');
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 20);

            if (!$categoryId) {
                return response()->json(['error' => 'Category ID required'], 400);
            }

            $category = \App\Models\ESBTPFraisCategory::find($categoryId);
            if (!$category) {
                return response()->json(['error' => 'Category not found'], 404);
            }

            // Récupérer les paramètres de filtrage
            $filiereId = $request->input('filiere_id');
            $niveauId = $request->input('niveau_id');
            $anneeId = $request->input('annee_id');

            // Année par défaut
            if (!$anneeId) {
                $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
                $anneeId = $anneeEnCours ? $anneeEnCours->id : null;
            }

            // Requête pour les inscriptions actives
        $inscriptionsQuery = \App\Models\ESBTPInscription::with([
            'etudiant.user',
            'filiere',
            'niveauEtude',
            'anneeUniversitaire'
        ])->whereIn('status', ['active', 'en_attente', 'validée']);

        // Appliquer les filtres
        if ($anneeId) {
            $inscriptionsQuery->where('annee_universitaire_id', $anneeId);
        }
        if ($filiereId) {
            $inscriptionsQuery->where('filiere_id', $filiereId);
        }
        if ($niveauId) {
            $inscriptionsQuery->where('niveau_id', $niveauId);
        }

        $inscriptions = $inscriptionsQuery->get();
        $inscriptionIds = $inscriptions->pluck('id')->toArray();

        // Pré-charger données pour performance
        $configurations = collect();
        if (!empty($inscriptions)) {
            $configurations = \App\Models\ESBTPFraisConfiguration::where('is_active', true)
                ->where('frais_category_id', $categoryId)
                ->get()
                ->groupBy(function($config) {
                    return $config->frais_category_id . '_' . $config->filiere_id . '_' . $config->niveau_id;
                });
        }

        $subscriptions = collect();
        if (!empty($inscriptionIds)) {
            $subscriptions = \App\Models\ESBTPFraisSubscription::where('is_active', true)
                ->whereIn('inscription_id', $inscriptionIds)
                ->where('frais_category_id', $categoryId)
                ->get()
                ->groupBy('inscription_id');
        }

        $paiements = collect();
        if (!empty($inscriptionIds)) {
            $paiements = ESBTPPaiement::where('status', 'validé')
                ->whereIn('inscription_id', $inscriptionIds)
                ->where('frais_category_id', $categoryId)
                ->where(function($query) {
                    $query->where('type_paiement', '!=', 'reliquat')
                          ->orWhereNull('type_paiement');
                })
                ->get()
                ->groupBy(function($paiement) {
                    return $paiement->inscription_id . '_' . $paiement->frais_category_id;
                });
        }

        // Analyser les détails avec données pré-chargées
        $details = $this->statsService->analyserCategorieDetailleOptimisee($category, $inscriptions, $configurations, $subscriptions, $paiements);

        // Filtrer par statut demandé
        $etudiants = collect();
        switch ($statut) {
            case 'non_payes':
                $etudiants = $details['etudiants_non_payes'];
                break;
            case 'en_retard':
                $etudiants = $details['etudiants_en_retard'];
                break;
            case 'a_jour':
                $etudiants = $details['etudiants_a_jour'];
                break;
        }

        // Filtre de recherche texte (nom, prénom, matricule)
        $search = $request->input('search');
        if ($search && trim($search) !== '') {
            $searchLower = mb_strtolower(trim($search));
            $etudiants = $etudiants->filter(function ($item) use ($searchLower) {
                $etudiant = $item['inscription']->etudiant ?? null;
                if (!$etudiant) return false;

                $nom = mb_strtolower($etudiant->nom ?? '');
                $prenoms = mb_strtolower($etudiant->prenoms ?? '');
                $matricule = mb_strtolower($etudiant->matricule ?? '');

                return str_contains($nom, $searchLower)
                    || str_contains($prenoms, $searchLower)
                    || str_contains($matricule, $searchLower)
                    || str_contains($nom . ' ' . $prenoms, $searchLower)
                    || str_contains($prenoms . ' ' . $nom, $searchLower);
            })->values();
        }

        // Paginer les résultats
        $total = $etudiants->count();
        $offset = ($page - 1) * $perPage;
        $etudiantsPagines = $etudiants->slice($offset, $perPage);
        $hasMore = $total > ($offset + $perPage);

        // Render template approprié
        if ((int)$page === 1) {
            $html = view('esbtp.paiements.partials.liste-etudiants', [
                'etudiants' => $etudiantsPagines,
                'statut' => $statut,
                'category' => $category
            ])->render();
        } else {
            $html = view('esbtp.paiements.partials.lignes-etudiants', [
                'etudiants' => $etudiantsPagines,
                'statut' => $statut,
                'category' => $category
            ])->render();
            }

            return response()->json([
                'html' => $html,
                'total' => $total,
                'current_page' => (int)$page,
                'has_more' => $hasMore
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur dans loadStudentsByStatut: ' . $e->getMessage(), [
                'statut' => $statut,
                'category_id' => $request->input('category_id'),
                'page' => $request->get('page', 1),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ]);

            return response()->json([
                'error' => 'Erreur serveur: ' . $e->getMessage(),
                'debug' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Export Excel — liste étudiants par statut de paiement (suivi-categories)
     */
    public function exportStudentsExcel(Request $request, string $statut)
    {
        $categoryId = $request->input('category_id');
        if (!$categoryId) {
            abort(400, 'category_id requis');
        }

        $category = \App\Models\ESBTPFraisCategory::find($categoryId);
        if (!$category) {
            abort(404, 'Catégorie introuvable');
        }

        $filiereId = $request->input('filiere_id');
        $niveauId  = $request->input('niveau_id');
        $anneeId   = $request->input('annee_id');

        if (!$anneeId) {
            $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
            $anneeId = $anneeEnCours ? $anneeEnCours->id : null;
        }

        $inscriptionsQuery = \App\Models\ESBTPInscription::with([
            'etudiant', 'filiere', 'niveauEtude', 'anneeUniversitaire', 'classe'
        ])->whereIn('status', ['active', 'en_attente', 'validée']);

        if ($anneeId)   $inscriptionsQuery->where('annee_universitaire_id', $anneeId);
        if ($filiereId) $inscriptionsQuery->where('filiere_id', $filiereId);
        if ($niveauId)  $inscriptionsQuery->where('niveau_id', $niveauId);

        $inscriptions   = $inscriptionsQuery->get();
        $inscriptionIds = $inscriptions->pluck('id')->toArray();

        $configurations = \App\Models\ESBTPFraisConfiguration::where('is_active', true)
            ->where('frais_category_id', $categoryId)->get()
            ->groupBy(fn($c) => $c->frais_category_id . '_' . $c->filiere_id . '_' . $c->niveau_id);

        $subscriptions = \App\Models\ESBTPFraisSubscription::where('is_active', true)
            ->whereIn('inscription_id', $inscriptionIds)
            ->where('frais_category_id', $categoryId)->get()
            ->groupBy('inscription_id');

        $paiements = ESBTPPaiement::where('status', 'validé')
            ->whereIn('inscription_id', $inscriptionIds)
            ->where('frais_category_id', $categoryId)
            ->where(fn($q) => $q->where('type_paiement', '!=', 'reliquat')->orWhereNull('type_paiement'))
            ->get()
            ->groupBy(fn($p) => $p->inscription_id . '_' . $p->frais_category_id);

        $details = $this->statsService->analyserCategorieDetailleOptimisee($category, $inscriptions, $configurations, $subscriptions, $paiements);

        $etudiants = match($statut) {
            'non_payes' => $details['etudiants_non_payes'],
            'en_retard' => $details['etudiants_en_retard'],
            'a_jour'    => $details['etudiants_a_jour'],
            default     => collect(),
        };

        $statutLabel = match($statut) {
            'non_payes' => 'Aucun paiement',
            'en_retard' => 'Paiements partiels',
            'a_jour'    => 'À jour',
            default     => ucfirst($statut),
        };

        $montantDu   = $etudiants->sum(fn($e) => $e['montant_attendu'] ?? 0);
        $montantPaye = $etudiants->sum(fn($e) => $e['montant_paye'] ?? 0);

        $stats = [
            'total'             => $etudiants->count(),
            'montant_total_du'  => $montantDu,
            'montant_total_paye'=> $montantPaye,
        ];

        $filiere = $filiereId ? \App\Models\ESBTPFiliere::find($filiereId) : null;
        $niveau  = $niveauId  ? \App\Models\ESBTPNiveauEtude::find($niveauId) : null;

        $filters  = [
            'filiere' => $filiere?->name,
            'niveau'  => $niveau?->name,
        ];

        $schoolInfo = \App\Helpers\SettingsHelper::getSchoolInfo();
        $settings = [
            'school_name'    => $schoolInfo['name'],
            'school_address' => $schoolInfo['address'],
            'school_phone'   => $schoolInfo['phone'] ?: $schoolInfo['mobile'],
            'school_email'   => $schoolInfo['email'],
            'school_city'    => $schoolInfo['city'],
            'primary_color'  => \App\Helpers\SettingsHelper::getPdfSettings()['primary_color'] ?? '#0453cb',
        ];

        $filename = 'suivi-' . $statut . '-' . ($category->name ? \Illuminate\Support\Str::slug($category->name) . '-' : '') . now()->format('Ymd') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\SuiviPaiementsExport($etudiants, $category, $statutLabel, $stats, $filters, $settings),
            $filename
        );
    }

    /**
     * Export PDF — liste étudiants par statut de paiement (suivi-categories)
     *
     * Pour 1000+ étudiants, utilise une stratégie de chunk+merge avec FPDI :
     * chaque chunk de 200 lignes est rendu comme un PDF séparé puis fusionné.
     * Cela évite le crash mémoire de DomPDF qui charge tout le HTML d'un coup.
     */
    public function exportStudentsPdf(Request $request, string $statut)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $categoryId = $request->input('category_id');
        if (!$categoryId) {
            abort(400, 'category_id requis');
        }

        $category = \App\Models\ESBTPFraisCategory::find($categoryId);
        if (!$category) {
            abort(404, 'Catégorie introuvable');
        }

        $filiereId = $request->input('filiere_id');
        $niveauId  = $request->input('niveau_id');
        $anneeId   = $request->input('annee_id');

        if (!$anneeId) {
            $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
            $anneeId = $anneeEnCours ? $anneeEnCours->id : null;
        }

        $inscriptionsQuery = \App\Models\ESBTPInscription::with([
            'etudiant', 'filiere', 'niveauEtude', 'anneeUniversitaire', 'classe'
        ])->whereIn('status', ['active', 'en_attente', 'validée']);

        if ($anneeId)   $inscriptionsQuery->where('annee_universitaire_id', $anneeId);
        if ($filiereId) $inscriptionsQuery->where('filiere_id', $filiereId);
        if ($niveauId)  $inscriptionsQuery->where('niveau_id', $niveauId);

        $inscriptions   = $inscriptionsQuery->get();
        $inscriptionIds = $inscriptions->pluck('id')->toArray();

        $configurations = \App\Models\ESBTPFraisConfiguration::where('is_active', true)
            ->where('frais_category_id', $categoryId)->get()
            ->groupBy(fn($c) => $c->frais_category_id . '_' . $c->filiere_id . '_' . $c->niveau_id);

        $subscriptions = \App\Models\ESBTPFraisSubscription::where('is_active', true)
            ->whereIn('inscription_id', $inscriptionIds)
            ->where('frais_category_id', $categoryId)->get()
            ->groupBy('inscription_id');

        $paiements = ESBTPPaiement::where('status', 'validé')
            ->whereIn('inscription_id', $inscriptionIds)
            ->where('frais_category_id', $categoryId)
            ->where(fn($q) => $q->where('type_paiement', '!=', 'reliquat')->orWhereNull('type_paiement'))
            ->get()
            ->groupBy(fn($p) => $p->inscription_id . '_' . $p->frais_category_id);

        $details = $this->statsService->analyserCategorieDetailleOptimisee($category, $inscriptions, $configurations, $subscriptions, $paiements);

        $etudiants = match($statut) {
            'non_payes' => $details['etudiants_non_payes'],
            'en_retard' => $details['etudiants_en_retard'],
            'a_jour'    => $details['etudiants_a_jour'],
            default     => collect(),
        };

        $statutLabel = match($statut) {
            'non_payes' => 'Aucun paiement',
            'en_retard' => 'Paiements partiels',
            'a_jour'    => 'À jour',
            default     => ucfirst($statut),
        };

        $montantDu   = $etudiants->sum(fn($e) => $e['montant_attendu'] ?? 0);
        $montantPaye = $etudiants->sum(fn($e) => $e['montant_paye'] ?? 0);

        $filiere = $filiereId ? \App\Models\ESBTPFiliere::find($filiereId) : null;
        $niveau  = $niveauId  ? \App\Models\ESBTPNiveauEtude::find($niveauId) : null;

        $stats = [
            'total'              => $etudiants->count(),
            'montant_total_du'   => $montantDu,
            'montant_total_paye' => $montantPaye,
            'taux_recouvrement'  => $montantDu > 0 ? round(($montantPaye / $montantDu) * 100, 1) : 0,
            'statut'             => $statut,
            'filiere_name'       => $filiere?->name,
            'niveau_name'        => $niveau?->name,
        ];

        $schoolInfo  = \App\Helpers\SettingsHelper::getSchoolInfo();
        $pdfSettings = \App\Helpers\SettingsHelper::getPdfSettings();

        $filename = 'suivi-' . $statut . '-' . (\Illuminate\Support\Str::slug($category->name ?? $statut)) . '-' . now()->format('Ymd') . '.pdf';

        $pdfOptions = [
            'dpi'                     => 72,
            'defaultFont'             => 'DejaVu Sans',
            'isRemoteEnabled'         => false,
            'isHtml5ParserEnabled'    => true,
            'isPhpEnabled'            => false,
            'isFontSubsettingEnabled' => true,
        ];

        $chunkSize = 200;

        // Pour les petits exports (< 500), rendu direct sans FPDI
        if ($etudiants->count() <= 500) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
                'esbtp.paiements.pdf.suivi-liste-etudiants',
                compact('etudiants', 'category', 'statutLabel', 'schoolInfo', 'pdfSettings', 'stats')
            )->setPaper('a4', 'portrait')->setOptions($pdfOptions);

            return $pdf->download($filename);
        }

        // Pour les gros exports (500+), chunk + merge avec FPDI
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $chunks = $etudiants->chunk($chunkSize);
        $tempFiles = [];
        $totalChunks = $chunks->count();

        foreach ($chunks as $chunkIndex => $chunk) {
            $isFirstChunk = ($chunkIndex === 0);
            $isLastChunk  = ($chunkIndex === $totalChunks - 1);
            $rowOffset    = $chunkIndex * $chunkSize;

            $chunkPdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
                'esbtp.paiements.pdf.suivi-liste-etudiants',
                [
                    'etudiants'    => $chunk,
                    'category'     => $category,
                    'statutLabel'  => $statutLabel,
                    'schoolInfo'   => $schoolInfo,
                    'pdfSettings'  => $pdfSettings,
                    'stats'        => $stats,
                    'isFirstChunk' => $isFirstChunk,
                    'isLastChunk'  => $isLastChunk,
                    'rowOffset'    => $rowOffset,
                    'chunkIndex'   => $chunkIndex,
                ]
            )->setPaper('a4', 'portrait')->setOptions($pdfOptions);

            $tempPath = $tempDir . '/suivi_chunk_' . uniqid() . '_' . $chunkIndex . '.pdf';
            file_put_contents($tempPath, $chunkPdf->output());
            $tempFiles[] = $tempPath;

            // Libérer la mémoire entre chaque chunk
            unset($chunkPdf);
        }

        // Fusionner tous les chunks avec FPDI
        $merger = new \setasign\Fpdi\Fpdi();
        foreach ($tempFiles as $file) {
            $pageCount = $merger->setSourceFile($file);
            for ($p = 1; $p <= $pageCount; $p++) {
                $tpl = $merger->importPage($p);
                $size = $merger->getTemplateSize($tpl);
                $merger->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $merger->useTemplate($tpl);
            }
        }

        $finalPath = $tempDir . '/suivi_final_' . uniqid() . '.pdf';
        $merger->Output('F', $finalPath);
        unset($merger);

        // Nettoyer les fichiers temporaires de chunks
        foreach ($tempFiles as $file) {
            @unlink($file);
        }

        // Retourner le PDF fusionné et nettoyer après envoi
        return response()->download($finalPath, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Payer un reliquat
     */
    public function payReliquat(Request $request)
    {
        try {
            // Validation
            $request->validate([
                'reliquat_id' => 'required|exists:esbtp_reliquats_details,id',
                'montant' => 'required|numeric|min:1',
                'mode_paiement' => 'required|string',
                'notes' => 'nullable|string|max:1000'
            ]);

            $reliquatId = $request->input('reliquat_id');
            $montantPaye = $request->input('montant');
            $modePaiement = $request->input('mode_paiement');
            $notes = $request->input('notes');

            DB::beginTransaction();

            // Récupérer le reliquat
            $reliquat = \App\Models\ESBTPReliquatDetail::findOrFail($reliquatId);

            // Vérifier que le montant ne dépasse pas le solde restant
            if ($montantPaye > $reliquat->solde_restant) {
                return redirect()->back()->with('error', 'Le montant à payer ne peut pas dépasser le solde restant (' . number_format($reliquat->solde_restant, 0, ',', ' ') . ' FCFA).');
            }

            // Générer un numéro de reçu
            $numeroRecu = ESBTPPaiement::genererNumeroRecu();

            // Créer le paiement
            $paiement = ESBTPPaiement::create([
                'etudiant_id' => $reliquat->inscriptionDestination->etudiant_id,
                'inscription_id' => $reliquat->inscription_destination_id,
                'annee_universitaire_id' => $reliquat->inscriptionDestination->annee_universitaire_id,
                'frais_category_id' => $reliquat->fraisSubscription->frais_category_id,
                'montant' => $montantPaye,
                'mode_paiement' => $modePaiement,
                'date_paiement' => now(),
                'status' => 'en_attente',
                'type_paiement' => 'reliquat',
                'reliquat_detail_id' => $reliquat->id,
                'motif' => $reliquat->fraisSubscription->fraisCategory->name ?? 'Reliquat',
                'numero_recu' => $numeroRecu,
                'commentaire' => $notes ? "Paiement de reliquat: " . $notes : "Paiement de reliquat",
                'created_by' => auth()->id()
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Paiement de reliquat créé avec succès. Le paiement est en attente de validation. Montant: ' . number_format($montantPaye, 0, ',', ' ') . ' FCFA - Numéro de reçu: ' . $numeroRecu);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Erreur lors du paiement de reliquat', [
                'reliquat_id' => $request->input('reliquat_id'),
                'montant' => $request->input('montant'),
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ]);

            return redirect()->back()->with('error', 'Erreur lors du paiement: ' . $e->getMessage());
        }
    }

    /**
     * Valider un paiement
     */
    public function valider(Request $request, $id)
    {
        try {
            $paiement = ESBTPPaiement::findOrFail($id);

            // Vérifier si le paiement peut être validé
            if ($paiement->status === 'validé') {
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Ce paiement est déjà validé.'], 400);
                }
                return redirect()->back()->with('error', 'Ce paiement est déjà validé.');
            }

            if ($paiement->status === 'rejeté') {
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Ce paiement a été rejeté et ne peut pas être validé.'], 400);
                }
                return redirect()->back()->with('error', 'Ce paiement a été rejeté et ne peut pas être validé.');
            }

            // S1.1 — Garde anti-auto-validation (séparation des tâches anti-fraude)
            if ($block = $this->assertNotSelfValidation($paiement)) {
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => $block['message']], 403);
                }
                return redirect()->back()->with('error', $block['message']);
            }

            DB::beginTransaction();

            // Changer le statut du paiement
            $paiement->update([
                'status' => 'validé',
                'date_validation' => now(),
                'validateur_id' => auth()->id()
            ]);

            // Si c'est un paiement de reliquat, mettre à jour le reliquat
            if ($paiement->type_paiement === 'reliquat' && $paiement->reliquat_detail_id) {
                $reliquat = \App\Models\ESBTPReliquatDetail::find($paiement->reliquat_detail_id);
                if ($reliquat) {
                    $nouveauMontantRegle = $reliquat->montant_regle + $paiement->montant;
                    $nouveauSolde = $reliquat->montant_reliquat - $nouveauMontantRegle;

                    $reliquat->update([
                        'montant_regle' => $nouveauMontantRegle,
                        'statut' => $nouveauSolde <= 0 ? 'totalement_regle' : 'partiellement_regle',
                        'date_derniere_maj' => now()
                    ]);
                }
            }

            DB::commit();

            app(\App\Services\GroupCacheInvalidator::class)->invalidate('paiement_validated');

            // S1.6 — Notif gros montant aux users avec permission `comptabilite.notifications.high_amount`
            $this->notifyHighAmountIfAny($paiement, auth()->user());

            // Envoyer notification à l'étudiant
            try {
                $notificationService = app(\App\Services\NotificationService::class);
                $notificationService->notifyPaiementValide($paiement, auth()->user());

                // Envoyer notification aux parents
                $notificationService->notifyParentsPaiementValide($paiement);
            } catch (\Exception $e) {
                Log::error('Erreur envoi notification paiement validé: ' . $e->getMessage());
            }

            // Désactiver les rappels pour ce paiement
            try {
                $reminder = \App\Models\NotificationReminder::where('remindable_type', 'App\Models\ESBTPPaiement')
                    ->where('remindable_id', $paiement->id)
                    ->first();
                if ($reminder) {
                    $reminder->deactivate();
                }
            } catch (\Exception $e) {
                Log::error('Erreur désactivation reminder paiement: ' . $e->getMessage());
            }

            // Workflow event : notifie holders de inscriptions.validate (issue #298)
            \App\Support\WorkflowFlash::dispatch(
                'paiement.validated',
                Auth::user(),
                ['inscription' => $paiement->inscription_id, 'paiement' => $paiement->id],
            );

            // Si requête AJAX, retourner JSON
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Paiement validé avec succès.',
                    'paiement_id' => $paiement->id
                ]);
            }

            return redirect()->back()->with('success', 'Paiement validé avec succès.');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Erreur lors de la validation du paiement', [
                'paiement_id' => $id,
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la validation: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Erreur lors de la validation: ' . $e->getMessage());
        }
    }

    /**
     * Valider rapidement un paiement depuis modal (inscriptions.index)
     * Version simplifiée de valider() pour usage AJAX
     */
    public function validerRapide(ESBTPPaiement $paiement)
    {
        try {
            // Vérifier si le paiement peut être validé
            if ($paiement->status === 'validé') {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce paiement est déjà validé.'
                ], 400);
            }

            if ($paiement->status === 'rejeté') {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce paiement a été rejeté et ne peut pas être validé.'
                ], 400);
            }

            // S1.1 — Garde anti-auto-validation (séparation des tâches anti-fraude)
            if ($block = $this->assertNotSelfValidation($paiement)) {
                return response()->json(['success' => false, 'message' => $block['message']], 403);
            }

            DB::beginTransaction();

            // Changer le statut du paiement
            $paiement->update([
                'status' => 'validé',
                'date_validation' => now(),
                'validateur_id' => auth()->id()
            ]);

            // Si c'est un paiement de reliquat, mettre à jour le reliquat
            if ($paiement->type_paiement === 'reliquat' && $paiement->reliquat_detail_id) {
                $reliquat = \App\Models\ESBTPReliquatDetail::find($paiement->reliquat_detail_id);
                if ($reliquat) {
                    $nouveauMontantRegle = $reliquat->montant_regle + $paiement->montant;
                    $nouveauSolde = $reliquat->montant_reliquat - $nouveauMontantRegle;

                    $reliquat->update([
                        'montant_regle' => $nouveauMontantRegle,
                        'statut' => $nouveauSolde <= 0 ? 'totalement_regle' : 'partiellement_regle',
                        'date_derniere_maj' => now()
                    ]);
                }
            }

            DB::commit();

            // S1.6 — Notif gros montant
            $this->notifyHighAmountIfAny($paiement, auth()->user());

            // Envoyer notifications
            try {
                $notificationService = app(\App\Services\NotificationService::class);
                $notificationService->notifyPaiementValide($paiement, auth()->user());
                $notificationService->notifyParentsPaiementValide($paiement);
            } catch (\Exception $e) {
                Log::error('Erreur envoi notification paiement validé (rapide): ' . $e->getMessage());
            }

            // Désactiver les rappels pour ce paiement
            try {
                $reminder = \App\Models\NotificationReminder::where('remindable_type', 'App\Models\ESBTPPaiement')
                    ->where('remindable_id', $paiement->id)
                    ->first();
                if ($reminder) {
                    $reminder->deactivate();
                }
            } catch (\Exception $e) {
                Log::error('Erreur désactivation reminder paiement (rapide): ' . $e->getMessage());
            }

            Log::info('Validation rapide de paiement réussie', [
                'paiement_id' => $paiement->id,
                'inscription_id' => $paiement->inscription_id,
                'montant' => $paiement->montant,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Paiement validé avec succès.',
                'paiement' => [
                    'id' => $paiement->id,
                    'status' => $paiement->status,
                    'date_validation' => $paiement->date_validation->format('d/m/Y H:i')
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Erreur lors de la validation rapide du paiement', [
                'paiement_id' => $paiement->id,
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rejeter un paiement
     */
    public function rejeter(\App\Http\Requests\Paiement\RejeterPaiementRequest $request, $id)
    {
        try {
            $paiement = ESBTPPaiement::findOrFail($id);

            // Vérifier si le paiement peut être rejeté
            if ($paiement->status === 'validé') {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ce paiement est déjà validé et ne peut pas être rejeté.'
                    ], 400);
                }
                return redirect()->back()->with('error', 'Ce paiement est déjà validé et ne peut pas être rejeté.');
            }

            if ($paiement->status === 'rejeté') {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ce paiement est déjà rejeté.'
                    ], 400);
                }
                return redirect()->back()->with('error', 'Ce paiement est déjà rejeté.');
            }

            // S1.4 — Garde verrouillage de période comptable
            if ($block = $this->assertPeriodNotLocked($paiement)) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $block['message']], 403);
                }
                return redirect()->back()->with('error', $block['message']);
            }

            $paiement->update([
                'status' => 'rejeté',
                'date_validation' => now(),
                'validateur_id' => auth()->id(),
                'commentaire' => $request->input('motif_rejet')
            ]);

            // Envoyer notification à l'étudiant
            try {
                $notificationService = app(\App\Services\NotificationService::class);
                $notificationService->notifyPaiementRejete($paiement, auth()->user(), $request->input('motif_rejet'));

                // Envoyer notification aux parents
                $notificationService->notifyParentsPaiementRejete($paiement);
            } catch (\Exception $e) {
                Log::error('Erreur envoi notification paiement rejeté: ' . $e->getMessage());
            }

            // Désactiver les rappels pour ce paiement
            try {
                $reminder = \App\Models\NotificationReminder::where('remindable_type', 'App\Models\ESBTPPaiement')
                    ->where('remindable_id', $paiement->id)
                    ->first();
                if ($reminder) {
                    $reminder->deactivate();
                }
            } catch (\Exception $e) {
                Log::error('Erreur désactivation reminder paiement: ' . $e->getMessage());
            }

            // Si c'est une requête AJAX, retourner JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Paiement rejeté avec succès.',
                    'paiement_id' => $paiement->id,
                    'numero_recu' => $paiement->numero_recu
                ]);
            }

            return redirect()->back()->with('success', 'Paiement rejeté avec succès.');

        } catch (\Exception $e) {
            \Log::error('Erreur lors du rejet du paiement', [
                'paiement_id' => $id,
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ]);

            // Si c'est une requête AJAX, retourner JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors du rejet: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Erreur lors du rejet: ' . $e->getMessage());
        }
    }

    /**
     * Supprimer définitivement un paiement (réservé au superAdmin)
     */
    public function destroy(Request $request, ESBTPPaiement $paiement)
    {
        $user = $request->user();
        if (!$user || !$user->can('paiements.manage')) {
            abort(403, 'Cette action est réservée au super administrateur.');
        }

        // S1.4 — Garde verrouillage de période comptable (même superAdmin doit utiliser bypass_lock)
        if ($block = $this->assertPeriodNotLocked($paiement)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $block['message']], 403);
            }
            return redirect()->back()->with('error', $block['message']);
        }

        DB::beginTransaction();

        try {
            $paiementId = $paiement->id;
            $numeroRecu = $paiement->numero_recu;

            // Désactiver les éventuels rappels associés
            try {
                $reminder = \App\Models\NotificationReminder::where('remindable_type', ESBTPPaiement::class)
                    ->where('remindable_id', $paiementId)
                    ->first();
                if ($reminder) {
                    $reminder->deactivate();
                }
            } catch (\Exception $inner) {
                Log::warning('Impossible de désactiver le rappel du paiement avant suppression', [
                    'paiement_id' => $paiementId,
                    'error' => $inner->getMessage()
                ]);
            }

            $paiement->delete();

            DB::commit();

            $message = "Le paiement {$numeroRecu} a été supprimé définitivement.";

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }

            return redirect()->route('esbtp.paiements.index')->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erreur lors de la suppression définitive du paiement', [
                'paiement_id' => $paiement->id,
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    /**
     * Valider plusieurs paiements en une fois
     */
    public function bulkValider(Request $request)
    {
        $request->validate([
            'paiements' => 'required|array|min:1',
            'paiements.*' => 'exists:esbtp_paiements,id'
        ]);

        $successCount = 0;
        $errorCount = 0;
        $alreadyProcessed = 0;

        try {
            DB::beginTransaction();

            $selfBlocked = 0;
            foreach ($request->paiements as $id) {
                $paiement = ESBTPPaiement::find($id);

                if (!$paiement) {
                    $errorCount++;
                    continue;
                }

                // Vérifier si le paiement peut être validé
                if ($paiement->status === 'validé') {
                    $alreadyProcessed++;
                    continue;
                }

                if ($paiement->status === 'rejeté') {
                    $errorCount++;
                    continue;
                }

                // S1.1 — Garde anti-auto-validation (séparation des tâches anti-fraude)
                if ($this->assertNotSelfValidation($paiement) !== null) {
                    $selfBlocked++;
                    continue;
                }

                // Valider le paiement
                $paiement->update([
                    'status' => 'validé',
                    'date_validation' => now(),
                    'validateur_id' => auth()->id()
                ]);

                // Si c'est un paiement de reliquat, mettre à jour le reliquat
                if ($paiement->type_paiement === 'reliquat' && $paiement->reliquat_detail_id) {
                    $reliquat = \App\Models\ESBTPReliquatDetail::find($paiement->reliquat_detail_id);
                    if ($reliquat) {
                        $nouveauMontantRegle = $reliquat->montant_regle + $paiement->montant;
                        $nouveauSolde = $reliquat->montant_reliquat - $nouveauMontantRegle;

                        $reliquat->update([
                            'montant_regle' => $nouveauMontantRegle,
                            'statut' => $nouveauSolde <= 0 ? 'totalement_regle' : 'partiellement_regle',
                            'date_derniere_maj' => now()
                        ]);
                    }
                }

                $successCount++;
            }

            DB::commit();

            if ($successCount > 0) {
                app(\App\Services\GroupCacheInvalidator::class)->invalidate('paiement_bulk_validated');
            }

            // Construire le message de retour
            $message = '';
            if ($successCount > 0) {
                $message = "$successCount paiement(s) validé(s) avec succès.";
            }
            if ($alreadyProcessed > 0) {
                $message .= " $alreadyProcessed paiement(s) déjà validé(s).";
            }
            if ($selfBlocked > 0) {
                $message .= " $selfBlocked paiement(s) bloqué(s) — vous ne pouvez pas auto-valider vos propres saisies (séparation des tâches).";
            }
            if ($errorCount > 0) {
                $message .= " $errorCount paiement(s) n'ont pas pu être validés.";
            }

            // Si c'est une requête AJAX, retourner JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'successCount' => $successCount,
                    'alreadyProcessed' => $alreadyProcessed,
                    'selfBlocked' => $selfBlocked,
                    'errorCount' => $errorCount
                ]);
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Erreur lors de la validation groupée des paiements', [
                'paiements' => $request->paiements,
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ]);

            // Si c'est une requête AJAX, retourner JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la validation groupée: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Erreur lors de la validation groupée: ' . $e->getMessage());
        }
    }

    /**
     * S1.6 — Notifie les users habilités quand un paiement > seuil tenant est validé.
     *
     * Cible : users avec permission `comptabilite.notifications.high_amount`.
     * Seuil : `comptabilite.notify_high_amount_threshold` (default 5 000 000 FCFA).
     * Canaux : mail + database (cloche).
     *
     * Échec silencieux par design — la validation du paiement ne doit pas dépendre
     * du succès de la notification. Erreurs loggées en warning.
     */
    private function notifyHighAmountIfAny(\App\Models\ESBTPPaiement $paiement, ?\App\Models\User $validateur): void
    {
        try {
            $threshold = (int) \App\Helpers\SettingsHelper::get('comptabilite.notify_high_amount_threshold', 5000000);
            if ($threshold <= 0 || (float) $paiement->montant < $threshold) {
                return;
            }

            $recipients = \App\Models\User::permission('comptabilite.notifications.high_amount')->get();
            if ($recipients->isEmpty()) {
                return;
            }

            \Illuminate\Support\Facades\Notification::send(
                $recipients,
                new \App\Notifications\PaiementHighAmountValidatedNotification($paiement, $validateur, $threshold),
            );

            Log::info('[S1.6] Notification gros paiement envoyée', [
                'paiement_id' => $paiement->id,
                'montant' => $paiement->montant,
                'threshold' => $threshold,
                'recipients_count' => $recipients->count(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('[S1.6] Échec notification gros paiement (non bloquant)', [
                'paiement_id' => $paiement->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * S1.4 — Garde anti-modification rétroactive (verrouillage de période comptable).
     *
     * Une fois qu'un mois est clôturé (setting `comptabilite.period_locked_until`),
     * plus aucun paiement antérieur à cette date ne peut être modifié, supprimé ou rejeté.
     * Garantit la traçabilité comptable et la conformité OHADA.
     *
     * Bypass possible via permission `comptabilite.period.bypass_lock` (rare,
     * pour corrections exceptionnelles). superAdmin/serviceTechnique passent via Gate::before.
     *
     * @return array{message: string}|null Null si OK, ou ['message' => '...'] si bloqué.
     */
    private function assertPeriodNotLocked(\App\Models\ESBTPPaiement $paiement): ?array
    {
        $lockedUntil = \App\Helpers\SettingsHelper::get('comptabilite.period_locked_until');
        if (empty($lockedUntil)) {
            return null;
        }

        try {
            $lockDate = \Carbon\Carbon::parse($lockedUntil)->endOfDay();
        } catch (\Throwable $e) {
            return null; // Setting mal formaté, on n'applique pas de garde
        }

        // Date de référence du paiement : date_paiement (la vraie date métier) ou created_at fallback
        $paiementDate = $paiement->date_paiement
            ? \Carbon\Carbon::parse($paiement->date_paiement)
            : ($paiement->created_at ?: now());

        if ($paiementDate->gt($lockDate)) {
            return null; // Postérieur au verrouillage → modifiable
        }

        // Bypass autorisé (superAdmin via Gate::before *, ou perm explicite)
        if (auth()->user()?->can('comptabilite.period.bypass_lock')) {
            Log::warning('[S1.4] Bypass verrouillage période utilisé', [
                'paiement_id' => $paiement->id,
                'paiement_date' => $paiementDate->toDateString(),
                'period_locked_until' => $lockDate->toDateString(),
                'user_id' => auth()->id(),
            ]);
            return null;
        }

        return [
            'message' => sprintf(
                'Action refusée : le paiement du %s appartient à une période comptable verrouillée (jusqu\'au %s). Demandez à un comptable habilité de débloquer la période ou créez une écriture corrective sur la période courante.',
                $paiementDate->translatedFormat('d/m/Y'),
                $lockDate->translatedFormat('d/m/Y'),
            ),
        ];
    }

    /**
     * S1.5 — Annuler son propre paiement créé il y a < N minutes (anti-erreur caissier).
     *
     * Évite que le caissier qui s'est trompé (typo cash, mauvais étudiant) doive
     * appeler un comptable pour annuler. Soft delete + log + audit.
     *
     * Permission via Policy::cancelOwnRecent — vérifie auteur + statut + fenêtre temps.
     */
    public function cancelOwn(Request $request, ESBTPPaiement $paiement)
    {
        $this->authorize('cancelOwnRecent', $paiement);

        try {
            DB::beginTransaction();

            // Désactive les rappels associés (cohérence avec destroy())
            try {
                $reminder = \App\Models\NotificationReminder::where('remindable_type', 'App\Models\ESBTPPaiement')
                    ->where('remindable_id', $paiement->id)
                    ->first();
                if ($reminder) {
                    $reminder->deactivate();
                }
            } catch (\Exception $e) {
                Log::error('Erreur désactivation reminder paiement (cancel-own): ' . $e->getMessage());
            }

            $contextLog = [
                'paiement_id' => $paiement->id,
                'numero_recu' => $paiement->numero_recu,
                'inscription_id' => $paiement->inscription_id,
                'montant' => $paiement->montant,
                'created_by' => $paiement->created_by,
                'created_at' => $paiement->created_at?->toIso8601String(),
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now()->toIso8601String(),
            ];

            $paiement->delete(); // Soft delete (trait SoftDeletes)

            DB::commit();

            Log::info('[S1.5] Paiement annulé par son auteur (fenêtre 5min)', $contextLog);

            // Cache invalidation pour mettre à jour les KPIs
            try {
                app(\App\Services\GroupCacheInvalidator::class)->invalidate('paiement_cancelled');
            } catch (\Throwable $e) {
                // pas bloquant
            }

            $message = 'Paiement annulé. Vous pouvez en créer un nouveau si nécessaire.';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'paiement_id' => $paiement->id,
                ]);
            }

            return redirect()->route('esbtp.paiements.index')->with('success', $message);

        } catch (\Throwable $e) {
            DB::rollback();
            Log::error('Erreur cancelOwn paiement', [
                'paiement_id' => $paiement->id,
                'error' => $e->getMessage(),
            ]);

            $msg = 'Impossible d\'annuler ce paiement : ' . $e->getMessage();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->back()->with('error', $msg);
        }
    }

    /**
     * S1.1 — Garde anti-auto-validation (séparation des tâches anti-fraude).
     *
     * Refuse qu'un user valide un paiement qu'il a lui-même créé (créator == auth).
     * Bypass possible via permission `paiements.validate.self_override` (réservée
     * aux toutes petites écoles avec un seul user comptable, fortement déconseillé).
     *
     * superAdmin/serviceTechnique passent automatiquement (Gate::before * couverture).
     *
     * @return array{message: string}|null Null si OK, ou ['message' => '...'] si bloqué.
     */
    private function assertNotSelfValidation(\App\Models\ESBTPPaiement $paiement): ?array
    {
        $userId = auth()->id();
        $createdBy = $paiement->created_by ?? null;

        // Pas de created_by (legacy data pré-audit log) → pas de garde
        if (!$createdBy) {
            return null;
        }

        // Permission self_override (Gate::before couvre superAdmin/serviceTechnique)
        if (auth()->user()?->can('paiements.validate.self_override')) {
            return null;
        }

        // Auteur != validateur → OK (séparation respectée)
        if ((int) $createdBy !== (int) $userId) {
            return null;
        }

        // Bloqué : self-validation tentée sans override permission
        Log::warning('[S1.1] Tentative auto-validation paiement bloquée', [
            'paiement_id' => $paiement->id,
            'user_id' => $userId,
            'created_by' => $createdBy,
            'montant' => $paiement->montant,
        ]);

        return [
            'message' => 'Vous ne pouvez pas valider votre propre paiement (séparation des tâches anti-fraude). Demandez à un autre comptable.',
        ];
    }

    /**
     * Rejeter plusieurs paiements en une fois
     */
    public function bulkRejeter(Request $request)
    {
        $request->validate([
            'paiements' => 'required|array|min:1',
            'paiements.*' => 'exists:esbtp_paiements,id',
            'motif_rejet' => 'required|string|min:10|max:500',
        ], [
            'motif_rejet.required' => 'Le motif de rejet est obligatoire.',
            'motif_rejet.min' => 'Le motif de rejet doit faire au moins 10 caractères.',
            'motif_rejet.max' => 'Le motif de rejet ne peut pas dépasser 500 caractères.',
        ]);

        $successCount = 0;
        $errorCount = 0;
        $alreadyProcessed = 0;

        try {
            DB::beginTransaction();

            foreach ($request->paiements as $id) {
                $paiement = ESBTPPaiement::find($id);

                if (!$paiement) {
                    $errorCount++;
                    continue;
                }

                // Vérifier si le paiement peut être rejeté
                if ($paiement->status === 'validé') {
                    $errorCount++;
                    continue;
                }

                if ($paiement->status === 'rejeté') {
                    $alreadyProcessed++;
                    continue;
                }

                // Rejeter le paiement
                $paiement->update([
                    'status' => 'rejeté',
                    'date_validation' => now(),
                    'validateur_id' => auth()->id(),
                    'commentaire' => $request->input('motif_rejet')
                ]);

                $successCount++;
            }

            DB::commit();

            // Construire le message de retour
            $message = '';
            if ($successCount > 0) {
                $message = "$successCount paiement(s) rejeté(s) avec succès.";
            }
            if ($alreadyProcessed > 0) {
                $message .= " $alreadyProcessed paiement(s) déjà rejeté(s).";
            }
            if ($errorCount > 0) {
                $message .= " $errorCount paiement(s) n'ont pas pu être rejetés (déjà validés ou introuvables).";
            }

            // Si c'est une requête AJAX, retourner JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'successCount' => $successCount,
                    'alreadyProcessed' => $alreadyProcessed,
                    'errorCount' => $errorCount
                ]);
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Erreur lors du rejet groupé des paiements', [
                'paiements' => $request->paiements,
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ]);

            // Si c'est une requête AJAX, retourner JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors du rejet groupé: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Erreur lors du rejet groupé: ' . $e->getMessage());
        }
    }

    /**
     * Rafraîchir une ligne de paiement spécifique (AJAX pour mise à jour partielle)
     */
    public function refreshLigne(ESBTPPaiement $paiement)
    {
        try {
            // Charger toutes les relations nécessaires
            $paiement->load([
                'etudiant.user',
                'fraisCategory',
                'categorie',
                'inscription'
            ]);

            // Rendu de la partial ligne-paiement
            $html = view('esbtp.paiements.partials.ligne-paiement', [
                'paiement' => $paiement
            ])->render();

            \Log::info('Ligne paiement rafraîchie avec succès', [
                'paiement_id' => $paiement->id,
                'user_id' => auth()->id(),
                'status' => $paiement->status
            ]);

            return response()->json([
                'success' => true,
                'html' => $html,
                'paiement_id' => $paiement->id,
                'status' => $paiement->status
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur refreshLigne paiement: ' . $e->getMessage(), [
                'paiement_id' => $paiement->id,
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rafraîchissement de la ligne: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Endpoint de test pour déboguer les filtres et l'export
     */
    public function testFilters(Request $request)
    {
        $filters = [
            'search' => $request->input('search'),
            'status' => $request->input('status'),
            'date_debut' => $request->input('date_debut'),
            'date_fin' => $request->input('date_fin'),
        ];

        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        $query = ESBTPPaiement::query();

        // Appliquer les filtres
        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        if ($filters['date_debut']) {
            $query->whereDate('date_paiement', '>=', $filters['date_debut']);
        }

        if ($filters['date_fin']) {
            $query->whereDate('date_paiement', '<=', $filters['date_fin']);
        }

        if ($anneeEnCours) {
            $query->whereHas('inscription', function ($q) use ($anneeEnCours) {
                $q->where('annee_universitaire_id', $anneeEnCours->id);
            });
        }

        // Calculer les statistiques sur les paiements filtrés
        $stats = [
            'total' => (clone $query)->count(),
            'valides' => (clone $query)->where('status', 'validé')->count(),
            'en_attente' => (clone $query)->where('status', 'en_attente')->count(),
            'rejetes' => (clone $query)->where('status', 'rejeté')->count(),
            'montant_total' => (clone $query)->sum('montant') ?? 0,
            'montant_valide' => (clone $query)->where('status', 'validé')->sum('montant') ?? 0,
            'montant_en_attente' => (clone $query)->where('status', 'en_attente')->sum('montant') ?? 0,
        ];

        // Récupérer un échantillon
        $paiements = $query->with(['etudiant', 'inscription'])->limit(5)->get();

        return response()->json([
            'success' => true,
            'filters_received' => $filters,
            'annee_en_cours' => $anneeEnCours ? $anneeEnCours->name : 'Aucune',
            'statistics' => [
                'total_count' => $stats['total'],
                'valides' => $stats['valides'],
                'en_attente' => $stats['en_attente'],
                'rejetes' => $stats['rejetes'],
                'montant_total' => number_format($stats['montant_total'], 0, ',', ' ') . ' FCFA',
                'montant_valide' => number_format($stats['montant_valide'], 0, ',', ' ') . ' FCFA',
                'montant_en_attente' => number_format($stats['montant_en_attente'], 0, ',', ' ') . ' FCFA',
                'recovery_rate' => $stats['montant_total'] > 0
                    ? round(($stats['montant_valide'] / $stats['montant_total']) * 100, 1) . '%'
                    : '0%',
            ],
            'sample_data' => $paiements->map(function($p) {
                return [
                    'id' => $p->id,
                    'date_paiement' => $p->date_paiement ? $p->date_paiement->format('Y-m-d') : null,
                    'montant' => number_format($p->montant, 0, ',', ' ') . ' FCFA',
                    'status' => $p->status,
                    'etudiant' => $p->etudiant ? $p->etudiant->nom : 'N/A',
                    'matricule' => $p->etudiant ? $p->etudiant->matricule : 'N/A',
                ];
            }),
        ]);
    }

    /**
     * Exporter les paiements au format Excel (XLSX)
     */
    public function exportExcel(Request $request, FuzzyNameMatcher $matcher)
    {
        try {
            // Récupérer les données filtrées pour les stats
            $data = $this->filterService->preparePaiementListing($request, $matcher, [], microtime(true), 'ESBTPPaiementController@exportExcel');

            // Récupérer TOUS les paiements filtrés (sans pagination)
            $paiements = $this->filterService->getAllFilteredPaiements($request, $matcher);

            // Préparer les filtres pour l'export
            $filters = [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'date_debut' => $request->input('date_debut'),
                'date_fin' => $request->input('date_fin'),
            ];

            // Créer l'export
            $export = new \App\Exports\PaiementsExport($paiements, $data['stats'], $filters);

            // Générer le nom du fichier
            $filename = 'paiements_' . now()->format('Y-m-d_His') . '.xlsx';

            Log::info('Export Excel paiements généré', [
                'user_id' => auth()->id(),
                'total_paiements' => $paiements->count(),
                'filename' => $filename
            ]);

            return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);

        } catch (\Exception $e) {
            Log::error('Erreur export Excel paiements: ' . $e->getMessage(), [
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ]);

            return redirect()->back()->with('error', 'Erreur lors de l\'export Excel: ' . $e->getMessage());
        }
    }

    /**
     * Exporter les paiements au format SAARI (Sage Saari Ligne 100).
     * Format compatible import direct dans SAARI :
     * colonnes (vide) | cj | date | libelle | debit | credit | n°cmpte | t | Colonne1
     *
     * Onglet généré "BNI BKE" (peut être renommé pour BNI BABI ou autre via setting).
     *
     * @see app/Exports/PaiementsSaariExport.php
     */
    public function exportSaari(Request $request, FuzzyNameMatcher $matcher)
    {
        try {
            $paiements = $this->filterService->getAllFilteredPaiements($request, $matcher);

            $filters = [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'date_debut' => $request->input('date_debut'),
                'date_fin' => $request->input('date_fin'),
                'periode_label' => $this->buildPeriodeLabel($request),
            ];

            $export = new \App\Exports\PaiementsSaariExport($paiements, $filters);
            $filename = 'export_saari_' . now()->format('Y-m-d_His') . '.xlsx';

            Log::info('Export SAARI paiements généré', [
                'user_id' => auth()->id(),
                'total_paiements' => $paiements->count(),
                'filename' => $filename,
            ]);

            return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);
        } catch (\Exception $e) {
            Log::error('Erreur export SAARI paiements: ' . $e->getMessage(), [
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);

            return redirect()->back()->with('error', 'Erreur lors de l\'export SAARI: ' . $e->getMessage());
        }
    }

    private function buildPeriodeLabel(Request $request): string
    {
        $d = $request->input('date_debut');
        $f = $request->input('date_fin');
        if ($d && $f) {
            return 'du ' . $d . ' au ' . $f;
        }
        if ($d) {
            return 'depuis le ' . $d;
        }
        if ($f) {
            return 'jusqu\'au ' . $f;
        }
        return '';
    }

    /**
     * Exporter les paiements au format CSV
     */
    public function exportCsv(Request $request, FuzzyNameMatcher $matcher)
    {
        try {
            // Récupérer les données filtrées pour les stats
            $data = $this->filterService->preparePaiementListing($request, $matcher, [], microtime(true), 'ESBTPPaiementController@exportCsv');

            // Récupérer TOUS les paiements filtrés (sans pagination)
            $paiements = $this->filterService->getAllFilteredPaiements($request, $matcher);

            // Préparer les filtres
            $filters = [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'date_debut' => $request->input('date_debut'),
                'date_fin' => $request->input('date_fin'),
            ];

            // Créer l'export
            $export = new \App\Exports\PaiementsExport($paiements, $data['stats'], $filters);

            // Générer le nom du fichier
            $filename = 'paiements_' . now()->format('Y-m-d_His') . '.csv';

            Log::info('Export CSV paiements généré', [
                'user_id' => auth()->id(),
                'total_paiements' => $paiements->count(),
                'filename' => $filename
            ]);

            return \Maatwebsite\Excel\Facades\Excel::download($export, $filename, \Maatwebsite\Excel\Excel::CSV, [
                'Content-Type' => 'text/csv',
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur export CSV paiements: ' . $e->getMessage(), [
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ]);

            return redirect()->back()->with('error', 'Erreur lors de l\'export CSV: ' . $e->getMessage());
        }
    }

    /**
     * Exporter les paiements au format PDF (téléchargement)
     */
    public function exportPdf(Request $request, FuzzyNameMatcher $matcher)
    {
        try {
            [$pdf, $filename] = $this->buildExportPdf($request, $matcher);
            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('Erreur export PDF paiements: ' . $e->getMessage(), [
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ]);
            return redirect()->back()->with('error', 'Erreur lors de l\'export PDF: ' . $e->getMessage());
        }
    }

    /**
     * Aperçu PDF des paiements (inline, nouvelle tab du navigateur).
     * Phase 9.5 — preview universel.
     */
    public function exportPdfPreview(Request $request, FuzzyNameMatcher $matcher)
    {
        try {
            [$pdf, $filename] = $this->buildExportPdf($request, $matcher);
            return new \Illuminate\Http\Response(
                $pdf->output(),
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $filename . '"',
                    'X-Robots-Tag' => 'noindex, nofollow',
                ]
            );
        } catch (\Exception $e) {
            Log::error('Erreur aperçu PDF paiements: ' . $e->getMessage(), [
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ]);
            return redirect()->back()->with('error', 'Erreur lors de l\'aperçu PDF: ' . $e->getMessage());
        }
    }

    /**
     * Construit l'objet DomPDF pour l'export paiements (download + preview
     * partagent la même logique). Retourne [Pdf, filename].
     *
     * @return array{0: \Barryvdh\DomPDF\PDF, 1: string}
     */
    private function buildExportPdf(Request $request, FuzzyNameMatcher $matcher): array
    {
        $data = $this->filterService->preparePaiementListing($request, $matcher, [], microtime(true), 'ESBTPPaiementController@buildExportPdf');
        $paiements = $this->filterService->getAllFilteredPaiements($request, $matcher);

        // Eager load createdBy si on va afficher la colonne (évite N+1)
        $paiements->loadMissing('createdBy:id,name');

        $filters = [
            'search' => $request->input('search'),
            'status' => $request->input('status'),
            'date_debut' => $request->input('date_debut'),
            'date_fin' => $request->input('date_fin'),
        ];

        $settings = $this->getReceiptSettings();
        $schoolInfo = \App\Helpers\SettingsHelper::getSchoolInfo();
        $pdfCfg = \App\Helpers\SettingsHelper::getPdfSettings();

        $etablissement = [
            'nom' => $schoolInfo['name'],
            'adresse' => $schoolInfo['address'],
            'telephone' => $schoolInfo['phone'],
            'email' => $schoolInfo['email'],
            'logo' => $schoolInfo['logo'],
        ];

        // Logique permissions :
        // - Si user voit TOUS les paiements (paiements.view) → colonne 'Encaissé par' sur chaque ligne
        // - Si user voit SEULEMENT ses propres paiements (paiements.view_own) → pas de colonne (redondant
        //   car c'est lui partout) MAIS son rôle + nom dans le header pour identifier le document.
        //   Format : "{Rôle français} : {Nom}" — ex "Caissier : N'GUESSAN Marcel".
        //   Supporte les rôles custom créés par l'admin tenant (Lot 19).
        $user = auth()->user();
        $canViewAll = $user && $user->can('paiements.view');
        $showCreatorColumn = $canViewAll;
        $creatorHeader = null;
        if (!$canViewAll && $user && $user->can('paiements.view_own')) {
            $roleName = optional($user->roles->first())->name;
            $roleLabels = [
                'superAdmin' => 'Administrateur',
                'secretaire' => 'Secrétaire',
                'comptable' => 'Comptable',
                'caissier' => 'Caissier',
                'coordinateur' => 'Coordinateur',
                'enseignant' => 'Enseignant',
                'etudiant' => 'Étudiant',
                'serviceTechnique' => 'Service Technique',
            ];
            $displayRole = $roleName ? ($roleLabels[$roleName] ?? ucfirst($roleName)) : null;
            $creatorHeader = $displayRole ? ($displayRole . ' : ' . $user->name) : $user->name;
        }

        Log::info('PDF paiements généré', [
            'user_id' => auth()->id(),
            'total_paiements' => $paiements->count(),
            'show_creator_column' => $showCreatorColumn,
            'creator_header' => $creatorHeader,
        ]);

        $pdf = PDF::loadView('esbtp.paiements.export-pdf', [
            'paiements' => $paiements,
            'stats' => $data['stats'],
            'filters' => $filters,
            'settings' => $settings,
            'etablissement' => $etablissement,
            'pdfCfg' => $pdfCfg,
            'dateExport' => now(),
            'showCreatorColumn' => $showCreatorColumn,
            'creatorHeader' => $creatorHeader,
        ]);

        $filename = 'paiements_' . now()->format('Y-m-d_His') . '.pdf';

        return [$pdf, $filename];
    }
}
