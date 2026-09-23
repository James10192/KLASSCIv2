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
 * Le depot de reinscription de bout en bout : la reponse annonce la
 * verification, la demande reste hors de la corbeille, un second depot relance
 * la meme verification, et le bon code la fait entrer.
 */
class DepotReinscriptionVerifieTest extends TestCase
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
    }

    public function test_le_depot_attend_le_code_avant_d_entrer_dans_la_corbeille(): void
    {
        $identite = ['matricule' => 'DEMO-0001', 'date_naissance' => '2004-03-15'];
        $depotCorps = $identite + ['consentement' => true];

        $depot = $this->appeler('api/public/reinscription/submit', $depotCorps)
            ->assertStatus(201)
            ->assertJson(['enregistre' => true, 'statut' => 'verification_email_requise', 'email_masque' => 'a***@gmail.com']);
        $demandeId = $depot->json('demande_id');

        $this->assertSame(0, ESBTPReinscriptionDemande::query()->count());
        $this->appeler('api/public/reinscription/lookup', $identite)->assertJson(['demande_existante' => false]);

        // Page perdue : la famille redepose, et retrouve la meme verification.
        $this->appeler('api/public/reinscription/submit', $depotCorps)->assertStatus(201)->assertJson(['demande_id' => $demandeId]);
        $this->assertSame(1, ESBTPReinscriptionDemande::sansFiltreVerification()->count());

        $this->appeler('api/portail/email/verifier', ['canal' => 'email', 'demande_id' => $demandeId, 'code' => $this->dernierCode()])
            ->assertOk()->assertExactJson(['verifie' => true, 'type' => 'reinscription']);

        $this->assertSame(1, ESBTPReinscriptionDemande::query()->count());
        $this->appeler('api/public/reinscription/lookup', $identite)->assertJson(['demande_existante' => true]);
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
