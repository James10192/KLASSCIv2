<?php

namespace Tests\Feature\Resultat;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * La liste /esbtp/resultats après sa refonte (namespace rsl-*) : la page garde
 * ses filtres et ses accès, et le défilement infini reçoit des pages qui se
 * rendent avec leurs actions par ligne.
 */
class ResultatsListePremiumTest extends TestCase
{
    use RefreshDatabase;
    use MonteUneClasseBts;

    public function test_la_page_garde_ses_filtres_ses_kpi_et_son_defilement(): void
    {
        $this->monterLaClasse();

        $reponse = $this->actingAs($this->unSuperAdmin())
            ->get(route('esbtp.resultats.index', ['classe_id' => $this->classe->id, 'annee_universitaire_id' => $this->annee->id]));

        $reponse->assertOk();
        $reponse->assertSee('Résultats des étudiants');
        $reponse->assertSee(route('esbtp.resultats.classes'), false);
        $reponse->assertSee(route('esbtp.bulletins.configuration'), false);
        foreach (['kpi-total-etudiants', 'kpi-moyenne-generale', 'kpi-taux-reussite', 'kpi-bulletins'] as $id) {
            $reponse->assertSee('id="' . $id . '"', false);
        }
        // Les trois filtres passent par le sélecteur premium : le select natif est caché.
        foreach (['classe_id', 'annee_universitaire_id', 'semestre'] as $id) {
            $this->assertSame(1, preg_match('/<select\b[^>]*\bid="' . $id . '"[^>]*>/', $reponse->getContent(), $balise), $id);
            $this->assertStringContainsString('au-select-native', $balise[0], $id . ' doit rester le select caché du composant');
        }
        $reponse->assertSee('id="include_all_statuses"', false);
        $reponse->assertSee('Inclure inscriptions inactives');
        $reponse->assertSee('id="rsl-sentinel"', false);
        $reponse->assertSee('IntersectionObserver', false);
        $reponse->assertSee('Mode Moyenne');
        $reponse->assertSee('class="search-bar"', false);
    }

    public function test_les_pages_du_defilement_se_rendent_avec_leurs_actions(): void
    {
        $this->monterLaClasse();
        $premier = $this->etudiantInscrit();
        $second = $this->etudiantInscrit();
        $admin = $this->unSuperAdmin();

        $filtres = [
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'include_all_statuses' => 0,
            'per_page' => 1,
        ];

        $page1 = $this->actingAs($admin)->getJson(route('esbtp.resultats.load-etudiants', $filtres + ['page' => 1]));
        $page1->assertOk();
        $page1->assertJson(['total' => 2, 'current_page' => 1, 'has_more' => true, 'loaded_count' => 1]);
        $html1 = $page1->json('html');
        $this->assertStringContainsString('id="select-all"', $html1);
        $this->assertStringContainsString('<tbody>', $html1);
        $this->assertSame(1, substr_count($html1, 'class="rsl-row"'));
        $this->assertStringContainsString('Détails', $html1);
        $this->assertStringContainsString('data-bs-toggle="dropdown"', $html1);
        $this->assertStringContainsString('Aperçu du bulletin', $html1);
        $this->assertStringContainsString('Télécharger le PDF', $html1);
        $this->assertStringContainsString('modalChoixPeriodeBulletin', $html1);
        $this->assertArrayHasKey('kpis', $page1->json());

        $page2 = $this->actingAs($admin)->getJson(route('esbtp.resultats.load-etudiants', $filtres + ['page' => 2]));
        $page2->assertOk();
        $page2->assertJson(['total' => 2, 'current_page' => 2, 'has_more' => false, 'loaded_count' => 1]);
        $html2 = $page2->json('html');
        $this->assertStringNotContainsString('<thead>', $html2);
        $this->assertSame(1, substr_count($html2, 'class="rsl-row"'));
        $this->assertStringContainsString('student-checkbox', $html2);
        $this->assertStringContainsString('Télécharger le PDF', $html2);

        // Les deux pages ensemble couvrent les deux élèves, sans doublon.
        $ids = [];
        foreach ([$html1, $html2] as $html) {
            preg_match_all('/student-checkbox" type="checkbox" value="(\d+)"/', $html, $m);
            $ids = array_merge($ids, $m[1]);
        }
        sort($ids);
        $attendus = [(string) $premier->id, (string) $second->id];
        sort($attendus);
        $this->assertSame($attendus, $ids);
    }

    private function unSuperAdmin(): User
    {
        Role::findOrCreate('superAdmin', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::withoutEvents(fn () => User::factory()->create());
        $user->assignRole('superAdmin');

        return $user;
    }
}
