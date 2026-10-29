<?php

namespace Tests\Feature\RendezVous;

use App\Enums\StatutConvocationRdv;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Models\Setting;
use App\Services\Reinscription\PortailReinscriptionService;
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
        // Hors du perimetre des rendez-vous (autre annee) : absente du detail, mais
        // la synchronisation, globale, la relit quand meme.
        $this->reservation('msg_4', now()->subDays(2), $this->creneauSur(ESBTPAnneeUniversitaire::factory()->create()));

        $this->assertSame(
            ['a_synchroniser' => 1, 'sans_identifiant' => 2, 'sans_date_envoi' => 1, 'hors_fenetre' => 1, 'code_neutre' => 0, 'autre' => 0],
            app(ConvocationsNonSynchronisees::class)->detail(),
        );

        $rapport = app(SynchroStatutsConvocations::class)->synchroniser();

        $this->assertSame(2, $rapport['lues'], 'Le relisible du perimetre, plus celle hors perimetre : la synchronisation est globale.');
        $this->assertSame(0, app(ConvocationsNonSynchronisees::class)->detail()['a_synchroniser']);
        $this->assertSame(2, app(ConvocationsNonSynchronisees::class)->detail()['sans_identifiant'], 'Une impasse ne disparait pas en silence.');
    }

    public function test_un_verdict_neutre_deja_note_est_une_impasse(): void
    {
        $this->reservation('msg_1', now()->subDays(2), null, 'reconciled');

        $detail = app(ConvocationsNonSynchronisees::class)->detail();

        $this->assertSame(1, $detail['code_neutre']);
        $this->assertSame(0, $detail['a_synchroniser']);
        $this->assertSame(0, $detail['autre']);
    }

    public function test_le_detail_compte_l_annee_cible_et_pas_l_annee_courante(): void
    {
        $cible = ESBTPAnneeUniversitaire::factory()->create(['is_current' => false]);
        Setting::setOrCreate(PortailReinscriptionService::REGLAGE_ANNEE_CIBLE, (string) $cible->id);
        Cache::flush();
        $surCible = $this->creneauSur($cible);
        $this->reservation('msg_1', now()->subDays(2), $surCible);
        $this->reservation(null, null, $surCible);
        // Sur l'annee courante, qui n'est plus celle des rendez-vous.
        $this->reservation('msg_2', now()->subDays(2));

        $detail = app(ConvocationsNonSynchronisees::class)->detail();

        $this->assertSame(1, $detail['a_synchroniser']);
        $this->assertSame(1, $detail['sans_identifiant']);
    }

    private function creneauSur(ESBTPAnneeUniversitaire $annee): ESBTPRdvCreneau
    {
        return ESBTPRdvCreneau::create([
            'annee_universitaire_id' => $annee->id, 'date' => now()->addDays(4)->toDateString(),
            'heure_debut' => '10:00:00', 'heure_fin' => '10:40:00', 'capacite' => 20, 'ouvert' => true,
        ]);
    }

    private function reservation(?string $messageId, $envoyeeAt, ?ESBTPRdvCreneau $creneau = null, ?string $code = null): ESBTPRdvReservation
    {
        $creneau ??= $this->creneau;
        $n = ++$this->numero;
        $candidature = ESBTPCandidature::create([
            'nom' => 'KONE', 'prenoms' => 'Awa', 'date_naissance' => '2007-01-01', 'telephone' => '+22507010203'.sprintf('%02d', $n),
            'email' => 'famille'.$n.'@gmail.com', 'annee_universitaire_id' => $creneau->annee_universitaire_id,
            'consentement_at' => now(), 'statut' => ESBTPCandidature::STATUT_EN_ATTENTE,
        ]);

        return ESBTPRdvReservation::create([
            'creneau_id' => $creneau->id, 'candidature_id' => $candidature->id, 'statut' => 'confirmee',
            'nom' => 'KONE', 'prenoms' => 'Awa', 'telephone' => '+22507010203'.sprintf('%02d', $n), 'date_naissance' => '2007-01-01',
            'email' => 'famille'.$n.'@gmail.com', 'convocation_statut' => StatutConvocationRdv::Envoyee, 'convocation_action' => 'confirme',
            'convocation_message_id' => $messageId, 'convocation_envoyee_at' => $envoyeeAt,
            'convocation_code_distant' => $code,
        ]);
    }
}
