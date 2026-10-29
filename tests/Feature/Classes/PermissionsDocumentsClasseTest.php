<?php

namespace Tests\Feature\Classes;

use App\Exports\ClassesExport;
use App\Helpers\InstallationHelper;
use App\Models\ESBTPClasse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PermissionsDocumentsClasseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        foreach ([
            'classes.view', 'classes.students.view',
            'classes.liste_appel.view', 'classes.liste_appel.export',
            'classes.liste_complete.view', 'classes.liste_complete.export',
            'classes.export', 'bulletins.generate', 'identity.teach', 'admin.access',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        InstallationHelper::flushCachedStatus();
    }

    public function test_une_permission_de_lecture_de_classe_ne_donne_pas_automatiquement_acces_aux_documents(): void
    {
        $classe = ESBTPClasse::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo('classes.view');

        foreach ([
            'esbtp.classes.liste-appel',
            'esbtp.classes.liste-appel.pdf',
            'esbtp.classes.liste-complete',
            'esbtp.classes.liste-complete.pdf',
            'esbtp.classes.liste-complete.excel',
            'esbtp.classes.student-table-html',
        ] as $route) {
            $this->actingAs($user)->get(route($route, $classe))->assertForbidden();
        }
    }

    public function test_le_droit_de_voir_la_liste_d_appel_n_autorise_pas_son_export_pdf(): void
    {
        $classe = ESBTPClasse::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo('classes.view', 'classes.liste_appel.view');

        $this->actingAs($user)
            ->get(route('esbtp.classes.liste-appel.pdf', $classe))
            ->assertForbidden();
    }

    public function test_un_droit_sur_une_liste_ne_donne_pas_l_autre_liste(): void
    {
        $classe = ESBTPClasse::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo('classes.view', 'classes.liste_complete.view');

        $this->actingAs($user)
            ->get(route('esbtp.classes.liste-appel', $classe))
            ->assertForbidden();
    }

    public function test_export_des_classes_ne_depend_pas_de_classes_view(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('classes.view');

        foreach (['excel', 'csv', 'pdf'] as $format) {
            $this->actingAs($user)
                ->get(route('esbtp.classes.export.'.$format))
                ->assertForbidden();
        }
    }

    public function test_le_selecteur_etudiants_des_bulletins_garde_son_droit_d_acces(): void
    {
        $classe = ESBTPClasse::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo('bulletins.generate');

        $this->actingAs($user)
            ->getJson(route('esbtp.classes.etudiants', $classe))
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_api_generale_des_classes_est_refusee_sans_permission_view(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/classes')
            ->assertForbidden();
    }

    public function test_admin_access_seul_ne_permet_pas_a_un_enseignant_de_contourner_ses_classes(): void
    {
        $classe = ESBTPClasse::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo(
            'identity.teach', 'admin.access', 'classes.view', 'classes.liste_appel.view'
        );

        // Aucun profil/séance affectée : même si le rôle enseignant possède
        // admin.access pour ouvrir le back-office, la classe reste hors périmètre.
        $this->actingAs($user)
            ->get(route('esbtp.classes.liste-appel', $classe))
            ->assertNotFound();
    }

    public function test_mappage_excel_sans_requete_par_classe(): void
    {
        $classes = collect();
        foreach ([17, 3, 0] as $effectif) {
            $classe = new ESBTPClasse([
                'name' => 'Classe test', 'code' => 'TST', 'places_totales' => 30,
            ]);
            $classe->setAttribute('effectif_export', $effectif);
            $classe->setRelation('filiere', null);
            $classe->setRelation('niveau', null);
            $classes->push($classe);
        }

        $export = new ClassesExport($classes, null, [], ['nom' => 'KLASSCI']);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $rows = $classes->map(fn ($classe) => $export->map($classe));
        $this->assertSame([17, 3, 0], $rows->pluck(5)->all());
        $this->assertSame([], DB::getQueryLog(), 'Un mapping Excel ne doit déclencher aucune requête SQL.');
    }
}
