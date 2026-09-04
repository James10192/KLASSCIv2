<?php

namespace Tests\Unit\Permissions;

use App\Services\PermissionRegistry;
use App\Services\PermissionSyncService;
use PHPUnit\Framework\TestCase;

/**
 * « Régénérer les frais » réécrit ce que l'école réclame aux familles, parfois
 * sur une année entière. Ce droit s'est détaché de `inscriptions.edit`.
 *
 * Une garde déplacée et pas les autres, c'est soit un bouton qui mène à un 403,
 * soit une route ouverte derrière un bouton caché. Ce test les tient ensemble.
 */
class DroitRegenererLesFraisTest extends TestCase
{
    private const DROIT = 'frais.regenerate';

    /** @return array<string, mixed> */
    private function registre(): array
    {
        return require __DIR__ . '/../../../config/permissions.php';
    }

    private function lire(string $chemin): string
    {
        return file_get_contents(__DIR__ . '/../../../' . $chemin);
    }

    public function test_le_droit_existe_dans_le_registre_avec_un_libelle_lisible(): void
    {
        $permissions = $this->registre()['permissions'];

        $this->assertArrayHasKey(self::DROIT, $permissions);
        $this->assertNotEmpty($permissions[self::DROIT]['label']);
        $this->assertSame('Frais', $permissions[self::DROIT]['group']);
    }

    public function test_seule_la_comptabilite_le_recoit_par_defaut(): void
    {
        $defauts = $this->registre()['role_defaults'];

        $this->assertContains(self::DROIT, $defauts['comptable']);

        // superAdmin et serviceTechnique passent par Gate::before ('*').
        // Tous les autres rôles d'organigramme ne l'ont pas : c'est l'école qui
        // décide, depuis /esbtp/custom-roles, si sa scolarité doit l'avoir.
        foreach (['secretaire', 'caissier', 'coordinateur', 'directeurEtudes',
                  'responsableScolarite', 'serviceScolarite', 'agentInscription',
                  'chargeCommunication', 'enseignant', 'etudiant'] as $role) {
            $this->assertNotContains(
                self::DROIT,
                $defauts[$role],
                "Le rôle {$role} ne doit pas retarifer une promotion par défaut."
            );
        }
    }

    public function test_la_synchronisation_le_distribue_aux_roles_deja_peuples(): void
    {
        // Sans cette entrée, `permissions:fix` annonce un succès et n'accorde
        // rien : la ligne 62 du service préserve tout rôle non vide, et sur les
        // instances en service la comptabilité l'est. Le bouton disparaîtrait
        // pour tout le monde, superAdmin excepté.
        $service = new PermissionSyncService(new PermissionRegistry());
        $methode = new \ReflectionMethod($service, 'newFeaturePermissions');
        $methode->setAccessible(true);

        $this->assertContains(self::DROIT, $methode->invoke($service));
    }

    public function test_les_quatre_gardes_designent_le_meme_droit(): void
    {
        $gardes = [
            'routes/web.php' => "'permission:frais.regenerate'",
            'app/Http/Controllers/ESBTP/CompleterFraisManquantsController.php' => "middleware('permission:frais.regenerate')",
            'resources/views/esbtp/inscriptions/show.blade.php' => "@can('frais.regenerate')",
        ];

        foreach ($gardes as $chemin => $attendu) {
            $this->assertStringContainsString(
                $attendu,
                $this->lire($chemin),
                "La garde de {$chemin} n'a pas suivi le déplacement du droit."
            );
        }

        // La liste porte deux entrées : le menu du hero et la barre de sélection.
        $this->assertSame(
            2,
            substr_count($this->lire('resources/views/esbtp/inscriptions/index.blade.php'), "@can('frais.regenerate')"),
            "Les deux points d'entrée de la liste doivent porter la même garde."
        );
    }

    public function test_aucune_garde_ne_reste_sur_l_ancien_droit(): void
    {
        // `inscriptions.edit` reste légitime ailleurs dans ces fichiers ; ce
        // qu'on vérifie, c'est qu'aucune route ni aucun bouton de régénération
        // ne s'y rattache encore.
        $routes = $this->lire('routes/web.php');
        $this->assertStringNotContainsString(
            "Route::middleware(['permission:inscriptions.edit', 'throttle:20,1'])",
            $routes,
            'Le groupe de routes frais-manquants doit porter frais.regenerate.'
        );
    }
}
