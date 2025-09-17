<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPFraisCategory;

class AnalyzeStudentPayments extends Command
{
    protected $signature = 'esbtp:analyze-payments {matricule}';
    protected $description = 'Analyser les paiements d\'un étudiant spécifique';

    public function handle()
    {
        $matricule = $this->argument('matricule');

        $etudiant = ESBTPEtudiant::where('matricule', $matricule)->first();

        if (!$etudiant) {
            $this->error("Étudiant avec matricule {$matricule} non trouvé");
            return;
        }

        $this->info("=== ANALYSE DES PAIEMENTS POUR {$etudiant->prenoms} {$etudiant->nom} ===");
        $this->info("Matricule: {$matricule}");

        $inscription = $etudiant->inscriptions()->first();
        if (!$inscription) {
            $this->error("Aucune inscription trouvée pour cet étudiant");
            return;
        }

        $this->info("Inscription ID: {$inscription->id}");
        $this->line('');

        // Récupérer tous les paiements de cette inscription
        $paiements = ESBTPPaiement::where('inscription_id', $inscription->id)
            ->with('fraisCategory')
            ->orderBy('date_paiement')
            ->get();

        $this->info("📋 LISTE DES PAIEMENTS:");
        $this->line('');

        $totalPaiements = 0;
        $categories = [];

        foreach($paiements as $paiement) {
            $categoryName = $paiement->fraisCategory->name ?? 'CATÉGORIE NON DÉFINIE';
            $categoryId = $paiement->frais_category_id ?? 'NULL';

            $this->line("💰 Paiement #{$paiement->id}");
            $this->info("  - Montant: " . number_format($paiement->montant, 0, ',', ' ') . " FCFA");
            $this->info("  - Date: " . ($paiement->date_paiement ?? 'N/A'));
            $this->info("  - Statut: " . ($paiement->status ?? 'N/A'));
            $this->info("  - Mode: " . ($paiement->mode_paiement ?? 'N/A'));
            $this->info("  - Catégorie ID: {$categoryId}");
            $this->info("  - Catégorie: {$categoryName}");
            $this->info("  - Type paiement: " . ($paiement->type_paiement ?? 'normal'));
            if ($paiement->reliquat_detail_id) {
                $this->warn("  - ⚠️ PAIEMENT DE RELIQUAT (ID: {$paiement->reliquat_detail_id})");
            }
            $this->line('');

            $totalPaiements += $paiement->montant;

            if (!isset($categories[$categoryId])) {
                $categories[$categoryId] = [
                    'name' => $categoryName,
                    'total' => 0,
                    'count' => 0
                ];
            }
            $categories[$categoryId]['total'] += $paiement->montant;
            $categories[$categoryId]['count']++;
        }

        $this->info("📊 RÉSUMÉ PAR CATÉGORIE:");
        $this->line('');

        foreach($categories as $catId => $catData) {
            $this->info("🏷️ Catégorie {$catId}: {$catData['name']}");
            $this->info("  - Total: " . number_format($catData['total'], 0, ',', ' ') . " FCFA");
            $this->info("  - Nombre de paiements: {$catData['count']}");
            $this->line('');
        }

        $this->info("💵 TOTAL GÉNÉRAL: " . number_format($totalPaiements, 0, ',', ' ') . " FCFA");

        // Analyser les souscriptions
        $this->line('');
        $this->info("📋 SOUSCRIPTIONS ACTIVES:");
        $this->line('');

        $subscriptions = \App\Models\ESBTPFraisSubscription::where('inscription_id', $inscription->id)
            ->where('is_active', true)
            ->with('fraisCategory')
            ->get();

        foreach($subscriptions as $subscription) {
            $categoryName = $subscription->fraisCategory->name ?? 'CATÉGORIE NON DÉFINIE';
            $this->info("🎯 {$categoryName} (ID: {$subscription->frais_category_id})");
            $this->info("  - Montant souscrit: " . number_format($subscription->amount, 0, ',', ' ') . " FCFA");
        }
    }
}