<?php

namespace App\Console\Commands;

use App\Domain\Bulletins\Taches\BulletinTache;
use App\Domain\Bulletins\Taches\ExecuteurTachesBulletins;
use Illuminate\Console\Command;

/**
 * Finit les travaux sur les bulletins que l'onglet a laissés en route.
 *
 * Pas de `queue:work` : les écoles tournent sur un hébergement mutualisé sans
 * processus permanent, et rien ne garantit qu'un worker y tourne
 * (`config/queue.php` vaut `database` par défaut). Un job dispatché y
 * attendrait pour toujours. La planification, elle, tourne chaque minute —
 * c'est déjà elle qui envoie les convocations et les activations MailPulse.
 *
 * Le PDF assemblé est gardé {@see BulletinTache::CONSERVATION_HEURES} heures ;
 * cette commande balaie aussi ceux qui ont dépassé ce délai.
 */
class TraiterTachesBulletins extends Command
{
    public const NOM = 'bulletins:traiter-taches';

    protected $signature = self::NOM.' {--budget=50 : secondes au plus}';

    protected $description = 'Fait avancer les générations et PDF groupés de bulletins lancés en arrière-plan';

    public function handle(ExecuteurTachesBulletins $executeur): int
    {
        $rapport = $executeur->traiterLaFile(max(5.0, (float) $this->option('budget')));
        $purges = $this->purgerLesDocumentsExpires();

        $this->info(sprintf(
            'Tranches traitées : %d · tâches tenues ailleurs : %d · encore actives : %d · documents expirés supprimés : %d',
            $rapport['tranches'],
            $rapport['occupees'],
            $rapport['actives'],
            $purges
        ));

        return self::SUCCESS;
    }

    private function purgerLesDocumentsExpires(): int
    {
        $expirees = BulletinTache::query()
            ->whereNotNull('fichier')
            ->where('terminee_at', '<', now()->subHours(BulletinTache::CONSERVATION_HEURES))
            ->limit(50)
            ->get();

        foreach ($expirees as $tache) {
            if (is_file($tache->fichier)) {
                @unlink($tache->fichier);
            }
            $tache->forceFill(['fichier' => null])->save();
        }

        return $expirees->count();
    }
}
