<?php

namespace Database\Seeders;

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

            $this->command?->info('▶ 4/4 — Paiements (mix réaliste + outliers analytics)');
            (new FinanceDemoData($this->command))->run($academic, $frais, $students);
        });

        $this->command?->info('✅ Seed presentation terminé.');
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
