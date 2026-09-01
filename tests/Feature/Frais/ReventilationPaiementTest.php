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
use OwenIt\Auditing\Models\Audit;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Corriger l'imputation d'un versement deja encaisse.
 *
 * Le piege central de cette feature n'est pas de reecrire des lignes : c'est que
 * le reste du a comparer chaque part se calcule EN EXCLUANT le versement qu'on
 * modifie. Sans cette exclusion, un versement qui a solde la scolarite verrait
 * la scolarite a zero de reste et ne pourrait plus y etre impute : le mecanisme
 * refuserait sa propre ecriture. C'est ce que verifie le premier test.
 */
class ReventilationPaiementTest extends TestCase
{
    use DatabaseTransactions;

    private User $comptable;

    private ESBTPInscription $inscription;

    private ESBTPFraisCategory $scolarite;

    private ESBTPFraisCategory $ramette;

    private ESBTPPaiement $paiement;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        Permission::findOrCreate('admin.access', 'web');
        Permission::findOrCreate('paiements.reventiler', 'web');
        Cache::flush();

        $this->comptable = User::factory()->create();
        $this->comptable->givePermissionTo(['admin.access', 'paiements.reventiler']);
        $this->actingAs($this->comptable);

        $classe = ESBTPClasse::factory()->create();
        $this->inscription = ESBTPInscription::factory()->create([
            'classe_id' => $classe->id,
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'annee_universitaire_id' => $classe->annee_universitaire_id,
            'created_by' => $this->comptable->id,
        ]);

        $this->scolarite = ESBTPFraisCategory::factory()->ordre(1)->create(['name' => 'Scolarite']);
        $this->ramette = ESBTPFraisCategory::factory()->ordre(2)->create(['name' => 'Ramette']);

        $this->doit($this->scolarite, 150000);
        $this->doit($this->ramette, 5000);

