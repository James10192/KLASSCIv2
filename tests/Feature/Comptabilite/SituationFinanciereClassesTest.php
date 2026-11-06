<?php

namespace Tests\Feature\Comptabilite;

use App\Domain\Comptabilite\SuiviClasses\SituationClassesService;
use App\Domain\Comptabilite\SuiviClasses\SituationClasseMontants;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Models\User;
use App\Services\Frais\MontantsParFrais;
use App\Services\RelanceCalculationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SituationFinanciereClassesTest extends TestCase
{
    use DatabaseTransactions;

    private ESBTPAnneeUniversitaire $annee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
            \App\Http\Middleware\ForcePasswordChange::class,
        ]);

        foreach ([
            'module.comptabilite.access', 'comptabilite.access',
            'comptabilite.suivi_classes.view',
            'comptabilite.suivi_classes.view_details',
            'comptabilite.suivi_classes.export',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        ESBTPAnneeUniversitaire::query()->update(['is_current' => false]);
        $this->annee = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2096-2097', 'start_date' => '2096-09-01',
            'end_date' => '2097-07-31', 'is_current' => true,
        ]);
    }

    private function utilisateur(array $permissions): User
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $user->givePermissionTo($permissions);

        return $user;
    }

    public function test_les_totaux_sont_visibles_sans_autoriser_les_dossiers_ni_les_exports(): void
    {
        $classe = ESBTPClasse::factory()->create();
        $user = $this->utilisateur([
            'module.comptabilite.access', 'comptabilite.access', 'comptabilite.suivi_classes.view',
        ]);
        $this->actingAs($user);

        $this->get(route('esbtp.comptabilite.suivi-classes.index', ['classe' => $classe->id]))
            ->assertOk()->assertSee('Situation financière des classes')
            ->assertDontSee('openDetails(', false);

        $this->getJson(route('esbtp.comptabilite.suivi-classes.details', $classe))
            ->assertForbidden();

        $this->get(route('esbtp.comptabilite.suivi-classes.excel'))
            ->assertForbidden();

        $this->get(route('esbtp.comptabilite.suivi-classes.pdf-preview'))
            ->assertForbidden();
    }

    public function test_sans_droit_ou_module_pas_de_lecture_ni_endpoint_ajax(): void
    {
        $sansDroit = $this->utilisateur(['module.comptabilite.access', 'comptabilite.access']);
        $this->actingAs($sansDroit);
        $this->get(route('esbtp.comptabilite.suivi-classes.index'))->assertForbidden();
        $this->getJson(route('esbtp.comptabilite.suivi-classes.data'))->assertForbidden();

        $sansModule = $this->utilisateur(['comptabilite.access', 'comptabilite.suivi_classes.view']);
        $this->actingAs($sansModule);
        $this->get(route('esbtp.comptabilite.suivi-classes.index'))->assertForbidden();
    }

    public function test_un_filtre_inconnu_est_refuse_au_lieu_de_rendre_un_rapport_vide(): void
    {
        $this->actingAs($this->utilisateur([
            'module.comptabilite.access', 'comptabilite.access', 'comptabilite.suivi_classes.view',
        ]));

        foreach (['filiere', 'niveau', 'classe', 'categorie', 'annee'] as $filtre) {
            $this->getJson(route('esbtp.comptabilite.suivi-classes.data', [$filtre => 999999]))
                ->assertStatus(422)
                ->assertJsonValidationErrors($filtre);
        }
    }

    public function test_grant_individuel_autorise_le_detail_sans_export(): void
    {
        $classe = ESBTPClasse::factory()->create();
        $user = $this->utilisateur([
            'module.comptabilite.access', 'comptabilite.access',
            'comptabilite.suivi_classes.view', 'comptabilite.suivi_classes.view_details',
        ]);
        $this->actingAs($user);
        $this->getJson(route('esbtp.comptabilite.suivi-classes.details', $classe))
            ->assertOk()->assertJsonStructure(['html']);
        $this->get(route('esbtp.comptabilite.suivi-classes.excel'))->assertForbidden();
    }

    public function test_annee_inscription_et_ventilation_canonique_sans_n_plus_un(): void
    {
        $classe = ESBTPClasse::factory()->create();
        $autreAnnee = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2097-2098', 'start_date' => '2097-09-01',
            'end_date' => '2098-07-31', 'is_current' => false,
        ]);
        $inscriptionCourante = null;
        foreach ([$this->annee, $autreAnnee] as $annee) {
            $inscription = ESBTPInscription::factory()->create([
                'classe_id' => $classe->id, 'annee_universitaire_id' => $annee->id,
                'filiere_id' => $classe->filiere_id, 'niveau_id' => $classe->niveau_etude_id,
                'status' => 'active',
            ]);
            if ($annee->is_current) $inscriptionCourante = $inscription;
        }

        $calcs = Mockery::mock(RelanceCalculationService::class);
        $calcs->shouldReceive('preloadForInscriptions')->once()->andReturnSelf();
        $calcs->shouldReceive('getFinancialState')->once()->andReturn([
            'total_due' => 100000,
            'total_paid' => 60000,
            'remaining_total' => 40000,
            'overdue_amount' => 15000,
            'due_lines' => [
                ['category_id' => 17, 'amount' => 75000, 'paid_amount' => 60000,
                    'remaining_amount' => 15000, 'due_date' => '2000-01-01', 'grace_days' => 0],
                ['category_id' => 17, 'amount' => 25000, 'paid_amount' => 0,
                    'remaining_amount' => 25000, 'due_date' => '2099-01-01', 'grace_days' => 0],
            ],
        ]);
        $parFrais = Mockery::mock(MontantsParFrais::class);
        $parFrais->shouldReceive('netParInscriptionEtFrais')->once()
            ->andReturn([(int) $inscriptionCourante->id => [17 => 60000]]);
        $service = new SituationClassesService($calcs, $parFrais, new SituationClasseMontants());
        $rapport = $service->build([
            'annee' => $this->annee->id, 'classe' => $classe->id,
        ], pagination: false);

        $ligne = $rapport['classes']->first();
        $this->assertSame(1, $ligne['effectif']);
        $this->assertEquals(100000, $ligne['attendu']);
        $this->assertEquals(60000, $ligne['regle']);
        $this->assertEquals(40000, $ligne['restant']);
        $this->assertEquals(15000, $ligne['echu']);
        $this->assertEquals(60, $ligne['taux']);
        $this->assertEquals(100000, $rapport['totaux']['attendu']);
    }
    public function test_le_detail_ne_montre_que_les_liens_que_l_utilisateur_peut_ouvrir(): void
    {
        foreach (['students.view', 'inscriptions.view', 'paiements.view'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $classe = ESBTPClasse::factory()->create();
        $inscription = ESBTPInscription::factory()->create([
            'classe_id' => $classe->id, 'annee_universitaire_id' => $this->annee->id,
            'filiere_id' => $classe->filiere_id, 'niveau_id' => $classe->niveau_etude_id,
            'status' => 'active',
        ]);
        $base = ['module.comptabilite.access', 'comptabilite.access', 'comptabilite.suivi_classes.view', 'comptabilite.suivi_classes.view_details'];
        $url = route('esbtp.comptabilite.suivi-classes.details', [$classe, 'annee' => $this->annee->id]);
        $fiche = route('esbtp.etudiants.show', $inscription->etudiant_id);
        $paiements = route('esbtp.paiements.index');

        $this->actingAs($this->utilisateur($base));
        $html = $this->getJson($url)->assertOk()->json('html');
        $this->assertStringNotContainsString($fiche, $html);
        $this->assertStringNotContainsString($paiements, $html);

        $this->actingAs($this->utilisateur([...$base, 'students.view', 'paiements.view']));
        $html = $this->getJson($url)->assertOk()->json('html');
        $this->assertStringContainsString($fiche, $html);
        $this->assertStringContainsString($paiements, $html);
    }

    public function test_un_export_autorise_produit_un_pdf_et_un_xlsx(): void
    {
        $classe = ESBTPClasse::factory()->create();
        $user = $this->utilisateur([
            'module.comptabilite.access', 'comptabilite.access',
            'comptabilite.suivi_classes.view', 'comptabilite.suivi_classes.export',
        ]);
        $this->actingAs($user);

        $pdf = $this->get(route('esbtp.comptabilite.suivi-classes.pdf-preview', [
            'annee' => $this->annee->id, 'classe' => $classe->id,
        ]));
        $pdf->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());

        $excel = $this->get(route('esbtp.comptabilite.suivi-classes.excel', [
            'annee' => $this->annee->id, 'classe' => $classe->id,
        ]));
        $excel->assertOk();
        $this->assertStringContainsString('.xlsx', (string) $excel->headers->get('Content-Disposition'));
    }


}
