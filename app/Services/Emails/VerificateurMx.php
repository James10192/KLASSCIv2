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
 * relance pas cent resolutions.
 */
class VerificateurMx
{
    public function __construct(private readonly ResolveurDns $dns) {}

    public function recoitDuCourrier(string $domaine): ?bool
    {
        if (! filter_var(config('emails_joignables.mx.actif', true), FILTER_VALIDATE_BOOLEAN)) {
            return null;
        }

        $domaine = mb_strtolower(trim($domaine));
        if ($domaine === '') {
            return null;
        }

        $cle = 'emails-joignables:mx:'.hash('sha256', $domaine);
        $cache = Cache::get($cle);
        if (is_bool($cache)) {
            return $cache;
        }

        $verdict = $this->resoudre($domaine);
        if ($verdict !== null) {
            Cache::put($cle, $verdict, (int) config('emails_joignables.mx.cache_secondes', 86400));
        }

        return $verdict;
    }

    private function resoudre(string $domaine): ?bool
    {
        try {
            if ($this->dns->recoitDuCourrier($domaine)) {
                return true;
            }

            $temoin = (string) config('emails_joignables.mx.domaine_temoin', 'gmail.com');

            return $this->dns->recoitDuCourrier($temoin) ? false : null;
        } catch (\Throwable $e) {
            Log::warning('Verification MX impossible', ['domaine' => $domaine, 'erreur' => $e->getMessage()]);

            return null;
        }
    }
}
