<?php

namespace Tests\Unit\Emails;

use App\Enums\EtatEmail;
use App\Rules\EmailJoignable;
use App\Services\Emails\AnalyseurEmail;
use App\Services\Emails\DiagnosticEmail;
use App\Services\Emails\ResolveurDns;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * La regle d'adresse joignable : memes verdicts que le site vitrine, plus le
 * DNS quand il sait repondre.
 */
class AnalyseurEmailTest extends TestCase
{
    /**
     * Empreinte du fichier partage avec klassci-landing (scripts/verifier-email.mjs),
     * apres ajout des domaines fabriques par KLASSCI et des extensions reservees.
     */
    private const EMPREINTE_LANDING = '021ecd8807b450edfa1ab7aa5ce331b79d30157e17c6a2d06a790770ea110439';

    private function analyser(string $email)
    {
        return app(AnalyseurEmail::class)->analyser($email);
    }

    public function test_la_copie_des_domaines_suspects_est_celle_du_site_vitrine(): void
    {
        $brut = (string) file_get_contents(resource_path('data/domaines-suspects.json'));

        // Meme serialisation que JSON.stringify : JSON compact, cles dans l'ordre du fichier.
        $this->assertSame(self::EMPREINTE_LANDING, hash('sha256', json_encode(json_decode($brut, true), JSON_UNESCAPED_SLASHES)));
    }

    public function test_une_faute_connue_est_certaine_et_suggere_l_adresse_complete(): void
    {
        $analyse = $this->analyser('Kouassi.Ama@gmail.con');

        $this->assertSame(EtatEmail::FauteDeFrappe, $analyse->etat);
        $this->assertSame('Kouassi.Ama@gmail.com', $analyse->suggestion);
    }

    public function test_une_extension_fautive_se_corrige_par_la_table_seulement(): void
    {
        $this->assertSame('a@hotmail.fr', $this->analyser('a@hotmail.frr')->suggestion);
        $this->assertSame('a@yahoo.com', $this->analyser('a@yahoo.con')->suggestion);
        // `gmail.cm` est une faute CONNUE (liste explicite), pas une regle d'extension.
        $this->assertSame('a@gmail.com', $this->analyser('a@gmail.cm')->suggestion);
        // Une extension de pays valide n'est jamais remplacee par une autre : .cm est le Cameroun.
        $this->assertSame(EtatEmail::Valide, $this->analyser('a@camtel.cm')->etat);
        $this->assertSame(EtatEmail::Valide, $this->analyser('a@orange.cm')->etat);
        $this->assertSame(EtatEmail::Valide, $this->analyser('a@live.be')->etat);
        $this->assertSame(EtatEmail::Valide, $this->analyser('a@orange.ci')->etat);
    }

    public function test_un_nom_voisin_est_une_faute_probable_transposition_comprise(): void
    {
        $analyse = $this->analyser('a@gmali.com');

        $this->assertSame(EtatEmail::FauteProbable, $analyse->etat);
        $this->assertSame('a@gmail.com', $analyse->suggestion);
        $this->assertTrue($analyse->joignable());
        $this->assertSame(EtatEmail::FauteProbable, $this->analyser('a@hotmali.fr')->etat);
    }

    public function test_les_noms_reels_voisins_ne_sont_jamais_corriges(): void
    {
        foreach (['ymail.com', 'mail.com', 'email.com', 'gmx.com'] as $domaine) {
            $this->assertSame(EtatEmail::Valide, $this->analyser('a@'.$domaine)->etat, $domaine);
        }
    }

    public function test_les_domaines_fabriques_par_klassci_sont_factices(): void
    {
        foreach (['m22-0521@esbtp.edu.ci', 'jean.kouame@esbtp.edu', 'x@mail.esbtp.edu.ci', 'eleve@demo.klassci.local', 'a@b.invalid'] as $email) {
            $this->assertSame(EtatEmail::Factice, $this->analyser($email)->etat, $email);
        }
    }

    public function test_un_point_final_ou_double_est_invalide(): void
    {
        $this->assertSame(EtatEmail::Invalide, $this->analyser('a@gmail.com.')->etat);
        $this->assertSame(EtatEmail::Invalide, $this->analyser('a@gmail..com')->etat);
    }

