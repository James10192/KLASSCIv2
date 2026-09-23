<?php

namespace Tests\Feature\RendezVous;

use App\Enums\StatutConvocationRdv;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Services\RendezVous\FamillesAPrevenirRdv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * « Envoyee » ne voulait dire que « acceptee par MailPulse ». La
 * synchronisation relit l'etat reel : un rebond fait entrer la famille dans
 * la liste d'appel, une remise est datee.
 */
class SynchroConvocationsTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPRdvCreneau $creneau;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.mailpulse.enabled' => true,
            'services.mailpulse.api_key' => 'cle-de-test',
            'services.mailpulse.base_url' => 'https://mailpulse.test',
        ]);
        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $this->creneau = ESBTPRdvCreneau::create([
            'annee_universitaire_id' => $annee->id, 'date' => now()->addDays(3)->toDateString(),
            'heure_debut' => '09:00:00', 'heure_fin' => '09:40:00', 'capacite' => 10, 'ouvert' => true,
        ]);
    }

    public function test_un_rebond_met_en_echec_et_rejoint_les_familles_a_prevenir(): void
    {
        Http::fake([
            'mailpulse.test/api/v1/messages/msg_rebond' => Http::response(['message' => ['id' => 'msg_rebond', 'status' => 'failed', 'error_code' => 'email_bounced', 'delivered_at' => null]]),
            'mailpulse.test/api/v1/messages/msg_ok' => Http::response(['message' => ['id' => 'msg_ok', 'status' => 'delivered', 'error_code' => null, 'delivered_at' => '2026-09-20T10:00:00Z']]),
            'mailpulse.test/api/v1/messages/msg_transit' => Http::response(['message' => ['id' => 'msg_transit', 'status' => 'sent', 'error_code' => null, 'delivered_at' => null]]),
        ]);
        $rebond = $this->reservation('msg_rebond');
        $ok = $this->reservation('msg_ok');
        $transit = $this->reservation('msg_transit');
        $this->assertSame(0, app(FamillesAPrevenirRdv::class)->compter());

        $this->artisan('inscriptions:synchroniser-convocations-rdv')->assertSuccessful();

        $this->assertSame(StatutConvocationRdv::Echec, $rebond->fresh()->convocation_statut);
        $this->assertStringContainsString('email_bounced', (string) $rebond->fresh()->convocation_erreur);
        $this->assertSame(1, app(FamillesAPrevenirRdv::class)->compter());

        $this->assertSame(StatutConvocationRdv::Envoyee, $ok->fresh()->convocation_statut);
        $this->assertSame('2026-09-20 10:00:00', $ok->fresh()->convocation_delivree_at->utc()->format('Y-m-d H:i:s'));

        $this->assertSame(StatutConvocationRdv::Envoyee, $transit->fresh()->convocation_statut);
        $this->assertNull($transit->fresh()->convocation_delivree_at);
        $this->assertNotNull($transit->fresh()->convocation_synchro_at);
    }

    public function test_un_message_illisible_ne_bloque_pas_la_file(): void
    {
        Http::fake(['mailpulse.test/api/v1/messages/*' => Http::response(['error' => 'Message not found'], 404)]);
        $perdue = $this->reservation('msg_perdu');

        $this->artisan('inscriptions:synchroniser-convocations-rdv')->assertSuccessful();

        $this->assertNotNull($perdue->fresh()->convocation_synchro_at);
        $this->assertSame('introuvable', $perdue->fresh()->convocation_code_distant);
        $this->assertSame(StatutConvocationRdv::Envoyee, $perdue->fresh()->convocation_statut);
    }

    public function test_une_convocation_replanifiee_pendant_la_lecture_n_est_pas_ecrasee(): void
    {
        $reservation = $this->reservation('msg_ancien');
        Http::fake(function () use ($reservation) {
            // Pendant l'appel, l'ecole deplace le rendez-vous : nouveau courriel en attente.
            $reservation->fresh()->forceFill(['convocation_statut' => StatutConvocationRdv::EnAttente, 'convocation_message_id' => null])->save();

            return Http::response(['message' => ['id' => 'msg_ancien', 'status' => 'failed', 'error_code' => 'email_bounced', 'delivered_at' => null]]);
        });

        $this->artisan('inscriptions:synchroniser-convocations-rdv')->assertSuccessful();

        $this->assertSame(StatutConvocationRdv::EnAttente, $reservation->fresh()->convocation_statut);
        $this->assertNull($reservation->fresh()->convocation_erreur);
    }

    public function test_une_panne_passagere_arrete_le_lot_sans_rien_dater(): void
    {
        Http::fake(['mailpulse.test/api/v1/messages/*' => Http::response(['error' => 'busy'], 503)]);
        $premiere = $this->reservation('msg_a');
        $seconde = $this->reservation('msg_b');

        $this->artisan('inscriptions:synchroniser-convocations-rdv')->assertFailed();

        Http::assertSentCount(1);
        $this->assertNull($premiere->fresh()->convocation_synchro_at);
        $this->assertNull($seconde->fresh()->convocation_synchro_at);
    }

    public function test_un_message_reconcilie_est_note_et_plus_jamais_relu(): void
    {
        Http::fake(['mailpulse.test/api/v1/messages/*' => Http::response(['message' => ['id' => 'msg_r', 'status' => 'reconciled', 'error_code' => null, 'delivered_at' => null]])]);
        $reservation = $this->reservation('msg_r');

        $this->artisan('inscriptions:synchroniser-convocations-rdv')->assertSuccessful();
        $this->artisan('inscriptions:synchroniser-convocations-rdv')->assertSuccessful();

        Http::assertSentCount(1);
        $this->assertSame('reconciled', $reservation->fresh()->convocation_code_distant);
        $this->assertSame(StatutConvocationRdv::Envoyee, $reservation->fresh()->convocation_statut);
    }

    public function test_mailpulse_sans_cle_arrete_le_lot_sans_rien_toucher(): void
    {
        config(['services.mailpulse.api_key' => '']);
        Http::fake();
        $reservation = $this->reservation('msg_x');

        $this->artisan('inscriptions:synchroniser-convocations-rdv')->assertFailed();

        Http::assertNothingSent();
        $this->assertNull($reservation->fresh()->convocation_synchro_at);
    }

    private function reservation(string $messageId): ESBTPRdvReservation
    {
        $candidature = ESBTPCandidature::create([
            'nom' => 'YAO', 'prenoms' => 'Awa', 'date_naissance' => '2007-01-01', 'telephone' => '+22507'.random_int(10000000, 99999999),
            'email' => 'awa'.random_int(1, 99999).'@gmail.com', 'annee_universitaire_id' => $this->creneau->annee_universitaire_id,
            'consentement_at' => now(), 'statut' => ESBTPCandidature::STATUT_EN_ATTENTE,
        ]);

        return ESBTPRdvReservation::create([
            'creneau_id' => $this->creneau->id, 'candidature_id' => $candidature->id, 'statut' => 'confirmee',
            'nom' => 'YAO', 'prenoms' => 'Awa', 'telephone' => $candidature->telephone, 'date_naissance' => '2007-01-01',
            'email' => $candidature->email, 'convocation_statut' => StatutConvocationRdv::Envoyee, 'convocation_action' => 'confirme',
            'convocation_envoyee_at' => now()->subHour(), 'convocation_message_id' => $messageId,
        ]);
    }
}
