<?php

namespace Tests\Feature\Admissions;

use App\Enums\StatutReservationRdv;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Tests\Feature\Admissions\Concerns\FabriqueDeDossiers;
use Tests\TestCase;

/**
 * « Aujourd'hui » : les familles du jour par creneau, quatre compteurs, une
 * coche « Reçue » en un clic qui mene au dossier, et le nouveau menu
 * « Admissions » qui garde toutes les anciennes adresses joignables.
 */
class AujourdhuiAccueilTest extends TestCase
{
    use FabriqueDeDossiers;
    use RefreshDatabase;

    private const PERMISSIONS = [
        'admin.access', 'module.etudiants.access', 'inscriptions.view', 'inscriptions.create',
        'inscriptions.candidatures.view', 'inscriptions.candidatures.process',
        'reinscriptions.demandes.view', 'reinscriptions.demandes.process',
        'inscriptions.rdv.view', 'inscriptions.rdv.manage', 'inscriptions.rdv.accueil',
    ];

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();
        // Lundi 10:15 : le creneau de 08:00 est termine, celui de 09:45 en cours, celui de 14:00 a venir.
        Carbon::setTestNow('2026-10-05 10:15:00');
        Cache::flush();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        foreach (self::PERMISSIONS as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->agent = User::factory()->create();
        $this->agent->givePermissionTo(self::PERMISSIONS);
        $this->annee = ESBTPAnneeUniversitaire::factory()->create(['name' => '2026-2027', 'is_current' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @return array<string, \App\Models\ESBTPRdvReservation> */
    private function journee(): array
    {
        $matin = $this->creneau(0, '08:00', '08:30');
        $enCours = $this->creneau(0, '09:45', '10:45');
        $apresMidi = $this->creneau(0, '14:00', '14:30');

        return [
            'recue' => $this->reserver($this->candidature(['nom' => 'RECUE']), $matin, StatutReservationRdv::Honoree, now()->setTime(8, 10)),
            'inscrite' => $this->reserver($this->candidature(['nom' => 'INSCRITE', 'statut' => 'convertie', 'traite_at' => now()]), $matin, StatutReservationRdv::Honoree, now()->setTime(8, 20)),
            'absente' => $this->reserver($this->candidature(['nom' => 'ABSENTE']), $matin),
            'retard' => $this->reserver($this->candidature(['nom' => 'RETARD']), $enCours),
            'attendue' => $this->reserver($this->demande('ATTENDUE'), $apresMidi),
        ];
    }

    public function test_la_page_montre_les_familles_du_jour_et_ses_quatre_compteurs(): void
    {
        $this->journee();
        $this->reserver($this->candidature(['nom' => 'DEMAIN']), $this->creneau(1));

        $reponse = $this->actingAs($this->agent)->getJson(route('esbtp.admissions.aujourdhui', ['fragment' => 1]))->assertOk();

        $this->assertSame(
            ['attendues' => 5, 'recues' => 2, 'en_retard' => 1, 'finalisees' => 1, 'a_recevoir' => 2, 'non_venues' => 1],
            $reponse->json('compteurs')
        );
        $creneaux = $reponse->json('creneaux');
        $this->assertStringContainsString('08:00 – 08:30', $creneaux);
        $this->assertStringContainsString('En retard · 30 min', $creneaux);
        $this->assertStringContainsString('Non venue', $creneaux);
        $this->assertStringContainsString('Inscrite', $creneaux);
        $this->assertStringNotContainsString('DEMAIN', $creneaux);
        // Au guichet : la famille reçue dont l'inscription reste a faire, pas celle deja inscrite.
        $this->assertStringContainsString('RECUE', $reponse->json('guichet'));
        $this->assertStringNotContainsString('INSCRITE', $reponse->json('guichet'));
    }

    public function test_cocher_recue_fait_avancer_la_famille_vers_la_finalisation_de_son_dossier(): void
    {
        $r = $this->journee()['attendue'];

        $this->actingAs($this->agent)->postJson(route('esbtp.rendez-vous.accueil.recu', $r), ['creneau_id' => $r->creneau_id])
            ->assertOk()->assertJson(['message' => 'Famille reçue.']);

        $this->assertSame(StatutReservationRdv::Honoree, $r->fresh()->statut);
        $reponse = $this->actingAs($this->agent)->getJson(route('esbtp.admissions.aujourdhui', ['fragment' => 1]))->assertOk();
        $this->assertSame(3, $reponse->json('compteurs.recues'));
        $guichet = $reponse->json('guichet');
        $this->assertStringContainsString('ATTENDUE', $guichet);
        $this->assertStringContainsString('Finaliser la réinscription', $guichet);
        $this->assertStringContainsString('ouvrir=reinscription-'.$r->reinscription_demande_id, $guichet);
        $this->assertStringContainsString('agir=1', $guichet);

        // Dans la liste des dossiers, la meme famille est desormais « Reçu aujourd'hui ».
        $this->actingAs($this->agent)->getJson(route('esbtp.demandes.index', ['etape' => 'recu_aujourdhui', 'fragment' => 1]))
            ->assertOk()->assertSee('ATTENDUE');
    }

    public function test_une_coche_se_corrige_et_une_ligne_deplacee_est_refusee(): void
    {
        $r = $this->journee()['attendue'];
        $this->actingAs($this->agent)->postJson(route('esbtp.rendez-vous.accueil.recu', $r), ['creneau_id' => $r->creneau_id])->assertOk();

        $this->actingAs($this->agent)->postJson(route('esbtp.rendez-vous.accueil.annuler', $r), ['creneau_id' => $r->creneau_id])->assertOk();
        $this->assertSame(StatutReservationRdv::Confirmee, $r->fresh()->statut);

        $this->actingAs($this->agent)->postJson(route('esbtp.rendez-vous.accueil.recu', $r), ['creneau_id' => $r->creneau_id + 999])
            ->assertStatus(422)->assertJsonPath('code', 'deplacee');
    }

    public function test_sans_la_permission_accueil_la_page_est_fermee(): void
    {
        $lecteur = User::factory()->create();
        $lecteur->givePermissionTo(['admin.access', 'inscriptions.candidatures.view']);

        $this->actingAs($lecteur)->get(route('esbtp.admissions.aujourdhui'))->assertForbidden();
    }

    public function test_un_jour_sans_rendez_vous_affiche_un_etat_vide(): void
    {
        $this->actingAs($this->agent)->get(route('esbtp.admissions.aujourdhui'))
            ->assertOk()->assertSee('Aucune famille attendue aujourd\'hui', false)->assertSee('Personne n\'attend son inscription', false);
    }

    public function test_le_menu_admissions_mene_partout_et_les_anciennes_adresses_restent_joignables(): void
    {
        $this->journee();

        $page = $this->actingAs($this->agent)->get(route('esbtp.admissions.aujourdhui'))->assertOk();
        $page->assertSee(route('esbtp.demandes.index', ['type' => 'nouvelle']), false)
            ->assertSee(route('esbtp.demandes.index', ['type' => 'reinscription']), false)
            ->assertSee('Familles à prévenir')
            ->assertSee('Planning des rendez-vous')
            ->assertSee(route('esbtp.rendez-vous.accueil.index'), false);

        $this->actingAs($this->agent)->get(route('esbtp.candidatures.index'))->assertRedirect()->assertStatus(301);
        $this->actingAs($this->agent)->get(route('esbtp.reinscription-demandes.index'))->assertStatus(301);
        $this->actingAs($this->agent)->get(route('esbtp.rendez-vous.accueil.index'))->assertOk();
    }

    public function test_un_agent_des_seules_candidatures_ne_voit_ni_reinscriptions_ni_aujourd_hui(): void
    {
        $lecteur = User::factory()->create();
        $lecteur->givePermissionTo(['admin.access', 'module.etudiants.access', 'inscriptions.candidatures.view']);

        $this->actingAs($lecteur)->get(route('esbtp.demandes.index'))
            ->assertOk()
            ->assertSee(route('esbtp.demandes.index', ['type' => 'nouvelle']), false)
            ->assertDontSee(route('esbtp.demandes.index', ['type' => 'reinscription']), false)
            ->assertDontSee(route('esbtp.admissions.aujourdhui'), false);
    }

    public function test_le_planning_mene_au_dossier_de_chaque_famille(): void
    {
        $r = $this->journee()['retard'];

        $this->actingAs($this->agent)->get(route('esbtp.rendez-vous.index'))
            ->assertOk()->assertSee('ouvrir=nouvelle-'.$r->candidature_id, false);
    }
}
