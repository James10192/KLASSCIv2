<?php

namespace App\Console\Commands\Support;

use App\Domain\Support\Exceptions\IdentifiantInstanceRefuse;
use App\Domain\Support\Exceptions\MasterSupportIndisponible;
use App\Domain\Support\Exceptions\MasterSupportRefus;
use App\Domain\Support\Exceptions\PorteeAbsente;
use App\Domain\Support\Models\SupportOutbox;
use App\Services\Care\ClientMasterSupport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Renvoie au Master ce qu'il n'a pas pu recevoir : les signalements, et les
 * avis 👍 / 👎 sur les reponses de l'assistant (qui ne partent QUE par ici).
 *
 * Chaque minute, par le planificateur. Meme cle d'idempotence qu'au premier
 * essai : un envoi arrive mais dont la reponse s'etait perdue retrouve sa
 * demande au lieu d'en creer une seconde. Le premier echec d'une passe
 * l'arrete : le coupe-circuit du client est alors ouvert, insister ne ferait
 * que reporter toutes les lignes pour rien.
 *
 * Un avis ne doit jamais retenir un signalement : si sa route ou sa portee
 * n'est pas (encore) ouverte au Master, la ligne est differee et la passe
 * continue.
 */
class ViderBoiteEnvoiSupport extends Command
{
    protected $signature = 'support:vider-boite-envoi {--limite=50}';

    protected $description = 'KLASSCI Care : renvoyer au Master les signalements et avis en attente';

    public function handle(ClientMasterSupport $master): int
    {
        // Coupe-circuit ouvert : aucun appel ne partirait, et compter un essai
        // pour rien avancerait l'abandon des lignes sans que le Master soit essaye.
        if (! $master->estConfigure() || $master->coupeCircuitOuvert()) {
            return self::SUCCESS;
        }

        $envoyes = 0;
        foreach (SupportOutbox::query()->aEnvoyer()->orderBy('id')->limit((int) $this->option('limite'))->get() as $ligne) {
            $avis = $ligne->kind === SupportOutbox::AVIS_ASSISTANT;

            try {
                $reponse = $avis
                    ? $master->transmettreRetourAssistant($ligne->payload, $ligne->idempotency_key)
                    : $master->creerTicket($ligne->payload, $ligne->idempotency_key, $ligne->request_id);
                $ligne->forceFill(['sent_at' => now(), 'reference' => $avis ? null : ($reponse['reference'] ?? null), 'last_error' => null])->save();
                $envoyes++;
            } catch (PorteeAbsente $e) {
                if (! $avis) {
                    break;
                }
                $ligne->differer('Portée absente : '.$e->getMessage());
            } catch (IdentifiantInstanceRefuse $e) {
                // La faute est celle de l'instance : la ligne garde tous ses essais.
                break;
            } catch (MasterSupportIndisponible $e) {
                $ligne->reporter($e->getMessage());
                break;
            } catch (MasterSupportRefus $e) {
                // Route des avis pas encore deployee au Master : on attend qu'elle le soit.
                if ($avis && in_array($e->statut, [404, 405], true)) {
                    $ligne->differer("Route des avis indisponible au Master ({$e->statut}).");

                    continue;
                }

                // Refuse aujourd'hui, refuse demain : on arrete d'essayer et on le dit.
                $ligne->forceFill(['abandoned_at' => now(), 'last_error' => "{$e->statut} {$e->codeErreur} : {$e->getMessage()}"])->save();
                Log::error('KLASSCI Care : envoi abandonné, refusé par le Master', ['outbox_id' => $ligne->id, 'nature' => $ligne->kind, 'statut' => $e->statut]);
            }
        }

        if ($envoyes > 0) {
            $this->info("{$envoyes} envoi(s) transmis au Master.");
        }

        return self::SUCCESS;
    }
}
