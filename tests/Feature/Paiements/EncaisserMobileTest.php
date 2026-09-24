<?php

namespace Tests\Feature\Paiements;

use App\Models\ESBTPClasse;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Encaisser en pas-a-pas plein ecran (shell mobile, profil caissier).
 *
 * L'ecran mobile n'a AUCUN endpoint d'ecriture a lui : il envoie les memes
 * champs au meme store() que le formulaire de bureau, en fetch JSON. Ce que
 * l'on verifie ici, c'est donc la frontiere : la page rend bien le pas-a-pas
 * a cote du DOM de bureau, store() repond en JSON quand on le lui demande
 * (avec de quoi aller au recu), et le formulaire classique garde sa
 * redirection.
 */
class EncaisserMobileTest extends TestCase
{
    use DatabaseTransactions;

    private User $caissier;

    private ESBTPInscription $inscription;

    private ESBTPFraisCategory $scolarite;

    private ESBTPFraisCategory $tenue;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        Permission::findOrCreate('admin.access', 'web');
        Permission::findOrCreate('paiements.create', 'web');
        Permission::findOrCreate('paiements.create.mobile_money', 'web');
        Permission::findOrCreate('paiements.create.non_cash', 'web');
        Permission::findOrCreate('paiements.validate', 'web');
        // Le profil mobile « caissier » se deduit de cette permission.
        Permission::findOrCreate('module.caisse.access', 'web');
        Cache::flush();

        $this->caissier = User::factory()->create();
        $this->caissier->givePermissionTo(['admin.access', 'paiements.create', 'module.caisse.access']);
        $this->actingAs($this->caissier);

        $classe = ESBTPClasse::factory()->create();
        $this->inscription = ESBTPInscription::factory()->create([
            'classe_id' => $classe->id,
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'annee_universitaire_id' => $classe->annee_universitaire_id,
            'created_by' => $this->caissier->id,
        ]);

