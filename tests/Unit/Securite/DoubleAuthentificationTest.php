<?php

namespace Tests\Unit\Securite;

use App\Domain\Securite\DoubleAuthentification;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Le second facteur.
 *
 * La verification la plus utile est celle des vecteurs de la RFC 6238 : elle
 * prouve que ce qu'on calcule est bien ce que produit l'application du
 * telephone. Sans elle, on ne saurait qu'a l'usage — c'est-a-dire le jour ou
 * quelqu'un ne peut plus se connecter.
 *
 * Part de `Tests\TestCase` et non de PHPUnit : la regle qui decide a qui le
 * second facteur s'applique lit un reglage, donc le conteneur. Aucune base
 * n'est touchee pour autant.
 */
class DoubleAuthentificationTest extends TestCase
{
    private DoubleAuthentification $totp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->totp = new DoubleAuthentification(new Google2FA());
    }

    public function test_le_calcul_suit_les_vecteurs_officiels_de_la_rfc_6238(): void
    {
        // Le secret de la RFC (« 12345678901234567890 ») en base32, et les
        // codes qu'elle publie pour des instants donnes. Si ceci passe, ce
        // qu'on calcule est ce que Google Authenticator, Authy et FreeOTP
        // calculent.
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
        $google2fa = new Google2FA();

        $vecteurs = [
            59 => '287082',
            1111111109 => '081804',
            1111111111 => '050471',
            1234567890 => '005924',
            2000000000 => '279037',
        ];

        foreach ($vecteurs as $instant => $attendu) {
            $this->assertSame(
                $attendu,
                $google2fa->oathTotp($secret, (int) floor($instant / 30)),
                "vecteur RFC a t={$instant}",
            );
        }
    }

    public function test_un_secret_neuf_fait_160_bits(): void
    {
        // La taille recommandee par la RFC 4226 pour une cle HMAC-SHA1.
        $secret = $this->totp->nouveauSecret();

        $this->assertSame(32, strlen($secret));
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret, 'doit etre du base32');
    }

    public function test_deux_secrets_ne_se_ressemblent_pas(): void
    {
        $this->assertNotSame($this->totp->nouveauSecret(), $this->totp->nouveauSecret());
    }

    public function test_le_code_courant_est_accepte(): void
    {
        $google2fa = new Google2FA();
        $secret = $this->totp->nouveauSecret();

        $this->assertTrue($this->totp->codeValide($secret, $google2fa->getCurrentOtp($secret)));
    }

    public function test_un_code_faux_est_refuse(): void
    {
        $secret = $this->totp->nouveauSecret();

        foreach (['000000', '123456', '', 'abcdef', '12345', '1234567'] as $faux) {
            $this->assertFalse($this->totp->codeValide($secret, $faux), "doit refuser : {$faux}");
        }
    }

    public function test_un_code_recopie_avec_une_espace_est_accepte(): void
    {
        // Les applications affichent « 123 456 ». On recopie l'espace.
        $google2fa = new Google2FA();
        $secret = $this->totp->nouveauSecret();
        $code = $google2fa->getCurrentOtp($secret);

        $avecEspace = substr($code, 0, 3) . ' ' . substr($code, 3);

        $this->assertTrue($this->totp->codeValide($secret, $avecEspace));
    }

    public function test_le_code_du_creneau_precedent_est_tolere(): void
    {
        // L'horloge d'un telephone derive, celle d'un serveur aussi. Sans
        // tolerance, une derive de quelques secondes refuse un code juste, et
        // la personne conclut que la fonction est cassee — ce qui produit une
        // demande de desactivation, pas un ticket d'horloge.
        $google2fa = new Google2FA();
        $secret = $this->totp->nouveauSecret();

        $creneauPrecedent = $google2fa->oathTotp($secret, (int) floor(time() / 30) - 1);

        $this->assertTrue($this->totp->codeValide($secret, $creneauPrecedent));
    }

    public function test_un_code_trop_ancien_est_refuse(): void
    {
        // La tolerance vaut 30 secondes, pas dix minutes : un code lu sur un
        // ecran il y a un quart d'heure ne doit plus ouvrir.
        $google2fa = new Google2FA();
        $secret = $this->totp->nouveauSecret();

        $vieux = $google2fa->oathTotp($secret, (int) floor(time() / 30) - 30);

        $this->assertFalse($this->totp->codeValide($secret, $vieux));
    }

    public function test_l_adresse_otp_porte_le_nom_de_l_ecole(): void
    {
        // Quelqu'un qui gere deux ecoles doit distinguer les deux lignes dans
        // son application. Un utilisateur qui ne sait pas lequel de ses codes
        // va ou finit par tous les supprimer.
        $adresse = $this->totp->adresseOtp('ESBTP Yamoussoukro', 'marcel@ecole.ci', $this->totp->nouveauSecret());

        $this->assertStringStartsWith('otpauth://totp/', $adresse);
        $this->assertStringContainsString('ESBTP', $adresse);
        $this->assertStringContainsString('marcel', $adresse);
    }

    public function test_le_qr_code_est_un_svg_qui_ne_sort_pas_le_secret_par_une_autre_requete(): void
    {
        $secret = $this->totp->nouveauSecret();
        $svg = $this->totp->qrCodeSvg($this->totp->adresseOtp('École', 'x@y.ci', $secret));

        $this->assertStringContainsString('<svg', $svg);
        // Le SVG est pose directement dans la page : l'adresse otpauth, qui
        // contient le secret, ne transite par aucune requete supplementaire et
        // ne s'ecrit dans aucun journal d'acces.
        $this->assertStringNotContainsString($secret, $svg);
    }

    public function test_aucun_role_n_est_concerne_par_defaut(): void
    {
        // Un deploiement qui exigerait d'emblee un second facteur
        // verrouillerait le secretariat d'une ecole en pleine rentree, sans
        // que personne sur place puisse y remedier.
        foreach (['', null, []] as $vide) {
            config(['securite.double_auth_roles' => $vide]);
            $this->assertSame([], DoubleAuthentification::rolesExiges());
        }
    }

    public function test_l_ecole_designe_les_roles_qu_elle_veut_proteger(): void
    {
        config(['securite.double_auth_roles' => 'superAdmin, comptable']);

        $this->assertSame(['superAdmin', 'comptable'], DoubleAuthentification::rolesExiges());
    }
}
