<?php

namespace Tests\Feature\RendezVous;

use App\Enums\StatutWhatsappRdv;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Models\User;
use App\Services\RendezVous\FamillesAPrevenirRdv;
use App\Services\RendezVous\FeuilleRendezVous;
use App\Services\RendezVous\RelaisWhatsappConvocationRdv;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * La convocation par WhatsApp, apres accord de la famille : ce que KLASSCI
 * remet a MailPulse, et ce qu'il fait de chaque compte rendu.
 */
class ConvocationWhatsappTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET_COMMANDE = 'secret-commande-test';

    private const SECRET_CALLBACK = '12345678901234567890123456789012';

    private const CLE_CALLBACK = 'fk_test';

    private const MAINTENANT = '2026-10-05 09:10:00';

    private User $agent;

    private int $annee;

    private int $numero = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(self::MAINTENANT);
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        Cache::flush();

        config()->set('app.url', 'https://ecole-test.klassci.com');
        config()->set('app.tenant_code', 'ecole-test');
        config()->set('services.mailpulse.enabled', true);
        config()->set('services.mailpulse.base_url', 'https://mailpulse.test');
        config()->set('services.mailpulse.external_application_key', 'klassci');
        config()->set('services.mailpulse.external_organization_id', 'org_1');
        config()->set('services.mailpulse.external_command_key_id', 'ck_1');
        config()->set('services.mailpulse.external_command_secret', self::SECRET_COMMANDE);
        config()->set('services.mailpulse.external_callback_key_id', self::CLE_CALLBACK);
        config()->set('services.mailpulse.external_callback_secret', self::SECRET_CALLBACK);
        DB::table('settings')->where('key', 'school_name')->delete();
        DB::table('settings')->insert([
            'key' => 'school_name', 'value' => 'Institut Test', 'type' => 'string', 'group' => 'general',
            'category' => 'general', 'is_active' => 1, 'is_required' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $permissions = ['admin.access', 'inscriptions.rdv.view', 'inscriptions.rdv.manage', 'inscriptions.rdv.whatsapp'];
        foreach ($permissions as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->agent = User::factory()->create();
        $this->agent->givePermissionTo($permissions);
        $this->annee = ESBTPAnneeUniversitaire::factory()->create()->id;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // --- Reglage et permission ----------------------------------------------

    public function test_reglage_eteint_rien_ne_part_et_le_bouton_n_existe_pas(): void
    {
        Http::fake();
        $this->reservation();

        $this->actingAs($this->agent)->postJson(route('esbtp.rendez-vous.convocations.whatsapp'))
            ->assertStatus(422)->assertJsonFragment(['message' => 'Le relais WhatsApp est désactivé dans les réglages.']);
        $this->actingAs($this->agent)->get(route('esbtp.rendez-vous.index'))
            ->assertOk()->assertDontSee('Prévenir par WhatsApp');
        Http::assertNothingSent();
    }

    public function test_sans_la_permission_ni_bouton_ni_envoi(): void
    {
        $this->allumer();
        Http::fake();
        $this->reservation();
        $autre = User::factory()->create();
        $autre->givePermissionTo(['admin.access', 'inscriptions.rdv.view', 'inscriptions.rdv.manage']);

        $this->actingAs($autre)->postJson(route('esbtp.rendez-vous.convocations.whatsapp'))->assertForbidden();
        $this->actingAs($autre)->get(route('esbtp.rendez-vous.index'))->assertOk()->assertDontSee('Prévenir par WhatsApp');
        Http::assertNothingSent();
    }

    public function test_le_bouton_compte_les_familles_eligibles(): void
    {
        $this->allumer();
        $this->reservation();
        $this->reservation(['convocation_statut' => 'echec']);
        $this->reservation(['convocation_statut' => 'envoyee']);

        $this->actingAs($this->agent)->get(route('esbtp.rendez-vous.index'))
            ->assertOk()->assertSee('Prévenir par WhatsApp (2)');
    }

    public function test_les_reglages_whatsapp_s_enregistrent_depuis_l_ecran(): void
    {
        Permission::findOrCreate('inscriptions.rdv.configure', 'web');
        $this->agent->givePermissionTo('inscriptions.rdv.configure');

        $this->actingAs($this->agent)->postJson(route('esbtp.rendez-vous.reglages'), [
            'whatsapp_formulaire' => '1',
            'inscriptions_rdv_whatsapp_relais' => '1',
            'inscriptions_rdv_whatsapp_texte_accord' => '  Bonjour de {ecole}.  ',
        ])->assertOk();
        $this->assertTrue(app(RelaisWhatsappConvocationRdv::class)->actif());
        $this->assertSame('Bonjour de {ecole}.', app(\App\Services\RendezVous\RendezVousReglages::class)->texteAccordWhatsapp());

        $this->actingAs($this->agent)->postJson(route('esbtp.rendez-vous.reglages'), ['whatsapp_formulaire' => '1'])->assertOk();
        $this->assertFalse(app(RelaisWhatsappConvocationRdv::class)->actif(), 'Case décochée : le relais s\'éteint.');
    }

    // --- Eligibilite ----------------------------------------------------------

    public function test_seules_les_familles_sans_courriel_recu_avec_un_mobile_lisible_a_venir_sont_eligibles(): void
    {
        $this->allumer();
        $relais = app(RelaisWhatsappConvocationRdv::class);

        $sansEmail = $this->reservation();
        $rebond = $this->reservation(['convocation_statut' => 'echec']);
        $recue = $this->reservation(['convocation_statut' => 'envoyee']);
        $telephoneIllisible = $this->reservation(['telephone' => '12']);
        $passee = $this->reservation([], -1);
        $refusee = $this->reservation(['whatsapp_statut' => 'refusee']);
        $enCours = $this->reservation(['whatsapp_statut' => 'accord_demande']);
        $dossierClos = $this->reservation([], 1, 'rejetee');

        $this->assertTrue($relais->eligible($sansEmail->fresh()));
        $this->assertTrue($relais->eligible($rebond->fresh()));
        $this->assertFalse($relais->eligible($recue->fresh()));
        $this->assertFalse($relais->eligible($telephoneIllisible->fresh()));
        $this->assertFalse($relais->eligible($passee->fresh()));
        $this->assertFalse($relais->eligible($refusee->fresh()), 'Un NON ou un STOP ne se redemande pas.');
        $this->assertFalse($relais->eligible($enCours->fresh()));
        $this->assertFalse($relais->eligible($dossierClos->fresh()));
        $this->assertSame(2, $relais->compterEligibles());
    }

    // --- La commande ----------------------------------------------------------

    public function test_la_commande_porte_le_document_et_la_demande_d_accord_signee(): void
    {
        $this->allumer();
        Http::fake(['*' => Http::response(['status' => 'consent_pending', 'operationId' => 'op_1'], 202)]);
        $r = $this->reservation();

        $this->actingAs($this->agent)->postJson(route('esbtp.rendez-vous.convocations.whatsapp'))
            ->assertOk()->assertJsonFragment(['demandees' => 1, 'restantes' => 0, 'bloque' => null]);

        $r->refresh();
        $cle = 'klassci-ecole-test-rdv-convocation-'.$r->id.'-1';
        Http::assertSent(function (Request $requete) use ($r, $cle) {
            $corps = json_decode($requete->body(), true);
            $reference = $corps['content']['filename'];

            return $requete->url() === 'https://mailpulse.test/api/v1/external-applications/klassci/commands'
                && $requete->header('x-external-signature')[0] === 'v1:ck_1='.hash_hmac('sha256', $requete->header('x-external-timestamp')[0].'.'.$requete->body(), self::SECRET_COMMANDE)
                && $corps['operation_key'] === 'rdv.convocation'
                && $corps['channel'] === 'whatsapp'
                && $corps['recipient'] === ['type' => 'phone', 'value' => $r->telephone]
                && $corps['content']['type'] === 'document'
                && str_starts_with($corps['content']['url'], 'https://ecole-test.klassci.com/convocation-rdv/'.$r->id.'.')
                && preg_match('/^convocation-[A-Za-z0-9._-]+\.pdf$/', $reference) === 1
                && $corps['content']['mimeType'] === 'application/pdf'
                && str_contains($corps['content']['caption'], 'Référence :')
                && str_contains($corps['consent']['request']['text'], 'service des inscriptions de Institut Test, via KLASSCI')
                && str_contains($corps['consent']['request']['text'], 'KOUASSI Ama')
                && str_contains($corps['consent']['request']['text'], 'Répondez OUI ou NON')
                && $corps['consent']['expiresInSeconds'] === 172800
                && $corps['metadata'] === ['idempotency_key' => $cle];
        });
        $this->assertSame(StatutWhatsappRdv::Demandee, $r->whatsapp_statut);
        $this->assertSame('op_1', $r->whatsapp_operation_id);
        $this->assertSame($cle, $r->whatsapp_idempotency_key);
        $this->assertSame($this->agent->id, (int) $r->whatsapp_demandee_par);
    }

    public function test_le_texte_d_accord_est_un_reglage_de_l_ecole(): void
    {
        $this->allumer();
        $this->reglage('inscriptions.rdv.whatsapp_texte_accord', '{ecole} : RDV {candidat} le {date} à {heure}. OUI ou NON ?');
        Http::fake(['*' => Http::response(['status' => 'consent_pending', 'operationId' => 'op_1'], 202)]);
        $this->reservation();

        app(RelaisWhatsappConvocationRdv::class)->envoyerUnPaquet($this->agent->id);

        Http::assertSent(fn (Request $q) => json_decode($q->body(), true)['consent']['request']['text']
            === 'Institut Test : RDV KOUASSI Ama le mardi 6 octobre 2026 à 10:00. OUI ou NON ?');
    }

    public function test_un_refus_deja_connu_de_mailpulse_est_consigne_sans_rien_envoyer(): void
    {
        $this->allumer();
        Http::fake(['*' => Http::response(['code' => 'consent_refused'], 409)]);
        $r = $this->reservation();

        $rapport = app(RelaisWhatsappConvocationRdv::class)->envoyerUnPaquet($this->agent->id);

        $this->assertSame(1, $rapport['refusees']);
        $this->assertSame(StatutWhatsappRdv::Refusee, $r->fresh()->whatsapp_statut);
        $this->assertContains($r->id, $this->aAppeler(), 'Un refus renvoie la famille à l\'appel.');
    }

    public function test_une_demande_mise_en_file_par_mailpulse_n_arrete_pas_le_lot(): void
    {
        $this->allumer();
        // Plafond du jour atteint ou heures de silence : MailPulse garde la demande.
        Http::fake(['*' => Http::response(['accepted' => false, 'status' => 'queued', 'operation_id' => 'op_q', 'dispatch_state' => 'queued'], 202)]);
        $r = $this->reservation();

        $rapport = app(RelaisWhatsappConvocationRdv::class)->envoyerUnPaquet($this->agent->id);

        $this->assertSame(1, $rapport['demandees']);
        $this->assertNull($rapport['bloque'] ?? null);
        $this->assertSame('op_q', $r->fresh()->whatsapp_operation_id);
    }

    public function test_un_accord_deja_donne_part_tout_de_suite(): void
    {
        $this->allumer();
        Http::fake(['*' => Http::response(['accepted' => true, 'operation_id' => 'op_9', 'dispatch_state' => 'accepted'], 202)]);
        $r = $this->reservation();

        $rapport = app(RelaisWhatsappConvocationRdv::class)->envoyerUnPaquet($this->agent->id);

        $this->assertSame(1, $rapport['envoyees']);
        $this->assertSame(StatutWhatsappRdv::Accordee, $r->fresh()->whatsapp_statut);
    }

    public function test_une_panne_de_mailpulse_arrete_le_lot_et_garde_la_cle_pour_rejouer(): void
    {
        $this->allumer();
        Http::fakeSequence()
            ->push(['message' => 'down'], 503)
            ->push(['status' => 'consent_pending', 'operationId' => 'op_2'], 202);
        $r = $this->reservation();
        $relais = app(RelaisWhatsappConvocationRdv::class);

        $premier = $relais->envoyerUnPaquet($this->agent->id);
        $cle = $r->fresh()->whatsapp_idempotency_key;
        $this->assertNotNull($premier['bloque']);
        $this->assertNull($r->fresh()->whatsapp_statut);

        $relais->envoyerUnPaquet($this->agent->id);
        $this->assertSame($cle, $r->fresh()->whatsapp_idempotency_key, 'Même tentative, même clé : MailPulse déduplique.');
        $this->assertSame(StatutWhatsappRdv::Demandee, $r->fresh()->whatsapp_statut);
    }

    // --- Les comptes rendus -----------------------------------------------------

    public function test_chaque_evenement_fait_avancer_la_reservation(): void
    {
        $r = $this->reservationProposee();

        $attendus = [
            'consent.requested' => StatutWhatsappRdv::AccordDemande,
            'consent.granted' => StatutWhatsappRdv::Accordee,
            'message.sent' => StatutWhatsappRdv::Envoyee,
            'message.delivered' => StatutWhatsappRdv::Remise,
            'message.read' => StatutWhatsappRdv::Lue,
        ];
        foreach ($attendus as $evenement => $statut) {
            $this->evenement($evenement, $r)->assertStatus(202)->assertJsonFragment(['applied' => true]);
            $this->assertSame($statut, $r->fresh()->whatsapp_statut, $evenement);
        }

        $r->refresh();
        $this->assertNotNull($r->whatsapp_accord_demande_at);
        $this->assertNotNull($r->whatsapp_accord_at);
        $this->assertNotNull($r->whatsapp_envoyee_at);
        $this->assertNotNull($r->whatsapp_remise_at);
    }

    public function test_une_convocation_remise_sort_de_la_liste_d_appel_et_la_feuille_dit_whatsapp(): void
    {
        $r = $this->reservationProposee();
        $this->assertContains($r->id, $this->aAppeler(), 'En attente d\'accord, la famille reste à appeler.');
        $this->assertStringContainsString('WhatsApp : demande en file', $this->motif($r));

        $this->evenement('message.delivered', $r)->assertStatus(202);

        $this->assertNotContains($r->id, $this->aAppeler());
        $this->assertFalse(app(FamillesAPrevenirRdv::class)->concerne($r->fresh()));
        $feuille = app(FeuilleRendezVous::class);
        $jour = Carbon::parse('2026-10-06');
        $this->assertSame('WhatsApp', $feuille->lignes($jour, $jour)[0]['convocation']);
    }

    public function test_refus_et_silence_renvoient_a_l_appel_avec_le_motif_exact(): void
    {
        $refus = $this->reservationProposee();
        $silence = $this->reservationProposee();

        $this->evenement('consent.refused', $refus)->assertStatus(202);
        $this->evenement('consent.expired', $silence)->assertStatus(202);

        $this->assertSame(StatutWhatsappRdv::Refusee, $refus->fresh()->whatsapp_statut);
        $this->assertSame(StatutWhatsappRdv::SansReponse, $silence->fresh()->whatsapp_statut);
        $this->assertStringContainsString('WhatsApp : refusée : La famille a refusé WhatsApp (NON ou STOP).', $this->motif($refus));
        $this->assertStringContainsString('WhatsApp : sans réponse (48 h) : Pas de réponse à la demande d\'accord sous 48 h.', $this->motif($silence));
        $this->assertFalse(app(RelaisWhatsappConvocationRdv::class)->eligible($refus->fresh()));
    }

    public function test_un_echec_de_remise_donne_son_code_et_peut_se_relancer(): void
    {
        $r = $this->reservationProposee();
        $this->evenement('message.failed', $r, ['failureCode' => 'recipient_unreachable'])->assertStatus(202);

        $r->refresh();
        $this->assertSame(StatutWhatsappRdv::Echec, $r->whatsapp_statut);
        $this->assertStringContainsString('recipient_unreachable', (string) $r->whatsapp_erreur);
        $this->assertTrue(app(RelaisWhatsappConvocationRdv::class)->eligible($r));
    }

    public function test_un_evenement_rejoue_ou_en_retard_ne_fait_rien(): void
    {
        $r = $this->reservationProposee();
        $this->evenement('message.delivered', $r)->assertJsonFragment(['applied' => true]);

        $this->evenement('message.delivered', $r)->assertStatus(202)->assertJsonFragment(['applied' => false, 'duplicate' => true]);
        $this->evenement('consent.requested', $r)->assertJsonFragment(['applied' => false]);
        $this->evenement('message.failed', $r)->assertJsonFragment(['applied' => false]);
        $this->assertSame(StatutWhatsappRdv::Remise, $r->fresh()->whatsapp_statut);
    }

    public function test_un_evenement_d_une_tentative_anterieure_ne_touche_plus_rien(): void
    {
        $r = $this->reservationProposee();
        $ancienne = $r->whatsapp_idempotency_key;
        $r->forceFill(['whatsapp_idempotency_key' => $ancienne.'-2', 'whatsapp_tentative' => 2])->save();

        $this->evenement('consent.refused', $r, ['idempotencyKey' => $ancienne])->assertJsonFragment(['applied' => false]);
        $this->assertSame(StatutWhatsappRdv::Demandee, $r->fresh()->whatsapp_statut);
    }

    public function test_un_oui_apres_un_refus_rend_la_famille_de_nouveau_proposable(): void
    {
        $this->allumer();
        $r = $this->reservationProposee();
        $this->evenement('consent.refused', $r);
        $this->evenement('consent.granted', $r)->assertJsonFragment(['applied' => true]);

        $this->assertTrue(app(RelaisWhatsappConvocationRdv::class)->eligible($r->fresh()));
    }

    public function test_un_callback_non_signe_est_refuse(): void
    {
        $r = $this->reservationProposee();
        $this->postJson('/api/v1/integrations/mailpulse/events', [
            'event' => 'message.delivered', 'operationKey' => 'rdv.convocation', 'idempotencyKey' => $r->whatsapp_idempotency_key,
        ])->assertStatus(401);
        $this->assertSame(StatutWhatsappRdv::Demandee, $r->fresh()->whatsapp_statut);
    }

    public function test_les_evenements_arrivent_aussi_sur_le_callback_du_chatbot(): void
    {
        $r = $this->reservationProposee();

        $this->evenement('message.delivered', $r, [], '/api/v1/integrations/mailpulse/parent-chatbot/inbound')
            ->assertStatus(202)->assertJsonFragment(['applied' => true]);
        $this->assertSame(StatutWhatsappRdv::Remise, $r->fresh()->whatsapp_statut);
    }

    // --- Outils -------------------------------------------------------------

    private function allumer(): void
    {
        $this->reglage('inscriptions.rdv.whatsapp_relais', '1');
    }

    private function reglage(string $cle, string $valeur): void
    {
        DB::table('settings')->where('key', $cle)->update(['value' => $valeur]);
        Cache::flush();
    }

    private function evenement(string $evenement, ESBTPRdvReservation $r, array $autres = [], string $url = '/api/v1/integrations/mailpulse/events')
    {
        $corps = json_encode(array_merge([
            'event' => $evenement,
            'operationKey' => 'rdv.convocation',
            'idempotencyKey' => $r->whatsapp_idempotency_key,
            'operationId' => 'op_1',
            'recipient' => $r->telephone,
            'occurredAt' => '2026-10-05T09:12:00Z',
        ], $autres));
        $horodatage = (string) time();

        return $this->call('POST', $url, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_EXTERNAL_EVENT' => $evenement,
            'HTTP_X_EXTERNAL_TIMESTAMP' => $horodatage,
            'HTTP_X_EXTERNAL_SIGNATURE' => 'v1:'.self::CLE_CALLBACK.'='.hash_hmac('sha256', $horodatage.'.'.$corps, self::SECRET_CALLBACK),
        ], $corps);
    }

    /** Une reservation deja remise a MailPulse, en attente de la demande d'accord. */
    private function reservationProposee(): ESBTPRdvReservation
    {
        $r = $this->reservation();
        $r->forceFill([
            'whatsapp_statut' => 'demandee',
            'whatsapp_tentative' => 1,
            'whatsapp_idempotency_key' => 'klassci-ecole-test-rdv-convocation-'.$r->id.'-1',
            'whatsapp_demandee_at' => now(),
            'whatsapp_operation_id' => 'op_1',
        ])->save();

        return $r->fresh();
    }

    /** Les reservations de la liste d'appel (le telephone est propre a chacune ici). @return list<int> */
    private function aAppeler(): array
    {
        $telephones = collect(app(FamillesAPrevenirRdv::class)->lignes())->pluck('telephone');

        return ESBTPRdvReservation::query()->whereIn('telephone', $telephones)->pluck('id')->all();
    }

    private function motif(ESBTPRdvReservation $r): string
    {
        return (string) collect(app(FamillesAPrevenirRdv::class)->lignes())
            ->firstWhere('telephone', $r->telephone)['motif'];
    }

    private function reservation(array $attributs = [], int $dansJours = 1, string $dossier = 'en_attente'): ESBTPRdvReservation
    {
        $creneau = ESBTPRdvCreneau::query()->firstOrCreate([
            'annee_universitaire_id' => $this->annee,
            'date' => Carbon::today()->addDays($dansJours)->toDateString(),
            'heure_debut' => '10:00:00',
        ], ['heure_fin' => '10:30:00', 'capacite' => 10, 'ouvert' => true]);
        $telephone = '+22507'.sprintf('%08d', ++$this->numero);
        $candidature = DB::table('esbtp_candidatures')->insertGetId([
            'nom' => 'KOUASSI', 'prenoms' => 'Ama', 'date_naissance' => '2007-03-12',
            'telephone' => $telephone, 'email' => null,
            'annee_universitaire_id' => $this->annee, 'consentement_at' => now(), 'statut' => $dossier,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return ESBTPRdvReservation::create(array_merge([
            'creneau_id' => $creneau->id, 'candidature_id' => $candidature, 'statut' => 'confirmee',
            'nom' => 'KOUASSI', 'prenoms' => 'Ama', 'telephone' => $telephone,
            'date_naissance' => '2007-03-12', 'email' => null,
            'convocation_statut' => 'sans_email', 'convocation_action' => 'confirme',
        ], $attributs));
    }
}