    public function test_une_adresse_deja_enregistree_ne_bloque_pas_une_edition(): void
    {
        $regle = new EmailJoignable(['m22-0521@esbtp.edu.ci', null]);

        $this->assertFalse(Validator::make(['email' => 'M22-0521@esbtp.edu.ci'], ['email' => [$regle]])->fails());
        $this->assertTrue(Validator::make(['email' => 'autre@esbtp.edu.ci'], ['email' => [$regle]])->fails());
    }

    public function test_la_regle_refuse_avec_le_message_de_suggestion(): void
    {
        $validation = Validator::make(['email' => 'k@gmail.con'], ['email' => [new EmailJoignable]]);

        $this->assertTrue($validation->fails());
        $this->assertSame('Vouliez-vous dire k@gmail.com ?', $validation->errors()->first('email'));
        $this->assertFalse(Validator::make(['email' => 'k@gmail.com'], ['email' => [new EmailJoignable]])->fails());
        $this->assertFalse(Validator::make(['email' => null], ['email' => ['nullable', new EmailJoignable]])->fails());
        $this->assertTrue(Validator::make(['email' => 'x@esbtp.edu.ci'], ['email' => [new EmailJoignable]])->fails());
    }

    public function test_un_domaine_sans_mx_est_refuse_quand_le_reseau_repond(): void
    {
        $this->dns(['gmail.com' => true, 'ecole-imaginaire.ci' => false, 'gmali.com' => false]);

        $diagnostic = app(DiagnosticEmail::class);

        $this->assertSame(EtatEmail::SansMx, $diagnostic->diagnostiquer('a@ecole-imaginaire.ci')->etat);
        // Une faute probable dont le domaine ne recoit rien devient certaine.
        $this->assertSame(EtatEmail::FauteDeFrappe, $diagnostic->diagnostiquer('a@gmali.com')->etat);
    }

    public function test_sans_reseau_le_dns_ne_refuse_rien(): void
    {
        $this->dns(['gmail.com' => false, 'ecole-imaginaire.ci' => false]);

        $this->assertSame(EtatEmail::Valide, app(DiagnosticEmail::class)->diagnostiquer('a@ecole-imaginaire.ci')->etat);
    }

    public function test_un_resolveur_trop_lent_suspend_la_verification_mx(): void
    {
        config(['emails_joignables.mx.actif' => true]);
        Cache::flush();
        $appels = 0;
        $this->app->instance(ResolveurDns::class, new class($appels) implements ResolveurDns
        {
            public function __construct(private int &$appels) {}

            public function recoitDuCourrier(string $domaine): bool
            {
                $this->appels++;
                throw new \App\Services\Emails\DnsTropLent('lent');
            }
        });
        $mx = app(\App\Services\Emails\VerificateurMx::class);

        $this->assertNull($mx->recoitDuCourrier('ecole-imaginaire.ci'));
        $this->assertNull($mx->recoitDuCourrier('autre-ecole.ci'));
        $this->assertSame(1, $appels, 'Une fois le resolveur juge lent, plus aucun formulaire ne l\'attend.');
    }

    public function test_le_domaine_temoin_n_est_interroge_qu_une_fois(): void
    {
        $this->dns(['gmail.com' => true]);
        $appels = [];
        $this->app->instance(ResolveurDns::class, new class($appels) implements ResolveurDns
        {
            public function __construct(private array &$appels) {}

            public function recoitDuCourrier(string $domaine): bool
            {
                $this->appels[] = $domaine;

                return $domaine === 'gmail.com';
            }
        });
        $mx = app(\App\Services\Emails\VerificateurMx::class);

        $this->assertFalse($mx->recoitDuCourrier('ecole-une.ci'));
        $this->assertFalse($mx->recoitDuCourrier('ecole-deux.ci'));
        $this->assertSame(1, count(array_keys($appels, 'gmail.com')));
    }

    /** @param  array<string, bool>  $reponses */
    private function dns(array $reponses): void
    {
        config(['emails_joignables.mx.actif' => true]);
        Cache::flush();
        $this->app->instance(ResolveurDns::class, new class($reponses) implements ResolveurDns
        {
            public function __construct(private readonly array $reponses) {}

            public function recoitDuCourrier(string $domaine): bool
            {
                return $this->reponses[$domaine] ?? false;
            }
        });
    }
}
