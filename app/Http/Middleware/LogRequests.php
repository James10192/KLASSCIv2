<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogRequests
{
    /**
     * Les champs qu'on ne recopie jamais dans un journal.
     *
     * Le mot de passe et le jeton pour des raisons evidentes ; matricule et
     * date de naissance parce qu'ils forment ensemble le facteur
     * d'identification du portail de reinscription ; `ip_client` parce que la
     * base ne conserve l'adresse du visiteur que sous forme d'empreinte, et
     * l'ecrire en clair ici annulerait cette precaution.
     */
    private const CHAMPS_MASQUES = [
        'password', 'password_confirmation', 'current_password', '_token',
        'matricule', 'date_naissance', 'ip_client',
    ];

    public function handle(Request $request, Closure $next)
    {
        Log::info('Incoming request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'input' => $this->corpsJournalisable($request),
            'route' => $request->route() ? $request->route()->getName() : null,
        ]);

        return $next($request);
    }

    /**
     * Sur les routes publiques, RIEN du corps n'est journalise.
     *
     * Une liste de champs a masquer se maintient a la main, et elle derive :
     * elle a ete ecrite quand le portail transportait un matricule et une date
     * de naissance, puis la candidature en ligne y a ajoute vingt-deux champs
     * — etat civil complet d'un bachelier souvent mineur, telephone, et
     * coordonnees de son tuteur — sans que personne ne pense a la rallonger.
     * Ces champs seraient partis en clair dans storage/logs, lisibles par le
     * support et repris dans les sauvegardes, sur six ecoles.
     *
     * L'inversion est le seul reglage stable : sur `api/public/*`, on ne
     * journalise pas le corps du tout. La methode, l'URL et le nom de route
     * suffisent a suivre le trafic et a diagnostiquer une panne ; le detail de
     * ce qu'un candidat a saisi n'a rien a faire la.
     */
    private function corpsJournalisable(Request $request): array
    {
        if ($request->is('api/public/*', 'api/portail/*')) {
            return ['_masque' => 'corps non journalise (route publique)'];
        }

        return $request->except(self::CHAMPS_MASQUES);
    }
}
