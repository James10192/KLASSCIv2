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
use App\Services\FuzzyNameMatcher;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;

class ESBTPPaiementSuiviController extends Controller
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
     * Rafraîchir les données de suivi par catégorie via AJAX
     */
    public function suiviCategoriesRefresh(Request $request)
    {
        // Si accès direct (non-AJAX), rediriger vers la page principale avec les mêmes filtres
        if (!$request->ajax() && !$request->wantsJson()) {
            return redirect()->route('esbtp.paiements.suivi-categories', $request->query());
        }

        // Récupérer les paramètres de filtrage
        $filiereId = $request->input('filiere_id');
        $niveauId = $request->input('niveau_id');
        $anneeId = $request->input('annee_id');
        $categoryId = $request->input('category_id');

        // Récupérer les données nécessaires pour les filtres
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
            $category = $categories->firstWhere('id', $categoryId);
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

        // Retourner JSON avec les partiels rendus
        return response()->json([
            'metrics' => view('esbtp.paiements.partials.suivi-metrics', compact('vueEnsemble'))->render(),
            'content' => view('esbtp.paiements.partials.suivi-content', compact(
                'vueEnsemble',
                'statistiquesCategories',
                'detailsCategorie',
                'categoryId'
            ))->render(),
            'url' => $request->fullUrl(),
            'last_updated_at' => now()->toIso8601String(),
        ]);
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

}
