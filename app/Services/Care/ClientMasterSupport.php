<?php

namespace App\Services\Care;

use App\Domain\Support\Exceptions\MasterSupportIndisponible;
use App\Domain\Support\Exceptions\MasterSupportRefus;
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
 * d'envoi.
 */
class ClientMasterSupport
{
    private const CLE_COUPE_CIRCUIT = 'care:master:indisponible';

    private const CLE_FONCTIONNALITES = 'care:master:fonctionnalites';

    private const CLE_FONCTIONNALITES_CONNUES = 'care:master:fonctionnalites:derniere';

    public function estConfigure(): bool
    {
        return filled(config('services.master.api_url')) && filled(config('services.master.support_token'));
    }

    /**
     * Les fonctionnalites KLASSCI Care ouvertes a cette instance.
     *
     * Lu au rendu des pages : ne leve jamais. Master injoignable → derniere
     * reponse connue, sinon tout ferme.
     *
     * @return array<string, bool>
     */
    public function fonctionnalites(): array
    {
        if (! $this->estConfigure()) {
            return [];
        }

        return Cache::remember(self::CLE_FONCTIONNALITES, (int) config('support.delais.fonctionnalites_secondes', 300), function () {
            try {
                $f = (array) ($this->requete('GET', 'bootstrap')['fonctionnalites'] ?? []);
                Cache::forever(self::CLE_FONCTIONNALITES_CONNUES, $f);

                return $f;
            } catch (MasterSupportIndisponible|MasterSupportRefus) {
                return (array) Cache::get(self::CLE_FONCTIONNALITES_CONNUES, []);
            }
        });
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
        } catch (ConnectionException $e) {
            $this->couper('connexion', $e->getMessage());
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
