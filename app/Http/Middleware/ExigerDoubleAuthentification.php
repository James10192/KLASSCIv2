<?php

namespace App\Http\Middleware;

use App\Domain\Securite\DoubleAuthentification;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Retient une session tant que le second facteur n'a pas été présenté.
 *
 * Le mot de passe a été vérifié — la personne est authentifiée au sens de
 * Laravel — mais la session ne porte pas encore la marque du second facteur.
 * Toute page est donc renvoyée vers l'écran de saisie du code.
 *
 * **Ce filtre ne bloque que ceux qui ont déjà confirmé un second facteur.**
 * Quelqu'un qui n'en a pas passe sans rien remarquer, même si son rôle figure
 * dans le réglage de l'école. C'est ce qui rend le déploiement sûr : activer
 * le réglage n'enferme personne dehors, il ouvre seulement la possibilité de
 * s'inscrire.
 */
class ExigerDoubleAuthentification
{
    public const CLE_SESSION = 'double_auth_presente';

    public function handle(Request $request, Closure $next): Response
    {
        $utilisateur = $request->user();

        if ($utilisateur === null) {
            return $next($request);
        }

        // La dependance du second facteur se resout ICI, et non dans le
        // constructeur.
        //
        // Injectee au constructeur, elle etait construite a CHAQUE requete web,
        // ce filtre appartenant au groupe « web ». Une instance dont le vendor
        // n'avait pas ete mis a jour apres la livraison ne pouvait donc plus
        // servir une seule page : le conteneur echouait avant meme d'atteindre
        // l'ecran de connexion, et l'application entiere repondait 500 pour une
        // fonctionnalite qui n'etait activee nulle part.
        //
        // Une garde de securite qui ferme l'etablissement au lieu de le
        // proteger n'est pas une garde. Sans le paquet, on laisse passer :
        // c'est exactement l'etat d'avant la livraison, et le reglage reste
        // sans effet tant que personne n'a confirme de second facteur.
        if (! class_exists(\PragmaRX\Google2FA\Google2FA::class)) {
            return $next($request);
        }

        $doubleAuth = app(DoubleAuthentification::class);

        if (! $doubleAuth->estExigee($utilisateur)) {
            return $next($request);
        }

        if ($request->session()->get(self::CLE_SESSION) === true) {
            return $next($request);
        }

        // Les routes du second facteur lui-même, et la déconnexion. Sans cette
        // exception, l'écran de saisie se renverrait vers lui-même à l'infini
        // et l'on ne pourrait même plus se déconnecter.
        if ($request->routeIs('securite.double-auth.*', 'logout')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Second facteur requis.',
                'redirection' => route('securite.double-auth.demande'),
            ], 423);
        }

        return redirect()->route('securite.double-auth.demande');
    }
}
