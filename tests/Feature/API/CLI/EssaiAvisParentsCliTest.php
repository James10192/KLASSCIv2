<?php

namespace Tests\Feature\API\CLI;

use App\Http\Controllers\API\CLI\CLIEssaiAvisParentsController;
use App\Mail\Parents\AvisDExemple;
use App\Mail\Parents\PaiementValideMail;
use App\Models\User;
use App\Services\Courriels\EssaiDesAvisAuxParents;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

/**
 * L'essai des avis aux parents n'écrit qu'aux adresses de test de l'école.
 */
class EssaiAvisParentsCliTest extends TestCase
{
    private const ADRESSE_DE_TEST = 'essais@ecole.test';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.mailpulse.mailpulse_test_email_recipients', '');
        config()->set('services.mailpulse.test_notification_email', self::ADRESSE_DE_TEST);
        Cache::put('setting_school_name', 'Institut Supérieur KLASSCI', 3600);
        Cache::put('setting_school_logo', '', 3600);
    }

    private function appeler(array $corps, bool $admin = true): JsonResponse
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('tokenCan')->andReturnUsing(fn (string $ability) => $admin && $ability === 'cli:admin');

        $request = Request::create('/', 'POST', $corps);
        $request->setUserResolver(fn () => $user);

        return app(CLIEssaiAvisParentsController::class)->envoyer($request, app(EssaiDesAvisAuxParents::class));
    }

    public function test_refuse_un_jeton_sans_cli_admin(): void
    {
        Mail::fake();

        $response = $this->appeler(['avis' => 'tous', 'dryRun' => false], admin: false);

        $this->assertSame(403, $response->getStatusCode());
        Mail::assertNothingSent();
    }

    public function test_refuse_sans_adresse_de_test(): void
    {
        config()->set('services.mailpulse.test_notification_email', '');
        Mail::fake();

        $response = $this->appeler(['avis' => 'tous', 'dryRun' => false]);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertArrayHasKey('destinataires', $response->getData(true)['errors']);
        Mail::assertNothingSent();
    }

    public function test_refuse_un_avis_inconnu(): void
    {
        Mail::fake();

        $response = $this->appeler(['avis' => 'convocation-du-directeur', 'dryRun' => false]);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertArrayHasKey('avis', $response->getData(true)['errors']);
        Mail::assertNothingSent();
    }

    public function test_la_simulation_est_le_defaut_et_n_envoie_rien(): void
    {
        Mail::fake();

        $response = $this->appeler(['avis' => 'tous']);
        $donnees = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($donnees['dryRun']);
        $this->assertCount(count(AvisDExemple::MAILABLES), $donnees['avis']);
        foreach ($donnees['avis'] as $ligne) {
            $this->assertSame('simulé', $ligne['statut']);
            $this->assertSame(self::ADRESSE_DE_TEST, $ligne['destinataire']);
            $this->assertStringStartsWith(EssaiDesAvisAuxParents::PREFIXE_SUJET, $ligne['sujet']);
        }
        Mail::assertNothingSent();
    }

    public function test_l_envoi_reel_ne_part_qu_aux_adresses_de_test(): void
    {
        Mail::fake();

        $response = $this->appeler(['avis' => 'tous', 'dryRun' => false]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['envoyé'], array_values(array_unique(array_column($response->getData(true)['avis'], 'statut'))));
        foreach (AvisDExemple::MAILABLES as $classe) {
            Mail::assertSent($classe, function (Mailable $courriel) {
                return $courriel->hasTo(self::ADRESSE_DE_TEST)
                    && count($courriel->to) === 1
                    && $courriel->cc === [] && $courriel->bcc === [];
            });
        }
        Mail::assertSent(Mailable::class, count(AvisDExemple::MAILABLES));
    }

    public function test_le_courriel_rendu_porte_le_prefixe_essai(): void
    {
        config()->set('mail.default', 'array');

        $response = $this->appeler(['avis' => 'paiement-valide', 'dryRun' => false]);
        $messages = Mail::mailer('array')->getSymfonyTransport()->messages();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, $messages);
        $courriel = $messages->first()->getOriginalMessage();
        $sujet = (new PaiementValideMail(AvisDExemple::donnees()))->build()->subject;
        $this->assertSame(EssaiDesAvisAuxParents::PREFIXE_SUJET.$sujet, $courriel->getSubject());
        $this->assertSame(self::ADRESSE_DE_TEST, $courriel->getTo()[0]->getAddress());
        $this->assertStringContainsString('Télécharger le reçu', $courriel->getHtmlBody());
    }
}
