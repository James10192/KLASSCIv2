<?php

namespace App\Domain\Securite;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Google2FA;

/**
 * Le second facteur, et la règle qui décide à qui il s'applique.
 *
 * Un mot de passe de huit caractères est aujourd'hui le seul rempart devant
 * l'état civil de deux mille élèves mineurs, leurs coordonnées familiales et
 * la comptabilité de l'école. Le second facteur existe pour qu'un mot de passe
 * deviné, réutilisé ailleurs, ou lu par-dessus une épaule ne suffise plus.
 *
 * **Rien n'est imposé, et c'est délibéré.** Un déploiement qui exigerait
 * d'emblée un second facteur verrouillerait le secrétariat d'une école en
 * pleine rentrée, sans que personne sur place puisse y remédier. L'école
 * l'active pour les rôles qu'elle choisit, et chaque personne le confirme
 * depuis son propre téléphone avant qu'il ne lui soit demandé. Une sécurité
 * qui bloque le travail se fait désactiver dans la semaine.
 */
class DoubleAuthentification
{
    public function __construct(private Google2FA $google2fa)
    {
    }

    /**
     * Un secret neuf.
     *
     * 32 caractères en base32, soit 160 bits — la taille recommandée par la
     * RFC 4226 pour une clé HMAC-SHA1.
     */
    public function nouveauSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    /**
     * Le code saisi correspond-il au secret ?
     *
     * La fenêtre de tolérance vaut 1, soit ±30 secondes. Ce n'est pas de la
     * complaisance : l'horloge d'un téléphone dérive, et celle d'un serveur
     * aussi. À 0, une dérive de quelques secondes refuse un code juste, et la
     * personne conclut que la fonction est cassée — ce qui produit une demande
     * de désactivation, pas un ticket d'horloge.
     */
    public function codeValide(string $secret, string $code): bool
    {
        $propre = preg_replace('/\D/', '', $code) ?? '';

        if (strlen($propre) !== 6) {
            return false;
        }

        return (bool) $this->google2fa->verifyKey($secret, $propre, 1);
    }

    /**
     * L'adresse `otpauth://` que lit une application d'authentification.
     *
     * L'émetteur porte le nom de l'établissement, pas « KLASSCI » : quelqu'un
     * qui gère deux écoles doit distinguer les deux lignes dans son
     * application. Un utilisateur qui ne sait pas lequel de ses codes va où
     * finit par tous les supprimer.
     */
    public function adresseOtp(string $etablissement, string $identifiant, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl($etablissement, $identifiant, $secret);
    }

    /**
     * Le QR code, en SVG, prêt à poser dans une page.
     *
     * SVG et non PNG : il reste net sur un écran comme à l'impression, et il
     * s'intègre sans fichier ni requête supplémentaire — donc sans que
     * l'adresse `otpauth://`, qui contient le secret, ne transite par une
     * autre requête ni ne s'écrive dans un journal d'accès.
     */
    public function qrCodeSvg(string $adresseOtp): string
    {
        $rendu = new ImageRenderer(new RendererStyle(220, 1), new SvgImageBackEnd());

        return (new Writer($rendu))->writeString($adresseOtp);
    }

    /* ─────────────────── À qui cela s'applique ─────────────────── */

    /**
     * Cette personne doit-elle présenter un second facteur ?
     *
     * Deux conditions, et il faut les deux : l'école a demandé le second
     * facteur pour l'un de ses rôles, ET elle l'a elle-même confirmé depuis
     * son téléphone. La seconde est le garde-fou : sans elle, activer le
     * réglage verrouillerait d'un coup tout le personnel concerné.
     */
    public function estExigee(User $utilisateur): bool
    {
        return $this->estConfirmee($utilisateur) && $this->rolesConcernes($utilisateur);
    }

    /** La personne a-t-elle confirmé un second facteur depuis son téléphone ? */
    public function estConfirmee(?User $utilisateur): bool
    {
        return $utilisateur !== null
            && $utilisateur->double_auth_confirme_le !== null
            && $utilisateur->double_auth_secret !== null;
    }

    /**
     * L'école a-t-elle demandé le second facteur pour un rôle de cette personne ?
     *
     * Le réglage liste des rôles plutôt qu'un simple oui/non : une école veut
     * protéger sa direction et sa comptabilité sans imposer un téléphone à
     * deux mille étudiants, dont beaucoup se connectent depuis un appareil
     * partagé.
     */
    public function rolesConcernes(User $utilisateur): bool
    {
        $exiges = self::rolesExiges();

        if ($exiges === []) {
            return false;
        }

        return $utilisateur->roles->pluck('name')->intersect($exiges)->isNotEmpty();
    }

    /** Les rôles pour lesquels l'école exige un second facteur. */
    public static function rolesExiges(): array
    {
        $brut = config('securite.double_auth_roles');

        if (is_string($brut)) {
            $brut = array_filter(array_map('trim', explode(',', $brut)));
        }

        return is_array($brut) ? array_values($brut) : [];
    }
}
