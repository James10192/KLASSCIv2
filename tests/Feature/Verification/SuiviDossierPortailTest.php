<?php

namespace Tests\Feature\Verification;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
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
 * Une famille revient suivre sa demande deja deposee : situation, adresse a
 * verifier ou corriger (posee sur la demande, jamais sur la fiche), convocation
 * a recevoir, reference oubliee envoyee par e-mail.
 */
class SuiviDossierPortailTest extends TestCase
{
    use ActiveLaVerificationContact;
    use RefreshDatabase;

    private const SECRET = 'un-secret-de-test-suffisamment-long-pour-passer';

    private const IDENTITE = ['identifiant' => 'DEMO-0001', 'date_naissance' => '2004-03-15'];

    private int $horloge = 0;

    private ESBTPEtudiant $etudiant;

    private ESBTPAnneeUniversitaire $cible;

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
        $this->cible = ESBTPAnneeUniversitaire::factory()->create(['name' => '2025-2026', 'start_date' => '2025-09-01', 'end_date' => '2026-07-31', 'is_current' => true]);
        $this->etudiant = ESBTPEtudiant::factory()->create([
            'matricule' => 'DEMO-0001', 'nom' => 'KOUASSI', 'prenoms' => 'Ama Grace', 'date_naissance' => '2004-03-15',
            'email' => 'ama.kouassi@gmail.com', 'email_personnel' => null, 'telephone' => '+2250701020304',
        ]);
        $classe = ESBTPClasse::factory()->create(['name' => 'BTS2 A', 'filiere_id' => $filiere->id, 'niveau_etude_id' => $niveau->id, 'is_active' => true]);
        ESBTPInscription::factory()->create([
            'etudiant_id' => $this->etudiant->id, 'filiere_id' => $filiere->id, 'niveau_id' => $niveau->id,
            'classe_id' => $classe->id, 'annee_universitaire_id' => $passee->id, 'status' => 'active',
        ]);
        Http::fake(['mailpulse.test/api/v1/*' => Http::response([
            'dispatch' => ['state' => 'accepted', 'sms_fallback_eligible' => false],
            'message' => ['id' => 'msg_1', 'status' => 'queued'],
        ], 202)]);

