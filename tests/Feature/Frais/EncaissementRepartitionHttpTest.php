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
        // L'apercu de repartition enumere ce que l'etudiant doit ENCORE, frais
        // par frais : c'est sa situation financiere, et sa route exige donc le
        // droit de la CONSULTER, pas seulement celui d'encaisser. Un caissier
        // reel porte les deux.
        Permission::findOrCreate('paiements.view', 'web');
        // Corriger un versement deja enregistre : deux permissions distinctes,
        // l'une pour la route, l'autre verifiee dans le controleur.
        Permission::findOrCreate('paiements.edit', 'web');
        Permission::findOrCreate('paiements.manage', 'web');
        // L'encaissement previent ensuite ceux qui valident. La permission doit
        // exister, sinon la notification jette et l'echec remonte comme une
        // erreur d'enregistrement — sans rapport avec ce qu'on teste ici.
        Permission::findOrCreate('paiements.validate', 'web');
        Cache::flush();

        $this->caissier = User::factory()->create();
        $this->caissier->givePermissionTo([
            'admin.access', 'paiements.create', 'paiements.view',
            'paiements.edit', 'paiements.manage',
        ]);
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

    public function test_l_ecran_de_caisse_rend_le_panneau_de_repartition(): void
    {
        // Le panneau et l'URL d'apercu doivent arriver dans la page. Sans cette
        // verification, une vue qui compile mais ne rend pas — une condition
        // Blade mal placee, une variable manquante — passerait inapercue : les
        // autres tests parlent a la route d'enregistrement, jamais a l'ecran.
        $reponse = $this->get(route('esbtp.paiements.create', [
            'etudiant_id' => $this->inscription->etudiant_id,
            'inscription_id' => $this->inscription->id,
        ]));

        $reponse->assertOk()
            ->assertSee('id="repartition-section"', false)
            ->assertSee('Répartition du versement', false)
            ->assertSee('Répartir moi-même', false);

        // L'URL d'apercu arrive dans la page via `@json(...)`, qui echappe les
        // slashes (`\/`). Chercher l'URL telle que `route()` la rend ne
        // matcherait donc jamais — on compare a la forme reellement emise.
        $reponse->assertSee(
            str_replace('/', '\/', route('esbtp.paiements.repartition.apercu')),
            false
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

    public function test_un_frais_sans_tarif_configure_reste_encaissable(): void
    {
        // L'ecole n'a pas encore dit ce que ce frais coute. Un zero veut dire
        // INCONNU : lui opposer un plafond de zero le rendrait inencaissable
        // jusqu'a ce qu'un administrateur s'en apercoive.
        $bibliotheque = ESBTPFraisCategory::factory()->ordre(3)->create(['name' => 'Bibliotheque']);
        $this->doit($bibliotheque, 0);

        $this->post(route('esbtp.paiements.store'), $this->versement(40000, $bibliotheque))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $paiement = ESBTPPaiement::where('inscription_id', $this->inscription->id)->firstOrFail();

        $this->assertSame(
            40000.0,
            (float) ESBTPPaiementAllocation::where('paiement_id', $paiement->id)
                ->where('frais_category_id', $bibliotheque->id)
                ->sum('montant')
        );
    }

    public function test_le_caissier_peut_repartir_lui_meme_le_versement(): void
    {
        // Le second mode de l'ecran : le caissier dit lui-meme ou va chaque
        // franc, au lieu de laisser la regle de service decider.
        $charge = $this->versement(120000, $this->scolarite);
        $charge['repartition'] = [
            $this->scolarite->id => 70000,
            $this->tenue->id => 50000,
        ];

        $this->post(route('esbtp.paiements.store'), $charge)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $paiement = ESBTPPaiement::where('inscription_id', $this->inscription->id)->firstOrFail();
        $allocations = ESBTPPaiementAllocation::where('paiement_id', $paiement->id)
            ->pluck('montant', 'frais_category_id');

        $this->assertSame(70000.0, (float) $allocations[$this->scolarite->id]);
        $this->assertSame(50000.0, (float) $allocations[$this->tenue->id]);
    }

    public function test_une_repartition_manuelle_qui_depasse_un_frais_est_refusee(): void
    {
        // La tenue ne doit que 100 000 : lui en imputer 150 000 ferait entrer
        // 50 000 F dans un frais qui ne les reclame pas.
        $charge = $this->versement(250000, $this->scolarite);
        $charge['repartition'] = [
            $this->scolarite->id => 100000,
            $this->tenue->id => 150000,
        ];

        $this->from(route('esbtp.paiements.create'))
            ->post(route('esbtp.paiements.store'), $charge)
            ->assertSessionHasErrors('montant');

        $this->assertSame(0, ESBTPPaiement::where('inscription_id', $this->inscription->id)->count());
    }

    // ------------------------------------------------------------------
    // Le depot en nature ferme les DEUX portes
    // ------------------------------------------------------------------

    public function test_un_frais_deja_depose_en_nature_ne_se_corrige_pas_non_plus(): void
    {
        // La garde vivait a l'encaissement seulement. Rediriger un versement en
        // attente vers la ramette deja apportee par l'etudiant la lui faisait
        // payer aussi surement qu'un encaissement direct : c'est le meme argent,
        // par l'autre porte.
        $ramette = ESBTPFraisCategory::factory()->ordre(4)->create(['name' => 'Ramette']);
        ESBTPFraisSubscription::factory()->enNature()->create([
            'inscription_id' => $this->inscription->id,
            'frais_category_id' => $ramette->id,
            'amount' => 5000,
            'created_by' => $this->caissier->id,
        ]);

        $this->post(route('esbtp.paiements.store'), $this->versement(50000, $this->scolarite))
            ->assertRedirect();

        $paiement = ESBTPPaiement::where('inscription_id', $this->inscription->id)->firstOrFail();

        $this->from(route('esbtp.paiements.edit', $paiement->id))
            ->put(route('esbtp.paiements.update', $paiement->id), [
                'montant' => 50000,
                'date_paiement' => now()->toDateString(),
                'mode_paiement' => 'espèces',
                'frais_category_id' => $ramette->id,
            ])
            ->assertSessionHasErrors('frais_category_id');

        $this->assertSame(
            (int) $this->scolarite->id,
            (int) $paiement->fresh()->frais_category_id,
            'Le versement doit rester sur son frais d origine.'
        );
    }

    // ------------------------------------------------------------------
    // Corriger un versement : l'imputation suit, et un refus se dit
    // ------------------------------------------------------------------

    public function test_corriger_le_montant_reecrit_l_imputation(): void
    {
        // Un versement alloue est lu EXCLUSIVEMENT par ses allocations : sa
        // propre categorie ne compte plus. Corriger le montant sans les
        // reecrire laisserait la difference hors de tout total par frais.
        $this->post(route('esbtp.paiements.store'), $this->versement(80000, $this->scolarite))
            ->assertRedirect();

        $paiement = ESBTPPaiement::where('inscription_id', $this->inscription->id)->firstOrFail();

        $this->put(route('esbtp.paiements.update', $paiement->id), [
            'montant' => 120000,
            'date_paiement' => now()->toDateString(),
            'mode_paiement' => 'espèces',
            'frais_category_id' => $this->scolarite->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame(
            120000.0,
            (float) ESBTPPaiementAllocation::where('paiement_id', $paiement->id)->sum('montant')
        );
    }

    // ------------------------------------------------------------------
    // La situation financiere n'est pas un sous-produit du droit d'encaisser
    // ------------------------------------------------------------------

    public function test_l_apercu_exige_le_droit_de_consulter_les_comptes(): void
    {
        // Cette reponse enumere, frais par frais, ce que l'etudiant doit ENCORE.
        // Les endpoints qui servent la meme information (`etudiants/soldes`,
        // `frais/categories`) l'ont toujours reservee ; celui-ci s'ouvrait sur le
        // seul droit d'encaisser, si bien qu'un role taille pour saisir des
        // versements sans consulter les comptes pouvait reconstituer les
        // finances de l'ecole en bouclant sur les identifiants d'inscription.
        $saisisseur = User::factory()->create();
        $saisisseur->givePermissionTo(['admin.access', 'paiements.create']);
        Cache::flush();

        $this->actingAs($saisisseur)
            ->postJson(route('esbtp.paiements.repartition.apercu'), [
                'inscription_id' => $this->inscription->id,
                'frais_category_id' => $this->scolarite->id,
                'montant' => 50000,
            ])
            ->assertForbidden();
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
