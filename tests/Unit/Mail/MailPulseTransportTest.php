<?php

namespace Tests\Unit\Mail;

use App\Mail\Transport\CorpsPourMailPulse;
use App\Mail\Transport\MailPulseTransport;
use Illuminate\Http\Client\Request;
use Illuminate\Mail\Message;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class MailPulseTransportTest extends TestCase
{
    private const ENDPOINT = 'mailpulse.test/api/v1/messages';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('mail.default', 'mailpulse');
        config()->set('mail.from', ['address' => 'noreply@klassci.com', 'name' => 'École Test']);
        config()->set('app.tenant_code', 'presentation');
        config()->set('services.mailpulse.enabled', true);
        config()->set('services.mailpulse.api_key', 'test-key');
        config()->set('services.mailpulse.base_url', 'https://mailpulse.test');
        config()->set('services.mailpulse.messages_endpoint', '/api/v1/messages');
        config()->set('services.mailpulse.sender_email', '');
        config()->set('services.mailpulse.sender_name', 'KLASSCI');
    }

    private function accepte(): void
    {
        Http::fake([self::ENDPOINT => Http::response([
            'dispatch' => ['state' => 'accepted', 'sms_fallback_eligible' => false],
            'message' => ['id' => 'msg-1', 'status' => 'sent'],
        ], 202)]);
    }

    /** @test */
    public function chaque_destinataire_recoit_son_message_avec_sujet_html_texte_et_expediteur(): void
    {
        $this->accepte();

        Mail::mailer('mailpulse')->html("<html>\n    <body>\n        <p>Bonjour</p>\n        <!-- note -->\n        <a href=\"https://x.test/lien\">Ouvrir</a>\n    </body>\n</html>", function (Message $m) {
            $m->to('parent@example.com')->cc('copie@example.com')->bcc('cache@example.com')
                ->subject('Avis de paiement');
            $m->getSymfonyMessage()->text('Bonjour, ouvrez https://x.test/lien');
        });

        Http::assertSentCount(3);

        $destinataires = [];
        $cles = [];
        Http::assertSent(function (Request $request) use (&$destinataires, &$cles) {
            $corps = $request->data();
            $destinataires[] = $corps['recipient']['value'];
            $cles[] = $request->header('Idempotency-Key')[0];

            return $corps['channel'] === 'email'
                && $corps['recipient']['type'] === 'email'
                && $corps['content'] === ['type' => 'text', 'text' => 'Bonjour, ouvrez https://x.test/lien']
                && $corps['metadata']['subject'] === 'Avis de paiement'
                && $corps['metadata']['email_html'] === "<html>\n<body>\n<p>Bonjour</p>\n\n<a href=\"https://x.test/lien\">Ouvrir</a>\n</body>\n</html>"
                && $corps['metadata']['sender_email'] === 'noreply@klassci.com'
                && $corps['metadata']['sender_name'] === 'École Test'
                && $corps['metadata']['external_tenant_id'] === 'presentation'
                && $corps['metadata']['source'] === 'klassci';
        });

        sort($destinataires);
        $this->assertSame(['cache@example.com', 'copie@example.com', 'parent@example.com'], $destinataires);
        $this->assertCount(3, array_unique($cles), 'Chaque destinataire porte sa propre clé d\'idempotence.');
    }

    /** @test */
    public function un_bloc_pre_line_garde_ses_sauts_de_ligne_et_ses_paragraphes(): void
    {
        $html = "<div style=\"white-space:pre-line;\">Bonjour,\n\n  Votre demande est traitée.\nCordialement</div>";

        $this->assertSame(
            "<div style=\"white-space:pre-line;\">Bonjour,\n\nVotre demande est traitée.\nCordialement</div>",
            CorpsPourMailPulse::resserrer($html)
        );
    }

    /** @test */
    public function le_reglage_d_expediteur_mailpulse_prime_sur_l_adresse_du_courriel(): void
    {
        $this->accepte();
        config()->set('services.mailpulse.sender_email', 'ecole@klassci.com');

        Mail::mailer('mailpulse')->raw('Texte seul', fn (Message $m) => $m->to('a@example.com')->subject('S'));

        Http::assertSent(fn (Request $r) => $r->data()['metadata']['sender_email'] === 'ecole@klassci.com'
            && ! isset($r->data()['metadata']['email_html'])
            && $r->data()['content']['text'] === 'Texte seul');
    }

    /** @test */
    public function un_courriel_sans_version_texte_en_recoit_une_tiree_du_html_avec_ses_liens(): void
    {
        $this->accepte();

        Mail::mailer('mailpulse')->html(
            '<html><head><style>p{color:red}</style></head><body><p>Votre code&nbsp;: 123</p><p><a href="https://x.test/v?a=1&amp;b=2">Confirmer</a></p></body></html>',
            fn (Message $m) => $m->to('a@example.com')->subject('Code')
        );

        Http::assertSent(fn (Request $r) => $r->data()['content']['text'] === "Votre code : 123\nConfirmer (https://x.test/v?a=1&b=2)");
    }

    /** @test */
    public function une_image_integree_est_retiree_et_journalisee(): void
    {
        $this->accepte();
        Log::spy();
        $logo = tempnam(sys_get_temp_dir(), 'logo').'.png';
        file_put_contents($logo, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));

        Mail::mailer('mailpulse')->send([], [], function (Message $m) use ($logo) {
            $cid = $m->embed($logo);
            $m->to('a@example.com')->subject('Logo')->html('<p>Avant<img src="'.$cid.'" alt="logo">Après</p>');
        });

        Http::assertSent(fn (Request $r) => $r->data()['metadata']['email_html'] === '<p>AvantAprès</p>');
        Log::shouldHaveReceived('warning')->withArgs(fn ($msg, $ctx) => str_contains($msg, 'images intégrées') && $ctx['images'] === 1)->once();
        @unlink($logo);
    }

    /** @test */
    public function une_piece_jointe_est_refusee_sans_rien_envoyer(): void
    {
        Http::fake();
        Log::spy();

        try {
            Mail::mailer('mailpulse')->raw('Ci-joint', function (Message $m) {
                $m->to('a@example.com')->subject('Export')->attachData('%PDF-1.4', 'export.pdf', ['mime' => 'application/pdf']);
            });
            $this->fail('Une pièce jointe aurait dû être refusée.');
        } catch (TransportException $e) {
            $this->assertStringContainsString('pièces jointes', $e->getMessage());
        }

        Http::assertNothingSent();
        Log::shouldHaveReceived('error')->once();
    }

    /** @test */
    public function un_refus_de_mailpulse_leve_et_se_journalise(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['error' => 'down'], 503)]);
        Log::spy();

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('provider_unavailable, HTTP 503');

        try {
            Mail::mailer('mailpulse')->raw('x', fn (Message $m) => $m->to('a@example.com')->subject('S'));
        } finally {
            Log::shouldHaveReceived('error')->withArgs(fn ($msg, $ctx) => $msg === 'Courriel refusé par MailPulse'
                && $ctx['statut'] === 'provider_unavailable'
                && $ctx['http'] === 503
                && $ctx['domaine_destinataire'] === 'example.com')->once();
        }
    }

    /** @test */
    public function un_envoi_refuse_par_le_fournisseur_dans_un_202_leve_aussi(): void
    {
        Http::fake([self::ENDPOINT => Http::response([
            'dispatch' => ['state' => 'failed', 'sms_fallback_eligible' => false],
            'code' => 'provider_error',
            'message' => ['id' => 'm', 'status' => 'failed', 'error_message' => 'Domain is not verified'],
        ], 202)]);

        $this->expectException(TransportException::class);

        Mail::mailer('mailpulse')->raw('x', fn (Message $m) => $m->to('a@example.com')->subject('S'));
    }

    /** @test */
    public function une_remise_a_confirmer_part_sans_exception_mais_se_journalise(): void
    {
        Http::fake([self::ENDPOINT => Http::response([
            'dispatch' => ['state' => 'pending_reconciliation', 'sms_fallback_eligible' => false],
            'message' => ['id' => 'm', 'status' => 'submission_unknown'],
        ], 202)]);
        Log::spy();

        Mail::mailer('mailpulse')->raw('x', fn (Message $m) => $m->to('a@example.com')->subject('S'));

        Log::shouldHaveReceived('warning')->withArgs(fn ($msg, $ctx) => str_contains($msg, 'remise à confirmer')
            && $ctx['etat'] === 'pending_reconciliation')->once();
    }

    /** @test */
    public function mailpulse_desactive_n_est_jamais_un_succes(): void
    {
        Http::fake();
        config()->set('services.mailpulse.enabled', false);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('disabled');

        try {
            Mail::mailer('mailpulse')->raw('x', fn (Message $m) => $m->to('a@example.com')->subject('S'));
        } finally {
            Http::assertNothingSent();
        }
    }

    /** @test */
    public function un_html_trop_lourd_part_en_texte_et_se_journalise(): void
    {
        $this->accepte();
        Log::spy();
        $html = '<p>'.str_repeat('Contenu long. ', 2000).'</p>';

        Mail::mailer('mailpulse')->html($html, fn (Message $m) => $m->to('a@example.com')->subject('Gros'));

        Http::assertSent(fn (Request $r) => ! isset($r->data()['metadata']['email_html'])
            && str_starts_with($r->data()['content']['text'], 'Contenu long.')
            && CorpsPourMailPulse::octetsJson($r->data()['metadata']) <= MailPulseTransport::PLAFOND_METADATA);
        Log::shouldHaveReceived('warning')->withArgs(fn ($msg) => str_contains($msg, 'HTML trop volumineux'))->once();
    }

    /** @test */
    public function le_canal_mail_des_notifications_passe_par_le_mailer_par_defaut(): void
    {
        $this->accepte();

        NotificationFacade::route('mail', 'parent@example.com')->notify(new class extends Notification {
            public function via($notifiable): array
            {
                return ['mail'];
            }

            public function toMail($notifiable): MailMessage
            {
                return (new MailMessage)->subject('Absence signalée')->line('Votre enfant était absent.');
            }
        });

        Http::assertSent(fn (Request $r) => $r->data()['recipient']['value'] === 'parent@example.com'
            && $r->data()['metadata']['subject'] === 'Absence signalée'
            && str_contains($r->data()['content']['text'], 'Votre enfant était absent.'));
    }

    /** @test */
    public function le_gabarit_commun_passe_par_l_url_du_logo_quand_mailpulse_est_le_mailer(): void
    {
        $logo = tempnam(sys_get_temp_dir(), 'logo');

        $html = view('esbtp.emails.layout', [
            'message' => new Message(new \Symfony\Component\Mime\Email()),
            'schoolLogoPath' => $logo,
            'schoolLogoUrl' => 'https://ecole.test/logo.png',
            'schoolName' => 'École Test',
        ])->render();

        $this->assertStringContainsString('https://ecole.test/logo.png', $html);
        $this->assertStringNotContainsString('cid:', $html);
        @unlink($logo);
    }

    /** @test */
    public function l_envoi_d_un_pdf_par_courriel_n_est_ni_propose_ni_accepte_sous_mailpulse(): void
    {
        $rendu = fn () => \Illuminate\Support\Facades\Blade::render(
            '<x-export-modal preview-url="p" pdf-url="d" excel-url="e" email-url="https://ecole.test/email-pdf" />'
        );
        $this->assertStringNotContainsString('https://ecole.test/email-pdf', $rendu());
        config()->set('mail.default', 'smtp');
        $this->assertStringContainsString('https://ecole.test/email-pdf', $rendu(), 'Sous SMTP, le bouton reste.');
        config()->set('mail.default', 'mailpulse');

        $this->expectException(\DomainException::class);
        app(\App\Services\ExportRenderer::class)->emailPdf($this->createMock(\App\Domain\Exports\ExportableReport::class), 'a@example.com');
    }
}
