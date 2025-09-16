<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ESBTPInscription;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPAnneeUniversitaire;
use App\Services\ESBTPInscriptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateFeesForAcademicYear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'esbtp:generate-fees-for-year
                            {year_id : ID of the academic year to process}
                            {--dry-run : Show what would be done without executing}
                            {--limit=100 : Limit the number of inscriptions to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate missing mandatory fees for inscriptions of a specific academic year';

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
        $yearId = (int) $this->argument('year_id');

        // Vérifier que l'année universitaire existe
        $anneeUniversitaire = ESBTPAnneeUniversitaire::find($yearId);
        if (!$anneeUniversitaire) {
            $this->error("❌ Année universitaire avec ID {$yearId} non trouvée");
            return Command::FAILURE;
        }

        $this->info("🎯 Traitement de l'année universitaire : {$anneeUniversitaire->name}");
        $this->info("📅 Période : {$anneeUniversitaire->start_date} → {$anneeUniversitaire->end_date}");

        // Récupérer toutes les inscriptions de cette année
        $allInscriptions = ESBTPInscription::with(['etudiant', 'classe', 'anneeUniversitaire'])
            ->where('annee_universitaire_id', $yearId)
            ->get();

        $this->info("📊 Total inscriptions pour cette année : " . $allInscriptions->count());

        // Filtrer celles sans frais
        $inscriptionsSansFrais = [];
        foreach ($allInscriptions as $inscription) {
            $fraisCount = ESBTPFraisSubscription::where('inscription_id', $inscription->id)->count();
            if ($fraisCount === 0) {
                $inscriptionsSansFrais[] = $inscription;
            }
        }

        $total = count($inscriptionsSansFrais);
        $limit = min($total, (int) $this->option('limit'));

        $this->info("🔍 Inscriptions sans frais : {$total}");
        $this->info("⚡ Traitement de : {$limit} inscriptions");

        if ($total === 0) {
            $this->info('✅ Toutes les inscriptions de cette année ont déjà leurs frais !');
            return Command::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn('🔍 MODE DRY-RUN - Aucune modification ne sera effectuée');
        }

        // Afficher quelques détails sur l'année
        $this->table(
            ['Détail', 'Valeur'],
            [
                ['Année universitaire', $anneeUniversitaire->name],
                ['Status', $anneeUniversitaire->is_current ? '✅ Courante' : '📝 Historique'],
                ['Inscriptions totales', $allInscriptions->count()],
                ['Sans frais', $total],
                ['À traiter', $limit]
            ]
        );

        if (!$this->confirm('Voulez-vous continuer ?')) {
            $this->info('❌ Opération annulée par l\'utilisateur');
            return Command::SUCCESS;
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
                                'selected_option_id' => null, // Pour les frais obligatoires, pas d'option spécifique
                                'amount' => $fee['amount'],
                                'is_active' => true,
                                'created_by' => 1,
                                'notes' => "Généré automatiquement pour année {$anneeUniversitaire->name} via commande generate-fees-for-year"
                            ]);
                            $feesGenerated++;
                        }
                    }

                    DB::commit();

                    Log::info('Frais générés pour inscription année spécifique', [
                        'annee_universitaire' => $anneeUniversitaire->name,
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
                Log::error('Erreur génération frais année spécifique', [
                    'annee_universitaire' => $anneeUniversitaire->name,
                    'inscription_id' => $inscription->id,
                    'error' => $e->getMessage()
                ]);
                $errors++;
            }

            $progress->advance();
        }

        $progress->finish();

        $this->newLine(2);
        $this->info("✅ Traitement terminé pour {$anneeUniversitaire->name} !");
        $this->info("   📊 Inscriptions traitées: {$processed}");
        $this->info("   💰 Frais générés: {$feesGenerated}");
        if ($errors > 0) {
            $this->error("   ❌ Erreurs: {$errors}");
        }

        if (!$this->option('dry-run')) {
            $this->info("🎯 Les frais ont été générés pour l'année {$anneeUniversitaire->name}");
            $this->info("🔄 Vous pouvez maintenant tester les réinscriptions pour voir si les reliquats se créent automatiquement");
        } else {
            $this->warn("🔍 Mode dry-run - Exécutez sans --dry-run pour appliquer les changements");
        }

        return Command::SUCCESS;
    }
}