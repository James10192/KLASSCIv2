<?php

namespace Tests\Feature\Paiements;

use App\Models\ESBTPClasse;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Liste des paiements en mobile : la page rend l'ecran m-* a cote du DOM de
 * bureau, et le meme controleur repond en donnees (mode=mobile) pour la
 * premiere page comme pour les suivantes — avec les filtres du bureau.
 */
class PaiementsListeMobileTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private ESBTPInscription $inscription;
    private ESBTPFraisCategory $scolarite;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        // Toutes les permissions : le profil mobile se deduit de
        // module.caisse.access, donc « caissier ».
        Gate::before(fn () => true);

        $classe = ESBTPClasse::factory()->create();
        $this->inscription = ESBTPInscription::factory()->create([
            'classe_id' => $classe->id,
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'annee_universitaire_id' => $classe->annee_universitaire_id,
            'created_by' => $this->user->id,
        ]);

        $this->scolarite = ESBTPFraisCategory::create([
            'name' => 'Scolarité liste mobile',
            'code' => 'SCOL_LM_'.uniqid(),
            'is_mandatory' => true,
            'is_active' => true,
            'category_type' => 'academic',
            'sort_order' => 1,
            'default_amount' => 100000,
            'payment_deadline_days' => 30,
        ]);
    }

    private function paiement(string $status = 'validé', float $montant = 50000, string $mode = 'Espèces'): ESBTPPaiement
    {
        return ESBTPPaiement::create([
            'inscription_id' => $this->inscription->id,
            'etudiant_id' => $this->inscription->etudiant_id,
            'annee_universitaire_id' => $this->inscription->annee_universitaire_id,
            'frais_category_id' => $this->scolarite->id,
            'montant' => $montant,
            'mode_paiement' => $mode,
            'date_paiement' => now()->toDateString(),
            'status' => $status,
            'nature' => 'encaissement',
            'created_by' => $this->user->id,
            'numero_recu' => 'REC-LM-'.uniqid(),
        ]);
    }

    public function test_la_page_rend_la_liste_mobile_a_cote_du_dom_de_bureau(): void
    {
        $paiement = $this->paiement();

        $reponse = $this->get(route('esbtp.paiements.index'));

        $reponse->assertOk()
            ->assertSee('pim-screen', false)
            ->assertSee('m-only-mobile', false)
            ->assertSee('m-only-desktop', false)
            ->assertSee('data-m-sheet="pim-filtres"', false)
            ->assertSee('data-m-sheet="pim-export"', false)
            ->assertSee('window.pimListe', false)
            // La premiere page part avec la vue : pas de second aller-retour.
            ->assertSee($paiement->numero_recu)
            // Le tableau de bureau est toujours la, simplement cache sous 992px.
            ->assertSee('pi-table-card', false);
    }

    public function test_la_liste_repond_en_donnees_pour_le_shell_mobile(): void
    {
        $valide = $this->paiement('validé', 50000);
        $this->paiement('en_attente', 75000, 'Wave');

        $reponse = $this->getJson(route('esbtp.paiements.refresh', ['mode' => 'mobile']));

        $reponse->assertOk()
            ->assertJsonStructure([
                'items' => [['id', 'url', 'numero_recu', 'nom', 'initiales', 'classe', 'frais', 'mode', 'quand', 'montant', 'avoir', 'statut']],
                'has_more', 'next_page', 'summary' => ['total', 'page', 'per_page'],
                'stats' => ['total', 'valides', 'en_attente', 'rejetes', 'montant_total', 'montant_valide'],
                'url',
            ])
            ->assertJsonPath('summary.total', 2)
            ->assertJsonPath('stats.en_attente', 1)
            ->assertJsonPath('has_more', false)
            ->assertJsonPath('url', route('esbtp.paiements.index'));

        $ligne = collect($reponse->json('items'))->firstWhere('id', $valide->id);
        $this->assertNotNull($ligne);
        $this->assertSame(route('esbtp.paiements.show', $valide->id), $ligne['url']);
        $this->assertSame('validé', $ligne['statut']);
        $this->assertSame(50000.0, $ligne['montant']);
        $this->assertSame('Scolarité liste mobile', $ligne['frais']);
        $this->assertFalse($ligne['avoir']);
        // Paye aujourd'hui : la ligne porte l'heure, pas la date.
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}$/', $ligne['quand']);
    }

    public function test_index_repond_aussi_en_donnees_quand_le_mobile_le_demande(): void
    {
        $this->paiement();

        $this->getJson(route('esbtp.paiements.index', ['mode' => 'mobile']))
            ->assertOk()
            ->assertJsonPath('summary.total', 1)
            ->assertJsonCount(1, 'items');
    }

    public function test_les_filtres_du_bureau_s_appliquent_a_la_liste_mobile(): void
    {
        $this->paiement('validé');
        $rejete = $this->paiement('rejeté');

        $reponse = $this->getJson(route('esbtp.paiements.refresh', ['mode' => 'mobile', 'status' => 'rejeté']));

        $reponse->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', $rejete->id)
            // Les parametres techniques ne remontent pas dans l'URL navigable.
            ->assertJsonPath('url', route('esbtp.paiements.index').'?status='.rawurlencode('rejeté'));
    }

    public function test_la_page_suivante_est_annoncee_quand_il_en_reste(): void
    {
        // PaymentFilterService pagine par 15 : 16 versements = 2 pages.
        for ($i = 0; $i < 16; $i++) {
            $this->paiement();
        }

        $premiere = $this->getJson(route('esbtp.paiements.refresh', ['mode' => 'mobile']));
        $premiere->assertOk()
            ->assertJsonCount(15, 'items')
            ->assertJsonPath('has_more', true)
            ->assertJsonPath('next_page', 2)
            ->assertJsonPath('summary.total', 16);

        $seconde = $this->getJson(route('esbtp.paiements.refresh', ['mode' => 'mobile', 'page' => 2]));
        $seconde->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('has_more', false);
    }

    public function test_un_avoir_est_signale_comme_tel(): void
    {
        $paiement = $this->paiement();
        $paiement->forceFill(['nature' => 'avoir', 'avoir_kind' => 'credit'])->save();

        $this->getJson(route('esbtp.paiements.refresh', ['mode' => 'mobile']))
            ->assertOk()
            ->assertJsonPath('items.0.avoir', true);
    }
}
