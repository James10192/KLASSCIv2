<?php

namespace Tests\Feature\Care;

use App\Domain\Support\Services\ResoudreDestinataireIntervention as Resolver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ResoudreDestinataireInterventionTest extends TestCase
{
    use RefreshDatabase;

    private function agent(bool $actif = true): User
    {
        return User::factory()->create(['is_active' => $actif]);
    }

    private function autoriser(User $user, string $permission = 'inscriptions.validate'): void
    {
        $user->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
    }

    public function test_un_agent_actif_est_retenu_uniquement_avec_sa_permission_effective(): void
    {
        $agent = $this->agent();
        $this->autoriser($agent);

        $resultat = app(Resolver::class)->executer('inscriptions.validate');

        $this->assertSame(Resolver::RESOLU, $resultat['etat']);
        $this->assertSame($agent->id, $resultat['utilisateur']->id);
    }

    public function test_une_cible_non_autorisee_ne_declenche_pas_un_autre_destinataire(): void
    {
        $agent = $this->agent();
        $autre = $this->agent();
        $this->autoriser($agent);

        $resultat = app(Resolver::class)->executer('inscriptions.validate', $autre->id);

        $this->assertSame(Resolver::INTROUVABLE, $resultat['etat']);
        $this->assertNull($resultat['utilisateur']);
    }

    public function test_plusieurs_candidats_ne_provoquent_pas_une_diffusion_de_masse(): void
    {
        $a = $this->agent();
        $b = $this->agent();
        $this->autoriser($a);
        $this->autoriser($b);

        $resultat = app(Resolver::class)->executer('inscriptions.validate');

        $this->assertSame(Resolver::AMBIGU, $resultat['etat']);
        $this->assertNull($resultat['utilisateur']);
    }

    public function test_le_compte_inactif_et_permission_inconnue_sont_refuses(): void
    {
        $inactif = $this->agent(false);
        $this->autoriser($inactif);

        $resolver = app(Resolver::class);
        $this->assertSame(Resolver::INTROUVABLE, $resolver->executer('inscriptions.validate')['etat']);
        $this->assertSame(Resolver::INTROUVABLE, $resolver->executer('*.all')['etat']);
        $this->assertSame(Resolver::INTROUVABLE, $resolver->executer('undefined.permission')['etat']);
    }

    public function test_la_permission_retiree_est_refusee_immediatement(): void
    {
        $agent = $this->agent();
        $this->autoriser($agent);

        $agent->revokePermissionTo('inscriptions.validate');

        $resultat = app(Resolver::class)->executer('inscriptions.validate', $agent->id);

        $this->assertSame(Resolver::INTROUVABLE, $resultat['etat']);
    }
}
