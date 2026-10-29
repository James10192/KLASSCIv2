<?php

namespace Tests\Feature\Emails;

use App\Enums\StatutConvocationRdv;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * `GET /api/cli/emails/diagnostic` : le contrat partage avec klassci-cli, et
 * aucune adresse complete dans la reponse.
 */
class DiagnosticEmailsCliTest extends TestCase
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
        config(['app.tenant_code' => 'esbtp-abidjan']);
    }

    public function test_le_diagnostic_respecte_le_contrat_et_ne_livre_aucune_adresse(): void
    {
        Sanctum::actingAs(User::factory()->create(['email' => 'direction@esbtp.edu']), ['cli:read']);
        DB::table('users')->where('email', '!=', 'direction@esbtp.edu')->update(['email' => null]);
        $this->reservation('kone@gmail.con', StatutConvocationRdv::Echec, 'email_bounced');
        $this->reservation('awa@gmail.com', StatutConvocationRdv::Envoyee, 'delivered', now());

        $reponse = $this->getJson('/api/cli/emails/diagnostic?details=1')->assertOk();

        $reponse->assertJsonStructure([
            'tenant', 'genere_le',
            'adresses' => ['total', 'valides', 'factices', 'fautes_de_frappe', 'sans_email', 'par_domaine_suspect' => [['domaine', 'nombre', 'type', 'suggestion']]],
            'convocations' => ['acceptees', 'delivrees', 'en_attente', 'echecs', 'rebonds', 'supprimees', 'non_synchronisees', 'derniere_synchro'],
            'familles_a_prevenir', 'demandes_non_verifiees',
            'exemples' => [['email_masque', 'table', 'motif']],
        ]);
        $reponse->assertJsonPath('tenant', 'esbtp-abidjan')
            ->assertJsonPath('convocations.rebonds', 1)
            ->assertJsonPath('convocations.delivrees', 1)
            ->assertJsonPath('convocations.acceptees', 1);

        $suspects = collect($reponse->json('adresses.par_domaine_suspect'))->keyBy('domaine');
        $this->assertSame('faute_de_frappe', $suspects['gmail.con']['type']);
        $this->assertSame('gmail.com', $suspects['gmail.con']['suggestion']);
        $this->assertSame('factice', $suspects['esbtp.edu']['type']);

        $corps = $reponse->getContent();
        foreach (['kone@gmail.con', 'awa@gmail.com', 'direction@esbtp.edu'] as $adresse) {
            $this->assertStringNotContainsString($adresse, $corps);
        }
        $this->assertStringContainsString('k***@gmail.con', $corps);
    }

    public function test_sans_details_pas_d_exemples_et_droit_de_lecture_exige(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['cli:read']);
        $this->getJson('/api/cli/emails/diagnostic')->assertOk()->assertJsonMissingPath('exemples');

        Sanctum::actingAs(User::factory()->create(), ['cli:admin']);
        $this->getJson('/api/cli/emails/diagnostic')->assertStatus(403);
    }

    private function reservation(string $email, StatutConvocationRdv $statut, string $code, $delivree = null): ESBTPRdvReservation
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $creneau = ESBTPRdvCreneau::create([
            'annee_universitaire_id' => $annee->id, 'date' => now()->addDays(3)->toDateString(),
            'heure_debut' => '09:00:00', 'heure_fin' => '09:40:00', 'capacite' => 10, 'ouvert' => true,
        ]);
        $candidature = ESBTPCandidature::create([
            'nom' => 'KONE', 'prenoms' => 'Awa', 'date_naissance' => '2007-01-01', 'telephone' => '+22507'.random_int(10000000, 99999999),
            'email' => $email, 'annee_universitaire_id' => $annee->id, 'consentement_at' => now(), 'statut' => ESBTPCandidature::STATUT_EN_ATTENTE,
        ]);

        return ESBTPRdvReservation::create([
            'creneau_id' => $creneau->id, 'candidature_id' => $candidature->id, 'statut' => 'confirmee',
            'nom' => 'KONE', 'prenoms' => 'Awa', 'telephone' => $candidature->telephone, 'date_naissance' => '2007-01-01',
            'email' => $email, 'convocation_statut' => $statut, 'convocation_action' => 'confirme',
            'convocation_envoyee_at' => now(), 'convocation_message_id' => 'msg_'.random_int(1, 99999),
            'convocation_code_distant' => $code, 'convocation_delivree_at' => $delivree, 'convocation_synchro_at' => now(),
        ]);
    }
}
