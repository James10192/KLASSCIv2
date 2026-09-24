<?php

namespace App\Services\Emails;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Un domaine recoit-il du courrier ? Trois reponses, pas deux.
 *
 * `false` n'est rendu que si le reseau repond : un domaine temoin (gmail.com)
 * se resout, et celui-ci non. Si le temoin lui-meme ne se resout pas, c'est le
 * serveur qui est hors ligne, et l'on repond `null` (inconnu) — une adresse ne
 * se refuse jamais parce que le DNS de l'hebergeur est en panne.
 *
 * Les verdicts sont caches un jour : un formulaire soumis cent fois ne
 * relance pas cent resolutions. Le temoin est cache cinq minutes.
 *
 * Le formulaire public n'attend jamais le DNS plus d'environ une seconde : le
 * resolveur a un delai borne (ResolveurDnsSysteme), et s'il le depasse, la
 * verification MX est suspendue cinq minutes pour tout le monde.
 */
class VerificateurMx
{
    private const INCONNU = 'inconnu';

    private const DUREE_INCONNU_SECONDES = 300;

    private const CLE_RESOLVEUR_LENT = 'emails-joignables:mx:resolveur-lent';

    private const CLE_TEMOIN = 'emails-joignables:mx:temoin';

    public function __construct(private readonly ResolveurDns $dns) {}

    public function recoitDuCourrier(string $domaine): ?bool
    {
        if (! filter_var(config('emails_joignables.mx.actif', true), FILTER_VALIDATE_BOOLEAN)) {
            return null;
        }

        $domaine = mb_strtolower(trim($domaine));
        if ($domaine === '' || Cache::get(self::CLE_RESOLVEUR_LENT) === true) {
            return null;
        }

        $cle = 'emails-joignables:mx:'.hash('sha256', $domaine);
        $cache = Cache::get($cle);
        if (is_bool($cache)) {
            return $cache;
        }
        if ($cache === self::INCONNU) {
            return null;
        }

        // Un echec DNS se memorise aussi, mais peu de temps : sans cela, un
        // resolveur en panne ferait attendre chaque soumission de formulaire.
        $verdict = $this->resoudre($domaine);
        $verdict === null
            ? Cache::put($cle, self::INCONNU, self::DUREE_INCONNU_SECONDES)
            : Cache::put($cle, $verdict, (int) config('emails_joignables.mx.cache_secondes', 86400));

        return $verdict;
    }

    private function resoudre(string $domaine): ?bool
    {
        try {
            if ($this->dns->recoitDuCourrier($domaine)) {
                return true;
            }

            return $this->reseauRepond() ? false : null;
        } catch (DnsTropLent $e) {
            Cache::put(self::CLE_RESOLVEUR_LENT, true, self::DUREE_INCONNU_SECONDES);
            Log::warning('Resolveur DNS trop lent : verification MX suspendue', ['domaine' => $domaine, 'minutes' => self::DUREE_INCONNU_SECONDES / 60]);

            return null;
        } catch (\Throwable $e) {
            Log::warning('Verification MX impossible', ['domaine' => $domaine, 'erreur' => $e->getMessage()]);

            return null;
        }
    }

    /** Le domaine temoin se resout-il ? Cache cinq minutes, dans un sens comme dans l'autre. */
    private function reseauRepond(): bool
    {
        return Cache::remember(self::CLE_TEMOIN, self::DUREE_INCONNU_SECONDES, fn () => $this->dns->recoitDuCourrier(
            (string) config('emails_joignables.mx.domaine_temoin', 'gmail.com')
        ) ? 'oui' : 'non') === 'oui';
    }
}
