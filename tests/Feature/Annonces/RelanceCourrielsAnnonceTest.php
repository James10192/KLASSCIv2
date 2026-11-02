<?php

namespace Tests\Feature\Annonces;

use App\Jobs\Annonces\EnvoyerTentativeAnnonceCourriel;
use App\Models\ESBTPAnnonce;
use App\Services\Annonces\ReferenceCourrielAnnonce;
use App\Services\Annonces\RelanceCourrielsAnnonce;
use App\Services\MailPulse\MailPulseStatutsMessages;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

final class RelanceCourrielsAnnonceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['app.tenant_code' => 'presentation', 'queue.default' => 'database']);
        Cache::put('setting_annonces_email_retry_enabled', '1', 3600);
        Cache::put('setting_annonces_email_retry_max_batch', '10', 3600);
    }

    private function annonce(): ESBTPAnnonce
    {
        return ESBTPAnnonce::create([
            'titre' => 'Annonce de test', 'contenu' => 'Informations importantes.',
            'type' => 'general', 'priorite' => 0,
            'is_published' => true, 'email_notification_enabled' => true,
            'date_publication' => now()->subMinute(),
            'date_expiration' => now()->addDay(),
        ]);
    }

    private function delivery(ESBTPAnnonce $annonce, string $email, string $erreur): int
    {
        $id = DB::table('esbtp_annonce_email_deliveries')->insertGetId([
            'annonce_id' => $annonce->id,
            'email' => $email, 'status' => 'failed',
            'error_code' => $erreur,
            'attempts' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->update([
            'mailpulse_request_id' => ReferenceCourrielAnnonce::nouvelle('presentation', $annonce->id, $id),
            'mailpulse_message_id' => 'mp-'.$id,
        ]);
        return $id;
    }

    public function test_la_migration_conserve_un_historique_append_only_et_une_cle_unique_par_tentative(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('esbtp_annonce_email_attempts'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('esbtp_annonce_email_attempt_events'));
    }

    public function test_les_rebonds_ne_creent_aucune_tentative_ni_job(): void
    {
        Bus::fake([EnvoyerTentativeAnnonceCourriel::class]);
        $annonce = $this->annonce();
        $id = $this->delivery($annonce, 'bounced@example.test', 'email_bounced');
        $reader = Mockery::mock(MailPulseStatutsMessages::class);
        $reader->shouldNotReceive('lireParReference');
        $bilan = (new RelanceCourrielsAnnonce($reader))
            ->programmer($annonce, [$id], 1, (string) Str::uuid());

        $this->assertSame(0, $bilan['acceptes']);
        $this->assertSame(1, $bilan['exclus']);
        $this->assertSame(0, DB::table('esbtp_annonce_email_attempts')->count());
        Bus::assertNotDispatched(EnvoyerTentativeAnnonceCourriel::class);
    }

    public function test_un_refus_prouve_avant_resend_permet_un_seul_nouvel_essai_idempotent(): void
    {
        Bus::fake([EnvoyerTentativeAnnonceCourriel::class]);
        $annonce = $this->annonce();
        $id = $this->delivery($annonce, 'etudiant@example.test', 'template_not_configured');
        $reference = ReferenceCourrielAnnonce::nouvelle('presentation', $annonce->id, $id);
        $reader = Mockery::mock(MailPulseStatutsMessages::class);
        $reader->shouldReceive('lireParReference')->once()->with($reference, 'presentation')
            ->andReturn([
                'id' => 'mp-'.$id, 'recipient' => 'etudiant@example.test',
                'status' => 'failed', 'error_code' => 'template_not_configured',
            ]);
        $service = new RelanceCourrielsAnnonce($reader);
        $token = (string) Str::uuid();

        $first = $service->programmer($annonce, [$id], 1, $token);
        $this->assertSame(1, $first['acceptes']);
        $this->assertSame(2, DB::table('esbtp_annonce_email_attempts')->count());
        $this->assertSame(1, DB::table('esbtp_annonce_email_attempt_events')->count());
        $attempt = DB::table('esbtp_annonce_email_attempts')
            ->where('delivery_id', $id)->where('attempt_number', 2)->first();
        $this->assertSame(ReferenceCourrielAnnonce::tentative('presentation', $annonce->id, $id, 2),
            $attempt->external_reference);
        $this->assertSame($reference, $attempt->previous_reference);
        $this->assertSame('queued', $attempt->status);
        Bus::assertDispatchedTimes(EnvoyerTentativeAnnonceCourriel::class, 1);

        $again = $service->programmer($annonce, [$id], 1, $token);
        $this->assertSame(1, $again['dupliques']);
        $this->assertSame(2, DB::table('esbtp_annonce_email_attempts')->count());
        Bus::assertDispatchedTimes(EnvoyerTentativeAnnonceCourriel::class, 1);
    }

    public function test_une_reponse_inconnue_une_mauvaise_adresse_ou_un_tenant_incorrect_ne_passe_pas(): void
    {
        Bus::fake([EnvoyerTentativeAnnonceCourriel::class]);
        $annonce = $this->annonce();
        $id = $this->delivery($annonce, 'etudiant@example.test', 'template_not_configured');
        $reader = Mockery::mock(MailPulseStatutsMessages::class);
        $reader->shouldReceive('lireParReference')->once()->andReturn('introuvable');
        $service = new RelanceCourrielsAnnonce($reader);
        $this->assertSame(1, $service->programmer($annonce, [$id], 1, (string) Str::uuid())['exclus']);
        config(['app.tenant_code' => 'esbtp-yakro']);
        $this->assertSame(1, $service->programmer($annonce, [$id], 1, (string) Str::uuid())['exclus']);
        Bus::assertNotDispatched(EnvoyerTentativeAnnonceCourriel::class);
    }

    public function test_sync_ou_commutateur_inactif_interdit_toute_reservation(): void
    {
        Bus::fake([EnvoyerTentativeAnnonceCourriel::class]);
        $annonce = $this->annonce();
        $id = $this->delivery($annonce, 'etudiant@example.test', 'template_not_configured');
        $reader = Mockery::mock(MailPulseStatutsMessages::class);
        $reader->shouldNotReceive('lireParReference');
        $service = new RelanceCourrielsAnnonce($reader);
        config(['queue.default' => 'sync']);
        $this->assertSame('relance_non_disponible',
            $service->programmer($annonce, [$id], 1, (string) Str::uuid())['motif']);
        Bus::assertNotDispatched(EnvoyerTentativeAnnonceCourriel::class);
    }
}
