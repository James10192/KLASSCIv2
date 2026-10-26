<?php

namespace Tests\Feature\Permissions;

use App\Models\ESBTPBulletin;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use App\Models\User;
use App\Policies\ESBTPBulletinPolicy;
use App\Policies\ESBTPInscriptionPolicy;
use App\Policies\ESBTPPaiementPolicy;
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
        foreach (['notes.view', 'notes.view_all', 'notes.view_own', 'notes.edit', 'bulletins.view', 'bulletins.view_own'] as $name) {
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

    public function test_un_compte_etudiant_et_financier_conserve_les_permissions_globales(): void
    {
        Role::findOrCreate('comptable', 'web');
        foreach (['inscriptions.view', 'paiements.view'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $actor = User::factory()->create(['is_active' => true]);
        $actor->assignRole(['etudiant', 'comptable']);
        $inscription = new ESBTPInscription();
        $paiement = new ESBTPPaiement();

        $inscriptions = app(ESBTPInscriptionPolicy::class);
        $paiements = app(ESBTPPaiementPolicy::class);

        $this->assertFalse($inscriptions->view($actor, $inscription));
        $this->assertFalse($paiements->view($actor, $paiement));

        $actor->givePermissionTo(['inscriptions.view', 'paiements.view']);
        $this->assertTrue($inscriptions->view($actor, $inscription));
        $this->assertTrue($paiements->view($actor, $paiement));

        $actor->revokePermissionTo(['inscriptions.view', 'paiements.view']);
        $this->assertFalse($inscriptions->view($actor, $inscription));
        $this->assertFalse($paiements->view($actor, $paiement));
    }

    public function test_etudiant_multi_fonctions_conserve_notes_view_global_et_revocation(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        $actor->assignRole(['etudiant', 'coordinateur']);
        $autre = User::factory()->create(['is_active' => true]);
        $etudiant = new ESBTPEtudiant();
        $etudiant->user_id = $autre->id;
        $note = new ESBTPNote();
        $note->setRelation('etudiant', $etudiant);
        $policy = app(ESBTPNotePolicy::class);

        $this->assertFalse($policy->view($actor, $note));
        $actor->givePermissionTo('notes.view');
        $this->assertTrue($policy->view($actor, $note));
        $actor->revokePermissionTo('notes.view');
        $this->assertFalse($policy->view($actor, $note));
    }

    public function test_enseignant_coordinateur_heritant_de_la_permission_globale_par_role(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        $actor->assignRole(['enseignant', 'coordinateur']);
        $actor->givePermissionTo('notes.view');
        $role = Role::findByName('coordinateur', 'web');
        $role->givePermissionTo('notes.view_all');

        $evaluation = new ESBTPEvaluation();
        $evaluation->enseignant_id = $actor->id + 1;
        $note = new ESBTPNote();
        $note->setRelation('evaluation', $evaluation);
        $policy = app(ESBTPNotePolicy::class);

        $this->assertTrue($policy->view($actor, $note));
        $role->revokePermissionTo('notes.view_all');
        $actor->unsetRelation('roles');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->assertFalse($policy->view($actor, $note));
    }

    public function test_compte_enseignant_et_etudiant_conserve_la_portee_de_ses_evaluations(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        $actor->assignRole(['enseignant', 'etudiant']);
        $actor->givePermissionTo('notes.view');
        $evaluation = new ESBTPEvaluation();
        $evaluation->enseignant_id = $actor->id;
        $note = new ESBTPNote();
        $note->setRelation('evaluation', $evaluation);
        $policy = app(ESBTPNotePolicy::class);

        $this->assertTrue($policy->view($actor, $note));
        $evaluation->enseignant_id = $actor->id + 1;
        $this->assertFalse($policy->view($actor, $note));
    }
}
