<?php

namespace Tests\Feature\Reports;

use App\Domain\Exports\Reports\ClasseEffectifsReport;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\User;
use App\Services\Reports\ClasseEffectifsReportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ClasseEffectifsReportTest extends TestCase
{
    use DatabaseTransactions;

    private ESBTPAnneeUniversitaire $annee;
    private ESBTPAnneeUniversitaire $autreAnnee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
            \App\Http\Middleware\ForcePasswordChange::class,
        ]);

        Permission::findOrCreate('reports.view', 'web');
        Permission::findOrCreate('reports.generate', 'web');
        ESBTPAnneeUniversitaire::query()->update(['is_current' => false]);
        $this->annee = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2097-2098', 'start_date' => '2097-09-01', 'end_date' => '2098-07-31', 'is_current' => true,
        ]);
        $this->autreAnnee = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2098-2099', 'start_date' => '2098-09-01', 'end_date' => '2099-07-31', 'is_current' => false,
        ]);
    }

    /** @return array<string, mixed> */
    private function filters(array $changes = []): array
    {
        return array_replace([
            'annee_universitaire_id' => $this->annee->id,
            'min_inscrits' => 0,
            'nombre_classes' => 'all',
            'sort' => 'effectif_desc',
            'filiere_id' => null,
            'niveau_id' => null,
            'page' => 1,
        ], $changes);
    }

    private function build(array $changes = [], bool $paginated = false): array
    {
        return app(ClasseEffectifsReportService::class)->build($this->filters($changes), $paginated);
    }

    private function inscrire(ESBTPClasse $classe, ESBTPAnneeUniversitaire $annee, array $changes = []): ESBTPInscription
    {
        return ESBTPInscription::factory()->create(array_replace([
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
        ], $changes));
    }

    private function lecteur(bool $export = false): User
    {
        $user = User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]);
        $user->givePermissionTo($export ? ['reports.view', 'reports.generate'] : ['reports.view']);
        return $user;
    }

    public function test_l_annee_d_inscription_fait_foi_pas_l_annee_legacy_de_la_classe(): void
    {
        $classe = ESBTPClasse::factory()->create(['annee_universitaire_id' => $this->autreAnnee->id]);
        $this->inscrire($classe, $this->annee);

        $this->assertSame(1, $this->build()['rows']->firstWhere('id', $classe->id)->effectif);
        $this->assertSame(0, $this->build(['annee_universitaire_id' => $this->autreAnnee->id])['rows']->firstWhere('id', $classe->id)->effectif);
        $this->assertSame(0, $this->build(['annee_universitaire_id' => $this->autreAnnee->id, 'min_inscrits' => 1])['retained_count']);
    }

    public function test_classes_vides_et_classe_inactive_avec_historique(): void
    {
        $vide = ESBTPClasse::factory()->create();
        $archive = ESBTPClasse::factory()->create(['is_active' => false]);
        $this->inscrire($archive, $this->annee);

        $this->assertSame(0, $this->build()['rows']->firstWhere('id', $vide->id)->effectif);
        $this->assertSame(1, $this->build()['rows']->firstWhere('id', $archive->id)->effectif);
        $this->assertNull($this->build(['min_inscrits' => 1])['rows']->firstWhere('id', $vide->id));
    }

    public function test_seuil_exact_et_un_de_plus(): void
    {
        $classe = ESBTPClasse::factory()->create();
        foreach (range(1, 15) as $_) {
            $this->inscrire($classe, $this->annee);
        }
        $this->assertSame(15, $this->build(['min_inscrits' => 15])['rows']->firstWhere('id', $classe->id)->effectif);
        $this->assertSame(0, $this->build(['min_inscrits' => 16])['retained_count']);
        $this->assertTrue($this->build(['min_inscrits' => 16])['has_classes_below_threshold']);
    }

    public function test_plafond_tri_et_compteurs_hors_pagination(): void
    {
        $petite = ESBTPClasse::factory()->create(['name' => 'Z classe']);
        $grande = ESBTPClasse::factory()->create(['name' => 'A classe']);
        $this->inscrire($petite, $this->annee);
        $this->inscrire($grande, $this->annee);
        $this->inscrire($grande, $this->annee);

        $rapport = $this->build(['nombre_classes' => '10', 'min_inscrits' => 1], true);
        $this->assertSame(2, $rapport['eligible_count']);
        $this->assertSame(2, $rapport['retained_count']);
        $this->assertSame($grande->id, $rapport['rows']->first()->id);
        $this->assertSame(3, $rapport['unique_students']);

        $rapportPageVide = $this->build(['nombre_classes' => '10', 'min_inscrits' => 1, 'page' => 2], true);
        $this->assertSame(2, $rapportPageVide['retained_count']);
        $this->assertSame(3, $rapportPageVide['unique_students']);
        $this->assertCount(0, $rapportPageVide['rows']);

        $rapportParNom = $this->build(['min_inscrits' => 1, 'sort' => 'nom_asc']);
        $this->assertSame($grande->id, $rapportParNom['rows']->first()->id);
    }

    public function test_plafond_reel_et_filtres_structurels_sans_changer_les_totaux(): void
    {
        $classes = collect();
        foreach (range(1, 11) as $i) {
            $classe = ESBTPClasse::factory()->create(['name' => sprintf('Promotion %02d', $i)]);
            $this->inscrire($classe, $this->annee);
            $classes->push($classe);
        }
        $rapport = $this->build(['min_inscrits' => 1, 'nombre_classes' => '10', 'sort' => 'nom_asc']);
        $this->assertSame(11, $rapport['eligible_count']);
        $this->assertSame(10, $rapport['retained_count']);
        $this->assertSame(10, $rapport['unique_students']);
        $this->assertSame('Promotion 01', $rapport['rows']->first()->name);
        $this->assertSame('Promotion 10', $rapport['rows']->last()->name);

        $first = $classes->first();
        $filtered = $this->build(['min_inscrits' => 1, 'filiere_id' => $first->filiere_id, 'niveau_id' => $first->niveau_etude_id]);
        $this->assertSame(1, $filtered['retained_count']);
        $this->assertSame($first->id, $filtered['rows']->first()->id);
    }

    public function test_etudiant_sur_deux_classes_compte_une_fois_dans_le_total_global(): void
    {
        $a = ESBTPClasse::factory()->create();
        $b = ESBTPClasse::factory()->create();
        $student = ESBTPEtudiant::factory()->create();
        $this->inscrire($a, $this->annee, ['etudiant_id' => $student->id]);
        $this->inscrire($b, $this->annee, ['etudiant_id' => $student->id]);

        $report = $this->build(['min_inscrits' => 1]);
        $this->assertSame(2, $report['sum_class_enrollments']);
        $this->assertSame(1, $report['unique_students']);
        $this->assertSame([1, 1], $report['rows']->pluck('effectif')->all());
        $this->assertStringContainsString('COUNT(DISTINCT', file_get_contents(app_path('Services/Reports/ClasseEffectifsReportService.php')));
    }

    public function test_statuts_non_valides_workflow_et_suppressions_exclus(): void
    {
        $classe = ESBTPClasse::factory()->create();
        $this->inscrire($classe, $this->annee);
        foreach (['en_attente', 'annulée', 'terminée'] as $status) {
            $this->inscrire($classe, $this->annee, ['status' => $status]);
        }
        foreach (['prospect', 'documents_complets', 'en_validation', 'valide'] as $workflow) {
            $this->inscrire($classe, $this->annee, ['workflow_step' => $workflow]);
        }
        $deleted = $this->inscrire($classe, $this->annee);
        $deleted->delete();
        $this->inscrire($classe, $this->autreAnnee);

        $this->assertSame(1, $this->build(['min_inscrits' => 1])['rows']->firstWhere('id', $classe->id)->effectif);
    }

    public function test_ajax_et_permissions_et_sans_donnees_personnelles(): void
    {
        $classe = ESBTPClasse::factory()->create();
        $inscription = $this->inscrire($classe, $this->annee);
        $endpoint = route('esbtp.rapports.effectifs.data');
        $params = ['annee_universitaire_id' => $this->annee->id, 'min_inscrits' => 1, 'nombre_classes' => '10'];

        $intrus = User::factory()->create(['must_change_password' => false]);
        $this->actingAs($intrus)->getJson($endpoint.'?'.http_build_query($params))->assertForbidden();

        $exportOnly = User::factory()->create(['must_change_password' => false]);
        $exportOnly->givePermissionTo('reports.generate');
        $this->actingAs($exportOnly)->get(route('esbtp.rapports.effectifs.pdf'))->assertForbidden();

        $this->actingAs($this->lecteur())
            ->getJson($endpoint.'?'.http_build_query($params))
            ->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('unique_students', 1)
            ->assertJsonPath('retained_count', 1)
            ->assertDontSee($inscription->etudiant->nom)
            ->assertDontSee($inscription->etudiant->matricule);

        $this->get(route('esbtp.rapports.effectifs.index'))->assertOk()
            ->assertSee('effectifsReport()', false)
            ->assertSee('Mise à jour des effectifs')
            ->assertSee('Filière / mention');

        $this->get(route('esbtp.rapports.effectifs.pdf'))->assertForbidden();
        $this->getJson($endpoint.'?min_inscrits=-1')->assertUnprocessable()->assertJsonValidationErrors('min_inscrits');
        $this->getJson($endpoint.'?nombre_classes=99999')->assertUnprocessable()->assertJsonValidationErrors('nombre_classes');
    }

    public function test_pdf_tenant_sans_logo_apercu_et_telechargement(): void
    {
        $classe = ESBTPClasse::factory()->create(['name' => 'Économie générale']);
        $this->inscrire($classe, $this->annee);
        SettingsHelper::set('school_name', 'Université Test Côte d’Ivoire');
        SettingsHelper::set('school_logo', 'logos/fichier-inexistant-rapport.png');
        Cache::flush();

        $this->actingAs($this->lecteur(true));
        $params = ['annee_universitaire_id' => $this->annee->id, 'min_inscrits' => 1, 'nombre_classes' => '10'];

        $report = $this->build(['min_inscrits' => 1, 'nombre_classes' => '10']);
        $pdfView = new ClasseEffectifsReport($report, ['Année universitaire' => $this->annee->name]);
        $html = view($pdfView->pdfView(), array_merge($pdfView->viewData(), [
            'reportTitle' => $pdfView->title(), 'reportSubtitle' => $pdfView->subtitle(), 'reportFilters' => $pdfView->filters(),
        ]))->render();
        $this->assertStringContainsString('Université Test Côte d’Ivoire', $html);
        $this->assertStringContainsString('Économie générale', $html);
        $this->assertTrue(str_contains($html, 'pdf-banner-logo-fallback') || str_contains($html, 'data:image/'));

        $this->get(route('esbtp.rapports.effectifs.pdf-preview', $params))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertSee('%PDF-', false);
        $this->get(route('esbtp.rapports.effectifs.pdf', $params))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition');
    }

    public function test_cle_de_cache_pdf_change_avec_les_inscriptions(): void
    {
        $classe = ESBTPClasse::factory()->create();
        $this->inscrire($classe, $this->annee);
        $filters = ['min_inscrits' => 1, 'nombre_classes' => '10'];
        $one = new ClasseEffectifsReport($this->build($filters), ['Année' => $this->annee->name]);
        $firstKey = $one->cacheKey();

        $this->inscrire($classe, $this->annee);
        $two = new ClasseEffectifsReport($this->build($filters), ['Année' => $this->annee->name]);
        $this->assertNotSame($firstKey, $two->cacheKey());
    }
}
