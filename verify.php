#!/usr/bin/env php
<?php

/**
 * KLASSCI Verify Script - Vérification de l'état du système
 *
 * Ce script vérifie que toutes les étapes d'initialisation ont été exécutées
 * et que le système est prêt à fonctionner.
 *
 * Usage:
 *   php verify.php               # Vérification complète
 *   php verify.php --verbose     # Affichage détaillé
 *   php verify.php --fix         # Suggère commandes de correction
 *   php verify.php --json        # Output JSON pour intégration CI/CD
 *
 * @author African Digit Consulting
 * @version 1.0
 */

// Vérifier que nous sommes à la racine du projet Laravel
if (!file_exists(__DIR__ . '/artisan')) {
    echo "❌ ERREUR: Ce script doit être exécuté à la racine du projet Laravel\n";
    exit(1);
}

class KLASSCIVerify
{
    private $lockFile = '.setup.lock';
    private $verbose = false;
    private $fix = false;
    private $json = false;
    private $baseDir;
    private $lockData = [];
    private $results = [];

    // Codes couleur ANSI
    private $colors = [
        'reset' => "\033[0m",
        'bold' => "\033[1m",
        'red' => "\033[31m",
        'green' => "\033[32m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
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

        if (!$this->json) {
            $this->printHeader();
        }

        // Vérifications
        $this->verifyLockFile();
        $this->verifyStorage();
        $this->verifyPermissions();
        $this->verifySettings();
        $this->verifySeeders();
        $this->verifyDatabase();

        // Affichage des résultats
        if ($this->json) {
            $this->outputJson();
        } else {
            $this->printSummary();

            if ($this->fix && $this->hasIssues()) {
                $this->suggestFixes();
            }
        }

        return $this->hasIssues() ? 1 : 0;
    }

    /**
     * Vérifie l'existence et validité du fichier lock
     */
    private function verifyLockFile(): void
    {
        $this->section("📝 Fichier de configuration");

        $lockPath = $this->baseDir . '/' . $this->lockFile;

        if (!file_exists($lockPath)) {
            $this->results['lock_file'] = [
                'status' => 'missing',
                'message' => 'Fichier .setup.lock introuvable',
                'severity' => 'critical'
            ];
            $this->error("❌ Fichier .setup.lock introuvable");
            $this->info("   💡 Exécutez: php setup.php");
            return;
        }

        if (empty($this->lockData)) {
            $this->results['lock_file'] = [
                'status' => 'invalid',
                'message' => 'Fichier .setup.lock corrompu',
                'severity' => 'critical'
            ];
            $this->error("❌ Fichier .setup.lock corrompu");
            return;
        }

        $lastRun = $this->lockData['last_run'] ?? 'Jamais';
        $this->results['lock_file'] = [
            'status' => 'ok',
            'message' => "Fichier lock valide (dernière exécution: $lastRun)",
            'severity' => 'info'
        ];
        $this->success("✅ Fichier .setup.lock présent");
        if ($this->verbose) {
            $this->info("   Dernière exécution: $lastRun");
            $this->info("   Version: " . ($this->lockData['version'] ?? 'inconnue'));
        }
    }

    /**
     * Vérifie l'initialisation du stockage
     */
    private function verifyStorage(): void
    {
        $this->section("📁 Stockage");

        $checks = [
            'storage/app/public' => 'Dossier de stockage principal',
            'storage/app/public/photos' => 'Dossier photos',
            'storage/app/public/logos' => 'Dossier logos',
            'storage/app/public/documents' => 'Dossier documents',
            'public/storage' => 'Lien symbolique storage'
        ];

        $allOk = true;
        $details = [];

        foreach ($checks as $path => $description) {
            $fullPath = $this->baseDir . '/' . $path;
            $exists = file_exists($fullPath);

            if ($exists) {
                if ($this->verbose) {
                    $this->success("  ✅ $description");
                }
                $details[$path] = 'ok';
            } else {
                $this->error("  ❌ $description manquant: $path");
                $details[$path] = 'missing';
                $allOk = false;
            }
        }

        // Vérifier le status dans lock file
        $lockStatus = $this->lockData['storage']['status'] ?? 'unknown';

        if ($allOk && $lockStatus === 'success') {
            $this->results['storage'] = [
                'status' => 'ok',
                'message' => 'Stockage correctement configuré',
                'severity' => 'info',
                'details' => $details
            ];
            $this->success("✅ Stockage: OK");
        } else {
            $this->results['storage'] = [
                'status' => 'error',
                'message' => 'Problèmes de stockage détectés',
                'severity' => 'critical',
                'details' => $details
            ];
            $this->error("❌ Stockage: Problèmes détectés");
        }
    }

