<?php

namespace App\Services\Care;

use App\Domain\Support\Exceptions\IdentifiantInstanceRefuse;
use App\Domain\Support\Exceptions\MasterSupportIndisponible;
use App\Domain\Support\Exceptions\MasterSupportRefus;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client serveur vers l'API KLASSCI Care du Master (/api/v1/support).
 *
 * Trois regles, apprises de PaywallMiddleware qui les enfreint :
 *
 *  - des delais COURTS (2 s pour se connecter, 5 s en tout). Un signalement
 *    ne doit jamais geler une page ;
 *  - un echec est MIS EN CACHE (coupe-circuit, 60 s). Le paywall ne met en
 *    cache que les succes : quand le Master tombe, chaque requete l'attend
 *    dix secondes ;
 *  - le jeton part en en-tete, jamais dans l'URL, et ce n'est pas
 *    MASTER_API_TOKEN mais l'identifiant dedie MASTER_SUPPORT_TOKEN.
 *
 * Un refus (4xx) et une indisponibilite (reseau, 5xx) sont deux exceptions
 * distinctes : la premiere ne se reessaie pas, la seconde part dans la boite
 * d'envoi. Un 401 ou un 403 est une indisponibilite, pas un refus : c'est
 * l'identifiant de l'instance qui est en cause (revoque, mal pose), pas la
 * demande, et elle doit attendre qu'on le corrige plutot qu'etre perdue.
 * Seule exception : un 403 `insufficient_scope`, ou l'identifiant est valide
 * mais ne couvre pas cette action — un refus, sans coupe-circuit.
 */
class ClientMasterSupport
{
    private const CLE_COUPE_CIRCUIT = 'care:master:indisponible';

    private const CLE_BOOTSTRAP = 'care:master:bootstrap';

    private const CLE_BOOTSTRAP_CONNU = 'care:master:bootstrap:dernier';

    public function estConfigure(): bool
    {
        return filled(config('services.master.api_url')) && filled(config('services.master.support_token'));
    }

    /**
     * Les fonctionnalites KLASSCI Care ouvertes a cette instance.
     *
     * @return array<string, bool>
     */
    public function fonctionnalites(): array
    {
        return (array) ($this->bootstrap()['fonctionnalites'] ?? []);
    }

    /**
     * Les limites de saisie, telles que le Master les valide. Une seule source :
     * si le Master releve le minimum, le formulaire le suit sans deploiement.
     *
     * @return array{description_min: int, description_max: int, reponse_min: int}
     */
    public function limites(): array
    {
        $l = (array) ($this->bootstrap()['limites'] ?? []) + (array) config('support.limites_par_defaut');

        return [
            'description_min' => (int) $l['description_min'],
            'description_max' => (int) $l['description_max'],
            'reponse_min' => (int) $l['reponse_min'],
        ];
    }

    /**
     * Ce que l'identifiant de l'instance a le droit de faire. Un identifiant
     * emis avant la tranche 2 n'a pas `support:update` : l'ecran le sait et ne
     * propose pas de repondre, plutot que d'essuyer un refus a l'envoi.
     *
     * @return list<string>
     */
    public function portees(): array
    {
        return array_values((array) ($this->bootstrap()['portees'] ?? []));
    }

    public function coupeCircuitOuvert(): bool
    {
        return Cache::has(self::CLE_COUPE_CIRCUIT);
    }

    /**
     * Lu au rendu des pages : ne leve jamais. Master injoignable → derniere
     * reponse connue, sinon tout ferme.
     *
     * Un seul processus rafraichit a l'expiration : les autres, sans attendre
     * le verrou, servent la derniere reponse connue. Sans ce verrou, chaque
     * requete arrivant pendant l'appel au Master l'appellerait aussi.
     */
    private function bootstrap(): array
    {
        if (! $this->estConfigure()) {
            return [];
        }

        $frais = Cache::get(self::CLE_BOOTSTRAP);
        if ($frais !== null) {
            return (array) $frais;
        }

        $connu = (array) Cache::get(self::CLE_BOOTSTRAP_CONNU, []);
        $verrou = Cache::lock(self::CLE_BOOTSTRAP.':verrou', 10);
        if (! $verrou->get()) {
            return $connu;
        }

        try {
            $reponse = $this->requete('GET', 'bootstrap');
            $bootstrap = [
                'fonctionnalites' => (array) ($reponse['fonctionnalites'] ?? []),
                'limites' => (array) ($reponse['limites'] ?? []),
                'portees' => (array) ($reponse['portees'] ?? []),
            ];
            Cache::forever(self::CLE_BOOTSTRAP_CONNU, $bootstrap);
        } catch (MasterSupportIndisponible|MasterSupportRefus) {
            $bootstrap = $connu;
        } finally {
            $verrou->release();
        }

        Cache::put(self::CLE_BOOTSTRAP, $bootstrap, (int) config('support.delais.fonctionnalites_secondes', 300));

        return $bootstrap;
    }

    public function fonctionnaliteActive(string $cle): bool
    {
        return (bool) ($this->fonctionnalites()[$cle] ?? false);
    }

