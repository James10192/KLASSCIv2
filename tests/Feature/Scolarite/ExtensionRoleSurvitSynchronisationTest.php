<?php

namespace Tests\Feature\Scolarite;

use App\Services\ExtensionsDeRole;
use App\Services\PermissionRegistry;
use App\Services\PermissionSyncService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * « Chez nous, la scolarite edite les fiches et valide les inscriptions. »
 *
 * Cette phrase est une decision d'organisation, pas une derive. Le nettoyage
 * qui suit chaque deploiement ramene pourtant cinq roles d'organigramme a leur
 * paquet d'usine — et effacerait la decision sans rien dire, si elle n'etait
 * pas inscrite.
 *
 * Ce test fige les deux moities de la regle sur le cas reel qui l'a motivee :
 * ISLG, ou le service scolarite a recu `students.edit` et
 * `inscriptions.validate`. Inscrite, la decision survit ; non inscrite, elle
 * disparait — et c'est voulu, sinon une permission restee la par accident
 * deviendrait definitive.
 */
class ExtensionRoleSurvitSynchronisationTest extends TestCase
{
    use DatabaseTransactions;

    private const DECISION_ISLG = ['students.edit', 'inscriptions.validate'];

    public function test_une_extension_inscrite_survit_a_la_synchronisation(): void
    {
        $role = $this->serviceScolariteEtendu();

        app(ExtensionsDeRole::class)->enregistrer(
            'serviceScolarite',
            $role->permissions()->pluck('name')->all(),
            'ISLG : la scolarite edite et valide.'
        );

        app(PermissionSyncService::class)->run();

        $role = Role::findByName('serviceScolarite', 'web');
        foreach (self::DECISION_ISLG as $droit) {
            $this->assertTrue(
                $role->hasPermissionTo($droit),
                "« {$droit} » a ete accorde deliberement : la synchronisation ne doit pas le reprendre."
            );
        }
    }

    public function test_une_permission_non_inscrite_est_traitee_comme_une_derive(): void
    {
        $this->serviceScolariteEtendu();

        app(PermissionSyncService::class)->run();

        $role = Role::findByName('serviceScolarite', 'web');
        foreach (self::DECISION_ISLG as $droit) {
            $this->assertFalse(
                $role->hasPermissionTo($droit),
                "« {$droit} » n'a pas ete inscrit : le nettoyage doit le reprendre, sinon un oubli devient definitif."
            );
        }
    }

    public function test_le_paquet_d_usine_du_role_reste_intact(): void
    {
        $this->serviceScolariteEtendu();

        app(PermissionSyncService::class)->run();

        $role = Role::findByName('serviceScolarite', 'web');
        foreach (['documents.print', 'students.view', 'notes.create'] as $droit) {
            $this->assertTrue(
                $role->hasPermissionTo($droit),
                "« {$droit} » est un defaut du role : le nettoyage ne doit pas y toucher."
            );
        }
    }

    /**
     * Le role tel qu'ISLG l'a pose : son paquet d'usine, plus la decision locale.
     */
    private function serviceScolariteEtendu(): Role
    {
        $registry = app(PermissionRegistry::class);
        $role = Role::findOrCreate('serviceScolarite', 'web');

        $droits = array_merge($registry->defaultPermissionsFor('serviceScolarite'), self::DECISION_ISLG);
        foreach ($droits as $droit) {
            Permission::findOrCreate($droit, 'web');
        }
        $role->syncPermissions($droits);

        return $role->fresh();
    }
}
