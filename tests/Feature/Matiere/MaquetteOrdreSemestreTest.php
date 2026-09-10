<?php

namespace Tests\Feature\Matiere;

use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * La maquette d'un combo : place sur le bulletin, semestre, et surtout le
 * moment ou elle devient « renseignee ».
 *
 * Le point delicat : « toutes les matieres aux deux semestres » est une
 * REPONSE, pas une absence de reponse. Un drapeau de validation explicite est
 * donc necessaire — sans lui, cette maquette serait indiscernable d'une
 * maquette jamais remplie, et repasser une matiere de « semestre 1 » a « les
 * deux » desactiverait tout le combo.
 */
class MaquetteOrdreSemestreTest extends TestCase
{
    use MonteUneClasseBts, RefreshDatabase;

    private User $acteur;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monterLaClasse();

        Permission::findOrCreate('admin.access', 'web');
        Permission::findOrCreate('matieres.edit', 'web');

        // Sans un superAdmin en base, EnsureInstalled redirige tout vers /install.
        $superAdmin = Role::findOrCreate('superAdmin', 'web');
        User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()])
            ->assignRole($superAdmin);

        $this->acteur = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $this->acteur->givePermissionTo(['admin.access', 'matieres.edit']);
    }

    private function lierAuCombo(ESBTPMatiere $matiere): ESBTPMatiereFilierNiveau
    {
        return ESBTPMatiereFilierNiveau::create([
            'matiere_id' => $matiere->id,
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
        ]);
    }

    private function enregistrer(array $lignes, bool $validerSemestres = false)
    {
        return $this->actingAs($this->acteur)->postJson(
            route('esbtp.matieres.classification.save'),
            array_filter([
                'filiere_id' => $this->filiere->id,
                'niveau_id' => $this->niveau->id,
                'classifications' => $lignes,
                'valider_semestres' => $validerSemestres ?: null,
            ], fn ($v) => $v !== null)
        );
    }

    public function test_la_place_et_le_semestre_sont_enregistres(): void
    {
        $matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);
        $this->lierAuCombo($matiere);

        $this->enregistrer([
            ['matiere_id' => $matiere->id, 'ordre_bulletin' => 3, 'semestre' => 2],
        ], validerSemestres: true)->assertOk();

        $ligne = ESBTPMatiereFilierNiveau::where('matiere_id', $matiere->id)->first();
        self::assertSame(3, (int) $ligne->ordre_bulletin);
        self::assertSame(2, (int) $ligne->semestre);
        self::assertTrue((bool) $ligne->semestre_renseigne);
    }

    public function test_un_combo_entierement_aux_deux_semestres_compte_comme_renseigne(): void
    {
        $a = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);
        $b = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);
        $this->lierAuCombo($a);
        $this->lierAuCombo($b);

        // Aucune matiere n'a de semestre : elles sont toutes aux deux.
        $reponse = $this->enregistrer([
            ['matiere_id' => $a->id, 'semestre' => null],
            ['matiere_id' => $b->id, 'semestre' => null],
        ], validerSemestres: true);

        $reponse->assertOk()->assertJsonPath('maquette_renseignee', true);
    }

    public function test_revenir_aux_deux_semestres_ne_desactive_pas_la_maquette(): void
    {
        $matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);
        $this->lierAuCombo($matiere);

        $this->enregistrer([['matiere_id' => $matiere->id, 'semestre' => 1]], validerSemestres: true)->assertOk();

        // La derniere valeur explicite redevient « les deux » : la maquette
        // reste renseignee, elle ne retombe pas dans l'indefini.
        $this->enregistrer([['matiere_id' => $matiere->id, 'semestre' => null]], validerSemestres: true)
            ->assertOk()
            ->assertJsonPath('maquette_renseignee', true);

        $ligne = ESBTPMatiereFilierNiveau::where('matiere_id', $matiere->id)->first();
        self::assertNull($ligne->semestre);
        self::assertTrue((bool) $ligne->semestre_renseigne);
    }

    public function test_enregistrer_un_ordre_seul_n_active_pas_la_maquette(): void
    {
        $matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);
        $this->lierAuCombo($matiere);

        $this->enregistrer([['matiere_id' => $matiere->id, 'ordre_bulletin' => 2]])
            ->assertOk()
            ->assertJsonPath('maquette_renseignee', false);

        self::assertFalse(
            (bool) ESBTPMatiereFilierNiveau::where('matiere_id', $matiere->id)->first()->semestre_renseigne
        );
    }

    public function test_un_semestre_hors_bornes_est_refuse(): void
    {
        $matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);
        $this->lierAuCombo($matiere);

        $this->enregistrer([['matiere_id' => $matiere->id, 'semestre' => 3]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('classifications.0.semestre');
    }

    public function test_une_place_nulle_ou_negative_est_refusee(): void
    {
        $matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);
        $this->lierAuCombo($matiere);

        $this->enregistrer([['matiere_id' => $matiere->id, 'ordre_bulletin' => 0]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('classifications.0.ordre_bulletin');

        $this->enregistrer([['matiere_id' => $matiere->id, 'ordre_bulletin' => -1]])
            ->assertStatus(422);
    }

    public function test_une_place_hors_bornes_de_stockage_est_refusee(): void
    {
        $matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);
        $this->lierAuCombo($matiere);

        // 65536 depasse unsignedSmallInteger : MySQL le tronquerait en silence.
        $this->enregistrer([['matiere_id' => $matiere->id, 'ordre_bulletin' => 65536]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('classifications.0.ordre_bulletin');
    }

    public function test_le_retour_a_l_ordre_general_efface_les_rangs_propres(): void
    {
        $matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null, 'ordre_bulletin' => 9]);
        $this->lierAuCombo($matiere);
        $this->enregistrer([['matiere_id' => $matiere->id, 'ordre_bulletin' => 1]])->assertOk();

        $this->actingAs($this->acteur)->postJson(route('esbtp.matieres.classification.reset-ordre'), [
            'filiere_id' => $this->filiere->id,
            'niveau_id' => $this->niveau->id,
        ])->assertOk();

        self::assertNull(
            ESBTPMatiereFilierNiveau::where('matiere_id', $matiere->id)->first()->ordre_bulletin
        );
        // L'ordre general, lui, survit : il est le repli.
        self::assertSame(9, (int) $matiere->fresh()->ordre_bulletin);
    }

    public function test_la_page_de_maquette_se_rend(): void
    {
        // Rend reellement la vue : c'est la seule facon de prendre un piege
        // Blade (directive avalee, composant dans un commentaire) qui ne se voit
        // ni a la lecture ni a la compilation du cache.
        $reponse = $this->actingAs($this->acteur)->get(route('esbtp.matieres.classification'));

        $reponse->assertOk()
            ->assertSee('Matières prévues', false)
            ->assertSee('matiereClassification()', false);
    }

    public function test_sans_la_permission_l_enregistrement_est_refuse(): void
    {
        $matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);
        $this->lierAuCombo($matiere);

        $intrus = User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]);
        $intrus->givePermissionTo('admin.access');

        $this->actingAs($intrus)->postJson(route('esbtp.matieres.classification.save'), [
            'filiere_id' => $this->filiere->id,
            'niveau_id' => $this->niveau->id,
            'classifications' => [['matiere_id' => $matiere->id, 'ordre_bulletin' => 1]],
        ])->assertForbidden();
    }
}