        // Deposee avant l'activation de la verification : aucune adresse prouvee.
        $this->reglerVerificationContact(false);
        $this->appeler('api/public/reinscription/submit', ['matricule' => 'DEMO-0001', 'date_naissance' => '2004-03-15', 'consentement' => true])->assertStatus(201);
        $this->reglerVerificationContact(true);
    }

    public function test_la_famille_retrouve_sa_demande_par_matricule_ou_reference(): void
    {
        $situation = $this->appeler('api/public/suivi/consulter', self::IDENTITE)->assertOk()
            ->assertJson(['trouve' => true, 'type' => 'reinscription', 'statut' => ['code' => 'en_attente'],
                'contact' => ['email_masque' => 'a***@gmail.com', 'email_verifie' => false], 'rendez_vous' => null])
            ->json();

        $this->appeler('api/public/suivi/consulter', ['identifiant' => $situation['reference'], 'date_naissance' => '2004-03-15'])
            ->assertOk()->assertJson(['trouve' => true, 'type' => 'reinscription']);
    }

    public function test_une_identite_fausse_rend_toujours_le_meme_corps(): void
    {
        $this->appeler('api/public/suivi/consulter', ['identifiant' => 'DEMO-0001', 'date_naissance' => '2004-03-16'])
            ->assertOk()->assertExactJson(['trouve' => false]);
        $this->appeler('api/public/suivi/consulter', ['identifiant' => 'INCONNU', 'date_naissance' => '2004-03-15'])
            ->assertOk()->assertExactJson(['trouve' => false]);
    }

    public function test_la_nouvelle_adresse_va_sur_la_demande_et_se_prouve_par_code(): void
    {
        $this->appeler('api/public/suivi/email', self::IDENTITE + ['email' => 'Grace.Kouassi@yahoo.fr'])
            ->assertOk()->assertJson(['code' => 'code_envoye', 'statut' => 'verification_email_requise', 'email_masque' => 'g***@yahoo.fr']);

        $demande = ESBTPReinscriptionDemande::query()->sole();
        $this->assertSame('grace.kouassi@yahoo.fr', $demande->email_contact);
        $this->assertSame('ama.kouassi@gmail.com', $this->etudiant->fresh()->email, 'La fiche de l\'etudiant n\'est jamais reecrite.');
        $this->assertTrue($demande->contactAConfirmer(), 'Adresse neuve : retenue jusqu\'au code.');

        $demandeId = \App\Models\ESBTPVerificationContact::query()->sole()->demande_id;
        $this->appeler('api/portail/email/verifier', ['canal' => 'email', 'demande_id' => $demandeId, 'code' => $this->dernierCode()])
            ->assertOk()->assertJson(['verifie' => true]);

        $this->appeler('api/public/suivi/consulter', self::IDENTITE)
            ->assertJson(['contact' => ['email_masque' => 'g***@yahoo.fr', 'email_verifie' => true, 'a_confirmer' => false]]);
    }

    public function test_la_convocation_repart_a_la_demande_de_la_famille(): void
    {
        $demande = ESBTPReinscriptionDemande::query()->sole();
        $creneau = ESBTPRdvCreneau::create(['annee_universitaire_id' => $this->cible->id, 'date' => now()->addDays(3)->toDateString(),
            'heure_debut' => '09:00:00', 'heure_fin' => '09:30:00', 'capacite' => 5, 'ouvert' => true]);
        ESBTPRdvReservation::create(['creneau_id' => $creneau->id, 'reinscription_demande_id' => $demande->id, 'statut' => 'confirmee',
            'nom' => 'KOUASSI', 'prenoms' => 'Ama Grace', 'telephone' => '+2250701020304', 'date_naissance' => '2004-03-15',
            'email' => 'ancienne@rebond.ci', 'convocation_statut' => 'echec', 'convocation_action' => 'confirme']);
        Http::fake(['mailpulse.test/api/v1/*' => Http::response([
            'dispatch' => ['state' => 'accepted', 'sms_fallback_eligible' => false],
            'message' => ['id' => 'msg_2', 'status' => 'queued'],
        ], 202)]);

        $this->appeler('api/public/suivi/convocation', self::IDENTITE)->assertOk()->assertJson(['code' => 'envoyee']);

        $this->assertSame('ama.kouassi@gmail.com', ESBTPRdvReservation::query()->sole()->email, 'L\'adresse du dossier remplace celle qui a rebondi.');
        Http::assertSent(fn (RequeteHttp $r) => ($r['recipient']['value'] ?? null) === 'ama.kouassi@gmail.com'
            && str_contains((string) ($r['content']['text'] ?? ''), '/convocation-rdv/'));
    }

    public function test_sans_rendez_vous_la_convocation_est_refusee_proprement(): void
    {
        $this->appeler('api/public/suivi/convocation', self::IDENTITE)->assertStatus(409)->assertJson(['code' => 'sans_rendez_vous']);
    }

    public function test_la_reference_oubliee_part_par_e_mail_et_jamais_a_l_ecran(): void
    {
        Http::fake(['mailpulse.test/api/v1/*' => Http::response(['dispatch' => ['state' => 'accepted', 'sms_fallback_eligible' => false], 'message' => ['id' => 'msg_3']], 202)]);
        $reference = app(\App\Services\Portail\ReferencePublique::class)->formater(ESBTPReinscriptionDemande::query()->sole()->assurerReferencePublique());

        $this->appeler('api/public/suivi/reference-oubliee', ['email' => 'AMA.kouassi@gmail.com', 'date_naissance' => '2004-03-15'])
            ->assertStatus(202)->assertExactJson(['envoye' => true]);
        Http::assertSent(fn (RequeteHttp $r) => ($r['recipient']['value'] ?? null) === 'ama.kouassi@gmail.com'
            && str_contains((string) ($r['content']['text'] ?? ''), $reference));

        // Inconnu : meme reponse, rien ne part.
        $avant = count(Http::recorded());
        $this->appeler('api/public/suivi/reference-oubliee', ['email' => 'personne@gmail.com', 'date_naissance' => '2004-03-15'])
            ->assertStatus(202)->assertExactJson(['envoye' => true]);
        $this->assertCount($avant, Http::recorded());
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
