<?php

namespace Tests\Feature\Layout;

use App\Models\User;
use App\Support\Nouveautes;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * La fenêtre « Nouveautés » ne montre à chacun que ce qui le concerne, et
 * n'apparaît pas du tout pour un compte à qui aucune entrée ne s'adresse.
 */
class NouveautesTest extends TestCase
{
    use DatabaseTransactions;

    private const CONTENU = [
        'titre' => 'Septembre 2026',
        'entrees' => [
            ['titre' => 'Pour tous', 'texte' => 'a'],
            ['titre' => 'Pour la caisse', 'texte' => 'b', 'permissions' => ['paiements.create', 'paiements.create.non_cash']],
            ['titre' => 'Pour la scolarité', 'texte' => 'c', 'permissions' => ['students.view']],
        ],
    ];

    public function test_une_entree_n_apparait_qu_a_qui_a_une_de_ses_permissions(): void
    {
        foreach (['paiements.create', 'paiements.create.non_cash', 'students.view'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $comptable = User::factory()->create();
        $comptable->givePermissionTo('paiements.create.non_cash');

        $titres = array_column(Nouveautes::pour($comptable, self::CONTENU)['entrees'], 'titre');

        $this->assertSame(['Pour tous', 'Pour la caisse'], $titres);
    }

    public function test_sans_compte_seules_les_entrees_sans_permission_restent(): void
    {
        $titres = array_column(Nouveautes::pour(null, self::CONTENU)['entrees'], 'titre');

        $this->assertSame(['Pour tous'], $titres);
    }

    public function test_le_contenu_livre_est_valide_et_ses_captures_existent(): void
    {
        $contenu = require resource_path('data/nouveautes.php');

        $this->assertNotEmpty($contenu['entrees']);
        foreach ($contenu['entrees'] as $entree) {
            $this->assertNotEmpty($entree['titre']);
            $this->assertNotEmpty($entree['texte']);
            foreach (['avant', 'apres'] as $cote) {
                if (isset($entree['captures'])) {
                    $this->assertFileExists(public_path($entree['captures'][$cote]));
                }
            }
        }
    }

    public function test_la_fenetre_rend_ses_entrees_et_garde_les_identifiants_lus_par_le_layout(): void
    {
        Permission::findOrCreate('students.view', 'web');
        $user = User::factory()->create();
        $user->givePermissionTo('students.view');
        $this->actingAs($user);

        $html = view('layouts.partials.nouveautes', ['cleVersion' => 'whatsNew.v2026_09_25'])->render();

        $this->assertStringContainsString('id="whatsNewModal"', $html);
        $this->assertStringContainsString('whatsNew.v2026_09_25.user.'.$user->id, $html);
        $this->assertStringContainsString('La liste des étudiants sur téléphone', $html);
        $this->assertStringContainsString('etudiants-telephone-avant.webp', $html);
        $this->assertStringNotContainsString('Un écran d’encaissement refait', $html);
        foreach (['whatsNewCloseBtn', 'whatsNewRemindLaterBtn', 'whatsNewDismissBtn'] as $id) {
            $this->assertStringContainsString('id="'.$id.'"', $html);
        }
    }

    public function test_un_compte_sans_entree_ne_recoit_pas_de_fenetre(): void
    {
        $this->actingAs(User::factory()->create());

        $html = view('layouts.partials.nouveautes', ['cleVersion' => 'whatsNew.v2026_09_25'])->render();

        $this->assertStringNotContainsString('whatsNewModal', $html);
    }
}
