<?php

namespace Tests\Feature\Verification;

use App\Enums\StatutVerificationContact;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCandidature;
use App\Models\User;
use App\Services\Reinscription\PortailSignatureVerifier;
use App\Services\Verification\VerificationDuDepot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as RequeteHttp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Une demande deposee sur le portail n'existe pour l'ecole qu'une fois son
 * contact prouve : code ou lien par e-mail, code WhatsApp sinon.
 *
 * MailPulse est simule (Http::fake) : aucun message ne part.
 */
class VerificationContactTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'un-secret-de-test-suffisamment-long-pour-passer';

    private int $horloge = 0;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.reinscription_portal.secret' => self::SECRET,
            'services.mailpulse.enabled' => true,
            'services.mailpulse.api_key' => 'cle-de-test',
            'services.mailpulse.base_url' => 'https://mailpulse.test',
            'app.tenant_code' => 'esbtp-abidjan',
        ]);
        User::factory()->create(['id' => 1])
            ->assignRole(Role::firstOrCreate(['name' => 'superAdmin', 'guard_name' => 'web']));
        Cache::flush();
    }

    public function test_une_candidature_avec_email_reste_invisible_jusqu_au_bon_code(): void
    {
        $this->mailpulseAccepteLesCourriels();
        $candidature = $this->candidature('awa.kone@gmail.com');

        $verification = app(VerificationDuDepot::class)->apres($candidature);

        $this->assertNotNull($verification);
        $this->assertSame('verification_email_requise', $verification->reponse()['statut']);
        $this->assertSame('a***@gmail.com', $verification->reponse()['email_masque']);
        $this->assertSame(0, ESBTPCandidature::query()->count(), 'Une demande non verifiee ne doit apparaitre nulle part.');

        $this->verifier(['demande_id' => $verification->demandeId, 'code' => $this->codeEnvoye()])
            ->assertOk()
            ->assertExactJson(['verifie' => true, 'type' => 'candidature']);

        $this->assertSame(1, ESBTPCandidature::query()->count());
        $this->assertNotNull($candidature->fresh()->email_verifie_at);
        $this->assertSame(StatutVerificationContact::Verifie->value, $candidature->fresh()->verification_contact);
    }

    public function test_le_lien_porte_l_ecole_en_requete_et_le_jeton_en_fragment(): void
    {
        $this->mailpulseAccepteLesCourriels();
        app(VerificationDuDepot::class)->apres($this->candidature('awa.kone@gmail.com'));

        $texte = $this->texteEnvoye();
        $this->assertMatchesRegularExpression('~https://www\.klassci\.com/verification-email\?ecole=esbtp-abidjan#jeton=[A-Za-z0-9_-]{40,}~', $texte);
        $this->assertStringNotContainsString('?jeton=', $texte);

        preg_match('~#jeton=([A-Za-z0-9_-]+)~', $texte, $m);
        $this->verifier(['canal' => 'email', 'jeton' => $m[1]])->assertOk()->assertJson(['verifie' => true]);
        $this->assertSame(1, ESBTPCandidature::query()->count());
    }

    public function test_cinq_codes_faux_bloquent_meme_le_bon(): void
    {
        $this->mailpulseAccepteLesCourriels();
        $verification = app(VerificationDuDepot::class)->apres($this->candidature('awa.kone@gmail.com'));
        $bon = $this->codeEnvoye();
        $faux = $bon === '000000' ? '111111' : '000000';

        for ($i = 1; $i <= 4; $i++) {
            $this->verifier(['demande_id' => $verification->demandeId, 'code' => $faux])
                ->assertStatus(422)->assertExactJson(['verifie' => false, 'motif' => 'code_invalide']);
        }
        $this->verifier(['demande_id' => $verification->demandeId, 'code' => $faux])->assertJson(['motif' => 'trop_de_tentatives']);
        $this->verifier(['demande_id' => $verification->demandeId, 'code' => $bon])->assertStatus(422)->assertJson(['motif' => 'trop_de_tentatives']);
        $this->assertSame(0, ESBTPCandidature::query()->count());
    }

    public function test_un_code_perime_est_refuse(): void
    {
        $this->mailpulseAccepteLesCourriels();
        $verification = app(VerificationDuDepot::class)->apres($this->candidature('awa.kone@gmail.com'));
        $code = $this->codeEnvoye();

        $this->travel(31)->minutes();

        $this->verifier(['demande_id' => $verification->demandeId, 'code' => $code])->assertStatus(422)->assertJson(['motif' => 'expire']);
    }

    public function test_une_demande_inconnue_repond_comme_un_code_faux(): void
    {
        $this->verifier(['demande_id' => '8d3c1a52-8f0e-4f7e-9d77-0d8e8f1f0a11', 'code' => '123456'])
            ->assertStatus(422)->assertExactJson(['verifie' => false, 'motif' => 'code_invalide']);
        $this->verifier(['canal' => 'sms', 'demande_id' => '8d3c1a52-8f0e-4f7e-9d77-0d8e8f1f0a11', 'code' => '123456'])
            ->assertStatus(422)->assertExactJson(['verifie' => false, 'motif' => 'code_invalide']);
    }

    public function test_sans_email_la_verification_passe_par_whatsapp(): void
    {
        Http::fake([
            'mailpulse.test/api/v1/verifications' => Http::response(['id' => 'ver_1', 'status' => 'pending', 'to_masked' => '+225******04', 'expires_at' => now()->addMinutes(10)->toIso8601String()], 201),
            'mailpulse.test/api/v1/verifications/ver_1/check' => Http::response(['status' => 'approved'], 200),
        ]);
        $candidature = $this->candidature(null);

        $verification = app(VerificationDuDepot::class)->apres($candidature);

        $this->assertSame('verification_telephone_requise', $verification->reponse()['statut']);
        $this->assertArrayHasKey('telephone_masque', $verification->reponse());
        $this->assertStringNotContainsString('01020304', $verification->reponse()['telephone_masque']);
        Http::assertSent(fn (RequeteHttp $r) => str_ends_with($r->url(), '/api/v1/verifications')
            && $r['channel'] === 'whatsapp' && $r['to'] === '+2250701020304' && $r['reference'] === $verification->demandeId);

        // Canal absent = e-mail : ne vaut pas pour une verification WhatsApp.
        $this->verifier(['demande_id' => $verification->demandeId, 'code' => '123456'])->assertStatus(422);

        $this->verifier(['canal' => 'telephone', 'demande_id' => $verification->demandeId, 'code' => '123456'])
            ->assertOk()->assertExactJson(['verifie' => true, 'type' => 'candidature']);
        $this->assertNotNull($candidature->fresh()->telephone_verifie_at);
    }

    public function test_whatsapp_indisponible_laisse_la_demande_visible(): void
    {
        Http::fake(['mailpulse.test/api/v1/verifications' => Http::response(['error' => 'whatsapp_indisponible'], 409)]);
        $candidature = $this->candidature(null);

        $this->assertNull(app(VerificationDuDepot::class)->apres($candidature));
        $this->assertSame(StatutVerificationContact::Impossible->value, $candidature->fresh()->verification_contact);
        $this->assertSame(1, ESBTPCandidature::query()->count());
    }

    public function test_un_premier_code_qui_ne_part_pas_ne_masque_jamais_la_demande(): void
    {
        Http::fake(['mailpulse.test/api/v1/verifications' => Http::response(['error' => 'envoi_echoue'], 502)]);
        $candidature = $this->candidature(null);

        $this->assertNull(app(VerificationDuDepot::class)->apres($candidature));
        $this->assertSame(1, ESBTPCandidature::query()->count(), 'Aucun code recu : la demande ne doit pas disparaitre.');
    }

    public function test_redeposer_en_boucle_ne_renvoie_pas_un_code_a_chaque_fois(): void
    {
        $this->mailpulseAccepteLesCourriels();
        $candidature = $this->candidature('awa.kone@gmail.com');
        $premiere = app(VerificationDuDepot::class)->apres($candidature);

        for ($i = 0; $i < 3; $i++) {
            $redepot = app(VerificationDuDepot::class)->apres($candidature->fresh());
            $this->assertSame($premiere->demandeId, $redepot->demandeId);
        }

        // Le premier envoi, puis un seul renvoi au plus dans la minute.
        Http::assertSentCount(2);
    }

    public function test_un_redepot_qui_change_l_adresse_d_une_demande_verifiee_la_reverifie(): void
    {
        $this->mailpulseAccepteLesCourriels();
        $candidature = $this->candidature('awa.kone@gmail.com');
        $verification = app(VerificationDuDepot::class)->apres($candidature);
        $this->verifier(['demande_id' => $verification->demandeId, 'code' => $this->codeEnvoye()])->assertOk();

        $rouverte = $candidature->fresh();
        $rouverte->update(['email' => 'awa.nouvelle@gmail.com']);
        $nouvelle = app(VerificationDuDepot::class)->apres($rouverte);

        $this->assertNotNull($nouvelle);
        $this->assertNull($candidature->fresh()->email_verifie_at);
        $this->assertSame(0, ESBTPCandidature::query()->count());
    }

    public function test_le_renvoi_est_limite_a_un_par_minute_sans_rien_reveler(): void
    {
        $this->mailpulseAccepteLesCourriels();
        $verification = app(VerificationDuDepot::class)->apres($this->candidature('awa.kone@gmail.com'));

        $this->renvoyer(['canal' => 'email', 'demande_id' => $verification->demandeId])->assertStatus(202);
        $this->renvoyer(['canal' => 'email', 'demande_id' => $verification->demandeId])->assertStatus(429)->assertJsonStructure(['retry_after']);
        $this->renvoyer(['demande_id' => '8d3c1a52-8f0e-4f7e-9d77-0d8e8f1f0a11'])->assertStatus(202);
        $this->renvoyer(['canal' => 'fax', 'demande_id' => $verification->demandeId])->assertStatus(422);
    }

    public function test_une_requete_non_signee_est_refusee(): void
    {
        $this->postJson('/api/portail/email/verifier', ['demande_id' => '8d3c1a52-8f0e-4f7e-9d77-0d8e8f1f0a11', 'code' => '123456'])->assertStatus(401);
    }

    private function candidature(?string $email): ESBTPCandidature
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);

        return ESBTPCandidature::create([
            'nom' => 'KONE', 'prenoms' => 'Awa', 'date_naissance' => '2007-01-01', 'telephone' => '+2250701020304',
            'email' => $email, 'annee_universitaire_id' => $annee->id,
            'consentement_at' => now(), 'statut' => ESBTPCandidature::STATUT_EN_ATTENTE,
        ]);
    }

    private function mailpulseAccepteLesCourriels(): void
    {
        Http::fake(['mailpulse.test/api/v1/messages' => Http::response([
            'dispatch' => ['state' => 'accepted', 'sms_fallback_eligible' => false],
            'message' => ['id' => 'msg_1', 'status' => 'queued'],
        ], 202)]);
    }

    private function texteEnvoye(): string
    {
        $texte = '';
        Http::assertSent(function (RequeteHttp $r) use (&$texte) {
            if (str_ends_with($r->url(), '/api/v1/messages')) {
                $texte = (string) ($r['content']['text'] ?? '');
            }

            return true;
        });

        return $texte;
    }

    private function codeEnvoye(): string
    {
        preg_match('/Votre code : (\d{6})/', $this->texteEnvoye(), $m);
        $this->assertNotEmpty($m, 'Le courriel doit contenir un code a six chiffres.');

        return $m[1];
    }

    private function verifier(array $corps)
    {
        return $this->signe('api/portail/email/verifier', $corps);
    }

    private function renvoyer(array $corps)
    {
        return $this->signe('api/portail/email/renvoyer', $corps);
    }

    private function signe(string $chemin, array $donnees)
    {
        $corps = json_encode($donnees + ['ip_client' => '41.66.10.24']);
        $horodatage = (int) (microtime(true) * 1000) + $this->horloge++;

        return $this->call('POST', '/'.$chemin, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_KLASSCI_SIGNATURE' => app(PortailSignatureVerifier::class)->signature($corps, 'POST', $chemin, $horodatage),
            'HTTP_X_KLASSCI_TIMESTAMP' => (string) $horodatage,
        ], $corps);
    }
}
