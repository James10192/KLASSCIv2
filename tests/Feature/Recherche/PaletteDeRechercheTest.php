<?php

namespace Tests\Feature\Recherche;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\User;
use App\Support\Recherche\RechercheDesEntites;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Domain\Permissions\AccesTemporaires;
use App\Models\TemporaryPermissionGrant;
use Tests\TestCase;

/**
 * La palette Ctrl K / ⌘ K ne montre que ce que l'utilisateur peut ouvrir.
 *
 * Avant elle, la recherche globale listait les étudiants à TOUT compte connecté,
 * sans `students.view` — un enseignant ou un caissier retrouvait n'importe quel
 * élève par son nom.
 */
class PaletteDeRechercheTest extends TestCase
{
    use DatabaseTransactions;

    private const PERMISSIONS = [
        'admin.access', 'students.view', 'paiements.create', 'paiements.view', 'paiements.view_own',
        'classes.view', 'classes.create', 'module.academique.access', 'system.manage',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        foreach (self::PERMISSIONS as $nom) {
            Permission::findOrCreate($nom, 'web');
        }
        Cache::flush();
    }

    private function recherche(User $user, string $q, array $extra = []): array
    {
        return $this->actingAs($user)
            ->getJson(route('search.global', array_merge(['q' => $q], $extra)))
            ->assertOk()
            ->json('results');
    }

    public function test_sans_students_view_aucun_etudiant_n_est_rendu(): void
    {
        ESBTPEtudiant::factory()->create(['nom' => 'Zorglubien', 'prenoms' => 'Aya']);

        $sansDroit = User::factory()->create();
        $sansDroit->givePermissionTo(['admin.access']);

        $types = array_column($this->recherche($sansDroit, 'Zorglub'), 'type');
        $this->assertNotContains('etudiant', $types);

        // Contre-épreuve : la même recherche, avec le droit, retrouve l'élève.
        $avecDroit = User::factory()->create();
        $avecDroit->givePermissionTo(['admin.access', 'students.view']);

        $titres = array_column(array_filter(
            $this->recherche($avecDroit, 'Zorglub'),
            fn ($r) => $r['type'] === 'etudiant'
        ), 'title');
        $this->assertContains('Zorglubien Aya', $titres);
    }

    public function test_les_pages_suivent_la_porte_de_leur_route(): void
    {
        $caissier = User::factory()->create();
        $caissier->givePermissionTo(['admin.access', 'paiements.create']);

        $pagesEncaisser = array_column($this->recherche($caissier, 'encaisser'), 'title');
        $this->assertContains('Encaisser', $pagesEncaisser);

        $pagesClasse = array_column($this->recherche($caissier, 'nouvelle classe'), 'title');
        $this->assertNotContains('Nouvelle classe', $pagesClasse);

        $pagesReglages = array_column($this->recherche($caissier, 'paramètres'), 'title');
        $this->assertNotContains('Paramètres', $pagesReglages);

        // Qui porte la permission retrouve la page.
        $gestionnaire = User::factory()->create();
        $gestionnaire->givePermissionTo(['admin.access', 'classes.create', 'module.academique.access']);
        $this->assertContains('Nouvelle classe', array_column($this->recherche($gestionnaire, 'nouvelle classe'), 'title'));
    }

    public function test_un_mot_cle_trouve_la_page_meme_sans_son_titre(): void
    {
        $caissier = User::factory()->create();
        $caissier->givePermissionTo(['admin.access', 'paiements.create']);

        $this->assertContains('Encaisser', array_column($this->recherche($caissier, 'reçu'), 'title'));
    }

    public function test_la_limite_est_plafonnee_quoi_que_demande_l_appelant(): void
    {
        ESBTPEtudiant::factory()->count(RechercheDesEntites::LIMITE_PALETTE + 3)->create(['nom' => 'Plafonnier']);

        $user = User::factory()->create();
        $user->givePermissionTo(['admin.access', 'students.view']);

        $etudiants = array_filter(
            $this->recherche($user, 'Plafonnier', ['limit' => 500]),
            fn ($r) => $r['type'] === 'etudiant'
        );

        $this->assertCount(RechercheDesEntites::LIMITE_PALETTE, $etudiants);
    }

