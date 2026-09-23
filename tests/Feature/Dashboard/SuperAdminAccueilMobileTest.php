<?php

namespace Tests\Feature\Dashboard;

use App\Helpers\InstallationHelper;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Models\User;
use App\Services\Mobile\MobileProfileResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * L'accueil mobile du superAdmin portait quatre compteurs et une liste.
 * Le bureau, lui, montre aussi filières, matières, l'évolution des
 * inscriptions, la répartition par filière et les actions rapides.
 */
class SuperAdminAccueilMobileTest extends TestCase
{
    use DatabaseTransactions;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('superAdmin', 'web');
        $this->superAdmin = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $this->superAdmin->assignRole('superAdmin');
        InstallationHelper::flushCachedStatus();
        SettingsHelper::set(MobileProfileResolver::REGLAGE_ACTIF, '1');
        MobileProfileResolver::oublier();
    }

    protected function tearDown(): void
    {
        MobileProfileResolver::oublier();
        parent::tearDown();
    }

    public function test_l_accueil_mobile_reprend_les_sections_du_bureau(): void
    {
        $classe = ESBTPClasse::factory()->create();
        ESBTPAnneeUniversitaire::query()->update(['is_current' => false]);
        ESBTPAnneeUniversitaire::whereKey($classe->annee_universitaire_id)->update(['is_current' => true]);
        ESBTPInscription::factory()->create([
            'classe_id' => $classe->id,
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'annee_universitaire_id' => $classe->annee_universitaire_id,
        ]);

        $this->actingAs($this->superAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('m-only-mobile', false)
            ->assertSee('Matières')
            ->assertSee('Inscriptions sur 12 mois')
            ->assertSee('sam-legende', false)
            ->assertSee('Répartition par filière')
            ->assertSee('class="sam-fil"', false)
            ->assertSee('Actions rapides')
            ->assertSee(route('esbtp.resultats.index'), false);
    }

    public function test_shell_coupe_aucune_section_mobile(): void
    {
        SettingsHelper::set(MobileProfileResolver::REGLAGE_ACTIF, '0');
        MobileProfileResolver::oublier();

        $this->actingAs($this->superAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Inscriptions sur 12 mois')
            ->assertDontSee('class="sam-fil"', false);
    }
}
