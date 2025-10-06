<?php

namespace App\Http\Controllers;

use App\Models\ESBTPPaiement;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPAnneeUniversitaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ESBTPPaiementController extends Controller
{
    /**
     * Constructeur du contrôleur.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:paiements.view', ['only' => ['index', 'show', 'paiementsEtudiant']]);
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
    public function index(Request $request)
    {
        // Récupérer les paramètres de filtrage
        $search = $request->input('search');
        $status = $request->input('status');
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');

        // Toujours utiliser l'année en cours
        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $anneeId = $anneeEnCours ? $anneeEnCours->id : null;

        // Construire la requête avec toutes les relations nécessaires
        $query = ESBTPPaiement::with([
            'etudiant.user', 
            'inscription.anneeUniversitaire', 
            'inscription.filiere',
            'inscription.niveauEtude', 
            'validatedBy', 
            'fraisCategory',
            'categorie' // Ancien système pour compatibilité
        ])->orderBy('created_at', 'desc');

        // Appliquer les filtres
        if ($search) {
            $query->whereHas('etudiant', function ($q) use ($search) {
                $q->whereHas('user', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhere('matricule', 'like', "%{$search}%");
            })
            ->orWhere('numero_recu', 'like', "%{$search}%")
            ->orWhere('reference_paiement', 'like', "%{$search}%");
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($dateDebut) {
            $query->whereDate('date_paiement', '>=', $dateDebut);
        }

        if ($dateFin) {
            $query->whereDate('date_paiement', '<=', $dateFin);
        }

        // Toujours filtrer par l'année en cours
        if ($anneeId) {
            $query->whereHas('inscription', function ($q) use ($anneeId) {
                $q->where('annee_universitaire_id', $anneeId);
            });
        } else {
            // Par défaut, afficher les paiements de l'année en cours
            $query->anneeEnCours();
        }

        // Paginer les résultats
        $paiements = $query->paginate(15);

        // Calculer les vraies statistiques basées sur les inscriptions et leurs frais attendus
        $categoriesStats = $this->calculateCategoryStats(null);
        
        // Calculer le total attendu et payé à partir des catégories
        $totalAttendu = $categoriesStats['academic_total'] + $categoriesStats['service_total'] + $categoriesStats['administrative_total'];
        $totalPaye = $categoriesStats['academic_paid'] + $categoriesStats['service_paid'] + $categoriesStats['administrative_paid'];
        $totalEnAttente = $categoriesStats['academic_pending'] + $categoriesStats['service_pending'] + $categoriesStats['administrative_pending'];
        
        $stats = [
            // Statistiques générales basées sur les vraies données
            'total' => $query->count(),
            'montant_total' => $totalAttendu,
            'valides' => $query->where('status', 'validé')->count(),
            'montant_valide' => $totalPaye,
            'en_attente' => $query->where('status', 'en_attente')->count(),
            'montant_en_attente' => $totalEnAttente,
        ];
        
        // Ajouter les statistiques par catégorie
        $stats = array_merge($stats, $categoriesStats);
        
        // Calcul du taux de recouvrement global corrigé
        $stats['recovery_rate'] = $totalAttendu > 0 ?
            round(($totalPaye / $totalAttendu) * 100, 1) : 0;

        // Calculer les statistiques spécifiques aux reliquats pour affichage séparé
        $inscriptions = \App\Models\ESBTPInscription::where('annee_universitaire_id', $anneeId)
            ->whereIn('status', ['active', 'en_attente', 'validée'])
            ->get();
        $reliquatsStats = $this->calculateReliquatsStats($inscriptions);
        $stats['reliquats_total'] = $reliquatsStats['academic_pending'] + $reliquatsStats['service_pending'] + $reliquatsStats['administrative_pending'];

        return view('esbtp.paiements.index', compact('paiements', 'stats'));
    }

    /**
     * Calcule les vraies statistiques basées sur les inscriptions et leurs frais attendus.
     */
    private function calculateCategoryStats($baseQuery = null)
    {
        // Obtenir l'année en cours pour les calculs
        $anneeEnCours = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
        
        // Si aucune année courante, prendre la plus récente
        if (!$anneeEnCours) {
            $anneeEnCours = \App\Models\ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->first();
        }
        
        if (!$anneeEnCours) {
            return $this->getEmptyStats();
        }

        // Récupérer toutes les inscriptions de l'année en cours (même filtrage que suivi-categories)
        $inscriptions = \App\Models\ESBTPInscription::with(['filiere', 'niveauEtude'])
            ->where('annee_universitaire_id', $anneeEnCours->id)
            ->whereIn('status', ['active', 'en_attente', 'validée'])
            ->get();

        $stats = [
            'academic_paid' => 0,
            'service_paid' => 0,
            'administrative_paid' => 0,
            'academic_pending' => 0,
            'service_pending' => 0,
            'administrative_pending' => 0,
            'academic_total' => 0,
            'service_total' => 0,
            'administrative_total' => 0,
        ];

        // Pour chaque inscription, calculer les montants attendus et payés
        foreach ($inscriptions as $inscription) {
            $fraisStats = $this->calculateFraisForInscription($inscription);
            
            foreach (['academic', 'service', 'administrative'] as $type) {
                $stats[$type . '_total'] += $fraisStats[$type]['expected'];
                $stats[$type . '_paid'] += $fraisStats[$type]['paid'];
                $stats[$type . '_pending'] += $fraisStats[$type]['expected'] - $fraisStats[$type]['paid'];
            }
        }

        // S'assurer que les pending ne sont jamais négatifs
        foreach (['academic', 'service', 'administrative'] as $type) {
            $stats[$type . '_pending'] = max(0, $stats[$type . '_pending']);
        }

        // Ajouter les reliquats aux montants en attente
        $reliquatsStats = $this->calculateReliquatsStats($inscriptions);
        foreach (['academic', 'service', 'administrative'] as $type) {
            $stats[$type . '_pending'] += $reliquatsStats[$type . '_pending'];
            $stats[$type . '_total'] += $reliquatsStats[$type . '_total'];
        }

        return $stats;
    }

    /**
     * Calcule les frais attendus et payés pour une inscription donnée.
     */
    private function calculateFraisForInscription($inscription)
    {
        $fraisStats = [
            'academic' => ['expected' => 0, 'paid' => 0],
            'service' => ['expected' => 0, 'paid' => 0],
            'administrative' => ['expected' => 0, 'paid' => 0],
        ];

        // Récupérer toutes les catégories de frais actives
        $categories = \App\Models\ESBTPFraisCategory::where('is_active', true)->get();

        foreach ($categories as $category) {
            $categoryType = $category->category_type ?? 'academic';
            $expectedAmount = 0;

            // Prioriser toujours la souscription individuelle (obligatoire ou optionnel)
            $subscription = \App\Models\ESBTPFraisSubscription::where('inscription_id', $inscription->id)
                ->where('frais_category_id', $category->id)
                ->where('is_active', true)
                ->first();

            if ($subscription) {
                $expectedAmount = $subscription->amount;
            } elseif ($category->is_mandatory) {
                // Frais obligatoire : fallback sur la configuration si pas de souscription
                $configuration = \App\Models\ESBTPFraisConfiguration::where('frais_category_id', $category->id)
                    ->where('filiere_id', $inscription->filiere_id)
                    ->where('niveau_id', $inscription->niveau_id)
                    ->where('is_active', true)
                    ->where('is_valid', true)
                    ->first();

                if ($configuration) {
                    $expectedAmount = $configuration->getMontantByStatus($inscription->affectation_status ?? 'affecté');
                } else {
                    // Utiliser le montant par défaut si pas de configuration spécifique
                    $expectedAmount = $category->default_amount ?? 0;
                }
            }

            // Si un montant est attendu, l'ajouter aux stats
            if ($expectedAmount > 0) {
                $fraisStats[$categoryType]['expected'] += $expectedAmount;

                // Calculer le montant payé pour cette catégorie (exclure les reliquats)
                $paidAmount = ESBTPPaiement::where('inscription_id', $inscription->id)
                    ->where('frais_category_id', $category->id)
                    ->where('status', 'validé')
                    ->where(function($query) {
                        $query->where('type_paiement', '!=', 'reliquat')
                              ->orWhereNull('type_paiement');
                    })
                    ->sum('montant');

                $fraisStats[$categoryType]['paid'] += $paidAmount;
            }
        }

        return $fraisStats;
    }

    /**
     * Calcule les statistiques des reliquats pour les inscriptions données.
     */
    private function calculateReliquatsStats($inscriptions)
    {
        $reliquatsStats = [
            'academic_pending' => 0,
            'service_pending' => 0,
            'administrative_pending' => 0,
            'academic_total' => 0,
            'service_total' => 0,
            'administrative_total' => 0,
        ];

        // Récupérer tous les reliquats entrants pour les inscriptions données
        $inscriptionIds = $inscriptions->pluck('id');

        $reliquats = \App\Models\ESBTPReliquatDetail::with([
            'fraisSubscription.fraisCategory'
        ])
        ->whereIn('inscription_destination_id', $inscriptionIds)
        ->where('statut', '!=', 'totalement_regle')  // Seulement les reliquats non soldés
        ->get();

        foreach ($reliquats as $reliquat) {
            if ($reliquat->fraisSubscription && $reliquat->fraisSubscription->fraisCategory) {
                $category = $reliquat->fraisSubscription->fraisCategory;
                $categoryType = $category->category_type ?? 'academic';
                $montantRestant = $reliquat->solde_restant;

                if ($montantRestant > 0) {
                    $reliquatsStats[$categoryType . '_pending'] += $montantRestant;
                    $reliquatsStats[$categoryType . '_total'] += $montantRestant;
                }
            }
        }

        return $reliquatsStats;
    }

    /**
     * Retourne des stats vides en cas de problème.
     */
    private function getEmptyStats()
    {
        return [
            'academic_paid' => 0,
            'service_paid' => 0,
            'administrative_paid' => 0,
            'academic_pending' => 0,
            'service_pending' => 0,
            'administrative_pending' => 0,
            'academic_total' => 0,
            'service_total' => 0,
            'administrative_total' => 0,
        ];
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

        return view('esbtp.paiements.create', compact('etudiant', 'inscription', 'anneeEnCours'));
    }

    /**
     * Enregistre un nouveau paiement.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Valider les données du formulaire
        $validated = $request->validate([
            'inscription_id' => 'required|exists:esbtp_inscriptions,id',
            'etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'frais_category_id' => 'required|exists:esbtp_frais_categories,id',
            'montant' => 'required|numeric|min:0',
            'date_paiement' => 'required|date',
            'mode_paiement' => 'required|string',
            'reference_paiement' => 'nullable|string',
            'tranche' => 'nullable|string',
            'commentaire' => 'nullable|string',
        ]);

        // Vérifier que l'étudiant correspond à l'inscription
        $inscription = ESBTPInscription::findOrFail($validated['inscription_id']);
        if ($inscription->etudiant_id != $validated['etudiant_id']) {
            return redirect()->back()->withErrors(['etudiant_id' => 'L\'étudiant ne correspond pas à l\'inscription sélectionnée.'])->withInput();
        }

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

            // Envoyer notification aux super-admins si le paiement est en attente
            if ($paiement->status === 'en_attente') {
                try {
                    $notificationService = app(\App\Services\NotificationService::class);
                    $notificationService->notifyPaiementCreated($paiement, auth()->user());
                } catch (\Exception $e) {
                    Log::error('Erreur envoi notification paiement créé: ' . $e->getMessage());
                }
            }

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
        $paiement = ESBTPPaiement::with([
            'etudiant.user',
            'inscription.anneeUniversitaire',
            'inscription.filiere',
            'inscription.niveauEtude',
            'validatedBy',
            'createdBy',
            'updatedBy'
        ])->findOrFail($id);

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
        // Vérifier que l'utilisateur est superadmin
        if (!auth()->user()->hasRole('superAdmin')) {
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

        return view('esbtp.paiements.edit', compact('paiement'));
    }

    /**
     * Met à jour un paiement existant.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Vérifier que l'utilisateur est superadmin
        if (!auth()->user()->hasRole('superAdmin')) {
            return redirect()->route('esbtp.paiements.show', $id)
                ->with('error', 'Seuls les super-administrateurs peuvent modifier les paiements.');
        }

        $paiement = ESBTPPaiement::findOrFail($id);

        // Vérifier si le paiement peut être modifié
        if ($paiement->status === 'validé') {
            return redirect()->route('esbtp.paiements.show', $paiement->id)
                ->with('error', 'Ce paiement a déjà été validé et ne peut plus être modifié.');
        }

        // Valider les données du formulaire
        $validated = $request->validate([
            'montant' => 'required|numeric|min:0',
            'date_paiement' => 'required|date',
            'mode_paiement' => 'required|string',
            'reference_paiement' => 'nullable|string',
            'tranche' => 'nullable|string',
            'motif' => 'required|string',
            'commentaire' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Mettre à jour le paiement
            $paiement->fill($validated);
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
            'validatedBy'
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
    public function genererRecu($id)
    {
        $paiement = ESBTPPaiement::with([
            'etudiant.user',
            'inscription.anneeUniversitaire',
            'inscription.filiere',
            'inscription.niveauEtude',
            'validatedBy'
        ])->findOrFail($id);

        // Récupérer les paramètres depuis les settings comme pour les bulletins
        $settings = $this->getReceiptSettings();

        // Générer le PDF avec les settings
        $pdf = PDF::loadView('esbtp.paiements.recu', compact('paiement', 'settings'));

        // Définir le nom du fichier
        $filename = 'Recu_' . $paiement->numero_recu . '.pdf';

        // Retourner le PDF pour téléchargement
        return $pdf->download($filename);
    }

    /**
     * Récupère les paramètres pour les reçus depuis les settings.
     */
    public function getReceiptSettings()
    {
        $settings = [
            'school_name' => \App\Helpers\SettingsHelper::get('school_name', 'Ecole Spéciale du Bâtiment et des Travaux Publics'),
            'school_address' => \App\Helpers\SettingsHelper::get('school_address', 'BP 2541 Yamoussoukro'),
            'school_phone' => \App\Helpers\SettingsHelper::get('school_phone', '30 64 39 93'),
            'school_email' => \App\Helpers\SettingsHelper::get('school_email', 'esbtp@aviso.ci'),
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
     * Affiche le suivi des paiements par catégorie de frais.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function suiviCategories(Request $request)
    {
        // Récupérer les paramètres de filtrage
        $filiereId = $request->input('filiere_id');
        $niveauId = $request->input('niveau_id');
        $anneeId = $request->input('annee_id');
        $categoryId = $request->input('category_id');

        // Récupérer les années universitaires pour le filtre
        $annees = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();
        $filieres = \App\Models\ESBTPFiliere::where('is_active', true)->get();
        $niveaux = \App\Models\ESBTPNiveauEtude::where('is_active', true)->get();
        $categories = \App\Models\ESBTPFraisCategory::where('is_active', true)->get();

        // Année par défaut (année en cours)
        if (!$anneeId) {
            $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
            $anneeId = $anneeEnCours ? $anneeEnCours->id : null;
        }

        // Construire la requête pour les inscriptions actives avec toutes les relations nécessaires
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

        // OPTIMISATION: Pré-charger toutes les données nécessaires en une seule fois
        $inscriptionIds = $inscriptions->pluck('id')->toArray();

        // Pré-charger toutes les configurations de frais
        $configurations = collect();
        if (!empty($inscriptions)) {
            $configurations = \App\Models\ESBTPFraisConfiguration::where('is_active', true)
                ->whereIn('frais_category_id', $categories->pluck('id'))
                ->get()
                ->groupBy(function($config) {
                    return $config->frais_category_id . '_' . $config->filiere_id . '_' . $config->niveau_id;
                });
        }

        // Pré-charger toutes les souscriptions
        $subscriptions = collect();
        if (!empty($inscriptionIds)) {
            $subscriptions = \App\Models\ESBTPFraisSubscription::where('is_active', true)
                ->whereIn('inscription_id', $inscriptionIds)
                ->get()
                ->groupBy('inscription_id');
        }

        // Pré-charger tous les paiements validés
        $paiements = collect();
        if (!empty($inscriptionIds)) {
            $paiements = ESBTPPaiement::where('status', 'validé')
                ->whereIn('inscription_id', $inscriptionIds)
                ->where(function($query) {
                    $query->where('type_paiement', '!=', 'reliquat')
                          ->orWhereNull('type_paiement');
                })
                ->get()
                ->groupBy(function($paiement) {
                    return $paiement->inscription_id . '_' . $paiement->frais_category_id;
                });
        }

        // Si une catégorie spécifique est sélectionnée, analyser en détail
        $detailsCategorie = null;
        if ($categoryId) {
            $category = \App\Models\ESBTPFraisCategory::find($categoryId);
            if ($category) {
                $detailsCategorie = $this->analyserCategorieDetailleOptimisee($category, $inscriptions, $configurations, $subscriptions, $paiements);
            }
        }

        // Statistiques globales par catégorie - version optimisée
        $statistiquesCategories = $this->calculerStatistiquesCategoriesOptimisees($inscriptions, $categories, $configurations, $subscriptions, $paiements);

        // Vue d'ensemble des étudiants par statut de paiement - version optimisée
        // Si un filtre par catégorie est appliqué, les KPIs doivent refléter seulement cette catégorie
        $categoriesForKPI = $categoryId ? $categories->where('id', $categoryId) : $categories;
        $vueEnsemble = $this->calculerVueEnsembleOptimisee($inscriptions, $categoriesForKPI, $configurations, $subscriptions, $paiements);

        return view('esbtp.paiements.suivi-categories', compact(
            'inscriptions',
            'annees',
            'filieres',
            'niveaux',
            'categories',
            'statistiquesCategories',
            'vueEnsemble',
            'detailsCategorie',
            'anneeId',
            'filiereId',
            'niveauId',
            'categoryId'
        ));
    }

    /**
     * Analyser une catégorie en détail
     */
    private function analyserCategorieDetaille($category, $inscriptions)
    {
        $details = [
            'category' => $category,
            'etudiants_a_jour' => collect(),
            'etudiants_en_retard' => collect(),
            'etudiants_non_payes' => collect(),
            'montant_total_attendu' => 0,
            'montant_total_recu' => 0,
        ];

        foreach ($inscriptions as $inscription) {
            // Vérifier si l'étudiant est concerné par ce frais
            $estConcerne = false;
            $montantAttendu = 0;

            if ($category->is_mandatory) {
                // Frais obligatoire : tous les étudiants sont concernés
                $estConcerne = true;
                $configuration = \App\Models\ESBTPFraisConfiguration::where('frais_category_id', $category->id)
                    ->where('filiere_id', $inscription->filiere_id)
                    ->where('niveau_id', $inscription->niveau_id)
                    ->where('is_active', true)
                    ->first();
                $montantAttendu = $configuration ? $configuration->getMontantByStatus($inscription->affectation_status ?? 'affecté') : $category->default_amount;
            } else {
                // Service optionnel : vérifier s'il y a une souscription active
                $subscription = \App\Models\ESBTPFraisSubscription::where('inscription_id', $inscription->id)
                    ->where('frais_category_id', $category->id)
                    ->where('is_active', true)
                    ->first();
                
                if ($subscription) {
                    $estConcerne = true;
                    $montantAttendu = $subscription->amount;
                }
            }

            // Traiter seulement les étudiants concernés
            if ($estConcerne) {
                $details['montant_total_attendu'] += $montantAttendu;

                // Vérifier les paiements de l'étudiant pour cette catégorie
                $paiements = ESBTPPaiement::where('inscription_id', $inscription->id)
                    ->where('frais_category_id', $category->id)
                    ->where('status', 'validé')
                    ->get();

                $montantPaye = $paiements->sum('montant');
                $details['montant_total_recu'] += $montantPaye;

                $statutEtudiant = [
                    'inscription' => $inscription,
                    'montant_attendu' => $montantAttendu,
                    'montant_paye' => $montantPaye,
                    'solde' => $montantAttendu - $montantPaye,
                    'pourcentage' => $montantAttendu > 0 ? round(($montantPaye / $montantAttendu) * 100, 1) : 0,
                    'derniers_paiements' => $paiements->sortByDesc('date_paiement')->take(3),
                ];

                // Catégoriser l'étudiant
                if ($montantPaye >= $montantAttendu) {
                    $details['etudiants_a_jour']->push($statutEtudiant);
                } elseif ($montantPaye > 0) {
                    $details['etudiants_en_retard']->push($statutEtudiant);
                } else {
                    $details['etudiants_non_payes']->push($statutEtudiant);
                }
            }
        }

        return $details;
    }

    /**
     * Calculer les statistiques par catégorie
     */
    private function calculerStatistiquesCategories($inscriptions)
    {
        $categories = \App\Models\ESBTPFraisCategory::where('is_active', true)->get();
        $statistiques = [];

        foreach ($categories as $category) {
            $stats = [
                'category' => $category,
                'total_etudiants' => $inscriptions->count(),
                'etudiants_concernes' => 0, // Nouveaux: étudiants concernés par ce frais
                'etudiants_a_jour' => 0,
                'etudiants_en_retard' => 0,
                'etudiants_non_payes' => 0,
                'montant_total_attendu' => 0,
                'montant_total_recu' => 0,
                'taux_recouvrement' => 0,
            ];

            foreach ($inscriptions as $inscription) {
                // Vérifier si l'étudiant est concerné par ce frais
                $estConcerne = false;
                $montantAttendu = 0;

                if ($category->is_mandatory) {
                    // Frais obligatoire : tous les étudiants sont concernés
                    $estConcerne = true;
                    $rule = \App\Models\ESBTPFraisConfiguration::where('frais_category_id', $category->id)
                        ->where('filiere_id', $inscription->filiere_id)
                        ->where('niveau_id', $inscription->niveau_id)
                        ->first();
                    $montantAttendu = $rule ? $rule->getMontantByStatus($inscription->affectation_status ?? 'affecté') : $category->default_amount;
                } else {
                    // Service optionnel : vérifier s'il y a une souscription active
                    $subscription = \App\Models\ESBTPFraisSubscription::where('inscription_id', $inscription->id)
                        ->where('frais_category_id', $category->id)
                        ->where('is_active', true)
                        ->first();
                    
                    if ($subscription) {
                        $estConcerne = true;
                        $montantAttendu = $subscription->amount;
                    }
                }

                // Traiter seulement les étudiants concernés
                if ($estConcerne) {
                    $stats['etudiants_concernes']++;
                    $stats['montant_total_attendu'] += $montantAttendu;

                    // Paiements de l'étudiant pour cette catégorie
                    $montantPaye = ESBTPPaiement::where('inscription_id', $inscription->id)
                        ->where('frais_category_id', $category->id)
                        ->where('status', 'validé')
                        ->sum('montant');

                    $stats['montant_total_recu'] += $montantPaye;

                    // Catégorisation
                    if ($montantPaye >= $montantAttendu) {
                        $stats['etudiants_a_jour']++;
                    } elseif ($montantPaye > 0) {
                        $stats['etudiants_en_retard']++;
                    } else {
                        $stats['etudiants_non_payes']++;
                    }
                }
            }

            // Calcul du taux de recouvrement basé sur les montants attendus réels
            $stats['taux_recouvrement'] = $stats['montant_total_attendu'] > 0 
                ? round(($stats['montant_total_recu'] / $stats['montant_total_attendu']) * 100, 1) 
                : 0;

            $statistiques[] = $stats;
        }

        return collect($statistiques);
    }

    /**
     * Calculer la vue d'ensemble globale
     */
    private function calculerVueEnsemble($inscriptions)
    {
        $totalEtudiants = $inscriptions->count();
        $etudiantsEnRegle = 0;
        $etudiantsEnRetard = 0;
        $etudiantsNonPayes = 0;
        $montantTotalAttendu = 0;
        $montantTotalRecu = 0;

        $categories = \App\Models\ESBTPFraisCategory::where('is_active', true)->get();

        foreach ($inscriptions as $inscription) {
            $etudiantEnRegle = true;
            $etudiantAPayeQuelqueChose = false;
            $montantEtudiantAttendu = 0;
            $montantEtudiantPaye = 0;

            foreach ($categories as $category) {
                // Vérifier si l'étudiant est concerné par ce frais
                $estConcerne = false;
                $montantAttendu = 0;

                if ($category->is_mandatory) {
                    // Frais obligatoire : tous les étudiants sont concernés
                    $estConcerne = true;
                    $rule = \App\Models\ESBTPFraisConfiguration::where('frais_category_id', $category->id)
                        ->where('filiere_id', $inscription->filiere_id)
                        ->where('niveau_id', $inscription->niveau_id)
                        ->first();
                    $montantAttendu = $rule ? $rule->getMontantByStatus($inscription->affectation_status ?? 'affecté') : $category->default_amount;
                } else {
                    // Service optionnel : vérifier s'il y a une souscription active
                    $subscription = \App\Models\ESBTPFraisSubscription::where('inscription_id', $inscription->id)
                        ->where('frais_category_id', $category->id)
                        ->where('is_active', true)
                        ->first();
                    
                    if ($subscription) {
                        $estConcerne = true;
                        $montantAttendu = $subscription->amount;
                    }
                }

                // Traiter seulement si l'étudiant est concerné
                if ($estConcerne) {
                    $montantEtudiantAttendu += $montantAttendu;
                    $montantTotalAttendu += $montantAttendu;

                    // Paiements de l'étudiant
                    $montantPaye = ESBTPPaiement::where('inscription_id', $inscription->id)
                        ->where('frais_category_id', $category->id)
                        ->where('status', 'validé')
                        ->sum('montant');

                    $montantEtudiantPaye += $montantPaye;
                    $montantTotalRecu += $montantPaye;

                    if ($montantPaye < $montantAttendu) {
                        $etudiantEnRegle = false;
                    }
                    
                    if ($montantPaye > 0) {
                        $etudiantAPayeQuelqueChose = true;
                    }
                }
            }

            // Catégorisation globale de l'étudiant (seulement s'il a des frais attendus)
            if ($montantEtudiantAttendu > 0) {
                if ($etudiantEnRegle) {
                    $etudiantsEnRegle++;
                } elseif ($etudiantAPayeQuelqueChose) {
                    $etudiantsEnRetard++;
                } else {
                    $etudiantsNonPayes++;
                }
            }
        }

        return [
            'total_etudiants' => $totalEtudiants,
            'etudiants_en_regle' => $etudiantsEnRegle,
            'etudiants_en_retard' => $etudiantsEnRetard,
            'etudiants_non_payes' => $etudiantsNonPayes,
            'montant_total_attendu' => $montantTotalAttendu,
            'montant_total_recu' => $montantTotalRecu,
            'taux_recouvrement_global' => $montantTotalAttendu > 0 
                ? round(($montantTotalRecu / $montantTotalAttendu) * 100, 1) 
                : 0,
        ];
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
     * Version optimisée de analyserCategorieDetaille - évite les requêtes N+1
     */
    private function analyserCategorieDetailleOptimisee($category, $inscriptions, $configurations, $subscriptions, $paiements)
    {
        $details = [
            'category' => $category,
            'etudiants_a_jour' => collect(),
            'etudiants_en_retard' => collect(),
            'etudiants_non_payes' => collect(),
            'montant_total_attendu' => 0,
            'montant_total_recu' => 0,
        ];

        foreach ($inscriptions as $inscription) {
            // Vérifier si l'étudiant est concerné par ce frais
            $estConcerne = false;
            $montantAttendu = 0;

            if ($category->is_mandatory) {
                // Frais obligatoire : tous les étudiants sont concernés
                $estConcerne = true;

                // Prioriser la souscription individuelle
                $inscriptionSubscriptions = $subscriptions->get($inscription->id, collect());
                $subscription = $inscriptionSubscriptions->where('frais_category_id', $category->id)->first();

                if ($subscription) {
                    $montantAttendu = $subscription->amount;
                } else {
                    // Fallback sur la configuration générale si pas de souscription
                    $configKey = $category->id . '_' . $inscription->filiere_id . '_' . $inscription->niveau_id;
                    $configuration = $configurations->get($configKey, collect())->first();
                    $montantAttendu = $configuration ? $configuration->getMontantByStatus($inscription->affectation_status ?? 'affecté') : $category->default_amount;
                }
            } else {
                // Service optionnel : vérifier s'il y a une souscription active
                $inscriptionSubscriptions = $subscriptions->get($inscription->id, collect());
                $subscription = $inscriptionSubscriptions->where('frais_category_id', $category->id)->first();

                if ($subscription) {
                    $estConcerne = true;
                    $montantAttendu = $subscription->amount;
                }
            }

            // Traiter seulement les étudiants concernés ET qui ont des frais > 0
            if ($estConcerne && $montantAttendu > 0) {
                $details['montant_total_attendu'] += $montantAttendu;

                // Vérifier les paiements de l'étudiant pour cette catégorie
                $paiementKey = $inscription->id . '_' . $category->id;
                $paiementsEtudiant = $paiements->get($paiementKey, collect());
                $montantPaye = $paiementsEtudiant->sum('montant');
                $details['montant_total_recu'] += $montantPaye;

                $statutEtudiant = [
                    'inscription' => $inscription,
                    'montant_attendu' => $montantAttendu,
                    'montant_paye' => $montantPaye,
                    'solde' => $montantAttendu - $montantPaye,
                    'pourcentage' => $montantAttendu > 0 ? round(($montantPaye / $montantAttendu) * 100, 1) : 0,
                    'derniers_paiements' => $paiementsEtudiant->sortByDesc('date_paiement')->take(3),
                ];

                // Catégoriser l'étudiant
                if ($montantPaye >= $montantAttendu) {
                    $details['etudiants_a_jour']->push($statutEtudiant);
                } elseif ($montantPaye > 0) {
                    $details['etudiants_en_retard']->push($statutEtudiant);
                } else {
                    $details['etudiants_non_payes']->push($statutEtudiant);
                }
            }
        }

        return $details;
    }

    /**
     * Version optimisée de calculerStatistiquesCategories - évite les requêtes N+1
     */
    private function calculerStatistiquesCategoriesOptimisees($inscriptions, $categories, $configurations, $subscriptions, $paiements)
    {
        $statistiques = [];

        foreach ($categories as $category) {
            $stats = [
                'category' => $category,
                'total_etudiants' => $inscriptions->count(),
                'etudiants_concernes' => 0,
                'etudiants_a_jour' => 0,
                'etudiants_en_retard' => 0,
                'etudiants_non_payes' => 0,
                'montant_total_attendu' => 0,
                'montant_total_recu' => 0,
                'taux_recouvrement' => 0,
            ];

            foreach ($inscriptions as $inscription) {
                // Vérifier si l'étudiant est concerné par ce frais
                $estConcerne = false;
                $montantAttendu = 0;

                if ($category->is_mandatory) {
                    // Frais obligatoire : tous les étudiants sont concernés
                    $estConcerne = true;

                    // Prioriser la souscription individuelle
                    $inscriptionSubscriptions = $subscriptions->get($inscription->id, collect());
                    $subscription = $inscriptionSubscriptions->where('frais_category_id', $category->id)->first();

                    if ($subscription) {
                        $montantAttendu = $subscription->amount;
                    } else {
                        // Fallback sur la configuration générale si pas de souscription
                        $configKey = $category->id . '_' . $inscription->filiere_id . '_' . $inscription->niveau_id;
                        $configuration = $configurations->get($configKey, collect())->first();
                        $montantAttendu = $configuration ? $configuration->getMontantByStatus($inscription->affectation_status ?? 'affecté') : $category->default_amount;
                    }
                } else {
                    // Service optionnel : vérifier s'il y a une souscription active
                    $inscriptionSubscriptions = $subscriptions->get($inscription->id, collect());
                    $subscription = $inscriptionSubscriptions->where('frais_category_id', $category->id)->first();

                    if ($subscription) {
                        $estConcerne = true;
                        $montantAttendu = $subscription->amount;
                    }
                }

                // Traiter seulement les étudiants concernés ET qui ont des frais > 0
                if ($estConcerne && $montantAttendu > 0) {
                    $stats['etudiants_concernes']++;
                    $stats['montant_total_attendu'] += $montantAttendu;

                    // Paiements de l'étudiant pour cette catégorie
                    $paiementKey = $inscription->id . '_' . $category->id;
                    $paiementsEtudiant = $paiements->get($paiementKey, collect());
                    $montantPaye = $paiementsEtudiant->sum('montant');
                    $stats['montant_total_recu'] += $montantPaye;

                    // Catégorisation
                    if ($montantPaye >= $montantAttendu) {
                        $stats['etudiants_a_jour']++;
                    } elseif ($montantPaye > 0) {
                        $stats['etudiants_en_retard']++;
                    } else {
                        $stats['etudiants_non_payes']++;
                    }
                }
            }

            // Calcul du taux de recouvrement basé sur les montants attendus réels
            $stats['taux_recouvrement'] = $stats['montant_total_attendu'] > 0
                ? round(($stats['montant_total_recu'] / $stats['montant_total_attendu']) * 100, 1)
                : 0;

            // Mettre à jour total_etudiants avec le nombre réel d'étudiants concernés
            $stats['total_etudiants'] = $stats['etudiants_concernes'];

            $statistiques[] = $stats;
        }

        return collect($statistiques);
    }

    /**
     * Version optimisée de calculerVueEnsemble - évite les requêtes N+1
     */
    private function calculerVueEnsembleOptimisee($inscriptions, $categories, $configurations, $subscriptions, $paiements)
    {
        $totalEtudiants = $inscriptions->count();
        $etudiantsEnRegle = 0;
        $etudiantsEnRetard = 0;
        $etudiantsNonPayes = 0;
        $montantTotalAttendu = 0;
        $montantTotalRecu = 0;

        foreach ($inscriptions as $inscription) {
            $etudiantEnRegle = true;
            $etudiantAPayeQuelqueChose = false;
            $montantEtudiantAttendu = 0;
            $montantEtudiantPaye = 0;

            foreach ($categories as $category) {
                // Vérifier si l'étudiant est concerné par ce frais
                $estConcerne = false;
                $montantAttendu = 0;

                if ($category->is_mandatory) {
                    // Frais obligatoire : tous les étudiants sont concernés
                    $estConcerne = true;

                    // Prioriser la souscription individuelle
                    $inscriptionSubscriptions = $subscriptions->get($inscription->id, collect());
                    $subscription = $inscriptionSubscriptions->where('frais_category_id', $category->id)->first();

                    if ($subscription) {
                        $montantAttendu = $subscription->amount;
                    } else {
                        // Fallback sur la configuration générale si pas de souscription
                        $configKey = $category->id . '_' . $inscription->filiere_id . '_' . $inscription->niveau_id;
                        $configuration = $configurations->get($configKey, collect())->first();
                        $montantAttendu = $configuration ? $configuration->getMontantByStatus($inscription->affectation_status ?? 'affecté') : $category->default_amount;
                    }
                } else {
                    // Service optionnel : vérifier s'il y a une souscription active
                    $inscriptionSubscriptions = $subscriptions->get($inscription->id, collect());
                    $subscription = $inscriptionSubscriptions->where('frais_category_id', $category->id)->first();

                    if ($subscription) {
                        $estConcerne = true;
                        $montantAttendu = $subscription->amount;
                    }
                }

                if ($estConcerne) {
                    $montantEtudiantAttendu += $montantAttendu;

                    // Paiements de l'étudiant pour cette catégorie
                    $paiementKey = $inscription->id . '_' . $category->id;
                    $paiementsEtudiant = $paiements->get($paiementKey, collect());
                    $montantPaye = $paiementsEtudiant->sum('montant');
                    $montantEtudiantPaye += $montantPaye;

                    if ($montantPaye > 0) {
                        $etudiantAPayeQuelqueChose = true;
                    }
                    if ($montantPaye < $montantAttendu) {
                        $etudiantEnRegle = false;
                    }
                }
            }

            // Si on filtre par catégorie spécifique, ne compter que les étudiants concernés par cette catégorie
            // (c'est-à-dire qui ont des frais > 0 pour cette catégorie)
            if ($montantEtudiantAttendu > 0) {
                $montantTotalAttendu += $montantEtudiantAttendu;
                $montantTotalRecu += $montantEtudiantPaye;

                // Catégoriser l'étudiant globalement
                if ($etudiantEnRegle) {
                    $etudiantsEnRegle++;
                } elseif ($etudiantAPayeQuelqueChose) {
                    $etudiantsEnRetard++;
                } else {
                    $etudiantsNonPayes++;
                }
            }
        }

        $tauxRecouvrement = $montantTotalAttendu > 0
            ? round(($montantTotalRecu / $montantTotalAttendu) * 100, 1)
            : 0;

        // Le total d'étudiants pour les pourcentages doit correspondre aux étudiants concernés
        $totalEtudiantsConcernes = $etudiantsEnRegle + $etudiantsEnRetard + $etudiantsNonPayes;

        return [
            'total_etudiants' => $totalEtudiantsConcernes,
            'etudiants_en_regle' => $etudiantsEnRegle,
            'etudiants_en_retard' => $etudiantsEnRetard,
            'etudiants_non_payes' => $etudiantsNonPayes,
            'montant_total_attendu' => $montantTotalAttendu,
            'montant_total_recu' => $montantTotalRecu,
            'taux_recouvrement' => $tauxRecouvrement,
            'taux_recouvrement_global' => $tauxRecouvrement, // Ajouté pour compatibilité avec la vue
            'pourcentage_en_regle' => $totalEtudiantsConcernes > 0 ? round(($etudiantsEnRegle / $totalEtudiantsConcernes) * 100, 1) : 0,
            'pourcentage_en_retard' => $totalEtudiantsConcernes > 0 ? round(($etudiantsEnRetard / $totalEtudiantsConcernes) * 100, 1) : 0,
            'pourcentage_non_payes' => $totalEtudiantsConcernes > 0 ? round(($etudiantsNonPayes / $totalEtudiantsConcernes) * 100, 1) : 0,
        ];
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
        $details = $this->analyserCategorieDetailleOptimisee($category, $inscriptions, $configurations, $subscriptions, $paiements);

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
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Erreur serveur: ' . $e->getMessage(),
                'debug' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
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
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Erreur lors du paiement: ' . $e->getMessage());
        }
    }

    /**
     * Valider un paiement
     */
    public function valider($id)
    {
        try {
            $paiement = ESBTPPaiement::findOrFail($id);

            // Vérifier si le paiement peut être validé
            if ($paiement->status === 'validé') {
                return redirect()->back()->with('error', 'Ce paiement est déjà validé.');
            }

            if ($paiement->status === 'rejeté') {
                return redirect()->back()->with('error', 'Ce paiement a été rejeté et ne peut pas être validé.');
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

            // Envoyer notification à l'étudiant
            try {
                $notificationService = app(\App\Services\NotificationService::class);
                $notificationService->notifyPaiementValide($paiement, auth()->user());
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

            return redirect()->back()->with('success', 'Paiement validé avec succès.');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Erreur lors de la validation du paiement', [
                'paiement_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Erreur lors de la validation: ' . $e->getMessage());
        }
    }

    /**
     * Rejeter un paiement
     */
    public function rejeter(Request $request, $id)
    {
        $request->validate([
            'motif_rejet' => 'required|string|max:500'
        ]);

        try {
            $paiement = ESBTPPaiement::findOrFail($id);

            // Vérifier si le paiement peut être rejeté
            if ($paiement->status === 'validé') {
                return redirect()->back()->with('error', 'Ce paiement est déjà validé et ne peut pas être rejeté.');
            }

            if ($paiement->status === 'rejeté') {
                return redirect()->back()->with('error', 'Ce paiement est déjà rejeté.');
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

            return redirect()->back()->with('success', 'Paiement rejeté avec succès.');

        } catch (\Exception $e) {
            \Log::error('Erreur lors du rejet du paiement', [
                'paiement_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Erreur lors du rejet: ' . $e->getMessage());
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

            // Construire le message de retour
            $message = '';
            if ($successCount > 0) {
                $message = "$successCount paiement(s) validé(s) avec succès.";
            }
            if ($alreadyProcessed > 0) {
                $message .= " $alreadyProcessed paiement(s) déjà validé(s).";
            }
            if ($errorCount > 0) {
                $message .= " $errorCount paiement(s) n'ont pas pu être validés.";
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Erreur lors de la validation groupée des paiements', [
                'paiements' => $request->paiements,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Erreur lors de la validation groupée: ' . $e->getMessage());
        }
    }

    /**
     * Rejeter plusieurs paiements en une fois
     */
    public function bulkRejeter(Request $request)
    {
        $request->validate([
            'paiements' => 'required|array|min:1',
            'paiements.*' => 'exists:esbtp_paiements,id',
            'motif_rejet' => 'required|string|max:500'
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

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Erreur lors du rejet groupé des paiements', [
                'paiements' => $request->paiements,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Erreur lors du rejet groupé: ' . $e->getMessage());
        }
    }
}
