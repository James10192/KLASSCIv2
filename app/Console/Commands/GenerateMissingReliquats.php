<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ESBTPInscription;
use App\Models\ESBTPReliquatDetail;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPPaiement;
use App\Services\ReeinscriptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateMissingReliquats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'esbtp:generate-missing-reliquats
                            {--dry-run : Show what would be done without executing}
                            {--limit=50 : Limit the number of reinscriptions to process}
                            {--etudiant-id= : Process only a specific student ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate missing reliquats for reinscriptions that should have them';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Recherche des réinscriptions sans reliquats...');

        // Récupérer les réinscriptions (type = 'reinscription' ou 'réinscription')
        $query = ESBTPInscription::with(['etudiant', 'classe', 'anneeUniversitaire'])
            ->whereIn('type_inscription', ['reinscription', 'réinscription']);

        if ($etudiantId = $this->option('etudiant-id')) {
            $query->where('etudiant_id', $etudiantId);
        }

        $reinscriptions = $query->get();
        $reinscriptionsSansReliquats = [];

        foreach ($reinscriptions as $reinscription) {
            // Vérifier si cette réinscription a déjà des reliquats entrants
            $reliquatsExistants = ESBTPReliquatDetail::where('inscription_destination_id', $reinscription->id)->count();

            if ($reliquatsExistants === 0) {
                // Trouver l'inscription source (précédente) de cet étudiant
                $inscriptionSource = ESBTPInscription::where('etudiant_id', $reinscription->etudiant_id)
                    ->where('id', '<', $reinscription->id) // Inscription précédente
                    ->where('status', 'active')
                    ->latest()
                    ->first();

                if ($inscriptionSource) {
                    // Vérifier s'il y a des frais impayés sur l'inscription source
                    $fraisSouscrits = ESBTPFraisSubscription::where('inscription_id', $inscriptionSource->id)
                        ->where('is_active', true)
                        ->get();

                    $hasUnpaidFees = false;
                    foreach ($fraisSouscrits as $frais) {
                        $montantPaye = ESBTPPaiement::where('inscription_id', $inscriptionSource->id)
                            ->where('frais_category_id', $frais->frais_category_id)
                            ->whereIn('status', ['validé', 'validated', 'valide', 'confirmé', 'confirmed'])
                            ->sum('montant');

                        if ($frais->amount > $montantPaye) {
                            $hasUnpaidFees = true;
                            break;
                        }
                    }

                    if ($hasUnpaidFees) {
                        $reinscriptionsSansReliquats[] = [
                            'reinscription' => $reinscription,
                            'inscription_source' => $inscriptionSource
                        ];
                    }
                }
            }
        }

        $total = count($reinscriptionsSansReliquats);
        $limit = min($total, (int) $this->option('limit'));

        $this->info("📊 Trouvé {$total} réinscriptions nécessitant des reliquats");
        $this->info("⚡ Traitement de {$limit} réinscriptions");

        if ($this->option('dry-run')) {
            $this->warn('🔍 MODE DRY-RUN - Aucune modification ne sera effectuée');
        }

        if ($total === 0) {
            $this->info('✅ Aucun reliquat manquant trouvé !');
            return Command::SUCCESS;
        }

        $progress = $this->output->createProgressBar($limit);
        $progress->start();

        $processed = 0;
        $errors = 0;
        $reliquatsGenerated = 0;

        foreach (array_slice($reinscriptionsSansReliquats, 0, $limit) as $data) {
            $reinscription = $data['reinscription'];
            $inscriptionSource = $data['inscription_source'];

            try {
                if (!$this->option('dry-run')) {
                    DB::beginTransaction();
                }

                // Générer les reliquats pour cette réinscription
                $reliquatsCreated = $this->createReliquatsForReinscription($inscriptionSource, $reinscription, $this->option('dry-run'));

                if (!$this->option('dry-run')) {
                    DB::commit();

                    Log::info('Reliquats générés pour réinscription', [
                        'inscription_source_id' => $inscriptionSource->id,
                        'inscription_destination_id' => $reinscription->id,
                        'etudiant' => $reinscription->etudiant->prenom . ' ' . $reinscription->etudiant->nom,
                        'reliquats_count' => $reliquatsCreated
                    ]);
                }

                $reliquatsGenerated += $reliquatsCreated;
                $processed++;

            } catch (\Exception $e) {
                if (!$this->option('dry-run')) {
                    DB::rollback();
                }

                $this->error("\n❌ Erreur pour réinscription {$reinscription->id}: " . $e->getMessage());
                Log::error('Erreur génération reliquats automatique', [
                    'inscription_destination_id' => $reinscription->id,
                    'inscription_source_id' => $inscriptionSource->id,
                    'error' => $e->getMessage()
                ]);
                $errors++;
            }

            $progress->advance();
        }

        $progress->finish();

        $this->newLine(2);
        $this->info("✅ Traitement terminé !");
        $this->info("   📊 Réinscriptions traitées: {$processed}");
        $this->info("   💰 Reliquats générés: {$reliquatsGenerated}");
        if ($errors > 0) {
            $this->error("   ❌ Erreurs: {$errors}");
        }

        if (!$this->option('dry-run')) {
            $this->info("🎯 Les reliquats ont été générés automatiquement en base de données");
        } else {
            $this->warn("🔍 Mode dry-run - Exécutez sans --dry-run pour appliquer les changements");
        }

        return Command::SUCCESS;
    }

    /**
     * Créer les reliquats pour une réinscription donnée
     */
    private function createReliquatsForReinscription($inscriptionSource, $inscriptionDestination, $dryRun = false)
    {
        $reliquatsCreated = 0;

        // Récupérer tous les frais souscrits pour l'inscription source
        $fraisSouscrits = ESBTPFraisSubscription::where('inscription_id', $inscriptionSource->id)
            ->where('is_active', true)
            ->get();

        foreach ($fraisSouscrits as $fraisSubscription) {
            // Calculer le montant attendu pour ce frais
            $montantAttendu = $fraisSubscription->amount;

            // Calculer le montant payé pour ce frais spécifique
            $montantPaye = ESBTPPaiement::where('inscription_id', $inscriptionSource->id)
                ->where('frais_category_id', $fraisSubscription->frais_category_id)
                ->whereIn('status', ['validé', 'validated', 'valide', 'confirmé', 'confirmed'])
                ->sum('montant');

            // Calculer le reliquat
            $montantReliquat = $montantAttendu - $montantPaye;

            // Créer un reliquat seulement s'il y a un montant impayé
            if ($montantReliquat > 0) {
                if ($dryRun) {
                    $this->line("\n  📝 Reliquat: {$inscriptionDestination->etudiant->prenom} {$inscriptionDestination->etudiant->nom} - {$montantReliquat} FCFA");
                } else {
                    ESBTPReliquatDetail::create([
                        'inscription_source_id' => $inscriptionSource->id,
                        'inscription_destination_id' => $inscriptionDestination->id,
                        'frais_subscription_id' => $fraisSubscription->id,
                        'montant_attendu' => $montantAttendu,
                        'montant_paye' => $montantPaye,
                        'montant_reliquat' => $montantReliquat,
                        'montant_regle' => 0,
                        'statut' => 'actif',
                        'date_creation' => now(),
                        'date_derniere_maj' => now(),
                        'created_by' => 1,
                        'notes' => "Reliquat créé automatiquement via commande generate-missing-reliquats - {$inscriptionSource->anneeUniversitaire->name} vers {$inscriptionDestination->anneeUniversitaire->name}"
                    ]);
                }
                $reliquatsCreated++;
            }
        }

        return $reliquatsCreated;
    }
}