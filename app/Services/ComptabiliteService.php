<?php

namespace App\Services;

use App\Models\ESBTPPaiement;
use App\Models\ESBTPDepense;
use App\Models\ESBTPSalaire;
use App\Models\ESBTPFraisScolarite;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPInscription;
use App\Models\ESBTPCategorieDepense;
use App\Models\ESBTPKPI;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ComptabiliteService
{
    /**
     * Calcule les KPIs financiers avancés
     */
    public function calculerKPIsAvances($anneeId = null)
    {
        $annee = $anneeId ? 
            ESBTPAnneeUniversitaire::find($anneeId) : 
            ESBTPAnneeUniversitaire::where('est_actif', true)->first();

        if (!$annee) {
            return $this->getDefaultKPIs();
        }

        return [
            'recettes' => $this->calculerStatsRecettes($annee),
            'depenses' => $this->calculerStatsDepenses($annee),
            'paiements' => $this->calculerStatsPaiements($annee),
            'performance' => $this->calculerIndicateursPerformance($annee),
            'previsions' => $this->calculerPrevisions($annee),
            'alertes' => $this->detecterAlertes($annee)
        ];
    }

    /**
     * Calcule les statistiques des recettes
     */
    private function calculerStatsRecettes($annee)
    {
        $totalPaiements = ESBTPPaiement::where('annee_universitaire_id', $annee->id)
            ->where('statut', 'completé')
            ->sum('montant');

        $paiementsMensuels = ESBTPPaiement::where('annee_universitaire_id', $annee->id)
            ->where('statut', 'completé')
            ->whereMonth('date_paiement', Carbon::now()->month)
            ->whereYear('date_paiement', Carbon::now()->year)
            ->sum('montant');

        $totalPrevisionnel = ESBTPFraisScolarite::where('annee_universitaire_id', $annee->id)
            ->where('est_actif', true)
            ->sum('montant_total');

        $tauxRecouvrement = $totalPrevisionnel > 0 ? 
            round(($totalPaiements / $totalPrevisionnel) * 100, 2) : 0;

        return [
            'total' => $totalPaiements,
            'mensuel' => $paiementsMensuels,
            'previsionnel' => $totalPrevisionnel,
            'taux_recouvrement' => $tauxRecouvrement,
            'objectif_atteint' => $tauxRecouvrement >= 85
        ];
    }

    /**
     * Calcule les statistiques des dépenses
     */
    private function calculerStatsDepenses($annee)
    {
        $dateDebut = Carbon::parse($annee->date_debut);
        $dateFin = Carbon::parse($annee->date_fin);

        $totalDepenses = ESBTPDepense::whereBetween('date_depense', [$dateDebut, $dateFin])
            ->whereIn('statut', ['validée', 'approuve'])
            ->sum('montant');

        $depensesMensuelles = ESBTPDepense::whereMonth('date_depense', Carbon::now()->month)
            ->whereYear('date_depense', Carbon::now()->year)
            ->whereIn('statut', ['validée', 'approuve'])
            ->sum('montant');

        // Dépenses par catégorie
        $depensesParCategorie = ESBTPDepense::with('categorie')
            ->whereBetween('date_depense', [$dateDebut, $dateFin])
            ->whereIn('statut', ['validée', 'approuve'])
            ->get()
            ->groupBy('categorie.nom')
            ->map(function ($group) {
                return $group->sum('montant');
            });

        return [
            'total' => $totalDepenses,
            'mensuel' => $depensesMensuelles,
            'par_categorie' => $depensesParCategorie,
            'budget_restant' => $this->calculerBudgetRestant($annee, $totalDepenses)
        ];
    }

    /**
     * Calcule les statistiques des paiements
     */
    private function calculerStatsPaiements($annee)
    {
        $totalInscriptions = ESBTPInscription::where('annee_universitaire_id', $annee->id)->count();

        $etudiantsPayeComplet = DB::table('esbtp_inscriptions')
            ->join('esbtp_etudiants', 'esbtp_inscriptions.etudiant_id', '=', 'esbtp_etudiants.id')
            ->join('esbtp_paiements', 'esbtp_inscriptions.etudiant_id', '=', 'esbtp_paiements.etudiant_id')
            ->where('esbtp_inscriptions.annee_universitaire_id', $annee->id)
            ->groupBy('esbtp_etudiants.id')
            ->havingRaw('SUM(esbtp_paiements.montant) >= (
                SELECT esbtp_frais_scolarite.montant_total
                FROM esbtp_frais_scolarite
                WHERE esbtp_frais_scolarite.filiere_id = esbtp_inscriptions.filiere_id
                AND esbtp_frais_scolarite.niveau_id = esbtp_inscriptions.niveau_id
                AND esbtp_frais_scolarite.annee_universitaire_id = esbtp_inscriptions.annee_universitaire_id
            )')
            ->count();

        $etudiantsPayePartiel = DB::table('esbtp_inscriptions')
            ->join('esbtp_etudiants', 'esbtp_inscriptions.etudiant_id', '=', 'esbtp_etudiants.id')
            ->join('esbtp_paiements', 'esbtp_inscriptions.etudiant_id', '=', 'esbtp_paiements.etudiant_id')
            ->where('esbtp_inscriptions.annee_universitaire_id', $annee->id)
            ->groupBy('esbtp_etudiants.id')
            ->havingRaw('SUM(esbtp_paiements.montant) > 0 AND SUM(esbtp_paiements.montant) < (
                SELECT esbtp_frais_scolarite.montant_total
                FROM esbtp_frais_scolarite
                WHERE esbtp_frais_scolarite.filiere_id = esbtp_inscriptions.filiere_id
                AND esbtp_frais_scolarite.niveau_id = esbtp_inscriptions.niveau_id
                AND esbtp_frais_scolarite.annee_universitaire_id = esbtp_inscriptions.annee_universitaire_id
            )')
            ->count();

        $etudiantsImpaye = $totalInscriptions - $etudiantsPayeComplet - $etudiantsPayePartiel;

        return [
            'total' => $totalInscriptions,
            'complets' => $etudiantsPayeComplet,
            'partiels' => $etudiantsPayePartiel,
            'impayés' => $etudiantsImpaye,
            'taux_recouvrement' => $totalInscriptions > 0 ? 
                round(($etudiantsPayeComplet / $totalInscriptions) * 100, 2) : 0
        ];
    }

    /**
     * Calcule les indicateurs de performance
     */
    private function calculerIndicateursPerformance($annee)
    {
        $recettes = $this->calculerStatsRecettes($annee);
        $depenses = $this->calculerStatsDepenses($annee);

        $resultatNet = $recettes['total'] - $depenses['total'];
        $margeNette = $recettes['total'] > 0 ? 
            round(($resultatNet / $recettes['total']) * 100, 2) : 0;

        return [
            'resultat_net' => $resultatNet,
            'marge_nette' => $margeNette,
            'rentabilite' => $resultatNet > 0 ? 'positive' : 'negative',
            'croissance_mensuelle' => $this->calculerCroissanceMensuelle($annee)
        ];
    }

    /**
     * Génère les prévisions financières
     */
    public function calculerPrevisions($annee, $nombreMois = 3)
    {
        // Moyenne des 6 derniers mois
        $moyenneRecettes = ESBTPPaiement::where('annee_universitaire_id', $annee->id)
            ->where('date_paiement', '>=', Carbon::now()->subMonths(6))
            ->where('statut', 'completé')
            ->avg(DB::raw('MONTH(date_paiement)'));

        $moyenneDepenses = ESBTPDepense::where('date_depense', '>=', Carbon::now()->subMonths(6))
            ->whereIn('statut', ['validée', 'approuve'])
            ->avg(DB::raw('MONTH(date_depense)'));

        $previsions = [];
        for ($i = 1; $i <= $nombreMois; $i++) {
            $moisFutur = Carbon::now()->addMonths($i);
            $previsions[$moisFutur->format('Y-m')] = [
                'recettes_prevues' => $moyenneRecettes * 1.05, // Légère croissance
                'depenses_prevues' => $moyenneDepenses * 1.02, // Légère inflation
                'resultat_prevu' => ($moyenneRecettes * 1.05) - ($moyenneDepenses * 1.02)
            ];
        }

        return $previsions;
    }

    /**
     * Détecte les alertes financières
     */
    private function detecterAlertes($annee)
    {
        $alertes = [];
        $recettes = $this->calculerStatsRecettes($annee);
        $paiements = $this->calculerStatsPaiements($annee);

        // Alerte taux de recouvrement faible
        if ($recettes['taux_recouvrement'] < 70) {
            $alertes[] = [
                'type' => 'warning',
                'message' => 'Taux de recouvrement faible: ' . $recettes['taux_recouvrement'] . '%',
                'action' => 'Intensifier les relances'
            ];
        }

        // Alerte grand nombre d'impayés
        if ($paiements['impayés'] > ($paiements['total'] * 0.3)) {
            $alertes[] = [
                'type' => 'danger',
                'message' => $paiements['impayés'] . ' étudiants n\'ont rien payé',
                'action' => 'Campagne de relance urgente'
            ];
        }

        return $alertes;
    }

    /**
     * Sauvegarde les KPIs calculés
     */
    public function sauvegarderKPIs($kpis, $periode = 'jour')
    {
        foreach ($kpis as $nom => $donnees) {
            if (is_array($donnees)) {
                foreach ($donnees as $sousNom => $valeur) {
                    if (is_numeric($valeur)) {
                        ESBTPKPI::updateOrCreate(
                            [
                                'nom' => $nom . '.' . $sousNom,
                                'periode' => $periode,
                                'date_calcul' => Carbon::now()->format('Y-m-d')
                            ],
                            [
                                'valeur' => $valeur,
                                'type' => $this->determinerTypeKPI($nom, $sousNom),
                                'metadata' => json_encode(['source' => 'auto_calculation'])
                            ]
                        );
                    }
                }
            }
        }
    }

    /**
     * Génère automatiquement les factures depuis les inscriptions
     */
    public function genererFacturesAutomatiques($anneeId = null)
    {
        // Cette méthode sera implémentée pour la facturation automatique
        // selon les configurations de frais de scolarité
        return ['status' => 'success', 'factures_generees' => 0];
    }

    /**
     * Méthodes privées utilitaires
     */
    private function getDefaultKPIs()
    {
        return [
            'recettes' => ['total' => 0, 'mensuel' => 0, 'taux_recouvrement' => 0],
            'depenses' => ['total' => 0, 'mensuel' => 0],
            'paiements' => ['total' => 0, 'complets' => 0, 'impayés' => 0],
            'performance' => ['resultat_net' => 0, 'marge_nette' => 0],
            'alertes' => []
        ];
    }

    private function calculerBudgetRestant($annee, $totalDepenses)
    {
        // Logique pour calculer le budget restant
        // Peut être configuré via la table de configuration
        return 0;
    }

    private function calculerCroissanceMensuelle($annee)
    {
        // Logique pour calculer la croissance mensuelle
        return 0;
    }

    private function determinerTypeKPI($nom, $sousNom)
    {
        if (strpos($nom, 'recette') !== false) return 'recette';
        if (strpos($nom, 'depense') !== false) return 'depense';
        if (strpos($nom, 'performance') !== false) return 'performance';
        return 'ratio';
    }
}
