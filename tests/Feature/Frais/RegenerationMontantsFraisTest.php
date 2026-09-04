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

    public function test_on_peut_n_appliquer_qu_une_partie_des_ecarts(): void
    {
        [$categorieA, $souscriptionA] = $this->fraisSouscrit(15000, 10000);
        [$categorieB, $souscriptionB] = $this->fraisSouscrit(9000, 6000);

        $apercu = $this->service()->executer(false, null, [$this->inscription->id], true);
        $ligneA = $this->ligneDe($apercu['lignes_ajustement'], $categorieA->id);
        $this->assertNotNull($ligneA);
        $this->assertNotNull($this->ligneDe($apercu['lignes_ajustement'], $categorieB->id));

        // On ne retient que la premiere ligne.
        $resultat = $this->service()->executer(
            true, null, [$this->inscription->id], true, null, [$ligneA['cle']]
        );

        $this->assertSame(1, $resultat['total_ajuster']);
        $this->assertSame(15000.0, (float) $souscriptionA->fresh()->amount);
        $this->assertSame(6000.0, (float) $souscriptionB->fresh()->amount, 'La ligne non retenue ne doit pas bouger.');
    }

    public function test_une_selection_vide_n_ecrit_rien(): void
    {
        [, $souscription] = $this->fraisSouscrit(15000, 10000);

        $resultat = $this->service()->executer(true, null, [$this->inscription->id], true, null, []);

        $this->assertSame(0, $resultat['total_ajuster']);
        $this->assertSame(10000.0, (float) $souscription->fresh()->amount);
    }

    public function test_un_montant_retouche_a_la_main_est_signale(): void
    {
        [$categorie, $souscription] = $this->fraisSouscrit(15000, 12000);

        // Une remise accordee a la main : c'est une decision, pas une erreur.
        $souscription->update(['amount' => 10000]);

        $apercu = $this->service()->executer(false, null, [$this->inscription->id], true);
        $ligne = $this->ligneDe($apercu['lignes_ajustement'], $categorie->id);

        $this->assertNotNull($ligne);
        $this->assertTrue($ligne['montant_deja_retouche'], 'Le montant retouche doit etre signale.');
        $this->assertNotNull($ligne['retouche_le']);
    }

    public function test_un_montant_jamais_touche_n_est_pas_signale(): void
    {
        [$categorie] = $this->fraisSouscrit(15000, 10000);

        $apercu = $this->service()->executer(false, null, [$this->inscription->id], true);
        $ligne = $this->ligneDe($apercu['lignes_ajustement'], $categorie->id);

        $this->assertNotNull($ligne);
        $this->assertFalse($ligne['montant_deja_retouche']);
    }

    public function test_la_regeneration_ne_prend_pas_ses_propres_ecritures_pour_des_retouches(): void
    {
        [$categorie, $souscription] = $this->fraisSouscrit(15000, 10000);

        // Premier passage : la regeneration ecrit 15 000.
        $this->service()->executer(true, null, [$this->inscription->id], true);
        $this->assertSame(15000.0, (float) $souscription->fresh()->amount);

        // L'ecole change encore le tarif : nouvel ecart, mais la seule ecriture
        // passee est la notre. Sans son marqueur, la ligne reviendrait signalee
        // « retouchee a la main » et l'alerte se serait diluee.
        $categorie->update(['default_amount' => 18000]);

        $apercu = $this->service()->executer(false, null, [$this->inscription->id], true);
        $ligne = $this->ligneDe($apercu['lignes_ajustement'], $categorie->id);

        $this->assertNotNull($ligne);
        $this->assertFalse($ligne['montant_deja_retouche']);
    }

    public function test_la_portee_filtre_suit_les_filtres_de_la_liste(): void
    {
        [$categorie] = $this->fraisSouscrit(15000, 10000);
        $this->inscription->update(['date_inscription' => '2026-09-10']);

        $dansLaPeriode = $this->postJson(route('esbtp.inscriptions.frais-manquants.preview'), [
            'scope' => 'filtre',
            'annee' => $this->inscription->annee_universitaire_id,
            'status' => 'all',
            'date_debut' => '2026-09-01',
            'date_fin' => '2026-09-30',
        ]);
        $dansLaPeriode->assertOk();
        $this->assertNotNull($this->ligneDe($dansLaPeriode->json('lignes_ajustement'), $categorie->id));

        $horsPeriode = $this->postJson(route('esbtp.inscriptions.frais-manquants.preview'), [
            'scope' => 'filtre',
            'annee' => $this->inscription->annee_universitaire_id,
            'status' => 'all',
            'date_debut' => '2026-10-01',
            'date_fin' => '2026-10-31',
        ]);
        $horsPeriode->assertOk()->assertJsonPath('total_ajuster', 0);
    }

    public function test_une_recherche_libre_ne_definit_pas_une_portee(): void
    {
        $this->fraisSouscrit(15000, 10000);

        $this->postJson(route('esbtp.inscriptions.frais-manquants.preview'), [
            'scope' => 'filtre',
            'search' => 'kouame',
        ])->assertStatus(422);
    }
}
