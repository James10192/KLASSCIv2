<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Http\Controllers\API\CLI\CLIPermissionController;

class CLIMaintenanceController extends BaseApiController
{
    /**
     * POST /api/cli/cache/clear — Clear all caches
     */
    public function cacheClear(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $output = [];

        try {
            Artisan::call('config:clear');
            $output[] = 'config:clear OK';

            Artisan::call('route:clear');
            $output[] = 'route:clear OK';

            Artisan::call('cache:clear');
            $output[] = 'cache:clear OK';

            Artisan::call('view:clear');
            $output[] = 'view:clear OK';

            Artisan::call('permission:cache-reset');
            $output[] = 'permission:cache-reset OK';

            // Also clear settings cache
            Setting::clearCache();
            $output[] = 'settings cache cleared';

            if (function_exists('opcache_reset')) {
                @opcache_reset();
                $output[] = 'opcache reset OK';
            }
        } catch (\Exception $e) {
            Log::error('CLI: cache clear failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->errorResponse('Operation failed. Check server logs for details.', ['completed' => $output], 500);
        }

        return $this->successResponse([
            'commands' => $output,
        ], 'All caches cleared successfully');
    }

    /**
     * POST /api/cli/evaluations/sync-notes — Resync esbtp_notes denormalized columns
     * (classe_id, matiere_id, semestre) from their parent evaluation.
     *
     * Optional body : { "evaluation_id": 622, "dry": true }
     */
    public function evaluationsSyncNotes(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $params = [];
        if ($request->filled('evaluation_id')) {
            $params['--evaluation'] = (int) $request->input('evaluation_id');
        }
        if ($request->boolean('dry')) {
            $params['--dry'] = true;
        }
        if ($request->boolean('clean_resultats')) {
            $params['--clean-resultats'] = true;
        }

        try {
            $exitCode = Artisan::call('evaluations:sync-notes', $params);
            $output = Artisan::output();
            return $this->successResponse([
                'exit_code' => $exitCode,
                'output' => $output,
                'params' => $params,
            ], 'Evaluations sync-notes completed');
        } catch (\Throwable $e) {
            Log::error('CLI: evaluations sync-notes failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Sync failed: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * GET /api/cli/etudiants/{id}/inscriptions-diag
     * Diagnostic des inscriptions d'un étudiant : annee_id, status, workflow_step.
     */
    public function etudiantInscriptionsDiag(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }
        $etudiant = \App\Models\ESBTPEtudiant::with(['inscriptions.anneeUniversitaire:id,name,is_current'])
            ->find($id);
        if (!$etudiant) {
            return $this->errorResponse("Etudiant {$id} not found", [], 404);
        }
        $anneeCourante = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first(['id', 'name', 'start_date', 'end_date']);
        $anneePrecedente = $anneeCourante
            ? \App\Models\ESBTPAnneeUniversitaire::where('end_date', '<', $anneeCourante->start_date)
                ->orderBy('end_date', 'desc')
                ->first(['id', 'name', 'start_date', 'end_date'])
            : null;
        return $this->successResponse([
            'etudiant' => ['id' => $etudiant->id, 'matricule' => $etudiant->matricule, 'nom' => $etudiant->nom . ' ' . $etudiant->prenoms],
            'annee_courante' => $anneeCourante,
            'annee_precedente' => $anneePrecedente,
            'inscriptions' => $etudiant->inscriptions->map(fn ($i) => [
                'id' => $i->id,
                'annee_id' => $i->annee_universitaire_id,
                'annee_name' => optional($i->anneeUniversitaire)->name,
                'is_current_annee' => optional($i->anneeUniversitaire)->is_current,
                'classe_id' => $i->classe_id,
                'status' => $i->status,
                'workflow_step' => $i->workflow_step,
                'type_inscription' => $i->type_inscription,
                'deleted_at' => $i->deleted_at,
            ])->toArray(),
        ]);
    }

    /**
     * GET /api/cli/matieres/{matiere}/coefficient?classe_id=X&annee=Y
     * Quick lookup : coefficient officiel d'une matière dans une combinaison
     * (filiere + niveau_etude + annee) déduite de la classe.
     */
    public function matiereCoefficientLookup(Request $request, int $matiere): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }
        $classeId = (int) $request->query('classe_id');
        $anneeId = (int) $request->query('annee_universitaire_id', 0);
        if (!$classeId) {
            return $this->errorResponse('classe_id requis', [], 422);
        }
        $classe = \App\Models\ESBTPClasse::with(['filiere', 'niveauEtude'])->find($classeId);
        if (!$classe) {
            return $this->errorResponse("Classe {$classeId} non trouvée", [], 404);
        }
        if (!$anneeId) {
            $anneeId = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->value('id');
        }
        $coefs = \App\Models\ESBTPMatiereCoefficient::where('matiere_id', $matiere)
            ->where('filiere_id', $classe->filiere_id)
            ->where('niveau_etude_id', $classe->niveau_etude_id)
            ->where('annee_universitaire_id', $anneeId)
            ->get(['matiere_id', 'filiere_id', 'niveau_etude_id', 'annee_universitaire_id', 'periode', 'coefficient'])
            ->toArray();
        $matiereModel = \App\Models\ESBTPMatiere::find($matiere);
        return $this->successResponse([
            'matiere' => $matiereModel ? ['id' => $matiereModel->id, 'name' => $matiereModel->name, 'code' => $matiereModel->code] : null,
            'classe' => ['id' => $classe->id, 'name' => $classe->name, 'filiere_id' => $classe->filiere_id, 'niveau_etude_id' => $classe->niveau_etude_id],
            'annee_universitaire_id' => $anneeId,
            'coefficients' => $coefs,
        ], 'Coefficient lookup');
    }

    /**
     * GET /api/cli/logs — Read recent application logs (sanitized)
     */
    public function logs(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $lines = min((int) $request->get('lines', 50), 200);
        $level = $request->get('level'); // error, warning, info, debug
        $search = $request->get('search');

        $logFile = storage_path('logs/laravel.log');

        if (!file_exists($logFile)) {
            return $this->successResponse(['entries' => [], 'total' => 0], 'Log file not found');
        }

        try {
            // Read log entries from end of file (handles large stack traces)
            $entries = $this->readLogEntries($logFile, $lines * 2);

            // Filter by level
            if ($level) {
                $levelUpper = strtoupper($level);
                $entries = array_filter($entries, function ($entry) use ($levelUpper) {
                    return stripos($entry['level'], $levelUpper) !== false;
                });
            }

            // Filter by search term
            if ($search) {
                $entries = array_filter($entries, function ($entry) use ($search) {
                    return stripos($entry['message'], $search) !== false
                        || stripos($entry['context'], $search) !== false;
                });
            }

            // Sanitize sensitive data
            $entries = array_map([$this, 'sanitizeLogEntry'], $entries);

            // Take last N entries
            $entries = array_slice(array_values($entries), -$lines);

            return $this->successResponse([
                'entries' => $entries,
                'total' => count($entries),
                'filters' => array_filter(['level' => $level, 'search' => $search]),
                'log_file_size' => $this->formatBytes(filesize($logFile)),
            ], 'Logs retrieved');
        } catch (\Exception $e) {
            Log::error('CLI: logs read failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to read logs', [], 500);
        }
    }

    private function readLogEntries(string $filepath, int $maxEntries): array
    {
        $fileSize = filesize($filepath);
        if ($fileSize === 0) {
            return [];
        }

        // Read backwards in chunks to find log entries
        $handle = fopen($filepath, 'r');
        $chunkSize = 64 * 1024; // 64 KB chunks
        $maxBytes = 2 * 1024 * 1024; // Read max 2 MB from end
        $buffer = '';
        $entries = [];
        $bytesRead = 0;

        $offset = max(0, $fileSize - $chunkSize);

        while ($offset >= 0 && $bytesRead < $maxBytes) {
            fseek($handle, $offset);
            $chunk = fread($handle, min($chunkSize, $fileSize - $offset));
            $buffer = $chunk . $buffer;
            $bytesRead += strlen($chunk);

            // Count log entry headers found so far
            $headerCount = preg_match_all('/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/m', $buffer);
            if ($headerCount >= $maxEntries + 1) {
                break; // We have enough entries
            }

            if ($offset === 0) {
                break;
            }
            $offset = max(0, $offset - $chunkSize);
        }

        fclose($handle);

        // Parse entries from buffer
        $lines = explode("\n", $buffer);
        $entries = [];
        $current = null;

        foreach ($lines as $line) {
            $line = rtrim($line);
            if ($line === '') {
                continue;
            }

            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+\w+\.(\w+):\s*(.*)$/', $line, $m)) {
                if ($current !== null) {
                    $entries[] = $current;
                }
                $current = [
                    'timestamp' => $m[1],
                    'level' => $m[2],
                    'message' => $m[3],
                    'context' => '',
                ];
            } elseif ($current !== null) {
                $current['context'] .= ($current['context'] ? "\n" : '') . $line;
            }
        }

        if ($current !== null) {
            $entries[] = $current;
        }

        return $entries;
    }

