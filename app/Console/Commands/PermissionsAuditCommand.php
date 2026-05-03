<?php

namespace App\Console\Commands;

use App\Services\PermissionRegistry;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Symfony\Component\Finder\Finder;

/**
 * Audite la cohérence du système de permissions :
 *
 * - Cassées      : référencées en code mais absentes du registry ET de la DB
 * - Hors-registry: référencées en code, en DB, mais pas dans le registry
 *                  (à ajouter au registry ou à canoniser)
 * - Aliases used : aliases legacy utilisés en code (à migrer Lot 6)
 * - Orphelines   : en DB mais jamais référencées (à supprimer Lot 7)
 *
 * Usage : php artisan permissions:audit
 *         php artisan permissions:audit --json
 */
class PermissionsAuditCommand extends Command
{
    protected $signature = 'permissions:audit {--json : Sortie JSON (storage/app/permissions-audit.json)}';

    protected $description = 'Audite la cohérence rôles/permissions code ↔ registry ↔ DB';

    /**
     * Policy abilities standard (Laravel) — à ignorer (gérées par Policy classes,
     * pas par permissions Spatie).
     */
    private const POLICY_ABILITIES = [
        'view', 'viewAny', 'view-any', 'create', 'update', 'delete',
        'restore', 'forceDelete', 'force-delete', 'replicate',
        'download', 'upload',
    ];

    /** Permissions référencées en code (chaque clé = nom utilisé) */
    private array $usedInCode = [];

