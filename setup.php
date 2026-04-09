#!/usr/bin/env php
<?php

/**
 * KLASSCI Setup Script - Orchestrateur d'initialisation complète
 *
 * Ce script unifié exécute toutes les étapes d'initialisation :
 * - Configuration du stockage (storage/symlinks)
 * - Configuration des permissions Spatie
 * - Déploiement des paramètres système
 * - Exécution des seeders critiques
 *
 * Usage:
 *   php setup.php                    # Mode automatique complet
 *   php setup.php --interactive      # Mode interactif avec confirmations
 *   php setup.php --force            # Réexécuter même si déjà fait
 *   php setup.php --only=storage     # Exécuter seulement storage
 *   php setup.php --only=permissions # Exécuter seulement permissions
 *   php setup.php --only=settings    # Exécuter seulement settings
 *   php setup.php --only=seeders     # Exécuter seulement seeders
 *   php setup.php --skip=seeders     # Tout sauf seeders
 *
 * @author African Digit Consulting
 * @version 1.0
 */

// Vérifier que nous sommes à la racine du projet Laravel
if (!file_exists(__DIR__ . '/artisan')) {
    echo "❌ ERREUR: Ce script doit être exécuté à la racine du projet Laravel\n";
    exit(1);
}

// Classe principale
class KLASSCISetup
{
    private $lockFile = '.setup.lock';
    private $interactive = false;
    private $force = false;
    private $only = null;
    private $skip = [];
    private $lockData = [];
    private $baseDir;

