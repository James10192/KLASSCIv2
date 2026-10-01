<?php

namespace App\Console\Commands\Support;

use App\Domain\Support\Actions\SuivreLesReponsesDuSupport;
use App\Domain\Support\Exceptions\MasterSupportIndisponible;
use App\Domain\Support\Exceptions\MasterSupportRefus;
use App\Domain\Support\Services\DisponibiliteSupport;
use App\Services\Care\ClientMasterSupport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Avertit les rapporteurs quand le support KLASSCI a repondu a leur demande,
 * ou l'a resolue / fermee. Toutes les 5 minutes, par le planificateur.
 *
 * Ne tourne que si le suivi des demandes est ouvert a l'instance : sans lui,
 * le lien de l'avertissement menerait a une page fermee.
 */
class SuivreDemandesSupport extends Command
{
    protected $signature = 'support:suivre-demandes';

    protected $description = 'KLASSCI Care : avertir les rapporteurs des réponses et clôtures du support';

    public function handle(DisponibiliteSupport $disponibilite, SuivreLesReponsesDuSupport $suivre, ClientMasterSupport $master): int
    {
        if ($master->coupeCircuitOuvert() || ! $disponibilite->suivi()) {
            return self::SUCCESS;
        }

        try {
            $bilan = $suivre->executer();
        } catch (MasterSupportIndisponible|MasterSupportRefus $e) {
            // Le curseur n'a pas bouge : le passage suivant reprend au meme endroit.
            Log::warning('KLASSCI Care : suivi des demandes interrompu', ['erreur' => $e->getMessage()]);

            return self::SUCCESS;
        }

        if ($bilan['initialisation']) {
            $this->info("Suivi initialisé : {$bilan['lues']} demande(s) relevée(s), aucun avertissement pour l'historique.");
        } elseif ($bilan['averties'] > 0) {
            $this->info("{$bilan['averties']} rapporteur(s) averti(s).");
        }

        return self::SUCCESS;
    }
}