        // Le cas d'ISLG : la ramette n'existait pas au guichet, tout est parti
        // sur la scolarite.
        $this->paiement = $this->versement(150000, $this->scolarite);
        ESBTPPaiementAllocation::create([
            'paiement_id' => $this->paiement->id,
            'frais_category_id' => $this->scolarite->id,
            'montant' => 150000,
        ]);
    }

    public function test_le_reste_exclut_le_versement_qu_on_est_en_train_de_corriger(): void
    {
        // Ce versement solde la scolarite. S'il se comptait lui-meme, la
        // scolarite ressortirait a zero de reste et l'ecran refuserait de lui
        // rendre la moindre part — la correction serait impossible.
        $reste = app(\App\Services\Frais\RepartitionDuVersement::class)
            ->resteConnuParFrais($this->inscription->id, $this->paiement->id);

        $this->assertSame(150000.0, $reste[$this->scolarite->id]);
        $this->assertSame(5000.0, $reste[$this->ramette->id]);

        // Sans l'exclusion, la scolarite est vue comme entierement soldee.
        $sansExclusion = app(\App\Services\Frais\RepartitionDuVersement::class)
            ->resteConnuParFrais($this->inscription->id);

        $this->assertSame(0.0, $sansExclusion[$this->scolarite->id]);
    }

    public function test_la_correction_reecrit_la_ventilation_sans_toucher_au_versement(): void
    {
        $reponse = $this->putJson($this->url(), [
            'motif' => 'La ramette n\'etait pas configuree le jour de l\'encaissement.',
            'repartition' => [
                $this->scolarite->id => 145000,
                $this->ramette->id => 5000,
            ],
        ]);

        $reponse->assertOk()->assertJsonPath('success', true);

        $allocations = ESBTPPaiementAllocation::where('paiement_id', $this->paiement->id)
            ->pluck('montant', 'frais_category_id');

        $this->assertSame(145000.0, (float) $allocations[$this->scolarite->id]);
        $this->assertSame(5000.0, (float) $allocations[$this->ramette->id]);

        // Ce qui NE change pas : c'est tout l'interet de cet ecran par rapport a
        // une annulation-ressaisie, qui casserait la numerotation des recus.
        $this->paiement->refresh();
        $this->assertSame(150000.0, (float) $this->paiement->montant);
        $this->assertNotNull($this->paiement->ventilation_rectifiee_le);
    }

    public function test_une_part_qui_depasse_ce_que_son_frais_reclame_est_refusee(): void
    {
        // La ramette ne reclame que 5 000 F. Le frais DESIGNE par le versement
        // (la scolarite) peut recevoir davantage — c'est ainsi qu'une avance
        // s'enregistre — mais pas les autres.
        $this->putJson($this->url(), [
            'motif' => 'Tentative d\'imputer plus que le du sur la ramette.',
            'repartition' => [
                $this->scolarite->id => 100000,
                $this->ramette->id => 50000,
            ],
        ])->assertStatus(422);

        $this->assertSame(
            150000.0,
            (float) ESBTPPaiementAllocation::where('paiement_id', $this->paiement->id)
                ->where('frais_category_id', $this->scolarite->id)
                ->value('montant')
        );
    }

    public function test_une_repartition_qui_ne_fait_pas_le_compte_est_refusee(): void
    {
        // 145 000 sur 150 000 : les 5 000 restants disparaitraient des totaux
        // par frais sans erreur ni trace, puisqu'un versement alloue est lu par
        // ses allocations et plus du tout par sa categorie.
        $this->putJson($this->url(), [
            'motif' => 'Repartition volontairement incomplete pour le test.',
            'repartition' => [$this->scolarite->id => 145000],
        ])->assertStatus(422);
    }

    public function test_un_frais_qui_disparait_de_la_ventilation_perd_sa_ligne(): void
    {
        // On part d'une ventilation sur DEUX frais, puis on la ramene a un
        // seul : la ramette avait ete imputee a tort, elle sort.
        //
        // `ecrire()` n'ajoute qu'aux lignes existantes. Si la correction se
        // contentait de cela, la ligne de ramette resterait a cote de la
        // nouvelle scolarite et le versement compterait 155 000 F pour 150 000
        // encaisses.
        ESBTPPaiementAllocation::where('paiement_id', $this->paiement->id)->delete();
        ESBTPPaiementAllocation::insert([
            ['paiement_id' => $this->paiement->id, 'frais_category_id' => $this->scolarite->id, 'montant' => 145000],
            ['paiement_id' => $this->paiement->id, 'frais_category_id' => $this->ramette->id, 'montant' => 5000],
        ]);

        $this->putJson($this->url(), [
            'motif' => 'La ramette avait ete imputee a tort, tout revient a la scolarite.',
            'repartition' => [$this->scolarite->id => 150000],
        ])->assertOk();

        $lignes = ESBTPPaiementAllocation::where('paiement_id', $this->paiement->id)->get();

        $this->assertCount(1, $lignes, 'La ligne de ramette doit avoir disparu.');
        $this->assertSame($this->scolarite->id, $lignes->first()->frais_category_id);
        $this->assertSame(150000.0, (float) $lignes->first()->montant);
    }

    public function test_une_part_sur_un_frais_non_designe_reste_plafonnee_au_du(): void
    {
        // Le pendant du test precedent : deplacer TOUT sur la ramette, qui ne
        // reclame que 5 000 F et n'est pas le frais designe par le versement,
        // est refuse. Seul le frais designe porte une avance.
        $this->putJson($this->url(), [
            'motif' => 'Tentative de tout reporter sur un frais qui ne le reclame pas.',
            'repartition' => [$this->ramette->id => 150000],
        ])->assertStatus(422);
    }

    public function test_un_motif_trop_court_est_refuse(): void
    {
        $this->putJson($this->url(), [
            'motif' => 'erreur',
            'repartition' => [
                $this->scolarite->id => 145000,
                $this->ramette->id => 5000,
            ],
        ])->assertStatus(422)->assertJsonValidationErrors('motif');
    }

    public function test_la_correction_laisse_un_audit_avec_les_deux_ventilations_et_le_motif(): void
    {
        $motif = 'La ramette n\'etait pas configuree le jour de l\'encaissement.';

        $this->putJson($this->url(), [
            'motif' => $motif,
            'repartition' => [
                $this->scolarite->id => 145000,
                $this->ramette->id => 5000,
            ],
        ])->assertOk();

        $audit = Audit::where('auditable_type', ESBTPPaiement::class)
            ->where('auditable_id', $this->paiement->id)
            ->where('event', 'reventilation')
            ->latest('id')
            ->first();

        $this->assertNotNull($audit, 'La correction doit laisser un audit de synthese.');
        $this->assertSame($this->comptable->id, (int) $audit->user_id);
        $this->assertSame($motif, $audit->new_values['motif']);
        $this->assertSame(150000, (int) $audit->old_values['ventilation'][$this->scolarite->id]);
        $this->assertSame(145000, (int) $audit->new_values['ventilation'][$this->scolarite->id]);
        $this->assertSame(5000, (int) $audit->new_values['ventilation'][$this->ramette->id]);
    }

    public function test_sans_la_permission_dediee_l_ecran_est_ferme(): void
    {
        // `paiements.edit` ne suffit pas : corriger une imputation est une
        // ecriture sur de l'argent deja encaisse, l'ecole decide separement a
        // qui elle la confie.
        Permission::findOrCreate('paiements.edit', 'web');

        $autre = User::factory()->create();
        $autre->givePermissionTo(['admin.access', 'paiements.edit']);

        $this->actingAs($autre)->get($this->url())->assertForbidden();
    }

    public function test_un_versement_de_periode_verrouillee_ne_se_corrige_pas(): void
    {
        // `set()` refuse une cle qui n'existe pas encore.
        \App\Helpers\SettingsHelper::setOrCreate(
            'comptabilite.period_locked_until',
            now()->addDay()->toDateString(),
            'comptabilite'
        );
        Cache::flush();

        // Le bypass est une permission a part, que ce comptable n'a pas.
        $this->putJson($this->url(), [
            'motif' => 'Tentative de correction sur une periode close.',
            'repartition' => [
                $this->scolarite->id => 145000,
                $this->ramette->id => 5000,
            ],
        ])->assertStatus(403);

        $this->assertSame(
            150000.0,
            (float) ESBTPPaiementAllocation::where('paiement_id', $this->paiement->id)
                ->where('frais_category_id', $this->scolarite->id)
                ->value('montant')
        );
    }

    public function test_un_versement_rejete_ne_se_reventile_pas(): void
    {
        // Un versement rejete n'entre dans aucun total par frais : lui ecrire
        // des allocations produirait des lignes que personne ne lit, et poserait
        // sur la fiche une mention de rectification sans objet.
        $this->paiement->update(['status' => 'rejeté']);

        $this->putJson($this->url(), [
            'motif' => 'Tentative de correction sur un versement rejete.',
            'repartition' => [
                $this->scolarite->id => 145000,
                $this->ramette->id => 5000,
            ],
        ])->assertStatus(403);
    }

    public function test_un_avoir_ne_se_reventile_pas(): void
    {
        // Le garde-fou compare chaque part au reste du. Un avoir REND du du au
        // lieu d'en eteindre : la mesure n'a pas de sens pour lui et refuserait
        // tout. On le dit plutot que de laisser l'utilisateur buter dessus.
        $this->paiement->update(['nature' => 'avoir']);

        $this->get($this->url())
            ->assertRedirect(route('esbtp.paiements.show', $this->paiement->id))
            ->assertSessionHas('error');
    }

    public function test_l_ecran_propose_les_frais_et_leur_reste_hors_ce_versement(): void
    {
        $reponse = $this->get($this->url());

        $reponse->assertOk()
            ->assertSee('Corriger la répartition', false)
            ->assertSee('Scolarite', false)
            ->assertSee('Ramette', false);
    }

    public function test_le_recu_enumere_les_frais_et_declare_la_rectification(): void
    {
        // Le recu n'imprimait que la categorie choisie au guichet : il mentait
        // des que le versement couvrait plusieurs frais. Et apres correction, il
        // doit dire qu'il a ete rectifie plutot que d'etre reecrit en silence —
        // deux exemplaires du meme numero ne peuvent pas dire deux choses sans
        // qu'on sache laquelle est la plus recente.
        $this->putJson($this->url(), [
            'motif' => 'La ramette n\'etait pas configuree le jour de l\'encaissement.',
            'repartition' => [
                $this->scolarite->id => 145000,
                $this->ramette->id => 5000,
            ],
        ])->assertOk();

        $this->paiement->refresh()->load('allocations.fraisCategory:id,name');

        $html = view('esbtp.paiements.partials.recu-exemplaire', [
            'paiement' => $this->paiement,
            'copyTag' => 'EXEMPLAIRE ÉLÈVE',
            'settings' => [],
            'primary' => '#0453cb',
            'hdrBg' => '#0453cb',
            'hdrText' => '#ffffff',
            'barText' => '#ffffff',
            'categoryName' => null,
            'fraisLignes' => collect(),
            'resteAPayer' => 0,
        ])->render();

        $this->assertStringContainsString('Scolarite : 145 000', $html);
        $this->assertStringContainsString('Ramette : 5 000', $html);
        $this->assertStringContainsString('Ventilation rectifiée le', $html);
    }

    // ------------------------------------------------------------------

    private function url(): string
    {
        return route('esbtp.paiements.ventilation.edit', $this->paiement->id);
    }

    private function versement(float $montant, ESBTPFraisCategory $categorie): ESBTPPaiement
    {
        return ESBTPPaiement::create([
            'etudiant_id' => $this->inscription->etudiant_id,
            'inscription_id' => $this->inscription->id,
            // La colonne est NOT NULL sans defaut : l'omettre fait echouer
            // l'insertion avec un message qui ne parle pas de paiement.
            'annee_universitaire_id' => $this->inscription->annee_universitaire_id,
            'frais_category_id' => $categorie->id,
            'montant' => $montant,
            'date_paiement' => now()->toDateString(),
            'mode_paiement' => 'espèces',
            'status' => 'validé',
            'numero_recu' => 'TEST-'.uniqid(),
            'created_by' => $this->comptable->id,
        ]);
    }

    private function doit(ESBTPFraisCategory $categorie, float $montant): void
    {
        ESBTPFraisSubscription::factory()->create([
            'inscription_id' => $this->inscription->id,
            'frais_category_id' => $categorie->id,
            'amount' => $montant,
            'created_by' => $this->comptable->id,
        ]);
    }
}