    private function sanitizeLogEntry(array $entry): array
    {
        $sensitivePatterns = [
            '/(?:password|passwd|pwd)\s*[:=]\s*\S+/i' => 'password=***REDACTED***',
            '/(?:DB_PASSWORD|DB_USERNAME)\s*[:=]\s*\S+/i' => '$0=***REDACTED***',
            '/(?:API_KEY|APP_KEY|SECRET|TOKEN)\s*[:=]\s*\S+/i' => '$0=***REDACTED***',
            '/Bearer\s+[A-Za-z0-9|._-]+/i' => 'Bearer ***REDACTED***',
            '/sk-[a-zA-Z0-9-]+/' => 'sk-***REDACTED***',
        ];

        foreach ($sensitivePatterns as $pattern => $replacement) {
            $entry['message'] = preg_replace($pattern, $replacement, $entry['message']);
            $entry['context'] = preg_replace($pattern, $replacement, $entry['context']);
        }

        // Truncate context to avoid massive stack traces
        if (strlen($entry['context']) > 1000) {
            $entry['context'] = substr($entry['context'], 0, 1000) . "\n... [truncated]";
        }

        return $entry;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 1) . ' ' . $units[$i];
    }

    /**
     * POST /api/cli/db/fix-duplicates — Find and remove duplicate inscriptions
     */
    public function fixDuplicates(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $dryRun = $request->boolean('dry_run', true);

        try {
            $duplicates = DB::table('esbtp_inscriptions')
                ->select(
                    'etudiant_id', 'annee_universitaire_id', 'classe_id',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('GROUP_CONCAT(id ORDER BY id) as ids')
                )
                ->whereNull('deleted_at')
                ->groupBy('etudiant_id', 'annee_universitaire_id', 'classe_id')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            if ($duplicates->isEmpty()) {
                return $this->successResponse([
                    'duplicates_found' => 0,
                    'deleted' => 0,
                ], 'No duplicates found');
            }

            $details = [];
            $totalDeleted = 0;

            foreach ($duplicates as $dupe) {
                $allIds = array_map('intval', explode(',', $dupe->ids));

                // Score each inscription: paiements count + notes count + status weight
                $scores = [];
                foreach ($allIds as $id) {
                    $paiements = DB::table('esbtp_paiements')->where('inscription_id', $id)->count();
                    $notes = DB::table('esbtp_notes')
                        ->where('etudiant_id', $dupe->etudiant_id)
                        ->where('classe_id', $dupe->classe_id)
                        ->count();
                    $inscription = DB::table('esbtp_inscriptions')->where('id', $id)->first();
                    $statusWeight = match ($inscription->status ?? '') {
                        'active' => 100,
                        'en_attente' => 50,
                        default => 0,
                    };
                    $workflowWeight = ($inscription->workflow_step ?? '') === 'etudiant_cree' ? 200 : 0;

                    $scores[$id] = [
                        'total' => $paiements + $statusWeight + $workflowWeight,
                        'paiements' => $paiements,
                        'notes' => $notes,
                        'status' => $inscription->status ?? 'unknown',
                        'workflow' => $inscription->workflow_step ?? 'unknown',
                    ];
                }

                // Keep the one with highest score (ties: keep oldest = lowest id)
                uasort($scores, fn($a, $b) => $b['total'] <=> $a['total']);
                $keepId = array_key_first($scores);
                $deleteIds = array_values(array_diff($allIds, [$keepId]));

                $detail = [
                    'etudiant_id' => $dupe->etudiant_id,
                    'annee_universitaire_id' => $dupe->annee_universitaire_id,
                    'classe_id' => $dupe->classe_id,
                    'count' => $dupe->count,
                    'keep_id' => $keepId,
                    'keep_reason' => $scores[$keepId],
                    'delete_ids' => $deleteIds,
                    'delete_scores' => array_map(fn($id) => $scores[$id], $deleteIds),
                ];

                if (!$dryRun) {
                    $deleted = DB::table('esbtp_inscriptions')
                        ->whereIn('id', $deleteIds)
                        ->delete();
                    $detail['deleted'] = $deleted;
                    $totalDeleted += $deleted;
                }

                $details[] = $detail;
            }

            return $this->successResponse([
                'dry_run' => $dryRun,
                'duplicates_found' => $duplicates->count(),
                'total_deleted' => $dryRun ? 0 : $totalDeleted,
                'details' => $details,
            ], $dryRun ? 'Dry run — no changes made' : "Deleted {$totalDeleted} duplicate inscriptions");
        } catch (\Exception $e) {
            Log::error('CLI: fix-duplicates failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * POST /api/cli/migrate — Run migrations + fix partial failures
     */
    public function migrate(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        try {
            $results = ['steps' => []];

            // 1. Try migrations — catch SQL exceptions for partial failures
            $migrationError = null;
            try {
                $exitCode = Artisan::call('migrate', ['--force' => true]);
                $output = trim(Artisan::output());
                $results['steps'][] = ['action' => 'migrate --force', 'exit_code' => $exitCode, 'output' => $output];
            } catch (\Exception $e) {
                $exitCode = 1;
                $output = $e->getMessage();
                $migrationError = $output;
                $results['steps'][] = ['action' => 'migrate --force', 'exit_code' => 1, 'output' => $output];
            }

            if ($exitCode !== 0 && (str_contains($output, 'Column already exists') || str_contains($output, 'Duplicate column'))) {
                // Partial migration failure — columns exist but index/migration record missing
                // Find the failed migration name from output or pending list
                $failedMigration = null;
                if (preg_match('/(\d{4}_\d{2}_\d{2}_\d+_\w+)/', $output, $m)) {
                    $failedMigration = $m[1];
                }
                if (!$failedMigration) {
                    // Check for pending migrations that match the error context
                    $pending = DB::table('migrations')->pluck('migration')->toArray();
                    $files = glob(database_path('migrations/*.php'));
                    foreach ($files as $file) {
                        $name = pathinfo($file, PATHINFO_FILENAME);
                        if (!in_array($name, $pending) && str_contains($name, 'inscription')) {
                            $failedMigration = $name;
                            break;
                        }
                    }
                }

                // Try to add the missing unique index
                $indexCreated = false;
                try {
                    $existingIndexes = collect(DB::select('SHOW INDEX FROM esbtp_inscriptions'))
                        ->pluck('Key_name')->unique()->toArray();

                    if (!in_array('inscriptions_etudiant_annee_classe_unique', $existingIndexes)) {
                        DB::statement('ALTER TABLE esbtp_inscriptions ADD UNIQUE inscriptions_etudiant_annee_classe_unique (etudiant_id, annee_universitaire_id, classe_id)');
                        $indexCreated = true;
                        $results['steps'][] = ['action' => 'create_unique_index', 'status' => 'created'];
                    } else {
                        $results['steps'][] = ['action' => 'create_unique_index', 'status' => 'already_exists'];
                    }
                } catch (\Exception $e) {
                    $results['steps'][] = ['action' => 'create_unique_index', 'status' => 'failed', 'error' => $e->getMessage()];
                }

                // Drop old unique index on (etudiant_id, annee_universitaire_id, status) if exists
                try {
                    $indexes = DB::select('SHOW INDEX FROM esbtp_inscriptions');
                    $indexMap = [];
                    foreach ($indexes as $idx) {
                        $indexMap[$idx->Key_name][] = $idx->Column_name;
                    }
                    foreach ($indexMap as $name => $cols) {
                        sort($cols);
                        $target = ['annee_universitaire_id', 'etudiant_id', 'status'];
                        if ($cols === $target) {
                            DB::statement("ALTER TABLE esbtp_inscriptions DROP INDEX `{$name}`");
                            $results['steps'][] = ['action' => 'drop_old_index', 'index' => $name, 'status' => 'dropped'];
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    $results['steps'][] = ['action' => 'drop_old_index', 'status' => 'skipped', 'error' => $e->getMessage()];
                }

                // Mark migration as done
                if ($failedMigration) {
                    $exists = DB::table('migrations')->where('migration', $failedMigration)->exists();
                    if (!$exists) {
                        $batch = DB::table('migrations')->max('batch') + 1;
                        DB::table('migrations')->insert(['migration' => $failedMigration, 'batch' => $batch]);
                        $results['steps'][] = ['action' => 'mark_migration_done', 'migration' => $failedMigration, 'batch' => $batch];
                    }
                }

                // Re-run remaining migrations
                $exitCode2 = Artisan::call('migrate', ['--force' => true]);
                $output2 = trim(Artisan::output());
                $results['steps'][] = ['action' => 'migrate_retry', 'exit_code' => $exitCode2, 'output' => $output2];
            }

            // 2. Clear caches
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            Artisan::call('permission:cache-reset');
            $results['steps'][] = ['action' => 'cache_clear', 'status' => 'done'];

            $failed = collect($results['steps'])->contains(fn($s) => ($s['status'] ?? '') === 'failed');

            return $this->successResponse($results, $failed ? 'Migration completed with warnings' : 'Migration completed successfully');
        } catch (\Exception $e) {
            Log::error('CLI: migrate failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Migration failed: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * POST /api/cli/permissions/fix — Sync all permissions and roles.
     *
     * Legacy endpoint kept for klassci-cli backward compatibility.
     * Now delegates to the registry-driven flow (config/permissions.php) so any
     * permission added to the registry — including academic ones (annees.*) —
     * gets synced automatically. Prefer /api/cli/permissions/sync going forward.
     */
    public function permissionsFix(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        return app(CLIPermissionController::class)->sync(
            $request,
            app(\App\Services\PermissionSyncService::class)
        );
    }

    /**
     * @deprecated Hardcoded fallback — kept for emergency rollback only.
     * Real sync happens in CLIPermissionController::sync via PermissionRegistry.
     */
    private function legacyHardcodedSync(Request $request): JsonResponse
    {
        try {
            // Reset permission cache
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            // All permissions from the canonical list
            $permissions = [
                'view_dashboard', 'access_admin',
                'view_students', 'create_students', 'edit_students', 'delete_students', 'view_own_students',
                'view_inscriptions', 'create_inscriptions', 'edit_inscriptions', 'approve_inscriptions', 'reject_inscriptions',
                'inscriptions.view', 'inscriptions.create', 'inscriptions.edit', 'inscriptions.delete', 'inscriptions.validate',
                'edit inscriptions', 'valider inscriptions', 'annuler inscriptions', 'delete inscriptions',
                'paiements.view', 'paiements.create', 'paiements.edit', 'paiements.delete', 'paiements.validate',
                'frais.view', 'frais.create', 'frais.edit', 'frais.delete', 'frais.configure',
                'security.audit.view', 'security.audit.export', 'comptabilite.audit.view', 'security.users.monitor',
                'generate-attendance-codes',
                'manage-planning', 'view-all-timetables', 'view_timetables', 'create_timetable', 'edit_timetables', 'delete_timetables', 'view_own_timetable',
                'view cycles', 'create cycles', 'edit cycles', 'delete cycles', 'restore cycles', 'force delete cycles',
                'view_classes', 'create_classes', 'edit_classes', 'delete_classes',
                'view_filieres', 'create_filieres', 'edit_filieres',
                'view_niveaux_etudes', 'create_niveaux_etudes', 'edit_niveaux_etudes', 'delete_niveaux_etudes',
                'view_matieres', 'create_matieres', 'edit_matieres', 'delete_matieres',
                'view_notes', 'create_notes', 'edit_notes', 'edit_existing_notes', 'view_own_notes', 'manage_own_notes',
                'view_grades', 'view_own_grades', 'create_grade', 'edit_grades', 'delete_grades',
                'view_evaluations', 'view_own_exams', 'create_evaluations', 'edit_evaluations',
                'view_bulletins', 'generate_bulletins', 'edit_bulletins', 'view_own_bulletin',
                'view_attendances', 'create_attendance', 'create_attendances', 'edit_attendances', 'delete_attendances',
                'view_own_attendances', 'sign_attendance', 'view_own_attendance',
                'view_payments', 'create_payments', 'edit_payments', 'view_comptabilite', 'manage_comptabilite',
                'view_teachers', 'create_teachers', 'edit_teachers', 'view_personnel', 'manage_personnel', 'view_own_profile',
                'view_coordinateurs', 'create_coordinateurs', 'edit_coordinateurs', 'delete_coordinateurs',
                'view_schedules', 'create_schedules', 'edit_schedules', 'view_own_schedule',
                'send_messages', 'receive_messages', 'view_annonces', 'create_annonces', 'edit_annonces',
                'view_reports', 'generate_reports',
                'view_settings', 'edit_settings', 'manage_system',
                'view_planning_general', 'edit_planning_general', 'view_resultats', 'edit_resultats',
                'module.enseignants.access', 'module.notes_evaluations.access', 'module.emploi_temps.access',
                'module.presences.access', 'module.lmd.access', 'module.academique.access',
                'module.etudiants.access', 'module.comptabilite.access', 'module.communication.access',
                'manage-users', 'edit_enseignants', 'edit_bulletins',
                'paywall.configure', 'paywall.manage_subscriptions', 'paywall.extend_subscriptions', 'paywall.view_all_stats',
                'system.technical_access', 'system.emergency_override',
                'comptabilite.access', 'comptabilite.dashboard.view', 'comptabilite.relances.send',
                'comptabilite.reports.export', 'comptabilite.config.manage',
                'comptabilite.paiements.view', 'comptabilite.paiements.validate',
                'comptabilite.frais.view', 'comptabilite.frais.configure',
            ];

            $createdPermissions = 0;
            foreach ($permissions as $permName) {
                Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
                $createdPermissions++;
            }

            // Roles
            $roles = [
                'superAdmin', 'admin', 'secretaire', 'coordinateur',
                'enseignant', 'etudiant', 'parent', 'serviceTechnique',
                'teacher', 'comptable', 'caissier',
            ];

            $createdRoles = 0;
            foreach ($roles as $roleName) {
                Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
                $createdRoles++;
            }

            // Sync superAdmin with all permissions
            $superAdminRole = Role::findByName('superAdmin');
            $superAdminRole->syncPermissions($permissions);

            // Sync admin with all permissions
            $adminRole = Role::findByName('admin');
            $adminRole->syncPermissions($permissions);

            // Reset cache after sync
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            return $this->successResponse([
                'permissions_synced' => $createdPermissions,
                'roles_synced' => $createdRoles,
                'superadmin_permissions' => count($permissions),
            ], 'Permissions and roles synced successfully');
        } catch (\Exception $e) {
            Log::error('CLI: permissions fix failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->errorResponse('Operation failed. Check server logs for details.', [], 500);
        }
    }

    /**
     * POST /api/cli/pull — Git pull la branche courante du tenant + cache:clear.
     *
     * Permet aux futurs deploys de se faire entièrement via CLI (au lieu de SSH).
     * Pour appliquer les migrations après pull, enchaîner avec POST /api/cli/migrate.
     */
    public function pull(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $steps = [];
        $cwd = base_path();

        try {
            $branchProcess = \Symfony\Component\Process\Process::fromShellCommandline('git rev-parse --abbrev-ref HEAD', $cwd);
            $branchProcess->setTimeout(15);
            $branchProcess->run();
            $branch = trim($branchProcess->getOutput()) ?: 'HEAD';
            $steps[] = ['action' => 'detect branch', 'status' => 'done', 'branch' => $branch];

            // Stash local changes (cas presentation où on a parfois des fichiers modifiés)
            $stashProcess = \Symfony\Component\Process\Process::fromShellCommandline('git stash --include-untracked', $cwd);
            $stashProcess->setTimeout(30);
            $stashProcess->run();
            $stashOutput = trim($stashProcess->getOutput() . $stashProcess->getErrorOutput());
            $stashed = !str_contains($stashOutput, 'No local changes to save');
            $steps[] = ['action' => 'git stash', 'status' => $stashed ? 'stashed' : 'skipped', 'output' => $stashOutput];

            // Pull
            $pullProcess = \Symfony\Component\Process\Process::fromShellCommandline("git pull origin {$branch}", $cwd);
            $pullProcess->setTimeout(120);
            $pullProcess->run();
            $pullOutput = trim($pullProcess->getOutput() . "\n" . $pullProcess->getErrorOutput());
            $pullExit = $pullProcess->getExitCode();
            $steps[] = ['action' => 'git pull', 'exit_code' => $pullExit, 'output' => $pullOutput];

            if ($pullExit !== 0) {
                if ($stashed) {
                    $popProcess = \Symfony\Component\Process\Process::fromShellCommandline('git stash pop', $cwd);
                    $popProcess->setTimeout(15);
                    $popProcess->run();
                    $steps[] = ['action' => 'git stash pop (after fail)', 'output' => trim($popProcess->getOutput() . $popProcess->getErrorOutput())];
                }
                return $this->errorResponse('git pull failed', ['steps' => $steps], 500);
            }

            // Drop stash silently (les changes locaux étaient sans doute des artefacts)
            if ($stashed) {
                $dropProcess = \Symfony\Component\Process\Process::fromShellCommandline('git stash drop', $cwd);
                $dropProcess->setTimeout(15);
                $dropProcess->run();
                $steps[] = ['action' => 'git stash drop', 'status' => 'done'];
            }

            // Cache clear pour que le nouveau code soit visible
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('cache:clear');
            $steps[] = ['action' => 'cache_clear', 'status' => 'done'];

            return $this->successResponse([
                'branch' => $branch,
                'steps' => $steps,
            ], 'Pull + cache:clear OK');
        } catch (\Throwable $e) {
            Log::error('CLI: pull failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Pull failed: ' . $e->getMessage(), ['steps' => $steps], 500);
        }
    }

    /**
     * POST /api/cli/seed-demo — Run PresentationDemoSeeder.
     *
     * Strictly limited to the 'presentation' tenant via a double guard:
     *  1. Server-side check on config('app.tenant_code')
     *  2. The seeder itself re-checks before running (defense in depth)
     */
    public function seedDemo(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $tenant = (string) config('app.tenant_code', 'default');
        if ($tenant !== 'presentation') {
            return $this->errorResponse(
                'Demo seed is only available on the "presentation" tenant. Current tenant: ' . $tenant,
                ['tenant' => $tenant],
                403
            );
        }

        try {
            // OPCache reset : sur prod, les fichiers PHP modifiés peuvent
            // rester en cache bytecode tant que PHP-FPM n'est pas redémarré.
            // On force une invalidation pour garantir que le seeder fraîchement
            // déployé (et ses sous-classes Demo/) est bien chargé.
            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }

            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\PresentationDemoSeeder',
                '--force' => true,
            ]);
            $output = trim(Artisan::output());

            return $this->successResponse([
                'tenant' => $tenant,
                'output' => $output,
            ], 'PresentationDemoSeeder completed');
        } catch (\Throwable $e) {
            Log::error('CLI: seed demo failed', [
                'tenant' => $tenant,
                'error'  => $e->getMessage(),
                'trace'  => $e->getTraceAsString(),
            ]);
            return $this->errorResponse('Seed failed: ' . $e->getMessage(), [], 500);
        }
    }
}
