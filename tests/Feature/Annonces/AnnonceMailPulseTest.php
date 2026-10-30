<?php

namespace Tests\Feature\Annonces;

use App\Jobs\Annonces\DiffuserAnnonceParCourriel;
use App\Jobs\Annonces\EnvoyerAnnonceCourriel;
use App\Models\ESBTPAnnonce;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Services\Annonces\CiblageCourrielsAnnonce;
use App\Mail\Transport\CadenceMailPulse;
use App\Services\MailPulse\MailPulseClient;
use App\Services\MailPulse\MailPulseStatutsMessages;
use App\Services\Annonces\SynchroniserCourrielsAnnonce;
use App\Services\MailPulse\MailPulseResult;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class AnnonceMailPulseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['app.tenant_code' => 'presentation']);
    }

    private function annonce(string $type = 'general', bool $courriel = true): ESBTPAnnonce
    {
        return ESBTPAnnonce::create([
            'titre' => 'Annonce test',
            'contenu' => 'Informations importantes pour les étudiants.',
            'type' => $type,
            'priorite' => 0,
            'date_publication' => now(),
            'date_expiration' => now()->addDay(),
            'is_published' => true,
            'email_notification_enabled' => $courriel,
        ]);
    }

    public function test_brouillon_et_case_decochee_n_envoient_rien(): void
    {
        Bus::fake([EnvoyerAnnonceCourriel::class]);
        ESBTPEtudiant::factory()->create(['email' => 'exemple@etudiants.test']);

        $annonce = $this->annonce('general', false);
        (new DiffuserAnnonceParCourriel($annonce->id))->handle();
        $this->assertSame(0, DB::table('esbtp_annonce_email_deliveries')->count());

        $annonce->update(['email_notification_enabled' => true, 'is_published' => false]);
        (new DiffuserAnnonceParCourriel($annonce->id))->handle();
        $this->assertSame(0, DB::table('esbtp_annonce_email_deliveries')->count());
        Bus::assertNotDispatched(EnvoyerAnnonceCourriel::class);
    }

    public function test_cible_nominative_une_adresse_unique_meme_si_deux_etudiants_la_partagent(): void
    {
        Bus::fake([EnvoyerAnnonceCourriel::class]);
        // Le champ email est unique en base, mais email_personnel peut être partagé.
        $a = ESBTPEtudiant::factory()->create(['email_personnel' => 'partage@etudiants.test']);
        $b = ESBTPEtudiant::factory()->create(['email_personnel' => 'partage@etudiants.test']);
        ESBTPEtudiant::factory()->create(['email' => 'hors-cible@etudiants.test']);

        $annonce = $this->annonce('etudiant');
        $annonce->etudiants()->attach([$a->id, $b->id]);
        (new DiffuserAnnonceParCourriel($annonce->id))->handle();

        $this->assertSame(['partage@etudiants.test'],
            DB::table('esbtp_annonce_email_deliveries')->where('annonce_id', $annonce->id)->pluck('email')->all());
        Bus::assertDispatchedTimes(EnvoyerAnnonceCourriel::class, 1);
    }

    public function test_cible_classe_n_inclut_pas_les_autres_classes_ni_les_anciennes_inscriptions(): void
    {
        Bus::fake([EnvoyerAnnonceCourriel::class]);
        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $ancienne = ESBTPAnneeUniversitaire::factory()->create(['is_current' => false]);
        ESBTPAnneeUniversitaire::flushCurrentCache();

        $classe = ESBTPClasse::factory()->create(['annee_universitaire_id' => $annee->id]);
        $autre = ESBTPClasse::factory()->create(['annee_universitaire_id' => $annee->id]);
        $a = ESBTPEtudiant::factory()->create(['email' => 'bonne@etudiants.test']);
        $b = ESBTPEtudiant::factory()->create(['email' => 'autre@etudiants.test']);
        $c = ESBTPEtudiant::factory()->create(['email' => 'ancienne@etudiants.test']);
        ESBTPInscription::factory()->create(['etudiant_id' => $a->id, 'annee_universitaire_id' => $annee->id, 'classe_id' => $classe->id]);
        ESBTPInscription::factory()->create(['etudiant_id' => $b->id, 'annee_universitaire_id' => $annee->id, 'classe_id' => $autre->id]);
        ESBTPInscription::factory()->create(['etudiant_id' => $c->id, 'annee_universitaire_id' => $ancienne->id, 'classe_id' => $classe->id]);

        $annonce = $this->annonce('classe');
        $annonce->classes()->attach($classe->id);
        (new DiffuserAnnonceParCourriel($annonce->id))->handle();

        $this->assertSame(['bonne@etudiants.test'],
            DB::table('esbtp_annonce_email_deliveries')->where('annonce_id', $annonce->id)->pluck('email')->all());
    }

    public function test_une_livraison_soumise_n_est_jamais_envoyee_deux_fois(): void
    {
        $annonce = $this->annonce();
        $id = DB::table('esbtp_annonce_email_deliveries')->insertGetId([
            'annonce_id' => $annonce->id, 'email' => 'etudiant@exemple.ci',
            'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $client = Mockery::mock(MailPulseClient::class);
        $client->shouldReceive('getSetting')->twice()->andReturn('');
        $client->shouldReceive('sendEmailMessage')->once()
            ->withArgs(function ($charge, $cle) use ($id, $annonce) {
                $this->assertSame('email', $charge['channel']);
                $this->assertSame('etudiant@exemple.ci', $charge['recipient']['value']);
                $this->assertStringContainsString('Annonce test', $charge['content']['text']);
                $this->assertSame("klassci-v2-presentation-annonce-{$annonce->id}-courriel-{$id}", $cle);
                $this->assertSame($cle, $charge['metadata']['external_reference']);
                return true;
            })->andReturn(new MailPulseResult(
                true, 'accepted', 201, 'r-test', 'mp-test', null, null, null, 'accepted'
            ));

        $job = new EnvoyerAnnonceCourriel($id);
        $job->handle($client, new CadenceMailPulse);
        $job->handle($client, new CadenceMailPulse);

        $this->assertSame('submitted', DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->value('status'));
        $this->assertNotNull(DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->value('submitted_at'));
        $this->assertNull(DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->value('provider_delivered_at'));
        $this->assertSame('mp-test', DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->value('mailpulse_message_id'));
    }

    public function test_mailpulse_pending_reconciliation_n_est_jamais_compte_comme_delivre(): void
    {
        $annonce = $this->annonce();
        $id = DB::table('esbtp_annonce_email_deliveries')->insertGetId([
            'annonce_id' => $annonce->id, 'email' => 'exemple@etudiants.test',
            'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $client = Mockery::mock(MailPulseClient::class);
        $client->shouldReceive('getSetting')->twice()->andReturn('');
        $client->shouldReceive('sendEmailMessage')->once()
            ->andReturn(new MailPulseResult(
                true, 'accepted', 202, 'r-reconcile', null, null, null, null, 'pending_reconciliation'
            ));
        (new EnvoyerAnnonceCourriel($id))->handle($client, new CadenceMailPulse);

        $livraison = DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->first();
        $this->assertSame('awaiting_dispatch', $livraison->status);
        $this->assertNull($livraison->submitted_at);
        $this->assertNull($livraison->provider_delivered_at);
    }

    public function test_le_filtre_annuel_ne_touche_que_les_inscriptions_actives_de_l_annee_choisie(): void
    {
        Bus::fake([EnvoyerAnnonceCourriel::class]);
        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $ancienne = ESBTPAnneeUniversitaire::factory()->create(['is_current' => false]);
        ESBTPAnneeUniversitaire::flushCurrentCache();

        $active = ESBTPEtudiant::factory()->create(['email' => 'actif@etudiants.test']);
        $annule = ESBTPEtudiant::factory()->create(['email' => 'annule@etudiants.test']);
        $archive = ESBTPEtudiant::factory()->create(['email' => 'archive@etudiants.test']);
        ESBTPInscription::factory()->create(['etudiant_id' => $active->id, 'annee_universitaire_id' => $annee->id, 'status' => 'active']);
        ESBTPInscription::factory()->create(['etudiant_id' => $annule->id, 'annee_universitaire_id' => $annee->id, 'status' => 'annulée']);
        ESBTPInscription::factory()->create(['etudiant_id' => $archive->id, 'annee_universitaire_id' => $ancienne->id, 'status' => 'active']);

        $annonce = $this->annonce();
        $annonce->update(['email_audience_scope' => CiblageCourrielsAnnonce::ACTIVES_ANNEE, 'email_target_year_id' => $annee->id]);

        (new DiffuserAnnonceParCourriel($annonce->id))->handle();

        $this->assertSame(['actif@etudiants.test'],
            DB::table('esbtp_annonce_email_deliveries')->where('annonce_id', $annonce->id)->pluck('email')->all());
    }

    public function test_une_classe_inactive_dans_l_annee_cible_ne_recoit_pas_par_une_autre_inscription_active(): void
    {
        Bus::fake([EnvoyerAnnonceCourriel::class]);
        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        ESBTPAnneeUniversitaire::flushCurrentCache();
        $cible = ESBTPClasse::factory()->create(['annee_universitaire_id' => $annee->id]);
        $autre = ESBTPClasse::factory()->create(['annee_universitaire_id' => $annee->id]);
        $etudiant = ESBTPEtudiant::factory()->create(['email' => 'invalide@etudiants.test']);
        ESBTPInscription::factory()->create(['etudiant_id' => $etudiant->id,
            'annee_universitaire_id' => $annee->id, 'classe_id' => $cible->id, 'status' => 'annulée']);
        ESBTPInscription::factory()->create(['etudiant_id' => $etudiant->id,
            'annee_universitaire_id' => $annee->id, 'classe_id' => $autre->id, 'status' => 'active']);

        $annonce = $this->annonce('classe');
        $annonce->classes()->attach($cible->id);
        $annonce->update(['email_audience_scope' => CiblageCourrielsAnnonce::ACTIVES_ANNEE, 'email_target_year_id' => $annee->id]);
        (new DiffuserAnnonceParCourriel($annonce->id))->handle();
        $this->assertSame(0, DB::table('esbtp_annonce_email_deliveries')->where('annonce_id', $annonce->id)->count());
    }

    public function test_signature_mailpulse_et_protection_contre_statuts_dans_le_desordre(): void
    {
        $annonce = $this->annonce();
        $id = DB::table('esbtp_annonce_email_deliveries')->insertGetId([
            'annonce_id' => $annonce->id, 'email' => 'test@etudiants.test',
            'status' => 'submitted', 'submitted_at' => now(),
            'mailpulse_message_id' => 'mp-webhook-target',
            'error_code' => 'submission_outcome_unknown',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->withoutMiddleware(\App\Http\Middleware\CheckInstalled::class);
        $payload = ['external_reference' => "klassci-annonce-{$annonce->id}-courriel-{$id}", 'state' => 'delivered', 'message_id' => 'mp-webhook-target',
            'occurred_at' => '2026-10-29T12:30:00+00:00'];
        config(['services.mailpulse.announcement_webhook_secret' => 'test-signature-longue']);
        $this->postJson('/api/v1/mailpulse/announcement-events', $payload)->assertUnauthorized();

        $envoyer = function ($data) {
            $contenu = json_encode($data);
            $stamp = (string) time();
            $sig = hash_hmac('sha256', $stamp.'.'.$contenu, 'test-signature-longue');
            return $this->call('POST', '/api/v1/mailpulse/announcement-events', [], [], [], [
                'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_MAILPULSE_TIMESTAMP' => $stamp,
                'HTTP_X_MAILPULSE_SIGNATURE' => $sig,
            ], $contenu);
        };
        $this->assertSame(422, $envoyer(array_diff_key($payload, ['message_id' => true]))->getStatusCode());
        $this->assertSame(202, $envoyer(array_replace($payload, ['message_id' => 'mp-other-tenant']))->getStatusCode());
        $this->assertSame('submitted', DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->value('status'));
        $this->assertSame(200, $envoyer($payload)->getStatusCode());
        $horodatage = DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->value('provider_delivered_at');
        $this->assertSame('2026-10-29 12:30:00', $horodatage);
        $this->assertSame(200, $envoyer($payload + ['ignored' => 'not stored'])->getStatusCode());
        $this->assertSame(200, $envoyer(array_replace($payload, ['state' => 'delivery_delayed']))->getStatusCode());
        $this->assertSame(200, $envoyer(array_replace($payload, ['state' => 'bounced']))->getStatusCode());
        $this->assertSame('delivered', DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->value('status'));
        $this->assertSame($horodatage, DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->value('provider_delivered_at'));
        $this->assertNull(DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->value('provider_failed_at'));
        $this->assertNull(DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->value('error_code'));
    }
    public function test_callback_standard_mailpulse_v1_controle_ecole_et_message_et_replay(): void
    {
        $annonce = $this->annonce();
        $id = DB::table('esbtp_annonce_email_deliveries')->insertGetId([
            'annonce_id' => $annonce->id, 'email' => 'test@etudiants.test',
            'status' => 'submitted', 'submitted_at' => now(),
            'mailpulse_message_id' => 'mp-standard', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->withoutMiddleware(\App\Http\Middleware\CheckInstalled::class);
        config([
            'app.tenant_code' => 'esbtp-abidjan',
            'services.mailpulse.external_organization_id' => 'org-mailpulse-esbtp',
            'services.mailpulse.announcement_webhook_secret' => 'secret-webhook-standard',
        ]);
        $event = [
            'event_id' => 'evt-001', 'tenant_id' => 'org-mailpulse-esbtp',
            'type' => 'message.delivered', 'created_at' => '2026-10-29T12:33:00+00:00',
            'data' => [
                'occurred_at' => '2026-10-29T12:30:00+00:00',
                'message' => [
                    'id' => 'mp-standard', 'channel' => 'email', 'status' => 'delivered',
                    'external_tenant_id' => 'esbtp-abidjan',
                    'recipient' => ['type' => 'email', 'value' => 'test@etudiants.test'],
                    'metadata' => [
                        'source' => 'klassci', 'workflow_event' => 'announcement_email',
                        'external_reference' => "klassci-annonce-{$annonce->id}-courriel-{$id}",
                    ],
                ],
            ],
        ];
        $post = function (array $payload) {
            $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
            $stamp = (string) time();
            $signature = 'v1='.hash_hmac('sha256', $stamp.'.'.$body, 'secret-webhook-standard');
            return $this->call('POST', '/api/v1/mailpulse/announcement-events', [], [], [], [
                'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_MAILPULSE_TIMESTAMP' => $stamp,
                'HTTP_X_MAILPULSE_SIGNATURE' => 'v1='.str_repeat('0', 64).','.$signature,
            ], $body);
        };
        config(['app.tenant_code' => 'default']);
        $this->assertSame(202, $post($event)->getStatusCode());
        config(['app.tenant_code' => 'esbtp-abidjan']);
        $this->assertSame(202, $post(array_replace($event, ['tenant_id' => 'other-org']))->getStatusCode());
        $this->assertSame(202, $post(array_replace_recursive($event, ['data' => ['message' => ['external_tenant_id' => 'other-school']]]))->getStatusCode());
        $this->assertSame(202, $post(array_replace_recursive($event, ['data' => ['message' => ['id' => 'other-message']]]))->getStatusCode());
        $this->assertSame(202, $post(array_replace_recursive($event, ['data' => ['message' => ['recipient' => ['value' => 'wrong@etudiants.test']]]]))->getStatusCode());
        $this->assertSame('submitted', DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->value('status'));
        $this->assertSame(200, $post($event)->getStatusCode());
        $this->assertSame(200, $post($event)->getStatusCode());
        $this->assertSame('delivered', DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->value('status'));
        $this->assertSame('2026-10-29 12:30:00', DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->value('provider_delivered_at'));
    }

    public function test_reconciliation_par_id_ne_touche_pas_un_destinataire_modifie_pendant_get(): void
    {
        Bus::fake([EnvoyerAnnonceCourriel::class]);
        $annonce = $this->annonce();
        $id = DB::table('esbtp_annonce_email_deliveries')->insertGetId([
            'annonce_id' => $annonce->id, 'email' => 'origine@etudiants.test',
            'status' => 'submitted', 'submitted_at' => now(),
            'mailpulse_message_id' => 'mp-course-email', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $reader = Mockery::mock(MailPulseStatutsMessages::class);
        $reader->shouldReceive('lire')->once()->with('mp-course-email')
            ->andReturnUsing(function () use ($id) {
                DB::table('esbtp_annonce_email_deliveries')->where('id', $id)
                    ->update(['email' => 'nouveau@etudiants.test']);

                return [
                    'status' => 'delivered', 'error_code' => null,
                    'delivered_at' => now()->toIso8601String(), 'delivery_delays' => [],
                ];
            });
        $result = (new SynchroniserCourrielsAnnonce($reader))->synchroniser(2);
        $this->assertSame(0, $result['delivrees']);
        $this->assertSame(0, $result['lues']);
        $row = DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->first();
        $this->assertSame('submitted', $row->status);
        $this->assertSame('nouveau@etudiants.test', $row->email);
        $this->assertNull($row->provider_delivered_at);
        Bus::assertNotDispatched(EnvoyerAnnonceCourriel::class);
    }

    public function test_reconciliation_par_reference_ne_change_pas_un_destinataire_modifie_pendant_get(): void
    {
        Bus::fake([EnvoyerAnnonceCourriel::class]);
        config(['app.tenant_code' => 'esbtp-abidjan']);
        $annonce = $this->annonce();
        $id = DB::table('esbtp_annonce_email_deliveries')->insertGetId([
            'annonce_id' => $annonce->id, 'email' => 'origine@etudiants.test',
            'status' => 'awaiting_dispatch', 'error_code' => 'submission_outcome_unknown',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $reference = "klassci-v2-esbtp-abidjan-annonce-{$annonce->id}-courriel-{$id}";
        DB::table('esbtp_annonce_email_deliveries')->where('id', $id)
            ->update(['mailpulse_request_id' => $reference]);
        $reader = Mockery::mock(MailPulseStatutsMessages::class);
        $reader->shouldReceive('lireParReference')->once()
            ->with($reference, 'esbtp-abidjan')
            ->andReturnUsing(function () use ($id) {
                DB::table('esbtp_annonce_email_deliveries')->where('id', $id)
                    ->update(['email' => 'nouveau@etudiants.test']);

                return [
                    'id' => 'mp-course-reference', 'recipient' => 'origine@etudiants.test',
                    'status' => 'delivered', 'delivered_at' => now()->toIso8601String(),
                    'error_code' => null, 'delivery_delays' => [],
                ];
            });
        $result = (new SynchroniserCourrielsAnnonce($reader))->synchroniser(2);
        $this->assertSame(0, $result['delivrees']);
        $this->assertSame(0, $result['lues']);
        $row = DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->first();
        $this->assertSame('awaiting_dispatch', $row->status);
        $this->assertSame('nouveau@etudiants.test', $row->email);
        $this->assertNull($row->mailpulse_message_id);
        Bus::assertNotDispatched(EnvoyerAnnonceCourriel::class);
    }

    public function test_un_webhook_concurrent_ne_peut_pas_regresser_en_echec_fournisseur(): void
    {
        Bus::fake([EnvoyerAnnonceCourriel::class]);
        $annonce = $this->annonce();
        $id = DB::table('esbtp_annonce_email_deliveries')->insertGetId([
            'annonce_id' => $annonce->id, 'email' => 'preuve@etudiants.test',
            'status' => 'submitted', 'submitted_at' => now(),
            'mailpulse_message_id' => 'mp-preuve', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $reader = Mockery::mock(MailPulseStatutsMessages::class);
        $reader->shouldReceive('lire')->once()->with('mp-preuve')
            ->andReturnUsing(function () use ($id) {
                DB::table('esbtp_annonce_email_deliveries')->where('id', $id)
                    ->update(['status' => 'delivered', 'provider_delivered_at' => now()]);

                return [
                    'status' => 'failed', 'error_code' => 'email_bounced',
                    'delivered_at' => null, 'delivery_delays' => [],
                ];
            });
        $result = (new SynchroniserCourrielsAnnonce($reader))->synchroniser(2);
        $this->assertSame(0, $result['echecs']);
        $this->assertSame(0, $result['lues']);
        $row = DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->first();
        $this->assertSame('delivered', $row->status);
        $this->assertNull($row->provider_failed_at);
        Bus::assertNotDispatched(EnvoyerAnnonceCourriel::class);
    }

    public function test_la_modification_du_destinataire_avant_http_empêche_toute_soumission(): void
    {
        $annonce = $this->annonce();
        $id = DB::table('esbtp_annonce_email_deliveries')->insertGetId([
            'annonce_id' => $annonce->id, 'email' => 'origine@etudiants.test',
            'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $client = Mockery::mock(MailPulseClient::class);
        $client->shouldReceive('getSetting')->twice()
            ->andReturnUsing(function () use ($id) {
                DB::table('esbtp_annonce_email_deliveries')->where('id', $id)
                    ->update(['email' => 'nouveau@etudiants.test']);

                return '';
            });
        $client->shouldNotReceive('sendEmailMessage');
        (new EnvoyerAnnonceCourriel($id))->handle($client, new CadenceMailPulse);
        $row = DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->first();
        $this->assertSame('awaiting_dispatch', $row->status);
        $this->assertSame('pre_submission_context_changed', $row->error_code);
        $this->assertNull($row->submitted_at);
        $this->assertNull($row->mailpulse_message_id);
        $this->assertSame('nouveau@etudiants.test', $row->email);
    }

    public function test_rapprochement_mailpulse_distingue_delivre_retarde_et_rebond_sans_renvoyer(): void
    {
        Bus::fake([EnvoyerAnnonceCourriel::class]);
        $annonce = $this->annonce();
        $ids = [];
        foreach (['delivered', 'delayed', 'failed'] as $type) {
            $ids[$type] = DB::table('esbtp_annonce_email_deliveries')->insertGetId([
                'annonce_id' => $annonce->id, 'email' => "{$type}@etudiants.test",
                'status' => 'submitted', 'submitted_at' => now(),
                'mailpulse_message_id' => "mailpulse-{$type}",
                'error_code' => 'submission_outcome_unknown',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $reader = Mockery::mock(MailPulseStatutsMessages::class);
        $reader->shouldReceive('lire')->once()->with('mailpulse-delivered')
            ->andReturn(['status' => 'delivered', 'error_code' => null,
                'delivered_at' => now()->toIso8601String(), 'delivery_delays' => []]);
        $reader->shouldReceive('lire')->once()->with('mailpulse-delayed')
            ->andReturn(['status' => 'sent', 'error_code' => null,
                'delivered_at' => null, 'delivery_delays' => [
                    ['occurred_at' => now()->toIso8601String()],
                ]]);
        $reader->shouldReceive('lire')->once()->with('mailpulse-failed')
            ->andReturn(['status' => 'failed', 'error_code' => 'email_bounced',
                'delivered_at' => null, 'delivery_delays' => []]);

        $result = (new SynchroniserCourrielsAnnonce($reader))->synchroniser(3);
        $this->assertSame(3, $result['lues']);
        $this->assertSame(1, $result['delivrees']);
        $this->assertSame(1, $result['retardees']);
        $this->assertSame(1, $result['echecs']);
        foreach ($ids as $type => $id) {
            $ligne = DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->first();
            $this->assertSame($type, $ligne->status);
        }
        $this->assertSame('email_bounced',
            DB::table('esbtp_annonce_email_deliveries')->where('id', $ids['failed'])->value('error_code'));
        $this->assertNull(DB::table('esbtp_annonce_email_deliveries')->where('id', $ids['delivered'])->value('error_code'));
        $this->assertNull(DB::table('esbtp_annonce_email_deliveries')->where('id', $ids['delayed'])->value('error_code'));
        Bus::assertNotDispatched(EnvoyerAnnonceCourriel::class);
    }

    public function test_reconciliation_par_cle_retourne_la_livraison_sans_reexpedier_un_message_incertain(): void
    {
        Bus::fake([EnvoyerAnnonceCourriel::class]);
        config(['app.tenant_code' => 'esbtp-abidjan']);
        $annonce = $this->annonce();
        $id = DB::table('esbtp_annonce_email_deliveries')->insertGetId([
            'annonce_id' => $annonce->id, 'email' => 'retrouve@etudiants.test',
            'status' => 'awaiting_dispatch', 'error_code' => 'submission_outcome_unknown',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $reference = "klassci-annonce-{$annonce->id}-courriel-{$id}";
        $reader = Mockery::mock(MailPulseStatutsMessages::class);
        $reader->shouldReceive('lireParReference')->once()
            ->with($reference, 'esbtp-abidjan')
            ->andReturn([
                'id' => 'mp-retrouve', 'recipient' => 'retrouve@etudiants.test',
                'status' => 'delivered', 'delivered_at' => '2026-10-30T01:00:00+00:00',
                'error_code' => null, 'delivery_delays' => [],
            ]);
        $result = (new SynchroniserCourrielsAnnonce($reader))->synchroniser(2);
        $this->assertSame(1, $result['lues']);
        $this->assertSame(1, $result['delivrees']);
        $ligne = DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->first();
        $this->assertSame('mp-retrouve', $ligne->mailpulse_message_id);
        $this->assertSame('delivered', $ligne->status);
        $this->assertSame('2026-10-30 01:00:00', $ligne->provider_delivered_at);
        Bus::assertNotDispatched(EnvoyerAnnonceCourriel::class);
    }

    public function test_reconciliation_sans_preuve_ou_mauvais_destinataire_ne_relance_pas(): void
    {
        Bus::fake([EnvoyerAnnonceCourriel::class]);
        config(['app.tenant_code' => 'esbtp-abidjan']);
        $annonce = $this->annonce();
        $id = DB::table('esbtp_annonce_email_deliveries')->insertGetId([
            'annonce_id' => $annonce->id, 'email' => 'original@etudiants.test',
            'status' => 'awaiting_dispatch', 'error_code' => 'queue_outcome_unknown',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $reference = "klassci-annonce-{$annonce->id}-courriel-{$id}";
        $reader = Mockery::mock(MailPulseStatutsMessages::class);
        $reader->shouldReceive('lireParReference')->twice()->with($reference, 'esbtp-abidjan')
            ->andReturn('introuvable', [
                'id' => 'mp-ailleurs', 'recipient' => 'autre@etudiants.test',
                'status' => 'delivered', 'delivered_at' => now()->toIso8601String(),
            ]);
        $service = new SynchroniserCourrielsAnnonce($reader);
        $this->assertSame(1, $service->synchroniser(2)['sans_preuve']);
        $this->assertSame(1, $service->synchroniser(2)['erreurs']);
        $ligne = DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->first();
        $this->assertSame('awaiting_dispatch', $ligne->status);
        $this->assertNull($ligne->mailpulse_message_id);
        Bus::assertNotDispatched(EnvoyerAnnonceCourriel::class);
    }

    public function test_synchronisation_ne_devine_pas_la_livraison_et_ignore_un_ancien_envoi_sans_id_mailpulse(): void
    {
        $annonce = $this->annonce();
        foreach (['legacy', 'current'] as $kind) {
            DB::table('esbtp_annonce_email_deliveries')->insert([
                'annonce_id' => $annonce->id, 'email' => "{$kind}@etudiants.test",
                'status' => 'submitted', 'submitted_at' => now(),
                'mailpulse_message_id' => $kind === 'current' ? 'mailpulse-current' : null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $reader = Mockery::mock(MailPulseStatutsMessages::class);
        $reader->shouldReceive('lire')->once()->with('mailpulse-current')
            ->andReturn(['status' => 'sent', 'error_code' => null,
                'delivered_at' => null, 'delivery_delays' => []]);
        $result = (new SynchroniserCourrielsAnnonce($reader))->synchroniser(10);
        $this->assertSame(1, $result['lues']);
        $this->assertSame(0, $result['delivrees']);
        $this->assertSame(2, DB::table('esbtp_annonce_email_deliveries')->where('status', 'submitted')->count());
    }

    public function test_une_reponse_reseau_incertaine_met_en_quarantaine_sans_renvoi(): void
    {
        $annonce = $this->annonce();
        $id = DB::table('esbtp_annonce_email_deliveries')->insertGetId([
            'annonce_id' => $annonce->id, 'email' => 'incertain@etudiants.test',
            'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $client = Mockery::mock(MailPulseClient::class);
        $client->shouldReceive('getSetting')->twice()->andReturn('');
        $client->shouldReceive('sendEmailMessage')->once()
            ->andReturn(new MailPulseResult(false, 'connection_failed', null, 'reference-incertaine'));
        $job = new EnvoyerAnnonceCourriel($id);
        $job->handle($client, new CadenceMailPulse);
        $job->handle($client, new CadenceMailPulse);

        $ligne = DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->first();
        $this->assertSame('awaiting_dispatch', $ligne->status);
        $this->assertSame('submission_outcome_unknown', $ligne->error_code);
        $this->assertSame("klassci-v2-presentation-annonce-{$annonce->id}-courriel-{$id}", $ligne->mailpulse_request_id);
        $this->assertNull($ligne->provider_failed_at);
        $this->assertNull($ligne->submitted_at);
        $this->assertSame(1, (int) $ligne->attempts);
    }

    public function test_exception_pendant_le_transport_ne_reexpedie_pas_un_email_incertain(): void
    {
        $annonce = $this->annonce();
        $id = DB::table('esbtp_annonce_email_deliveries')->insertGetId([
            'annonce_id' => $annonce->id, 'email' => 'exception@etudiants.test',
            'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $client = Mockery::mock(MailPulseClient::class);
        $client->shouldReceive('getSetting')->twice()->andReturn('');
        $client->shouldReceive('sendEmailMessage')->once()
            ->andThrow(new \RuntimeException('remote response lost'));
        $job = new EnvoyerAnnonceCourriel($id);
        $job->handle($client, new CadenceMailPulse);
        $job->handle($client, new CadenceMailPulse);
        $this->assertSame('awaiting_dispatch',
            DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->value('status'));
        $this->assertSame('submission_outcome_unknown',
            DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->value('error_code'));
    }

    public function test_acceptation_sans_identifiant_message_ne_devient_pas_faussement_soumise(): void
    {
        $annonce = $this->annonce();
        $id = DB::table('esbtp_annonce_email_deliveries')->insertGetId([
            'annonce_id' => $annonce->id, 'email' => 'sans-id@etudiants.test',
            'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $client = Mockery::mock(MailPulseClient::class);
        $client->shouldReceive('getSetting')->twice()->andReturn('');
        $client->shouldReceive('sendEmailMessage')->once()
            ->andReturn(new MailPulseResult(true, 'accepted', 201, 'r-sans-id', null, null, null, null, 'accepted'));
        (new EnvoyerAnnonceCourriel($id))->handle($client, new CadenceMailPulse);
        $ligne = DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->first();
        $this->assertSame('awaiting_dispatch', $ligne->status);
        $this->assertNull($ligne->submitted_at);
        $this->assertSame('submission_outcome_unknown', $ligne->error_code);
    }

    public function test_avant_http_le_job_est_deja_en_quarantaine_et_ne_regresse_pas_un_terminal(): void
    {
        $annonce = $this->annonce();
        $id = DB::table('esbtp_annonce_email_deliveries')->insertGetId([
            'annonce_id' => $annonce->id, 'email' => 'sans-doublon@etudiants.test',
            'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $client = Mockery::mock(MailPulseClient::class);
        $client->shouldReceive('getSetting')->twice()->andReturn('');
        $client->shouldReceive('sendEmailMessage')->once()
            ->andReturnUsing(function () use ($id) {
                $ligne = DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->first();
                $this->assertSame('awaiting_dispatch', $ligne->status);
                $this->assertSame('submission_outcome_unknown', $ligne->error_code);
                $this->assertNull($ligne->submitted_at);
                // A concurrent confirmed provider outcome wins over a stale
                // response from this worker; never regress a terminal state.
                DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->update([
                    'status' => 'delivered', 'provider_delivered_at' => now(),
                ]);
                return new MailPulseResult(true, 'accepted', 202, 'req-test', 'mp-1', null, null, null, 'accepted');
            });
        (new EnvoyerAnnonceCourriel($id))->handle($client, new CadenceMailPulse);
        (new EnvoyerAnnonceCourriel($id))->handle($client, new CadenceMailPulse);
        $this->assertSame('delivered', DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->value('status'));
    }

    public function test_un_worker_perdu_est_isole_sans_relance_mais_un_worker_actif_reste_intact(): void
    {
        Bus::fake([EnvoyerAnnonceCourriel::class]);
        config(['app.tenant_code' => 'esbtp-abidjan', 'services.mailpulse.announcement_stale_minutes' => 15]);
        $annonce = $this->annonce();
        $ids = [];
        foreach (['ancien' => 20, 'recent' => 5] as $nom => $minutes) {
            $ids[$nom] = DB::table('esbtp_annonce_email_deliveries')->insertGetId([
                'annonce_id' => $annonce->id, 'email' => "{$nom}@etudiants.test",
                'status' => 'processing', 'processing_at' => now()->subMinutes($minutes),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $reader = Mockery::mock(MailPulseStatutsMessages::class);
        $reader->shouldReceive('lireParReference')->once()
            ->with("klassci-annonce-{$annonce->id}-courriel-{$ids['ancien']}", 'esbtp-abidjan')
            ->andReturn('introuvable');
        $bilan = (new SynchroniserCourrielsAnnonce($reader))->synchroniser(5);
        $this->assertSame(1, $bilan['quarantaines']);
        $this->assertSame(1, $bilan['sans_preuve']);
        $this->assertSame('awaiting_dispatch', DB::table('esbtp_annonce_email_deliveries')->where('id', $ids['ancien'])->value('status'));
        $this->assertSame('processing', DB::table('esbtp_annonce_email_deliveries')->where('id', $ids['recent'])->value('status'));
        Bus::assertNotDispatched(EnvoyerAnnonceCourriel::class);
    }

    public function test_quarantaine_workers_est_bornee_par_le_budget_de_lot(): void
    {
        Bus::fake([EnvoyerAnnonceCourriel::class]);
        config(['app.tenant_code' => 'esbtp-abidjan', 'services.mailpulse.announcement_stale_minutes' => 15]);
        $annonce = $this->annonce();
        for ($i = 0; $i < 3; $i++) {
            DB::table('esbtp_annonce_email_deliveries')->insert([
                'annonce_id' => $annonce->id, 'email' => "bloque-{$i}@etudiants.test",
                'status' => 'processing', 'processing_at' => now()->subMinutes(20),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $reader = Mockery::mock(MailPulseStatutsMessages::class);
        $reader->shouldReceive('lireParReference')->once()->andReturn('introuvable');
        $result = (new SynchroniserCourrielsAnnonce($reader))->synchroniser(1);
        $this->assertSame(1, $result['quarantaines']);
        $this->assertSame(1, $result['sans_preuve']);
        $this->assertSame(2, DB::table('esbtp_annonce_email_deliveries')->where('status', 'processing')->count());
        Bus::assertNotDispatched(EnvoyerAnnonceCourriel::class);
    }

    public function test_code_tenant_non_configure_bloque_avant_tout_appel_mailpulse(): void
    {
        config(['app.tenant_code' => 'default']);
        $annonce = $this->annonce();
        $id = DB::table('esbtp_annonce_email_deliveries')->insertGetId([
            'annonce_id' => $annonce->id, 'email' => 'protection@etudiants.test',
            'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $client = Mockery::mock(MailPulseClient::class);
        $client->shouldNotReceive('sendEmailMessage');
        (new EnvoyerAnnonceCourriel($id))->handle($client, new CadenceMailPulse);
        $ligne = DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->first();
        $this->assertSame('awaiting_dispatch', $ligne->status);
        $this->assertSame('tenant_unconfigured', $ligne->error_code);
        $this->assertNull($ligne->provider_failed_at);
        $this->assertSame(0, (int) $ligne->attempts);
    }

    public function test_un_refus_local_de_configuration_n_est_pas_un_echec_fournisseur(): void
    {
        $annonce = $this->annonce();
        $id = DB::table('esbtp_annonce_email_deliveries')->insertGetId([
            'annonce_id' => $annonce->id, 'email' => 'cle-absente@etudiants.test',
            'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $client = Mockery::mock(MailPulseClient::class);
        $client->shouldReceive('getSetting')->twice()->andReturn('');
        $client->shouldReceive('sendEmailMessage')->once()
            ->andReturn(new MailPulseResult(false, 'missing_api_key', null, 'req-manquante'));
        (new EnvoyerAnnonceCourriel($id))->handle($client, new CadenceMailPulse);
        $ligne = DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->first();
        $this->assertSame('awaiting_dispatch', $ligne->status);
        $this->assertSame('missing_api_key', $ligne->error_code);
        $this->assertNull($ligne->provider_failed_at);
        $this->assertNull($ligne->submitted_at);
    }

    public function test_references_versionnees_sont_isolees_par_ecole_et_legacy_reste_identique(): void
    {
        $ligne = (object) ['annonce_id' => 12, 'id' => 581, 'attempts' => 0, 'mailpulse_request_id' => null];
        $abidjan = \App\Services\Annonces\ReferenceCourrielAnnonce::pourEnvoi($ligne, 'esbtp-abidjan');
        $yakro = \App\Services\Annonces\ReferenceCourrielAnnonce::pourEnvoi($ligne, 'esbtp-yakro');
        $this->assertSame('klassci-v2-esbtp-abidjan-annonce-12-courriel-581', $abidjan);
        $this->assertSame('klassci-v2-esbtp-yakro-annonce-12-courriel-581', $yakro);
        $this->assertNotSame($abidjan, $yakro);
        $this->assertSame('klassci-annonce-12-courriel-581',
            \App\Services\Annonces\ReferenceCourrielAnnonce::pourEnvoi((object) ['annonce_id' => 12, 'id' => 581, 'attempts' => 1, 'mailpulse_request_id' => null], 'esbtp-abidjan'));
        $ligne->mailpulse_request_id = $abidjan;
        $ligne->attempts = 3;
        $this->assertSame($abidjan, \App\Services\Annonces\ReferenceCourrielAnnonce::pourEnvoi($ligne, 'esbtp-abidjan'));
        $this->assertNull(\App\Services\Annonces\ReferenceCourrielAnnonce::pourEnvoi($ligne, 'esbtp-yakro'));
        $this->assertSame($abidjan, \App\Services\Annonces\ReferenceCourrielAnnonce::pourRapprochement($ligne, 'esbtp-abidjan'));
        $this->assertNull(\App\Services\Annonces\ReferenceCourrielAnnonce::pourRapprochement($ligne, 'esbtp-yakro'));
    }

    public function test_ref_v2_est_stockee_avant_http_et_reste_stable_apres_une_reponse_incertaine(): void
    {
        $annonce = $this->annonce();
        $id = DB::table('esbtp_annonce_email_deliveries')->insertGetId([
            'annonce_id' => $annonce->id, 'email' => 'v2@etudiants.test',
            'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $reference = "klassci-v2-presentation-annonce-{$annonce->id}-courriel-{$id}";
        $client = Mockery::mock(MailPulseClient::class);
        $client->shouldReceive('getSetting')->twice()->andReturn('');
        $client->shouldReceive('sendEmailMessage')->once()
            ->withArgs(function ($charge, $key) use ($id, $reference) {
                $this->assertSame($reference, $key);
                $this->assertSame($reference, $charge['metadata']['external_reference']);
                $this->assertSame($reference,
                    DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->value('mailpulse_request_id'));
                return true;
            })->andReturn(new MailPulseResult(false, 'connection_failed', null, 'external-request-id'));
        (new EnvoyerAnnonceCourriel($id))->handle($client, new CadenceMailPulse);
        (new EnvoyerAnnonceCourriel($id))->handle($client, new CadenceMailPulse);
        $row = DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->first();
        $this->assertSame('awaiting_dispatch', $row->status);
        $this->assertSame($reference, $row->mailpulse_request_id);
        $this->assertSame(1, (int) $row->attempts);
    }

    public function test_le_rapprochement_v2_consulte_la_cle_originale_sans_post(): void
    {
        Bus::fake([EnvoyerAnnonceCourriel::class]);
        config(['app.tenant_code' => 'esbtp-abidjan']);
        $annonce = $this->annonce();
        $id = DB::table('esbtp_annonce_email_deliveries')->insertGetId([
            'annonce_id' => $annonce->id, 'email' => 'v2@etudiants.test',
            'status' => 'awaiting_dispatch', 'error_code' => 'submission_outcome_unknown',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $reference = "klassci-v2-esbtp-abidjan-annonce-{$annonce->id}-courriel-{$id}";
        DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->update(['mailpulse_request_id' => $reference]);
        $reader = Mockery::mock(MailPulseStatutsMessages::class);
        $reader->shouldReceive('lireParReference')->once()
            ->with($reference, 'esbtp-abidjan')->andReturn([
                'id' => 'mp-v2', 'recipient' => 'v2@etudiants.test',
                'status' => 'delivered', 'delivered_at' => '2026-10-30T01:00:00+00:00',
                'error_code' => null, 'delivery_delays' => [],
            ]);
        $outcome = (new SynchroniserCourrielsAnnonce($reader))->synchroniser(2);
        $this->assertSame(1, $outcome['delivrees']);
        $this->assertSame('delivered', DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->value('status'));
        $this->assertSame($reference, DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->value('mailpulse_request_id'));
        Bus::assertNotDispatched(EnvoyerAnnonceCourriel::class);
    }

    public function test_les_references_generiques_ou_a_identifiants_debordants_sont_rejetees(): void
    {
        $service = \App\Services\Annonces\ReferenceCourrielAnnonce::class;
        $this->assertNull($service::extraire('klassci-annonce-1-courriel-1', 'default'));
        $this->assertNull($service::extraire('klassci-annonce-1-courriel-1', ''));
        $this->assertNull($service::extraire('klassci-annonce-999999999999999999999-courriel-1', 'presentation'));
        $this->assertNull($service::extraire('klassci-v2-presentation-annonce-1-courriel-999999999999999999999', 'presentation'));
        $this->assertSame(['annonce_id' => 1, 'livraison_id' => 1, 'v2' => false],
            $service::extraire('klassci-annonce-1-courriel-1', 'presentation'));
    }

    public function test_le_refus_429_utilise_la_meme_reference_v2_au_second_essai(): void
    {
        $annonce = $this->annonce();
        $id = DB::table('esbtp_annonce_email_deliveries')->insertGetId([
            'annonce_id' => $annonce->id, 'email' => 'deux-essais@etudiants.test',
            'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $reference = "klassci-v2-presentation-annonce-{$annonce->id}-courriel-{$id}";
        $client = Mockery::mock(MailPulseClient::class);
        $client->shouldReceive('getSetting')->times(4)->andReturn('');
        $client->shouldReceive('sendEmailMessage')->twice()
            ->withArgs(function ($charge, $key) use ($reference) {
                $this->assertSame($reference, $key);
                $this->assertSame($reference, $charge['metadata']['external_reference']);
                return true;
            })
            ->andReturn(
                new MailPulseResult(false, 'rate_limited', 429, 'req-429'),
                new MailPulseResult(true, 'accepted', 201, 'req-ok', 'mp-apres-429', null, null, null, 'accepted')
            );
        $job = new EnvoyerAnnonceCourriel($id);
        $job->handle($client, new CadenceMailPulse);
        $this->assertSame('retrying', DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->value('status'));
        $this->assertSame($reference, DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->value('mailpulse_request_id'));
        $job->handle($client, new CadenceMailPulse);
        $row = DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->first();
        $this->assertSame('submitted', $row->status);
        $this->assertSame($reference, $row->mailpulse_request_id);
        $this->assertSame('mp-apres-429', $row->mailpulse_message_id);
        $this->assertSame(2, (int) $row->attempts);
    }

    public function test_callback_v2_refuse_autre_tenant_et_reference_ancienne_sur_ligne_v2(): void
    {
        $annonce = $this->annonce();
        $id = DB::table('esbtp_annonce_email_deliveries')->insertGetId([
            'annonce_id' => $annonce->id, 'email' => 'v2-signature@etudiants.test',
            'status' => 'submitted', 'submitted_at' => now(),
            'mailpulse_message_id' => 'mp-signature',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $ref = "klassci-v2-esbtp-abidjan-annonce-{$annonce->id}-courriel-{$id}";
        DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->update(['mailpulse_request_id' => $ref]);
        config([
            'app.tenant_code' => 'esbtp-abidjan',
            'services.mailpulse.external_organization_id' => 'org-test',
            'services.mailpulse.announcement_webhook_secret' => 'secret-v2',
        ]);
        $this->withoutMiddleware(\App\Http\Middleware\CheckInstalled::class);
        $event = [
            'event_id' => 'evt-v2', 'tenant_id' => 'org-test',
            'type' => 'message.delivered', 'created_at' => '2026-10-30T02:00:00+00:00',
            'data' => ['message' => [
                'id' => 'mp-signature', 'channel' => 'email', 'status' => 'delivered',
                'external_tenant_id' => 'esbtp-abidjan',
                'recipient' => ['type' => 'email', 'value' => 'v2-signature@etudiants.test'],
                'metadata' => [
                    'source' => 'klassci', 'workflow_event' => 'announcement_email',
                    'external_reference' => $ref,
                ],
            ]],
        ];
        $post = function (array $body) {
            $raw = json_encode($body, JSON_UNESCAPED_SLASHES);
            $timestamp = (string) time();
            return $this->call('POST', '/api/v1/mailpulse/announcement-events', [], [], [], [
                'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_MAILPULSE_TIMESTAMP' => $timestamp,
                'HTTP_X_MAILPULSE_SIGNATURE' => 'v1='.hash_hmac('sha256', $timestamp.'.'.$raw, 'secret-v2'),
            ], $raw);
        };
        $legacy = "klassci-annonce-{$annonce->id}-courriel-{$id}";
        $this->assertSame(202, $post(array_replace_recursive($event, ['data' => ['message' => ['metadata' => ['external_reference' => $legacy]]]]))->getStatusCode());
        $wrong = "klassci-v2-esbtp-yakro-annonce-{$annonce->id}-courriel-{$id}";
        $this->assertSame(202, $post(array_replace_recursive($event, ['data' => ['message' => ['metadata' => ['external_reference' => $wrong]]]]))->getStatusCode());
        $this->assertSame('submitted', DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->value('status'));
        $this->assertSame(200, $post($event)->getStatusCode());
        $this->assertSame(200, $post($event)->getStatusCode());
        $this->assertSame('delivered', DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->value('status'));
    }

    public function test_un_get_ancien_n_ecrase_pas_une_reference_modifiee_pendant_le_reseau(): void
    {
        Bus::fake([EnvoyerAnnonceCourriel::class]);
        config(['app.tenant_code' => 'esbtp-abidjan']);
        $annonce = $this->annonce();
        $id = DB::table('esbtp_annonce_email_deliveries')->insertGetId([
            'annonce_id' => $annonce->id, 'email' => 'race@etudiants.test',
            'status' => 'awaiting_dispatch', 'error_code' => 'submission_outcome_unknown',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $ref = "klassci-v2-esbtp-abidjan-annonce-{$annonce->id}-courriel-{$id}";
        DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->update(['mailpulse_request_id' => $ref]);
        $reader = Mockery::mock(MailPulseStatutsMessages::class);
        $reader->shouldReceive('lireParReference')->once()
            ->with($ref, 'esbtp-abidjan')
            ->andReturnUsing(function () use ($id) {
                // A competing administrative correction changed ownership
                // while MailPulse was being queried.
                DB::table('esbtp_annonce_email_deliveries')->where('id', $id)
                    ->update(['mailpulse_request_id' => 'klassci-v2-esbtp-yakro-annonce-9-courriel-9']);
                return [
                    'id' => 'mp-foreign', 'recipient' => 'race@etudiants.test',
                    'status' => 'delivered', 'delivered_at' => now()->toIso8601String(),
                    'error_code' => null, 'delivery_delays' => [],
                ];
            });
        $result = (new SynchroniserCourrielsAnnonce($reader))->synchroniser(2);
        $this->assertSame(0, $result['delivrees']);
        $row = DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->first();
        $this->assertSame('awaiting_dispatch', $row->status);
        $this->assertNull($row->mailpulse_message_id);
        Bus::assertNotDispatched(EnvoyerAnnonceCourriel::class);
    }

    public function test_epuisement_queue_ne_devient_pas_faussement_un_echec_fournisseur(): void
    {
        $annonce = $this->annonce();
        $id = DB::table('esbtp_annonce_email_deliveries')->insertGetId([
            'annonce_id' => $annonce->id, 'email' => 'reconciliation@etudiants.test',
            'status' => 'retrying', 'created_at' => now(), 'updated_at' => now(),
        ]);
        (new EnvoyerAnnonceCourriel($id))->failed(new \RuntimeException('worker timeout'));
        $ligne = DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->first();
        $this->assertSame('awaiting_dispatch', $ligne->status);
        $this->assertSame('queue_outcome_unknown', $ligne->error_code);
        $this->assertNull($ligne->provider_failed_at);
    }

}