        $this->scolarite = ESBTPFraisCategory::factory()->ordre(1)->create(['name' => 'Scolarite']);
        $this->tenue = ESBTPFraisCategory::factory()->ordre(2)->create(['name' => 'Tenue']);
        $this->doit($this->scolarite, 150000);
        $this->doit($this->tenue, 100000);
    }

    public function test_la_page_rend_le_pas_a_pas_mobile_a_cote_du_formulaire_de_bureau(): void
    {
        $reponse = $this->get(route('esbtp.paiements.create'));

        $reponse->assertOk()
            // Les deux DOM cohabitent, chacun derriere sa bascule.
            ->assertSee('m-only-desktop', false)
            ->assertSee('m-only-mobile m-screen mab-screen', false)
            ->assertSee('window.mabEncaisser', false)
            ->assertSee('data-m-sheet="mab-abandon"', false)
            ->assertSee('Solder tout')
            // Le mode especes est propose a qui porte paiements.create.
            ->assertSee('data-canon="especes"', false)
            // Le formulaire de bureau est toujours la, intact.
            ->assertSee('id="payment-form"', false);
    }

    public function test_la_page_pre_remplie_depuis_une_fiche_porte_l_inscription_dans_la_configuration(): void
    {
        $reponse = $this->get(route('esbtp.paiements.create', [
            'etudiant_id' => $this->inscription->etudiant_id,
            'inscription_id' => $this->inscription->id,
        ]));

        // La configuration arrive dans un attribut HTML echappe : les guillemets
        // JSON y sont des &quot;.
        $reponse->assertOk()
            ->assertSee('&quot;inscription&quot;:{&quot;id&quot;:'.$this->inscription->id, false);
    }

    public function test_sans_le_droit_especes_seuls_les_modes_mobiles_sont_proposes(): void
    {
        $mobile = User::factory()->create();
        $mobile->givePermissionTo(['admin.access', 'paiements.create.mobile_money', 'module.caisse.access']);

        $reponse = $this->actingAs($mobile)->get(route('esbtp.paiements.create'));

        $reponse->assertOk()
            ->assertSee('data-canon="wave"', false)
            ->assertDontSee('data-canon="especes"', false)
            ->assertDontSee('data-canon="cheque"', false);
    }

    /**
     * Le comptable « hors espèces » (septembre 2026, ISLG et USAT) : il n'a ni
     * la caisse ni `paiements.create`, et ne trouvait plus l'écran. Il le voit
     * dans son menu, y trouve tous les modes sauf les espèces, et les espèces
     * lui restent refusées à l'enregistrement.
     */
    public function test_le_droit_hors_especes_ouvre_l_ecran_sans_les_especes(): void
    {
        $comptable = User::factory()->create();
        Permission::findOrCreate('module.comptabilite.access', 'web');
        Permission::findOrCreate('comptabilite.access', 'web');
        $comptable->givePermissionTo(['admin.access', 'module.comptabilite.access', 'comptabilite.access', 'paiements.create.non_cash']);

        $reponse = $this->actingAs($comptable)->get(route('esbtp.paiements.create'))
            ->assertOk()
            // L'entrée du menu hors de la section Caisse.
            ->assertSee('<div class="menu-text">Encaisser</div>', false);
        $modes = $reponse->viewData('allowedPaymentModes');
        $this->assertContains('wave', $modes);
        $this->assertContains('cheque', $modes);
        $this->assertContains('virement', $modes);
        $this->assertNotContains('especes', $modes);

        $this->actingAs($comptable)->postJson(route('esbtp.paiements.store'), $this->versement(10000))
            ->assertForbidden();
        $this->assertSame(0, ESBTPPaiement::where('inscription_id', $this->inscription->id)->count());
    }

    public function test_l_enregistrement_repond_en_json_avec_de_quoi_aller_au_recu(): void
    {
        $reponse = $this->postJson(route('esbtp.paiements.store'), $this->versement(255000));

        $reponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('paiement.montant', 255000);

        $paiement = ESBTPPaiement::where('inscription_id', $this->inscription->id)->firstOrFail();

        $reponse->assertJsonPath('paiement.id', $paiement->id)
            ->assertJsonPath('paiement.numero_recu', $paiement->numero_recu)
            ->assertJsonPath('paiement.url_show', route('esbtp.paiements.show', $paiement->id))
            ->assertJsonPath('paiement.url_recu', route('esbtp.paiements.recu', $paiement->id));
    }

    public function test_une_repartition_sur_plusieurs_frais_passe_par_le_meme_store(): void
    {
        // Deux frais choisis sur mobile : l'ecran PROPOSE la ventilation, le
        // serveur la verifie et l'ecrit.
        $reponse = $this->postJson(route('esbtp.paiements.store'), $this->versement(250000) + [
            'repartition' => [
                (string) $this->scolarite->id => 150000,
                (string) $this->tenue->id => 100000,
            ],
        ]);

        $reponse->assertOk()->assertJsonPath('success', true);

        $paiement = ESBTPPaiement::where('inscription_id', $this->inscription->id)->firstOrFail();
        $net = ESBTPPaiement::netPaidByCategory($this->inscription->id, true);

        $this->assertSame(250000.0, (float) $paiement->montant);
        $this->assertSame(100000.0, (float) $net[$this->tenue->id]);
    }

    public function test_un_refus_metier_vaut_422_json_avec_le_champ_en_cause(): void
    {
        $this->postJson(route('esbtp.paiements.store'), $this->versement(255000))->assertOk();

        // Tout est deja couvert : un second versement n'eteint aucune dette.
        $reponse = $this->postJson(route('esbtp.paiements.store'), $this->versement(50000));

        $reponse->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['message', 'errors' => ['montant']]);

        $this->assertSame(1, ESBTPPaiement::where('inscription_id', $this->inscription->id)->count());
    }

    public function test_un_montant_a_zero_est_refuse_en_json(): void
    {
        $reponse = $this->postJson(route('esbtp.paiements.store'), $this->versement(0));

        $reponse->assertStatus(422)->assertJsonValidationErrors(['montant']);
        $this->assertSame(0, ESBTPPaiement::where('inscription_id', $this->inscription->id)->count());
    }

    public function test_le_formulaire_de_bureau_garde_sa_redirection(): void
    {
        $reponse = $this->post(route('esbtp.paiements.store'), $this->versement(80000));

        $paiement = ESBTPPaiement::where('inscription_id', $this->inscription->id)->firstOrFail();

        $reponse->assertRedirect(route('esbtp.paiements.show', $paiement->id));
    }

    // ------------------------------------------------------------------

    /**
     * Exactement les champs que l'ecran mobile envoie (cf. charge() dans
     * _encaisser-mobile.blade.php) : les memes que le formulaire de bureau.
     *
     * @return array<string, mixed>
     */
    private function versement(int $montant): array
    {
        return [
            'etudiant_id' => $this->inscription->etudiant_id,
            'inscription_id' => $this->inscription->id,
            'frais_category_id' => $this->scolarite->id,
            'montant' => $montant,
            'date_paiement' => now()->toDateString(),
            'mode_paiement' => 'Espèces',
            'reference_paiement' => null,
            'tranche' => null,
            'commentaire' => null,
            'confirmed_unusual_amount' => '1',
        ];
    }

    private function doit(ESBTPFraisCategory $categorie, float $montant): void
    {
        ESBTPFraisSubscription::factory()->create([
            'inscription_id' => $this->inscription->id,
            'frais_category_id' => $categorie->id,
            'amount' => $montant,
            'created_by' => $this->caissier->id,
        ]);
    }
}
