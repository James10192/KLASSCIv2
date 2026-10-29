<?php

namespace Tests\Feature\Matiere;

use App\Domain\Assistant\Actions\Matieres\ConfigurerMaquetteBts;
use App\Models\ESBTPConfigMatiere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\User;
use App\Services\BulletinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

class MaquetteTypeFormationTest extends TestCase
{
    use MonteUneClasseBts, RefreshDatabase;

    private User $acteur;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monterLaClasse();

        foreach (['admin.access', 'matieres.edit', 'bulletins.configure'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $superAdmin = Role::findOrCreate('superAdmin', 'web');
        User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()])->assignRole($superAdmin);

        $this->acteur = User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]);
        $this->acteur->givePermissionTo(['admin.access', 'matieres.edit', 'bulletins.configure']);
    }

    private function lier(ESBTPMatiere $matiere): void
    {
        ESBTPMatiereFilierNiveau::create([
            'matiere_id' => $matiere->id,
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
        ]);
    }

    public function test_la_maquette_enregistre_general_technique_au_grain_filiere_niveau(): void
    {
        $matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null, 'type_formation' => 'technologique_professionnelle']);
        $this->lier($matiere);

        $this->actingAs($this->acteur)->postJson(route('esbtp.matieres.classification.save'), [
            'filiere_id' => $this->filiere->id,
            'niveau_id' => $this->niveau->id,
            'classifications' => [[
                'matiere_id' => $matiere->id,
                'semestre' => 1,
                'classification' => 'tronc_commun',
                'type_formation' => 'general',
            ]],
        ])->assertOk();

        $this->assertDatabaseHas('esbtp_matiere_filiere_niveau', [
            'matiere_id' => $matiere->id,
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
            'type_formation' => 'general',
        ]);

        $this->actingAs($this->acteur)->getJson(route('esbtp.matieres.classification.combo', [
            'filiere_id' => $this->filiere->id,
            'niveau_id' => $this->niveau->id,
        ]))->assertOk()->assertJsonPath('matieres.0.type_formation', 'general');
    }

    public function test_resultats_reste_un_override_plus_precis_que_la_maquette(): void
    {
        $matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null, 'type_formation' => 'technologique_professionnelle']);
        $this->lier($matiere);
        ESBTPMatiereFilierNiveau::forCombo($this->filiere->id, $this->niveau->id)
            ->where('matiere_id', $matiere->id)->update(['type_formation' => 'general']);

        $service = app(BulletinService::class);
        self::assertSame('generale', $service->resolveMatiereTypeFormation(
            $matiere->id, $this->classe->id, 'semestre1', $this->annee->id
        ));

        ESBTPConfigMatiere::create([
            'matiere_id' => $matiere->id,
            'classe_id' => $this->classe->id,
            'periode' => 'semestre1',
            'annee_universitaire_id' => $this->annee->id,
            'config' => ['type' => 'technique'],
        ]);

        self::assertSame('technologique_professionnelle', $service->resolveMatiereTypeFormation(
            $matiere->id, $this->classe->id, 'semestre1', $this->annee->id
        ));
    }

    public function test_modifier_general_technique_exige_bulletins_configure(): void
    {
        $matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);
        $this->lier($matiere);
        $acteur = User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]);
        $acteur->givePermissionTo(['admin.access', 'matieres.edit']);

        $this->actingAs($acteur)->postJson(route('esbtp.matieres.classification.save'), [
            'filiere_id' => $this->filiere->id,
            'niveau_id' => $this->niveau->id,
            'classifications' => [['matiere_id' => $matiere->id, 'type_formation' => 'general']],
        ])->assertForbidden();
    }

    public function test_nanan_ne_recoit_l_action_que_si_les_deux_droits_sont_presents(): void
    {
        $action = app(ConfigurerMaquetteBts::class);
        self::assertTrue($action->isAvailableFor($this->acteur));

        $limite = User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]);
        $limite->givePermissionTo(['admin.access', 'matieres.edit']);
        self::assertFalse($action->isAvailableFor($limite));
    }
}
