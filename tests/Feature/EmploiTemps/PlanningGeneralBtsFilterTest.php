<?php

namespace Tests\Feature\EmploiTemps;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPLMDDomaine;
use App\Models\ESBTPLMDMention;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PlanningGeneralBtsFilterTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('module.emploi_temps.access', 'web');
        Permission::findOrCreate('admin.access', 'web');
        Role::findOrCreate('superAdmin', 'web');
        Role::findOrCreate('enseignant', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_planning_general_routes_hide_pure_lmd_filters_and_keep_mixed_filiere(): void
    {
        $this->authenticateAsAdmin();
        $this->seedPlanningFilterFixtures();

        foreach ([
            route('esbtp.planning-general.index'),
            route('esbtp.planning-general.repartition-matieres'),
            route('esbtp.planning-general.impact-emargements'),
        ] as $url) {
            $response = $this->get($url);
            $response->assertOk();
            $html = $response->getContent();

            $this->assertMatchesRegularExpression('/<option[^>]*>\s*Filiere Mixte\s*<\/option>/u', $html);
            $this->assertMatchesRegularExpression('/<option[^>]*>\s*Filiere BTS\s*<\/option>/u', $html);
            $this->assertDoesNotMatchRegularExpression('/<option[^>]*>\s*Filiere LMD Pure\s*<\/option>/u', $html);
            $this->assertMatchesRegularExpression('/<option[^>]*>\s*BTS Niveau Visible\s*<\/option>/u', $html);
            $this->assertDoesNotMatchRegularExpression('/<option[^>]*>\s*Licence Niveau Cache\s*<\/option>/u', $html);
        }
    }

    private function authenticateAsAdmin(): User
    {
        Role::findOrCreate('superAdmin', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::withoutEvents(fn () => User::factory()->create());
        $user->assignRole('superAdmin');
        $user->givePermissionTo('module.emploi_temps.access');
        $user->givePermissionTo('admin.access');
        $this->actingAs($user);

        return $user;
    }

    private function seedPlanningFilterFixtures(): void
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2025-2026',
            'is_current' => true,
        ]);

        $btsNiveau = ESBTPNiveauEtude::create([
            'name' => 'BTS Niveau Visible',
            'code' => 'BTS-V',
            'type' => 'BTS',
            'year' => 1,
            'is_active' => true,
        ]);

        $lmdNiveau = ESBTPNiveauEtude::create([
            'name' => 'Licence Niveau Cache',
            'code' => 'LIC-C',
            'type' => 'Licence',
            'year' => 1,
            'is_active' => true,
        ]);

        $pureLmdFiliere = ESBTPFiliere::create(['name' => 'Filiere LMD Pure', 'code' => 'FLP', 'is_active' => true]);
        $mixedFiliere = ESBTPFiliere::create(['name' => 'Filiere Mixte', 'code' => 'FMX', 'is_active' => true]);
        $btsFiliere = ESBTPFiliere::create(['name' => 'Filiere BTS', 'code' => 'FBT', 'is_active' => true]);

        $domaine = ESBTPLMDDomaine::create(['name' => 'Domaine Test', 'code' => 'DOM-T', 'is_active' => true]);
        $mention = ESBTPLMDMention::create(['name' => 'Mention Test', 'code' => 'MEN-T', 'domaine_id' => $domaine->id, 'is_active' => true]);

        ESBTPLMDParcours::create([
            'name' => 'Parcours Pure LMD',
            'code' => 'PAR-LMD',
            'mention_id' => $mention->id,
            'filiere_id' => $pureLmdFiliere->id,
            'is_active' => true,
        ]);

        $mixedParcours = ESBTPLMDParcours::create([
            'name' => 'Parcours Mixte',
            'code' => 'PAR-MIX',
            'mention_id' => $mention->id,
            'filiere_id' => $mixedFiliere->id,
            'is_active' => true,
        ]);

        ESBTPClasse::create([
            'name' => 'Classe BTS Mixte',
            'code' => 'CBM',
            'filiere_id' => $mixedFiliere->id,
            'niveau_etude_id' => $btsNiveau->id,
            'annee_universitaire_id' => $annee->id,
            'is_active' => true,
        ]);

        ESBTPClasse::create([
            'name' => 'Classe BTS Visible',
            'code' => 'CBV',
            'filiere_id' => $btsFiliere->id,
            'niveau_etude_id' => $btsNiveau->id,
            'annee_universitaire_id' => $annee->id,
            'is_active' => true,
        ]);

        ESBTPClasse::create([
            'name' => 'Classe LMD Cachee',
            'code' => 'CLC',
            'filiere_id' => $mixedFiliere->id,
            'niveau_etude_id' => $lmdNiveau->id,
            'annee_universitaire_id' => $annee->id,
            'parcours_id' => $mixedParcours->id,
            'is_active' => true,
        ]);
    }
}
