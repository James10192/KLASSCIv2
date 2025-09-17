<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ESBTPInscription;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPFraisCategory;

class VerifyPaymentStats extends Command
{
    protected $signature = 'esbtp:verify-stats {--filiere=} {--niveau=}';
    protected $description = 'Vérifier les statistiques de paiement et les comparer avec les KPIs';

    public function handle()
    {
        $this->info('=== VÉRIFICATION DES STATISTIQUES DE PAIEMENT ===');

        // Récupérer l'année en cours
        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        if (!$anneeEnCours) {
            $this->error('Aucune année universitaire en cours trouvée');
            return;
        }

        $this->info("Année en cours: {$anneeEnCours->annee_debut}/{$anneeEnCours->annee_fin}");
        $this->line('');

        // Récupérer les inscriptions de l'année en cours
        $query = ESBTPInscription::with(['etudiant.user', 'filiere', 'niveauEtude'])
            ->where('annee_universitaire_id', $anneeEnCours->id)
            ->whereIn('status', ['active', 'en_attente', 'validée']);

        if ($this->option('filiere')) {
            $query->where('filiere_id', $this->option('filiere'));
        }
        if ($this->option('niveau')) {
            $query->where('niveau_id', $this->option('niveau'));
        }

        $inscriptions = $query->get();

        $this->info("📊 Nombre total d'inscriptions trouvées: " . $inscriptions->count());
        $this->line('');

        // Statistiques globales
        $montantTotalAttendu = 0;
        $montantTotalPaye = 0;
        $etudiantsEnRegle = 0;
        $etudiantsPartiels = 0;
        $etudiantsImpayes = 0;

        $details = [];

        foreach ($inscriptions as $inscription) {
            $this->line("----------------------------------------");
            $etudiantName = $inscription->etudiant->user->name ?? 'Nom non trouvé';
            $etudiantMatricule = $inscription->etudiant->matricule ?? 'Matricule non trouvé';
            $filiereNom = $inscription->filiere->nom ?? 'Filière non trouvée';
            $niveauNom = $inscription->niveauEtude->nom ?? 'Niveau non trouvé';

            $this->info("Étudiant: {$etudiantName} (Matricule: {$etudiantMatricule})");
            $this->info("Filière: {$filiereNom} - Niveau: {$niveauNom}");
            $this->info("Statut inscription: {$inscription->status}");

            // Récupérer toutes les souscriptions actives de cet étudiant
            $subscriptions = ESBTPFraisSubscription::where('inscription_id', $inscription->id)
                ->where('is_active', true)
                ->with('fraisCategory')
                ->get();

            $montantAttenduEtudiant = 0;
            $montantPayeEtudiant = 0;

            $this->line("\n  📋 SOUSCRIPTIONS:");

            if ($subscriptions->isEmpty()) {
                $this->warn("  ⚠️  Aucune souscription trouvée pour cet étudiant!");

                // Vérifier s'il devrait avoir des frais obligatoires
                $fraisObligatoires = ESBTPFraisCategory::where('is_mandatory', true)
                    ->where('is_active', true)
                    ->get();

                if ($fraisObligatoires->isNotEmpty()) {
                    $this->error("  ❌ Frais obligatoires manquants: " . $fraisObligatoires->pluck('name')->join(', '));
                }
            } else {
                foreach ($subscriptions as $subscription) {
                    $categoryName = $subscription->fraisCategory->name ?? 'Catégorie inconnue';
                    $this->info("  - {$categoryName}: " . number_format($subscription->amount, 0, ',', ' ') . " FCFA");
                    $montantAttenduEtudiant += $subscription->amount;

                    // Calculer les paiements pour cette catégorie
                    $paiements = ESBTPPaiement::where('inscription_id', $inscription->id)
                        ->where('frais_category_id', $subscription->frais_category_id)
                        ->whereIn('status', ['validé', 'en_attente'])
                        ->get();

                    $montantPayeCategorie = $paiements->sum('montant');

                    if ($montantPayeCategorie > 0) {
                        $this->line("    → Payé: " . number_format($montantPayeCategorie, 0, ',', ' ') . " FCFA");
                        foreach ($paiements as $paiement) {
                            $this->line("      • " . number_format($paiement->montant, 0, ',', ' ') . " FCFA ({$paiement->status}) - {$paiement->date_paiement}");
                        }
                    } else {
                        $this->warn("    → Non payé");
                    }

                    $montantPayeEtudiant += $montantPayeCategorie;
                }
            }

            // Vérifier aussi les paiements sans souscription correspondante
            $paiementsSansSubscription = ESBTPPaiement::where('inscription_id', $inscription->id)
                ->whereIn('status', ['validé', 'en_attente'])
                ->whereNotIn('frais_category_id', $subscriptions->pluck('frais_category_id'))
                ->with('fraisCategory')
                ->get();

            if ($paiementsSansSubscription->isNotEmpty()) {
                $this->warn("\n  ⚠️  PAIEMENTS SANS SOUSCRIPTION:");
                foreach ($paiementsSansSubscription as $paiement) {
                    $categoryName = $paiement->fraisCategory->name ?? 'Catégorie inconnue';
                    $this->warn("  - {$categoryName}: " . number_format($paiement->montant, 0, ',', ' ') . " FCFA ({$paiement->status})");
                    $montantPayeEtudiant += $paiement->montant;
                }
            }

            // Résumé pour cet étudiant
            $this->line("\n  💰 RÉSUMÉ:");
            $this->info("  Montant attendu: " . number_format($montantAttenduEtudiant, 0, ',', ' ') . " FCFA");
            $this->info("  Montant payé: " . number_format($montantPayeEtudiant, 0, ',', ' ') . " FCFA");

            $solde = $montantAttenduEtudiant - $montantPayeEtudiant;
            if ($solde > 0) {
                $this->error("  Solde restant: " . number_format($solde, 0, ',', ' ') . " FCFA");
            } elseif ($solde < 0) {
                $this->warn("  Trop-perçu: " . number_format(abs($solde), 0, ',', ' ') . " FCFA");
            } else {
                $this->info("  ✅ À jour");
            }

            // Catégoriser l'étudiant
            if ($montantAttenduEtudiant > 0) {
                if ($montantPayeEtudiant >= $montantAttenduEtudiant) {
                    $etudiantsEnRegle++;
                    $statut = "EN RÈGLE";
                } elseif ($montantPayeEtudiant > 0) {
                    $etudiantsPartiels++;
                    $statut = "PARTIEL";
                } else {
                    $etudiantsImpayes++;
                    $statut = "IMPAYÉ";
                }
            } else {
                // Pas de souscription = pas de frais attendus
                $etudiantsEnRegle++;
                $statut = "EN RÈGLE (pas de frais)";
            }

            $this->info("  Statut: {$statut}");

            // Ajouter aux totaux
            $montantTotalAttendu += $montantAttenduEtudiant;
            $montantTotalPaye += $montantPayeEtudiant;

            $details[] = [
                'etudiant' => $etudiantName,
                'montant_attendu' => $montantAttenduEtudiant,
                'montant_paye' => $montantPayeEtudiant,
                'statut' => $statut
            ];
        }

        // Afficher le résumé global
        $this->line("\n========================================");
        $this->info("📊 STATISTIQUES GLOBALES");
        $this->line("========================================");

        $totalEtudiants = $inscriptions->count();

        $this->info("Nombre total d'étudiants: {$totalEtudiants}");
        $this->line('');

        $this->info("RÉPARTITION PAR STATUT:");
        $this->info("  ✅ En règle: {$etudiantsEnRegle} (" . ($totalEtudiants > 0 ? round($etudiantsEnRegle / $totalEtudiants * 100, 1) : 0) . "%)");
        $this->info("  ⚠️  Partiels: {$etudiantsPartiels} (" . ($totalEtudiants > 0 ? round($etudiantsPartiels / $totalEtudiants * 100, 1) : 0) . "%)");
        $this->info("  ❌ Impayés: {$etudiantsImpayes} (" . ($totalEtudiants > 0 ? round($etudiantsImpayes / $totalEtudiants * 100, 1) : 0) . "%)");
        $this->line('');

        $this->info("MONTANTS FINANCIERS:");
        $this->info("  💵 Total attendu: " . number_format($montantTotalAttendu, 0, ',', ' ') . " FCFA");
        $this->info("  💰 Total payé: " . number_format($montantTotalPaye, 0, ',', ' ') . " FCFA");
        $this->info("  📊 Restant à payer: " . number_format($montantTotalAttendu - $montantTotalPaye, 0, ',', ' ') . " FCFA");

        $tauxRecouvrement = $montantTotalAttendu > 0 ? round($montantTotalPaye / $montantTotalAttendu * 100, 1) : 0;
        $this->info("  📈 Taux de recouvrement: {$tauxRecouvrement}%");

        $this->line("\n========================================");
        $this->info("COMPARAISON AVEC VOS KPIs:");
        $this->line("========================================");

        $this->table(
            ['Indicateur', 'KPI Affiché', 'Calcul Réel', 'Différence'],
            [
                ['En règle', '0 (0%)', "{$etudiantsEnRegle} (" . ($totalEtudiants > 0 ? round($etudiantsEnRegle / $totalEtudiants * 100, 1) : 0) . "%)", $etudiantsEnRegle > 0 ? '⚠️ Différence!' : '✅'],
                ['Partiels', '4 (57.1%)', "{$etudiantsPartiels} (" . ($totalEtudiants > 0 ? round($etudiantsPartiels / $totalEtudiants * 100, 1) : 0) . "%)", abs($etudiantsPartiels - 4) > 0 ? '⚠️ Différence!' : '✅'],
                ['Impayés', '3 (42.9%)', "{$etudiantsImpayes} (" . ($totalEtudiants > 0 ? round($etudiantsImpayes / $totalEtudiants * 100, 1) : 0) . "%)", abs($etudiantsImpayes - 3) > 0 ? '⚠️ Différence!' : '✅'],
                ['Total payé', '345 000 FCFA', number_format($montantTotalPaye, 0, ',', ' ') . ' FCFA', abs($montantTotalPaye - 345000) > 0 ? '⚠️ Différence!' : '✅'],
                ['Total attendu', '1 505 000 FCFA', number_format($montantTotalAttendu, 0, ',', ' ') . ' FCFA', abs($montantTotalAttendu - 1505000) > 0 ? '⚠️ Différence!' : '✅'],
                ['Taux recouvrement', '22.9%', "{$tauxRecouvrement}%", abs($tauxRecouvrement - 22.9) > 1 ? '⚠️ Différence!' : '✅'],
            ]
        );

        return 0;
    }
}