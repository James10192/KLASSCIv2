<?php

namespace Tests\Feature\Permissions;

use App\Http\Controllers\ESBTP\BtsStudentTranscriptController;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEtudiant;
use App\Models\User;
use App\Services\ESBTP\BtsStudentAcademicRecords;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class BtsReleveAccesCiblesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::findOrCreate('bts.releve.view', 'web');
        Permission::findOrCreate('bts.releve.export', 'web');
    }

    public function test_la_permission_de_previsualiser_ne_permet_pas_de_telecharger(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('bts.releve.view');

        $this->assertDenied($user, 'download');
    }

    public function test_la_permission_d_export_ne_donne_pas_automatiquement_la_previsualisation(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('bts.releve.export');

        $this->assertDenied($user, 'preview');
    }

    private function assertDenied(User $user, string $action): void
    {
        $this->actingAs($user);
        $request = Request::create('/esbtp/bts/releves/1/1/'.($action === 'download' ? 'telecharger' : 'apercu'));
        $request->setUserResolver(fn () => $user);

        try {
            app(BtsStudentTranscriptController::class)->{$action}(
                $request,
                new ESBTPEtudiant(),
                new ESBTPAnneeUniversitaire(),
                app(BtsStudentAcademicRecords::class),
            );

            $this->fail('Un droit BTS ciblé ne doit pas ouvrir une action distincte.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }
}
