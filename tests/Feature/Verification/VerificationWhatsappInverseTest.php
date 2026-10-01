<?php

namespace Tests\Feature\Verification;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCandidature;
use App\Models\Setting;
use App\Models\User;
use App\Services\Reinscription\PortailSignatureVerifier;
use App\Services\TenantScolariteSettings;
use App\Services\Verification\DemarrageVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as RequeteHttp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Verification WhatsApp inversee : la famille envoie le code au numero de
 * l'ecole depuis un lien wa.me, MailPulse approuve sur son message, et le
 * site suit l'avancement par la route de statut.
 *
 * MailPulse est simule (Http::fake) : aucun message ne part.
 */
class VerificationWhatsappInverseTest extends TestCase
{
    use ActiveLaVerificationContact;
    use RefreshDatabase;

    private const SECRET = 'un-secret-de-test-suffisamment-long-pour-passer';

    private const LIEN = 'https://wa.me/22541540178?text=Je%20confirme%20mon%20num%C3%A9ro%20WhatsApp.%20Code%20%3A%20123456';

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
        $this->reglerVerificationContact(true);
    }

    public function test_le_depot_rend_le_lien_et_le_statut_suit_jusqu_a_la_verification(): void
    {
        $this->reglerInverse(true);
        $approuvee = false;
        Http::fake(function (RequeteHttp $r) use (&$approuvee) {
            if ($r->method() === 'POST' && str_ends_with($r->url(), '/api/v1/verifications')) {
                return Http::response(['id' => 'ver_1', 'status' => 'pending', 'mode' => 'reverse', 'wa_link' => self::LIEN, 'message' => 'Code : 123456'], 201);
            }
            if ($r->method() === 'GET' && str_ends_with($r->url(), '/api/v1/verifications/ver_1')) {
                return Http::response(['id' => 'ver_1', 'status' => $approuvee ? 'approved' : 'pending'], 200);
            }

            return Http::response(['error' => 'inattendu'], 500);
        });
        $candidature = $this->candidature();

        $verification = app(DemarrageVerification::class)->apresDepot($candidature);

        $reponse = $verification->reponse();
        $this->assertSame('verification_telephone_requise', $reponse['statut']);
        $this->assertSame(self::LIEN, $reponse['lien_whatsapp']);
        Http::assertSent(fn (RequeteHttp $r) => $r->method() === 'POST' && ($r['mode'] ?? null) === 'reverse');

        $this->statut(['canal' => 'telephone', 'demande_id' => $verification->demandeId])
            ->assertStatus(202)->assertExactJson(['verifie' => false, 'motif' => 'en_attente', 'lien_whatsapp' => self::LIEN]);
        $this->assertNull($candidature->fresh()->telephone_verifie_at);

        $approuvee = true;
        $this->statut(['canal' => 'telephone', 'demande_id' => $verification->demandeId])
            ->assertOk()->assertExactJson(['verifie' => true, 'type' => 'candidature']);
        $this->assertNotNull($candidature->fresh()->telephone_verifie_at);

        // Deja verifiee : repond sans rappeler MailPulse.
        $this->statut(['canal' => 'telephone', 'demande_id' => $verification->demandeId])->assertOk();
    }

    public function test_sans_numero_propre_le_code_repart_dans_l_autre_sens(): void
    {
        $this->reglerInverse(true);
        Http::fake(function (RequeteHttp $r) {
            return ($r['mode'] ?? null) === 'reverse'
                ? Http::response(['error' => 'inverse_indisponible'], 409)
                : Http::response(['id' => 'ver_2', 'status' => 'pending'], 201);
        });

        $verification = app(DemarrageVerification::class)->apresDepot($this->candidature());

        $this->assertArrayNotHasKey('lien_whatsapp', $verification->reponse());
        Http::assertSentCount(2);
        Http::assertSent(fn (RequeteHttp $r) => $r->method() === 'POST' && ! isset($r['mode']));
    }

    public function test_reglage_coupe_aucun_mode_inverse_n_est_demande(): void
    {
        Http::fake(['mailpulse.test/api/v1/verifications' => Http::response(['id' => 'ver_3', 'status' => 'pending'], 201)]);

        $verification = app(DemarrageVerification::class)->apresDepot($this->candidature());

        $this->assertArrayNotHasKey('lien_whatsapp', $verification->reponse());
        Http::assertNotSent(fn (RequeteHttp $r) => isset($r['mode']));
    }

    public function test_une_demande_inconnue_au_statut_repond_comme_un_code_faux(): void
    {
        $this->statut(['canal' => 'telephone', 'demande_id' => '8d3c1a52-8f0e-4f7e-9d77-0d8e8f1f0a11'])
            ->assertStatus(422)->assertExactJson(['verifie' => false, 'motif' => 'code_invalide']);
    }

    private function reglerInverse(bool $actif): void
    {
        Setting::updateOrCreate(['key' => TenantScolariteSettings::VERIFICATION_WHATSAPP_INVERSE], [
            'value' => $actif ? '1' : '0',
            'type' => 'boolean',
            'group' => 'scolarite',
            'is_active' => true,
        ]);
        Cache::flush();
    }

    private function candidature(): ESBTPCandidature
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);

        return ESBTPCandidature::create([
            'nom' => 'KONE', 'prenoms' => 'Awa', 'date_naissance' => '2007-01-01', 'telephone' => '+2250701020304',
            'email' => null, 'annee_universitaire_id' => $annee->id,
            'consentement_at' => now(), 'statut' => ESBTPCandidature::STATUT_EN_ATTENTE,
        ]);
    }

    private function statut(array $donnees)
    {
        $chemin = 'api/portail/email/statut';
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
