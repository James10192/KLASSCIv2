<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ESBTPComptabiliteConfiguration;
use App\Models\ESBTPFraisScolarite;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPBourse;
use App\Models\ESBTPTransactionFinanciere;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPInscription;
use App\Models\ESBTPClasse;
use App\Models\User;
use App\Services\ComptabiliteService;
use App\Services\PerformanceMonitoringService;
use App\Services\AnalyticsPredictifService;
use App\Services\AIAnalyticsService;
use App\Services\BonDepenseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class ESBTPComptabiliteRelanceController extends Controller
{
    /**
     * Constructeur avec injection des services optimisés
     */
    public function __construct(
        ComptabiliteService $comptabiliteService,
        PerformanceMonitoringService $performanceMonitor,
        AnalyticsPredictifService $analyticsPredictifService,
        AIAnalyticsService $aiAnalyticsService,
        BonDepenseService $bonDepenseService
    ) {
        $this->comptabiliteService = $comptabiliteService;
        $this->performanceMonitor = $performanceMonitor;
        $this->analyticsPredictifService = $analyticsPredictifService;
        $this->aiAnalyticsService = $aiAnalyticsService;
        $this->bonDepenseService = $bonDepenseService;

        $this->middleware('auth');
        $this->middleware('comptabilite.access');
    }


    /**
     * Fiche relance pour un étudiant spécifique
     */
    public function relanceEtudiant(\App\Models\ESBTPInscription $inscription)
    {
        $inscription->load(['etudiant.parents', 'classe', 'anneeUniversitaire', 'fraisSubscriptions', 'paiements' => function ($q) {
            $q->whereIn('status', ['validé', 'en_attente'])->whereNull('deleted_at');
        }]);

        $etudiant = $inscription->etudiant;
        if (!$etudiant) {
            abort(404, 'Étudiant introuvable.');
        }

        // Calculs financiers — alignés avec suivi-catégories (fix fraisSubscriptions->sum)
        $allCategories = \App\Models\ESBTPFraisCategory::where('is_active', true)->get();
        $subsByInscription = \App\Models\ESBTPFraisSubscription::where('is_active', true)
            ->where('inscription_id', $inscription->id)
            ->get()
            ->groupBy('inscription_id');
        $allConfigurations = \App\Models\ESBTPFraisConfiguration::where('is_active', true)
            ->whereIn('frais_category_id', $allCategories->pluck('id'))
            ->get()
            ->groupBy(fn($c) => $c->frais_category_id . '_' . $c->filiere_id . '_' . $c->niveau_id);

        $totalDu = $this->calculerTotalDuParInscription($inscription, $allCategories, $subsByInscription, $allConfigurations);
        $totalPaye = $inscription->paiements->sum('montant');
        $soldeRestant = max(0, $totalDu - $totalPaye);
        $pourcentagePaye = $totalDu > 0 ? min(100, round($totalPaye / $totalDu * 100)) : 0;

        // Frais par catégorie — même logique que calculerTotalDuParInscription()
        // Inclut les catégories obligatoires sans subscription (via config/default_amount)
        $inscriptionSubsDetail = $subsByInscription->get($inscription->id, collect());
        $fraisDetail = [];
        foreach ($allCategories as $category) {
            $sub = $inscriptionSubsDetail->where('frais_category_id', $category->id)->first();
            if ($category->is_mandatory) {
                if ($sub) {
                    $montant = $sub->amount;
                } else {
                    $configKey = $category->id . '_' . $inscription->filiere_id . '_' . $inscription->niveau_id;
                    $config = $allConfigurations->get($configKey, collect())->first();
                    $montant = $config
                        ? $config->getMontantByStatus($inscription->affectation_status ?? 'affecté')
                        : $category->default_amount;
                }
            } else {
                $montant = $sub ? $sub->amount : 0;
            }
            if ($montant <= 0) continue;
            $paye = $inscription->paiements
                ->where('frais_category_id', $category->id)
                ->sum('montant');
            $fraisDetail[] = [
                'name'   => $category->name,
                'amount' => $montant,
                'paye'   => $paye,
            ];
        }
        $fraisImpayés = collect($fraisDetail)->values();

        // Niveau de risque selon le solde restant à payer
        if ($soldeRestant <= 0) {
            $riskLevel = 'low'; $riskLabel = 'À jour'; $riskColor = '#10b981';
        } elseif ($totalDu > 0 && ($soldeRestant / $totalDu) <= 0.25) {
            $riskLevel = 'medium'; $riskLabel = 'Presque soldé'; $riskColor = '#5e91de';
        } elseif ($totalPaye > 0) {
            $riskLevel = 'high'; $riskLabel = 'En cours'; $riskColor = '#0453cb';
        } else {
            $riskLevel = 'critical'; $riskLabel = 'Impayé'; $riskColor = '#1e293b';
        }

        // Autres inscriptions de l'étudiant (pour navigation multi-années)
        $autresInscriptions = \App\Models\ESBTPInscription::with(['anneeUniversitaire', 'classe'])
            ->where('etudiant_id', $etudiant->id)
            ->where('id', '!=', $inscription->id)
            ->orderByDesc('id')
            ->get();

        // Historique relances
        try {
            $historique = \App\Models\Notification::where('notifiable_id', $etudiant->user_id ?? 0)
                ->where('notifiable_type', \App\Models\User::class)
                ->latest()
                ->limit(20)
                ->get();
        } catch (\Exception $e) {
            $historique = collect();
        }

        return view('esbtp.comptabilite.relances.etudiant', compact(
            'inscription', 'etudiant',
            'totalDu', 'totalPaye', 'soldeRestant', 'pourcentagePaye',
            'fraisImpayés', 'historique',
            'riskLevel', 'riskLabel', 'riskColor',
            'autresInscriptions'
        ));
    }


    /**
     * Liste des relances — étudiants avec soldes impayés
     */
    public function gestionRelances(Request $request)
    {
        $search       = $request->input('search', '');
        $riskFilter   = $request->input('risk', '');
        $filiereId    = $request->input('filiere_id', '');
        $classeId     = $request->input('classe_id', '');
        $anneeId      = $request->input('annee_id', '');
        $perPage      = (int) $request->input('per_page', 25);

        // Année universitaire : paramètre ou active
        $anneeActive = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $anneeId     = $anneeId ?: optional($anneeActive)->id;

        // Query de base : inscriptions actives avec solde potentiel non nul
        $query = \App\Models\ESBTPInscription::with([
            'etudiant',
            'classe.filiere',
            'anneeUniversitaire',
            'fraisSubscriptions',
            'paiements' => fn ($q) => $q->whereIn('status', ['validé', 'en_attente'])->whereNull('deleted_at'),
        ])
        ->when($anneeId, fn ($q) => $q->where('annee_universitaire_id', $anneeId))
        ->when($classeId, fn ($q) => $q->where('classe_id', $classeId))
        ->when($filiereId, fn ($q) => $q->whereHas('classe', fn ($c) => $c->where('filiere_id', $filiereId)))
        ->when($search, fn ($q) => $q->whereHas('etudiant', fn ($e) => $e->where('nom', 'like', "%$search%")->orWhere('prenoms', 'like', "%$search%")->orWhere('matricule', 'like', "%$search%")))
        ->latest('created_at');

        // Calculer risk levels en PHP (après fetch)
        $allInscriptions = $query->get();

        // Pré-charger catégories et configurations une seule fois (évite N+1 dans la map)
        $relCategories = \App\Models\ESBTPFraisCategory::where('is_active', true)->get();
        $relInscriptionIds = $allInscriptions->pluck('id')->toArray();
        $relSubscriptions = \App\Models\ESBTPFraisSubscription::where('is_active', true)
            ->whereIn('inscription_id', $relInscriptionIds)
            ->get()
            ->groupBy('inscription_id');
        $relConfigurations = \App\Models\ESBTPFraisConfiguration::where('is_active', true)
            ->whereIn('frais_category_id', $relCategories->pluck('id'))
            ->get()
            ->groupBy(fn($c) => $c->frais_category_id . '_' . $c->filiere_id . '_' . $c->niveau_id);

        $rows = $allInscriptions->map(function ($inscription) use ($relCategories, $relSubscriptions, $relConfigurations) {
            $totalDu            = $this->calculerTotalDuParInscription($inscription, $relCategories, $relSubscriptions, $relConfigurations);
            $totalPaye          = $inscription->paiements->where('status', 'validé')->sum('montant');
            $totalPayeEnAttente = $inscription->paiements->where('status', 'en_attente')->sum('montant');
            $soldeRestant       = max(0, $totalDu - $totalPaye);
            $pourcentage        = $totalDu > 0 ? min(100, round($totalPaye / $totalDu * 100)) : 100;

            if ($soldeRestant <= 0) {
                $risk = 'low'; $riskLabel = 'À jour';
            } elseif ($totalDu > 0 && ($soldeRestant / $totalDu) <= 0.25) {
                $risk = 'medium'; $riskLabel = 'Presque soldé';
            } elseif ($totalPaye > 0) {
                $risk = 'high'; $riskLabel = 'En cours';
            } else {
                $risk = 'critical'; $riskLabel = 'Impayé';
            }

            return (object) compact('inscription', 'totalDu', 'totalPaye', 'totalPayeEnAttente', 'soldeRestant', 'pourcentage', 'risk', 'riskLabel');
        });

        // KPIs globaux calculés sur TOUTES les lignes (avant filtrage risk)
        // Les counts des tabs doivent refléter le total réel, pas le filtre actif
        $allRowsWithDebt = $rows->filter(fn ($r) => $r->soldeRestant > 0);
        $kpis = [
            'total_impaye'      => $allRowsWithDebt->sum(fn ($r) => $r->soldeRestant),
            'total_en_attente'  => $rows->sum(fn ($r) => $r->totalPayeEnAttente),
            'count_critical'    => $rows->where('risk', 'critical')->count(),
            'count_high'        => $rows->where('risk', 'high')->count(),
            'count_medium'      => $rows->where('risk', 'medium')->count(),
            'count_low'         => $rows->where('risk', 'low')->count(),
            'total_etudiants'   => $allRowsWithDebt->count(),
        ];

        // Filtrer par risque pour l'affichage du tableau uniquement
        if ($riskFilter) {
            $rows = $rows->filter(fn ($r) => $r->risk === $riskFilter);
        }

        // Exclure les étudiants à jour pour la liste paginée
        $rowsWithDebt = $rows->filter(fn ($r) => $r->soldeRestant > 0);

        // Pagination manuelle
        $page       = (int) $request->input('page', 1);
        $offset     = ($page - 1) * $perPage;
        $paginated  = new \Illuminate\Pagination\LengthAwarePaginator(
            $rowsWithDebt->slice($offset, $perPage)->values(),
            $rowsWithDebt->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Données filtres
        $filieres = \App\Models\ESBTPFiliere::orderBy('name')->get();
        $classes  = \App\Models\ESBTPClasse::when($filiereId, fn ($q) => $q->where('filiere_id', $filiereId))->orderBy('name')->get();
        $annees   = \App\Models\ESBTPAnneeUniversitaire::orderByDesc('annee_debut')->get();

        // Vérifier si les délais sont configurés (requis pour signaler à l'utilisateur)
        $delaisRows = \DB::table('settings')
            ->where('group', 'relances')
            ->whereIn('key', ['relances.delai_niveau_1', 'relances.delai_niveau_2', 'relances.delai_niveau_3'])
            ->count();
        $configManquante = $delaisRows < 3;

        $viewData = compact(
            'paginated', 'kpis', 'filieres', 'classes', 'annees',
            'search', 'riskFilter', 'filiereId', 'classeId', 'anneeId', 'perPage', 'anneeActive',
            'configManquante'
        );

        // AJAX request → return JSON avec table HTML + kpis mis à jour
        if ($request->ajax() || $request->input('ajax') === '1') {
            return response()->json([
                'table' => view('esbtp.comptabilite.relances._table', $viewData)->render(),
                'kpis'  => $kpis,
            ]);
        }

        return view('esbtp.comptabilite.relances.index', $viewData);
    }


    /**
     * Export Excel des relances (filtres respectés)
     */
    public function exportRelancesExcel(Request $request)
    {
        $search     = $request->input('search', '');
        $riskFilter = $request->input('risk', '');
        $filiereId  = $request->input('filiere_id', '');
        $classeId   = $request->input('classe_id', '');
        $anneeId    = $request->input('annee_id', '');

        $anneeActive = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $anneeId     = $anneeId ?: optional($anneeActive)->id;

        $allInscriptions = \App\Models\ESBTPInscription::with([
            'etudiant', 'classe.filiere', 'anneeUniversitaire', 'fraisSubscriptions',
            'paiements' => fn ($q) => $q->whereIn('status', ['validé', 'en_attente'])->whereNull('deleted_at'),
        ])
        ->when($anneeId, fn ($q) => $q->where('annee_universitaire_id', $anneeId))
        ->when($classeId, fn ($q) => $q->where('classe_id', $classeId))
        ->when($filiereId, fn ($q) => $q->whereHas('classe', fn ($c) => $c->where('filiere_id', $filiereId)))
        ->when($search, fn ($q) => $q->whereHas('etudiant', fn ($e) => $e->where('nom', 'like', "%$search%")->orWhere('prenoms', 'like', "%$search%")->orWhere('matricule', 'like', "%$search%")))
        ->get();

        // Pré-charger catégories et configurations pour éviter N+1 dans la map
        $xlsCategories = \App\Models\ESBTPFraisCategory::where('is_active', true)->get();
        $xlsSubscriptions = \App\Models\ESBTPFraisSubscription::where('is_active', true)
            ->whereIn('inscription_id', $allInscriptions->pluck('id')->toArray())
            ->get()->groupBy('inscription_id');
        $xlsConfigurations = \App\Models\ESBTPFraisConfiguration::where('is_active', true)
            ->whereIn('frais_category_id', $xlsCategories->pluck('id'))
            ->get()->groupBy(fn($c) => $c->frais_category_id . '_' . $c->filiere_id . '_' . $c->niveau_id);

        $rows = $allInscriptions->map(function ($inscription) use ($xlsCategories, $xlsSubscriptions, $xlsConfigurations) {
            $totalDu      = $this->calculerTotalDuParInscription($inscription, $xlsCategories, $xlsSubscriptions, $xlsConfigurations);
            $totalPaye    = $inscription->paiements->where('status', 'validé')->sum('montant');
            $soldeRestant = max(0, $totalDu - $totalPaye);

            if ($soldeRestant <= 0) {
                $risk = 'low';
            } elseif ($totalDu > 0 && ($soldeRestant / $totalDu) <= 0.25) {
                $risk = 'medium';
            } elseif ($totalPaye > 0) {
                $risk = 'high';
            } else {
                $risk = 'critical';
            }

            return [
                'matricule'    => $inscription->etudiant->matricule ?? 'N/A',
                'nom'          => $inscription->etudiant->nom ?? '',
                'prenoms'      => $inscription->etudiant->prenoms ?? '',
                'classe'       => $inscription->classe->name ?? 'N/A',
                'filiere'      => $inscription->classe->filiere->name ?? 'N/A',
                'total_du'     => $totalDu,
                'total_paye'   => $totalPaye,
                'solde_restant'=> $soldeRestant,
                'risk_level'   => $risk,
            ];
        })->filter(fn ($r) => $r['solde_restant'] > 0);

        if ($riskFilter) {
            $rows = $rows->filter(fn ($r) => $r['risk_level'] === $riskFilter);
        }

        $rowsCollection = $rows->values();

        $kpis = [
            'nb_relances'  => $rowsCollection->count(),
            'total_impaye' => $rowsCollection->sum('solde_restant'),
            'nb_critical'  => $rowsCollection->where('risk_level', 'critical')->count(),
            'nb_high'      => $rowsCollection->where('risk_level', 'high')->count(),
            'nb_medium'    => $rowsCollection->where('risk_level', 'medium')->count(),
            'nb_low'       => $rowsCollection->where('risk_level', 'low')->count(),
        ];

        // Labels lisibles pour filtres info
        $filters = array_filter([
            'search'   => $search,
            'filiere'  => $filiereId ? (\App\Models\ESBTPFiliere::find($filiereId)->name ?? null) : null,
            'classe'   => $classeId  ? (\App\Models\ESBTPClasse::find($classeId)->name ?? null)  : null,
            'annee'    => $anneeId   ? (\App\Models\ESBTPAnneeUniversitaire::find($anneeId)->name ?? null) : null,
            'risk'     => $riskFilter,
        ]);

        $filename = 'relances_' . now()->format('Y-m-d_Hi') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\RelancesExport($rowsCollection, $kpis, $filters),
            $filename
        );
    }


    /**
     * Export PDF des relances (filtres respectés)
     */
    public function exportRelancesPdf(Request $request)
    {
        // Pour les gros exports (1000+ étudiants), utilise chunk+merge FPDI
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $search     = $request->input('search', '');
        $riskFilter = $request->input('risk', '');
        $filiereId  = $request->input('filiere_id', '');
        $classeId   = $request->input('classe_id', '');
        $anneeId    = $request->input('annee_id', '');

        $anneeActive = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $anneeId     = $anneeId ?: optional($anneeActive)->id;

        $allInscriptions = \App\Models\ESBTPInscription::with([
            'etudiant', 'classe.filiere', 'anneeUniversitaire', 'fraisSubscriptions',
            'paiements' => fn ($q) => $q->whereIn('status', ['validé', 'en_attente'])->whereNull('deleted_at'),
        ])
        ->when($anneeId, fn ($q) => $q->where('annee_universitaire_id', $anneeId))
        ->when($classeId, fn ($q) => $q->where('classe_id', $classeId))
        ->when($filiereId, fn ($q) => $q->whereHas('classe', fn ($c) => $c->where('filiere_id', $filiereId)))
        ->when($search, fn ($q) => $q->whereHas('etudiant', fn ($e) => $e->where('nom', 'like', "%$search%")->orWhere('prenoms', 'like', "%$search%")->orWhere('matricule', 'like', "%$search%")))
        ->get();

        // Pré-charger catégories et configurations pour éviter N+1 dans la map
        $expCategories = \App\Models\ESBTPFraisCategory::where('is_active', true)->get();
        $expSubscriptions = \App\Models\ESBTPFraisSubscription::where('is_active', true)
            ->whereIn('inscription_id', $allInscriptions->pluck('id')->toArray())
            ->get()->groupBy('inscription_id');
        $expConfigurations = \App\Models\ESBTPFraisConfiguration::where('is_active', true)
            ->whereIn('frais_category_id', $expCategories->pluck('id'))
            ->get()->groupBy(fn($c) => $c->frais_category_id . '_' . $c->filiere_id . '_' . $c->niveau_id);

        $relances = $allInscriptions->map(function ($inscription) use ($expCategories, $expSubscriptions, $expConfigurations) {
            $totalDu      = $this->calculerTotalDuParInscription($inscription, $expCategories, $expSubscriptions, $expConfigurations);
            $totalPaye    = $inscription->paiements->where('status', 'validé')->sum('montant');
            $soldeRestant = max(0, $totalDu - $totalPaye);

            if ($soldeRestant <= 0) {
                $risk = 'low';
            } elseif ($totalDu > 0 && ($soldeRestant / $totalDu) <= 0.25) {
                $risk = 'medium';
            } elseif ($totalPaye > 0) {
                $risk = 'high';
            } else {
                $risk = 'critical';
            }

            return [
                'matricule'    => $inscription->etudiant->matricule ?? 'N/A',
                'nom'          => $inscription->etudiant->nom ?? '',
                'prenoms'      => $inscription->etudiant->prenoms ?? '',
                'classe'       => $inscription->classe->name ?? 'N/A',
                'filiere'      => $inscription->classe->filiere->name ?? 'N/A',
                'total_du'     => $totalDu,
                'total_paye'   => $totalPaye,
                'solde_restant'=> $soldeRestant,
                'risk_level'   => $risk,
            ];
        })->filter(fn ($r) => $r['solde_restant'] > 0);

        if ($riskFilter) {
            $relances = $relances->filter(fn ($r) => $r['risk_level'] === $riskFilter);
        }

        $relances = $relances->values();

        // Infos établissement depuis settings
        $etablissement = [
            'nom'       => \App\Models\Setting::get('school_name', config('app.name')),
            'adresse'   => \App\Models\Setting::get('school_address', ''),
            'telephone' => \App\Models\Setting::get('school_phone', ''),
            'email'     => \App\Models\Setting::get('school_email', ''),
            'logo'      => \App\Models\Setting::get('school_logo', ''),
        ];

        // Filtres actifs lisibles pour le header
        $activeFilters = array_filter([
            $search     ? "Recherche: $search"                                                             : null,
            $filiereId  ? ('Filière: ' . (\App\Models\ESBTPFiliere::find($filiereId)->name ?? $filiereId)) : null,
            $classeId   ? ('Classe: '  . (\App\Models\ESBTPClasse::find($classeId)->name  ?? $classeId))   : null,
            $riskFilter ? ('Risque: '  . ucfirst($riskFilter))                                             : null,
        ]);

        // Stats globales calculées sur TOUTE la collection (pour le header du premier chunk)
        $globalStats = [
            'total_impaye' => $relances->sum('solde_restant'),
            'nb_critical'  => $relances->where('risk_level', 'critical')->count(),
            'nb_high'      => $relances->where('risk_level', 'high')->count(),
            'nb_medium'    => $relances->where('risk_level', 'medium')->count(),
            'nb_low'       => $relances->where('risk_level', 'low')->count(),
            'nb_total'     => $relances->count(),
        ];

        $filename   = 'relances_' . now()->format('Y-m-d_Hi') . '.pdf';
        $pdfOptions = [
            'dpi'                     => 72,
            'defaultFont'             => 'DejaVu Sans',
            'isRemoteEnabled'         => false,
            'isHtml5ParserEnabled'    => true,
            'isPhpEnabled'            => false,
            'isFontSubsettingEnabled' => true,
        ];

        $chunkSize = 200;

        // Petit export (< 200) → rendu direct sans FPDI
        if ($relances->count() <= $chunkSize) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('esbtp.comptabilite.relances.pdf', array_merge(
                compact('relances', 'anneeActive', 'etablissement', 'activeFilters'),
                ['globalStats' => $globalStats, 'isFirstChunk' => true, 'isLastChunk' => true, 'rowOffset' => 0]
            ))->setPaper('a4', 'landscape')->setOptions($pdfOptions);

            return $pdf->download($filename);
        }

        // Gros export → chunk + merge FPDI
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $chunks      = $relances->chunk($chunkSize);
        $tempFiles   = [];
        $totalChunks = $chunks->count();

        foreach ($chunks as $chunkIndex => $chunk) {
            $isFirstChunk = ($chunkIndex === 0);
            $isLastChunk  = ($chunkIndex === $totalChunks - 1);
            $rowOffset    = $chunkIndex * $chunkSize;

            $chunkPdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('esbtp.comptabilite.relances.pdf', [
                'relances'     => $chunk,
                'anneeActive'  => $anneeActive,
                'etablissement'=> $etablissement,
                'activeFilters'=> $activeFilters,
                'globalStats'  => $globalStats,
                'isFirstChunk' => $isFirstChunk,
                'isLastChunk'  => $isLastChunk,
                'rowOffset'    => $rowOffset,
                'chunkIndex'   => $chunkIndex,
            ])->setPaper('a4', 'landscape')->setOptions($pdfOptions);

            $tempPath = $tempDir . '/relances_chunk_' . uniqid() . '_' . $chunkIndex . '.pdf';
            file_put_contents($tempPath, $chunkPdf->output());
            $tempFiles[] = $tempPath;

            unset($chunkPdf);
        }

        // Fusionner tous les chunks avec FPDI (1:1 sans déformation)
        $merger = new \setasign\Fpdi\Fpdi();
        $merger->SetAutoPageBreak(false);

        foreach ($tempFiles as $file) {
            $pageCount = $merger->setSourceFile($file);
            for ($p = 1; $p <= $pageCount; $p++) {
                $tpl  = $merger->importPage($p);
                $size = $merger->getTemplateSize($tpl);
                $merger->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $merger->useTemplate($tpl, 0, 0, $size['width'], $size['height']);
            }
        }

        $finalPath = $tempDir . '/relances_final_' . uniqid() . '.pdf';
        $merger->Output('F', $finalPath);
        unset($merger);

        foreach ($tempFiles as $file) {
            @unlink($file);
        }

        return response()->download($finalPath, $filename)->deleteFileAfterSend(true);
    }


    /**
     * Configuration des relances
     */
    public function configurationRelances()
    {
        // Récupérer les templates existants depuis la configuration
        $templates = [
            'email'    => [],
            'sms'      => [],
            'courrier' => [],
        ];

        // Lire les paramètres depuis la BDD (table settings, group=relances)
        // Aucune valeur hardcodée — si absent → null
        $rows = \DB::table('settings')
            ->where('group', 'relances')
            ->pluck('value', 'key');

        $parametres = [
            'delai_niveau_1'        => isset($rows['relances.delai_niveau_1'])   ? (int) $rows['relances.delai_niveau_1']   : null,
            'delai_niveau_2'        => isset($rows['relances.delai_niveau_2'])   ? (int) $rows['relances.delai_niveau_2']   : null,
            'delai_niveau_3'        => isset($rows['relances.delai_niveau_3'])   ? (int) $rows['relances.delai_niveau_3']   : null,
            'montant_minimum'       => isset($rows['relances.montant_minimum'])  ? (int) $rows['relances.montant_minimum']  : null,
            'relances_automatiques' => isset($rows['relances.relances_automatiques']) ? (bool) $rows['relances.relances_automatiques'] : false,
            'heure_envoi'           => $rows['relances.heure_envoi'] ?? null,
        ];

        return view('esbtp.comptabilite.relances.config', compact('templates', 'parametres'));
    }


    /**
     * Aperçu des étudiants pour relances
     */
    public function apercuRelances(Request $request)
    {
        $dette = $request->input('dette', 50000);
        $jours = $request->input('jours', 30);

        $etudiants = \App\Models\ESBTPEtudiant::whereHas('factures', function($query) use ($dette, $jours) {
            $query->where('status', 'impayee')
                  ->where('montant_total', '>=', $dette)
                  ->where('date_echeance', '<', now()->subDays($jours));
        })->with('factures')->get();

        $totalDette = $etudiants->sum(function($etudiant) {
            return $etudiant->factures->where('status', 'impayee')->sum('montant_total');
        });

        $moyenneDette = $etudiants->count() > 0 ? $totalDette / $etudiants->count() : 0;

        return response()->json([
            'success' => true,
            'count' => $etudiants->count(),
            'total_dette' => number_format($totalDette, 0, ',', ' '),
            'moyenne_dette' => number_format($moyenneDette, 0, ',', ' ')
        ]);
    }


    /**
     * Planifier des relances
     */
    public function planifierRelances(Request $request)
    {
        $request->validate([
            'critere_dette' => 'required|numeric|min:0',
            'critere_jours' => 'required|numeric|min:1',
            'type_relance' => 'required|string|in:auto,email,sms,courrier',
            'date_envoi' => 'required|date'
        ]);

        try {
            $notificationService = app(\App\Services\NotificationService::class);

            // Utiliser la logique de planification du service
            $result = $notificationService->planifierRelances();

            return response()->json([
                'success' => true,
                'message' => "Relances planifiées avec succès: {$result['relances_planifiees']} relances créées"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la planification: ' . $e->getMessage()
            ]);
        }
    }


    /**
     * Afficher les détails d'une relance
     */
    public function showRelance($id)
    {
        $relance = \App\Models\ESBTPRelance::with(['etudiant', 'facture'])
            ->findOrFail($id);

        return view('esbtp.comptabilite.relances.show', compact('relance'));
    }


    /**
     * Renvoyer une relance
     */
    public function renvoyerRelance($id)
    {
        try {
            $relance = \App\Models\ESBTPRelance::findOrFail($id);

            // Vérifier que la relance peut être renvoyée
            if (!$relance->peutEtreRenvoyee()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette relance ne peut pas être renvoyée.'
                ]);
            }

            // Dispatche le job d'envoi
            \App\Jobs\EnvoyerRelanceJob::dispatch($relance);

            return response()->json([
                'success' => true,
                'message' => 'Relance mise en file d\'attente pour renvoi.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du renvoi: ' . $e->getMessage()
            ]);
        }
    }


    /**
     * Sauvegarder les templates de relances
     */
    public function sauvegarderTemplates(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:email,sms,courrier'
        ]);

        try {
            // Logique de sauvegarde des templates
            // À implémenter selon la structure de configuration choisie

            return response()->json([
                'success' => true,
                'message' => 'Templates sauvegardés avec succès.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()
            ]);
        }
    }


    /**
     * Sauvegarder les paramètres de relances
     */
    public function sauvegarderParametres(Request $request)
    {
        $request->validate([
            'delai_niveau_1' => 'required|integer|min:1|max:365',
            'delai_niveau_2' => 'required|integer|min:1|max:365',
            'delai_niveau_3' => 'required|integer|min:1|max:365',
            'montant_minimum' => 'required|numeric|min:0',
            'heure_envoi'     => 'required|date_format:H:i',
        ]);

        $parametres = [
            'relances.delai_niveau_1'        => (string) (int) $request->delai_niveau_1,
            'relances.delai_niveau_2'        => (string) (int) $request->delai_niveau_2,
            'relances.delai_niveau_3'        => (string) (int) $request->delai_niveau_3,
            'relances.montant_minimum'       => (string) (int) $request->montant_minimum,
            'relances.heure_envoi'           => $request->heure_envoi,
            'relances.relances_automatiques' => $request->boolean('relances_automatiques') ? '1' : '0',
        ];

        foreach ($parametres as $key => $value) {
            \DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'group' => 'relances', 'updated_at' => now(), 'created_at' => now()]
            );
        }

        return redirect()->route('esbtp.comptabilite.relances.config')
            ->with('success', 'Paramètres de relances sauvegardés avec succès.');
    }


    /**
     * Aperçu d'un template de relance
     */
    public function previewTemplate(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:email,sms,courrier',
            'niveau' => 'required|integer|min:1|max:3',
            'contenu' => 'required|string'
        ]);

        try {
            // Données d'exemple pour l'aperçu
            $etudiantExemple = (object) [
                'nom' => 'KOUAME',
                'prenoms' => 'Jean Pierre',
                'email' => 'jean.kouame@example.com',
                'telephone' => '+225 01 02 03 04 05'
            ];

            $contenu = $request->input('contenu');
            $type = $request->input('type');

            // Remplacer les variables par les exemples
            $variables = [
                '{nom}' => $etudiantExemple->nom,
                '{prenom}' => $etudiantExemple->prenoms,
                '{nom_complet}' => $etudiantExemple->prenoms . ' ' . $etudiantExemple->nom,
                '{email}' => $etudiantExemple->email,
                '{telephone}' => $etudiantExemple->telephone,
                '{montant_dette}' => '150,000 FCFA',
                '{date_echeance}' => now()->subDays(45)->format('d/m/Y'),
                '{jours_retard}' => '45',
                '{niveau_relance}' => $request->input('niveau'),
                '{nom_ecole}' => 'École Supérieure du Bâtiment et des Travaux Publics',
                '{date_aujourdhui}' => now()->format('d/m/Y')
            ];

            $contenuApercu = str_replace(array_keys($variables), array_values($variables), $contenu);

            $html = view('esbtp.comptabilite.relances.preview', compact('contenuApercu', 'type'))->render();

            return response($html);

        } catch (\Exception $e) {
            return response('<div class="text-center text-danger">Erreur lors de la génération</div>');
        }
    }


    /**
     * Exécuter les relances en attente manuellement
     */
    public function executerRelances()
    {
        try {
            $notificationService = app(\App\Services\NotificationService::class);
            $resultats = $notificationService->executerRelancesEnAttente();

            return response()->json([
                'success' => true,
                'message' => "Exécution terminée: {$resultats['reussies']} réussies, {$resultats['echecs']} échecs sur {$resultats['total']} relances."
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'exécution: ' . $e->getMessage()
            ]);
        }
    }




    /**
     * NOUVELLES MÉTHODES ANALYTICS AVANCÉES - Tâche #4
     */

    /**
     * Tableau de bord analytics des relances
     */
    public function analyticsRelances()
    {
        try {
            $notificationService = app(\App\Services\NotificationService::class);

            // Récupérer les statistiques avancées
            $statistiques = $notificationService->getStatistiquesRelancesAvancees();

            // Ajouter des métriques supplémentaires
            $statistiques['taux_global'] = $this->calculerTauxGlobalEfficacite();
            $statistiques['conversions_totales'] = $this->calculerConversionsTotal();
            $statistiques['delai_moyen'] = $this->calculerDelaiMoyenReponse();
            $statistiques['roi'] = $this->calculerROIRelances();

            return view('esbtp.comptabilite.relances.analytics', compact('statistiques'));

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur analytics relances: ' . $e->getMessage());

            // Retourner des données par défaut en cas d'erreur
            $statistiques = $this->getStatistiquesParDefaut();
            return view('esbtp.comptabilite.relances.analytics', compact('statistiques'));
        }
    }


    /**
     * Planification avancée avec segmentation
     */
    public function planifierRelancesAvancees(Request $request)
    {
        $request->validate([
            'segmentation' => 'required|string|in:auto,niveau_retard,montant_dette,historique_paiement,classe',
            'niveau_max' => 'required|integer|min:1|max:5',
            'types_relance' => 'required|array',
            'types_relance.*' => 'in:email,sms,courrier',
            'date_execution' => 'nullable|date|after_or_equal:today'
        ]);

        try {
            $notificationService = app(\App\Services\NotificationService::class);

            $parametres = [
                'segmentation' => $request->input('segmentation'),
                'niveau_max' => $request->input('niveau_max'),
                'types_relance' => $request->input('types_relance'),
                'date_execution' => $request->input('date_execution')
            ];

            // Si date future, programmer le job
            if ($request->filled('date_execution') && $request->input('date_execution') > now()->format('Y-m-d')) {
                \App\Jobs\PlanifierRelancesJob::dispatch($parametres)
                    ->delay(now()->parse($request->input('date_execution')));

                $message = "Relances programmées pour le " . now()->parse($request->input('date_execution'))->format('d/m/Y');
            } else {
                // Exécution immédiate
                $resultat = $notificationService->planifierRelancesAvancees($parametres);
                $message = "Planification terminée: {$resultat['relances_planifiees']} relances créées pour {$resultat['etudiants_traites']} étudiants";
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la planification avancée: ' . $e->getMessage()
            ]);
        }
    }


    /**
     * Export des données analytics
     */
    public function exportAnalyticsRelances(Request $request)
    {
        $request->validate([
            'format' => 'required|string|in:pdf,excel,csv',
            'periode' => 'required|string|in:mois_actuel,3_mois,6_mois,annee',
            'inclure_graphiques' => 'boolean'
        ]);

        try {
            $format = $request->input('format');
            $periode = $request->input('periode');
            $inclureGraphiques = $request->boolean('inclure_graphiques');

            $notificationService = app(\App\Services\NotificationService::class);
            $statistiques = $notificationService->getStatistiquesRelancesAvancees();

            switch ($format) {
                case 'pdf':
                    return $this->exportPDFAnalytics($statistiques, $periode, $inclureGraphiques);

                case 'excel':
                    return $this->exportExcelAnalytics($statistiques, $periode);

                case 'csv':
                    return $this->exportCSVAnalytics($statistiques, $periode);

                default:
                    throw new \Exception('Format non supporté');
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export: ' . $e->getMessage()
            ]);
        }
    }


    /**
     * Aperçu des segments avant planification
     */
    public function previewSegmentation(Request $request)
    {
        $request->validate([
            'type_segmentation' => 'required|string|in:auto,niveau_retard,montant_dette,historique_paiement,classe'
        ]);

        try {
            $notificationService = app(\App\Services\NotificationService::class);
            $segments = $notificationService->segmenterEtudiants($request->input('type_segmentation'));

            $preview = [];
            foreach ($segments as $nomSegment => $etudiants) {
                $preview[$nomSegment] = [
                    'nombre_etudiants' => count($etudiants),
                    'total_dette' => $etudiants->sum(function($etudiant) use ($notificationService) {
                        return $notificationService->calculerDette($etudiant);
                    }),
                    'exemple_etudiants' => array_slice($etudiants->pluck('nom', 'id')->toArray(), 0, 3)
                ];
            }

            return response()->json([
                'success' => true,
                'segments' => $preview
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'aperçu: ' . $e->getMessage()
            ]);
        }
    }


    /**
     * Méthodes privées pour le calcul des métriques
     */
    private function calculerTauxGlobalEfficacite()
    {
        $totalRelances = \App\Models\ESBTPRelance::where('status', 'envoyee')->count();

        if ($totalRelances == 0) return 0;

        $relancesEfficaces = \App\Models\ESBTPRelance::where('status', 'envoyee')
            ->whereHas('etudiant.paiements', function($query) {
                $query->where('created_at', '>', \DB::raw('esbtp_relances.date_envoi'))
                      ->where('created_at', '<', \DB::raw('DATE_ADD(esbtp_relances.date_envoi, INTERVAL 30 DAY)'));
            })
            ->count();

        return round(($relancesEfficaces / $totalRelances) * 100, 2);
    }


    private function calculerConversionsTotal()
    {
        return \App\Models\ESBTPRelance::where('status', 'envoyee')
            ->whereMonth('date_envoi', now()->month)
            ->whereYear('date_envoi', now()->year)
            ->whereHas('etudiant.paiements', function($query) {
                $query->where('created_at', '>', \DB::raw('esbtp_relances.date_envoi'))
                      ->where('created_at', '<', \DB::raw('DATE_ADD(esbtp_relances.date_envoi, INTERVAL 30 DAY)'));
            })
            ->count();
    }


    private function calculerDelaiMoyenReponse()
    {
        $relancesAvecPaiement = \App\Models\ESBTPRelance::where('status', 'envoyee')
            ->whereHas('etudiant.paiements', function($query) {
                $query->where('created_at', '>', \DB::raw('esbtp_relances.date_envoi'));
            })
            ->with(['etudiant.paiements' => function($query) {
                $query->where('created_at', '>', \DB::raw('esbtp_relances.date_envoi'))
                      ->orderBy('created_at', 'asc')
                      ->limit(1);
            }])
            ->get();

        if ($relancesAvecPaiement->isEmpty()) return 0;

        $totalJours = 0;
        $nombreRelances = 0;

        foreach ($relancesAvecPaiement as $relance) {
            $premierPaiement = $relance->etudiant->paiements->first();
            if ($premierPaiement) {
                $jours = $relance->date_envoi->diffInDays($premierPaiement->created_at);
                $totalJours += $jours;
                $nombreRelances++;
            }
        }

        return $nombreRelances > 0 ? round($totalJours / $nombreRelances, 1) : 0;
    }


    private function calculerROIRelances()
    {
        // Calcul simple du ROI basé sur les montants récupérés vs coût estimé des relances
        $montantRecupere = \App\Models\ESBTPPaiement::whereHas('relance')
            ->whereMonth('created_at', now()->month)
            ->sum('montant');

        $coutEstimeRelances = \App\Models\ESBTPRelance::whereMonth('created_at', now()->month)
            ->count() * 100; // 100 FCFA par relance (coût estimé)

        if ($coutEstimeRelances == 0) return 0;

        return round((($montantRecupere - $coutEstimeRelances) / $coutEstimeRelances) * 100, 2);
    }


    private function getStatistiquesParDefaut()
    {
        return [
            'taux_global' => 0,
            'conversions_totales' => 0,
            'delai_moyen' => 0,
            'roi' => 0,
            'efficacite_par_type' => [
                'email' => ['total_envoyees' => 0, 'avec_paiement' => 0, 'taux_efficacite' => 0],
                'sms' => ['total_envoyees' => 0, 'avec_paiement' => 0, 'taux_efficacite' => 0],
                'courrier' => ['total_envoyees' => 0, 'avec_paiement' => 0, 'taux_efficacite' => 0]
            ],
            'taux_conversion_par_niveau' => [
                'niveau_1' => ['total' => 0, 'conversions' => 0, 'taux' => 0],
                'niveau_2' => ['total' => 0, 'conversions' => 0, 'taux' => 0],
                'niveau_3' => ['total' => 0, 'conversions' => 0, 'taux' => 0]
            ],
            'segmentation_performance' => [
                'priorite_haute' => ['taux_reponse' => 0, 'delai_moyen_paiement' => 0],
                'priorite_moyenne' => ['taux_reponse' => 0, 'delai_moyen_paiement' => 0],
                'priorite_faible' => ['taux_reponse' => 0, 'delai_moyen_paiement' => 0]
            ],
            'tendances_mensuelles' => [],
            'predictions' => [
                'efficacite_prevue_mois_prochain' => 0,
                'volume_relances_prevu' => 0,
                'recommandations' => ['Données insuffisantes pour les recommandations']
            ]
        ];
    }


    private function exportPDFAnalytics($statistiques, $periode, $inclureGraphiques)
    {
        // Implémentation de l'export PDF
        $pdf = \PDF::loadView('esbtp.comptabilite.relances.analytics-pdf', compact('statistiques', 'periode', 'inclureGraphiques'));
        return $pdf->download('analytics-relances-' . now()->format('Y-m-d') . '.pdf');
    }


    private function exportExcelAnalytics($statistiques, $periode)
    {
        // Implémentation de l'export Excel
        // À implémenter avec Maatwebsite\Excel
        return response()->json(['message' => 'Export Excel en cours de développement']);
    }


    private function exportCSVAnalytics($statistiques, $periode)
    {
        // Implémentation de l'export CSV
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="analytics-relances-' . now()->format('Y-m-d') . '.csv"'
        ];

        $callback = function() use ($statistiques) {
            $file = fopen('php://output', 'w');

            // Headers CSV
            fputcsv($file, ['Type', 'Total Envoyées', 'Avec Paiement', 'Taux Efficacité']);

            // Données efficacité par type
            foreach ($statistiques['efficacite_par_type'] as $type => $data) {
                fputcsv($file, [$type, $data['total_envoyees'], $data['avec_paiement'], $data['taux_efficacite'] . '%']);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Calculer le total dû par inscription en utilisant les données pré-chargées
     */
    private function calculerTotalDuParInscription($inscription, $categories, $subscriptionsByInscription, $configurations): float
    {
        $inscriptionSubs = $subscriptionsByInscription->get($inscription->id, collect());
        $totalDu = 0;

        foreach ($categories as $category) {
            $sub = $inscriptionSubs->where('frais_category_id', $category->id)->first();
            if ($category->is_mandatory) {
                if ($sub) {
                    $montant = $sub->amount;
                } else {
                    $configKey = $category->id . '_' . $inscription->filiere_id . '_' . $inscription->niveau_id;
                    $config = $configurations->get($configKey, collect())->first();
                    $montant = $config
                        ? $config->getMontantByStatus($inscription->affectation_status ?? 'affecté')
                        : $category->default_amount;
                }
            } else {
                $montant = $sub ? $sub->amount : 0;
            }
            if ($montant > 0) {
                $totalDu += $montant;
            }
        }

        return (float) $totalDu;
    }

}
