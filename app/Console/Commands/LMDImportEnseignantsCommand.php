<?php

namespace App\Console\Commands;

use App\Services\LMD\LMDEnseignantsImporter;
use Illuminate\Console\Command;

/**
 * Import bulk enseignants UEMOA depuis JSONs extraits des maquettes PDF
 * (DROIT / LETTRES-MOD / SVT / SEG).
 *
 * Usage :
 *   php artisan lmd:import-enseignants droit --dry-run
 *   php artisan lmd:import-enseignants svt --include-inferred-responsable
 *   php artisan lmd:import-enseignants all
 *
 * Source des JSONs : database/seeds-data/lmd-enseignants/*.json
 * (extraits par py + pypdf — voir CHANGELOG W1.3 et `feature-delivery-methodology`).
 *
 * Permission CLI distante : voir endpoint /api/cli/lmd/import-enseignants
 * (Sanctum + tokenCan('cli:admin') — équivalent permission `lmd.planning.edit`
 * côté UI web).
 */
class LMDImportEnseignantsCommand extends Command
{
    /**
     * Mapping filière → fichier JSON dans database/seeds-data/lmd-enseignants/.
     * Source unique de vérité — utilisé par la commande Artisan ET l'endpoint CLI.
     */
    public const FILIERE_FILES = [
        'droit' => 'licence-droit-enseignants.json',
        'lettres-modernes' => 'licence-lettres-modernes-enseignants.json',
        'svt' => 'licence-svt-enseignants.json',
        'economie' => 'licence-economie-enseignants.json',
    ];

    protected $signature = 'lmd:import-enseignants
                            {filiere : droit|lettres-modernes|svt|economie|all}
                            {--dry-run : Affiche ce qui serait importé sans persister en DB}
                            {--include-inferred-responsable : Assigne aussi les Responsables UE inférés (par défaut: skip)}';

    protected $description = 'Import enseignants UEMOA depuis JSONs extraits des maquettes PDF (W1.3 LMD)';

    public function handle(): int
    {
        $filiere = (string) $this->argument('filiere');
        $dryRun = (bool) $this->option('dry-run');
        $includeInferred = (bool) $this->option('include-inferred-responsable');

        $files = $this->resolveFiles($filiere);
        if (empty($files)) {
            $this->error("Filière invalide: {$filiere}. Valeurs acceptées: droit|lettres-modernes|svt|economie|all");
            return self::FAILURE;
        }

        if ($dryRun) {
            $this->warn('=== MODE DRY-RUN — aucun commit DB (transaction rollback systématique) ===');
        } else {
            $this->info('=== MODE LIVE — les modifications seront persistées ===');
        }

        if ($includeInferred) {
            $this->line('Option --include-inferred-responsable activée : les Responsables UE inférés depuis les PDFs seront assignés.');
        } else {
            $this->line('Responsables UE inférés : SKIP (utiliser --include-inferred-responsable pour les inclure).');
        }
        $this->newLine();

        $importer = new LMDEnseignantsImporter($dryRun, $includeInferred);
        $totalStats = [
            'users_created' => 0,
            'users_matched' => 0,
            'ecues_assigned' => 0,
            'ecues_not_found' => 0,
            'ues_assigned_responsable' => 0,
            'ues_not_found' => 0,
            'warnings' => [],
        ];

        foreach ($files as $file) {
            $path = database_path("seeds-data/lmd-enseignants/{$file}");
            $this->info("→ Import : {$file}");

            try {
                $importer->resetStats();
                $stats = $importer->importFile($path);
            } catch (\Throwable $e) {
                $this->error("Erreur import {$file} : " . $e->getMessage());
                return self::FAILURE;
            }

            foreach (['users_created', 'users_matched', 'ecues_assigned', 'ecues_not_found', 'ues_assigned_responsable', 'ues_not_found'] as $k) {
                $totalStats[$k] += $stats[$k];
            }
            $totalStats['warnings'] = array_merge($totalStats['warnings'], $stats['warnings']);

            $this->line(sprintf(
                '   users: %d créés / %d matchés · ECUEs: %d assignés / %d introuvables · UEs: %d responsable assigné / %d introuvables · %d warnings',
                $stats['users_created'],
                $stats['users_matched'],
                $stats['ecues_assigned'],
                $stats['ecues_not_found'],
                $stats['ues_assigned_responsable'],
                $stats['ues_not_found'],
                count($stats['warnings']),
            ));
        }

        $this->newLine();
        $this->info('=== Récapitulatif total ===');
        $this->table(['Indicateur', 'Valeur'], [
            ['Users créés', $totalStats['users_created']],
            ['Users matchés (existants)', $totalStats['users_matched']],
            ['ECUEs avec enseignant assigné (planifications)', $totalStats['ecues_assigned']],
            ['ECUEs introuvables (code matière manquant)', $totalStats['ecues_not_found']],
            ['UEs avec responsable assigné', $totalStats['ues_assigned_responsable']],
            ['UEs introuvables (code UE manquant)', $totalStats['ues_not_found']],
            ['Warnings (total)', count($totalStats['warnings'])],
        ]);

        if (!empty($totalStats['warnings'])) {
            $this->newLine();
            $this->warn('Premiers warnings (20 max) :');
            foreach (array_slice($totalStats['warnings'], 0, 20) as $w) {
                $this->line('  • ' . $w);
            }
            if (count($totalStats['warnings']) > 20) {
                $this->line(sprintf('  … et %d autres warnings non affichés.', count($totalStats['warnings']) - 20));
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->comment('Dry-run terminé — aucun changement persisté. Relancez sans --dry-run pour appliquer.');
        } else {
            $this->newLine();
            $this->info('Import terminé avec succès.');
            $this->comment('Note : Les mots de passe temporaires loggués ci-dessus doivent être communiqués aux enseignants via un canal sécurisé séparé. Ils doivent les changer au premier login (`must_change_password = true`).');
        }

        return self::SUCCESS;
    }

    /**
     * Résout la liste de fichiers à traiter selon l'argument filière.
     *
     * @return array<int, string>
     */
    private function resolveFiles(string $filiere): array
    {
        if ($filiere === 'all') {
            return array_values(self::FILIERE_FILES);
        }
        return isset(self::FILIERE_FILES[$filiere]) ? [self::FILIERE_FILES[$filiere]] : [];
    }
}
