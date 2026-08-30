<?php

namespace App\Services;

use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;

class PaymentStatsService
{
    /**
     * Calculer la vue d'ensemble globale
     */
    public function calculerVueEnsemble($inscriptions)
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
                $montantAttendu = FeeCalculationService::getMontantAttendu($category, $inscription);
                $estConcerne = $montantAttendu > 0;

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
     * Version optimisée de calculerVueEnsemble - évite les requêtes N+1
     */
    public function calculerVueEnsembleOptimisee($inscriptions, $categories, $configurations, $subscriptions, $paiements)
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
                [$estConcerne, $montantAttendu] = $this->resolvePreloadedCharge($inscription, $category, $subscriptions, $configurations);

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
     * Version optimisée de analyserCategorieDetaille - évite les requêtes N+1
     */
    public function analyserCategorieDetailleOptimisee($category, $inscriptions, $configurations, $subscriptions, $paiements)
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
            [$estConcerne, $montantAttendu] = $this->resolvePreloadedCharge($inscription, $category, $subscriptions, $configurations);

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
    public function calculerStatistiquesCategoriesOptimisees($inscriptions, $categories, $configurations, $subscriptions, $paiements)
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
                [$estConcerne, $montantAttendu] = $this->resolvePreloadedCharge($inscription, $category, $subscriptions, $configurations);

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
     * Calcule les frais attendus et payés pour une inscription donnée.
     */
    public function calculateFraisForInscription($inscription)
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
            $expectedAmount = FeeCalculationService::getMontantAttendu($category, $inscription);

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
    public function calculateReliquatsStats($inscriptions)
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

    private function resolvePreloadedCharge($inscription, $category, $subscriptions, $configurations): array
    {
        $amount = (float) FeeCalculationService::getMontantAttendu(
            $category,
            $inscription,
            $configurations,
            $subscriptions,
        );

        return [$amount > 0, $amount];
    }
}
