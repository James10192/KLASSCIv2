<?php

namespace Tests\Feature\Frais;

use App\Models\ESBTPClasse;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\User;
use App\Services\Frais\SouscriptionsObligatoiresManquantes;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Une souscription fige le tarif du jour de l'inscription.
 *
 * Corriger le prix d'une categorie ne rattrapait donc aucun etudiant deja
 * inscrit : la caisse continuait de reclamer l'ancien montant, et « Regenerer
 * les frais » repondait « aucun ecart ». Ces tests tiennent le nouveau
 * comportement, et surtout ses garde-fous : on ne rejoue pas un bareme dans le
 * dos de la caisse.
 */
class RegenerationMontantsFraisTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private ESBTPInscription $inscription;

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
        Permission::findOrCreate('inscriptions.edit', 'web');
        Cache::flush();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo(['admin.access', 'inscriptions.edit']);
        $this->actingAs($this->user);

        $classe = ESBTPClasse::factory()->create();
        $this->inscription = ESBTPInscription::factory()->create([
            'classe_id' => $classe->id,
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'annee_universitaire_id' => $classe->annee_universitaire_id,
            'created_by' => $this->user->id,
            'statut_etablissement' => ESBTPInscription::STATUT_ETABLISSEMENT_ANCIEN,
        ]);
    }

    private function service(): SouscriptionsObligatoiresManquantes
    {
        return app(SouscriptionsObligatoiresManquantes::class);
    }

    /**
     * @return array{0: ESBTPFraisCategory, 1: ESBTPFraisSubscription}
     */
    private function fraisSouscrit(float $tarifCatalogue, float $montantFige, array $etat = []): array
    {
        $categorie = ESBTPFraisCategory::factory()->create([
            'name' => 'Logistique',
            'default_amount' => $tarifCatalogue,
        ]);

        $souscription = ESBTPFraisSubscription::factory()->create($etat + [
            'inscription_id' => $this->inscription->id,
            'frais_category_id' => $categorie->id,
            'amount' => $montantFige,
            'created_by' => $this->user->id,
        ]);

        return [$categorie, $souscription];
    }

    /**
     * @param  array<int, array<string, mixed>>  $lignes
     */
    private function ligneDe(array $lignes, int $categorieId): ?array
    {
        foreach ($lignes as $ligne) {
            if ((int) $ligne['categorie_id'] === $categorieId) {
                return $ligne;
            }
        }

        return null;
    }

    public function test_un_tarif_change_apres_l_inscription_est_signale_puis_applique(): void
    {
        [$categorie, $souscription] = $this->fraisSouscrit(15000, 10000);

        $apercu = $this->service()->executer(false, null, [$this->inscription->id], true);

        $ligne = $this->ligneDe($apercu['lignes_ajustement'], $categorie->id);
        $this->assertNotNull($ligne, "L'ecart de montant doit apparaitre dans l'apercu.");
        $this->assertSame(10000.0, $ligne['montant_actuel']);
        $this->assertSame(15000.0, $ligne['montant']);
        $this->assertSame(5000.0, $ligne['ecart']);

        // Un apercu n'ecrit rien.
        $this->assertSame(10000.0, (float) $souscription->fresh()->amount);

        $applique = $this->service()->executer(true, null, [$this->inscription->id], true);

        $this->assertTrue($applique['applique']);
        $this->assertSame(1, $applique['total_ajuster']);
        $this->assertSame(15000.0, (float) $souscription->fresh()->amount);
    }

    public function test_sans_demande_explicite_les_montants_ne_bougent_pas(): void
    {
        [$categorie, $souscription] = $this->fraisSouscrit(15000, 10000);

        $resultat = $this->service()->executer(true, null, [$this->inscription->id]);

        $this->assertSame(0, $resultat['total_ajuster']);
        $this->assertNull($this->ligneDe($resultat['lignes_ajustement'], $categorie->id));
        $this->assertSame(10000.0, (float) $souscription->fresh()->amount);
    }

    public function test_un_bareme_sans_configuration_n_efface_pas_le_du_existant(): void
    {
        // default_amount a zero signifie « pas de tarif configure pour ce
        // scope », pas « gratuit » : ecraser la souscription effacerait la
        // dette de l'etudiant sans que personne ne l'ait decide.
        [$categorie, $souscription] = $this->fraisSouscrit(0, 10000);

        $resultat = $this->service()->executer(true, null, [$this->inscription->id], true);

        $this->assertNull($this->ligneDe($resultat['lignes_ajustement'], $categorie->id));
        $this->assertSame(10000.0, (float) $souscription->fresh()->amount);
    }

    public function test_un_frais_solde_en_nature_n_est_pas_reevalue(): void
    {
        [$categorie, $souscription] = $this->fraisSouscrit(15000, 10000, [
            'satisfied_in_kind' => true,
            'deposited_at' => now(),
        ]);

        $resultat = $this->service()->executer(true, null, [$this->inscription->id], true);

        $this->assertNull($this->ligneDe($resultat['lignes_ajustement'], $categorie->id));
        $this->assertSame(10000.0, (float) $souscription->fresh()->amount);
    }

    public function test_une_souscription_desactivee_n_est_pas_reevaluee(): void
    {
        [$categorie, $souscription] = $this->fraisSouscrit(15000, 10000, ['is_active' => false]);

        $resultat = $this->service()->executer(true, null, [$this->inscription->id], true);

        $this->assertNull($this->ligneDe($resultat['lignes_ajustement'], $categorie->id));
        $this->assertSame(10000.0, (float) $souscription->fresh()->amount);
    }

    public function test_un_tarif_identique_ne_produit_aucun_ecart(): void
    {
        [$categorie] = $this->fraisSouscrit(10000, 10000);

        $resultat = $this->service()->executer(false, null, [$this->inscription->id], true);

        $this->assertNull($this->ligneDe($resultat['lignes_ajustement'], $categorie->id));
    }

    public function test_la_route_de_regeneration_rend_les_ecarts_de_montant(): void
    {
        [$categorie] = $this->fraisSouscrit(15000, 10000);

        $reponse = $this->postJson(route('esbtp.inscriptions.frais-manquants.preview'), [
            'inscription_ids' => [$this->inscription->id],
        ]);

        $reponse->assertOk()
            ->assertJsonPath('total_ajuster', 1);

        $this->assertNotNull(
            $this->ligneDe($reponse->json('lignes_ajustement'), $categorie->id),
            "L'ecart doit remonter jusqu'a l'ecran.",
        );
    }

    public function test_la_portee_annee_ne_reclame_pas_de_selection(): void
    {
        [$categorie] = $this->fraisSouscrit(15000, 10000);

        $reponse = $this->postJson(route('esbtp.inscriptions.frais-manquants.preview'), [
            'scope' => 'annee',
            'annee_id' => $this->inscription->annee_universitaire_id,
        ]);

        $reponse->assertOk();
        $this->assertNotNull($this->ligneDe($reponse->json('lignes_ajustement'), $categorie->id));
    }
}
