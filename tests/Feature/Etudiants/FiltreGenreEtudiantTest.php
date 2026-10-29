<?php

namespace Tests\Feature\Etudiants;

use App\Models\ESBTPEtudiant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Le filtre Genre de /esbtp/etudiants.
 *
 * La colonne `sexe` porte « M » / « F » depuis les formulaires, mais les
 * reprises d'anciennes bases y ont laisse des libelles entiers. Les exports
 * comptent deja les deux ecritures : le filtre doit en faire autant, sinon il
 * annonce moins d'eleves que le total affiche juste a cote.
 */
class FiltreGenreEtudiantTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

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
        Permission::findOrCreate('students.view', 'web');
        Cache::flush();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo(['admin.access', 'students.view']);
        $this->actingAs($this->user);
    }

    private function etudiant(string $sexe): ESBTPEtudiant
    {
        return ESBTPEtudiant::factory()->create(['sexe' => $sexe]);
    }

    private function html(?string $sexe): string
    {
        $reponse = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('esbtp.etudiants.index', $sexe === null ? [] : ['sexe' => $sexe]));

        $reponse->assertOk();

        return (string) $reponse->json('html');
    }

    public function test_le_filtre_masculin_ecarte_les_etudiantes(): void
    {
        $garcon = $this->etudiant('M');
        $fille = $this->etudiant('F');

        $html = $this->html('M');

        $this->assertStringContainsString($garcon->matricule, $html);
        $this->assertStringNotContainsString($fille->matricule, $html);
    }

    public function test_le_filtre_feminin_ecarte_les_etudiants(): void
    {
        $garcon = $this->etudiant('M');
        $fille = $this->etudiant('F');

        $html = $this->html('F');

        $this->assertStringContainsString($fille->matricule, $html);
        $this->assertStringNotContainsString($garcon->matricule, $html);
    }

    public function test_les_libelles_entiers_des_anciennes_bases_sont_reconnus(): void
    {
        $ancien = $this->etudiant('Masculin');
        $ancienne = $this->etudiant('Féminin');

        $masculin = $this->html('M');
        $this->assertStringContainsString($ancien->matricule, $masculin);
        $this->assertStringNotContainsString($ancienne->matricule, $masculin);

        $feminin = $this->html('F');
        $this->assertStringContainsString($ancienne->matricule, $feminin);
    }

    public function test_sans_filtre_les_deux_genres_sont_listes(): void
    {
        $garcon = $this->etudiant('M');
        $fille = $this->etudiant('F');

        $html = $this->html(null);

        $this->assertStringContainsString($garcon->matricule, $html);
        $this->assertStringContainsString($fille->matricule, $html);
    }

    public function test_une_valeur_inconnue_est_ignoree_plutot_que_de_vider_la_liste(): void
    {
        // Un parametre bricole dans l'URL ne doit pas faire disparaitre la liste.
        $garcon = $this->etudiant('M');
        $fille = $this->etudiant('F');

        $html = $this->html('Z');

        $this->assertStringContainsString($garcon->matricule, $html);
        $this->assertStringContainsString($fille->matricule, $html);
    }
}
