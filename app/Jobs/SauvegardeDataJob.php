<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SauvegardeDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $typeSauvegarde;
    protected $options;

    /**
     * Create a new job instance.
     */
    public function __construct($typeSauvegarde = 'complet', array $options = [])
    {
        $this->typeSauvegarde = $typeSauvegarde;
        $this->options = array_merge([
            'inclure_fichiers' => true,
            'compression' => true,
            'destination' => 'local',
            'retention_jours' => 30,
            'cloud_backup' => false
        ], $options);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Début sauvegarde automatique - Type: {$this->typeSauvegarde}");

            $timestamp = now()->format('Y-m-d_H-i-s');
            $backupPath = "backups/{$timestamp}";

            // Créer le répertoire de sauvegarde
            Storage::disk('local')->makeDirectory($backupPath);

            $sauvegardeInfo = [
                'timestamp' => $timestamp,
                'type' => $this->typeSauvegarde,
                'fichiers_inclus' => [],
                'taille_totale' => 0,
                'duree_execution' => 0
            ];

            $startTime = microtime(true);

            // Sauvegarde de la base de données
            $this->sauvegarderBaseDonnees($backupPath, $sauvegardeInfo);

            // Sauvegarde des fichiers si demandé
            if ($this->options['inclure_fichiers']) {
                $this->sauvegarderFichiers($backupPath, $sauvegardeInfo);
            }

            // Compression si demandée
            if ($this->options['compression']) {
                $this->compresserSauvegarde($backupPath, $sauvegardeInfo);
            }

            // Sauvegarde cloud si configurée
            if ($this->options['cloud_backup']) {
                $this->sauvegarderCloud($backupPath, $sauvegardeInfo);
            }

            // Nettoyage des anciennes sauvegardes
            $this->nettoyerAnciennesSauvegardes();

            $sauvegardeInfo['duree_execution'] = round(microtime(true) - $startTime, 2);

            Log::info("Sauvegarde terminée avec succès", $sauvegardeInfo);

            // Envoyer notification de succès
            $this->envoyerNotificationSucces($sauvegardeInfo);

        } catch (\Exception $e) {
            Log::error("Erreur lors de la sauvegarde automatique: " . $e->getMessage(), [
                'type' => $this->typeSauvegarde,
                'options' => $this->options,
                'trace' => $e->getTraceAsString()
            ]);

            $this->envoyerNotificationEchec($e);
            $this->fail($e);
        }
    }

    /**
     * Sauvegarde la base de données
     */
    private function sauvegarderBaseDonnees($backupPath, &$sauvegardeInfo)
    {
        $dbName = config('database.connections.' . config('database.default') . '.database');
        $dbHost = config('database.connections.' . config('database.default') . '.host');
        $dbUser = config('database.connections.' . config('database.default') . '.username');
        $dbPassword = config('database.connections.' . config('database.default') . '.password');

        $sqlFileName = "database_backup_{$sauvegardeInfo['timestamp']}.sql";
        $sqlFilePath = storage_path("app/{$backupPath}/{$sqlFileName}");

        // Créer le répertoire si nécessaire
        $directory = dirname($sqlFilePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Commande mysqldump adaptée selon l'OS
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows - utiliser xampp mysqldump
            $mysqldumpPath = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
            if (!file_exists($mysqldumpPath)) {
                $mysqldumpPath = 'mysqldump'; // Fallback si pas dans xampp
            }
        } else {
            // Unix/Linux
            $mysqldumpPath = 'mysqldump';
        }

        $command = sprintf(
            '%s --host=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s',
            $mysqldumpPath,
            $dbHost,
            $dbUser,
            $dbPassword,
            $dbName,
            $sqlFilePath
        );

        // Exécuter la commande
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception("Erreur lors de la sauvegarde de la base de données. Code retour: {$returnCode}");
        }

        if (file_exists($sqlFilePath)) {
            $sauvegardeInfo['fichiers_inclus'][] = $sqlFileName;
            $sauvegardeInfo['taille_totale'] += filesize($sqlFilePath);
            Log::info("Sauvegarde base de données créée: {$sqlFileName}");
        } else {
            throw new \Exception("Fichier de sauvegarde de la base de données non créé");
        }
    }

    /**
     * Sauvegarde les fichiers importants
     */
    private function sauvegarderFichiers($backupPath, &$sauvegardeInfo)
    {
        $fichiersAsauvegarder = [
            'storage/app/public' => 'fichiers_uploads',
            'storage/logs' => 'logs',
            '.env' => 'configuration/.env'
        ];

        foreach ($fichiersAsauvegarder as $source => $destination) {
            $sourcePath = base_path($source);
            $destPath = storage_path("app/{$backupPath}/{$destination}");

            if (file_exists($sourcePath)) {
                $this->copierRepertoireOuFichier($sourcePath, $destPath);
                $sauvegardeInfo['fichiers_inclus'][] = $destination;
                Log::info("Fichiers sauvegardés: {$source} -> {$destination}");
            }
        }
    }

    /**
     * Compresse la sauvegarde
     */
    private function compresserSauvegarde($backupPath, &$sauvegardeInfo)
    {
        $archiveName = "backup_{$sauvegardeInfo['timestamp']}.zip";
        $archivePath = storage_path("app/backups/{$archiveName}");
        $sourcePath = storage_path("app/{$backupPath}");

        // Créer l'archive ZIP
        $zip = new \ZipArchive();
        if ($zip->open($archivePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            $this->ajouterDossierAuZip($zip, $sourcePath, '');
            $zip->close();

            if (file_exists($archivePath)) {
                $sauvegardeInfo['archive'] = $archiveName;
                $sauvegardeInfo['taille_archive'] = filesize($archivePath);

                // Supprimer le dossier temporaire non compressé
                $this->supprimerDossierRecursif($sourcePath);

                Log::info("Archive créée: {$archiveName} (" . number_format(filesize($archivePath) / 1024 / 1024, 2) . " MB)");
            }
        } else {
            Log::warning("Impossible de créer l'archive ZIP: {$archivePath}");
        }
    }

    /**
     * Sauvegarde sur le cloud (si configuré)
     */
    private function sauvegarderCloud($backupPath, &$sauvegardeInfo)
    {
        // Implémentation future pour AWS S3, Google Drive, etc.
        Log::info("Sauvegarde cloud - Fonctionnalité à implémenter");
    }

    /**
     * Nettoie les anciennes sauvegardes
     */
    private function nettoyerAnciennesSauvegardes()
    {
        $retentionJours = $this->options['retention_jours'];
        $backupsPath = storage_path('app/backups');

        if (is_dir($backupsPath)) {
            $files = scandir($backupsPath);
            $cutoffDate = Carbon::now()->subDays($retentionJours);

            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;

                $filePath = $backupsPath . DIRECTORY_SEPARATOR . $file;
                $fileTime = Carbon::createFromTimestamp(filemtime($filePath));

                if ($fileTime->lt($cutoffDate)) {
                    unlink($filePath);
                    Log::info("Ancienne sauvegarde supprimée: {$file}");
                }
            }
        }
    }

    /**
     * Utilitaires
     */
    private function copierRepertoireOuFichier($source, $destination)
    {
        if (is_file($source)) {
            $destinationDir = dirname($destination);
            if (!is_dir($destinationDir)) {
                mkdir($destinationDir, 0755, true);
            }
            copy($source, $destination);
        } elseif (is_dir($source)) {
            if (!is_dir($destination)) {
                mkdir($destination, 0755, true);
            }

            $files = scandir($source);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                $this->copierRepertoireOuFichier($source . DIRECTORY_SEPARATOR . $file, $destination . DIRECTORY_SEPARATOR . $file);
            }
        }
    }

    private function ajouterDossierAuZip($zip, $dossier, $prefixeLocal)
    {
        $files = scandir($dossier);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $filePath = $dossier . DIRECTORY_SEPARATOR . $file;
            $localPath = $prefixeLocal . $file;

            if (is_file($filePath)) {
                $zip->addFile($filePath, $localPath);
            } elseif (is_dir($filePath)) {
                $zip->addEmptyDir($localPath);
                $this->ajouterDossierAuZip($zip, $filePath, $localPath . '/');
            }
        }
    }

    private function supprimerDossierRecursif($dossier)
    {
        if (is_dir($dossier)) {
            $files = scandir($dossier);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                $filePath = $dossier . DIRECTORY_SEPARATOR . $file;

                if (is_file($filePath)) {
                    unlink($filePath);
                } elseif (is_dir($filePath)) {
                    $this->supprimerDossierRecursif($filePath);
                }
            }
            rmdir($dossier);
        }
    }

    /**
     * Notifications
     */
    private function envoyerNotificationSucces($sauvegardeInfo)
    {
        // Implémentation future pour notifications par email/slack
        Log::info("Notification de succès envoyée", $sauvegardeInfo);
    }

    private function envoyerNotificationEchec(\Exception $e)
    {
        // Implémentation future pour notifications d'échec
        Log::error("Notification d'échec envoyée: " . $e->getMessage());
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job SauvegardeDataJob échoué", [
            'type' => $this->typeSauvegarde,
            'options' => $this->options,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 2;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [60, 300]; // 1 minute, puis 5 minutes

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addHours(2); // 2 heures max
    }
}
