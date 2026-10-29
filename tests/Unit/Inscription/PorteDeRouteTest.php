<?php

namespace Tests\Unit\Inscription;

use App\Support\PorteDeRoute;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Lire la porte d'une route plutot que recopier ses permissions.
 *
 * Deux surfaces prejugent d'une autorisation avant de l'appliquer : la
 * redirection qui suit l'acceptation d'une candidature, et le bouton « Creer
 * l'inscription » de la corbeille. Se tromper la ne rend pas la mauvaise
 * reponse — cela affiche un chemin qui se termine par un 403, a un agent qui
 * n'a fait que suivre ce qu'on lui montrait.
 *
 * La recopie a deja echoue une fois exactement ainsi : le controleur avait ete
 * corrige, la vue non. Ce test fige la lecture, y compris ses refus — un
 * middleware illisible doit fermer la porte, jamais l'ouvrir au hasard.
 *
 * Sans base : on enregistre des routes jetables et on presente un utilisateur
 * factice qui ne sait que deux choses, ses permissions et ses roles.
 */
class PorteDeRouteTest extends TestCase
{
    /** Un porteur de permissions et de roles, sans base ni conteneur. */
    private function porteur(array $permissions, array $roles = []): Authorizable
    {
        return new class($permissions, $roles) implements Authorizable
        {
            /**
             * @param  list<string>  $permissions
             * @param  list<string>  $roles
             */
            public function __construct(private array $permissions, private array $roles) {}

            public function can($abilities, $arguments = [])
            {
                return in_array($abilities, $this->permissions, true);
            }

            public function cant($abilities, $arguments = [])
            {
                return ! $this->can($abilities, $arguments);
            }

            public function cannot($abilities, $arguments = [])
            {
                return $this->cant($abilities, $arguments);
            }

            public function hasRole($role): bool
            {
                return in_array($role, $this->roles, true);
            }
        };
    }

    private function utilisateur(string ...$permissions): Authorizable
    {
        return $this->porteur($permissions);
    }

    private function porte(string $nom, array $middlewares): void
    {
        Route::middleware($middlewares)
            ->get('/porte-de-test/'.$nom, static fn () => '')
            ->name($nom);

        Route::getRoutes()->refreshNameLookups();
    }

    /**
     * Deux middlewares veulent dire « les deux », un `|` veut dire « l'une ».
     *
     * C'est toute la regle, et c'est celle que la recopie manquait : la vue
     * n'exigeait que `inscriptions.create` quand la route exige AUSSI l'une
     * des permissions d'identite.
     */
    public function test_deux_middlewares_exigent_les_deux(): void
    {
        $this->porte('porte.double', [
            'permission:admin.access|identity.registrar',
            'permission:inscriptions.create',
        ]);

        $this->assertTrue(PorteDeRoute::ouverte('porte.double', $this->utilisateur('admin.access', 'inscriptions.create')));
        $this->assertTrue(PorteDeRoute::ouverte('porte.double', $this->utilisateur('identity.registrar', 'inscriptions.create')));

        // L'agent taille sur mesure : il cree des inscriptions, mais ne porte
        // aucune permission d'identite. C'est lui qui recevait le 403.
        $this->assertFalse(PorteDeRoute::ouverte('porte.double', $this->utilisateur('inscriptions.create')));
        $this->assertFalse(PorteDeRoute::ouverte('porte.double', $this->utilisateur('admin.access')));
    }

    /** Les gardes qui ne dependent pas d'une autorisation ne comptent pas. */
    public function test_auth_et_paywall_sont_ignores(): void
    {
        $this->porte('porte.gardes', ['auth', 'paywall', 'throttle:5,1', 'permission:inscriptions.create']);

        $this->assertTrue(PorteDeRoute::ouverte('porte.gardes', $this->utilisateur('inscriptions.create')));
    }

    /** `can:` sans modele se lit comme une permission. */
    public function test_le_middleware_can_est_lu(): void
    {
        $this->porte('porte.can', ['can:inscriptions.create']);

        $this->assertTrue(PorteDeRoute::ouverte('porte.can', $this->utilisateur('inscriptions.create')));
        $this->assertFalse(PorteDeRoute::ouverte('porte.can', $this->utilisateur('autre.chose')));
    }

