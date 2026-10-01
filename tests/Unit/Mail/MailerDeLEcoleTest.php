<?php

namespace Tests\Unit\Mail;

use App\Http\Controllers\ESBTP\ESBTPSettingsController;
use App\Mail\Transport\MailerDeLEcole;
use App\Mail\Transport\MailPulseTransport;
use App\Models\Setting;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Le réglage « Envoyer tous les e-mails de l'école par MailPulse » remplace
 * MAIL_MAILER=mailpulse : le mailer par défaut se lit à l'envoi.
 */
class MailerDeLEcoleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('mail.default', 'smtp');
        config()->set('mail.driver', null);
        config()->set('services.mailpulse.enabled', true);
        config()->set('services.mailpulse.api_key', '');
        config()->set('cache.default', 'array');
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');
        Cache::flush();

        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->string('group')->nullable();
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(false);
            $table->text('default_value')->nullable();
            $table->text('validation_rules')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_restart')->default(false);
            $table->integer('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    private function poser(string $cle, string $valeur, string $type = 'boolean'): void
    {
        Setting::updateOrCreate(['key' => $cle], ['value' => $valeur, 'type' => $type, 'is_active' => true]);
        Cache::flush();
    }

    /** @test */
    public function reglage_coche_bascule_le_mailer_par_defaut_sur_mailpulse(): void
    {
        $this->poser('mailpulse_enabled', '1');
        $this->poser(MailerDeLEcole::REGLAGE, '1');

        $this->assertSame('mailpulse', app('mail.manager')->getDefaultDriver());
        $this->assertTrue(MailPulseTransport::actif());
        // Le mailer résolu sans nom est bien celui de MailPulse.
        $this->assertSame('mailpulse', (string) Mail::mailer()->getSymfonyTransport());
    }

    /** @test */
    public function reglage_decoche_laisse_le_mailer_du_serveur(): void
    {
        $this->poser('mailpulse_enabled', '1');
        $this->poser(MailerDeLEcole::REGLAGE, '0');

        $this->assertSame('smtp', app('mail.manager')->getDefaultDriver());
        $this->assertFalse(MailPulseTransport::actif());
    }

    /** @test */
    public function reglage_absent_laisse_le_mailer_du_serveur(): void
    {
        $this->assertSame('smtp', app('mail.manager')->getDefaultDriver());
        $this->assertFalse(MailPulseTransport::actif());
    }

    /** @test */
    public function mailpulse_coupe_garde_le_mailer_du_serveur_meme_reglage_coche(): void
    {
        $this->poser('mailpulse_enabled', '0');
        $this->poser(MailerDeLEcole::REGLAGE, '1');

        $this->assertSame('smtp', app('mail.manager')->getDefaultDriver());
        $this->assertFalse(MailPulseTransport::actif());
    }

    /** @test */
    public function mail_mailer_mailpulse_dans_le_env_impose_mailpulse_quel_que_soit_le_reglage(): void
    {
        config()->set('mail.default', 'mailpulse');
        $this->poser(MailerDeLEcole::REGLAGE, '0');

        $this->assertSame('mailpulse', app('mail.manager')->getDefaultDriver());
        $this->assertTrue(MailPulseTransport::actif());
        $this->assertTrue(app(MailerDeLEcole::class)->imposeParLeServeur());
    }

    /** @test */
    public function base_injoignable_ne_plante_pas_et_garde_le_mailer_du_serveur(): void
    {
        Schema::drop('settings');
        Cache::flush();

        $this->assertSame('smtp', app('mail.manager')->getDefaultDriver());
        $this->assertFalse(MailPulseTransport::actif());
    }

    /** @test */
    public function un_changement_de_reglage_est_suivi_sans_redemarrer(): void
    {
        $this->poser('mailpulse_enabled', '1');
        $this->poser(MailerDeLEcole::REGLAGE, '0');
        $this->assertSame('smtp', app('mail.manager')->getDefaultDriver());

        // Ce que fait l'enregistrement : la ligne change, le cache est vidé.
        $this->poser(MailerDeLEcole::REGLAGE, '1');
        $this->assertSame('mailpulse', app('mail.manager')->getDefaultDriver());
    }

    private function enregistrer(array $champs): \Illuminate\Http\JsonResponse
    {
        $requete = Request::create('/esbtp/settings/mailpulse/save', 'POST', $champs);
        $requete->headers->set('Accept', 'application/json');

        return app(ESBTPSettingsController::class)->saveMailPulseSettings($requete);
    }

    /** @test */
    public function enregistrer_refuse_de_basculer_les_courriels_si_mailpulse_est_coupe(): void
    {
        $reponse = $this->enregistrer([
            'setting_mailpulse_enabled' => '0',
            'setting_mailpulse_courriels_enabled' => '1',
        ]);

        $this->assertSame(422, $reponse->getStatusCode());
        $this->assertArrayHasKey('setting_mailpulse_courriels_enabled', $reponse->getData(true)['errors']);
        $this->assertNotSame('1', Setting::where('key', MailerDeLEcole::REGLAGE)->value('value'));
    }

    /** @test */
    public function enregistrer_refuse_de_basculer_les_courriels_sans_cle_api(): void
    {
        $reponse = $this->enregistrer([
            'setting_mailpulse_enabled' => '1',
            'setting_mailpulse_courriels_enabled' => '1',
        ]);

        $this->assertSame(422, $reponse->getStatusCode());
        $this->assertStringContainsString('clé API', $reponse->getData(true)['errors']['setting_mailpulse_courriels_enabled'][0]);
    }

    /** @test */
    public function enregistrer_bascule_les_courriels_avec_cle_et_mailpulse_actif(): void
    {
        $reponse = $this->enregistrer([
            'setting_mailpulse_enabled' => '1',
            'setting_mailpulse_api_key' => 'mp_test_abc123',
            'setting_mailpulse_courriels_enabled' => '1',
        ]);

        $this->assertSame(200, $reponse->getStatusCode(), json_encode($reponse->getData(true)));
        $this->assertTrue($reponse->getData(true)['courriels_par_mailpulse']);
        $this->assertSame('1', Setting::where('key', MailerDeLEcole::REGLAGE)->value('value'));
        $this->assertSame('mailpulse', app('mail.manager')->getDefaultDriver());
    }

    /** @test */
    public function enregistrer_refuse_une_valeur_hors_zero_un(): void
    {
        $reponse = $this->enregistrer(['setting_mailpulse_courriels_enabled' => 'oui']);

        $this->assertSame(422, $reponse->getStatusCode());
    }

    /** @test */
    public function un_etat_deja_en_base_ne_bloque_pas_un_autre_enregistrement(): void
    {
        // Cle retiree depuis : l'ecole enregistre son nom d'expediteur sans toucher la case.
        $this->poser('mailpulse_enabled', '1');
        $this->poser(MailerDeLEcole::REGLAGE, '1');

        $reponse = $this->enregistrer([
            'setting_mailpulse_enabled' => '1',
            'setting_mailpulse_courriels_enabled' => '1',
            'setting_mailpulse_sender_name' => 'Ecole Test',
        ]);

        $this->assertSame(200, $reponse->getStatusCode(), json_encode($reponse->getData(true)));
    }

    /** @test */
    public function l_ecran_previent_quand_aucune_file_ne_permet_de_differer(): void
    {
        config()->set('queue.default', 'sync');
        $html = view('esbtp.settings.partials.mailpulse-courriels')->render();

        $this->assertStringContainsString('data-mailpulse-sans-file', $html);
        $this->assertStringContainsString('ne pourront pas être différés', $html);

        config()->set('queue.default', 'database');
        $html = view('esbtp.settings.partials.mailpulse-courriels')->render();
        $this->assertStringNotContainsString('data-mailpulse-sans-file', $html);
    }

    /** @test */
    public function l_ecran_dit_quand_le_serveur_impose_deja_mailpulse(): void
    {
        config()->set('mail.default', 'mailpulse');
        $html = view('esbtp.settings.partials.mailpulse-courriels')->render();

        $this->assertStringContainsString('impose déjà MailPulse', $html);
    }

    /** @test */
    public function la_cli_ne_peut_pas_basculer_les_courriels_ni_changer_le_serveur_mailpulse(): void
    {
        $admin = new class {
            public $id = 1;
            public function tokenCan(string $capacite): bool { return true; }
        };

        // L'enveloppe de réponse de l'API lit l'année courante.
        Schema::create('esbtp_annee_universitaires', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('is_current')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        foreach (MailerDeLEcole::REGLAGES_RESERVES_A_L_ECRAN as $cle) {
            $requete = Request::create('/api/cli/settings', 'POST', ['key' => $cle, 'value' => '1', 'apply' => true]);
            $requete->setUserResolver(fn () => $admin);
            $this->assertSame(422, app(\App\Http\Controllers\API\CLI\CLISettingsController::class)->update($requete)->getStatusCode(), $cle);

            $requete = Request::create("/api/cli/settings/{$cle}", 'PUT', ['value' => '1']);
            $requete->setUserResolver(fn () => $admin);
            $reponse = app(\App\Http\Controllers\API\CLI\CLIDataController::class)->settingsUpdate($requete, $cle);
            $this->assertSame(422, $reponse->getStatusCode(), $cle);
        }

        foreach (MailerDeLEcole::REGLAGES_RESERVES_A_L_ECRAN as $cle) {
            $this->assertNull(Setting::where('key', $cle)->value('value'), $cle);
        }
        $this->assertStringContainsString("depuis l'écran des paramètres", $reponse->getData(true)['message']);
    }

    private function mailpulseAccepte(): void
    {
        config()->set('services.mailpulse.api_key', 'test-key');
        config()->set('services.mailpulse.base_url', 'https://mailpulse.test');
        config()->set('services.mailpulse.messages_endpoint', '/api/v1/messages');
        config()->set('services.mailpulse.mail_per_minute', 30);
        config()->set('mail.from', ['address' => 'noreply@klassci.com', 'name' => 'Ecole Test']);
        Http::fake(['mailpulse.test/api/v1/messages' => Http::response([
            'dispatch' => ['state' => 'accepted', 'sms_fallback_eligible' => false],
            'message' => ['id' => 'msg-1', 'status' => 'sent'],
        ], 202)]);
    }

    /** @test */
    public function un_mailable_en_file_et_une_notification_suivent_la_case(): void
    {
        $this->mailpulseAccepte();
        config()->set('queue.default', 'sync');
        $this->poser('mailpulse_enabled', '1');
        $this->poser(MailerDeLEcole::REGLAGE, '1');

        Mail::to('parent@exemple.ci')->queue(new MailableDeTestEnFile());
        NotificationFacade::route('mail', 'tuteur@exemple.ci')->notify(new NotificationDeTestEnFile());

        Http::assertSentCount(2);
        foreach (['parent@exemple.ci', 'tuteur@exemple.ci'] as $destinataire) {
            Http::assertSent(fn ($r) => str_contains($r->url(), 'mailpulse.test/api/v1/messages')
                && ($r->data()['recipient']['value'] ?? null) === $destinataire);
        }
    }

    /** @test */
    public function case_decochee_le_mailable_en_file_ne_passe_pas_par_mailpulse(): void
    {
        $this->mailpulseAccepte();
        config()->set('queue.default', 'sync');
        config()->set('mail.default', 'array');
        $this->poser('mailpulse_enabled', '1');
        $this->poser(MailerDeLEcole::REGLAGE, '0');

        Mail::to('parent@exemple.ci')->queue(new MailableDeTestEnFile());

        Http::assertNothingSent();
        $this->assertCount(1, app('mail.manager')->mailer('array')->getSymfonyTransport()->messages());
    }
}

class MailableDeTestEnFile extends \Illuminate\Mail\Mailable implements \Illuminate\Contracts\Queue\ShouldQueue
{
    public function build()
    {
        return $this->subject('Rappel')->html('<p>Bonjour</p>');
    }
}

class NotificationDeTestEnFile extends \Illuminate\Notifications\Notification implements \Illuminate\Contracts\Queue\ShouldQueue
{
    use \Illuminate\Bus\Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        return (new \Illuminate\Notifications\Messages\MailMessage())->subject('Avis')->line('Bonjour');
    }
}
