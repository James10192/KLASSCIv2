<?php

namespace Tests\Feature\Permissions;

use App\Models\ESBTPBulletin;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPSeanceCours;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Vérifie le Gate réel de chaque Policy, pas seulement une méthode isolée.
 * Un rôle protégé identifie un compte, sans pouvoir d'autorisation implicite.
 */
final class PoliciesPermissionEffectiveTest extends TestCase
{
    use DatabaseTransactions;

    public function test_superadmin_sans_permission_ne_peut_pas_lire_les_six_domaines(): void
    {
        Role::findOrCreate('superAdmin', 'web');
        $actor = User::factory()->create(['is_active' => true]);
        $actor->assignRole('superAdmin');

        foreach ($this->domains() as [$model, $permission]) {
            Permission::findOrCreate($permission, 'web');
            $this->assertFalse(
                Gate::forUser($actor)->allows('viewAny', $model),
                "{$model} a autorisé superAdmin sans {$permission}"
            );
        }
    }

    public function test_permission_directe_autorise_un_compte_ordinaire_puis_sa_revocation_refuse(): void
    {
        $actor = User::factory()->create(['is_active' => true]);

        foreach ($this->domains() as [$model, $permission]) {
            Permission::findOrCreate($permission, 'web');
            $actor->givePermissionTo($permission);
            $this->assertTrue(Gate::forUser($actor)->allows('viewAny', $model), "Le droit {$permission} doit fonctionner sans rôle privilégié.");

            $actor->revokePermissionTo($permission);
            $actor->unsetRelation('permissions');
            $this->assertFalse(Gate::forUser($actor)->allows('viewAny', $model), "La révocation {$permission} doit être immédiate.");
        }
    }

    /**
     * @return array<int,array{class-string,string}>
     */
    private function domains(): array
    {
        return [
            [ESBTPNote::class, 'module.notes_evaluations.access'],
            [ESBTPInscription::class, 'inscriptions.view'],
            [ESBTPPaiement::class, 'paiements.view'],
            [ESBTPBulletin::class, 'bulletins.view'],
            [ESBTPMatiere::class, 'matieres.view'],
            [ESBTPSeanceCours::class, 'timetables.view'],
        ];
    }
}
