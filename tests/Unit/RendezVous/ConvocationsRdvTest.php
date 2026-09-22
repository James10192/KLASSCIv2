<?php

namespace Tests\Unit\RendezVous;

use App\Enums\StatutConvocationRdv;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Services\MailPulse\MailPulseResult;
use App\Services\RendezVous\CourrielConvocationRdv;
use App\Services\RendezVous\FileConvocationsRdv;
use App\Services\RendezVous\MessagerieRdv;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

/**
 * La convocation ne se perd plus en silence.
 *
 * Avant : un lot partait dans `terminating`, le premier refus arretait tous les
 * suivants, MailPulse coupe ne laissait aucune trace, et rien n'etait consigne
 * sur la reservation.
 */
class ConvocationsRdvTest extends TestCase
{
    private int $creneauFutur;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');

        Schema::create('esbtp_rdv_creneaux', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('annee_universitaire_id')->nullable();
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
            $t->timestamps();
        });

        // Le porteur est lu apres un envoi reussi ; ces reservations n'en ont pas.
        foreach (['esbtp_candidatures', 'esbtp_reinscription_demandes'] as $table) {
            Schema::create($table, fn (Blueprint $t) => $t->id());
        }

        $this->creneauFutur = ESBTPRdvCreneau::create([
            'date' => now()->addDays(3)->toDateString(),
            'heure_debut' => '08:00:00',
            'heure_fin' => '08:30:00',
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

    public function test_mailpulse_desactive_se_dit_et_ne_consomme_pas_de_tentative(): void
    {
        $this->courriel(fn () => $this->refus('disabled', 'MailPulse est desactive par MAILPULSE_ENABLED=false.'));
        $r = $this->reservation();

        $bloque = app(MessagerieRdv::class)->envoyer($r);

        $this->assertNotNull($bloque, 'Un MailPulse coupe doit etre dit, pas avale.');
        $this->assertStringContainsString('desactive', $bloque);
        $r->refresh();
        $this->assertSame(StatutConvocationRdv::EnAttente, $r->convocation_statut);
        $this->assertSame(0, $r->convocation_tentatives);
        $this->assertStringContainsString('disabled', (string) $r->convocation_erreur);
    }

    public function test_un_envoi_accepte_est_consigne_avec_son_identifiant_mailpulse(): void
    {
        $this->courriel(fn () => new MailPulseResult(true, 'queued', 202, 'req-1', 'msg-42', dispatchState: 'accepted'));
        $r = $this->reservation();

        $this->assertNull(app(MessagerieRdv::class)->envoyer($r));

        $r->refresh();
        $this->assertSame(StatutConvocationRdv::Envoyee, $r->convocation_statut);
        $this->assertSame('msg-42', $r->convocation_message_id);
        $this->assertNotNull($r->convocation_envoyee_at);
    }

    public function test_un_mailpulse_injoignable_arrete_le_lot_et_passe_en_echec_apres_cinq_essais(): void
    {
        $this->courriel(fn () => $this->refus('connection_failed', 'MailPulse est injoignable.'));
        $r = $this->reservation(['convocation_tentatives' => MessagerieRdv::MAX_TENTATIVES - 2]);
        $mails = app(MessagerieRdv::class);

        $this->assertNotNull($mails->envoyer($r));
        $this->assertSame(StatutConvocationRdv::EnAttente, $r->fresh()->convocation_statut);

        $mails->envoyer($r->fresh());
        $this->assertSame(StatutConvocationRdv::Echec, $r->fresh()->convocation_statut);
    }

    public function test_un_refus_rendu_en_json_brut_reste_lisible(): void
    {
        $this->courriel(fn () => new MailPulseResult(false, 'provider_error', 200, null, null, 'RECIPIENT_REJECTED', '{"status":"failed"}'));
        $r = $this->reservation();

        app(MessagerieRdv::class)->envoyer($r);

        $this->assertSame('Refusé par MailPulse (RECIPIENT_REJECTED)', $r->fresh()->convocation_erreur);
    }

    public function test_une_exception_ne_remonte_pas(): void
    {
        $this->courriel(fn () => throw new \RuntimeException('vue introuvable'));
        $r = $this->reservation();

        app(FileConvocationsRdv::class)->envoyerCelle($r->id);

        $r->refresh();
        $this->assertSame(1, $r->convocation_tentatives);
        $this->assertSame('vue introuvable', $r->convocation_erreur);
    }

    public function test_un_echec_au_milieu_du_lot_n_arrete_pas_les_suivants(): void
    {
        $premiere = $this->reservation(['email' => 'a@exemple.ci']);
        $refusee = $this->reservation(['email' => 'b@exemple.ci']);
        $suivante = $this->reservation(['email' => 'c@exemple.ci']);

        $this->courriel(fn (ESBTPRdvReservation $r) => $r->email === 'b@exemple.ci'
            ? $this->refus('provider_error', 'Adresse refusée.')
            : new MailPulseResult(true, 'queued', 202, null, 'ok-'.$r->id, dispatchState: 'accepted'));

        $rapport = app(FileConvocationsRdv::class)->envoyerUnPaquet();

        $this->assertSame(2, $rapport['envoyees']);
        $this->assertSame(1, $rapport['echecs']);
        $this->assertSame(0, $rapport['restantes']);
        $this->assertNull($rapport['bloque']);
        $this->assertSame(StatutConvocationRdv::Envoyee, $premiere->fresh()->convocation_statut);
        $this->assertSame(StatutConvocationRdv::Echec, $refusee->fresh()->convocation_statut);
        $this->assertSame(StatutConvocationRdv::Envoyee, $suivante->fresh()->convocation_statut);
    }

    public function test_un_refus_de_configuration_arrete_le_paquet_sans_toucher_aux_suivantes(): void
    {
        $this->courriel(fn () => $this->refus('missing_api_key', 'MAILPULSE_API_KEY est manquante.'));
        $this->reservation();
        $this->reservation();

        $rapport = app(FileConvocationsRdv::class)->envoyerUnPaquet();

        $this->assertSame(0, $rapport['envoyees']);
        $this->assertSame(2, $rapport['restantes']);
        $this->assertStringContainsString('manquante', (string) $rapport['bloque']);
    }

    public function test_sans_email_et_creneau_passe_ne_partent_pas(): void
    {
        $courriel = Mockery::mock(CourrielConvocationRdv::class);
        $courriel->shouldNotReceive('expedier');
        $this->app->instance(CourrielConvocationRdv::class, $courriel);

        $passe = ESBTPRdvCreneau::create([
            'date' => now()->subDay()->toDateString(), 'heure_debut' => '08:00:00',
            'heure_fin' => '08:30:00', 'capacite' => 10, 'ouvert' => true,
        ]);
        $sansEmail = $this->reservation(['email' => 'pas-un-email']);
        $enRetard = $this->reservation(['creneau_id' => $passe->id]);

        $mails = app(MessagerieRdv::class);
        $mails->envoyer($sansEmail);
        $mails->envoyer($enRetard);

        $this->assertSame(StatutConvocationRdv::SansEmail, $sansEmail->fresh()->convocation_statut);
        $this->assertSame(StatutConvocationRdv::SansObjet, $enRetard->fresh()->convocation_statut);
    }

    public function test_un_envoi_en_cours_empeche_un_second_de_doubler_les_courriels(): void
    {
        $courriel = Mockery::mock(CourrielConvocationRdv::class);
        $courriel->shouldNotReceive('expedier');
        $this->app->instance(CourrielConvocationRdv::class, $courriel);
        $r = $this->reservation();

        // Un autre envoi (tache planifiee, second onglet) tient le verrou.
        $verrou = Cache::lock('rdv-convocations-envoi', 60);
        $this->assertTrue($verrou->get());

        $file = app(FileConvocationsRdv::class);
        $rapport = $file->envoyerUnPaquet();
        $file->envoyerCelle($r->id);
        $verrou->release();

        $this->assertTrue($rapport['en_cours']);
        $this->assertSame(1, $rapport['restantes']);
        $this->assertSame(StatutConvocationRdv::EnAttente, $r->fresh()->convocation_statut);
    }

    public function test_la_reservation_du_portail_part_apres_la_reponse_par_la_meme_porte(): void
    {
        $this->courriel(fn () => new MailPulseResult(true, 'queued', 202, null, 'mp-1', dispatchState: 'accepted'));
        $r = $this->reservation(['convocation_statut' => null]);

        app(FileConvocationsRdv::class)->confirmer($r, 'confirme');
        $this->assertSame(StatutConvocationRdv::EnAttente, $r->fresh()->convocation_statut, 'Rien ne part avant la reponse.');

        $this->app->terminate();
        $this->assertSame(StatutConvocationRdv::Envoyee, $r->fresh()->convocation_statut);
    }

    public function test_planifier_n_envoie_rien(): void
    {
        $courriel = Mockery::mock(CourrielConvocationRdv::class);
        $courriel->shouldNotReceive('expedier');
        $this->app->instance(CourrielConvocationRdv::class, $courriel);
        $r = $this->reservation(['convocation_statut' => null]);

        app(MessagerieRdv::class)->planifier($r, 'confirme');
        $this->app->terminate();

        $this->assertSame(StatutConvocationRdv::EnAttente, $r->fresh()->convocation_statut);
    }

    public function test_les_reservations_d_avant_le_suivi_ne_partent_que_sur_demande(): void
    {
        $this->courriel(fn () => new MailPulseResult(true, 'queued', 202, null, 'x', dispatchState: 'accepted'));
        $inconnue = $this->reservation(['convocation_statut' => null]);
        $annulee = $this->reservation(['convocation_statut' => null, 'statut' => 'annulee']);
        $file = app(FileConvocationsRdv::class);

        $this->assertSame(0, $file->envoyerUnPaquet()['envoyees'], 'La tache planifiee ne doit pas vider un arriere en silence.');
        $this->assertSame(1, $file->remettreEnAttente('inconnues'));
        $this->assertSame(1, $file->envoyerUnPaquet()['envoyees']);
        $this->assertSame(StatutConvocationRdv::Envoyee, $inconnue->fresh()->convocation_statut);
        $this->assertNull($annulee->fresh()->convocation_statut);
    }

    public function test_remettre_ne_saute_aucune_ligne_au_dela_d_une_page(): void
    {
        foreach (range(1, 1201) as $i) {
            $this->reservation(['convocation_statut' => null, 'email' => "f{$i}@exemple.ci"]);
        }

        $this->assertSame(1201, app(FileConvocationsRdv::class)->remettreEnAttente('inconnues'));
        $this->assertSame(0, ESBTPRdvReservation::query()->whereNull('convocation_statut')->count());
    }

    private function reservation(array $attributs = []): ESBTPRdvReservation
    {
        return ESBTPRdvReservation::create(array_merge([
            'creneau_id' => $this->creneauFutur,
            'statut' => 'confirmee',
            'nom' => 'KOUASSI',
            'prenoms' => 'Ama',
            'telephone' => '+2250700000000',
            'date_naissance' => '2006-01-01',
            'email' => 'famille@exemple.ci',
            'convocation_statut' => StatutConvocationRdv::EnAttente,
            'convocation_action' => 'confirme',
        ], $attributs));
    }

    private function courriel(callable $reponse): void
    {
        $courriel = Mockery::mock(CourrielConvocationRdv::class);
        $courriel->shouldReceive('expedier')->andReturnUsing(fn (ESBTPRdvReservation $r) => $reponse($r));
        $this->app->instance(CourrielConvocationRdv::class, $courriel);
    }

    private function refus(string $statut, string $message): MailPulseResult
    {
        return new MailPulseResult(false, $statut, null, null, null, $statut, $message);
    }
}
