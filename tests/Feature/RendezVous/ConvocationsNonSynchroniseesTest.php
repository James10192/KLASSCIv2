<?php

namespace Tests\Feature\RendezVous;

use App\Enums\StatutConvocationRdv;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Services\RendezVous\ConvocationsNonSynchronisees;
use App\Services\RendezVous\SynchroStatutsConvocations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Sur esbtp-abidjan, ~320 convocations « envoyees » restaient « non
 * synchronisees » passage apres passage : la migration du suivi (22/09) les a
 * marquees envoyees sans identifiant MailPulse, et la synchronisation ne relit
 * que ce qui en a un. Le diagnostic dit desormais pourquoi, avec le meme
 * predicat que la synchronisation.
 */
class ConvocationsNonSynchroniseesTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPRdvCreneau $creneau;

    private int $numero = 0;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.mailpulse.enabled' => true,
            'services.mailpulse.api_key' => 'cle-de-test',
            'services.mailpulse.base_url' => 'https://mailpulse.test',
        ]);
        Cache::flush();
        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $this->creneau = ESBTPRdvCreneau::create([
            'annee_universitaire_id' => $annee->id, 'date' => now()->addDays(3)->toDateString(),
            'heure_debut' => '09:00:00', 'heure_fin' => '09:40:00', 'capacite' => 20, 'ouvert' => true,
        ]);
    }

    public function test_chaque_convocation_jamais_relue_a_sa_raison_et_seules_les_relisibles_sont_relues(): void
    {
        Http::fake(['mailpulse.test/api/v1/messages/*' => Http::response(['message' => ['id' => 'x', 'status' => 'sent', 'error_code' => null, 'delivered_at' => null]])]);
        $this->reservation('msg_1', now()->subDays(2));
        // Avant le suivi : marquee envoyee par la migration, sans identifiant ni date.
        $this->reservation(null, null);
        $this->reservation(null, null);
        $this->reservation('msg_2', null);
        $this->reservation('msg_3', now()->subDays(SynchroStatutsConvocations::FENETRE_JOURS + 5));

        $this->assertSame(
            ['a_synchroniser' => 1, 'sans_identifiant' => 2, 'sans_date_envoi' => 1, 'hors_fenetre' => 1, 'code_neutre' => 0, 'autre' => 0],
            app(ConvocationsNonSynchronisees::class)->detail(),
        );

        $rapport = app(SynchroStatutsConvocations::class)->synchroniser();

        $this->assertSame(1, $rapport['lues'], 'La synchronisation relit exactement ce que le diagnostic annonce.');
        $this->assertSame(0, app(ConvocationsNonSynchronisees::class)->detail()['a_synchroniser']);
        $this->assertSame(2, app(ConvocationsNonSynchronisees::class)->detail()['sans_identifiant'], 'Une impasse ne disparait pas en silence.');
    }

    private function reservation(?string $messageId, $envoyeeAt): ESBTPRdvReservation
    {
        $n = ++$this->numero;
        $candidature = ESBTPCandidature::create([
            'nom' => 'KONE', 'prenoms' => 'Awa', 'date_naissance' => '2007-01-01', 'telephone' => '+22507010203'.sprintf('%02d', $n),
            'email' => 'famille'.$n.'@gmail.com', 'annee_universitaire_id' => $this->creneau->annee_universitaire_id,
            'consentement_at' => now(), 'statut' => ESBTPCandidature::STATUT_EN_ATTENTE,
        ]);

        return ESBTPRdvReservation::create([
            'creneau_id' => $this->creneau->id, 'candidature_id' => $candidature->id, 'statut' => 'confirmee',
            'nom' => 'KONE', 'prenoms' => 'Awa', 'telephone' => '+22507010203'.sprintf('%02d', $n), 'date_naissance' => '2007-01-01',
            'email' => 'famille'.$n.'@gmail.com', 'convocation_statut' => StatutConvocationRdv::Envoyee, 'convocation_action' => 'confirme',
            'convocation_message_id' => $messageId, 'convocation_envoyee_at' => $envoyeeAt,
        ]);
    }
}
