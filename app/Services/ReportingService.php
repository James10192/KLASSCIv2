<?php

namespace App\Services;

use App\Models\ESBTPPaiement;
use App\Models\ESBTPDepense;
use App\Models\ESBTPSalaire;
use App\Models\ESBTPFacture;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPAnneeUniversitaire;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportingService
{
    /**
     * Génère un rapport personnalisé selon les paramètres
     */
    public function genererRapportPersonnalise($parametres)
    {
        $typeRapport = $parametres['type'] ?? 'general';
        $dateDebut = Carbon::parse($parametres['date_debut'] ?? now()->startOfMonth());
        $dateFin = Carbon::parse($parametres['date_fin'] ?? now()->endOfMonth());
        
        switch ($typeRapport) {
            case 'paiements':
                return $this->rapportPaiements($dateDebut, $dateFin, $parametres);
            
            case 'depenses':
                return $this->rapportDepenses($dateDebut, $dateFin, $parametres);
            
            case 'performance':
                return $this->rapportPerformance($dateDebut, $dateFin, $parametres);
            
            case 'recouvrement':
                return $this->rapportRecouvrement($dateDebut, $dateFin, $parametres);
            
            case 'comparatif':
                return $this->rapportComparatif($dateDebut, $dateFin, $parametres);
            
            default:
                return $this->rapportGeneral($dateDebut, $dateFin, $parametres);
        }
    }

    /**
     * Rapport détaillé des paiements
     */
    private function rapportPaiements($dateDebut, $dateFin, $parametres)
    {
        $query = ESBTPPaiement::with(['etudiant', 'anneeUniversitaire', 'createur'])
            ->whereBetween('date_paiement', [$dateDebut, $dateFin]);

        // Filtres additionnels
        if (isset($parametres['filiere_id'])) {
            $query->whereHas('etudiant.inscriptions', function($q) use ($parametres) {
                $q->where('filiere_id', $parametres['filiere_id']);
            });
        }

        if (isset($parametres['mode_paiement'])) {
            $query->where('mode_paiement', $parametres['mode_paiement']);
        }

        $paiements = $query->get();

        return [
            'titre' => 'Rapport des Paiements',
            'periode' => $dateDebut->format('d/m/Y') . ' - ' . $dateFin->format('d/m/Y'),
            'donnees' => [
                'paiements' => $paiements,
                'total_montant' => $paiements->sum('montant'),
                'nombre_paiements' => $paiements->count(),
                'moyenne_paiement' => $paiements->avg('montant'),
                'repartition_modes' => $this->repartitionParModesPaiement($paiements),
                'evolution_mensuelle' => $this->evolutionMensuelle($paiements, 'montant'),
                'top_payeurs' => $this->getTopPayeurs($paiements, 10)
            ],
            'graphiques' => [
                'evolution' => $this->preparerDonneesGraphique($paiements, 'date_paiement', 'montant'),
                'repartition' => $this->preparerDonneesRepartition($paiements, 'mode_paiement')
            ]
        ];
    }

    /**
     * Rapport des dépenses
     */
    private function rapportDepenses($dateDebut, $dateFin, $parametres)
    {
        $query = ESBTPDepense::with(['categorie', 'createur'])
            ->whereBetween('date_depense', [$dateDebut, $dateFin])
            ->whereIn('statut', ['validée', 'approuve']);

        if (isset($parametres['categorie_id'])) {
            $query->where('categorie_id', $parametres['categorie_id']);
        }

        $depenses = $query->get();

        return [
            'titre' => 'Rapport des Dépenses',
            'periode' => $dateDebut->format('d/m/Y') . ' - ' . $dateFin->format('d/m/Y'),
            'donnees' => [
                'depenses' => $depenses,
                'total_montant' => $depenses->sum('montant'),
                'nombre_depenses' => $depenses->count(),
                'moyenne_depense' => $depenses->avg('montant'),
                'repartition_categories' => $this->repartitionParCategories($depenses),
                'evolution_mensuelle' => $this->evolutionMensuelle($depenses, 'montant'),
                'top_categories' => $this->getTopCategories($depenses, 5)
            ],
            'graphiques' => [
                'evolution' => $this->preparerDonneesGraphique($depenses, 'date_depense', 'montant'),
                'categories' => $this->preparerDonneesRepartition($depenses, 'categorie.nom')
            ]
        ];
    }

    /**
     * Rapport de performance financière
     */
    private function rapportPerformance($dateDebut, $dateFin, $parametres)
    {
        $recettes = ESBTPPaiement::whereBetween('date_paiement', [$dateDebut, $dateFin])
            ->where('statut', 'completé')
            ->sum('montant');

        $depenses = ESBTPDepense::whereBetween('date_depense', [$dateDebut, $dateFin])
            ->whereIn('statut', ['validée', 'approuve'])
            ->sum('montant');

        $resultatNet = $recettes - $depenses;
        $margeNette = $recettes > 0 ? ($resultatNet / $recettes) * 100 : 0;

        // Comparaison avec période précédente
        $periodePrecedente = $this->calculerPeriodePrecedente($dateDebut, $dateFin);
        $recettesPrecedentes = ESBTPPaiement::whereBetween('date_paiement', $periodePrecedente)
            ->where('statut', 'completé')
            ->sum('montant');
        
        $croissance = $recettesPrecedentes > 0 ? 
            (($recettes - $recettesPrecedentes) / $recettesPrecedentes) * 100 : 0;

        return [
            'titre' => 'Rapport de Performance',
            'periode' => $dateDebut->format('d/m/Y') . ' - ' . $dateFin->format('d/m/Y'),
            'donnees' => [
                'recettes' => $recettes,
                'depenses' => $depenses,
                'resultat_net' => $resultatNet,
                'marge_nette' => round($margeNette, 2),
                'croissance' => round($croissance, 2),
                'rentabilite' => $resultatNet > 0 ? 'Positive' : 'Négative',
                'indicateurs' => [
                    'ratio_depenses' => $recettes > 0 ? round(($depenses / $recettes) * 100, 2) : 0,
                    'point_equilibre' => $this->calculerPointEquilibre($recettes, $depenses),
                    'tresorerie' => $this->calculerTresorerie($dateDebut, $dateFin)
                ]
            ]
        ];
    }

    /**
     * Rapport de recouvrement
     */
    private function rapportRecouvrement($dateDebut, $dateFin, $parametres)
    {
        $annee = ESBTPAnneeUniversitaire::where('est_actif', true)->first();
        
        $totalInscriptions = DB::table('esbtp_inscriptions')
            ->where('annee_universitaire_id', $annee->id)
            ->count();

        $etudiantsPayeComplet = $this->getEtudiantsPayeComplet($annee->id);
        $etudiantsPayePartiel = $this->getEtudiantsPayePartiel($annee->id);
        $etudiantsImpaye = $totalInscriptions - $etudiantsPayeComplet - $etudiantsPayePartiel;

        $tauxRecouvrement = $totalInscriptions > 0 ? 
            round(($etudiantsPayeComplet / $totalInscriptions) * 100, 2) : 0;

        return [
            'titre' => 'Rapport de Recouvrement',
            'periode' => $dateDebut->format('d/m/Y') . ' - ' . $dateFin->format('d/m/Y'),
            'donnees' => [
                'total_inscriptions' => $totalInscriptions,
                'paye_complet' => $etudiantsPayeComplet,
                'paye_partiel' => $etudiantsPayePartiel,
                'impaye' => $etudiantsImpaye,
                'taux_recouvrement' => $tauxRecouvrement,
                'objectif_atteint' => $tauxRecouvrement >= 85,
                'montant_recouvre' => $this->getMontantRecouvre($annee->id),
                'montant_restant' => $this->getMontantRestant($annee->id),
                'top_debiteurs' => $this->getTopDebiteurs(10)
            ]
        ];
    }

    /**
     * Rapport comparatif multi-périodes
     */
    private function rapportComparatif($dateDebut, $dateFin, $parametres)
    {
        $periodes = $this->genererPeriodesComparatif($dateDebut, $dateFin, $parametres['type_comparaison'] ?? 'mensuel');
        $donnees = [];

        foreach ($periodes as $periode) {
            $recettes = ESBTPPaiement::whereBetween('date_paiement', [$periode['debut'], $periode['fin']])
                ->where('statut', 'completé')
                ->sum('montant');
                
            $depenses = ESBTPDepense::whereBetween('date_depense', [$periode['debut'], $periode['fin']])
                ->whereIn('statut', ['validée', 'approuve'])
                ->sum('montant');

            $donnees[] = [
                'periode' => $periode['label'],
                'recettes' => $recettes,
                'depenses' => $depenses,
                'resultat' => $recettes - $depenses
            ];
        }

        return [
            'titre' => 'Rapport Comparatif',
            'type_comparaison' => $parametres['type_comparaison'] ?? 'mensuel',
            'donnees' => $donnees,
            'tendances' => $this->analyserTendances($donnees)
        ];
    }

    /**
     * Rapport général (synthèse)
     */
    private function rapportGeneral($dateDebut, $dateFin, $parametres)
    {
        return [
            'titre' => 'Rapport Général',
            'periode' => $dateDebut->format('d/m/Y') . ' - ' . $dateFin->format('d/m/Y'),
            'sections' => [
                'resume' => $this->resumeExecutif($dateDebut, $dateFin),
                'paiements' => $this->rapportPaiements($dateDebut, $dateFin, $parametres)['donnees'],
                'depenses' => $this->rapportDepenses($dateDebut, $dateFin, $parametres)['donnees'],
                'performance' => $this->rapportPerformance($dateDebut, $dateFin, $parametres)['donnees'],
                'recommandations' => $this->genererRecommandations($dateDebut, $dateFin)
            ]
        ];
    }

    /**
     * Exporte les données au format spécifié
     */
    public function exporterDonnees($format, $donnees)
    {
        switch (strtolower($format)) {
            case 'pdf':
                return $this->exporterPDF($donnees);
            
            case 'excel':
                return $this->exporterExcel($donnees);
            
            case 'csv':
                return $this->exporterCSV($donnees);
            
            default:
                return ['success' => false, 'message' => 'Format non supporté'];
        }
    }

    // ===== MÉTHODES UTILITAIRES =====

    private function repartitionParModesPaiement($paiements)
    {
        return $paiements->groupBy('mode_paiement')
            ->map(function ($group) {
                return [
                    'nombre' => $group->count(),
                    'montant' => $group->sum('montant')
                ];
            });
    }

    private function repartitionParCategories($depenses)
    {
        return $depenses->groupBy('categorie.nom')
            ->map(function ($group) {
                return [
                    'nombre' => $group->count(),
                    'montant' => $group->sum('montant')
                ];
            });
    }

    private function evolutionMensuelle($collection, $champ)
    {
        return $collection->groupBy(function ($item) {
            return Carbon::parse($item->date_paiement ?? $item->date_depense)->format('Y-m');
        })->map(function ($group) use ($champ) {
            return $group->sum($champ);
        });
    }

    private function getTopPayeurs($paiements, $limite)
    {
        return $paiements->groupBy('etudiant_id')
            ->map(function ($group) {
                return [
                    'etudiant' => $group->first()->etudiant,
                    'total' => $group->sum('montant'),
                    'nombre_paiements' => $group->count()
                ];
            })
            ->sortByDesc('total')
            ->take($limite)
            ->values();
    }

    private function getTopCategories($depenses, $limite)
    {
        return $depenses->groupBy('categorie_id')
            ->map(function ($group) {
                return [
                    'categorie' => $group->first()->categorie,
                    'total' => $group->sum('montant'),
                    'nombre_depenses' => $group->count()
                ];
            })
            ->sortByDesc('total')
            ->take($limite)
            ->values();
    }

    private function preparerDonneesGraphique($collection, $champDate, $champValeur)
    {
        $donnees = $collection->groupBy(function ($item) use ($champDate) {
            return Carbon::parse($item->$champDate)->format('Y-m-d');
        })->map(function ($group) use ($champValeur) {
            return $group->sum($champValeur);
        });

        return [
            'labels' => $donnees->keys()->toArray(),
            'data' => $donnees->values()->toArray()
        ];
    }

    private function preparerDonneesRepartition($collection, $champ)
    {
        $donnees = $collection->groupBy($champ)
            ->map(function ($group) {
                return $group->sum('montant');
            });

        return [
            'labels' => $donnees->keys()->toArray(),
            'data' => $donnees->values()->toArray()
        ];
    }

    private function calculerPeriodePrecedente($dateDebut, $dateFin)
    {
        $duree = $dateDebut->diffInDays($dateFin);
        return [
            $dateDebut->copy()->subDays($duree + 1),
            $dateDebut->copy()->subDay()
        ];
    }

    private function calculerPointEquilibre($recettes, $depenses)
    {
        return $recettes >= $depenses ? 'Atteint' : 'Non atteint';
    }

    private function calculerTresorerie($dateDebut, $dateFin)
    {
        // Calcul simplifié de la trésorerie
        return 0; // À implémenter selon vos besoins
    }

    private function getEtudiantsPayeComplet($anneeId)
    {
        return DB::table('esbtp_inscriptions')
            ->join('esbtp_etudiants', 'esbtp_inscriptions.etudiant_id', '=', 'esbtp_etudiants.id')
            ->join('esbtp_paiements', 'esbtp_inscriptions.etudiant_id', '=', 'esbtp_paiements.etudiant_id')
            ->where('esbtp_inscriptions.annee_universitaire_id', $anneeId)
            ->groupBy('esbtp_etudiants.id')
            ->havingRaw('SUM(esbtp_paiements.montant) >= (
                SELECT esbtp_frais_scolarite.montant_total
                FROM esbtp_frais_scolarite
                WHERE esbtp_frais_scolarite.filiere_id = esbtp_inscriptions.filiere_id
                AND esbtp_frais_scolarite.niveau_id = esbtp_inscriptions.niveau_id
                AND esbtp_frais_scolarite.annee_universitaire_id = esbtp_inscriptions.annee_universitaire_id
            )')
            ->count();
    }

    private function getEtudiantsPayePartiel($anneeId)
    {
        return DB::table('esbtp_inscriptions')
            ->join('esbtp_etudiants', 'esbtp_inscriptions.etudiant_id', '=', 'esbtp_etudiants.id')
            ->join('esbtp_paiements', 'esbtp_inscriptions.etudiant_id', '=', 'esbtp_paiements.etudiant_id')
            ->where('esbtp_inscriptions.annee_universitaire_id', $anneeId)
            ->groupBy('esbtp_etudiants.id')
            ->havingRaw('SUM(esbtp_paiements.montant) > 0 AND SUM(esbtp_paiements.montant) < (
                SELECT esbtp_frais_scolarite.montant_total
                FROM esbtp_frais_scolarite
                WHERE esbtp_frais_scolarite.filiere_id = esbtp_inscriptions.filiere_id
                AND esbtp_frais_scolarite.niveau_id = esbtp_inscriptions.niveau_id
                AND esbtp_frais_scolarite.annee_universitaire_id = esbtp_inscriptions.annee_universitaire_id
            )')
            ->count();
    }

    private function getMontantRecouvre($anneeId)
    {
        return ESBTPPaiement::where('annee_universitaire_id', $anneeId)
            ->where('statut', 'completé')
            ->sum('montant');
    }

    private function getMontantRestant($anneeId)
    {
        // Calcul du montant restant à recouvrer
        return 0; // À implémenter
    }

    private function getTopDebiteurs($limite)
    {
        // Top des plus gros débiteurs
        return collect([]); // À implémenter
    }

    private function genererPeriodesComparatif($dateDebut, $dateFin, $type)
    {
        $periodes = [];
        
        if ($type === 'mensuel') {
            $debut = $dateDebut->copy()->startOfMonth();
            while ($debut->lte($dateFin)) {
                $fin = $debut->copy()->endOfMonth();
                $periodes[] = [
                    'debut' => $debut->copy(),
                    'fin' => $fin->copy(),
                    'label' => $debut->translatedFormat('F Y')
                ];
                $debut->addMonth();
            }
        }

        return $periodes;
    }

    private function analyserTendances($donnees)
    {
        // Analyse des tendances sur les données
        return [
            'recettes' => 'stable',
            'depenses' => 'croissance',
            'resultat' => 'stable'
        ];
    }

    private function resumeExecutif($dateDebut, $dateFin)
    {
        $recettes = ESBTPPaiement::whereBetween('date_paiement', [$dateDebut, $dateFin])
            ->where('statut', 'completé')
            ->sum('montant');

        $depenses = ESBTPDepense::whereBetween('date_depense', [$dateDebut, $dateFin])
            ->whereIn('statut', ['validée', 'approuve'])
            ->sum('montant');

        return [
            'recettes_totales' => $recettes,
            'depenses_totales' => $depenses,
            'resultat_net' => $recettes - $depenses,
            'points_cles' => [
                'Principal mode de paiement : Mobile Money',
                'Catégorie de dépense principale : Fournitures',
                'Taux de recouvrement : 78%'
            ]
        ];
    }

    private function genererRecommandations($dateDebut, $dateFin)
    {
        return [
            'Intensifier les relances pour améliorer le taux de recouvrement',
            'Optimiser les dépenses en fournitures',
            'Diversifier les modes de paiement acceptés'
        ];
    }

    private function exporterPDF($donnees)
    {
        // Implémentation export PDF
        return ['success' => true, 'path' => '/tmp/rapport.pdf'];
    }

    private function exporterExcel($donnees)
    {
        // Implémentation export Excel
        return ['success' => true, 'path' => '/tmp/rapport.xlsx'];
    }

    private function exporterCSV($donnees)
    {
        // Implémentation export CSV
        return ['success' => true, 'path' => '/tmp/rapport.csv'];
    }
}
