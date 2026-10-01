<?php

namespace Tests\Unit\Mail;

use App\Mail\Support\LienVerificationCourrielMail;
use App\Mail\Support\ReponseDuSupportMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

/**
 * Les courriels KLASSCI Care prennent le gabarit commun : logo de l'école par
 * son URL publique ABSOLUE (les courriels partent par MailPulse, qui ne
 * transporte que du HTML : ni pièce intégrée, ni base64 que Gmail bloque), et
 * une initiale à la place quand aucun logo n'est configuré — jamais une image
 * cassée. Les réglages sont posés dans le cache que lit `Setting::get`.
 */
class CourrielsSupportTest extends TestCase
{
    private ?string $logo = null;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['mail.default' => 'array']);
        $this->regler('school_name', 'Institut Supérieur KLASSCI');
        $this->regler('school_phone', '+225 07 07 00 00 00');
        $this->regler('school_logo', '');
    }

    protected function tearDown(): void
    {
        if ($this->logo && is_file($this->logo)) {
            unlink($this->logo);
        }
        parent::tearDown();
    }

    private function regler(string $cle, string $valeur): void
    {
        Cache::put('setting_'.$cle, $valeur, 3600);
    }

    private function avecLogo(): void
    {
        $nom = 'test-courriel-'.Str::random(8).'.png';
        $dossier = storage_path('app/public/logos');
        is_dir($dossier) || mkdir($dossier, 0775, true);
        $this->logo = $dossier.'/'.$nom;
        // PNG 1x1 valide.
        file_put_contents($this->logo, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
        $this->regler('school_logo', 'logos/'.$nom);
    }

    private function envoyer($courriel): Email
    {
        Mail::to('awa.kone@ecole.test')->send($courriel);
        $envois = app('mailer')->getSymfonyTransport()->messages();

        return $envois->last()->getOriginalMessage();
    }

    private function verification(): LienVerificationCourrielMail
    {
        return new LienVerificationCourrielMail('Awa Koné', 'https://ecole.test/support/courriel/confirmer?sig=abc', 48);
    }

    public function test_le_logo_configure_est_appele_par_son_url_absolue(): void
    {
        $this->avecLogo();
        $email = $this->envoyer($this->verification());
        $html = $email->getHtmlBody();

        $this->assertStringContainsString('<img src="'.route('api.public.etablissement.logo').'"', $html);
        $this->assertMatchesRegularExpression('#<img src="https?://#', $html, 'Une URL relative ne s\'affiche dans aucune messagerie.');
        $this->assertCount(0, $email->getAttachments(), 'Aucune pièce intégrée : MailPulse ne transporte que le HTML.');
        $this->assertStringNotContainsString('cid:', $html);
        $this->assertStringNotContainsString('data:image', $html, 'Gmail bloque les images base64.');
    }

    public function test_sans_logo_l_initiale_remplace_l_image(): void
    {
        $email = $this->envoyer($this->verification());
        $html = $email->getHtmlBody();

        $this->assertCount(0, $email->getAttachments());
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('>I</span>', $html, "L'initiale de l'école tient la place du logo.");
    }

    public function test_la_verification_porte_le_bouton_le_lien_en_clair_et_les_reglages_de_l_ecole(): void
    {
        $email = $this->envoyer($this->verification());
        $html = $email->getHtmlBody();

        $this->assertSame('Confirmez votre adresse e-mail · Institut Supérieur KLASSCI', $email->getSubject());
        $this->assertStringContainsString('Confirmer mon adresse', $html);
        $this->assertStringContainsString('Copiez ce lien', $html);
        $this->assertStringContainsString('valable <strong style="color:#0f172a;">48 heures', $html);
        $this->assertStringContainsString('+225 07 07 00 00 00', $html);
        $this->assertStringContainsString('Message automatique du support KLASSCI', $html);
    }

    public function test_la_reponse_du_support_cite_la_reference_le_message_et_le_statut(): void
    {
        $this->avecLogo();
        $email = $this->envoyer(new ReponseDuSupportMail([
            'reference' => 'KC-2026-000042',
            'titre' => 'Le bouton Valider les notes ne répond plus',
            'nom' => 'Awa Koné',
            'a_repondu' => true,
            'statut_libelle' => 'Résolue',
            'statut_code' => 'RESOLU',
            'extrait' => 'Le correctif est déployé, rechargez la page.',
            'lien' => 'https://ecole.test/support/demandes/KC-2026-000042',
        ]));
        $html = $email->getHtmlBody();

        $this->assertStringContainsString('KC-2026-000042', $html);
        $this->assertStringContainsString('Le correctif est déployé, rechargez la page.', $html);
        $this->assertStringContainsString('Résolue', $html);
        $this->assertStringContainsString('#dcfce7', $html, 'Une demande résolue porte la pastille verte.');
        $this->assertStringContainsString('Ouvrir la demande', $html);
        $this->assertCount(0, $email->getAttachments());
    }

    public function test_un_statut_action_requise_se_lit_en_francais(): void
    {
        $email = $this->envoyer(new ReponseDuSupportMail([
            'reference' => 'KC-2026-000043', 'titre' => 'Import des notes', 'nom' => 'Awa Koné',
            'a_repondu' => false, 'statut_libelle' => 'Action requise', 'statut_code' => 'ACTION_REQUISE',
            'extrait' => null, 'lien' => 'https://ecole.test/support/demandes/KC-2026-000043',
        ]));

        $this->assertStringStartsWith('Votre demande « Import des notes » attend une action de votre part', $email->getSubject());
        $this->assertStringContainsString('Votre demande attend une action de votre part', $email->getHtmlBody());
        $this->assertStringNotContainsString('est action requise', $email->getHtmlBody());
    }

    public function test_la_pastille_de_l_ecran_et_celle_du_courriel_lisent_le_meme_sens(): void
    {
        $this->assertSame('succes', \App\Domain\Support\TonDuStatut::pour('RESOLU'));
        $this->assertSame('attention', \App\Domain\Support\TonDuStatut::pour('ACTION_REQUISE'));
        $this->assertSame('neutre', \App\Domain\Support\TonDuStatut::pour('FERME'));
        $this->assertSame('info', \App\Domain\Support\TonDuStatut::pour(null));
        $pastille = view('support.demandes._statut', ['statut' => ['code' => 'RESOLU', 'libelle' => 'Résolue']])->render();
        $this->assertStringContainsString('sd-statut--succes', $pastille);
    }
}
