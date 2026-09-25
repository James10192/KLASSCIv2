<?php

namespace Tests\Feature\RendezVous;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * « Retrouver un rendez-vous » : une famille appelle sans connaitre le jour,
 * elle donne son nom, son telephone ou sa reference.
 */
class RechercheRdvTest extends TestCase
{
    use RefreshDatabase;

    private int $annee;

    private int $numero = 0;

    private const MAINTENANT = '2026-10-05 09:10:00';

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(self::MAINTENANT);
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        Cache::flush();
        foreach (['admin.access', 'inscriptions.rdv.view', 'inscriptions.rdv.accueil', 'inscriptions.candidatures.view'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->annee = ESBTPAnneeUniversitaire::factory()->create()->id;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function agent(array $permissions = ['admin.access', 'inscriptions.rdv.accueil', 'inscriptions.candidatures.view']): User
    {
        $u = User::factory()->create();
        $u->givePermissionTo($permissions);

        return $u;
    }

    private function rdv(string $nom, string $prenoms, int $dansJours, ?string $telephone = null, ?string $reference = null): ESBTPRdvReservation
    {
        // Un creneau par debut et par jour : plusieurs familles s'y partagent la place.
        $creneau = ESBTPRdvCreneau::firstOrCreate([
            'annee_universitaire_id' => $this->annee,
            'date' => Carbon::today()->addDays($dansJours)->toDateString(),
            'heure_debut' => '10:00:00',
        ], ['heure_fin' => '10:30:00', 'capacite' => 40, 'ouvert' => true]);
        $n = ++$this->numero;
        // Un telephone par candidature et par annee : c'est une contrainte d'unicite.
        $telephone ??= '+22505'.sprintf('%08d', $n);
        $candidature = DB::table('esbtp_candidatures')->insertGetId([
            'nom' => $nom, 'prenoms' => $prenoms, 'date_naissance' => '2007-03-12',
            'telephone' => $telephone, 'email' => 'famille'.$n.'@exemple.ci',
            'annee_universitaire_id' => $this->annee, 'consentement_at' => now(), 'statut' => 'en_attente',
            'tuteur_nom' => 'Tuteur', 'tuteur_telephone' => '+2250505050505', 'tuteur_lien' => 'Père',
            'reference_publique' => $reference ?? 'REF'.str_pad((string) $n, 5, '0', STR_PAD_LEFT),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return ESBTPRdvReservation::create([
            'creneau_id' => $creneau->id, 'candidature_id' => $candidature, 'statut' => 'confirmee',
            'nom' => $nom, 'prenoms' => $prenoms, 'telephone' => $telephone,
            'date_naissance' => '2007-03-12', 'email' => 'famille'.$n.'@exemple.ci',
        ]);
    }

    private function cles(string $html): array
    {
        preg_match_all('/data-li-cle="(\d+)"/', $html, $m);

        return array_map('intval', $m[1]);
    }

    public function test_un_nom_retrouve_le_rendez_vous_a_venir_comme_le_passe(): void
    {
        $avenir = $this->rdv('KOUASSI', 'Ama', 3);
        // La meme famille, une annee plus tot : autre candidature, autre annee.
        $this->annee = ESBTPAnneeUniversitaire::factory()->create()->id;
        $passe = $this->rdv('KOUASSI', 'Ama', -4, '+22505'.sprintf('%08d', 1));
        $autre = $this->rdv('TRAORE', 'Issa', 3);

        $html = $this->actingAs($this->agent())->get(route('esbtp.rendez-vous.recherche', ['q' => 'kouassi ama']))
            ->assertOk()->getContent();

        $this->assertEqualsCanonicalizing([$avenir->id, $passe->id], $this->cles($html));
        $this->assertStringNotContainsString('TRAORE', $html);
        $this->assertStringContainsString(route('esbtp.rendez-vous.accueil.index', ['jour' => Carbon::today()->addDays(3)->toDateString()]), $html);
    }

    public function test_sans_texte_on_voit_ce_qui_vient_dans_l_ordre_du_calendrier(): void
    {
        $loin = $this->rdv('A', 'Loin', 9);
        $proche = $this->rdv('B', 'Proche', 1);
        $this->rdv('C', 'Passe', -2);

        $html = $this->actingAs($this->agent())->get(route('esbtp.rendez-vous.recherche'))->assertOk()->getContent();

        $this->assertSame([$proche->id, $loin->id], $this->cles($html));
    }

    public function test_un_telephone_tape_avec_espaces_et_une_reference_avec_tiret(): void
    {
        $ama = $this->rdv('KOUASSI', 'Ama', 2, '+2250707123456', 'ABCD1234');
        $this->rdv('TRAORE', 'Issa', 2, '+2250101999999', 'WXYZ9876');
        $agent = $this->agent();

        $this->assertSame([$ama->id], $this->cles($this->actingAs($agent)->get(route('esbtp.rendez-vous.recherche', ['q' => '07 07 12 34']))->getContent()));
        $this->assertSame([$ama->id], $this->cles($this->actingAs($agent)->get(route('esbtp.rendez-vous.recherche', ['q' => 'abcd-1234']))->getContent()));
    }

    public function test_la_suite_arrive_par_tranches_sans_repetition(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->rdv('FAMILLE', 'N'.$i, 1 + ($i % 3));
        }
        $agent = $this->agent();

        $page = $this->actingAs($agent)->get(route('esbtp.rendez-vous.recherche'))
            ->assertOk()->assertSee('data-page-suivante="2"', false)->getContent();
        $suite = $this->actingAs($agent)->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('esbtp.rendez-vous.recherche', ['page' => 2, 'mode' => 'rows']))
            ->assertOk()->assertJsonPath('pagination.total', 30)->json('rows_html');

        $tous = array_merge($this->cles($page), $this->cles($suite));
        $this->assertCount(30, $tous);
        $this->assertCount(30, array_unique($tous));
    }

    public function test_l_acces_suit_les_permissions_du_planning_ou_de_l_accueil(): void
    {
        $this->actingAs($this->agent(['admin.access', 'inscriptions.rdv.view']))
            ->get(route('esbtp.rendez-vous.recherche'))->assertOk();
        $this->actingAs($this->agent(['admin.access']))
            ->get(route('esbtp.rendez-vous.recherche'))->assertForbidden();
    }

    public function test_klassci_cli_retrouve_le_meme_rendez_vous(): void
    {
        $ama = $this->rdv('KOUASSI', 'Ama', 2, '+2250707123456', 'ABCD1234');
        $this->rdv('TRAORE', 'Issa', 2);
        Sanctum::actingAs(User::factory()->create(), ['cli:read']);

        $this->getJson(route('api.cli.rendez-vous.recherche', ['q' => 'kouassi']))
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.rendez_vous.0.id', $ama->id)
            ->assertJsonPath('data.rendez_vous.0.reference', 'ABCD-1234')
            ->assertJsonPath('data.rendez_vous.0.date', Carbon::today()->addDays(2)->toDateString())
            ->assertJsonPath('data.rendez_vous.0.etat_accueil', 'attendu');
    }
}
