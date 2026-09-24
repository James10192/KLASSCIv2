<?php

namespace App\Services\MailPulse;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Le transport commun aux lectures et aux verifications MailPulse (rail cle
 * API). Les envois historiques restent dans MailPulseClient ; ceci ne fait que
 * porter les nouveaux appels sans alourdir ce fichier.
 *
 * Meme source de reglages que MailPulseClient (Setting puis config), meme
 * refus explicites quand MailPulse est desactive ou sans cle : on ne part
 * jamais sur le reseau pour rien.
 */
class MailPulseApi
{
    /** Codes rendus sans appel reseau, parce que la configuration l'interdit. */
    public const DESACTIVE = 'disabled';

    public const CLE_ABSENTE = 'missing_api_key';

    public const INJOIGNABLE = 'connection_failed';

    public function __construct(private readonly MailPulseClient $client) {}

    /**
     * @param  array<string, mixed>|null  $corps
     * @return Response|string la reponse, ou le code de refus local
     */
    public function appeler(string $methode, string $chemin, ?array $corps, string $operation, int $timeout = 10, int $essais = 2): Response|string
    {
        if (! filter_var($this->client->getSetting('mailpulse_enabled', 'enabled', '1'), FILTER_VALIDATE_BOOLEAN)) {
            return self::DESACTIVE;
        }

        $cle = $this->client->getSetting('mailpulse_api_key', 'api_key', '');
        if ($cle === '') {
            return self::CLE_ABSENTE;
        }

        $requestId = 'klassci-'.(string) Str::uuid();

        try {
            $reponse = $this->requete($cle, $requestId, $timeout, $essais)->send($methode, $this->url($chemin), $corps === null ? [] : ['json' => $corps]);
        } catch (ConnectionException $e) {
            Log::warning('MailPulse injoignable', ['operation' => $operation, 'request_id' => $requestId, 'erreur' => $e->getMessage()]);

            return self::INJOIGNABLE;
        }

        Log::info('MailPulse appel termine', ['operation' => $operation, 'request_id' => $requestId, 'http_status' => $reponse->status()]);

        return $reponse;
    }

    private function requete(string $cle, string $requestId, int $timeout, int $essais): PendingRequest
    {
        return Http::withToken($cle)
            ->acceptJson()
            ->timeout($timeout)
            ->connectTimeout(min(5, $timeout))
            // Seules les pannes de transport se rejouent : une reponse HTTP,
            // meme une erreur, est un verdict que l'appelant interprete.
            ->retry($essais, 300, fn ($e) => $e instanceof ConnectionException, throw: false)
            ->withHeaders(['X-KLASSCI-Request-Id' => $requestId]);
    }

    private function url(string $chemin): string
    {
        $base = rtrim($this->client->getSetting('mailpulse_base_url', 'base_url', 'https://mailpulse-two.vercel.app'), '/');

        return $base.'/'.ltrim($chemin, '/');
    }
}
