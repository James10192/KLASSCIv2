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
 *
 * La commande ne connaît ni les préfixes ni l'emplacement : c'est l'exportateur
 * qui possède ces conventions.
 */
class PurgerExportsBulletins extends Command
{
    protected $signature = 'bulletins:purger-exports
                            {--minutes= : Âge au-delà duquel un reste est supprimé}
                            {--dry-run : Lister sans supprimer}';

    protected $description = 'Supprime les dossiers et PDF laissés par les exports groupés abandonnés';

    public function handle(BulletinBulkPdfExporter $exporter): int
    {
        $minutes = (int) ($this->option('minutes') ?: BulletinBulkPdfExporter::DUREE_VIE_MINUTES);
        $simulation = (bool) $this->option('dry-run');

        $restes = $exporter->purger($minutes, $simulation);

        foreach ($restes as $reste) {
            if ($simulation || $this->output->isVerbose()) {
                $this->line('  '.basename($reste));
            }
        }

        $this->info(sprintf(
            '%s : %d reste(s) de plus de %d min.',
            $simulation ? 'À supprimer' : 'Supprimés',
            count($restes),
            $minutes
        ));

        return self::SUCCESS;
    }
}
