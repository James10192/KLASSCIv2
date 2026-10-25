<?php

namespace Tests\Feature\Enseignant;

use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\PaywallMiddleware;
use App\Models\ESBTPEvaluation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Régression : le garde précédent omettait le contrôle de propriété quand
 * identity.teach OU notes.manage_own manquait, laissant une écriture ouverte.
 */
final class TeacherEvaluationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            CheckInstalled::class, EnsureInstalled::class,
            PaywallMiddleware::class, ForcePasswordChange::class,
        ]);
        Role::findOrCreate('enseignant', 'web');
        Permission::findOrCreate('identity.teach', 'web');
        Permission::findOrCreate('notes.manage_own', 'web');
    }

    private function utilisateur(bool $droit = false): User
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $user->assignRole('enseignant');
        $user->givePermissionTo('identity.teach');
        if ($droit) {
            $user->givePermissionTo('notes.manage_own');
        }

        return $user;
    }

    public function test_enseignant_sans_droit_de_gestion_est_refuse_sur_ses_propres_notes(): void
    {
        $actor = $this->utilisateur();
        $evaluation = ESBTPEvaluation::factory()->create(['created_by' => $actor->id]);

        $this->actingAs($actor)->get(route('teacher.grades.note-modal', $evaluation))->assertForbidden();
        $this->actingAs($actor)->postJson(route('teacher.grades.note-store', $evaluation), [
            'etudiant_id' => 1, 'note' => 14,
        ])->assertForbidden();
    }

    public function test_le_droit_ne_donne_pas_acces_aux_notes_d_autrui(): void
    {
        $actor = $this->utilisateur(true);
        $owner = $this->utilisateur(true);
        $evaluation = ESBTPEvaluation::factory()->create([
            'created_by' => $owner->id, 'enseignant_id' => $owner->id,
        ]);

        $this->actingAs($actor)->get(route('teacher.grades.note-modal', $evaluation))->assertForbidden();
        $this->actingAs($actor)->postJson(route('teacher.grades.note-store', $evaluation), [
            'etudiant_id' => 1, 'note' => 14,
        ])->assertForbidden();
    }
}
