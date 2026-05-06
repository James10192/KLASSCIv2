<?php

namespace Database\Seeders;

use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Services\ESBTPInscriptionService;
use Database\Seeders\Demo\AcademicDemoData;
use Database\Seeders\Demo\FraisDemoData;
use Database\Seeders\Demo\StudentsDemoData;
use Database\Seeders\Demo\FinanceDemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Demo seeder pour le tenant `presentation` UNIQUEMENT.
 *
 * Garde-fou : refuse de tourner si TENANT_CODE !== 'presentation'.
 * Idempotent : firstOrCreate / updateOrCreate partout.
 *
 * Orchestration en 4 étapes thématiques pour respecter no-god-code
 * (chaque sous-classe < 250 LOC, méthodes < 40 LOC).
 */
class PresentationDemoSeeder extends Seeder
{
    private const TARGET_TENANT = 'presentation';

    public function run(): void
    {
        $this->guardTenant();

        DB::transaction(function () {
            $this->command?->info('▶ 1/4 — Académique (année, filières, niveaux, classes)');
            $academic = (new AcademicDemoData($this->command))->run();

            $this->command?->info('▶ 2/4 — Frais (catégories, configurations, échéanciers)');
            $frais = (new FraisDemoData($this->command))->run($academic);

            $this->command?->info('▶ 3/4 — Étudiants + inscriptions');
            $students = (new StudentsDemoData($this->command))->run($academic);

            $this->command?->info('▶ 4/5 — Paiements (mix réaliste + outliers analytics)');
            (new FinanceDemoData($this->command))->run($academic, $frais, $students);

            $this->command?->info('▶ 5/5 — Génération des frais subscriptions par inscription');
            $this->regenerateFeesSubscriptions($academic['annee']->id);
        });

        $this->command?->info('✅ Seed presentation terminé.');
    }

    /**
     * Sans ESBTPFraisSubscription posée pour chaque inscription, le calcul
     * "Total attendu" reste à 0 sur la fiche étudiant. On appelle
     * directement ESBTPInscriptionService::generateFeesForInscription
     * (la commande artisan esbtp:generate-fees-for-year demande un confirm()
     * interactif incompatible avec Artisan::call, on bypasse).
     */
    private function regenerateFeesSubscriptions(int $anneeId): void
    {
        $service = app(ESBTPInscriptionService::class);

        $created = 0;
        $skipped = 0;

        $inscriptions = ESBTPInscription::query()
            ->where('annee_universitaire_id', $anneeId)
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->get();

        foreach ($inscriptions as $inscription) {
            $hasFees = ESBTPFraisSubscription::where('inscription_id', $inscription->id)->exists();
            if ($hasFees) {
                $skipped++;
                continue;
            }

            try {
                $created += $this->generateForInscription($service, $inscription);
            } catch (\Throwable $e) {
                Log::warning('Demo seeder: fees regen failed for inscription', [
                    'inscription_id' => $inscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->command?->line(sprintf(
            '   • %d subscriptions créées · %d inscriptions déjà couvertes (skip)',
            $created,
            $skipped
        ));
    }

    private function generateForInscription(ESBTPInscriptionService $service, ESBTPInscription $inscription): int
    {
        $affectation = $inscription->affectation_status ?? ESBTPInscription::DEFAULT_AFFECTATION_STATUS;
        $fees = $service->generateFeesForInscription($inscription, [], $affectation);

        $count = 0;
        foreach ($fees as $fee) {
            if (($fee['amount'] ?? 0) <= 0) {
                continue;
            }
            ESBTPFraisSubscription::create([
                'inscription_id'    => $inscription->id,
                'frais_category_id' => $fee['category_id'],
                'selected_option_id'=> null,
                'amount'            => $fee['amount'],
                'is_active'         => true,
                'created_by'        => 1,
                'notes'             => 'Posé par PresentationDemoSeeder',
            ]);
            $count++;
        }
        return $count;
    }

    private function guardTenant(): void
    {
        $tenant = (string) config('app.tenant_code', env('TENANT_CODE', 'default'));

        if ($tenant !== self::TARGET_TENANT) {
            $msg = sprintf(
                'PresentationDemoSeeder refuses to run: TENANT_CODE is "%s", expected "%s". '
                . 'This seeder is only allowed on the demo tenant.',
                $tenant,
                self::TARGET_TENANT
            );
            Log::warning($msg);
            throw new RuntimeException($msg);
        }
    }
}
