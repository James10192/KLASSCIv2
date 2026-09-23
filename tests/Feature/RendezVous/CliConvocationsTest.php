<?php

namespace Tests\Feature\RendezVous;

use App\Enums\StatutConvocationRdv;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

/**
 * Le geste « Convoquer les non suivies » de l'ecran, par l'API CLI.
 */
class CliConvocationsTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPRdvCreneau $creneau;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $this->creneau = ESBTPRdvCreneau::create([
            'annee_universitaire_id' => $annee->id, 'date' => now()->addDays(3)->toDateString(),
            'heure_debut' => '09:00:00', 'heure_fin' => '09:40:00', 'capacite' => 10, 'ouvert' => true,
        ]);
        Sanctum::actingAs(User::factory()->create(), ['cli:admin']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_remettre_ne_touche_que_les_reservations_actives_d_avant_le_suivi(): void
    {
        $inconnue = $this->reservation(null);
        $annulee = $this->reservation(null, 'annulee');
        $envoyee = $this->reservation(StatutConvocationRdv::Envoyee);

        $this->postJson('/api/cli/rendez-vous/convocations/remettre', ['quoi' => 'inconnues'])
            ->assertOk()
            ->assertJsonPath('data.remises', 1)
            ->assertJsonPath('data.a_envoyer', 1);

        $this->assertSame(StatutConvocationRdv::EnAttente, $inconnue->fresh()->convocation_statut);
        $this->assertNull($annulee->fresh()->convocation_statut);
        $this->assertSame(StatutConvocationRdv::Envoyee, $envoyee->fresh()->convocation_statut);
    }

    public function test_remettre_exige_de_dire_quoi(): void
    {
        $this->postJson('/api/cli/rendez-vous/convocations/remettre', [])->assertStatus(422);
    }

    public function test_remettre_avec_limite_ne_libere_qu_un_premier_courriel(): void
    {
        $premiere = $this->reservation(null);
        $seconde = $this->reservation(null);

        $this->postJson('/api/cli/rendez-vous/convocations/remettre', ['quoi' => 'inconnues', 'limite' => 1])
            ->assertOk()
            ->assertJsonPath('data.remises', 1)
            ->assertJsonPath('data.a_envoyer', 1);

        // La seconde reste hors de la file : la tache planifiee ne peut pas l'envoyer.
        $this->assertSame(StatutConvocationRdv::EnAttente, $premiere->fresh()->convocation_statut);
        $this->assertNull($seconde->fresh()->convocation_statut);
    }

    public function test_une_limite_invalide_est_refusee(): void
    {
        $this->postJson('/api/cli/rendez-vous/convocations/remettre', ['quoi' => 'inconnues', 'limite' => 'abc'])->assertStatus(422);
        $this->postJson('/api/cli/rendez-vous/convocations/remettre', ['quoi' => 'inconnues', 'limite' => 0])->assertStatus(422);
    }

    private function reservation(?StatutConvocationRdv $etat, string $statut = 'confirmee'): ESBTPRdvReservation
    {
        $candidature = ESBTPCandidature::create([
            'nom' => 'YAO', 'prenoms' => 'Awa', 'date_naissance' => '2007-01-01', 'telephone' => '+2250700000'.random_int(100, 999),
            'email' => 'awa'.random_int(1, 99999).'@exemple.ci', 'annee_universitaire_id' => $this->creneau->annee_universitaire_id,
            'consentement_at' => now(), 'statut' => ESBTPCandidature::STATUT_EN_ATTENTE,
        ]);

        return ESBTPRdvReservation::create([
            'creneau_id' => $this->creneau->id, 'candidature_id' => $candidature->id, 'statut' => $statut,
            'nom' => 'YAO', 'prenoms' => 'Awa', 'telephone' => '+2250700000000', 'date_naissance' => '2007-01-01',
            'email' => $candidature->email, 'convocation_statut' => $etat, 'convocation_action' => $etat ? 'confirme' : null,
        ]);
    }
}