    public function creerTicket(array $charge, string $cleIdempotence, ?string $requestId = null): array
    {
        return $this->requete('POST', 'tickets', $charge, ['Idempotency-Key' => $cleIdempotence], $requestId);
    }

    public function lister(int $rapporteurId, string $portee = 'mine', int $page = 1): array
    {
        return $this->requete('GET', 'tickets', ['reporter' => $rapporteurId, 'scope' => $portee, 'page' => $page]);
    }

    public function afficher(string $reference, int $rapporteurId, string $portee = 'mine'): ?array
    {
        try {
            return $this->requete('GET', 'tickets/'.rawurlencode($reference), ['reporter' => $rapporteurId, 'scope' => $portee]);
        } catch (MasterSupportRefus $e) {
            if ($e->statut === 404) {
                return null;
            }
            throw $e;
        }
    }

    /**
     * Une reponse de l'ecole dans la conversation. La cle d'idempotence vient
     * du navigateur : un double clic ou un renvoi apres coupure ne publie
     * pas deux fois le meme message.
     */
    public function repondre(string $reference, int $rapporteurId, string $portee, string $corps, ?string $nom, string $cle): array
    {
        return $this->requete(
            'POST',
            'tickets/'.rawurlencode($reference).'/messages?'.http_build_query(['reporter' => $rapporteurId, 'scope' => $portee]),
            ['body' => $corps, 'author_name' => $nom],
            ['Idempotency-Key' => $cle],
        );
    }

    private function requete(string $methode, string $chemin, array $donnees = [], array $entetes = [], ?string $requestId = null): array
    {
        if (! $this->estConfigure()) {
            throw new MasterSupportIndisponible('KLASSCI Care n\'est pas configuré sur cette instance (MASTER_SUPPORT_TOKEN).');
        }
        if (Cache::has(self::CLE_COUPE_CIRCUIT)) {
            throw new MasterSupportIndisponible('Master injoignable récemment, nouvel essai plus tard.');
        }

        $url = rtrim((string) config('services.master.api_url'), '/').'/v1/support/'.$chemin;
        $requestId ??= request()?->attributes->get('request_id');

        try {
            $reponse = $this->client($entetes + array_filter(['X-Request-ID' => $requestId]))
                ->send($methode, $url, $methode === 'GET' ? ['query' => $donnees] : ['json' => $donnees]);
        } catch (ConnectionException|TransferException $e) {
            // TransferException : redirections en boucle, reponse tronquee… tout ce
            // que Guzzle leve hors connexion. Le Master est a joindre plus tard.
            $this->couper('transport', $e->getMessage());
            throw new MasterSupportIndisponible('Master injoignable.', 0, $e);
        }

        return $this->interpreter($reponse);
    }

    private function interpreter(Response $reponse): array
    {
        if ($reponse->successful()) {
            return (array) $reponse->json();
        }

        if ($reponse->serverError() || $reponse->status() === 429) {
            $this->couper('http_'.$reponse->status());
            throw new MasterSupportIndisponible("Le Master a répondu {$reponse->status()}.");
        }

        // Une portee manquante n'est pas un identifiant revoque : l'instance
        // reste joignable pour tout le reste, donc pas de coupe-circuit.
        if ($reponse->status() === 403 && $reponse->json('error') === 'insufficient_scope') {
            Log::warning('KLASSCI Care : portée absente de l\'identifiant de l\'instance', ['message' => $reponse->json('message')]);

            throw new MasterSupportRefus(403, 'insufficient_scope', (string) ($reponse->json('message') ?? 'Portée insuffisante.'));
        }

        if (in_array($reponse->status(), [401, 403], true)) {
            $this->couper('identifiant_refuse');
            Log::error('KLASSCI Care : le Master refuse l\'identifiant de l\'instance (MASTER_SUPPORT_TOKEN)', [
                'statut' => $reponse->status(),
                'code' => $reponse->json('error'),
            ]);
            throw new IdentifiantInstanceRefuse("Identifiant de l'instance refusé par le Master ({$reponse->status()}).");
        }

        Log::warning('KLASSCI Care : requête refusée par le Master', [
            'statut' => $reponse->status(),
            'code' => $reponse->json('error'),
        ]);

        throw new MasterSupportRefus(
            $reponse->status(),
            (string) ($reponse->json('error') ?? 'refus'),
            (string) ($reponse->json('message') ?? 'Demande refusée par le Master.'),
            (array) ($reponse->json('errors') ?? []),
        );
    }

    private function client(array $entetes): PendingRequest
    {
        return Http::withToken((string) config('services.master.support_token'))
            ->acceptJson()
            ->withHeaders($entetes)
            ->connectTimeout((int) config('support.delais.connexion', 2))
            ->timeout((int) config('support.delais.reponse', 5));
    }

    private function couper(string $raison, ?string $detail = null): void
    {
        Cache::put(self::CLE_COUPE_CIRCUIT, $raison, (int) config('support.delais.coupe_circuit_secondes', 60));
        Log::warning('KLASSCI Care : Master indisponible, coupe-circuit ouvert', ['raison' => $raison, 'detail' => $detail]);
    }
}
