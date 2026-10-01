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

        $limite = (int) $this->option('limite');
        $envoyes = 0;

        // Les signalements d'abord, puis peu d'avis par passage : les deux
        // partagent la limite d'ecriture du Master (30 par minute), et un
        // signalement compte plus qu'un 👍.
        $signalements = SupportOutbox::query()->aEnvoyer()->signalements()->orderBy('id')->limit($limite)->get();
        $continuer = $this->envoyer($master, $signalements, $envoyes);

        if ($continuer) {
            $avis = SupportOutbox::query()->aEnvoyer()->where('kind', SupportOutbox::AVIS_ASSISTANT)
                ->orderBy('id')->limit(min($limite, (int) config('support.boite_envoi.avis_par_passage', 10)))->get();
            $this->envoyer($master, $avis, $envoyes);
        }

        if ($envoyes > 0) {
            $this->info("{$envoyes} envoi(s) transmis au Master.");
        }

        return self::SUCCESS;
    }

    /** Rend faux quand la passe doit s'arreter (Master injoignable, instance refusee). */
    private function envoyer(ClientMasterSupport $master, $lignes, int &$envoyes): bool
    {
        foreach ($lignes as $ligne) {
            $avis = $ligne->kind === SupportOutbox::AVIS_ASSISTANT;
            // Reserve la ligne avant l'appel : un avis change pendant l'envoi
            // ne peut plus remplacer une charge deja en route (TransmettreRetour).
            if (! $ligne->reserver()) {
                continue;
            }

            try {
                $reponse = $avis
                    ? $master->transmettreRetourAssistant($ligne->payload, $ligne->idempotency_key)
                    : $master->creerTicket($ligne->payload, $ligne->idempotency_key, $ligne->request_id);
                $ligne->forceFill(['sent_at' => now(), 'reference' => $avis ? null : ($reponse['reference'] ?? null), 'last_error' => null])->save();
                $envoyes++;
            } catch (PorteeAbsente $e) {
                if (! $avis) {
                    return false;
                }
                $ligne->differer('Portée absente : '.$e->getMessage());
            } catch (IdentifiantInstanceRefuse $e) {
                // La faute est celle de l'instance : la ligne garde tous ses essais.
                return false;
            } catch (MasterSupportIndisponible $e) {
                $ligne->reporter($e->getMessage());

                return false;
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

        return true;
    }
}
