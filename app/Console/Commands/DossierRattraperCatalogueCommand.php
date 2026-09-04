<?php

namespace App\Console\Commands;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPInscription;
use App\Services\Dossier\MaterialisationPiecesService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rattrape le catalogue des pièces sur les inscriptions déjà créées.
 *
 * C'est la réponse opérationnelle à la question « une pièce ajoutée au catalogue
 * après la rentrée apparaît-elle sur les dossiers existants ? ». Le service
 * répond selon le réglage de l'instance ; cette commande permet de l'appliquer
 * en masse au lieu d'attendre que chaque dossier soit rouvert un par un.
 *
 * Usage : php artisan dossier:rattraper-catalogue [--annee=ID] [--dry-run]
 */
class DossierRattraperCatalogueCommand extends Command
{
    protected $signature = 'dossier:rattraper-catalogue
                            {--annee= : ID de l\'année universitaire (par défaut : courante)}
                            {--dry-run : Ne rien écrire, compter seulement}';

    protected $description = 'Ajoute aux dossiers existants les pièces ajoutées au catalogue après coup';

    public function handle(MaterialisationPiecesService $service): int
    {
        $mode = $service->modeRattrapage();
        $this->line("Mode de rattrapage configuré : <info>{$mode}</info>");

        if ($mode === MaterialisationPiecesService::RATTRAPAGE_AUCUN) {
            $this->warn(
                'Le rattrapage est désactivé sur cette instance ('
                .MaterialisationPiecesService::REGLAGE_RATTRAPAGE.' = aucun). Rien à faire.'
            );

            return self::SUCCESS;
        }

        $anneeId = $this->option('annee') ?: ESBTPAnneeUniversitaire::getCurrent()?->id;

        if ($anneeId === null) {
            $this->error('Aucune année universitaire courante. Précisez --annee=ID.');

            return self::FAILURE;
        }

        $query = ESBTPInscription::query()->where('annee_universitaire_id', $anneeId);
        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('Aucune inscription sur cette année.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $barre = $this->output->createProgressBar($total);
        $lignesCreees = 0;
        $dossiersTouches = 0;

        // chunkById : deux instances dépassent 2000 inscriptions, on ne charge
        // pas la cohorte entière en mémoire.
        $query->chunkById(200, function ($inscriptions) use ($service, $dryRun, $barre, &$lignesCreees, &$dossiersTouches) {
            foreach ($inscriptions as $inscription) {
                $creees = $dryRun
                    ? $this->compterSansEcrire($service, $inscription)
                    : $service->materialiser($inscription);

                if ($creees > 0) {
                    $lignesCreees += $creees;
                    $dossiersTouches++;
                }

                $barre->advance();
            }
        });

        $barre->finish();
        $this->newLine(2);

        $verbe = $dryRun ? 'seraient créées' : 'créées';
        $this->info("{$lignesCreees} ligne(s) {$verbe} sur {$dossiersTouches} dossier(s) (sur {$total} inscriptions).");

        return self::SUCCESS;
    }

    /**
     * Simule la matérialisation dans une transaction annulée : on obtient le
     * compte exact sans dupliquer la logique de résolution du catalogue, qui
     * serait la première chose à diverger de la vraie.
     */
    private function compterSansEcrire(MaterialisationPiecesService $service, ESBTPInscription $inscription): int
    {
        DB::beginTransaction();

        try {
            return $service->materialiser($inscription);
        } finally {
            DB::rollBack();
        }
    }
}
