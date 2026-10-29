<?php

namespace Tests\Feature\LMD;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\User;
use App\Services\LMD\LMDImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Les planifications laissees a 0 credit par la saisie d'heures se reparent ;
 * un 0 choisi a la main reste.
 */
class CreditsNulsReparationTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPPlanificationAcademique $laissee;

    private ESBTPPlanificationAcademique $choisie;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        app(LMDImportService::class)->import([
            'domaine' => ['name' => 'Sciences Agronomiques', 'code' => 'SA'],
            'mention' => ['name' => 'Productions', 'code' => 'PVA'],
            'parcours' => ['name' => 'Parcours LPA', 'code' => 'LPA', 'credits_licence' => 180],
            'filiere' => ['name' => 'Filiere LPA', 'code' => 'FLPA'],
            'niveaux' => [['name' => 'Licence 2', 'year' => 2]],
            'ues' => [[
                'code' => 'AGR2103', 'name' => 'Amelioration genetique', 'credit' => 5,
                'niveau_year' => 2, 'semestre' => 3,
                'ecues' => [
                    ['code' => 'AGR21033', 'name' => 'Genetique animale', 'credit_ecue' => 3],
                    ['code' => 'AGR21034', 'name' => 'Reproduction animale', 'credit_ecue' => 2],
                ],
            ]],
        ]);

        // Des lignes d'avant septembre 2026 : leur creation n'etait pas auditee.
        DB::table('audits')->where('auditable_type', ESBTPPlanificationAcademique::class)->where('event', 'created')->delete();

        $planif = fn (string $code) => ESBTPPlanificationAcademique::where('matiere_id', ESBTPMatiere::where('code', $code)->value('id'))->firstOrFail();

        // Laissee a 0 par l'ancienne saisie d'heures : aucune trace d'audit.
        $this->laissee = $planif('AGR21033');
        DB::table('esbtp_planifications_academiques')->where('id', $this->laissee->id)->update(['credits_ects' => 0]);

        // Mise a 0 a la main depuis l'ecran : l'audit la trahit.
        $this->choisie = $planif('AGR21034');
        $this->choisie->update(['credits_ects' => 0]);
    }

    public function test_la_simulation_liste_sans_ecrire(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['cli:admin']);

        $data = $this->postJson('/api/cli/lmd/planifications/reparer-credits')
            ->assertOk()->assertJsonPath('dry_run', true)->json('data');

        $this->assertSame(1, $data['candidates'], 'Seule la ligne laissee a 0 est candidate.');
        $this->assertSame($this->laissee->id, $data['lignes'][0]['id']);
        $this->assertSame(3, $data['lignes'][0]['credit_attendu']);
        $this->assertSame(0, (int) $this->laissee->fresh()->credits_ects, 'La simulation n\'ecrit rien.');
    }

    public function test_la_reparation_pose_le_credit_et_garde_le_zero_choisi(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['cli:admin']);

        $this->postJson('/api/cli/lmd/planifications/reparer-credits', ['dry_run' => false, 'ids' => [$this->laissee->id, $this->choisie->id]])
            ->assertOk()->assertJsonPath('data.reparees', 1);

        $this->assertSame(3, (int) $this->laissee->fresh()->credits_ects);
        $this->assertSame(0, (int) $this->choisie->fresh()->credits_ects, 'Un 0 choisi est une decision de l\'ecole.');
    }

    public function test_sans_audit_l_ecriture_est_refusee(): void
    {
        config(['audit.enabled' => false]);
        Sanctum::actingAs(User::factory()->create(), ['cli:admin']);

        $this->postJson('/api/cli/lmd/planifications/reparer-credits', ['dry_run' => false, 'ids' => [$this->laissee->id]])->assertStatus(409);
        $this->assertSame(0, (int) $this->laissee->fresh()->credits_ects);
    }

    public function test_un_jeton_de_lecture_ne_suffit_pas(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['cli:read']);
        $this->postJson('/api/cli/lmd/planifications/reparer-credits')->assertForbidden();
    }

    public function test_ecrire_sans_liste_relue_est_refuse(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['cli:admin']);

        $this->postJson('/api/cli/lmd/planifications/reparer-credits', ['dry_run' => false])->assertStatus(422);
        $this->assertSame(0, (int) $this->laissee->fresh()->credits_ects);
    }

    public function test_un_zero_saisi_a_la_creation_n_est_pas_candidat(): void
    {
        // Creation auditee : un 0 pose des la creation est une decision tracee.
        $this->laissee->delete();
        $nouvelle = $this->laissee->replicate();
        $nouvelle->credits_ects = 0;
        $nouvelle->deleted_at = null;
        $nouvelle->annee_universitaire_id = ESBTPAnneeUniversitaire::factory()->create()->id;
        $nouvelle->save();

        $ids = app(\App\Services\LMD\CreditDeMaquette::class)->creditsNulsAReparer()->pluck('id');
        $this->assertFalse($ids->contains($nouvelle->id));
    }
}
