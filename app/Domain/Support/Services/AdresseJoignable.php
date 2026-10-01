<?php

namespace App\Domain\Support\Services;

use App\Models\User;
use App\Services\Verification\MasqueContact;
use Illuminate\Support\Facades\Route;

/**
 * Peut-on ecrire a cette personne par e-mail ?
 *
 * Une seule reponse pour toute l'application : une adresse bien formee ET
 * marquee `email_verified_at`. Ce que ce champ garantit, et rien de plus :
 * l'adresse a ete saisie par l'administration a la creation du compte (qui
 * pose le champ d'office), ou confirmee par un lien recu a cette adresse.
 * Il ne prouve pas que l'administration ne s'est pas trompee en la saisissant.
 * En revanche, une adresse modifiee depuis n'est jamais tenue pour confirmee :
 * User remet le champ a null quand `email` change sans qu'il soit repose.
 *
 * Une adresse jamais confirmee peut etre une faute de frappe, ou celle d'un
 * collegue : y envoyer le contenu d'une demande de support serait l'envoyer
 * a un inconnu.
 *
 * Rien n'est bloque quand l'adresse n'est pas joignable : l'avertissement
 * dans l'application suffit, l'e-mail ne fait que s'y ajouter.
 */
final class AdresseJoignable
{
    public static function pour(?User $user): ?string
    {
        if ($user === null || $user->email_verified_at === null) {
            return null;
        }

        return self::bienFormee($user);
    }

    public static function estJoignable(?User $user): bool
    {
        return self::pour($user) !== null;
    }

    /**
     * Une adresse existe, bien formee, mais n'est pas encore confirmee : c'est
     * le seul cas ou proposer d'envoyer un lien a un sens.
     */
    public static function aVerifier(?User $user): bool
    {
        return $user !== null && $user->email_verified_at === null && self::bienFormee($user) !== null;
    }

    /**
     * Ce que l'interface doit savoir pour inviter a confirmer l'adresse.
     *
     * @return array{email_a_verifier: bool, email_masque: string|null, email_verification_url: string|null}
     */
    public static function etat(?User $user): array
    {
        $aVerifier = self::aVerifier($user);

        return [
            'email_a_verifier' => $aVerifier,
            'email_masque' => $aVerifier ? MasqueContact::email($user->email) : null,
            'email_verification_url' => $aVerifier && Route::has('support.courriel.lien') ? route('support.courriel.lien') : null,
        ];
    }

    private static function bienFormee(User $user): ?string
    {
        $email = trim((string) $user->email);

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }
}
