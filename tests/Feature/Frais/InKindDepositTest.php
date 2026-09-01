<?php

namespace Tests\Feature\Frais;

use App\Http\Requests\Paiement\StorePaiementRequest;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\User;
use App\Services\ESBTPInscriptionService;
use App\Services\InKindDepositService;
use App\Services\PaymentStatsService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InKindDepositTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private ESBTPInscription $inscription;
    private ESBTPFraisCategory $ramette;
    private ESBTPFraisCategory $chemise;
    private ESBTPFraisCategory $scolarite;

    protected function setUp(): void
    {
        parent::setUp();

        // Sans superAdmin, EnsureInstalled considere l'application NON INSTALLEE
        // et redirige toute requete HTTP vers /install. Les tests qui passaient
        // par une route ne touchaient donc jamais leur controleur : ils
        // echouaient sur un 302 ou un 404 qui n'avait aucun rapport avec ce
        // qu'ils pretendaient verifier.
        \Spatie\Permission\Models\Role::findOrCreate('superAdmin', 'web');

        $this->user = User::factory()->create();
        $this->user->assignRole('superAdmin');
        \Illuminate\Support\Facades\Cache::flush();

        $this->actingAs($this->user);

        $classe = ESBTPClasse::factory()->create();
        $this->inscription = ESBTPInscription::factory()->create([
            'classe_id' => $classe->id,
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'annee_universitaire_id' => $classe->annee_universitaire_id,
            'created_by' => $this->user->id,
        ]);

        $this->ramette = ESBTPFraisCategory::create([
            'name' => 'Ramette',
            'code' => 'RAMETTE_' . uniqid(),
            'is_mandatory' => true,
            'accepts_in_kind' => true,
            'is_active' => true,
            'category_type' => 'administrative',
            'sort_order' => 10,
            'default_amount' => 5000,
            'payment_deadline_days' => 30,
        ]);

        $this->chemise = ESBTPFraisCategory::create([
            'name' => 'Chemise cartonnée',
            'code' => 'CHEMISE_' . uniqid(),
            'is_mandatory' => true,
            'accepts_in_kind' => true,
            'is_active' => true,
            'category_type' => 'administrative',
            'sort_order' => 11,
            'default_amount' => 2000,
            'payment_deadline_days' => 30,
        ]);

        $this->scolarite = ESBTPFraisCategory::create([
            'name' => 'Scolarité test',
            'code' => 'SCOL_INK_' . uniqid(),
            'is_mandatory' => true,
            'accepts_in_kind' => false,
            'is_active' => true,
            'category_type' => 'academic',
            'sort_order' => 1,
            'default_amount' => 100000,
            'payment_deadline_days' => 30,
        ]);
    }

    public function test_two_in_kind_categories_one_deposited_creates_two_subscriptions_and_reste_du_excludes_deposit(): void
    {
        $this->generateFees([
            $this->ramette->id => 1,
            $this->chemise->id => 0,
        ]);

        $subs = ESBTPFraisSubscription::where('inscription_id', $this->inscription->id)
            ->whereIn('frais_category_id', [$this->ramette->id, $this->chemise->id])
            ->get();

        $this->assertCount(2, $subs);
        $this->assertTrue((bool) $subs->firstWhere('frais_category_id', $this->ramette->id)->satisfied_in_kind);
        $this->assertFalse((bool) $subs->firstWhere('frais_category_id', $this->chemise->id)->satisfied_in_kind);
        $this->assertEquals(2000.0, $this->dueFor([$this->ramette->id, $this->chemise->id]));
    }

    public function test_late_deposit_without_payment_flips_satisfied_in_kind_and_lowers_reste_du(): void
    {
        $this->generateFees([
            $this->ramette->id => 0,
            $this->chemise->id => 0,
        ]);

        $this->assertEquals(7000.0, $this->dueFor([$this->ramette->id, $this->chemise->id]));

        Gate::before(fn () => true);
        $this->withoutMiddleware();

        $response = $this->post(route('esbtp.inscriptions.in-kind-deposits.store', [
            $this->inscription,
            $this->ramette,
        ]));

        $response->assertRedirect();
        $this->assertTrue(
            (bool) ESBTPFraisSubscription::where('inscription_id', $this->inscription->id)
                ->where('frais_category_id', $this->ramette->id)
                ->first()
                ->satisfied_in_kind
        );
        $this->assertEquals(2000.0, $this->dueFor([$this->ramette->id, $this->chemise->id]));
    }

    public function test_late_deposit_with_validated_payment_returns_403(): void
    {
        $this->generateFees([$this->ramette->id => 0]);

        ESBTPPaiement::create([
            'inscription_id' => $this->inscription->id,
            'etudiant_id' => $this->inscription->etudiant_id,
            'annee_universitaire_id' => $this->inscription->annee_universitaire_id,
            'frais_category_id' => $this->ramette->id,
            'montant' => 5000,
            'mode_paiement' => 'espèces',
            'date_paiement' => now()->toDateString(),
            'status' => 'validé',
            'created_by' => $this->user->id,
        ]);

        Gate::before(fn () => true);
        $this->withoutMiddleware();

        $response = $this->post(route('esbtp.inscriptions.in-kind-deposits.store', [
            $this->inscription,
            $this->ramette,
        ]));

        $response->assertForbidden();
        $this->assertFalse(
            (bool) ESBTPFraisSubscription::where('inscription_id', $this->inscription->id)
                ->where('frais_category_id', $this->ramette->id)
                ->first()
                ->satisfied_in_kind
        );
    }

    public function test_deposit_does_not_change_scolarite_kpi_nor_total_recettes(): void
    {
        $this->generateFees([
            $this->ramette->id => 0,
            $this->scolarite->id => 0,
        ]);

        $statsBefore = app(PaymentStatsService::class)->calculateFraisForInscription($this->inscription);
        $recettesBefore = (float) ESBTPPaiement::where('inscription_id', $this->inscription->id)
            ->where('status', 'validé')
            ->sum('montant');

        Gate::before(fn () => true);
        $this->withoutMiddleware();
        $this->post(route('esbtp.inscriptions.in-kind-deposits.store', [
            $this->inscription,
            $this->ramette,
        ]))->assertRedirect();

        $statsAfter = app(PaymentStatsService::class)->calculateFraisForInscription($this->inscription);
        $recettesAfter = (float) ESBTPPaiement::where('inscription_id', $this->inscription->id)
            ->where('status', 'validé')
            ->sum('montant');

        $this->assertEquals($statsBefore['academic']['expected'], $statsAfter['academic']['expected']);
        $this->assertEquals($recettesBefore, $recettesAfter);
        $this->assertSame(0, ESBTPPaiement::where('inscription_id', $this->inscription->id)->count());
    }

    public function test_caisse_can_collect_undeposited_in_kind_category(): void
    {
        $this->generateFees([$this->ramette->id => 0]);

        $request = new StorePaiementRequest();
        $validator = Validator::make([
            'inscription_id' => $this->inscription->id,
            'etudiant_id' => $this->inscription->etudiant_id,
            'frais_category_id' => $this->ramette->id,
            'montant' => 5000,
            'date_paiement' => now()->toDateString(),
            'mode_paiement' => 'espèces',
        ], $request->rules());

        $this->assertFalse($validator->fails(), (string) $validator->errors()->first());
    }

    /**
     * Le symetrique du test precedent, et le seul qui protege reellement.
     *
     * Le test « la caisse peut encaisser un frais en nature NON depose » ne
     * verifie que les regles de validation du formulaire : il n'atteint jamais
     * le controleur. Le cas inverse — la caisse doit etre REFUSEE sur un frais
     * DEJA depose — n'existait nulle part, et c'est exactement par la que le
     * defaut est passe : la garde ne vivait que dans un gabarit d'affichage,
     * une vue l'a contournee, et un etudiant pouvait payer sa ramette deux fois.
     *
     * Celui-ci passe par la vraie route, pour que la protection soit celle du
     * serveur et pas celle d'un template.
     */
    public function test_caisse_refuse_un_frais_deja_depose_en_nature(): void
    {
        $this->generateFees([$this->ramette->id => 1]);

        $souscription = ESBTPFraisSubscription::where('inscription_id', $this->inscription->id)
            ->where('frais_category_id', $this->ramette->id)
            ->first();

        $this->assertTrue((bool) $souscription->satisfied_in_kind, 'Le depot doit etre enregistre avant le test.');

        $avant = ESBTPPaiement::where('inscription_id', $this->inscription->id)->count();

        $reponse = $this->post(route('esbtp.paiements.store'), [
            'inscription_id' => $this->inscription->id,
            'etudiant_id' => $this->inscription->etudiant_id,
            'frais_category_id' => $this->ramette->id,
            'montant' => 5000,
            'date_paiement' => now()->toDateString(),
            'mode_paiement' => 'espèces',
        ]);

        $reponse->assertSessionHasErrors('frais_category_id');

        $this->assertSame(
            $avant,
            ESBTPPaiement::where('inscription_id', $this->inscription->id)->count(),
            'Aucun paiement ne doit etre enregistre sur un frais deja depose en nature.'
        );
    }

    public function test_fee_regeneration_does_not_overwrite_satisfied_in_kind(): void
    {
        $this->generateFees([$this->ramette->id => 1]);

        app(ESBTPInscriptionService::class)->regenererFraisInscription($this->inscription->fresh());

        $sub = ESBTPFraisSubscription::where('inscription_id', $this->inscription->id)
            ->where('frais_category_id', $this->ramette->id)
            ->first();

        $this->assertTrue((bool) $sub->satisfied_in_kind);
        $this->assertNotNull($sub->deposited_at);
    }

    public function test_enrollment_officer_without_finance_can_mark_in_kind(): void
    {
        $this->generateFees([$this->ramette->id => 0]);
        $this->actingAsEnrollmentOfficer();

        $this->post(route('esbtp.inscriptions.in-kind-deposits.store', [
            $this->inscription,
            $this->ramette,
        ]))->assertRedirect(route('esbtp.inscriptions.show', $this->inscription));

        $this->assertTrue(
            (bool) ESBTPFraisSubscription::where('inscription_id', $this->inscription->id)
                ->where('frais_category_id', $this->ramette->id)
                ->first()
                ->satisfied_in_kind
        );
    }

    public function test_user_without_in_kind_permission_cannot_mark(): void
    {
        $this->generateFees([$this->ramette->id => 0]);

        Permission::findOrCreate('identity.enrollment_officer', 'web');
        $denied = User::factory()->create();
        $denied->givePermissionTo('identity.enrollment_officer');
        $this->actingAs($denied);

        $this->post(route('esbtp.inscriptions.in-kind-deposits.store', [
            $this->inscription,
            $this->ramette,
        ]))->assertForbidden();

        $this->assertFalse(
            (bool) ESBTPFraisSubscription::where('inscription_id', $this->inscription->id)
                ->where('frais_category_id', $this->ramette->id)
                ->first()
                ->satisfied_in_kind
        );
    }

    public function test_show_lists_in_kind_without_finance_for_enrollment_officer(): void
    {
        $this->generateFees([$this->ramette->id => 0]);
        $this->actingAsEnrollmentOfficer();

        $response = $this->get(route('esbtp.inscriptions.show', $this->inscription));

        $response->assertOk();
        $response->assertSee('Fournitures à déposer', false);
        $response->assertSee('Marquer déposé', false);
        $response->assertDontSee('Situation Financière Détaillée', false);
        $response->assertDontSee('Total attendu', false);
    }

    private function actingAsEnrollmentOfficer(): User
    {
        foreach (['identity.enrollment_officer', 'inscriptions.view', 'inscriptions.in_kind.mark'] as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $agent = User::factory()->create();
        $agent->givePermissionTo([
            'identity.enrollment_officer',
            'inscriptions.view',
            'inscriptions.in_kind.mark',
        ]);
        $this->actingAs($agent);

        return $agent;
    }

    private function dueFor(array $categoryIds): float
    {
        return (float) ESBTPFraisSubscription::where('inscription_id', $this->inscription->id)
            ->whereIn('frais_category_id', $categoryIds)
            ->charged()
            ->sum('amount');
    }

    private function generateFees(array $inKindDeposits): void
    {
        $service = app(ESBTPInscriptionService::class);
        $fees = $service->generateFeesForInscription(
            $this->inscription,
            [],
            ESBTPInscription::DEFAULT_AFFECTATION_STATUS,
        );
        $service->saveGeneratedFeesAsSubscriptions($this->inscription, $fees);
        app(InKindDepositService::class)->applyDeposits($this->inscription, $inKindDeposits, $this->user->id);
    }
}
