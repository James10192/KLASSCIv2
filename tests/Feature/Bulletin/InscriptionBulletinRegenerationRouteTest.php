<?php

namespace Tests\Feature\Bulletin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

class InscriptionBulletinRegenerationRouteTest extends TestCase
{
    use RefreshDatabase, MonteUneClasseBts;

    public function test_une_periode_annuelle_bts_est_refusee_avant_toute_generation(): void
    {
        $this->monterLaClasse();
        $inscription = $this->etudiantInscrit()->inscriptions()->firstOrFail();

        $this->actingAs($this->superAdmin())
            ->postJson(route('esbtp.bulletins.regenerer-inscription', $inscription), ['periode' => 'annuel'])
            ->assertUnprocessable();
    }

    public function test_le_semestre_lmd_ne_peut_pas_etre_applique_a_une_inscription_bts(): void
    {
        $this->monterLaClasse();
        $inscription = $this->etudiantInscrit()->inscriptions()->firstOrFail();

        $this->actingAs($this->superAdmin())
            ->postJson(route('esbtp.bulletins.regenerer-inscription', $inscription), ['periode' => '3'])
            ->assertUnprocessable();
    }

    private function superAdmin(): User
    {
        Role::findOrCreate('superAdmin', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::withoutEvents(fn () => User::factory()->create());
        $user->assignRole('superAdmin');

        return $user;
    }
}
