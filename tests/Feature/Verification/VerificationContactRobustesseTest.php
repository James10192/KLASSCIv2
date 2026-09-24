<?php

namespace Tests\Feature\Verification;

use App\Enums\StatutConvocationRdv;
use App\Enums\StatutVerificationContact;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Models\ESBTPVerificationContact;
use App\Models\Setting;
use App\Models\User;
use App\Services\Inscription\PortailCandidatureService;
use App\Services\Reinscription\PortailReinscriptionService;
use App\Services\Reinscription\PortailSignatureVerifier;
use App\Services\RendezVous\MessagerieRdv;
use App\Services\Verification\DemarrageVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as RequeteHttp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Les cas limites de la verification : depot reel, courses entre un appel
 * MailPulse et un nouvel envoi, limites de debit, action de l'ecole.
 * MailPulse est simule (Http::fake) : aucun message ne part.
 */
class VerificationContactRobustesseTest extends TestCase
{
    use ActiveLaVerificationContact;
    use RefreshDatabase;

    private const URL_VERIF = 'mailpulse.test/api/v1/verifications';

    private int $horloge = 0;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.reinscription_portal.secret' => 'un-secret-de-test-suffisamment-long-pour-passer',
            'services.mailpulse.enabled' => true,
            'services.mailpulse.api_key' => 'cle-de-test',
            'services.mailpulse.base_url' => 'https://mailpulse.test',
        ]);
        User::factory()->create(['id' => 1])->assignRole(Role::firstOrCreate(['name' => 'superAdmin', 'guard_name' => 'web']));
        Cache::flush();
        $this->reglerVerificationContact(true);
    }

    public function test_un_redepot_par_deposer_qui_change_l_adresse_laisse_la_demande_visible(): void
    {
        Http::fake(['mailpulse.test/api/v1/messages' => Http::response(['dispatch' => ['state' => 'accepted', 'sms_fallback_eligible' => false], 'message' => ['id' => 'm1', 'status' => 'queued']], 202)]);
        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        Setting::updateOrCreate(['key' => PortailReinscriptionService::REGLAGE_ANNEE_CIBLE], ['value' => (string) $annee->id, 'type' => 'string', 'group' => 'scolarite', 'is_active' => true]);
        Cache::flush();
        $champs = ['nom' => 'KONE', 'prenoms' => 'Awa', 'date_naissance' => '2007-01-01', 'telephone' => '+2250701020304', 'email' => 'awa@gmail.com'];
        $service = app(PortailCandidatureService::class);

        $candidature = $service->deposer($champs);
        app(DemarrageVerification::class)->apresDepot($candidature);
        $candidature->fresh()->forceFill(['verification_contact' => StatutVerificationContact::Verifie->value])->saveQuietly();
        ESBTPVerificationContact::query()->update(['verifie_at' => now()]);

        $rouverte = $service->deposer(['email' => 'autre@gmail.com'] + $champs);
        $reponse = app(DemarrageVerification::class)->apresDepot($rouverte);

        $this->assertSame(1, ESBTPCandidature::query()->count());
        $this->assertSame(StatutVerificationContact::AReconfirmer->value, $candidature->fresh()->verification_contact);
        $this->assertNotNull($reponse);
    }

    public function test_un_verdict_whatsapp_perime_pendant_l_appel_ne_vaut_rien(): void
    {
        Http::fake([
            self::URL_VERIF.'/ver_1/check' => function () {
                // Un nouveau code part pendant l'appel : autre verification MailPulse.
                ESBTPVerificationContact::query()->update(['mailpulse_verification_id' => 'ver_2']);

                return Http::response(['status' => 'approved'], 200);
            },
            self::URL_VERIF => Http::response(['id' => 'ver_1', 'status' => 'pending'], 201),
        ]);
        $verification = app(DemarrageVerification::class)->apresDepot($this->candidature());

        $this->appeler('verifier', ['canal' => 'telephone', 'demande_id' => $verification->demandeId, 'code' => '123456'])
            ->assertStatus(422)->assertJson(['motif' => 'expire']);
        $this->assertTrue(ESBTPCandidature::query()->sole()->contactNonVerifie());
    }

    public function test_un_code_expire_ne_compte_pas_dans_les_plafonds(): void
    {
        Http::fake([
            self::URL_VERIF.'/ver_1/check' => Http::response(['status' => 'expired', 'error' => 'expire'], 422),
            self::URL_VERIF => Http::response(['id' => 'ver_1', 'status' => 'pending'], 201),
        ]);
        $verification = app(DemarrageVerification::class)->apresDepot($this->candidature());

        $this->appeler('verifier', ['canal' => 'telephone', 'demande_id' => $verification->demandeId, 'code' => '123456'])->assertJson(['motif' => 'expire']);
        $this->assertSame(0, ESBTPVerificationContact::query()->sole()->tentatives_total);
    }

    public function test_un_429_au_premier_envoi_marque_sans_declarer_l_impossibilite(): void
    {
        Http::fake([self::URL_VERIF => Http::response(['retry_after' => 30], 429)]);
        $candidature = $this->candidature();

        $this->assertNotNull(app(DemarrageVerification::class)->apresDepot($candidature));
        $this->assertSame(StatutVerificationContact::TelephoneNonVerifie->value, $candidature->fresh()->verification_contact);
    }

    public function test_le_renvoi_rend_le_delai_de_mailpulse(): void
    {
        Http::fakeSequence(self::URL_VERIF)
            ->push(['id' => 'ver_1', 'status' => 'pending'], 201)
            ->push(['retry_after' => 42], 429);
        $verification = app(DemarrageVerification::class)->apresDepot($this->candidature());

        $this->appeler('renvoyer', ['canal' => 'telephone', 'demande_id' => $verification->demandeId])
            ->assertStatus(429)->assertJson(['retry_after' => 42]);
    }

    public function test_un_contact_non_confirme_n_est_ni_convoque_ni_place_jusqu_a_confirmation(): void
    {
        $this->withoutMiddleware([\App\Http\Middleware\PaywallMiddleware::class]);
        $candidature = $this->candidature();
        $candidature->forceFill(['email' => 'awa@gmail.com', 'verification_contact' => StatutVerificationContact::Impossible->value])->saveQuietly();
        $reservation = $this->reservation($candidature);

        app(MessagerieRdv::class)->planifier($reservation);
        $this->assertSame(StatutConvocationRdv::SansEmail, $reservation->fresh()->convocation_statut);
        $this->assertSame(0, ESBTPCandidature::query()->contactUtilisable()->count());

        Permission::firstOrCreate(['name' => 'inscriptions.candidatures.process', 'guard_name' => 'web']);
        $agent = User::factory()->create();
        $agent->givePermissionTo('inscriptions.candidatures.process');
        $this->actingAs($agent)->post(route('esbtp.candidatures.confirmer-contact', $candidature), ['empreinte' => $candidature->fresh()->empreinteContact()])
            ->assertRedirect()->assertSessionHas('success');

        $confirmee = $candidature->fresh();
        $this->assertSame(StatutVerificationContact::Verifie->value, $confirmee->verification_contact);
        $this->assertSame($agent->id, (int) $confirmee->contact_confirme_par);
        $this->assertNotNull($confirmee->contact_confirme_at);
        // Le rendez-vous deja pris repart dans la file, avec l'adresse du dossier.
        $this->assertSame(StatutConvocationRdv::EnAttente, $reservation->fresh()->convocation_statut);
        $this->assertTrue(\OwenIt\Auditing\Models\Audit::query()
            ->where('auditable_type', $confirmee->getMorphClass())->where('auditable_id', $confirmee->id)
            ->get()->contains(fn ($a) => ($a->new_values['verification_contact'] ?? null) === 'verifie'), 'Le geste doit laisser une trace d\'audit.');
    }

    public function test_la_corbeille_des_candidatures_montre_la_demande_et_filtre_les_contacts_non_verifies(): void
    {
        $marquee = $this->candidature();
        $marquee->forceFill(['nom' => 'MARQUEE', 'verification_contact' => StatutVerificationContact::TelephoneNonVerifie->value])->saveQuietly();
        $verifiee = $this->candidature('+2250701020307');
        $verifiee->forceFill(['nom' => 'VERIFIEE', 'verification_contact' => StatutVerificationContact::Verifie->value])->saveQuietly();
        Permission::firstOrCreate(['name' => 'inscriptions.candidatures.view', 'guard_name' => 'web']);
        $agent = User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]);
        $agent->givePermissionTo('inscriptions.candidatures.view');

        $this->actingAs($agent)->get(route('esbtp.candidatures.index'))
            ->assertOk()->assertSee('MARQUEE')->assertSee('VERIFIEE')->assertSee('Contact non vérifié');
        $this->get(route('esbtp.candidatures.index', ['contact' => 'non_verifie']))
            ->assertOk()->assertSee('MARQUEE')->assertDontSee('VERIFIEE');
    }

    public function test_reglage_coupe_rien_n_est_retenu_ni_envoye(): void
    {
        Http::fake();
        $this->reglerVerificationContact(false);
        $candidature = $this->candidature();
        $marquee = $this->candidature('+2250701020306');
        $marquee->forceFill(['email' => 'awa@gmail.com', 'verification_contact' => StatutVerificationContact::Impossible->value])->saveQuietly();
        $reservation = $this->reservation($marquee);

        $this->assertNull(app(DemarrageVerification::class)->apresDepot($candidature));
        Http::assertNothingSent();
        $this->assertNull($candidature->fresh()->verification_contact);

        // Une demande marquee pendant que le reglage etait actif n'est plus retenue.
        app(MessagerieRdv::class)->planifier($reservation);
        $this->assertSame(StatutConvocationRdv::EnAttente, $reservation->fresh()->convocation_statut);
        $this->assertSame(2, ESBTPCandidature::query()->contactUtilisable()->count());
    }

    public function test_confirmer_un_dossier_modifie_depuis_l_affichage_est_refuse(): void
    {
        $this->withoutMiddleware([\App\Http\Middleware\PaywallMiddleware::class]);
        $candidature = $this->candidature();
        $candidature->forceFill(['verification_contact' => StatutVerificationContact::Impossible->value])->saveQuietly();
        $empreinteAffichee = $candidature->fresh()->empreinteContact();
        $candidature->fresh()->forceFill(['email' => 'autre@gmail.com'])->saveQuietly();

        Permission::firstOrCreate(['name' => 'inscriptions.candidatures.process', 'guard_name' => 'web']);
        $agent = User::factory()->create();
        $agent->givePermissionTo('inscriptions.candidatures.process');
        $this->actingAs($agent)->post(route('esbtp.candidatures.confirmer-contact', $candidature), ['empreinte' => $empreinteAffichee])
            ->assertRedirect()->assertSessionHas('error');

        $this->assertSame(StatutVerificationContact::Impossible->value, $candidature->fresh()->verification_contact);
    }

    public function test_confirmer_le_contact_exige_le_droit_de_traitement(): void
    {
        $this->withoutMiddleware([\App\Http\Middleware\PaywallMiddleware::class]);
        $candidature = $this->candidature();
        $candidature->forceFill(['verification_contact' => StatutVerificationContact::Impossible->value])->saveQuietly();
        $demande = \App\Models\ESBTPReinscriptionDemande::create([
            'etudiant_id' => \App\Models\ESBTPEtudiant::factory()->create()->id,
            'annee_universitaire_id' => $candidature->annee_universitaire_id,
            'statut' => \App\Models\ESBTPReinscriptionDemande::STATUT_EN_ATTENTE,
            'consentement_at' => now(),
            'verification_contact' => StatutVerificationContact::Impossible->value,
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('esbtp.candidatures.confirmer-contact', $candidature), ['empreinte' => $candidature->fresh()->empreinteContact()])
            ->assertForbidden();
        $this->post(route('esbtp.reinscription-demandes.confirmer-contact', $demande), ['empreinte' => $demande->empreinteContact()])
            ->assertForbidden();
        $this->assertSame(StatutVerificationContact::Impossible->value, $candidature->fresh()->verification_contact);
    }

    public function test_un_renvoi_croise_avec_un_redepot_n_ecrit_rien_sur_le_nouveau_contact(): void
    {
        $numeroInitial = '+2250701020304';
        Http::fake([self::URL_VERIF => Http::sequence()
            ->push(['id' => 'ver_1', 'status' => 'pending'], 201)
            ->whenEmpty(function () {
                // Pendant le renvoi, un redepot remplace le numero de la ligne.
                ESBTPVerificationContact::query()->update(['destination' => '+2250709090909', 'mailpulse_verification_id' => null]);

                return Http::response(['id' => 'ver_ancien', 'status' => 'pending'], 201);
            })]);
        $verification = app(DemarrageVerification::class)->apresDepot($this->candidature($numeroInitial));

        $this->appeler('renvoyer', ['canal' => 'telephone', 'demande_id' => $verification->demandeId])->assertStatus(202);

        $ligne = ESBTPVerificationContact::query()->sole();
        $this->assertSame('+2250709090909', $ligne->destination);
        $this->assertNull($ligne->mailpulse_verification_id, 'Le code de l\'ancien numero ne doit pas valoir pour le nouveau.');
    }

    public function test_whatsapp_sature_se_traite_comme_une_limite(): void
    {
        Http::fakeSequence(self::URL_VERIF)
            ->push(['id' => 'ver_1', 'status' => 'pending'], 201)
            ->push(['error' => 'whatsapp_sature', 'retry_after' => 20], 503);
        $candidature = $this->candidature();
        $verification = app(DemarrageVerification::class)->apresDepot($candidature);

        $this->appeler('renvoyer', ['canal' => 'telephone', 'demande_id' => $verification->demandeId])
            ->assertStatus(429)->assertJson(['retry_after' => 20]);
        $this->assertSame(StatutVerificationContact::TelephoneNonVerifie->value, $candidature->fresh()->verification_contact);
    }

    public function test_un_redepot_d_une_demande_non_verifiable_relance_un_code(): void
    {
        Http::fake([self::URL_VERIF => Http::response(['id' => 'ver_1', 'status' => 'pending'], 201)]);
        $expiree = $this->candidature();
        $expiree->forceFill(['verification_contact' => StatutVerificationContact::Impossible->value])->saveQuietly();

        $reponse = app(DemarrageVerification::class)->apresDepot($expiree->fresh());

        $this->assertNotNull($reponse);
        $this->assertSame(1, ESBTPCandidature::query()->count());
        Http::assertSentCount(1);
    }

    public function test_la_commande_des_familles_s_arrete_sur_une_limite_sans_garder_de_ligne(): void
    {
        Http::fake([self::URL_VERIF => Http::response(['error' => 'trop_de_demandes', 'retry_after' => 60], 429)]);
        $this->candidature('+2250701020304')->fresh()->forceFill(['verification_contact' => null])->saveQuietly();
        $this->candidature('+2250701020305')->fresh()->forceFill(['verification_contact' => null])->saveQuietly();

        $this->artisan('inscriptions:verifier-familles-sans-email', ['--execute' => true])
            ->expectsOutputToContain('Arrêt')
            ->assertSuccessful();

        Http::assertSentCount(1);
        $this->assertSame(0, ESBTPVerificationContact::query()->count());
    }

    private function candidature(string $telephone = '+2250701020304'): ESBTPCandidature
    {
        return ESBTPCandidature::create([
            'nom' => 'KONE', 'prenoms' => 'Awa', 'date_naissance' => '2007-01-01', 'telephone' => $telephone,
            'email' => null, 'annee_universitaire_id' => ESBTPAnneeUniversitaire::factory()->create()->id,
            'consentement_at' => now(), 'statut' => ESBTPCandidature::STATUT_EN_ATTENTE,
        ]);
    }

    private function reservation(ESBTPCandidature $candidature): ESBTPRdvReservation
    {
        $creneau = ESBTPRdvCreneau::create([
            'annee_universitaire_id' => $candidature->annee_universitaire_id, 'date' => now()->addDays(3)->toDateString(),
            'heure_debut' => '09:00:00', 'heure_fin' => '09:40:00', 'capacite' => 10, 'ouvert' => true,
        ]);

        return ESBTPRdvReservation::create([
            'creneau_id' => $creneau->id, 'candidature_id' => $candidature->id, 'statut' => 'confirmee',
            'nom' => 'KONE', 'prenoms' => 'Awa', 'telephone' => $candidature->telephone, 'date_naissance' => '2007-01-01',
            'email' => 'awa@gmail.com',
        ]);
    }

    private function appeler(string $action, array $donnees)
    {
        $chemin = 'api/portail/email/'.$action;
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
