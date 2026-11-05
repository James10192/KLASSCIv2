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

    protected function setUp(): void
    {
        parent::setUp();
        $this->dossier = storage_path('app/backups');
        File::deleteDirectory($this->dossier);
        File::ensureDirectoryExists($this->dossier);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dossier);
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

    public function test_un_dossier_interrompu_n_arrete_plus_le_menage(): void
    {
        $this->poser('2026-09-01_03-00-00', 40, true);          // reste d'une sauvegarde interrompue
        $this->poser('backup_2026-09-02_03-00-00.zip', 40);      // ancienne archive sans type
        $this->poser('backup_database_2026-09-03_03-00-00.zip', 40);
        $this->poser('backup_database_2026-11-04_03-00-00.zip', 1);

        $this->nettoyer('database', 7);

        $this->assertSame(['backup_database_2026-11-04_03-00-00.zip'], $this->restants());
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
