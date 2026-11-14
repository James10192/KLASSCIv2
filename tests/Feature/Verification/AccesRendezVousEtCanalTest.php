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
use App\Services\RendezVous\JetonAccesRdv;
use App\Services\RendezVous\RendezVousReglages;
use App\Services\TenantScolariteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as RequeteHttp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Le portail de reinscription, retours de terrain de novembre 2026 :
 *
 * - le matricule se retrouve malgre espaces, tirets et casse, sans jamais
 *   devenir une recherche ;
 * - la famille voit nom et prenoms, et ou partira son code ;
 * - elle peut recevoir ce code par e-mail, a une nouvelle adresse, quand
 *   WhatsApp ne passe pas — adresse rangee sur la demande, pas sur le dossier ;
 * - la convocation ne s'ouvre plus sur la seule date de naissance : il faut un
 *   code, ou le jeton rendu par le code du depot.
 */
class AccesRendezVousEtCanalTest extends TestCase
{
    use ActiveLaVerificationContact;
    use RefreshDatabase;

    private const SECRET = 'un-secret-de-test-suffisamment-long-pour-passer';

    private int $horloge = 0;

    private ESBTPEtudiant $etudiant;

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

        foreach ([
            TenantScolariteSettings::REINSCRIPTION_EN_LIGNE => ['1', 'boolean'],
            PortailReinscriptionService::REGLAGE_OUVERTURE => ['', 'string'],
            PortailReinscriptionService::REGLAGE_FERMETURE => ['', 'string'],
            RendezVousReglages::ENABLED => ['1', 'boolean'],
        ] as $cle => [$valeur, $type]) {
            Setting::updateOrCreate(['key' => $cle], ['value' => $valeur, 'type' => $type, 'group' => 'scolarite', 'is_active' => true]);
        }
        Cache::flush();