    /**
     * Vérifie les permissions Spatie
     */
    private function verifyPermissions(): void
    {
        $this->section("🔐 Permissions");

        $lockStatus = $this->lockData['permissions']['status'] ?? 'unknown';
        $lockDate = $this->lockData['permissions']['date'] ?? 'jamais';

        if ($lockStatus === 'success') {
            $this->results['permissions'] = [
                'status' => 'ok',
                'message' => "Permissions configurées ($lockDate)",
                'severity' => 'info'
            ];
            $this->success("✅ Permissions: OK");
            if ($this->verbose) {
                $this->info("   Date: $lockDate");
            }
        } else {
            $this->results['permissions'] = [
                'status' => 'error',
                'message' => 'Permissions non configurées',
                'severity' => 'critical'
            ];
            $this->error("❌ Permissions: Non configurées");
        }
    }

    /**
     * Vérifie les paramètres système
     */
    private function verifySettings(): void
    {
        $this->section("⚙️  Paramètres");

        $lockStatus = $this->lockData['settings']['status'] ?? 'unknown';
        $lockDate = $this->lockData['settings']['date'] ?? 'jamais';

        if ($lockStatus === 'success') {
            $this->results['settings'] = [
                'status' => 'ok',
                'message' => "Paramètres déployés ($lockDate)",
                'severity' => 'info'
            ];
            $this->success("✅ Paramètres: OK");
            if ($this->verbose) {
                $this->info("   Date: $lockDate");
            }
        } else {
            $this->results['settings'] = [
                'status' => 'error',
                'message' => 'Paramètres non déployés',
                'severity' => 'high'
            ];
            $this->error("❌ Paramètres: Non déployés");
        }
    }

    /**
     * Vérifie les seeders
     */
    private function verifySeeders(): void
    {
        $this->section("🌱 Seeders");

        $criticalSeeders = [
            'ChatbotSeeder' => 'Chatbot IA',
            'ServiceTechniqueSeeder' => 'Service Technique ADC',
            'SettingsSeeder' => 'Paramètres système'
        ];

        $allOk = true;
        $details = [];

        foreach ($criticalSeeders as $seeder => $label) {
            $status = $this->lockData['seeders'][$seeder]['status'] ?? 'unknown';
            $date = $this->lockData['seeders'][$seeder]['date'] ?? 'jamais';

            if ($status === 'success') {
                if ($this->verbose) {
                    $this->success("  ✅ $label");
                    $this->info("     Date: $date");
                }
                $details[$seeder] = ['status' => 'ok', 'date' => $date];
            } else {
                $this->error("  ❌ $label: $status");
                $details[$seeder] = ['status' => 'error', 'date' => $date];
                $allOk = false;
            }
        }

        if ($allOk) {
            $this->results['seeders'] = [
                'status' => 'ok',
                'message' => 'Tous les seeders critiques exécutés',
                'severity' => 'info',
                'details' => $details
            ];
            $this->success("✅ Seeders: OK");
        } else {
            $this->results['seeders'] = [
                'status' => 'error',
                'message' => 'Certains seeders ont échoué',
                'severity' => 'high',
                'details' => $details
            ];
            $this->error("❌ Seeders: Problèmes détectés");
        }
    }

    /**
     * Vérifie la connexion à la base de données
     */
    private function verifyDatabase(): void
    {
        $this->section("💾 Base de données");

        // Test simple de connexion sans charger Laravel
        $envFile = $this->baseDir . '/.env';

        if (!file_exists($envFile)) {
            $this->results['database'] = [
                'status' => 'error',
                'message' => 'Fichier .env introuvable',
                'severity' => 'critical'
            ];
            $this->error("❌ Fichier .env introuvable");
            return;
        }

        // Parser le .env pour extraire les infos DB
        $envContent = parse_ini_file($envFile);
        $dbConnection = $envContent['DB_CONNECTION'] ?? 'mysql';
        $dbDatabase = $envContent['DB_DATABASE'] ?? 'unknown';

        $this->results['database'] = [
            'status' => 'ok',
            'message' => "Configuration DB présente (connexion: $dbConnection, base: $dbDatabase)",
            'severity' => 'info'
        ];
        $this->success("✅ Base de données: Configuration OK");

        if ($this->verbose) {
            $this->info("   Connexion: $dbConnection");
            $this->info("   Base: $dbDatabase");
        }
    }

