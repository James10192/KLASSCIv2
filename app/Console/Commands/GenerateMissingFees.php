<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ESBTPInscription;
use App\Models\ESBTPFraisSubscription;
use App\Services\ESBTPInscriptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateMissingFees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'esbtp:generate-missing-fees
                            {--dry-run : Show what would be done without executing}
                            {--limit=100 : Limit the number of inscriptions to process}
                            {--inscription-id= : Process only a specific inscription ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate missing mandatory fees for inscriptions that don\'t have any fees';

    protected $inscriptionService;

    public function __construct(ESBTPInscriptionService $inscriptionService)
    {
        parent::__construct();
        $this->inscriptionService = $inscriptionService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Recherche des inscriptions sans frais...');

        // Récupérer les inscriptions sans frais
        $query = ESBTPInscription::with(['etudiant', 'classe', 'anneeUniversitaire']);

        if ($inscriptionId = $this->option('inscription-id')) {
            $query->where('id', $inscriptionId);
        }

        $allInscriptions = $query->get();
        $inscriptionsSansFrais = [];

        foreach ($allInscriptions as $inscription) {
            $fraisCount = ESBTPFraisSubscription::where('inscription_id', $inscription->id)->count();
            if ($fraisCount === 0) {
                $inscriptionsSansFrais[] = $inscription;
            }
        }

        $total = count($inscriptionsSansFrais);
        $limit = min($total, (int) $this->option('limit'));

        $this->info("📊 Trouvé {$total} inscriptions sans frais");
        $this->info("⚡ Traitement de {$limit} inscriptions");

        if ($this->option('dry-run')) {
            $this->warn('🔍 MODE DRY-RUN - Aucune modification ne sera effectuée');
        }

        $progress = $this->output->createProgressBar($limit);
        $progress->start();

        $processed = 0;
        $errors = 0;
        $feesGenerated = 0;

        foreach (array_slice($inscriptionsSansFrais, 0, $limit) as $inscription) {
            try {
                if (!$this->option('dry-run')) {
                    DB::beginTransaction();
                }

                // Générer les frais pour cette inscription
                $affectationStatus = $inscription->affectation_status ?? 'affecté';
                $generatedFees = $this->inscriptionService->generateFeesForInscription(
                    $inscription,
                    [], // Pas d'optionnels pour la correction automatique
                    $affectationStatus
                );

                if (!$this->option('dry-run')) {
                    // Sauvegarder les frais générés en base
                    foreach ($generatedFees as $fee) {
                        if ($fee['amount'] > 0) { // Seulement si montant > 0
                            ESBTPFraisSubscription::create([
                                'inscription_id' => $inscription->id,
                                'frais_category_id' => $fee['category_id'],
                                'frais_configuration_id' => $fee['configuration_id'],
                                'amount' => $fee['amount'],
                                'is_active' => true,
                                'created_by' => 1,
                                'notes' => 'Généré automatiquement via commande generate-missing-fees'
                            ]);
                            $feesGenerated++;
                        }
                    }

                    DB::commit();

                    Log::info('Frais générés pour inscription', [
                        'inscription_id' => $inscription->id,
                        'etudiant' => $inscription->etudiant->prenom . ' ' . $inscription->etudiant->nom,
                        'fees_count' => count($generatedFees),
                        'fees_amount' => array_sum(array_column($generatedFees, 'amount'))
                    ]);
                } else {
                    // Mode dry-run : juste afficher ce qui serait fait
                    $this->line("\n  📝 Inscription {$inscription->id} ({$inscription->etudiant->prenom} {$inscription->etudiant->nom}): " . count($generatedFees) . " frais seraient générés");
                    foreach ($generatedFees as $fee) {
                        if ($fee['amount'] > 0) {
                            $this->line("    - {$fee['description']}: {$fee['amount']} FCFA");
                            $feesGenerated++;
                        }
                    }
                }

                $processed++;

            } catch (\Exception $e) {
                if (!$this->option('dry-run')) {
                    DB::rollback();
                }

                $this->error("\n❌ Erreur pour inscription {$inscription->id}: " . $e->getMessage());
                Log::error('Erreur génération frais automatique', [
                    'inscription_id' => $inscription->id,
                    'error' => $e->getMessage()
                ]);
                $errors++;
            }

            $progress->advance();
        }

        $progress->finish();

        $this->newLine(2);
        $this->info("✅ Traitement terminé !");
        $this->info("   📊 Inscriptions traitées: {$processed}");
        $this->info("   💰 Frais générés: {$feesGenerated}");
        if ($errors > 0) {
            $this->error("   ❌ Erreurs: {$errors}");
        }

        if (!$this->option('dry-run')) {
            $this->info("🎯 Les frais ont été générés automatiquement en base de données");
        } else {
            $this->warn("🔍 Mode dry-run - Exécutez sans --dry-run pour appliquer les changements");
        }

        return Command::SUCCESS;
    }
}