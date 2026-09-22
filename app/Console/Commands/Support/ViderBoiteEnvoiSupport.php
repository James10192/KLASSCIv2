<?php

namespace App\Console\Commands\Support;

use App\Domain\Support\Exceptions\MasterSupportIndisponible;
use App\Domain\Support\Exceptions\MasterSupportRefus;
use App\Domain\Support\Models\SupportOutbox;
use App\Services\Care\ClientMasterSupport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Renvoie au Master les signalements qu'il n'a pas pu recevoir.
 *
 * Chaque minute, par le planificateur. Meme cle d'idempotence qu'au premier
 * essai : un envoi arrive mais dont la reponse s'etait perdue retrouve sa
 * demande au lieu d'en creer une seconde. Le premier echec d'une passe
 * l'arrete : le coupe-circuit du client est alors ouvert, insister ne ferait
 * que reporter toutes les lignes pour rien.
 */
class ViderBoiteEnvoiSupport extends Command
{
    protected $signature = 'support:vider-boite-envoi {--limite=50}';

    protected $description = 'KLASSCI Care : renvoyer au Master les signalements en attente';

    public function handle(ClientMasterSupport $master): int
    {
        // Coupe-circuit ouvert : aucun appel ne partirait, et compter un essai
        // pour rien avancerait l'abandon des lignes sans que le Master soit essaye.
        if (! $master->estConfigure() || $master->coupeCircuitOuvert()) {
            return self::SUCCESS;
        }

        $envoyes = 0;
        foreach (SupportOutbox::query()->aEnvoyer()->orderBy('id')->limit((int) $this->option('limite'))->get() as $ligne) {
            try {
                $reponse = $master->creerTicket($ligne->payload, $ligne->idempotency_key, $ligne->request_id);
                $ligne->forceFill(['sent_at' => now(), 'reference' => $reponse['reference'] ?? null, 'last_error' => null])->save();
                $envoyes++;
            } catch (MasterSupportIndisponible $e) {
                $ligne->reporter($e->getMessage());
                break;
            } catch (MasterSupportRefus $e) {
                // Refuse aujourd'hui, refuse demain : on arrete d'essayer et on le dit.
                $ligne->forceFill(['abandoned_at' => now(), 'last_error' => "{$e->statut} {$e->codeErreur} : {$e->getMessage()}"])->save();
                Log::error('KLASSCI Care : signalement abandonné, refusé par le Master', ['outbox_id' => $ligne->id, 'statut' => $e->statut]);
            }
        }

        if ($envoyes > 0) {
            $this->info("{$envoyes} signalement(s) transmis au Master.");
        }

        return self::SUCCESS;
    }
}
