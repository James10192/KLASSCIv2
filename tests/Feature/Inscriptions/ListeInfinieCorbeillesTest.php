<?php

namespace Tests\Feature\Inscriptions;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPReinscriptionDemande;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Les deux corbeilles de dossiers en ligne (candidatures, demandes de
 * reinscription) se chargent au defilement : la page rend la premiere tranche,
 * la suite arrive en lignes seules, chacune une fois.
 */
class ListeInfinieCorbeillesTest extends TestCase
{
    use RefreshDatabase;

    private int $annee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        Cache::flush();

        $perms = ['admin.access', 'inscriptions.candidatures.view', 'reinscriptions.demandes.view'];
        foreach ($perms as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $agent = User::factory()->create();
        $agent->givePermissionTo($perms);
        $this->actingAs($agent);

        $this->annee = ESBTPAnneeUniversitaire::factory()->create()->id;
    }

    private function ajax(): self
    {
        return $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest']);
    }

    /** @return list<string> */
    private function cles(string $html): array
    {
        preg_match_all('/data-li-cle="(\d+)"/', $html, $m);

        return $m[1];
    }

    public function test_les_candidatures_se_chargent_par_tranches_sans_repetition(): void
    {
        // Meme seconde de reception : seul le departage par identifiant ordonne.
        $maintenant = now();
        for ($i = 1; $i <= 30; $i++) {
            DB::table('esbtp_candidatures')->insert([
                'nom' => 'KOUASSI', 'prenoms' => 'Ama '.$i, 'date_naissance' => '2007-03-12',
                'telephone' => '+22507'.sprintf('%08d', $i), 'email' => 'famille'.$i.'@exemple.ci',
                'annee_universitaire_id' => $this->annee, 'consentement_at' => $maintenant, 'statut' => 'en_attente',
                'tuteur_nom' => 'Kouassi Paul', 'tuteur_telephone' => '+2250505050505', 'tuteur_lien' => 'Père',
                'created_at' => $maintenant, 'updated_at' => $maintenant,
            ]);
        }

        $page = $this->get(route('esbtp.candidatures.index'))->assertOk()
            ->assertSee('data-liste-infinie', false)
            ->assertSee('data-page-suivante="2"', false)
            ->getContent();

        $suite = $this->ajax()->getJson(route('esbtp.candidatures.index', ['page' => 2, 'mode' => 'rows']))
            ->assertOk()
            ->assertJsonPath('pagination.has_more', false)
            ->assertJsonPath('pagination.total', 30)
            ->json('rows_html');

        $toutes = array_merge($this->cles($page), $this->cles($suite));
        $this->assertCount(30, $toutes);
        $this->assertCount(30, array_unique($toutes));
    }

    public function test_les_demandes_de_reinscription_se_chargent_par_tranches(): void
    {
        $classe = ESBTPClasse::factory()->create();
        for ($i = 1; $i <= 27; $i++) {
            ESBTPReinscriptionDemande::create([
                'etudiant_id' => ESBTPEtudiant::factory()->create()->id,
                'annee_universitaire_id' => $this->annee,
                'classe_souhaitee_id' => $classe->id,
                'statut' => ESBTPReinscriptionDemande::STATUT_EN_ATTENTE,
                'consentement_at' => now(),
            ]);
        }

        $page = $this->get(route('esbtp.reinscription-demandes.index'))->assertOk()
            ->assertSee('data-page-suivante="2"', false)
            ->getContent();

        $suite = $this->ajax()->getJson(route('esbtp.reinscription-demandes.index', ['page' => 2, 'mode' => 'rows']))
            ->assertOk()
            ->assertJsonPath('pagination.total', 27)
            ->assertJsonPath('pagination.affiches', 27)
            ->json('rows_html');

        $this->assertCount(2, $this->cles($suite));
        $this->assertCount(27, array_unique(array_merge($this->cles($page), $this->cles($suite))));
    }

    public function test_une_adresse_copiee_avec_le_mode_lignes_rend_la_page(): void
    {
        $this->get(route('esbtp.reinscription-demandes.index', ['mode' => 'rows']))
            ->assertOk()
            ->assertHeader('content-type', 'text/html; charset=UTF-8')
            ->assertDontSee('rows_html', false);
    }
}
