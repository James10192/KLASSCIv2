<?php

namespace Tests\Unit\RendezVous;

use App\Contracts\PorteurDeRendezVous;
use App\Enums\CanalConvocationRdv;
use App\Enums\StatutConvocationRdv;
use App\Models\ESBTPRdvCreneau;
use App\Services\MailPulse\MailPulseResult;
use App\Services\RendezVous\CourrielConvocationRdv;
use App\Services\RendezVous\MessagerieRdv;
use App\Services\RendezVous\WhatsAppConvocationRdv;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class ConvocationsRdvMulticanalTest extends TestCase
{
    private int $creneauId;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');
        Cache::flush();

        Schema::create('esbtp_rdv_creneaux', function (Blueprint $t) {
            $t->id();
            $t->date('date');
            $t->time('heure_debut');
            $t->time('heure_fin');
            $t->unsignedInteger('capacite');
            $t->boolean('ouvert')->default(true);
            $t->timestamps();
        });

        Schema::create('esbtp_rdv_reservations', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('creneau_id');
            $t->unsignedBigInteger('candidature_id')->nullable();
            $t->unsignedBigInteger('reinscription_demande_id')->nullable();
            $t->string('statut', 20);
            $t->string('nom', 100);
            $t->string('prenoms', 150);
            $t->string('telephone', 30);
            $t->date('date_naissance');
            $t->string('email', 150)->nullable();
            $t->timestamp('libere_at')->nullable();
            $t->string('convocation_statut', 20)->nullable();
            $t->string('convocation_action', 12)->nullable();
            $t->unsignedTinyInteger('convocation_tentatives')->default(0);
            $t->timestamp('convocation_envoyee_at')->nullable();
            $t->string('convocation_erreur', 255)->nullable();
            $t->string('convocation_message_id', 100)->nullable();
            $t->string('convocation_canal', 20)->nullable();
            $t->string('convocation_destination_masquee', 180)->nullable();
            $t->boolean('convocation_fallback_utilise')->default(false);
            $t->unsignedBigInteger('prevenue_par')->nullable();
            $t->timestamps();
        });

        $this->creneauId = ESBTPRdvCreneau::create([
            'date' => now()->addDays(3)->toDateString(),
            'heure_debut' => '09:00:00',
            'heure_fin' => '09:30:00',
            'capacite' => 10,
            'ouvert' => true,
        ])->id;
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        Mockery::close();
        parent::tearDown();
    }

    public function test_sans_email_joignable_la_convocation_part_sur_whatsapp(): void
    {
        $courriel = Mockery::mock(CourrielConvocationRdv::class);
        $courriel->shouldNotReceive('expedier');
        $this->app->instance(CourrielConvocationRdv::class, $courriel);

        $whatsapp = Mockery::mock(WhatsAppConvocationRdv::class);
        $whatsapp->shouldReceive('expedier')->once()->andReturn(
            new MailPulseResult(true, 'queued', 202, null, 'wa-1', dispatchState: 'accepted')
        );
        $this->app->instance(WhatsAppConvocationRdv::class, $whatsapp);

        $r = $this->reservation(['email' => 'adresse-invalide']);

        $this->assertNull(app(MessagerieRdv::class)->envoyer($r));

        $r->refresh();
        $this->assertSame(StatutConvocationRdv::Envoyee, $r->convocation_statut);
        $this->assertSame(CanalConvocationRdv::Whatsapp, $r->convocation_canal);
        $this->assertSame('wa-1', $r->convocation_message_id);
        $this->assertFalse($r->convocation_fallback_utilise);
        $this->assertStringEndsWith('0000', (string) $r->convocation_destination_masquee);
    }

    public function test_refus_definitif_email_bascule_sur_whatsapp(): void
    {
        $courriel = Mockery::mock(CourrielConvocationRdv::class);
        $courriel->shouldReceive('expedier')->once()->andReturn(new MailPulseResult(
            false,
            'provider_error',
            422,
            null,
            null,
            'EMAIL_RECIPIENT_REJECTED',
            'Adresse refusée.',
            null,
            'failed',
        ));
        $this->app->instance(CourrielConvocationRdv::class, $courriel);

        $whatsapp = Mockery::mock(WhatsAppConvocationRdv::class);
        $whatsapp->shouldReceive('expedier')->once()->andReturn(
            new MailPulseResult(true, 'queued', 202, null, 'wa-fallback', dispatchState: 'accepted')
        );
        $this->app->instance(WhatsAppConvocationRdv::class, $whatsapp);

        $r = $this->reservation();

        app(MessagerieRdv::class)->envoyer($r);

        $r->refresh();
        $this->assertSame(StatutConvocationRdv::Envoyee, $r->convocation_statut);
        $this->assertSame(CanalConvocationRdv::Whatsapp, $r->convocation_canal);
        $this->assertTrue($r->convocation_fallback_utilise);
        $this->assertSame('wa-fallback', $r->convocation_message_id);
    }

    public function test_panne_globale_email_ne_double_pas_la_panne_sur_whatsapp(): void
    {
        $courriel = Mockery::mock(CourrielConvocationRdv::class);
        $courriel->shouldReceive('expedier')->once()->andReturn(new MailPulseResult(
            false,
            'provider_unavailable',
            503,
            null,
            null,
            'provider_unavailable',
            'MailPulse indisponible.',
        ));
        $this->app->instance(CourrielConvocationRdv::class, $courriel);

        $whatsapp = Mockery::mock(WhatsAppConvocationRdv::class);
        $whatsapp->shouldNotReceive('expedier');
        $this->app->instance(WhatsAppConvocationRdv::class, $whatsapp);

        $r = $this->reservation();

        $bloque = app(MessagerieRdv::class)->envoyer($r);

        $this->assertNotNull($bloque);
        $r->refresh();
        $this->assertSame(StatutConvocationRdv::EnAttente, $r->convocation_statut);
        $this->assertSame(CanalConvocationRdv::Email, $r->convocation_canal);
        $this->assertFalse($r->convocation_fallback_utilise);
    }

    public function test_quota_mailpulse_epuise_garde_la_convocation_en_attente_et_arrete_la_file(): void
    {
        // quota_exceeded touche toute l'organisation, à l'identique pour la
        // convocation suivante : c'est un refus de configuration, pas un échec
        // de cette famille.
        $courriel = Mockery::mock(CourrielConvocationRdv::class);
        $courriel->shouldReceive('expedier')->once()->andReturn(new MailPulseResult(
            false,
            'quota_exceeded',
            429,
            null,
            null,
            'quota_exceeded',
            'Quota mensuel MailPulse atteint.',
        ));
        $this->app->instance(CourrielConvocationRdv::class, $courriel);

        $whatsapp = Mockery::mock(WhatsAppConvocationRdv::class);
        $whatsapp->shouldNotReceive('expedier');
        $this->app->instance(WhatsAppConvocationRdv::class, $whatsapp);

        $r = $this->reservation();

        $bloque = app(MessagerieRdv::class)->envoyer($r);

        $this->assertNotNull($bloque, 'La file doit s\'arrêter : la suivante tomberait sur le même quota.');
        $r->refresh();
        $this->assertSame(StatutConvocationRdv::EnAttente, $r->convocation_statut);
        $this->assertSame(0, (int) $r->convocation_tentatives);
    }

    private function reservation(array $attributs = []): ReservationMulticanalTestDouble
    {
        $r = ReservationMulticanalTestDouble::create(array_merge([
            'creneau_id' => $this->creneauId,
            'statut' => 'confirmee',
            'nom' => 'KOUASSI',
            'prenoms' => 'Ama',
            'telephone' => '+2250700000000',
            'date_naissance' => '2006-01-01',
            'email' => 'famille@exemple.ci',
            'convocation_statut' => StatutConvocationRdv::EnAttente,
            'convocation_action' => 'confirme',
        ], $attributs));

        $porteur = Mockery::mock(PorteurDeRendezVous::class);
        $porteur->shouldReceive('marquerInviteRdv')->zeroOrMoreTimes();
        $r->fakePorteur = $porteur;

        return $r;
    }
}
