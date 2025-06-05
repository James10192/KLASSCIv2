<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SyncGitHubFeatures extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'esbtp:sync-github {--feature=all : Specific feature to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise les nouvelles fonctionnalités depuis le repository GitHub ESBTP-yAKROv2';

    /**
     * Repository GitHub
     */
    private $owner = 'MD-Ruben';
    private $repo = 'ESBTP-yAKROv2';
    private $branch = 'main';

    /**
     * Fichiers à synchroniser
     */
    private $filesToSync = [
        // Bulletins
        'resources/views/esbtp/bulletins/bulletin-pdf.blade.php',
        'resources/views/esbtp/bulletins/index.blade.php',
        'resources/views/esbtp/bulletins/show.blade.php',

        // Assiduité
        'app/Http/Controllers/ESBTP/ESBTPAttendanceController.php',
        'resources/views/esbtp/attendance/index.blade.php',
        'resources/views/esbtp/attendance/create.blade.php',
        'resources/views/esbtp/attendance/show.blade.php',

        // Résultats
        'resources/views/esbtp/resultats/moyennes-preview.blade.php',
        'resources/views/esbtp/resultats/index.blade.php',

        // Contrôleurs
        'app/Http/Controllers/ESBTP/ESBTPBulletinController.php',
        'app/Http/Controllers/ESBTP/ESBTPResultatsController.php',
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔄 Synchronisation des fonctionnalités depuis GitHub...');

        $feature = $this->option('feature');

        if ($feature !== 'all') {
            $this->syncSpecificFeature($feature);
        } else {
            $this->syncAllFeatures();
        }

        return 0;
    }

    /**
     * Synchroniser toutes les fonctionnalités
     */
    private function syncAllFeatures()
    {
        $this->info('📥 Synchronisation de tous les fichiers...');

        $synced = 0;
        $errors = 0;

        foreach ($this->filesToSync as $filePath) {
            try {
                if ($this->syncFile($filePath)) {
                    $synced++;
                    $this->line("✅ {$filePath}");
                } else {
                    $errors++;
                    $this->error("❌ {$filePath}");
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("❌ {$filePath}: {$e->getMessage()}");
                Log::error("Erreur sync GitHub", [
                    'file' => $filePath,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->info("\n📊 Résumé:");
        $this->info("✅ Fichiers synchronisés: {$synced}");
        if ($errors > 0) {
            $this->warn("❌ Erreurs: {$errors}");
        }
    }

    /**
     * Synchroniser une fonctionnalité spécifique
     */
    private function syncSpecificFeature($feature)
    {
        $featureFiles = $this->getFeatureFiles($feature);

        if (empty($featureFiles)) {
            $this->error("❌ Fonctionnalité '{$feature}' non reconnue.");
            $this->info("Fonctionnalités disponibles: bulletins, attendance, resultats, controllers");
            return;
        }

        $this->info("📥 Synchronisation de la fonctionnalité '{$feature}'...");

        foreach ($featureFiles as $filePath) {
            try {
                if ($this->syncFile($filePath)) {
                    $this->line("✅ {$filePath}");
                } else {
                    $this->error("❌ {$filePath}");
                }
            } catch (\Exception $e) {
                $this->error("❌ {$filePath}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Obtenir les fichiers d'une fonctionnalité
     */
    private function getFeatureFiles($feature)
    {
        $features = [
            'bulletins' => [
                'resources/views/esbtp/bulletins/bulletin-pdf.blade.php',
                'resources/views/esbtp/bulletins/index.blade.php',
                'resources/views/esbtp/bulletins/show.blade.php',
                'app/Http/Controllers/ESBTP/ESBTPBulletinController.php',
            ],
            'attendance' => [
                'app/Http/Controllers/ESBTP/ESBTPAttendanceController.php',
                'resources/views/esbtp/attendance/index.blade.php',
                'resources/views/esbtp/attendance/create.blade.php',
                'resources/views/esbtp/attendance/show.blade.php',
            ],
            'resultats' => [
                'resources/views/esbtp/resultats/moyennes-preview.blade.php',
                'resources/views/esbtp/resultats/index.blade.php',
                'app/Http/Controllers/ESBTP/ESBTPResultatsController.php',
            ],
            'controllers' => [
                'app/Http/Controllers/ESBTP/ESBTPBulletinController.php',
                'app/Http/Controllers/ESBTP/ESBTPResultatsController.php',
                'app/Http/Controllers/ESBTP/ESBTPAttendanceController.php',
            ]
        ];

        return $features[$feature] ?? [];
    }

    /**
     * Synchroniser un fichier depuis GitHub
     */
    private function syncFile($filePath)
    {
        try {
            // Construire l'URL de l'API GitHub
            $url = "https://api.github.com/repos/{$this->owner}/{$this->repo}/contents/{$filePath}";

            // Faire la requête à l'API GitHub
            $response = Http::get($url, [
                'ref' => $this->branch
            ]);

            if (!$response->successful()) {
                if ($response->status() === 404) {
                    $this->warn("⚠️  Fichier non trouvé sur GitHub: {$filePath}");
                    return false;
                }
                throw new \Exception("Erreur API GitHub: " . $response->status());
            }

            $data = $response->json();

            // Vérifier que c'est un fichier (pas un dossier)
            if ($data['type'] !== 'file') {
                throw new \Exception("Le chemin spécifié n'est pas un fichier");
            }

            // Décoder le contenu base64
            $content = base64_decode($data['content']);

            // Créer le dossier parent si nécessaire
            $localPath = base_path($filePath);
            $directory = dirname($localPath);

            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            // Sauvegarder le fichier existant si il existe
            if (File::exists($localPath)) {
                $backupPath = $localPath . '.backup.' . date('Y-m-d_H-i-s');
                File::copy($localPath, $backupPath);
                $this->line("💾 Sauvegarde créée: {$backupPath}");
            }

            // Écrire le nouveau contenu
            File::put($localPath, $content);

            return true;

        } catch (\Exception $e) {
            Log::error("Erreur lors de la synchronisation du fichier", [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Lister les commits récents
     */
    public function listRecentCommits()
    {
        try {
            $url = "https://api.github.com/repos/{$this->owner}/{$this->repo}/commits";

            $response = Http::get($url, [
                'sha' => $this->branch,
                'per_page' => 10
            ]);

            if ($response->successful()) {
                $commits = $response->json();

                $this->info("📝 Derniers commits sur {$this->branch}:");

                foreach ($commits as $commit) {
                    $date = \Carbon\Carbon::parse($commit['commit']['author']['date'])->format('d/m/Y H:i');
                    $message = substr($commit['commit']['message'], 0, 60);
                    $author = $commit['commit']['author']['name'];

                    $this->line("• {$date} - {$author}: {$message}");
                }
            }
        } catch (\Exception $e) {
            $this->error("Erreur lors de la récupération des commits: " . $e->getMessage());
        }
    }
}