        $filiere = ESBTPFiliere::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create();
        $passee = ESBTPAnneeUniversitaire::factory()->create(['name' => '2024-2025', 'start_date' => '2024-09-01', 'end_date' => '2025-07-31', 'is_current' => false]);
        ESBTPAnneeUniversitaire::factory()->create(['name' => '2025-2026', 'start_date' => '2025-09-01', 'end_date' => '2026-07-31', 'is_current' => true]);
        $this->etudiant = ESBTPEtudiant::factory()->create([
            'matricule' => 'ESBTP-24/0413', 'nom' => 'KOUASSI', 'prenoms' => 'Ama Grace', 'date_naissance' => '2004-03-15',
            'email' => 'ama.kouassi@gmail.com', 'email_personnel' => null, 'telephone' => '+2250701020304',
        ]);
        $classe = ESBTPClasse::factory()->create(['name' => 'BTS2 A', 'filiere_id' => $filiere->id, 'niveau_etude_id' => $niveau->id, 'is_active' => true]);
        ESBTPInscription::factory()->create([
            'etudiant_id' => $this->etudiant->id, 'filiere_id' => $filiere->id, 'niveau_id' => $niveau->id,
            'classe_id' => $classe->id, 'annee_universitaire_id' => $passee->id, 'status' => 'active',
        ]);
        Http::fake([
            'mailpulse.test/api/v1/messages' => Http::response([
                'dispatch' => ['state' => 'accepted', 'sms_fallback_eligible' => false],
                'message' => ['id' => 'msg_1', 'status' => 'queued'],
            ], 202),
            'mailpulse.test/api/v1/verifications' => Http::response(['id' => 'ver_1', 'status' => 'pending', 'to_masked' => '+225******04', 'expires_at' => now()->addMinutes(10)->toIso8601String()], 201),
        ]);
        $this->reglerVerificationContact(true);
    }

    public function test_le_matricule_se_retrouve_sans_ses_separateurs_mais_jamais_par_approximation(): void
    {
        $naissance = '2004-03-15';

        foreach (['ESBTP-24/0413', 'esbtp 24 0413', 'Esbtp.24-0413', "ESBTP\u{00A0}240413"] as $saisie) {
            $this->appeler('api/public/reinscription/lookup', ['matricule' => $saisie, 'date_naissance' => $naissance])
                ->assertOk()->assertJson(['trouve' => true], "« {$saisie} » doit retrouver le dossier.");
        }

        // Un chiffre de moins, ou la mauvaise date : rien, comme avant.
        $this->appeler('api/public/reinscription/lookup', ['matricule' => 'ESBTP24041', 'date_naissance' => $naissance])
            ->assertJson(['trouve' => false]);
        $this->appeler('api/public/reinscription/lookup', ['matricule' => 'ESBTP240413', 'date_naissance' => '2004-03-16'])
            ->assertJson(['trouve' => false]);
    }

    public function test_deux_matricules_identiques_une_fois_normalises_ne_s_ouvrent_pas_au_hasard(): void
    {
        ESBTPEtudiant::factory()->create(['matricule' => 'ESBTP240413', 'date_naissance' => '2004-03-15']);

        // Les deux sont nes le meme jour : impossible de choisir, donc introuvable.
        $this->appeler('api/public/reinscription/lookup', ['matricule' => 'esbtp 24 0413', 'date_naissance' => '2004-03-15'])
            ->assertJson(['trouve' => false]);
        // La saisie exacte, elle, reste sans ambiguite.
        $this->appeler('api/public/reinscription/lookup', ['matricule' => 'ESBTP-24/0413', 'date_naissance' => '2004-03-15'])
            ->assertJson(['trouve' => true]);
    }

    public function test_la_consultation_donne_nom_complet_et_contacts_masques(): void
    {
        $this->appeler('api/public/reinscription/lookup', ['matricule' => 'ESBTP-24/0413', 'date_naissance' => '2004-03-15'])
            ->assertOk()
            ->assertJson([
                'prenom' => 'Ama',
                'nom' => 'KOUASSI',
                'prenoms' => 'Ama Grace',
                'verification' => true,
                'contacts' => ['email_masque' => 'a***@gmail.com'],
            ])
            ->assertJsonMissing(['email' => 'ama.kouassi@gmail.com']);
    }

    public function test_une_nouvelle_adresse_recoit_le_code_sans_toucher_au_dossier(): void
    {
        $this->appeler('api/public/reinscription/submit', [
            'matricule' => 'ESBTP-24/0413', 'date_naissance' => '2004-03-15', 'consentement' => true,
            'email' => 'ama.nouvelle@gmail.com',
        ])->assertStatus(201)->assertJson(['statut' => 'verification_email_requise', 'email_masque' => 'a***@gmail.com']);

        $this->assertSame('ama.nouvelle@gmail.com', ESBTPReinscriptionDemande::query()->sole()->email_contact);
        $this->assertSame('ama.kouassi@gmail.com', $this->etudiant->fresh()->email, 'Le dossier ne change pas sans l’ecole.');
        $this->assertSame('ama.nouvelle@gmail.com', $this->dernierDestinataire());
    }

    public function test_whatsapp_qui_ne_passe_pas_bascule_sur_l_e_mail(): void
    {
        $this->appeler('api/public/reinscription/submit', [
            'matricule' => 'ESBTP-24/0413', 'date_naissance' => '2004-03-15', 'consentement' => true, 'canal' => 'telephone',
        ])->assertStatus(201)->assertJson(['statut' => 'verification_telephone_requise']);
        $demandeId = \App\Models\ESBTPVerificationContact::query()->sole()->demande_id;

        $this->appeler('api/portail/email/changer-canal', ['demande_id' => $demandeId, 'canal' => 'email', 'email' => 'ama.lue@gmail.com'])
            ->assertOk()
            ->assertJson(['envoye' => true, 'statut' => 'verification_email_requise', 'demande_id' => $demandeId, 'email_masque' => 'a***@gmail.com']);

        $this->assertSame('ama.lue@gmail.com', $this->dernierDestinataire());
        $this->appeler('api/portail/email/verifier', ['canal' => 'email', 'demande_id' => $demandeId, 'code' => $this->dernierCode()])
            ->assertOk()->assertJson(['verifie' => true, 'type' => 'reinscription']);

        $this->assertSame('ama.lue@gmail.com', ESBTPReinscriptionDemande::query()->sole()->emailRdv());
    }

    public function test_une_adresse_qui_ne_recoit_rien_est_refusee(): void
    {
        $this->appeler('api/public/reinscription/submit', [
            'matricule' => 'ESBTP-24/0413', 'date_naissance' => '2004-03-15', 'consentement' => true,
        ])->assertStatus(201);
        $demandeId = \App\Models\ESBTPVerificationContact::query()->sole()->demande_id;

        $this->appeler('api/portail/email/changer-canal', ['demande_id' => $demandeId, 'canal' => 'email', 'email' => 'ama@exemple.test'])
            ->assertStatus(422)->assertJson(['envoye' => false, 'motif' => 'email_invalide']);
    }

    public function test_la_convocation_s_ouvre_avec_le_jeton_du_depot_et_pas_sans_lui(): void
    {
        $depot = $this->appeler('api/public/reinscription/submit', [
            'matricule' => 'ESBTP-24/0413', 'date_naissance' => '2004-03-15', 'consentement' => true,
        ])->assertStatus(201);
        $reference = (string) $depot->json('reference_publique');
        $demandeId = (string) $depot->json('demande_id');

        $verifie = $this->appeler('api/portail/email/verifier', ['canal' => 'email', 'demande_id' => $demandeId, 'code' => $this->dernierCode()])
            ->assertOk()->assertJson(['verifie' => true]);
        $jeton = (string) $verifie->json('jeton_acces');
        $this->assertNotSame('', $jeton);

        // Sans jeton : rien de la convocation, et un code part au contact du dossier.
        $sansJeton = $this->appeler('api/public/rendez-vous/consulter', ['reference' => $reference, 'date_naissance' => '2004-03-15'])
            ->assertOk()->assertJson(['trouve' => true, 'statut' => 'verification_email_requise'])
            ->assertJsonMissingPath('reservation');

        // L'ecran qui suit un depot lit sans jamais declencher d'envoi.
        Http::fake();
        $this->appeler('api/public/rendez-vous/consulter', ['reference' => $reference, 'date_naissance' => '2004-03-15', 'sans_code' => true])
            ->assertOk()->assertExactJson(['trouve' => true, 'statut' => 'verification_requise']);
        Http::assertNothingSent();

        // Avec le jeton : la convocation s'ouvre.
        $this->appeler('api/public/rendez-vous/consulter', ['reference' => $reference, 'date_naissance' => '2004-03-15', 'jeton_acces' => $jeton])
            ->assertOk()->assertJson(['trouve' => true])->assertJsonStructure(['reservation', 'peut_modifier']);

        // Les gestes qui modifient le rendez-vous exigent le jeton.
        $this->appeler('api/public/rendez-vous/annuler', ['reference' => $reference, 'date_naissance' => '2004-03-15'])
            ->assertStatus(403)->assertJson(['code' => 'verification_requise']);

        $this->assertIsString($sansJeton->json('demande_id'));
    }

    public function test_le_code_d_acces_rend_un_jeton_qui_ne_vaut_que_pour_son_dossier(): void
    {
        $depot = $this->appeler('api/public/reinscription/submit', [
            'matricule' => 'ESBTP-24/0413', 'date_naissance' => '2004-03-15', 'consentement' => true,
        ])->assertStatus(201);
        $reference = (string) $depot->json('reference_publique');

        $acces = $this->appeler('api/public/rendez-vous/consulter', ['reference' => $reference, 'date_naissance' => '2004-03-15'])
            ->assertJson(['statut' => 'verification_email_requise']);
        $idAcces = (string) $acces->json('demande_id');

        $this->appeler('api/portail/email/verifier', ['canal' => 'email', 'demande_id' => $idAcces, 'code' => '000000'])
            ->assertStatus(422)->assertJson(['verifie' => false, 'motif' => 'code_invalide']);

        $reussi = $this->appeler('api/portail/email/verifier', ['canal' => 'email', 'demande_id' => $idAcces, 'code' => $this->dernierCode()])
            ->assertOk()->assertJson(['verifie' => true, 'type' => 'rendez_vous']);
        $jeton = (string) $reussi->json('jeton_acces');

        $normalisee = app(\App\Services\Portail\ReferencePublique::class)->normaliser($reference);
        $this->assertTrue(JetonAccesRdv::valide($jeton, $normalisee));
        $this->assertFalse(JetonAccesRdv::valide($jeton, $normalisee.'X'), 'Un jeton ne s’applique qu’a son dossier.');
        $this->assertFalse(JetonAccesRdv::valide($jeton, $normalisee, time() + 3600), 'Il expire.');
    }

    public function test_sans_verification_activee_le_portail_ouvre_la_convocation_comme_avant(): void
    {
        $this->reglerVerificationContact(false);
        $depot = $this->appeler('api/public/reinscription/submit', [
            'matricule' => 'ESBTP-24/0413', 'date_naissance' => '2004-03-15', 'consentement' => true,
        ])->assertStatus(201);

        $this->appeler('api/public/rendez-vous/consulter', ['reference' => (string) $depot->json('reference_publique'), 'date_naissance' => '2004-03-15'])
            ->assertOk()->assertJson(['trouve' => true])->assertJsonMissingPath('statut');
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

    private function dernierDestinataire(): string
    {
        $destinataire = '';
        Http::assertSent(function (RequeteHttp $r) use (&$destinataire) {
            if (($r['recipient']['type'] ?? null) === 'email') {
                $destinataire = (string) $r['recipient']['value'];
            }

            return true;
        });

        return $destinataire;
    }

    private function appeler(string $chemin, array $donnees)
    {
        $corps = json_encode($donnees + ['ip_client' => '41.66.10.'.(24 + $this->horloge % 200)]);
        $horodatage = (int) (microtime(true) * 1000) + $this->horloge++;

        return $this->call('POST', '/'.$chemin, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_KLASSCI_SIGNATURE' => app(PortailSignatureVerifier::class)->signature($corps, 'POST', $chemin, $horodatage),
            'HTTP_X_KLASSCI_TIMESTAMP' => (string) $horodatage,
        ], $corps);
    }
}
