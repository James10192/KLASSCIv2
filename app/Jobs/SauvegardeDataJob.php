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
        // Le ménage passe AVANT : sur un disque plein, la sauvegarde échoue, et
        // un ménage placé seulement après une réussite ne passait plus jamais —
        // le disque restait plein, la sauvegarde suivante échouait aussi.
        $this->nettoyerAnciennesSauvegardes();

        $backupPath = null;
        $donneesCompletes = false;

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

            $donneesCompletes = true;

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
        } finally {
            // Une sauvegarde interrompue AVANT d'avoir tout copié laissait un
            // dossier incomplet : on le retire. Un dossier complet reste — sans
            // compression, ou quand l'archive n'a pas pu s'écrire, c'est la
            // seule copie ; le ménage le retire avec la durée de conservation.
            if (!$donneesCompletes && $backupPath && is_dir(storage_path("app/{$backupPath}"))) {
                $this->supprimerDossierRecursif(storage_path("app/{$backupPath}"));
            }
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
            // Les pieces du dossier etudiant — extraits de naissance, pieces
            // d'identite, diplomes.
            //
            // Elles vivaient sous `storage/app/public`, donc sauvegardees par la
            // premiere ligne. Les avoir mises a l'abri du serveur web les a
            // sorties de ce repertoire, et donc de la sauvegarde : le correctif
            // de securite a ouvert un trou de conservation, sans que rien ne le
            // dise. Tant que l'original papier existe, la perte reste
            // rattrapable ; elle cesse de l'etre le jour ou une ecole consulte a
            // l'ecran plutot que d'aller au classeur, ce qui est precisement le
            // but de ces fichiers.
            //
            // Voir App\Services\Documents\StockageDocumentEtudiant::DOSSIER.
            'storage/app/dossiers-etudiants' => 'pieces_dossier_etudiants',
            // Pas storage/logs : des journaux, pas des données d'école, et la
            // plus grosse part de chaque archive, recopiée trente fois.
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
        // Le type dans le nom : chaque sauvegarde ne retire que les archives
        // de son type, chacune avec sa propre durée de conservation.
        $archiveName = "backup_{$this->typeSauvegarde}_{$sauvegardeInfo['timestamp']}.zip";
        $archivePath = storage_path("app/backups/{$archiveName}");
        $sourcePath = storage_path("app/{$backupPath}");

        // Une archive qui n'a pas pu être écrite est une sauvegarde ratée, et
        // elle se dit ratée : ZipArchive n'écrit qu'à close(), si bien qu'un
        // disque presque plein passait inaperçu (open() réussit, close() non),
        // et la sauvegarde se déclarait réussie sans archive.
        $zip = new \ZipArchive();
        if ($zip->open($archivePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Impossible de créer l'archive ZIP : {$archiveName}");
        }

        $this->ajouterDossierAuZip($zip, $sourcePath, '');

        if (!$zip->close() || !is_file($archivePath) || filesize($archivePath) === 0) {
            @unlink($archivePath);
            throw new \RuntimeException("Archive ZIP non écrite (disque plein ?) : {$archiveName}");
        }

        $sauvegardeInfo['archive'] = $archiveName;
        $sauvegardeInfo['taille_archive'] = filesize($archivePath);

        // Le dossier non compressé n'est retiré qu'une fois l'archive vérifiée.
        $this->supprimerDossierRecursif($sourcePath);

        Log::info("Archive créée: {$archiveName} (" . number_format(filesize($archivePath) / 1024 / 1024, 2) . " MB)");
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
    private function nettoyerAnciennesSauvegardes(): void
    {
        $backupsPath = storage_path('app/backups');
        if (!is_dir($backupsPath)) {
            return;
        }

        $limite = Carbon::now()->subDays($this->options['retention_jours'])->getTimestamp();
        // Un dossier resté là est une sauvegarde complète non compressée (archive
        // impossible) ou le reste d'un processus tué. On n'en garde qu'UN, le
        // plus récent : chacun contient une copie intégrale des pièces, et en
        // garder un par jour sur un disque plein recréait la spirale.
        $limiteDossier = Carbon::now()->subDay()->getTimestamp();
        $dossiers = array_values(array_filter(
            scandir($backupsPath),
            fn ($e) => $e !== '.' && $e !== '..' && is_dir($backupsPath . DIRECTORY_SEPARATOR . $e)
        ));
        usort($dossiers, fn ($a, $b) => filemtime($backupsPath . DIRECTORY_SEPARATOR . $b) <=> filemtime($backupsPath . DIRECTORY_SEPARATOR . $a));
        $dossierGarde = $dossiers[0] ?? null;

        // Cette copie de secours ne sert que tant qu'aucune archive réussie ne
        // l'a remplacée, et jamais au-delà de la durée de conservation : sans
        // ces deux bornes, le dernier dossier resté sur le disque n'était plus
        // jamais délogé.
        if ($dossierGarde !== null) {
            $dateGarde = filemtime($backupsPath . DIRECTORY_SEPARATOR . $dossierGarde);
            // Seule une archive complète la remplace : une archive de la base
            // seule ne contient pas les pièces que porte cette copie.
            $archivePlusRecente = collect(scandir($backupsPath))
                ->contains(fn ($e) => $this->estArchiveComplete($e) && filemtime($backupsPath . DIRECTORY_SEPARATOR . $e) > $dateGarde);

            if ($archivePlusRecente || $dateGarde < $limite) {
                $dossierGarde = null;
            }
        }

        foreach (scandir($backupsPath) as $entree) {
            if ($entree === '.' || $entree === '..') {
                continue;
            }

            $chemin = $backupsPath . DIRECTORY_SEPARATOR . $entree;

            // Un échec sur une entrée n'arrête plus le ménage : auparavant, le
            // premier dossier rencontré faisait lever unlink(), et plus aucune
            // archive n'était jamais supprimée.
            try {
                if (is_dir($chemin)) {
                    if ($entree !== $dossierGarde && filemtime($chemin) < $limiteDossier) {
                        $this->supprimerDossierRecursif($chemin);
                        Log::info("Ancienne sauvegarde non compressée retirée: {$entree}");
                    }
                    continue;
                }

                if ($this->archiveConcernee($entree) && filemtime($chemin) < $limite) {
                    unlink($chemin);
                    Log::info("Ancienne sauvegarde supprimée: {$entree}");
                }
            } catch (\Throwable $e) {
                Log::error("Sauvegarde non supprimée: {$entree} — " . $e->getMessage());
            }
        }
    }

    /**
     * Archive complète : nommée « complet », ou ancienne archive sans type
     * (les complètes l'étaient avant que le type n'entre dans le nom).
     */
    private function estArchiveComplete(string $nom): bool
    {
        return (bool) preg_match('/^backup_(?:complet_)?\d{4}-\d{2}-\d{2}_[\d-]+\.zip$/', $nom);
    }

    /**
     * Archive de ce type, ou ancienne archive sans type (`backup_<date>.zip`,
     * avant que le type n'entre dans le nom). Tout autre fichier est laissé —
     * l'ancien ménage effaçait aussi les emails-factices-*.json que relit
     * AnciennesAdresses.
     */
    private function archiveConcernee(string $nom): bool
    {
        if (!preg_match('/^backup_(?:([a-z]+)_)?\d{4}-\d{2}-\d{2}_[\d-]+\.zip$/', $nom, $m)) {
            return false;
        }

        // Une ancienne archive sans type peut être une sauvegarde complète :
        // seule la tâche « complet » (30 jours) la retire, jamais celle de la
        // base seule (7 jours), qui passe la première après le déploiement.
        if (($m[1] ?? '') === '') {
            return $this->typeSauvegarde === 'complet';
        }

        return $m[1] === $this->typeSauvegarde;
    }

    /**
     * Utilitaires
     */
    private function copierRepertoireOuFichier($source, $destination)
    {
        // Chaque échec lève : sur un disque presque plein, copy() rendait false
        // sans bruit, et une sauvegarde tronquée se déclarait réussie puis
        // prenait sa place dans la rotation.
        if (is_file($source)) {
            $this->creerDossier(dirname($destination));
            if (!copy($source, $destination)) {
                throw new \RuntimeException("Copie impossible (disque plein ?) : {$source}");
            }
        } elseif (is_dir($source)) {
            $this->creerDossier($destination);

            foreach (scandir($source) as $file) {
                if ($file === '.' || $file === '..') continue;
                $this->copierRepertoireOuFichier($source . DIRECTORY_SEPARATOR . $file, $destination . DIRECTORY_SEPARATOR . $file);
            }
        }
    }

    private function creerDossier(string $dossier): void
    {
        if (!is_dir($dossier) && !mkdir($dossier, 0755, true) && !is_dir($dossier)) {
            throw new \RuntimeException("Dossier impossible à créer : {$dossier}");
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
