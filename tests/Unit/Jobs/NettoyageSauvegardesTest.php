<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SauvegardeDataJob;
use Illuminate\Support\Facades\File;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Le ménage des sauvegardes s'arrêtait au premier dossier rencontré (unlink
 * sur un dossier lève une erreur) : plus aucune archive n'était supprimée, et
 * les disques des écoles se remplissaient.
 */
class NettoyageSauvegardesTest extends TestCase
{
    private string $dossier;

    private string $stockage;

    protected function setUp(): void
    {
        parent::setUp();
        // Jamais le vrai storage : backups/ y contient aussi des fichiers que
        // d'autres codes relisent (emails-factices-*.json…).
        $this->stockage = sys_get_temp_dir() . '/klassci-sauvegardes-' . uniqid();
        $this->app->useStoragePath($this->stockage);
        $this->dossier = storage_path('app/backups');
        File::ensureDirectoryExists($this->dossier);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->stockage);
        parent::tearDown();
    }

    private function poser(string $nom, int $joursAge, bool $dossier = false): void
    {
        $chemin = $this->dossier . '/' . $nom;
        $dossier ? File::ensureDirectoryExists($chemin . '/sous') : File::put($chemin, 'x');
        touch($chemin, time() - $joursAge * 86400);
    }

    private function nettoyer(string $type, int $retention): void
    {
        $job = new SauvegardeDataJob($type, ['retention_jours' => $retention]);
        $methode = new ReflectionMethod($job, 'nettoyerAnciennesSauvegardes');
        $methode->setAccessible(true);
        $methode->invoke($job);
    }

    private function restants(): array
    {
        return collect(scandir($this->dossier))->reject(fn ($e) => in_array($e, ['.', '..']))->sort()->values()->all();
    }

    public function test_un_seul_dossier_non_compresse_est_garde(): void
    {
        $this->poser('2026-11-01_03-00-00', 4, true);
        $this->poser('2026-11-02_03-00-00', 3, true);
        $this->poser('2026-11-03_03-00-00', 2, true);   // le plus récent : la copie de secours

        $this->nettoyer('database', 7);

        $this->assertSame(['2026-11-03_03-00-00'], $this->restants());
    }

    public function test_une_copie_de_fichier_ratee_est_un_echec(): void
    {
        $job = new SauvegardeDataJob('complet', ['retention_jours' => 30]);
        $copier = new ReflectionMethod($job, 'copierRepertoireOuFichier');
        $copier->setAccessible(true);
        File::put($this->stockage . '/source.txt', 'pièce');
        // La destination est un dossier : copy() échoue, comme sur un disque plein.
        File::ensureDirectoryExists($this->stockage . '/cible.txt');

        // RuntimeException du job, ou ErrorException de Laravel sur l'avertissement
        // de copy() : dans les deux cas un échec, rattrapé et notifié.
        $this->expectException(\Exception::class);
        $copier->invoke($job, $this->stockage . '/source.txt', $this->stockage . '/cible.txt');
    }

    public function test_un_dossier_ne_bloque_plus_le_menage(): void
    {
        $this->poser('2026-09-01_03-00-00', 40, true);          // trié avant les archives
        $this->poser('2026-09-02_03-00-00', 39, true);
        $this->poser('backup_database_2026-09-03_03-00-00.zip', 40);
        $this->poser('backup_database_2026-11-04_03-00-00.zip', 1);

        $this->nettoyer('database', 7);

        // Le plus récent des dossiers reste comme copie de secours ; l'autre et
        // l'archive périmée partent.
        $this->assertSame(['2026-09-02_03-00-00', 'backup_database_2026-11-04_03-00-00.zip'], $this->restants());
    }

    public function test_une_ancienne_archive_sans_type_n_est_retiree_que_par_la_tache_complete(): void
    {
        // Elle peut être une sauvegarde complète gardée 30 jours : la tâche de
        // la base seule (7 jours) ne doit pas l'emporter.
        $this->poser('backup_2026-10-20_03-00-00.zip', 15);

        $this->nettoyer('database', 7);
        $this->assertSame(['backup_2026-10-20_03-00-00.zip'], $this->restants());

        $this->nettoyer('complet', 30);
        $this->assertSame(['backup_2026-10-20_03-00-00.zip'], $this->restants());

        $this->poser('backup_2026-09-20_03-00-00.zip', 45);
        $this->nettoyer('complet', 30);
        $this->assertSame(['backup_2026-10-20_03-00-00.zip'], $this->restants());
    }

    public function test_une_archive_impossible_a_ecrire_est_un_echec_et_garde_les_donnees(): void
    {
        $job = new SauvegardeDataJob('complet', ['retention_jours' => 30]);
        File::ensureDirectoryExists($this->dossier . '/2026-11-05_03-00-00');
        File::put($this->dossier . '/2026-11-05_03-00-00/base.sql', 'dump');
        // Un dossier au nom de l'archive : elle ne peut pas être écrite.
        File::ensureDirectoryExists($this->dossier . '/backup_complet_2026-11-05_03-00-00.zip');

        $compresser = new ReflectionMethod($job, 'compresserSauvegarde');
        $compresser->setAccessible(true);
        $info = ['timestamp' => '2026-11-05_03-00-00'];

        try {
            $compresser->invokeArgs($job, ['backups/2026-11-05_03-00-00', &$info]);
            $this->fail('Une archive non écrite doit être une erreur, pas une réussite.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('backup_complet_2026-11-05_03-00-00.zip', $e->getMessage());
        }

        $this->assertFileExists($this->dossier . '/2026-11-05_03-00-00/base.sql');
    }

    public function test_chaque_type_garde_sa_propre_duree(): void
    {
        $this->poser('backup_complet_2026-10-20_03-00-00.zip', 15);
        $this->poser('backup_database_2026-10-20_03-00-00.zip', 15);

        $this->nettoyer('database', 7);

        $this->assertSame(['backup_complet_2026-10-20_03-00-00.zip'], $this->restants());
    }

    public function test_un_dossier_recent_et_un_fichier_etranger_sont_laisses(): void
    {
        $this->poser('2026-11-05_03-00-00', 0, true);           // sauvegarde en cours
        $this->poser('notes.txt', 90);

        $this->nettoyer('complet', 30);

        $this->assertSame(['2026-11-05_03-00-00', 'notes.txt'], $this->restants());
    }
}