    public function handle(PermissionRegistry $registry): int
    {
        $this->info('🔍 Audit du système de permissions');
        $this->newLine();

        $this->scanCode();

        $registryNames = $registry->all()->keys()->all();
        $aliasMap = $registry->aliasMap();
        $allRegistryNames = $registry->allNames()->all();

        try {
            $dbNames = Permission::pluck('name')->all();
            $dbAvailable = true;
        } catch (\Throwable $e) {
            $this->warn('⚠️  DB indisponible — audit limité au code et au registry');
            $this->warn('   ('.$e->getMessage().')');
            $this->newLine();
            $dbNames = [];
            $dbAvailable = false;
        }

        $broken = [];        // référencé en code, ni en registry ni en DB
        $offRegistry = [];   // référencé en code, en DB, mais pas dans le registry
        $aliasesUsed = [];   // alias legacy utilisé en code (à migrer)
        $dbNamesIndex = array_flip($dbNames);

        foreach ($this->usedInCode as $name => $locations) {
            $sample = array_slice($locations, 0, 3);

            if (isset($aliasMap[$name])) {
                $aliasesUsed[$name] = [
                    'canonical' => $aliasMap[$name],
                    'locations' => $sample,
                ];
                continue;
            }

            if ($registry->isCanonical($name)) {
                continue; // OK, canonique utilisé directement
            }

            // Pas dans le registry
            if (isset($dbNamesIndex[$name])) {
                $offRegistry[$name] = ['locations' => $sample];
            } else {
                $broken[$name] = ['locations' => $sample];
            }
        }

        $allRegistryIndex = array_flip($allRegistryNames);
        $orphaned = [];
        $deprecatedAssigned = [];
        foreach ($dbNames as $dbName) {
            if (! isset($this->usedInCode[$dbName]) && ! isset($allRegistryIndex[$dbName])) {
                $orphaned[] = $dbName;
            }
            if ($registry->isDeprecated($dbName)) {
                $deprecatedAssigned[$dbName] = $registry->deprecatedReason($dbName);
            }
        }

        $brokenLabel = $dbAvailable
            ? '🔴 Cassées (référencées en code, ni registry ni DB)'
            : '🔴 Cassées (référencées en code, hors registry — DB non vérifiée)';
        $this->report($brokenLabel, $broken);
        if ($dbAvailable) {
            $this->report('🟠 Hors-registry (référencées + DB, à ajouter au registry)', $offRegistry);
        }
        $this->report('🟡 Aliases legacy utilisés en code (à migrer Lot 6)', $aliasesUsed);
        if ($dbAvailable) {
            $this->reportSimple('⚪ Orphelines en DB (jamais référencées, à supprimer Lot 7)', $orphaned);
            $this->reportSimple('📦 Deprecated encore en DB', array_keys($deprecatedAssigned));
        }

        $this->newLine();
        $this->line('═══════════════════════════════════════════════════════');
        $this->line(sprintf(
            '  Référencées en code : %d  |  Registry : %d  |  DB : %d',
            count($this->usedInCode),
            count($registryNames),
            count($dbNames)
        ));
        $this->line('═══════════════════════════════════════════════════════');

        if ($this->option('json')) {
            $path = storage_path('app/permissions-audit.json');
            file_put_contents($path, json_encode([
                'broken' => $broken,
                'off_registry' => $offRegistry,
                'aliases_used' => $aliasesUsed,
                'orphaned' => $orphaned,
                'deprecated_in_db' => $deprecatedAssigned,
                'summary' => [
                    'used_in_code' => count($this->usedInCode),
                    'in_registry' => count($registryNames),
                    'in_db' => count($dbNames),
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info("📄 Rapport JSON : {$path}");
        }

        return empty($broken) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Scanne tous les fichiers pertinents et collecte les permissions référencées.
     */
    private function scanCode(): void
    {
        $patterns = [
            // @can('xxx'), @canany([...]), @cannot('xxx')
            '/@can(?:any|not)?\s*\(\s*[\'"]([^\'"]+)[\'"]/',
            // permission:xxx en middleware (NOT Artisan::call('permission:xxx'))
            '/middleware\s*\(\s*\[?\s*[\'"]permission:([^\'",]+?)[\'"|]/',
            '/->middleware\s*\(\s*\[?\s*[\'"]permission:([^\'",]+?)[\'"|]/',
            // hasPermissionTo('xxx'), can('xxx') sans 2e arg (avec 2e arg = Policy)
            '/->(?:hasPermissionTo|can)\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/',
            // authorize('xxx') — sans 2e arg = permission. authorize('xxx', $model) = policy
            '/->authorize\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/',
            // Gate::define('xxx', ...) / Gate::has('xxx', ...)
            '/Gate::(?:define|has|allows|denies)\s*\(\s*[\'"]([^\'"]+)[\'"]/',
        ];

        $finder = (new Finder())
            ->files()
            ->in([
                base_path('app'),
                base_path('routes'),
                base_path('resources/views'),
                base_path('config'),
            ])
            ->name('*.php')
            ->name('*.blade.php')
            ->notPath('Console/Commands/PermissionsAuditCommand.php')
            ->notPath('storage')
            ->notPath('vendor')
            ->notPath('node_modules');

        foreach ($finder as $file) {
            $content = $file->getContents();
            $relPath = str_replace(base_path().'/', '', $file->getRealPath());

            foreach ($patterns as $pattern) {
                if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                    foreach ($matches[1] as $match) {
                        $name = $match[0];
                        // Skip dynamic names ($var, {$var})
                        if (str_contains($name, '$') || str_contains($name, '{')) {
                            continue;
                        }
                        // Skip noise / spaces in middle (likely false match from string concat)
                        if (str_contains($name, ' OK')) {
                            continue;
                        }
                        // Skip Policy abilities standard
                        if (in_array($name, self::POLICY_ABILITIES, true)) {
                            continue;
                        }
                        $line = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                        $this->usedInCode[$name][] = "{$relPath}:{$line}";
                    }
                }
            }
        }
    }

    private function report(string $title, array $items): void
    {
        $this->newLine();
        $this->line($title.' : '.count($items));
        if (empty($items)) {
            $this->line('  ✓ Rien à signaler');
            return;
        }

        $rows = [];
        foreach ($items as $name => $meta) {
            if (isset($meta['canonical'])) {
                $rows[] = [$name, '→ '.$meta['canonical'], implode("\n", $meta['locations'])];
            } else {
                $rows[] = [$name, '', implode("\n", $meta['locations'] ?? [])];
            }
            if (count($rows) >= 25) {
                $rows[] = ['...', '', '('.(count($items) - 25).' de plus)'];
                break;
            }
        }
        $this->table(['Nom', 'Canonique', 'Locations (max 3)'], $rows);
    }

    private function reportSimple(string $title, array $items): void
    {
        $this->newLine();
        $this->line($title.' : '.count($items));
        if (empty($items)) {
            $this->line('  ✓ Rien à signaler');
            return;
        }
        foreach (array_slice($items, 0, 30) as $item) {
            $this->line('  • '.$item);
        }
        if (count($items) > 30) {
            $this->line('  ... ('.(count($items) - 30).' de plus)');
        }
    }
}
