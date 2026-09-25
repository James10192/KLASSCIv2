<?php

namespace Tests\Feature\Etudiants;

use App\Models\ESBTPEtudiant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Sur telephone, la grille de cartes s'arretait a sa premiere tranche : la
 * seule sentinelle du defilement vivait dans le tableau, cache sous 992px.
 * La grille a desormais la sienne, et la reponse d'une tranche porte ses cartes.
 */
class GrilleMobileDefilementTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('superAdmin', 'web');
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        Permission::findOrCreate('admin.access', 'web');
        Permission::findOrCreate('students.view', 'web');
        Cache::flush();
        $user = User::factory()->create();
        $user->givePermissionTo(['admin.access', 'students.view']);
        $this->actingAs($user);
    }

    public function test_la_grille_mobile_a_sa_sentinelle_et_la_tranche_suivante_porte_des_cartes(): void
    {
        ESBTPEtudiant::factory()->count(45)->create();

        $html = $this->get(route('esbtp.etudiants.index'))
            ->assertOk()
            ->assertSee('id="etudiants-grid-mobile"', false)
            ->assertSee('id="etudiants-sentinel-mobile"', false)
            ->getContent();

        if ($chemin = env('ETUDIANTS_HTML_DUMP')) {
            file_put_contents($chemin, $html);
        }

        $tranche = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('esbtp.etudiants.index', ['page' => 2]))
            ->assertOk()
            ->json('html');

        $this->assertStringContainsString('id="etudiants-grid-mobile"', $tranche);
        $this->assertGreaterThan(0, substr_count($tranche, 'class="etm-card'));

        // Le defilement n'ajoute a la grille que les elements portant la classe de
        // la carte : si l'une change sans l'autre, la grille s'arrete en silence.
        $this->assertStringContainsString("carte.classList.contains('etm-card')", $html);
    }

    public function test_le_tri_finit_par_l_identifiant(): void
    {
        ESBTPEtudiant::factory()->count(3)->create();
        $requetes = [];
        DB::listen(function ($q) use (&$requetes) {
            $requetes[] = $q->sql;
        });

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('esbtp.etudiants.index', ['page' => 2, 'sort' => 'statut']))
            ->assertOk();

        $liste = collect($requetes)->first(fn ($sql) => str_contains($sql, 'from `esbtp_etudiants`') && str_contains($sql, 'limit'));
        $this->assertMatchesRegularExpression('/order by .*`esbtp_etudiants`\.`id` (asc|desc) limit/', (string) $liste);
    }

    public function test_la_liste_du_telephone_rend_des_lignes_et_leur_etat_d_inscription(): void
    {
        \App\Models\ESBTPAnneeUniversitaire::query()->update(['is_current' => false]);
        $annee = \App\Models\ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $inscrit = ESBTPEtudiant::factory()->create(['statut' => 'actif']);
        $enCours = ESBTPEtudiant::factory()->create(['statut' => 'actif']);
        $sans = ESBTPEtudiant::factory()->create(['statut' => 'actif']);
        \App\Models\ESBTPInscription::factory()->create(['etudiant_id' => $inscrit->id, 'annee_universitaire_id' => $annee->id, 'workflow_step' => 'etudiant_cree']);
        \App\Models\ESBTPInscription::factory()->create(['etudiant_id' => $enCours->id, 'annee_universitaire_id' => $annee->id, 'workflow_step' => 'prospect']);

        $tranche = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('esbtp.etudiants.index', ['mode' => 'mobile']))
            ->assertOk()
            ->assertJsonStructure(['items' => [['id', 'nom', 'matricule', 'initiales', 'photo', 'classe', 'lmd', 'annee', 'etat', 'etat_libelle', 'actif', 'url']], 'has_more', 'next_page', 'total'])
            ->json();

        $etats = collect($tranche['items'])->pluck('etat', 'id');
        $this->assertSame('inscrit', $etats[$inscrit->id]);
        $this->assertSame('en_cours', $etats[$enCours->id]);
        $this->assertSame('aucune', $etats[$sans->id]);

        // Le segment « Sans inscription » applique le meme filtre que le bureau.
        $ids = collect($this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('esbtp.etudiants.index', ['mode' => 'mobile', 'inscrit_annee_courante' => 'absente']))
            ->json('items'))->pluck('id');
        $this->assertTrue($ids->contains($sans->id));
        $this->assertFalse($ids->contains($inscrit->id));

        // Sans inscription cette annee : la derniere classe connue, avec son annee.
        $ancienne = \App\Models\ESBTPAnneeUniversitaire::factory()->create(['is_current' => false, 'name' => '2023-2024']);
        $classe = \App\Models\ESBTPClasse::factory()->create(['name' => 'CLASSE-ANCIENNE']);
        \App\Models\ESBTPInscription::factory()->create(['etudiant_id' => $sans->id, 'annee_universitaire_id' => $ancienne->id, 'classe_id' => $classe->id]);
        $ligne = collect($this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('esbtp.etudiants.index', ['mode' => 'mobile', 'inscrit_annee_courante' => 'absente']))
            ->json('items'))->firstWhere('id', $sans->id);
        $this->assertSame('CLASSE-ANCIENNE', $ligne['classe']);
        $this->assertSame('2023-2024', $ligne['annee']);
    }

    public function test_l_ecran_du_telephone_se_rend_avec_ses_lignes_et_ses_actions(): void
    {
        $html = view('esbtp.etudiants.partials._index-mobile', ['listeMobile' => [
            'items' => [['id' => 1, 'nom' => 'KOUASSI Ama', 'matricule' => 'M1', 'initiales' => 'KA', 'photo' => null,
                'classe' => 'B2 COM', 'lmd' => false, 'annee' => null, 'etat' => 'inscrit', 'etat_libelle' => 'Inscrit',
                'actif' => true, 'url' => '/esbtp/etudiants/1']],
            'has_more' => true, 'next_page' => 2, 'total' => 262,
        ]])->render();

        $this->assertStringContainsString('eimListe(', $html);
        $this->assertStringContainsString('262 étudiants', $html);
        $this->assertStringContainsString('KOUASSI Ama', $html);
        // Exporter est ouvert a tous ceux qui voient la liste ; la corbeille demande trash.view.
        $this->assertStringContainsString("ouvrirModale('exportModal')", $html);
        $this->assertStringNotContainsString(route('esbtp.trash.index'), $html);
    }
}
