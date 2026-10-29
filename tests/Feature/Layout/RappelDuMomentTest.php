<?php

namespace Tests\Feature\Layout;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\User;
use App\Services\EvaluationGradingShortcutService;
use App\Services\EvaluationPublishShortcutService;
use App\Services\TimetableShortcutService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Les quatre rappels du gabarit ne sont plus calculés à chaque page : la page ne
 * porte que les rappels permis, et le navigateur demande celui du moment.
 */
class RappelDuMomentTest extends TestCase
{
    use DatabaseTransactions;

    private const PERMISSIONS = [
        'inscriptions.validate', 'timetables.view', 'timetables.view_all', 'exams.view',
        'evaluations.view', 'notes.view', 'notes.create', 'notes.edit', 'notes.manage_own',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        foreach (self::PERMISSIONS as $nom) {
            Permission::findOrCreate($nom, 'web');
        }

        ESBTPAnneeUniversitaire::query()->update(['is_current' => false]);
        ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2099-2100', 'start_date' => '2099-09-01', 'end_date' => '2100-07-31', 'is_current' => true,
        ]);
    }

    private function services(array $timetable, array $grading, array $publish): void
    {
        foreach ([
            TimetableShortcutService::class => $timetable,
            EvaluationGradingShortcutService::class => $grading,
            EvaluationPublishShortcutService::class => $publish,
        ] as $classe => $resume) {
            $mock = Mockery::mock($classe);
            $mock->shouldReceive('getShortcutSummary')->andReturn($resume);
            $this->app->instance($classe, $mock);
        }
    }

    private function servicesInterdits(): void
    {
        foreach ([TimetableShortcutService::class, EvaluationGradingShortcutService::class, EvaluationPublishShortcutService::class] as $classe) {
            $mock = Mockery::mock($classe);
            $mock->shouldNotReceive('getShortcutSummary');
            $this->app->instance($classe, $mock);
        }
    }

    private function toutesLesPermissions(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(self::PERMISSIONS);

        return $user;
    }

    public function test_une_page_ordinaire_ne_calcule_plus_les_rappels(): void
    {
        $this->servicesInterdits();
        $user = $this->toutesLesPermissions();

        DB::enableQueryLog();
        $reponse = $this->actingAs($user)->get(route('password.change.form'))->assertOk();
        $requetes = array_column(DB::getQueryLog(), 'query');
        DB::disableQueryLog();

        $surLesRappels = array_filter($requetes, fn (string $sql) => preg_match('/esbtp_inscriptions|esbtp_evaluations|esbtp_emploi_temps|esbtp_classes/', $sql));
        $this->assertSame([], array_values($surLesRappels));

        $reponse->assertSee('id="gabaritRappels"', false)
            ->assertSee(route('gabarit.rappel-du-moment'), false)
            ->assertSee('pendingInscriptionsReminder.user.'.$user->id, false)
            ->assertDontSee('id="timetableReminderModal"', false)
            ->assertDontSee('id="pendingInscriptionsReminderModal"', false);
    }

    public function test_la_route_rend_le_premier_rappel_du_dans_l_ordre(): void
    {
        $this->services(
            ['show' => true, 'missing' => 2, 'expired' => 1, 'expiring_soon' => 0],
            ['show' => true, 'total' => 4],
            ['show' => true, 'total' => 3],
        );
        $user = $this->toutesLesPermissions();

        $reponse = $this->actingAs($user)
            ->getJson(route('gabarit.rappel-du-moment', ['rappels' => 'inscriptions-en-attente,emplois-du-temps,evaluations-a-publier']))
            ->assertOk();

        // Aucune inscription en attente sur l'année créée : regardée, vide, puis on passe.
        $reponse->assertJson(['rappel' => 'emplois-du-temps', 'cle' => 'timetableReminder.user.'.$user->id]);
        $reponse->assertJsonPath('verifies', ['inscriptions-en-attente']);
        $html = $reponse->json('html');
        $this->assertStringContainsString('id="timetableReminderModal"', $html);
        $this->assertStringContainsString('data-reminder-key="timetableReminder.user.'.$user->id.'"', $html);
        $this->assertStringContainsString('<strong>2</strong> classe(s) sans emploi du temps', $html);
    }

    public function test_un_rappel_vide_cede_la_place_au_suivant(): void
    {
        $this->services(['show' => false], ['show' => false], ['show' => true, 'total' => 3, 'overdue' => 1]);
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(['timetables.view', 'evaluations.view']);

        $this->actingAs($user)
            ->getJson(route('gabarit.rappel-du-moment', ['rappels' => 'emplois-du-temps,notes-a-saisir,evaluations-a-publier']))
            ->assertOk()
            ->assertJson(['rappel' => 'evaluations-a-publier'])
            ->assertJsonPath('verifies', ['emplois-du-temps', 'notes-a-saisir']);
    }

    public function test_les_droits_sont_reverifies_par_la_route(): void
    {
        $this->servicesInterdits();
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo('notes.view');

        // Emplois du temps et publication demandés sans en avoir le droit : ignorés.
        $this->actingAs($user)
            ->getJson(route('gabarit.rappel-du-moment', ['rappels' => 'inscriptions-en-attente,emplois-du-temps,evaluations-a-publier']))
            ->assertOk()
            ->assertExactJson(['rappel' => null, 'cle' => null, 'html' => null, 'verifies' => []]);
    }

    public function test_sans_droit_la_page_ne_porte_aucun_rappel(): void
    {
        $this->servicesInterdits();

        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->get(route('password.change.form'))
            ->assertOk()
            ->assertDontSee('id="gabaritRappels"', false);
    }

    public function test_un_visiteur_non_connecte_est_refuse(): void
    {
        $this->getJson(route('gabarit.rappel-du-moment', ['rappels' => 'emplois-du-temps']))->assertUnauthorized();
    }
}
