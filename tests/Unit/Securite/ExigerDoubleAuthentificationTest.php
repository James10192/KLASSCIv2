<?php

namespace Tests\Unit\Securite;

use App\Domain\Securite\DoubleAuthentification;
use App\Http\Middleware\ExigerDoubleAuthentification;
use App\Models\User;
use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Le filtre qui retient une session tant que le second facteur n'a pas ete
 * presente.
 *
 * La verification la plus importante de ce fichier est la troisieme : activer
 * le reglage ne doit enfermer personne dehors. C'est cette propriete qui rend
 * le deploiement sur une ecole en pleine rentree acceptable — sans elle, une
 * ligne de configuration priverait un secretariat de son outil de travail, un
 * matin, sans que personne sur place puisse y remedier.
 */
class ExigerDoubleAuthentificationTest extends TestCase
{
    private function filtre(): ExigerDoubleAuthentification
    {
        return new ExigerDoubleAuthentification(new DoubleAuthentification(new Google2FA()));
    }

    /** Un utilisateur en memoire, sans base. */
    private function utilisateur(array $attributs = [], array $roles = []): User
    {
        $u = new User();
        $u->forceFill(array_merge([
            'id' => 1,
            'double_auth_secret' => null,
            'double_auth_confirme_le' => null,
        ], $attributs));

        // La relation `roles` est resolue sans base : on la pose directement.
        $u->setRelation('roles', collect(array_map(
            fn (string $nom) => new \Spatie\Permission\Models\Role(['name' => $nom]),
            $roles,
        )));

        return $u;
    }

    private function requete(?User $utilisateur, bool $sessionMarquee = false): Request
    {
        $requete = Request::create('/esbtp/etudiants', 'GET');
        $requete->setUserResolver(fn () => $utilisateur);

        $session = $this->app['session.store'];
        $session->put(ExigerDoubleAuthentification::CLE_SESSION, $sessionMarquee ?: null);
        $requete->setLaravelSession($session);

        return $requete;
    }

    private function passe(Request $requete): bool
    {
        $suivant = fn () => response('page');
        $reponse = $this->filtre()->handle($requete, $suivant);

        return $reponse->getContent() === 'page';
    }

    public function test_un_visiteur_non_authentifie_passe(): void
    {
        // Ce filtre ne remplace pas l'authentification : il ne fait que
        // retenir une session deja authentifiee.
        $this->assertTrue($this->passe($this->requete(null)));
    }

    public function test_quelqu_un_sans_second_facteur_passe(): void
    {
        config(['securite.double_auth_roles' => '']);

        $this->assertTrue($this->passe($this->requete($this->utilisateur())));
    }

    public function test_activer_le_reglage_n_enferme_personne_dehors(): void
    {
        // LA verification qui rend ce deploiement acceptable. L'ecole exige le
        // second facteur pour superAdmin, mais cette personne ne l'a jamais
        // mis en place : elle doit continuer a travailler.
        config(['securite.double_auth_roles' => 'superAdmin']);

        $sansSecondFacteur = $this->utilisateur([], ['superAdmin']);

        $this->assertTrue($this->passe($this->requete($sansSecondFacteur)));
    }

    public function test_quelqu_un_qui_a_confirme_est_retenu(): void
    {
        config(['securite.double_auth_roles' => 'superAdmin']);

        $confirme = $this->utilisateur([
            'double_auth_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ',
            'double_auth_confirme_le' => now(),
        ], ['superAdmin']);

        $this->assertFalse($this->passe($this->requete($confirme)));
    }

    public function test_une_fois_le_code_presente_la_session_passe(): void
    {
        config(['securite.double_auth_roles' => 'superAdmin']);

        $confirme = $this->utilisateur([
            'double_auth_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ',
            'double_auth_confirme_le' => now(),
        ], ['superAdmin']);

        $this->assertTrue($this->passe($this->requete($confirme, sessionMarquee: true)));
    }

    public function test_un_role_hors_du_reglage_n_est_pas_concerne(): void
    {
        // Une ecole veut proteger sa direction et sa comptabilite sans imposer
        // un telephone a deux mille etudiants, dont beaucoup se connectent
        // depuis un appareil partage.
        config(['securite.double_auth_roles' => 'superAdmin,comptable']);

        $etudiant = $this->utilisateur([
            'double_auth_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ',
            'double_auth_confirme_le' => now(),
        ], ['etudiant']);

        $this->assertTrue($this->passe($this->requete($etudiant)));
    }

    public function test_la_redirection_mene_a_l_ecran_de_saisie(): void
    {
        config(['securite.double_auth_roles' => 'superAdmin']);

        $confirme = $this->utilisateur([
            'double_auth_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ',
            'double_auth_confirme_le' => now(),
        ], ['superAdmin']);

        $reponse = $this->filtre()->handle($this->requete($confirme), fn () => response('page'));

        $this->assertSame(302, $reponse->getStatusCode());
        $this->assertStringContainsString('double-authentification/verification', $reponse->headers->get('Location'));
    }

    public function test_une_requete_ajax_recoit_un_code_et_non_une_page_de_connexion(): void
    {
        // Sans cela, un appel AJAX recevrait le HTML de l'ecran de saisie et
        // l'afficherait dans un conteneur prevu pour du JSON — ce qui se lit
        // comme une panne.
        config(['securite.double_auth_roles' => 'superAdmin']);

        $confirme = $this->utilisateur([
            'double_auth_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ',
            'double_auth_confirme_le' => now(),
        ], ['superAdmin']);

        $requete = $this->requete($confirme);
        $requete->headers->set('Accept', 'application/json');

        $reponse = $this->filtre()->handle($requete, fn () => response('page'));

        $this->assertSame(423, $reponse->getStatusCode());
        $this->assertStringContainsString('double-authentification', $reponse->getContent());
    }

    public function test_le_secret_seul_sans_confirmation_ne_retient_pas(): void
    {
        // Quelqu'un qui a genere un secret mais ne l'a jamais essaye depuis
        // son telephone : s'il etait retenu ici, il serait enferme dehors avec
        // un secret qu'il ne peut pas produire.
        config(['securite.double_auth_roles' => 'superAdmin']);

        $aMiChemin = $this->utilisateur([
            'double_auth_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ',
            'double_auth_confirme_le' => null,
        ], ['superAdmin']);

        $this->assertTrue($this->passe($this->requete($aMiChemin)));
    }
}
