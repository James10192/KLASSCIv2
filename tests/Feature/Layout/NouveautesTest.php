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

    public function test_une_entree_conditionnee_suit_l_ecran_qu_elle_annonce(): void
    {
        $contenu = ['titre' => 'T', 'entrees' => [
            ['titre' => 'Reclamations', 'texte' => 'x', 'si' => 'reclamations'],
            ['titre' => 'Inconnue', 'texte' => 'x', 'si' => 'nimporte'],
            ['titre' => 'Pour tous', 'texte' => 'x'],
        ]];

        \App\Models\Setting::updateOrCreate(['key' => 'notes.reclamations.enabled'], ['value' => '0', 'type' => 'boolean', 'group' => 'notes', 'is_required' => false]);
        \Illuminate\Support\Facades\Cache::flush();
        $this->assertSame(['Pour tous'], array_column(Nouveautes::pour(null, $contenu)['entrees'], 'titre'));

        \App\Models\Setting::where('key', 'notes.reclamations.enabled')->update(['value' => '1']);
        \Illuminate\Support\Facades\Cache::flush();
        $this->assertSame(['Reclamations', 'Pour tous'], array_column(Nouveautes::pour(null, $contenu)['entrees'], 'titre'));
    }

    /** Le bandeau de requalification est fermé à l'enseignant seul : son annonce aussi. */
    public function test_la_requalification_n_est_annoncee_qu_a_qui_peut_l_ouvrir(): void
    {
        foreach (['lmd.notes.manage', 'evaluations.edit', 'identity.teach', 'identity.coordinate'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $contenu = ['titre' => 'T', 'entrees' => [
            ['titre' => 'Requalification', 'texte' => 'x', 'si' => 'requalification_examen', 'permissions' => ['lmd.notes.manage']],
        ]];

        $enseignant = User::factory()->create();
        $enseignant->givePermissionTo(['lmd.notes.manage', 'evaluations.edit', 'identity.teach']);
        $coordinateur = User::factory()->create();
        $coordinateur->givePermissionTo(['lmd.notes.manage', 'evaluations.edit', 'identity.teach', 'identity.coordinate']);
        $sansEdition = User::factory()->create();
        $sansEdition->givePermissionTo('lmd.notes.manage');

        $this->assertSame([], Nouveautes::pour($enseignant, $contenu)['entrees']);
        $this->assertSame([], Nouveautes::pour($sansEdition, $contenu)['entrees']);
        $this->assertSame(['Requalification'], array_column(Nouveautes::pour($coordinateur, $contenu)['entrees'], 'titre'));
    }

    public function test_le_contenu_livre_est_valide_et_ses_captures_existent(): void
    {
        $contenu = require resource_path('data/nouveautes.php');

        $this->assertNotEmpty($contenu['entrees']);
        foreach ($contenu['entrees'] as $entree) {
            $this->assertNotEmpty($entree['titre']);
            $this->assertNotEmpty($entree['texte']);
            if (isset($entree['captures'])) {
                // L'apres est toujours la ; l'avant manque seulement pour un ecran nouveau.
                $this->assertFileExists(public_path($entree['captures']['apres']));
                if (isset($entree['captures']['avant'])) {
                    $this->assertFileExists(public_path($entree['captures']['avant']));
                }
            }
        }
    }

    public function test_la_fenetre_rend_ses_entrees_et_garde_les_identifiants_lus_par_le_layout(): void
    {
        Permission::findOrCreate('bulletins.view', 'web');
        $user = User::factory()->create();
        $user->givePermissionTo('bulletins.view');
        $this->actingAs($user);

        $html = view('layouts.partials.nouveautes', ['cleVersion' => 'whatsNew.v2026_10_02'])->render();

        $this->assertStringContainsString('id="whatsNewModal"', $html);
        $this->assertStringContainsString('whatsNew.v2026_10_02.user.'.$user->id, $html);
        $this->assertStringContainsString('Les résultats refaits', $html);
        $this->assertStringContainsString('resultats-bureau-avant.webp', $html);
        $this->assertStringNotContainsString('Les bulletins se génèrent même si vous quittez la page', $html);
        // Un ecran nouveau montre sa capture seule, sans curseur avant / apres.
        $this->assertStringContainsString('notifications.webp', $html);
        $this->assertSame(1, substr_count($html, 'aria-label="Comparer avant et après"'));
        foreach (['whatsNewCloseBtn', 'whatsNewRemindLaterBtn', 'whatsNewDismissBtn'] as $id) {
            $this->assertStringContainsString('id="'.$id.'"', $html);
        }
    }

    public function test_un_compte_sans_permission_ne_voit_que_les_entrees_pour_tous(): void
    {
        $this->actingAs(User::factory()->create());

        $html = view('layouts.partials.nouveautes', ['cleVersion' => 'whatsNew.v2026_10_02'])->render();

        $this->assertStringContainsString('Des pages plus rapides', $html);
        $this->assertStringNotContainsString('Les résultats refaits', $html);
        $this->assertStringNotContainsString('Nanan fait davantage pour vous', $html);
    }
}
