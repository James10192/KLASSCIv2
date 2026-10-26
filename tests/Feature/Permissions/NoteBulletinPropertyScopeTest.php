<?php

namespace Tests\Feature\Permissions;

use App\Models\ESBTPBulletin;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use App\Models\User;
use App\Policies\ESBTPBulletinPolicy;
use App\Policies\ESBTPNotePolicy;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class NoteBulletinPropertyScopeTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['enseignant', 'etudiant', 'coordinateur'] as $name) {
            Role::findOrCreate($name, 'web');
        }
        foreach (['notes.view', 'notes.view_own', 'notes.edit', 'bulletins.view', 'bulletins.view_own'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_un_enseignant_proprietaire_sans_permission_ne_peut_pas_modifier_la_note(): void
    {
        $teacher = User::factory()->create(['is_active' => true]);
        $teacher->assignRole('enseignant');
        $evaluation = new ESBTPEvaluation();
        $evaluation->enseignant_id = $teacher->id;
        $note = new ESBTPNote();
        $note->setRelation('evaluation', $evaluation);

        $policy = app(ESBTPNotePolicy::class);
        $this->assertFalse($policy->view($teacher, $note));
        $this->assertFalse($policy->update($teacher, $note));

        $teacher->givePermissionTo(['notes.view', 'notes.edit']);
        $this->assertTrue($policy->view($teacher, $note));
        $this->assertTrue($policy->update($teacher, $note));

        $teacher->revokePermissionTo('notes.edit');
        $this->assertFalse($policy->update($teacher, $note));
    }

    public function test_un_enseignant_ne_peut_pas_modifier_la_note_du_collegue(): void
    {
        $teacher = User::factory()->create(['is_active' => true]);
        $teacher->assignRole('enseignant');
        $teacher->givePermissionTo('notes.edit');

        $evaluation = new ESBTPEvaluation();
        $evaluation->enseignant_id = $teacher->id + 1;
        $note = new ESBTPNote();
        $note->setRelation('evaluation', $evaluation);

        $this->assertFalse(app(ESBTPNotePolicy::class)->update($teacher, $note));
    }

    public function test_un_coordinateur_avec_droit_explicite_peut_modifier_sans_bypass_role(): void
    {
        $coordinator = User::factory()->create(['is_active' => true]);
        $coordinator->assignRole('coordinateur');
        $note = new ESBTPNote();
        $policy = app(ESBTPNotePolicy::class);

        $this->assertFalse($policy->update($coordinator, $note));
        $coordinator->givePermissionTo('notes.edit');
        $this->assertTrue($policy->update($coordinator, $note));
    }

    public function test_notes_personnelles_ne_sont_pas_visibles_sans_permission(): void
    {
        $student = User::factory()->create(['is_active' => true]);
        $student->assignRole('etudiant');
        $record = new ESBTPEtudiant();
        $record->user_id = $student->id;
        $note = new ESBTPNote();
        $note->setRelation('etudiant', $record);

        $policy = app(ESBTPNotePolicy::class);
        $this->assertFalse($policy->view($student, $note));
        $student->givePermissionTo('notes.view_own');
        $this->assertTrue($policy->view($student, $note));
    }

    public function test_bulletin_view_own_est_restreint_au_titulaire_meme_sans_role_etudiant(): void
    {
        $viewer = User::factory()->create(['is_active' => true]);
        $viewer->givePermissionTo('bulletins.view_own');

        $student = User::factory()->create(['is_active' => true]);
        $record = new ESBTPEtudiant();
        $record->user_id = $student->id;
        $bulletin = new ESBTPBulletin();
        $bulletin->setRelation('etudiant', $record);

        $policy = app(ESBTPBulletinPolicy::class);
        $this->assertFalse($policy->view($viewer, $bulletin));
        $viewer->givePermissionTo('bulletins.view');
        $this->assertTrue($policy->view($viewer, $bulletin));
        $viewer->revokePermissionTo('bulletins.view');
        $this->assertFalse($policy->view($viewer, $bulletin));

        $record->user_id = $viewer->id;
        $this->assertTrue($policy->view($viewer, $bulletin));
    }
}
