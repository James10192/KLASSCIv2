<?php

namespace Tests\Feature\Frais;

use App\Models\ESBTPClasse;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPPaiementAllocation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * L'encaissement lui-meme, par la porte que le caissier emprunte.
 *
 * Le service {@see \App\Services\Frais\RepartitionDuVersement} est teste a part.
 * Ici on verifie ce qui manquait vraiment : que l'ECRAN DE CAISSE declenche
 * l'imputation. C'etait le defaut de fond du mecanisme precedent — la regle
 * existait, correcte, mais rien ne l'appelait, et les montants par frais
 * n'etaient justes que si quelqu'un se souvenait de lancer une commande.
 */
class EncaissementRepartitionHttpTest extends TestCase
{
    use DatabaseTransactions;

    private User $caissier;

    private ESBTPInscription $inscription;

    private ESBTPFraisCategory $scolarite;

    private ESBTPFraisCategory $tenue;

    protected function setUp(): void
    {
        parent::setUp();

        // Deux middlewares hors sujet renverraient la requete ailleurs :
        // l'assistant d'installation, qui redirige tant que la base de test ne
        // porte pas d'administrateur, et le paywall, qui interroge l'API du SaaS
        // maitre et rendrait le test dependant du reseau.
        //
        // On les neutralise NOMMEMENT plutot que d'appeler `withoutMiddleware()`
        // sans argument : celui-ci desactive aussi la resolution des routes, et
        // le test cesserait alors de verifier ce qu'il croit verifier.
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        // Le groupe de routes exige l'acces a l'espace de gestion en plus du
        // droit d'encaisser.
        Permission::findOrCreate('admin.access', 'web');
        Permission::findOrCreate('paiements.create', 'web');
        // L'encaissement previent ensuite ceux qui valident. La permission doit
        // exister, sinon la notification jette et l'echec remonte comme une
        // erreur d'enregistrement — sans rapport avec ce qu'on teste ici.
        Permission::findOrCreate('paiements.validate', 'web');
        Cache::flush();

        $this->caissier = User::factory()->create();
        $this->caissier->givePermissionTo(['admin.access', 'paiements.create']);
        $this->actingAs($this->caissier);

        $this->inscription = $this->creerInscription();

        $this->scolarite = ESBTPFraisCategory::factory()->ordre(1)->create(['name' => 'Scolarite']);
        $this->tenue = ESBTPFraisCategory::factory()->ordre(2)->create(['name' => 'Tenue']);

        $this->doit($this->scolarite, 150000);
        $this->doit($this->tenue, 100000);
    }

    public function test_un_encaissement_couvrant_plusieurs_frais_ecrit_ses_allocations(): void
    {
        $reponse = $this->post(route('esbtp.paiements.store'), $this->versement(255000, $this->scolarite));

        $reponse->assertSessionHasNoErrors()->assertRedirect();

        $paiement = ESBTPPaiement::where('inscription_id', $this->inscription->id)->firstOrFail();

        $allocations = ESBTPPaiementAllocation::where('paiement_id', $paiement->id)
            ->pluck('montant', 'frais_category_id');

        // La propriete qui porte tout : ce qui est encaisse se retrouve, au
        // franc pres, dans les totaux par frais.
        $this->assertSame(255000.0, (float) $allocations->sum());
        $this->assertSame(155000.0, (float) $allocations[$this->scolarite->id]);
        $this->assertSame(100000.0, (float) $allocations[$this->tenue->id]);

        // Et la tenue est vue comme payee, ce qui n'arrivait pas avant : tout
        // atterrissait sur la seule categorie designee.
        $net = ESBTPPaiement::netPaidByCategory($this->inscription->id, true);
        $this->assertSame(100000.0, (float) $net[$this->tenue->id]);
    }

    public function test_un_second_encaissement_sans_dette_a_eteindre_est_refuse(): void
    {
        $this->post(route('esbtp.paiements.store'), $this->versement(255000, $this->scolarite))
            ->assertRedirect();

        // Le premier versement est encore EN ATTENTE de validation : c'est
        // exactement l'angle mort qui laissait passer le doublon.
        $this->assertSame(
            'en_attente',
            ESBTPPaiement::where('inscription_id', $this->inscription->id)->first()->status
        );

        // Un montant DIFFERENT du premier : identique, il serait intercepte en
        // amont par la detection de double-clic, et le garde-fou ne serait
        // jamais exerce.
        $this->from(route('esbtp.paiements.create'))
            ->post(route('esbtp.paiements.store'), $this->versement(50000, $this->scolarite))
            ->assertSessionHasErrors('montant');

        // Rien n'a ete ecrit : le refus intervient avant toute creation.
        $this->assertSame(1, ESBTPPaiement::where('inscription_id', $this->inscription->id)->count());
    }

    public function test_un_encaissement_simple_porte_une_allocation_couvrant_la_totalite(): void
    {
        $this->post(route('esbtp.paiements.store'), $this->versement(80000, $this->scolarite))
            ->assertRedirect();

        $paiement = ESBTPPaiement::where('inscription_id', $this->inscription->id)->firstOrFail();

        // L'invariant tient meme quand l'imputation ne dit rien de plus que le
        // paiement. Sans cela l'application resterait une machine a deux etats
        // dont le mode se fixe hors bande.
        $this->assertSame(
            80000.0,
            (float) ESBTPPaiementAllocation::where('paiement_id', $paiement->id)->sum('montant')
        );
    }

    public function test_l_apercu_annonce_la_repartition_sans_encaisser(): void
    {
        $reponse = $this->postJson(route('esbtp.paiements.repartition.apercu'), [
            'inscription_id' => $this->inscription->id,
            'frais_category_id' => $this->scolarite->id,
            'montant' => 255000,
        ]);

        $reponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('allocations.0.name', 'Scolarite')
            ->assertJsonPath('allocations.0.montant', 155000)
            ->assertJsonPath('allocations.1.name', 'Tenue')
            ->assertJsonPath('allocations.1.montant', 100000);

        // Un apercu ne touche a rien.
        $this->assertSame(0, ESBTPPaiement::where('inscription_id', $this->inscription->id)->count());
    }

    // ------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private function versement(int $montant, ESBTPFraisCategory $categorie): array
    {
        return [
            'inscription_id' => $this->inscription->id,
            'etudiant_id' => $this->inscription->etudiant_id,
            'frais_category_id' => $categorie->id,
            'montant' => $montant,
            'date_paiement' => now()->toDateString(),
            'mode_paiement' => 'espèces',
            // Le seuil « montant inhabituel » est un autre garde-fou, deja teste
            // ailleurs : on le confirme pour ne pas le confondre avec celui-ci.
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

    private function creerInscription(): ESBTPInscription
    {
        $classe = ESBTPClasse::factory()->create();

        return ESBTPInscription::factory()->create([
            'classe_id' => $classe->id,
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'annee_universitaire_id' => $classe->annee_universitaire_id,
            'created_by' => $this->caissier->id,
        ]);
    }
}