    public function test_view_own_ne_retrouve_que_ses_propres_encaissements(): void
    {
        $caissier = User::factory()->create();
        $caissier->givePermissionTo(['admin.access', 'paiements.view_own']);
        $collegue = User::factory()->create();

        $inscription = ESBTPInscription::factory()->create();
        ESBTPPaiement::factory()->pour($inscription)->create(['numero_recu' => 'RQX-MIEN-1', 'created_by' => $caissier->id]);
        ESBTPPaiement::factory()->pour($inscription)->create(['numero_recu' => 'RQX-AUTRE-1', 'created_by' => $collegue->id]);

        $recus = array_column(array_filter(
            $this->recherche($caissier, 'RQX-'),
            fn ($r) => $r['type'] === 'paiement'
        ), 'title');

        $this->assertContains('RQX-MIEN-1', $recus);
        $this->assertNotContains('RQX-AUTRE-1', $recus);
    }

    public function test_une_saisie_courte_rend_des_suggestions_ouvrables(): void
    {
        $caissier = User::factory()->create();
        $caissier->givePermissionTo(['admin.access', 'paiements.create']);

        $reponse = $this->actingAs($caissier)->getJson(route('search.global'))->assertOk();

        $this->assertSame([], $reponse->json('results'));
        $suggestions = array_column($reponse->json('suggestions'), 'title');
        $this->assertContains('Encaisser', $suggestions);
        $this->assertNotContains('Nouvelle classe', $suggestions);
    }

    public function test_un_compte_etudiant_ne_retrouve_que_ses_propres_paiements(): void
    {
        Role::findOrCreate('etudiant', 'web');
        $eleve = User::factory()->create();
        $eleve->assignRole('etudiant');
        $eleve->givePermissionTo(['admin.access', 'paiements.view']);

        $sonDossier = ESBTPEtudiant::factory()->create(['user_id' => $eleve->id]);
        $autreDossier = ESBTPEtudiant::factory()->create();
        $sienne = ESBTPInscription::factory()->create(['etudiant_id' => $sonDossier->id]);
        $autre = ESBTPInscription::factory()->create(['etudiant_id' => $autreDossier->id]);
        ESBTPPaiement::factory()->pour($sienne)->create(['numero_recu' => 'RQE-SIEN-1']);
        ESBTPPaiement::factory()->pour($autre)->create(['numero_recu' => 'RQE-AUTRE-1']);

        $recus = array_column(array_filter(
            $this->recherche($eleve, 'RQE-'),
            fn ($r) => $r['type'] === 'paiement'
        ), 'title');

        $this->assertContains('RQE-SIEN-1', $recus);
        $this->assertNotContains('RQE-AUTRE-1', $recus);
    }

    public function test_un_acces_temporaire_ouvre_la_page_sans_attendre_un_cache(): void
    {
        $agent = User::factory()->create();
        $agent->givePermissionTo(['admin.access']);

        $this->assertNotContains('Encaisser', array_column($this->recherche($agent, 'encaisser'), 'title'));

        TemporaryPermissionGrant::create([
            'user_id' => $agent->id,
            'permission' => 'paiements.create',
            'starts_at' => now()->subMinute(),
            'expires_at' => now()->addDay(),
            'motif' => 'Remplacement du caissier',
            'granted_by' => $agent->id,
        ]);
        $this->app->forgetInstance(AccesTemporaires::class);

        $this->assertContains('Encaisser', array_column($this->recherche($agent, 'encaisser'), 'title'));
    }

    public function test_le_gabarit_rend_la_palette_et_son_declencheur(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['admin.access']);

        $this->actingAs($user)
            ->get(route('search.results', ['q' => 'xy']))
            ->assertOk()
            ->assertSee('class="spl-root"', false)
            ->assertSee('window.klassciSpotlight', false)
            ->assertSee('data-spl-ouvrir', false)
            ->assertSee('role="combobox"', false);
    }
}
