<?php

namespace App\Services\Master;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Les limites de l'instance, lues chez adminKlassci (/tenants/{code}/limits).
 *
 * Seul lecteur de cet appel. Le paywall, l'alerte d'expiration du contrat et le
 * budget de l'assistant l'interrogeaient chacun de leur côté, et deux d'entre eux
 * passaient par `Cache::remember` : or un échec rend `null`, que le cache ne
 * retient pas. Quand le master répondait en erreur, CHAQUE page refaisait l'appel
 * HTTP, avec un délai de 10 s — l'utilisateur attendait le master à chaque clic.
 *
 * Désormais : une réponse est gardée 5 min, un échec 1 min, et l'appel abandonne
 * au bout de 3 s. Une page ne paie donc jamais plus d'un appel par minute, et
 * jamais plus de 3 s.
 */
class LimitesDuMaster
{
    public const DUREE_REPONSE = 300;

    public const DUREE_ECHEC = 60;

    public const DELAI_SECONDES = 3;

    public static function cle(string $code): string
    {
        return 'paywall_limits_' . $code;
    }

    /** Les limites, ou null si le master n'est pas configuré ou ne répond pas. */
    public function lire(): ?array
    {
        $url = config('services.master.api_url');
        $jeton = config('services.master.api_token');
        $code = config('app.tenant_code');

        if (! $code) {
            return null;
        }

        // Le cache d'abord : une valeur deja lue vaut, meme si la configuration
        // de l'appel manque sur ce processus.
        $enCache = Cache::get(self::cle($code));
        if (is_array($enCache)) {
            return $enCache;
        }

        if (! $url || ! $jeton) {
            return null;
        }

        $cleEchec = self::cle($code) . '_echec';
        if (Cache::has($cleEchec)) {
            return null;
        }

        try {
            $reponse = Http::withToken($jeton)
                ->connectTimeout(2)
                ->timeout(self::DELAI_SECONDES)
                ->get(rtrim($url, '/') . '/tenants/' . $code . '/limits');

            if ($reponse->successful() && is_array($donnees = $reponse->json())) {
                Cache::put(self::cle($code), $donnees, self::DUREE_REPONSE);
                Cache::put(self::cle($code) . '_lu_a', now()->toIso8601String(), self::DUREE_REPONSE);
                Cache::forget($cleEchec);

                return $donnees;
            }

            $erreur = 'adminKlassci a répondu ' . $reponse->status();
            Log::warning('master.limites_illisibles', ['statut' => $reponse->status()]);
        } catch (\Throwable $e) {
            $erreur = 'adminKlassci injoignable';
            Log::warning('master.limites_injoignables', ['erreur' => $e->getMessage()]);
        }

        // L'echec garde sa raison et son heure : la page du paywall les montre
        // au service technique au lieu d'afficher des valeurs sans dire d'ou
        // elles viennent.
        Cache::put($cleEchec, ['erreur' => $erreur, 'a' => now()->toIso8601String()], self::DUREE_ECHEC);

        return null;
    }

    /** Le master est-il configure sur cette instance (adresse, jeton, code) ? */
    public function estConfigure(): bool
    {
        return filled(config('services.master.api_url'))
            && filled(config('services.master.api_token'))
            && filled(config('app.tenant_code'));
    }

    /** L'heure de la derniere lecture reussie encore en cache, ou null. */
    public function derniereLecture(): ?string
    {
        $code = config('app.tenant_code');

        return $code ? (Cache::get(self::cle($code) . '_lu_a') ?: null) : null;
    }

    /** Le dernier echec encore retenu : ['erreur' => ..., 'a' => iso], ou null. */
    public function dernierEchec(): ?array
    {
        $code = config('app.tenant_code');
        $echec = $code ? Cache::get(self::cle($code) . '_echec') : null;

        return is_array($echec) ? $echec : null;
    }

    /**
     * Oublie la reponse et l'echec gardes, pour que la lecture suivante
     * interroge adminKlassci sur-le-champ. Bouton « Actualiser ».
     */
    public function oublier(): void
    {
        $code = config('app.tenant_code');
        if (! $code) {
            return;
        }

        Cache::forget(self::cle($code));
        Cache::forget(self::cle($code) . '_lu_a');
        Cache::forget(self::cle($code) . '_echec');
    }
}
