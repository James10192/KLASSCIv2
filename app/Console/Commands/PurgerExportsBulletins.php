<?php

namespace App\Console\Commands;

use App\Services\BulletinBulkPdfExporter;
use Illuminate\Console\Command;

/**
 * Balaie les restes des exports groupés.
 *
 * Un export abandonné — tranche en échec, réseau coupé, onglet fermé — laisse
 * derrière lui son dossier de travail et parfois son PDF assemblé. Rien ne les
 * reprenait : la durée de vie annoncée n'existait que dans un commentaire.
 */
class PurgerExportsBulletins extends Command
{
    protected $signature = 'bulletins:purger-exports
                            {--minutes= : Âge au-delà duquel un reste est supprimé}
                            {--dry-run : Lister sans supprimer}';

    protected $description = 'Supprime les dossiers et PDF laissés par les exports groupés abandonnés';

    public function handle(): int
    {
        $minutes = (int) ($this->option('minutes') ?: BulletinBulkPdfExporter::DUREE_VIE_MINUTES);
        $simulation = (bool) $this->option('dry-run');
        $limite = now()->subMinutes($minutes)->getTimestamp();

        $racine = storage_path('app/temp');
        if (! is_dir($racine)) {
            $this->info('Aucun dossier temporaire : rien à purger.');

            return self::SUCCESS;
        }

        $dossiers = $this->purgerDossiers($racine, $limite, $simulation);
        $fichiers = $this->purgerFichiers($racine, $limite, $simulation);

        $this->info(sprintf(
            '%s : %d dossier(s) et %d PDF de plus de %d min.',
            $simulation ? 'À supprimer' : 'Supprimés',
            $dossiers,
            $fichiers,
            $minutes
        ));

        return self::SUCCESS;
    }

    private function purgerDossiers(string $racine, int $limite, bool $simulation): int
    {
        $compte = 0;

        foreach (glob($racine.'/export_*', GLOB_ONLYDIR) ?: [] as $dossier) {
            if (filemtime($dossier) > $limite) {
                continue;
            }

            $this->ligne($dossier, $simulation);

            if (! $simulation) {
                foreach (glob($dossier.'/*') ?: [] as $fichier) {
                    @unlink($fichier);
                }
                @rmdir($dossier);
            }

            $compte++;
        }

        return $compte;
    }

    private function purgerFichiers(string $racine, int $limite, bool $simulation): int
    {
        $compte = 0;

        foreach (glob($racine.'/bulletins_export_*.pdf') ?: [] as $fichier) {
            if (filemtime($fichier) > $limite) {
                continue;
            }

            $this->ligne($fichier, $simulation);

            if (! $simulation) {
                @unlink($fichier);
            }

            $compte++;
        }

        return $compte;
    }

    private function ligne(string $chemin, bool $simulation): void
    {
        if ($this->output->isVerbose() || $simulation) {
            $this->line('  '.basename($chemin));
        }
    }
}