    /**
     * `role:` se lit aussi — y compris quand il vient du constructeur d'un
     * controleur. Un coordinateur portait toutes les permissions du formulaire
     * secretaire et recevait 403 : le controleur exigeait `role:superAdmin`, et
     * la premiere lecture ne le voyait pas.
     */
    public function test_le_middleware_role_est_lu(): void
    {
        $this->porte('porte.role', ['permission:admin.access', 'role:superAdmin|serviceTechnique']);

        $this->assertTrue(PorteDeRoute::ouverte('porte.role', $this->porteur(['admin.access'], ['superAdmin'])));
        $this->assertTrue(PorteDeRoute::ouverte('porte.role', $this->porteur(['admin.access'], ['serviceTechnique'])));
        $this->assertFalse(PorteDeRoute::ouverte('porte.role', $this->porteur(['admin.access'], ['coordinateur'])));
        $this->assertFalse(PorteDeRoute::ouverte('porte.role', $this->porteur([], ['superAdmin'])));
    }

    /** `role_or_permission:` : l'un ou l'autre suffit. */
    public function test_le_middleware_role_ou_permission_est_lu(): void
    {
        $this->porte('porte.mixte', ['role_or_permission:superAdmin|personnel.manage']);

        $this->assertTrue(PorteDeRoute::ouverte('porte.mixte', $this->porteur([], ['superAdmin'])));
        $this->assertTrue(PorteDeRoute::ouverte('porte.mixte', $this->porteur(['personnel.manage'], [])));
        $this->assertFalse(PorteDeRoute::ouverte('porte.mixte', $this->porteur(['autre.chose'], ['coordinateur'])));
    }

    /**
     * Une porte illisible est fermee, jamais ouverte.
     *
     * Quatre formes qu'on ne sait pas evaluer : un `can:` avec modele, un
     * `role:` avec garde, une route sans aucune exigence lisible — gardee par
     * une policy ou par rien, les deux se ressemblent d'ici — et une route qui
     * n'existe pas. `ouverte()` repond « non » ; `verdict()` dit « je ne sais
     * pas », pour que l'appelant distingue un refus d'une ignorance.
     */
    public function test_une_porte_illisible_reste_fermee(): void
    {
        $tout = $this->porteur(['admin.access', 'inscriptions.create', 'posts.update'], ['superAdmin']);

        $this->porte('porte.modele', ['can:update,post']);
        $this->assertFalse(PorteDeRoute::ouverte('porte.modele', $tout));
        $this->assertNull(PorteDeRoute::verdict('porte.modele', $tout));

        $this->porte('porte.role.garde', ['role:superAdmin,web']);
        $this->assertFalse(PorteDeRoute::ouverte('porte.role.garde', $tout));
        $this->assertNull(PorteDeRoute::verdict('porte.role.garde', $tout));

        $this->porte('porte.nue', ['auth']);
        $this->assertFalse(PorteDeRoute::ouverte('porte.nue', $tout));
        $this->assertNull(PorteDeRoute::verdict('porte.nue', $tout));

        $this->assertFalse(PorteDeRoute::ouverte('porte.qui.nexiste.pas', $tout));
        $this->assertNull(PorteDeRoute::verdict('porte.qui.nexiste.pas', $tout));
    }

    /** Un refus lisible est un refus, pas une ignorance. */
    public function test_le_verdict_distingue_le_refus_de_l_ignorance(): void
    {
        $this->porte('porte.refus', ['permission:inscriptions.create']);

        $this->assertFalse(PorteDeRoute::verdict('porte.refus', $this->utilisateur('autre.chose')));
        $this->assertTrue(PorteDeRoute::verdict('porte.refus', $this->utilisateur('inscriptions.create')));
    }

    /** Personne n'est personne : un visiteur anonyme ne franchit rien. */
    public function test_sans_utilisateur_la_porte_est_fermee(): void
    {
        $this->porte('porte.anonyme', ['permission:inscriptions.create']);

        $this->assertFalse(PorteDeRoute::ouverte('porte.anonyme', null));
        $this->assertFalse(PorteDeRoute::verdict('porte.anonyme', null));
    }
}
