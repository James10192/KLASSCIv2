<?php

namespace Tests\Feature\Emails;

use App\Enums\StatutConvocationRdv;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Models\User;
use App\Services\TenantScolariteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use OwenIt\Auditing\Models\Audit;
use Tests\TestCase;

/**
 * Ce que le CLI peut faire sans SSH : le reglage de verification, le
 * nettoyage des adresses fabriquees, la synchronisation des convocations et
 * la liste des familles a recontacter, sans jamais livrer une donnee en clair.
 */
class CliJoignabiliteTest extends TestCase
{
    use RefreshDatabase;

    private int $jour = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        Sanctum::actingAs(User::factory()->create(), ['cli:read', 'cli:admin']);
        Cache::flush();
    }

    public function test_le_reglage_de_verification_se_lit_et_s_ecrit_en_booleen(): void
    {
        $cle = TenantScolariteSettings::VERIFICATION_CONTACT;

        $this->getJson('/api/cli/settings?search=verification_contact')->assertOk()
            ->assertJsonFragment(['key' => $cle, 'value' => '0']);
        $this->postJson('/api/cli/settings', ['key' => $cle, 'value' => 'peut-etre', 'apply' => true])->assertStatus(422);
        $this->postJson('/api/cli/settings', ['key' => $cle, 'value' => 'true', 'apply' => true])->assertOk()
            ->assertJsonPath('data.apres', '1');

        $this->assertTrue(app(TenantScolariteSettings::class)->verificationContactActive());
    }

    public function test_le_reglage_se_cree_sur_une_ecole_qui_n_a_pas_la_ligne(): void
    {
        $cle = TenantScolariteSettings::VERIFICATION_CONTACT;
        \App\Models\Setting::query()->where('key', $cle)->delete();
        Cache::flush();

        $this->postJson('/api/cli/settings', ['key' => $cle, 'value' => '1', 'apply' => false])->assertStatus(404);
        $this->postJson('/api/cli/settings', ['key' => $cle, 'value' => '1', 'apply' => true])->assertOk();

        $this->assertSame('1', (string) \App\Models\Setting::query()->where('key', $cle)->value('value'));
        $this->assertTrue(app(TenantScolariteSettings::class)->verificationContactActive());
    }

    public function test_le_nettoyage_simule_par_defaut_et_exige_un_vrai_booleen(): void
    {
        Storage::fake('local');
        $eleve = \App\Models\ESBTPEtudiant::factory()->create(['email' => 'm22-0521@esbtp.edu.ci']);
        DB::table('esbtp_etudiants')->where('id', '!=', $eleve->id)->update(['email' => null]);

        $simulation = $this->postJson('/api/cli/emails/nettoyer-factices?sans_mx=1', [])->assertOk()
            ->assertJsonPath('data.execute', false)->assertJsonPath('data.modifiees', 0)->assertJsonPath('data.sauvegarde', null);
        $this->assertContains('factice', array_column($simulation->json('data.lignes'), 'type'));
        $this->assertContains('esbtp.edu.ci', array_column($simulation->json('data.lignes'), 'domaine'));
        $this->assertStringNotContainsString('m22-0521@', $simulation->getContent());
        $this->assertSame('m22-0521@esbtp.edu.ci', $eleve->fresh()->email);

        $this->postJson('/api/cli/emails/nettoyer-factices', ['execute' => 'true'])->assertStatus(422);
        $this->assertSame('m22-0521@esbtp.edu.ci', $eleve->fresh()->email);

        $this->postJson('/api/cli/emails/nettoyer-factices?sans_mx=1', ['execute' => true])->assertOk()
            ->assertJsonPath('data.modifiees', 1)->assertJsonStructure(['data' => ['sauvegarde']]);
        $this->assertNull($eleve->fresh()->email);
        $this->assertTrue(Audit::query()->where('event', 'cli.emails.nettoyer_factices')->exists());
    }

    public function test_la_synchronisation_rend_le_contrat_du_cli(): void
    {
        config(['services.mailpulse.enabled' => true, 'services.mailpulse.api_key' => 'cle', 'services.mailpulse.base_url' => 'https://mailpulse.test']);
        Http::fake(['mailpulse.test/api/v1/messages/*' => Http::response(['message' => ['status' => 'failed', 'error_code' => 'email_bounced']])]);
        $this->reservation($this->candidature('awa@gmail.com'), StatutConvocationRdv::Envoyee, 'confirmee', 'msg_1');

        $this->postJson('/api/cli/rendez-vous/synchroniser-convocations')->assertOk()
            ->assertJsonPath('data', ['lus' => 1, 'delivrees' => 0, 'echecs' => 1, 'rebonds' => 1, 'supprimees' => 0, 'en_attente' => 0, 'arretee_sur' => null, 'erreurs' => 0]);
    }

    public function test_les_familles_sont_dedupliquees_classees_et_masquees(): void
    {
        $fabriquee = $this->candidature('m22-0521@esbtp.edu.ci', '+2250701020304');
        $this->reservation($fabriquee, StatutConvocationRdv::Echec, 'annulee', null, 'email_bounced');
        $this->reservation($fabriquee, StatutConvocationRdv::Echec, 'confirmee', null, 'email_bounced');
        $this->reservation($this->candidature('awa@gmail.com', '+2250701020305'), StatutConvocationRdv::Envoyee, 'confirmee', 'm2', 'delivered', now());
        $this->reservation($this->candidature(null, '+2250701020306'), StatutConvocationRdv::SansEmail, 'confirmee');

        $reponse = $this->getJson('/api/cli/rendez-vous/familles?details=1')->assertOk();

        $reponse->assertJsonPath('data.synthese.familles', 3)
            ->assertJsonPath('data.synthese.A_au_moins_une_remise', 1)
            ->assertJsonPath('data.synthese.B_uniquement_des_echecs', 1)
            ->assertJsonPath('data.synthese.C_remise_inconnue', 0)
            ->assertJsonPath('data.synthese.D_sans_email', 1)
            ->assertJsonPath('data.synthese.E_B_ou_D_avec_telephone', 2)
            ->assertJsonPath('data.synthese.F_adresse_fabriquee', ['total' => 1, 'email_alternatif' => 0, 'telephone_seul' => 1, 'rien' => 0]);
        $famille = collect($reponse->json('data.familles'))->firstWhere('email_etat', 'factice');
        $this->assertSame(2, $famille['reservations']);
        $this->assertSame(['+225 07 ** ** ** 04'], $famille['telephones_masques']);

        $corps = $reponse->getContent();
        foreach (['KONE', 'Awa', '0701020304', 'm22-0521', 'awa@gmail.com'] as $clair) {
            $this->assertStringNotContainsString($clair, $corps);
        }
        $this->getJson('/api/cli/rendez-vous/familles')->assertOk()->assertJsonMissingPath('data.familles');
    }

    public function test_les_familles_sont_celles_de_l_annee_cible_des_inscriptions(): void
    {
        // Septembre : l'annee courante s'acheve, les rendez-vous sont poses sur la suivante.
        $courante = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $cible = ESBTPAnneeUniversitaire::factory()->create(['is_current' => false]);
        \App\Models\Setting::setOrCreate(\App\Services\Reinscription\PortailReinscriptionService::REGLAGE_ANNEE_CIBLE, (string) $cible->id);
        Cache::flush();
        $this->reservationSurAnnee($cible, 'a@gmail.com', '+2250701020307');
        $this->reservationSurAnnee($cible, 'b@gmail.com', '+2250701020308');
        $this->reservationSurAnnee($courante, 'c@gmail.com', '+2250701020309');

        $this->getJson('/api/cli/rendez-vous/familles')->assertOk()
            ->assertJsonPath('data.synthese.familles', 2)
            ->assertJsonPath('data.synthese.B_uniquement_des_echecs', 2);
        // Le diagnostic des convocations lit le meme perimetre : il ne peut pas diverger.
        $this->getJson('/api/cli/rendez-vous/diagnostic')->assertOk()->assertJsonPath('data.convocations.echec', 2);
        $this->getJson('/api/cli/emails/diagnostic')->assertOk()->assertJsonPath('convocations.echecs', 2);
    }

    public function test_sans_annee_cible_reglee_les_familles_sont_celles_de_l_annee_courante(): void
    {
        $courante = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $autre = ESBTPAnneeUniversitaire::factory()->create(['is_current' => false]);
        $this->reservationSurAnnee($courante, 'a@gmail.com', '+2250701020307');
        $this->reservationSurAnnee($autre, 'b@gmail.com', '+2250701020308');

        $this->getJson('/api/cli/rendez-vous/familles')->assertOk()->assertJsonPath('data.synthese.familles', 1);
    }

    public function test_sans_aucune_annee_les_familles_sont_celles_des_douze_derniers_mois(): void
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => false]);
        $this->reservationSurAnnee($annee, 'a@gmail.com', '+2250701020307');
        $this->reservationSurAnnee($annee, 'b@gmail.com', '+2250701020308', now()->subMonths(13)->toDateString());

        $this->getJson('/api/cli/rendez-vous/familles')->assertOk()->assertJsonPath('data.synthese.familles', 1);
    }

    private function reservationSurAnnee(ESBTPAnneeUniversitaire $annee, string $email, string $telephone, ?string $date = null): void
    {
        $c = $this->candidature($email, $telephone);
        $c->forceFill(['annee_universitaire_id' => $annee->id])->saveQuietly();
        $r = $this->reservation($c->fresh(), StatutConvocationRdv::Echec, 'confirmee', null, 'email_bounced');
        if ($date !== null) {
            $r->creneau->forceFill(['date' => $date])->save();
        }
    }

    private function candidature(?string $email, string $telephone = '+2250701020304'): ESBTPCandidature
    {
        return ESBTPCandidature::create([
            'nom' => 'KONE', 'prenoms' => 'Awa', 'date_naissance' => '2007-01-01', 'telephone' => $telephone,
            'email' => $email, 'annee_universitaire_id' => ESBTPAnneeUniversitaire::factory()->create()->id,
            'consentement_at' => now(), 'statut' => ESBTPCandidature::STATUT_EN_ATTENTE,
        ]);
    }

    private function reservation(ESBTPCandidature $c, StatutConvocationRdv $etat, string $statut, ?string $message = null, ?string $code = null, $delivree = null): ESBTPRdvReservation
    {
        $creneau = ESBTPRdvCreneau::create([
            'annee_universitaire_id' => $c->annee_universitaire_id, 'date' => now()->addDays(3 + $this->jour++)->toDateString(),
            'heure_debut' => '09:00:00', 'heure_fin' => '09:40:00', 'capacite' => 10, 'ouvert' => true,
        ]);

        return ESBTPRdvReservation::create([
            'creneau_id' => $creneau->id, 'candidature_id' => $c->id, 'statut' => $statut,
            'nom' => 'KONE', 'prenoms' => 'Awa', 'telephone' => $c->telephone, 'date_naissance' => '2007-01-01',
            'email' => $c->email, 'convocation_statut' => $etat, 'convocation_action' => 'confirme',
            'convocation_envoyee_at' => now()->subHour(), 'convocation_message_id' => $message,
            'convocation_code_distant' => $code, 'convocation_delivree_at' => $delivree,
        ]);
    }
}
