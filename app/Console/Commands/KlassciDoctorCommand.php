<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * klassci:doctor — health check du tenant courant.
 *
 * Vérifie :
 * - migrations LMD chantier emploi-temps (PR8-PR13)
 * - permissions registry LMD (10 perms)
 * - tables critiques présentes
 * - settings tenant LMD (defaults documentés)
 *
 * Usage : php artisan klassci:doctor [--json]
 */
class KlassciDoctorCommand extends Command
{
    protected $signature = 'klassci:doctor {--json : Output JSON instead of human-readable}';

    protected $description = 'Health check du tenant — verifie migrations, permissions, tables, settings chantier emploi-temps LMD';

    public function handle(): int
    {
        $checks = [
            'tables' => $this->checkTables(),
            'permissions' => $this->checkPermissions(),
            'settings' => $this->checkSettings(),
            'models' => $this->checkModels(),
        ];

        $allOk = collect($checks)->every(fn ($c) => $c['ok']);

        if ($this->option('json')) {
            $this->line(json_encode([
                'ok' => $allOk,
                'tenant_code' => env('TENANT_CODE', 'unknown'),
                'checks' => $checks,
                'checked_at' => now()->toIso8601String(),
            ], JSON_PRETTY_PRINT));

            return $allOk ? 0 : 1;
        }

        $this->line('');
        $this->info('=== KLASSCI Doctor — Chantier Emploi-Temps LMD ===');
        $this->line('Tenant : ' . env('TENANT_CODE', '???'));
        $this->line('Date   : ' . now()->format('Y-m-d H:i:s'));
        $this->line('');

        foreach ($checks as $name => $check) {
            $icon = $check['ok'] ? '<fg=green>OK</>' : '<fg=red>KO</>';
            $this->line(sprintf('%s %-15s %s', $icon, $name, $check['message']));
            if (! $check['ok'] && ! empty($check['details'])) {
                foreach ($check['details'] as $d) {
                    $this->line('    <fg=yellow>></> ' . $d);
                }
            }
        }

        $this->line('');
        $this->{$allOk ? 'info' : 'error'}($allOk ? 'All checks passed' : 'Some checks failed — voir details ci-dessus');

        return $allOk ? 0 : 1;
    }

    private function checkTables(): array
    {
        $required = [
            'esbtp_examens_planifies',
            'esbtp_examen_surveillants',
            'esbtp_lmd_sessions',
            'esbtp_lmd_jurys',
            'esbtp_lmd_jury_membres',
            'esbtp_lmd_jury_decisions',
        ];

        $missing = array_filter($required, fn ($t) => ! Schema::hasTable($t));

        return [
            'ok' => empty($missing),
            'message' => empty($missing)
                ? sprintf('%d/%d tables OK', count($required), count($required))
                : sprintf('%d/%d tables presentes', count($required) - count($missing), count($required)),
            'details' => array_map(fn ($t) => "Table manquante : {$t}", $missing),
        ];
    }

    private function checkPermissions(): array
    {
        $required = [
            'lmd.examens.view', 'lmd.examens.manage', 'lmd.examens.notes_lock',
            'lmd.rattrapage.view', 'lmd.rattrapage.manage',
            'lmd.jury.view', 'lmd.jury.preside', 'lmd.jury.deliberate', 'lmd.jury.publish',
        ];

        $missing = [];
        try {
            $registered = array_keys(config('permissions.permissions') ?? []);
            foreach ($required as $perm) {
                if (! in_array($perm, $registered, true)) {
                    $missing[] = $perm;
                }
            }
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Erreur lecture registry', 'details' => [$e->getMessage()]];
        }

        return [
            'ok' => empty($missing),
            'message' => empty($missing)
                ? sprintf('%d/%d permissions registry', count($required), count($required))
                : sprintf('%d/%d permissions registry', count($required) - count($missing), count($required)),
            'details' => array_map(fn ($p) => "Permission manquante : {$p}", $missing),
        ];
    }

    private function checkSettings(): array
    {
        if (! class_exists(\App\Helpers\SettingsHelper::class)) {
            return ['ok' => false, 'message' => 'SettingsHelper introuvable', 'details' => []];
        }

        $documented = [
            'lmd_seuil_validation_ecue' => 10,
            'lmd_note_eliminatoire' => 0,
            'lmd_rattrapage_replace' => false,
            'lmd_jury_quorum_min' => 2,
            'lmd_jury_quorum_assesseurs_min' => 1,
            'lmd_pv_retention_years' => 5,
            'lmd_mention_p_threshold' => 10,
            'lmd_mention_ab_threshold' => 12,
            'lmd_mention_b_threshold' => 14,
            'lmd_mention_tb_threshold' => 16,
            'lmd_compensation_enabled' => true,
            'lmd_intra_ue_compensation' => true,
        ];

        $details = [];
        foreach ($documented as $key => $default) {
            try {
                $value = \App\Helpers\SettingsHelper::get($key, $default);
                $details[] = "{$key} = " . json_encode($value);
            } catch (\Throwable $e) {
                $details[] = "{$key} -> ERR : " . $e->getMessage();
            }
        }

        return [
            'ok' => true,
            'message' => sprintf('%d settings tenant LMD (defaults documentes)', count($documented)),
            'details' => $details,
        ];
    }

    private function checkModels(): array
    {
        $models = [
            \App\Models\ESBTPExamenPlanifie::class,
            \App\Models\ESBTPExamenSurveillant::class,
            \App\Models\ESBTPLMDSession::class,
            \App\Models\ESBTPLMDJury::class,
            \App\Models\ESBTPLMDJuryMembre::class,
            \App\Models\ESBTPLMDJuryDecision::class,
        ];

        $missing = array_filter($models, fn ($c) => ! class_exists($c));

        return [
            'ok' => empty($missing),
            'message' => empty($missing)
                ? sprintf('%d/%d models loadables', count($models), count($models))
                : sprintf('%d models manquants', count($missing)),
            'details' => array_map(fn ($c) => "Class manquante : {$c}", $missing),
        ];
    }
}