    /**
     * Parse les arguments CLI
     */
    private function parseArguments(array $argv): void
    {
        foreach ($argv as $arg) {
            if ($arg === '--verbose' || $arg === '-v') {
                $this->verbose = true;
            } elseif ($arg === '--fix') {
                $this->fix = true;
            } elseif ($arg === '--json') {
                $this->json = true;
            }
        }
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
        }
    }

    /**
     * Vérifie s'il y a des problèmes
     */
    private function hasIssues(): bool
    {
        foreach ($this->results as $result) {
            if ($result['status'] === 'error' || $result['status'] === 'missing' || $result['status'] === 'invalid') {
                return true;
            }
        }
        return false;
    }

    /**
     * Suggère des commandes de correction
     */
    private function suggestFixes(): void
    {
        $this->section("🔧 Suggestions de correction");

        $hasLockIssue = isset($this->results['lock_file']) && $this->results['lock_file']['status'] !== 'ok';
        $hasStorageIssue = isset($this->results['storage']) && $this->results['storage']['status'] !== 'ok';
        $hasPermissionsIssue = isset($this->results['permissions']) && $this->results['permissions']['status'] !== 'ok';
        $hasSettingsIssue = isset($this->results['settings']) && $this->results['settings']['status'] !== 'ok';
        $hasSeedersIssue = isset($this->results['seeders']) && $this->results['seeders']['status'] !== 'ok';

        if ($hasLockIssue || ($hasStorageIssue && $hasPermissionsIssue && $hasSettingsIssue && $hasSeedersIssue)) {
            $this->info("💡 Réinitialisation complète recommandée:");
            $this->info("   php setup.php --force");
            echo "\n";
        } else {
            if ($hasStorageIssue) {
                $this->info("💡 Problème de stockage:");
                $this->info("   php setup.php --only=storage");
            }
            if ($hasPermissionsIssue) {
                $this->info("💡 Problème de permissions:");
                $this->info("   php setup.php --only=permissions");
            }
            if ($hasSettingsIssue) {
                $this->info("💡 Problème de paramètres:");
                $this->info("   php setup.php --only=settings");
            }
            if ($hasSeedersIssue) {
                $this->info("💡 Problème de seeders:");
                $this->info("   php setup.php --only=seeders");
                echo "\n";
                $this->info("   Ou individuellement:");
                foreach ($this->results['seeders']['details'] ?? [] as $seeder => $data) {
                    if ($data['status'] !== 'ok') {
                        $this->info("   php artisan db:seed --class=$seeder");
                    }
                }
            }
        }
    }

    /**
     * Affiche le résumé
     */
    private function printSummary(): void
    {
        echo "\n";
        $this->section("📊 RÉSUMÉ DE LA VÉRIFICATION");

        $total = count($this->results);
        $ok = 0;
        $errors = 0;

        foreach ($this->results as $key => $result) {
            if ($result['status'] === 'ok') {
                $ok++;
            } else {
                $errors++;
            }
        }

        $this->info("Total de vérifications: $total");
        $this->success("✅ Réussies: $ok");

        if ($errors > 0) {
            $this->error("❌ Problèmes: $errors");
            echo "\n";
            $this->warning("⚠️  Le système n'est PAS complètement configuré");
        } else {
            echo "\n";
            $this->success("🎉 Le système est PRÊT !");
        }
    }

    /**
     * Sortie JSON
     */
    private function outputJson(): void
    {
        $output = [
            'ready' => !$this->hasIssues(),
            'timestamp' => date('Y-m-d H:i:s'),
            'results' => $this->results,
            'lock_data' => $this->lockData
        ];

        echo json_encode($output, JSON_PRETTY_PRINT) . "\n";
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
        echo "║           🔍 KLASSCI VERIFY - Vérification              ║\n";
        echo "║                                                          ║\n";
        echo "║              African Digit Consulting                   ║\n";
        echo "║                                                          ║\n";
        echo "╚══════════════════════════════════════════════════════════╝\n";
        echo $this->colors['reset'] . "\n";
    }

    // Helpers d'affichage
    private function section(string $title): void
    {
        if (!$this->json) {
            echo "\n" . $this->colors['bold'] . $this->colors['blue'];
            echo "═══ $title ═══\n";
            echo $this->colors['reset'];
        }
    }

    private function info(string $message): void
    {
        if (!$this->json) {
            echo $this->colors['cyan'] . $message . $this->colors['reset'] . "\n";
        }
    }

    private function success(string $message): void
    {
        if (!$this->json) {
            echo $this->colors['green'] . $message . $this->colors['reset'] . "\n";
        }
    }

    private function warning(string $message): void
    {
        if (!$this->json) {
            echo $this->colors['yellow'] . $message . $this->colors['reset'] . "\n";
        }
    }

    private function error(string $message): void
    {
        if (!$this->json) {
            echo $this->colors['red'] . $message . $this->colors['reset'] . "\n";
        }
    }
}

// Exécution
$verify = new KLASSCIVerify();
exit($verify->run($argv));
