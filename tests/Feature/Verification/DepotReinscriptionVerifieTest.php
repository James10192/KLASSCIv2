<?php

namespace Tests\Feature\Verification;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPReinscriptionDemande;
use App\Models\Setting;
use App\Models\User;
use App\Services\Reinscription\PortailReinscriptionService;
use App\Services\Reinscription\PortailSignatureVerifier;
use App\Services\TenantScolariteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as RequeteHttp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Le depot de reinscription de bout en bout, reglage actif puis coupe : la
 * reponse annonce (ou non) la verification, la demande est dans la corbeille
 * des le depot, avec son badge et sous le filtre « Contact non verifie »
 * jusqu'au bon code.
 */
class DepotReinscriptionVerifieTest extends TestCase
{
    use ActiveLaVerificationContact;
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
        ]);
        User::factory()->create(['id' => 1])->assignRole(Role::firstOrCreate(['name' => 'superAdmin', 'guard_name' => 'web']));

        foreach ([TenantScolariteSettings::REINSCRIPTION_EN_LIGNE => '1', PortailReinscriptionService::REGLAGE_OUVERTURE => '', PortailReinscriptionService::REGLAGE_FERMETURE => ''] as $cle => $valeur) {
            Setting::updateOrCreate(['key' => $cle], ['value' => $valeur, 'type' => $cle === TenantScolariteSettings::REINSCRIPTION_EN_LIGNE ? 'boolean' : 'string', 'group' => 'scolarite', 'is_active' => true]);
        }
        Cache::flush();

        $filiere = ESBTPFiliere::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create();
        $passee = ESBTPAnneeUniversitaire::factory()->create(['name' => '2024-2025', 'start_date' => '2024-09-01', 'end_date' => '2025-07-31', 'is_current' => false]);
        ESBTPAnneeUniversitaire::factory()->create(['name' => '2025-2026', 'start_date' => '2025-09-01', 'end_date' => '2026-07-31', 'is_current' => true]);
        $etudiant = ESBTPEtudiant::factory()->create([
            'matricule' => 'DEMO-0001', 'nom' => 'KOUASSI', 'prenoms' => 'Ama Grace', 'date_naissance' => '2004-03-15',
            'email' => 'ama.kouassi@gmail.com', 'email_personnel' => null, 'telephone' => '+2250701020304',
        ]);
        $classe = ESBTPClasse::factory()->create(['name' => 'BTS2 A', 'filiere_id' => $filiere->id, 'niveau_etude_id' => $niveau->id, 'is_active' => true]);
        ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id, 'filiere_id' => $filiere->id, 'niveau_id' => $niveau->id,
            'classe_id' => $classe->id, 'annee_universitaire_id' => $passee->id, 'status' => 'active',
        ]);
        Http::fake(['mailpulse.test/api/v1/messages' => Http::response([
            'dispatch' => ['state' => 'accepted', 'sms_fallback_eligible' => false],
            'message' => ['id' => 'msg_1', 'status' => 'queued'],
        ], 202)]);
        $this->reglerVerificationContact(true);
    }

    public function test_le_depot_marque_la_demande_jusqu_au_code(): void
    {
        $identite = ['matricule' => 'DEMO-0001', 'date_naissance' => '2004-03-15'];
        $depotCorps = $identite + ['consentement' => true];

        $depot = $this->appeler('api/public/reinscription/submit', $depotCorps)
            ->assertStatus(201)
            ->assertJson(['enregistre' => true, 'statut' => 'verification_email_requise', 'email_masque' => 'a***@gmail.com']);
        $demandeId = $depot->json('demande_id');

        $this->assertTrue(ESBTPReinscriptionDemande::query()->sole()->contactNonVerifie(), 'Transmise, mais marquee.');
        $this->appeler('api/public/reinscription/lookup', $identite)->assertJson(['demande_existante' => true]);

        // Page perdue : la famille redepose, et retrouve la meme verification.
        $this->appeler('api/public/reinscription/submit', $depotCorps)->assertStatus(201)->assertJson(['demande_id' => $demandeId]);
        $this->assertSame(1, ESBTPReinscriptionDemande::query()->count());

        $this->appeler('api/portail/email/verifier', ['canal' => 'email', 'demande_id' => $demandeId, 'code' => $this->dernierCode()])
            ->assertOk()->assertExactJson(['verifie' => true, 'type' => 'reinscription']);

        $this->assertSame(1, ESBTPReinscriptionDemande::query()->count());
        $this->appeler('api/public/reinscription/lookup', $identite)->assertJson(['demande_existante' => true]);
    }

    public function test_reglage_desactive_le_portail_repond_comme_avant(): void
    {
        $this->reglerVerificationContact(false);

        $this->appeler('api/public/reinscription/submit', ['matricule' => 'DEMO-0001', 'date_naissance' => '2004-03-15', 'consentement' => true])
            ->assertStatus(201)
            ->assertJson(['enregistre' => true, 'message' => 'Votre demande a bien été transmise à votre établissement.'])
            ->assertJsonMissingPath('statut')
            ->assertJsonMissingPath('demande_id');

        Http::assertNothingSent();
        $this->assertNull(ESBTPReinscriptionDemande::query()->sole()->verification_contact);
        $this->assertSame(0, \App\Models\ESBTPVerificationContact::query()->count());
    }

    public function test_reglage_actif_la_demande_est_dans_la_corbeille_avec_son_badge_et_sous_le_filtre(): void
    {
        $this->appeler('api/public/reinscription/submit', ['matricule' => 'DEMO-0001', 'date_naissance' => '2004-03-15', 'consentement' => true])
            ->assertStatus(201)->assertJson(['statut' => 'verification_email_requise']);
        $this->actingAs($this->secretaire());

        $this->get(route('esbtp.reinscription-demandes.index'))
            ->assertOk()->assertSee('KOUASSI')->assertSee('pas de convocation automatique tant que le contact');
        $this->get(route('esbtp.reinscription-demandes.index', ['contact' => 'non_verifie']))
            ->assertOk()->assertSee('KOUASSI');

        $demandeId = \App\Models\ESBTPVerificationContact::query()->sole()->demande_id;
        $this->appeler('api/portail/email/verifier', ['canal' => 'email', 'demande_id' => $demandeId, 'code' => $this->dernierCode()])->assertOk();

        $this->get(route('esbtp.reinscription-demandes.index', ['contact' => 'non_verifie']))
            ->assertOk()->assertDontSee('KOUASSI');
        $this->get(route('esbtp.reinscription-demandes.index'))
            ->assertOk()->assertSee('KOUASSI')->assertDontSee('pas de convocation automatique tant que le contact');
    }

    private function secretaire(): User
    {
        $role = Role::firstOrCreate(['name' => 'secretaire-verification', 'guard_name' => 'web']);
        foreach (['reinscriptions.demandes.view', 'reinscriptions.demandes.process'] as $nom) {
            $role->givePermissionTo(\Spatie\Permission\Models\Permission::firstOrCreate(['name' => $nom, 'guard_name' => 'web']));
        }
        $user = User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]);
        $user->assignRole($role);

        return $user;
    }

    private function dernierCode(): string
    {
        $code = '';
        Http::assertSent(function (RequeteHttp $r) use (&$code) {
            if (preg_match('/Votre code : (\d{6})/', (string) ($r['content']['text'] ?? ''), $m)) {
                $code = $m[1];
            }

            return true;
        });

        return $code;
    }

    private function appeler(string $chemin, array $donnees)
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