    // Codes couleur ANSI
    private $colors = [
        'reset' => "\033[0m",
        'bold' => "\033[1m",
        'red' => "\033[31m",
        'green' => "\033[32m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'magenta' => "\033[35m",
        'cyan' => "\033[36m",
    ];

    public function __construct()
    {
        $this->baseDir = __DIR__;
        $this->loadLockFile();
    }

    /**
     * Point d'entrée principal
     */
    public function run(array $argv): int
    {
        $this->parseArguments($argv);

        $this->printHeader();

        if (!$this->force && $this->isFullySetup()) {
            $this->info("✅ Le système est déjà complètement configuré !");
            $this->info("📋 Exécutez 'php verify.php' pour voir les détails");
            $this->info("💡 Utilisez --force pour réexécuter l'initialisation");
            return 0;
        }

        try {
            // Ordre d'exécution OBLIGATOIRE (dépendances)
            $steps = [
                'storage' => 'Initialisation du stockage',
                'permissions' => 'Configuration des permissions',
                'settings' => 'Déploiement des paramètres',
                'seeders' => 'Exécution des seeders',
            ];

            foreach ($steps as $step => $label) {
                if ($this->shouldRun($step)) {
                    $this->section($label);

                    if ($this->interactive && !$this->confirm("Exécuter l'étape '$label' ?")) {
                        $this->warning("⏭️  Étape '$step' sautée (mode interactif)");
                        continue;
                    }

                    $method = 'run' . ucfirst($step);
                    $this->$method();
                }
            }

            $this->saveLockFile();
            $this->printSummary();

            return 0;
        } catch (Exception $e) {
            $this->error("❌ ERREUR FATALE: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Étape 1: Initialisation du stockage
     */
    private function runStorage(): void
    {
        $this->info("📁 Initialisation du stockage...");

        $script = $this->baseDir . '/bin/deploy/init_storage.php';

        if (!file_exists($script)) {
            throw new Exception("Script init_storage.php introuvable dans bin/deploy/");
        }

        $output = [];
        $returnCode = 0;
        exec("php \"$script\" 2>&1", $output, $returnCode);

        foreach ($output as $line) {
            echo $line . "\n";
        }

        // Le storage n'est pas bloquant — les dossiers sont créés même si le symlink échoue
        // On tente php artisan storage:link en dernier recours
        $publicStoragePath = $this->baseDir . '/public/storage';
        if (!is_link($publicStoragePath) && !is_dir($publicStoragePath)) {
            $this->warning("⚠️  Symlink manquant, essai avec php artisan storage:link...");
            $artisanCode = 0;
            exec("php artisan storage:link 2>&1", $artisanOutput, $artisanCode);
            if ($artisanCode === 0) {
                $this->success("✅ Symlink créé avec artisan storage:link");
            } else {
                $this->warning("⚠️  Symlink non créé. Exécutez manuellement: ln -s ../storage/app/public public/storage");
            }
        }

        $this->lockData['storage'] = [
            'status' => 'success',
            'date' => date('Y-m-d H:i:s'),
            'errors' => $returnCode !== 0 ? $output : []
        ];

        $this->success("✅ Stockage initialisé avec succès");
    }

    /**
     * Étape 2: Configuration des permissions
     */
    private function runPermissions(): void
    {
        $this->info("🔐 Configuration des permissions...");

        $script = $this->baseDir . '/bin/deploy/fix_permissions.php';

        if (!file_exists($script)) {
            throw new Exception("Script fix_permissions.php introuvable dans bin/deploy/");
        }

        $output = [];
        $returnCode = 0;
        exec("php \"$script\" 2>&1", $output, $returnCode);

        if ($returnCode !== 0) {
            $this->lockData['permissions'] = [
                'status' => 'failed',
                'date' => date('Y-m-d H:i:s'),
                'errors' => $output
            ];
            throw new Exception("Échec de la configuration des permissions");
        }

        foreach ($output as $line) {
            echo $line . "\n";
        }

        $this->lockData['permissions'] = [
            'status' => 'success',
            'date' => date('Y-m-d H:i:s'),
            'errors' => []
        ];

        $this->success("✅ Permissions configurées avec succès");
    }

    /**
     * Étape 3: Déploiement des paramètres
     */
    private function runSettings(): void
    {
        $this->info("⚙️  Déploiement des paramètres...");

        $script = $this->baseDir . '/bin/deploy/deploy_settings.php';

        if (!file_exists($script)) {
            throw new Exception("Script deploy_settings.php introuvable dans bin/deploy/");
        }

        $output = [];
        $returnCode = 0;
        exec("php \"$script\" 2>&1", $output, $returnCode);

        if ($returnCode !== 0) {
            $this->lockData['settings'] = [
                'status' => 'failed',
                'date' => date('Y-m-d H:i:s'),
                'errors' => $output
            ];
            throw new Exception("Échec du déploiement des paramètres");
        }

        foreach ($output as $line) {
            echo $line . "\n";
        }

        $this->lockData['settings'] = [
            'status' => 'success',
            'date' => date('Y-m-d H:i:s'),
            'errors' => []
        ];

        $this->success("✅ Paramètres déployés avec succès");
    }

    /**
     * Étape 4: Exécution des seeders
     */
    private function runSeeders(): void
    {
        $this->info("🌱 Exécution des seeders...");

        $seeders = [
            'ChatbotSeeder',
            'ServiceTechniqueSeeder',
            'SettingsSeeder'
        ];

        if (!isset($this->lockData['seeders'])) {
            $this->lockData['seeders'] = [];
        }

        foreach ($seeders as $seeder) {
            $this->info("  ▶ $seeder...");

            // Ajouter --force pour éviter la confirmation interactive en production
            $returnCode = 0;
            passthru("php artisan db:seed --class=$seeder --force 2>&1", $returnCode);

            if ($returnCode !== 0) {
                $this->lockData['seeders'][$seeder] = [
                    'status' => 'failed',
                    'date' => date('Y-m-d H:i:s'),
                    'errors' => ["Seeder a échoué avec le code de retour: $returnCode"]
                ];
                $this->warning("⚠️  $seeder a échoué");
            } else {
                $this->lockData['seeders'][$seeder] = [
                    'status' => 'success',
                    'date' => date('Y-m-d H:i:s'),
                    'errors' => []
                ];
                $this->success("  ✅ $seeder terminé");
            }
        }

        $this->success("✅ Seeders exécutés");
    }

    /**
     * Parse les arguments CLI
     */
    private function parseArguments(array $argv): void
    {
        foreach ($argv as $arg) {
            if ($arg === '--interactive' || $arg === '-i') {
                $this->interactive = true;
            } elseif ($arg === '--force' || $arg === '-f') {
                $this->force = true;
            } elseif (strpos($arg, '--only=') === 0) {
                $this->only = explode(',', substr($arg, 7));
            } elseif (strpos($arg, '--skip=') === 0) {
                $this->skip = explode(',', substr($arg, 7));
            }
        }
    }

    /**
     * Vérifie si une étape doit être exécutée
     */
    private function shouldRun(string $step): bool
    {
        if (in_array($step, $this->skip)) {
            return false;
        }

        if ($this->only !== null && !in_array($step, $this->only)) {
            return false;
        }

        return true;
    }

    /**
     * Charge le fichier lock
     */
    private function loadLockFile(): void
    {
        $lockPath = $this->baseDir . '/' . $this->lockFile;

        if (file_exists($lockPath)) {
            $content = file_get_contents($lockPath);
            $this->lockData = json_decode($content, true) ?? [];
        } else {
            $this->lockData = [
                'version' => '1.0',
                'last_run' => null,
            ];
        }
    }

    /**
     * Sauvegarde le fichier lock
     */
    private function saveLockFile(): void
    {
        $this->lockData['version'] = '1.0';
        $this->lockData['last_run'] = date('Y-m-d H:i:s');

        $lockPath = $this->baseDir . '/' . $this->lockFile;
        file_put_contents($lockPath, json_encode($this->lockData, JSON_PRETTY_PRINT));

        $this->info("📝 État sauvegardé dans $this->lockFile");
    }

    /**
     * Vérifie si le système est complètement configuré
     */
    private function isFullySetup(): bool
    {
        $required = ['storage', 'permissions', 'settings', 'seeders'];

        foreach ($required as $key) {
            if ($key === 'seeders') {
                // Vérifier que tous les seeders critiques sont OK
                if (!isset($this->lockData['seeders'])) {
                    return false;
                }
                $criticalSeeders = ['ChatbotSeeder', 'ServiceTechniqueSeeder', 'SettingsSeeder'];
                foreach ($criticalSeeders as $seeder) {
                    if (!isset($this->lockData['seeders'][$seeder]) ||
                        $this->lockData['seeders'][$seeder]['status'] !== 'success') {
                        return false;
                    }
                }
            } else {
                if (!isset($this->lockData[$key]) || $this->lockData[$key]['status'] !== 'success') {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Confirmation interactive
     */
    private function confirm(string $question): bool
    {
        echo $this->colors['yellow'] . "❓ $question [o/N] " . $this->colors['reset'];
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);
        return strtolower($line) === 'o' || strtolower($line) === 'oui';
    }

    /**
     * Affichage du header
     */
    private function printHeader(): void
    {
        echo $this->colors['bold'] . $this->colors['cyan'];
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════╗\n";
        echo "║                                                          ║\n";
        echo "║           🚀 KLASSCI SETUP - Initialisation             ║\n";
        echo "║                                                          ║\n";
        echo "║              African Digit Consulting                   ║\n";
        echo "║                                                          ║\n";
        echo "╚══════════════════════════════════════════════════════════╝\n";
        echo $this->colors['reset'] . "\n";
    }

    /**
     * Affichage du résumé
     */
    private function printSummary(): void
    {
        echo "\n";
        $this->section("📊 RÉSUMÉ DE L'INITIALISATION");

        $steps = ['storage', 'permissions', 'settings'];
        foreach ($steps as $step) {
            if (isset($this->lockData[$step])) {
                $status = $this->lockData[$step]['status'];
                $icon = $status === 'success' ? '✅' : '❌';
                $color = $status === 'success' ? 'green' : 'red';
                echo $this->colors[$color] . "$icon $step: $status" . $this->colors['reset'] . "\n";
            }
        }

        // Seeders
        if (isset($this->lockData['seeders'])) {
            echo $this->colors['bold'] . "\n🌱 Seeders:\n" . $this->colors['reset'];
            foreach ($this->lockData['seeders'] as $seeder => $data) {
                $status = $data['status'];
                $icon = $status === 'success' ? '✅' : '❌';
                $color = $status === 'success' ? 'green' : 'red';
                echo $this->colors[$color] . "  $icon $seeder: $status" . $this->colors['reset'] . "\n";
            }
        }

        echo "\n";
        $this->success("🎉 Initialisation terminée !");
        $this->info("📋 Exécutez 'php verify.php' pour vérifier l'état du système");
    }

    // Helpers d'affichage
    private function section(string $title): void
    {
        echo "\n" . $this->colors['bold'] . $this->colors['blue'];
        echo "═══ $title ═══\n";
        echo $this->colors['reset'];
    }

    private function info(string $message): void
    {
        echo $this->colors['cyan'] . $message . $this->colors['reset'] . "\n";
    }

    private function success(string $message): void
    {
        echo $this->colors['green'] . $message . $this->colors['reset'] . "\n";
    }

    private function warning(string $message): void
    {
        echo $this->colors['yellow'] . $message . $this->colors['reset'] . "\n";
    }

    private function error(string $message): void
    {
        echo $this->colors['red'] . $message . $this->colors['reset'] . "\n";
    }
}

// Exécution
$setup = new KLASSCISetup();
exit($setup->run($argv));
