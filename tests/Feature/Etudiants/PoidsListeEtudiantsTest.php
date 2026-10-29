<?php

namespace Tests\Feature\Etudiants;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\User;
use App\Services\Mobile\MobileProfileResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * La liste des etudiants pesait pres d'un mega-octet et 1,5 s de serveur
 * (presentation, octobre 2026) : feuille de style et script de la page recopies
 * dans chaque reponse, etudiants eligibles a la reinscription groupee calcules et
 * serialises pour un modal rarement ouvert, grille de cartes rendue sous la liste
 * du telephone qui la cache, et six requetes par ligne pour le badge BTS.
 */
class PoidsListeEtudiantsTest extends TestCase
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
        MobileProfileResolver::oublier();
        $this->beforeApplicationDestroyed(fn () => MobileProfileResolver::oublier());
        $user = User::factory()->create();
        $user->givePermissionTo(['admin.access', 'students.view']);
        $this->actingAs($user);
    }

    /**
     * 40 etudiants inscrits cette annee, 60 eligibles a la reinscription.
     *
     * @return \Illuminate\Support\Collection<int, ESBTPEtudiant> les eligibles
     */
    private function semer()
    {
        ESBTPAnneeUniversitaire::query()->update(['is_current' => false]);
        $precedente = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2024-2025', 'start_date' => '2024-09-01', 'end_date' => '2025-07-31',
        ]);
        $courante = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2025-2026', 'start_date' => '2025-09-01', 'end_date' => '2026-07-31', 'is_current' => true,
        ]);
        $classes = ESBTPClasse::factory()->count(3)->create();
        $inscrire = function (ESBTPEtudiant $e, int $i, ESBTPAnneeUniversitaire $annee) use ($classes): void {
            $classe = $classes[$i % 3];
            ESBTPInscription::factory()->create([
                'etudiant_id' => $e->id, 'annee_universitaire_id' => $annee->id, 'classe_id' => $classe->id,
                'filiere_id' => $classe->filiere_id, 'niveau_id' => $classe->niveau_etude_id,
            ]);
        };

        ESBTPEtudiant::factory()->count(40)->create()->each(fn ($e, $i) => $inscrire($e, $i, $courante));

        return ESBTPEtudiant::factory()->count(60)->create()->each(fn ($e, $i) => $inscrire($e, $i, $precedente));
    }

    private function compterRequetes(callable $appel): int
    {
        $n = 0;
        DB::listen(function () use (&$n) {
            $n++;
        });
        $appel();

        return $n;
    }

    public function test_la_page_charge_sa_feuille_et_ses_scripts_au_lieu_de_les_recopier(): void
    {
        $html = $this->get(route('esbtp.etudiants.index'))->assertOk()->getContent();

        foreach (['css/etudiants-index.css', 'js/etudiants-index.js', 'css/reinscription-bulk-modal.css', 'js/reinscription-bulk-modal.js'] as $fichier) {
            $this->assertMatchesRegularExpression('#' . preg_quote($fichier, '#') . '\?v=\d+#', $html, $fichier . ' doit etre charge et versionne');
            $this->assertFileExists(public_path($fichier));
        }

        // Plus de copie en ligne : la feuille, le script de la page, la fabrique du modal.
        $this->assertStringNotContainsString('.students-grid {', $html);
        $this->assertStringNotContainsString('function exportModal()', $html);
        $this->assertStringNotContainsString('window.__brmSharedFactory = function', $html);
        $this->assertStringNotContainsString('.brm-modal {', $html);

        // Ce qui depend du serveur arrive par la configuration de la page.
        $this->assertStringContainsString('window.etuIndexConfig = ', $html);
        $this->assertStringContainsString('function exportModal()', file_get_contents(public_path('js/etudiants-index.js')));
    }

    public function test_les_etudiants_eligibles_se_chargent_a_l_ouverture_du_modal(): void
    {
        $eligibles = $this->semer();

        $html = $this->get(route('esbtp.etudiants.index'))->assertOk()->getContent();

        // Le modal est toujours la, vide, avec l'adresse de ses etudiants.
        $this->assertStringContainsString('id="bulkReinscriptionModal"', $html);
        $this->assertMatchesRegularExpression('/__brmSharedFactory\(\s*"bulkReinscriptionModal",\s*\[\],/', $html);
        $this->assertStringContainsString(json_encode(route('esbtp.etudiants.reinscription-eligibles')), $html);

        $lignes = $this->getJson(route('esbtp.etudiants.reinscription-eligibles'))
            ->assertOk()
            ->assertJsonStructure(['students' => [['id', 'matricule', 'nom_complet', 'classe', 'telephone', 'email', 'fiche_complete']]])
            ->json('students');

        $this->assertEqualsCanonicalizing($eligibles->pluck('id')->all(), array_column($lignes, 'id'));
    }

    public function test_les_eligibles_demandent_le_droit_de_voir_la_liste(): void
    {
        $sansDroit = User::factory()->create();
        $sansDroit->givePermissionTo('admin.access');

        $this->actingAs($sansDroit)
            ->getJson(route('esbtp.etudiants.reinscription-eligibles'))
            ->assertForbidden();
    }

    public function test_shell_mobile_actif_la_grille_de_cartes_n_est_pas_rendue(): void
    {
        $this->semer();

        $html = $this->get(route('esbtp.etudiants.index'))->assertOk()->getContent();

        // La liste du telephone est la ; la grille qu'elle remplace ne l'est pas.
        $this->assertStringContainsString('eim-screen', $html);
        $this->assertStringNotContainsString('id="etudiants-grid-mobile"', $html);
        $this->assertStringContainsString('eu-vue-unique', $html);
        $this->assertStringContainsString('id="etudiants-table"', $html);

        $tranche = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('esbtp.etudiants.index', ['page' => 2]))
            ->assertOk()
            ->json('html');
        $this->assertStringNotContainsString('class="etm-card', $tranche);
        $this->assertStringContainsString('id="etudiants-tbody"', $tranche);
    }

    public function test_le_nombre_de_requetes_ne_depend_plus_du_nombre_de_lignes(): void
    {
        $this->semer();

        // Mesure avant ce chantier sur ce jeu : 297 requetes (badge BTS et
        // inscriptions en attente lus ligne par ligne, eligibles calcules).
        $html = '';
        $requetes = $this->compterRequetes(function () use (&$html) {
            $html = $this->get(route('esbtp.etudiants.index'))->assertOk()->getContent();
        });

        // Mesure locale du poids : ETUDIANTS_HTML_DUMP=/chemin/page.html
        if ($chemin = env('ETUDIANTS_HTML_DUMP')) {
            file_put_contents($chemin, $html);
            file_put_contents($chemin . '.requetes', (string) $requetes);
        }

        $this->assertLessThan(90, $requetes, "{$requetes} requetes pour 30 lignes");
    }

    public function test_le_badge_bts_lit_toujours_une_inscription_de_tronc_commun_d_une_autre_annee(): void
    {
        $this->semer();
        $tc = ESBTPFiliere::factory()->create(['is_tronc_commun' => true, 'parent_id' => null]);
        $ancienne = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2023-2024', 'start_date' => '2023-09-01', 'end_date' => '2024-07-31',
        ]);
        $etudiant = ESBTPEtudiant::factory()->create();
        ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id, 'annee_universitaire_id' => $ancienne->id, 'filiere_id' => $tc->id,
        ]);

        $html = $this->get(route('esbtp.etudiants.index'))->assertOk()->getContent();

        // Seul cet etudiant a un parcours de tronc commun : un badge, sur sa ligne.
        $this->assertSame(1, substr_count($html, 'class="bts-mini-badge '));
        $ligne = substr($html, strpos($html, 'data-li-cle="' . $etudiant->id . '"'));
        $this->assertStringContainsString('bts-mini-badge', substr($ligne, 0, strpos($ligne, '</tr>')));
    }
}
