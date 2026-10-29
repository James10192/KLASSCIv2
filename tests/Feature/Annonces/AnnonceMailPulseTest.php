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
                $this->assertSame("klassci-annonce-{$annonce->id}-courriel-{$id}", $cle);
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
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->withoutMiddleware(\App\Http\Middleware\CheckInstalled::class);
        $payload = ['external_reference' => "klassci-annonce-{$annonce->id}-courriel-{$id}", 'state' => 'delivered'];
        config(['services.mailpulse.announcement_webhook_secret' => 'test-signature-longue']);
        $this->postJson('/api/v1/mailpulse/announcement-events', $payload)->assertUnauthorized();

        $envoyer = function ($data) {
            $contenu = json_encode($data);
            $stamp = (string) time();
            $sig = hash_hmac('sha256', $stamp.'.'.$contenu, 'test-signature-longue');
            return $this->call('POST', '/api/v1/mailpulse/announcement-events', [], [], [], [
                'CONTENT_TYPE' => 'application/json', 'HTTP_X_MAILPULSE_TIMESTAMP' => $stamp,
                'HTTP_X_MAILPULSE_SIGNATURE' => $sig,
            ], $contenu);
        };
        $this->assertSame(200, $envoyer($payload)->getStatusCode());
        $this->assertSame(200, $envoyer($payload + ['ignored' => 'not stored'])->getStatusCode());
        $this->assertSame(200, $envoyer(array_replace($payload, ['state' => 'delivery_delayed']))->getStatusCode());
        $this->assertSame('delivered', DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->value('status'));
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
