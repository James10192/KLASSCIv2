<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ESBTPInscription;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPFraisConfiguration;
use Illuminate\Support\Facades\DB;

class FixReaffectedStudentsFees extends Command
{
    protected $signature = 'esbtp:fix-reaffected-fees
                           {--dry-run : Show what would be changed without making actual changes}
                           {--force : Skip confirmation prompt}';

    protected $description = 'Fix fees for reaffected students who were incorrectly using non_affecte amounts instead of affecte amounts';

    public function handle()
    {
        $this->info('🔍 Analyzing ESBTPFraisSubscription for reaffected students...');

        $isDryRun = $this->option('dry-run');
        $isForced = $this->option('force');

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        // Find inscriptions with reaffecte status in current academic year
        $currentYear = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();

        if (!$currentYear) {
            $this->error('❌ No current academic year found. Please set is_current = true for the current year.');
            return 1;
        }

        $this->info("🎓 Current academic year: {$currentYear->name}");

        $reaffectedInscriptions = ESBTPInscription::where('affectation_status', 'réaffecté')
            ->where('annee_universitaire_id', $currentYear->id)
            ->with(['fraisSubscriptions.fraisCategory', 'etudiant'])
            ->get();

        if ($reaffectedInscriptions->isEmpty()) {
            $this->info('✅ No reaffected students found.');
            return 0;
        }

        $this->info("📊 Found {$reaffectedInscriptions->count()} reaffected student(s)");

        $toUpdate = [];
        $statistics = [
            'total_students' => $reaffectedInscriptions->count(),
            'subscriptions_to_update' => 0,
            'total_amount_change' => 0,
            'errors' => []
        ];

        foreach ($reaffectedInscriptions as $inscription) {
            $this->line("👤 Processing: {$inscription->etudiant->nom} {$inscription->etudiant->prenoms} (ID: {$inscription->id})");

            foreach ($inscription->fraisSubscriptions()->where('is_active', true)->get() as $subscription) {
                // Get the correct configuration for this subscription
                $config = ESBTPFraisConfiguration::where('frais_category_id', $subscription->frais_category_id)
                    ->where('filiere_id', $inscription->filiere_id)
                    ->where('niveau_id', $inscription->niveau_id)
                    ->where('is_active', true)
                    ->first();

                if (!$config) {
                    $statistics['errors'][] = "No config found for subscription {$subscription->id} (Category: {$subscription->fraisCategory->name})";
                    continue;
                }

                // Calculate correct amount for reaffected using the proper service method
                $correctAmount = $config->getMontantByStatus('réaffecté');
                $currentAmount = $subscription->amount;

                if ($currentAmount != $correctAmount) {
                    $toUpdate[] = [
                        'subscription_id' => $subscription->id,
                        'inscription_id' => $inscription->id,
                        'student_name' => "{$inscription->etudiant->nom} {$inscription->etudiant->prenoms}",
                        'category_name' => $subscription->fraisCategory->name,
                        'current_amount' => $currentAmount,
                        'correct_amount' => $correctAmount,
                        'difference' => $correctAmount - $currentAmount,
                        'config_id' => $config->id
                    ];

                    $statistics['subscriptions_to_update']++;
                    $statistics['total_amount_change'] += ($correctAmount - $currentAmount);
                }
            }
        }

        if (empty($toUpdate)) {
            $this->info('✅ All reaffected students already have correct fees!');
            return 0;
        }

        // Display summary
        $this->info("\n📋 SUMMARY:");
        $this->table(
            ['Student', 'Category', 'Current Amount', 'Correct Amount', 'Difference'],
            array_map(function($item) {
                return [
                    $item['student_name'],
                    $item['category_name'],
                    number_format($item['current_amount'], 0, ',', ' ') . ' FCFA',
                    number_format($item['correct_amount'], 0, ',', ' ') . ' FCFA',
                    ($item['difference'] >= 0 ? '+' : '') . number_format($item['difference'], 0, ',', ' ') . ' FCFA'
                ];
            }, $toUpdate)
        );

        $this->info("\n📊 STATISTICS:");
        $this->info("• Total students affected: {$statistics['total_students']}");
        $this->info("• Subscriptions to update: {$statistics['subscriptions_to_update']}");
        $this->info("• Total amount change: " . ($statistics['total_amount_change'] >= 0 ? '+' : '') .
                   number_format($statistics['total_amount_change'], 0, ',', ' ') . ' FCFA');

        if (!empty($statistics['errors'])) {
            $this->warn("\n⚠️  ERRORS:");
            foreach ($statistics['errors'] as $error) {
                $this->error("• $error");
            }
        }

        if ($isDryRun) {
            $this->warn("\n🔍 DRY RUN COMPLETE - No changes were made");
            $this->info("Run without --dry-run to apply these changes");
            return 0;
        }

        // Confirm before making changes
        if (!$isForced && !$this->confirm("\nDo you want to apply these changes?", false)) {
            $this->info('Operation cancelled.');
            return 0;
        }

        // Apply changes
        $this->info("\n🔄 Applying changes...");
        $updated = 0;
        $failed = 0;

        DB::beginTransaction();

        try {
            foreach ($toUpdate as $update) {
                $subscription = ESBTPFraisSubscription::find($update['subscription_id']);
                if ($subscription) {
                    $oldAmount = $subscription->amount;
                    $subscription->amount = $update['correct_amount'];
                    $subscription->notes = ($subscription->notes ? $subscription->notes . "\n" : '') .
                                         "Auto-corrected reaffected fees: {$oldAmount} → {$update['correct_amount']} FCFA on " . now()->format('Y-m-d H:i:s');

                    if ($subscription->save()) {
                        $updated++;
                        $this->info("✅ Updated subscription {$subscription->id}: {$oldAmount} → {$update['correct_amount']} FCFA");
                    } else {
                        $failed++;
                        $this->error("❌ Failed to update subscription {$subscription->id}");
                    }
                } else {
                    $failed++;
                    $this->error("❌ Subscription {$update['subscription_id']} not found");
                }
            }

            if ($failed === 0) {
                DB::commit();
                $this->info("\n✅ SUCCESS! Updated {$updated} subscription(s)");
                $this->info("💾 All changes have been committed to the database");
            } else {
                DB::rollBack();
                $this->error("\n❌ FAILED! {$failed} error(s) occurred. All changes have been rolled back.");
                return 1;
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("\n❌ ERROR: " . $e->getMessage());
            $this->error("All changes have been rolled back.");
            return 1;
        }

        return 0;
    }
}