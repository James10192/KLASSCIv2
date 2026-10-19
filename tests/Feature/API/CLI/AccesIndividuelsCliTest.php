<?php

namespace Tests\Feature\API\CLI;

use App\Models\TemporaryPermissionGrant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AccesIndividuelsCliTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'notes.edit',
            'permissions.temporaires.manage',
            'permissions.individuels.permanents.manage',
            'personnel.manage',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate('superAdmin', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function auteur(array $abilities = ['cli:read', 'cli:admin'], bool $superAdmin = true): User
    {
        $auteur = User::factory()->create();
        if ($superAdmin) {
            $auteur->assignRole('superAdmin');
        }

        Sanctum::actingAs($auteur, $abilities);

        return $auteur;
    }

    public function test_cli_definitif_ne_s_appuie_pas_sur_une_date_de_fin_implicite(): void
    {
        $auteur = $this->auteur();
        $cible = User::factory()->create();

        $this->postJson("/api/cli/user/{$cible->id}/acces-temporaires", [
            'permission' => 'notes.edit',
            'mode' => 'definitif',
            'motif' => 'Délégation nominative sans date limite',
        ])->assertOk()
            ->assertJsonPath('data.mode', 'definitif')
            ->assertJsonPath('data.fin', null)
            ->assertJsonPath('data.statut', 'definitif')
            ->assertJsonPath('data.accorde_par', $auteur->id);

        $grant = TemporaryPermissionGrant::where('user_id', $cible->id)->firstOrFail();
        $this->assertTrue($grant->is_permanent);

        $this->getJson("/api/cli/user/{$cible->id}/acces-temporaires")
            ->assertOk()
            ->assertJsonPath('data.acces.0.mode', 'definitif');

        $this->postJson("/api/cli/acces-temporaires/{$grant->id}/retirer")
            ->assertOk()->assertJsonPath('data.statut', 'retiree');
        $this->assertSame($auteur->id, $grant->fresh()->revoked_by);
    }

    public function test_cli_admin_sans_habilitation_permanente_ne_peut_pas_creer_ni_retirer_un_definitif(): void
    {
        $admin = $this->auteur();
        $cible = User::factory()->create();
        $grant = TemporaryPermissionGrant::create([
            'user_id' => $cible->id,
            'permission' => 'notes.edit',
            'starts_at' => now(),
            'expires_at' => null,
            'is_permanent' => true,
            'motif' => 'Accès permanent existant du service',
            'granted_by' => $admin->id,
        ]);

        $simple = $this->auteur(['cli:read', 'cli:admin'], false);
        $simple->givePermissionTo(['permissions.temporaires.manage', 'personnel.manage', 'notes.edit']);

        $this->postJson("/api/cli/user/{$cible->id}/acces-temporaires", [
            'permission' => 'notes.edit',
            'mode' => 'definitif',
            'motif' => 'Tentative sans habilitation permanente',
        ])->assertUnprocessable()->assertJsonPath('errors.code', 'ACCES_REFUSE');

        $this->postJson("/api/cli/acces-temporaires/{$grant->id}/retirer")
            ->assertForbidden()->assertJsonPath('errors.code', 'ACCES_REFUSE');
        $this->assertNull($grant->fresh()->revoked_at);
    }

    public function test_cli_definitif_refuse_les_parametres_de_duree_meme_pour_superadmin(): void
    {
        $this->auteur();
        $cible = User::factory()->create();

        foreach (['fin' => now()->addDay()->toIso8601String(), 'duree_heures' => 24, 'debut' => now()->toIso8601String()] as $champ => $valeur) {
            $this->postJson("/api/cli/user/{$cible->id}/acces-temporaires", [
                'permission' => 'notes.edit',
                'mode' => 'definitif',
                $champ => $valeur,
                'motif' => 'Accès permanent avec durée abusive',
            ])->assertUnprocessable()->assertJsonValidationErrors($champ);
        }

        $this->assertSame(0, TemporaryPermissionGrant::where('user_id', $cible->id)->count());
    }

    public function test_cli_ancien_client_sans_mode_conserve_un_acces_temporaire(): void
    {
        $this->auteur();
        $cible = User::factory()->create();

        $this->postJson("/api/cli/user/{$cible->id}/acces-temporaires", [
            'permission' => 'notes.edit',
            'duree_heures' => 24,
            'motif' => 'Correction temporaire de notes du semestre',
        ])->assertOk()
            ->assertJsonPath('data.mode', 'temporaire')
            ->assertJsonPath('data.statut', 'active');

        $grant = TemporaryPermissionGrant::where('user_id', $cible->id)->firstOrFail();
        $this->assertFalse($grant->is_permanent);
        $this->assertNotNull($grant->expires_at);
    }

    public function test_cli_jeton_en_lecture_seule_ne_peut_pas_accorder_un_acces(): void
    {
        $this->auteur(['cli:read']);
        $cible = User::factory()->create();

        $this->postJson("/api/cli/user/{$cible->id}/acces-temporaires", [
            'permission' => 'notes.edit',
            'mode' => 'definitif',
            'motif' => 'Tentative interdite en lecture seule',
        ])->assertForbidden();

        $this->assertSame(0, TemporaryPermissionGrant::where('user_id', $cible->id)->count());
    }
}
