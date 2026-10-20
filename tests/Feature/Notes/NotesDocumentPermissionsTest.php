<?php

namespace Tests\Feature\Notes;

use App\Models\ESBTPEvaluation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotesDocumentPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_consulting_notes_does_not_grant_completed_grade_sheet_pdf_export(): void
    {
        Role::findOrCreate('superAdmin', 'web');
        User::factory()->create()->assignRole('superAdmin');

        Permission::findOrCreate('module.notes_evaluations.access', 'web');
        Permission::findOrCreate('notes.view', 'web');
        Permission::findOrCreate('evaluations.notes.export', 'web');

        $reader = User::factory()->create();
        $reader->givePermissionTo('module.notes_evaluations.access');
        $reader->givePermissionTo('notes.view');
        $this->actingAs($reader);

        $evaluation = ESBTPEvaluation::factory()->create();

        $this->get(route('esbtp.evaluations.pdf-preview', $evaluation))->assertForbidden();
        $this->get(route('esbtp.evaluations.pdf', $evaluation))->assertForbidden();
    }
}
