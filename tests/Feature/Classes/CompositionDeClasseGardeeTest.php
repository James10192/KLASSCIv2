<?php

namespace Tests\Feature\Classes;

use App\Models\ESBTPClasse;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Modifier la composition d'une classe demande un droit.
 *
 * Cinq routes d'ecriture ne gardaient que « etre connecte » : changer les
 * matieres d'une classe, y ajouter ou en retirer des eleves, rejouer la
 * synchronisation du systeme academique. Aucune permission n'etait exigee, et
 * les FormRequest correspondantes rendaient `authorize(): true` — donc rien ne
 * rattrapait en aval. N'importe quel compte authentifie, un etudiant compris,
 * pouvait retirer une promotion entiere de sa classe.
 *
 * Ces cas tiennent la garde des deux cotes : la route, et la requete.
 */
class CompositionDeClasseGardeeTest extends TestCase
{
    use DatabaseTransactions;

    private ESBTPClasse $classe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        Permission::findOrCreate('classes.edit', 'web');
        Permission::findOrCreate('classes.view', 'web');
        Permission::findOrCreate('students.edit', 'web');
        Role::findOrCreate('etudiant', 'web');
        Cache::flush();

        $this->classe = ESBTPClasse::factory()->create();
    }

    private function sansDroit(): User
    {
        // Le cas qui compte : un compte reel de l'application, pas un utilisateur
        // vide. Un etudiant est authentifie comme un autre.
        $user = User::factory()->create();
        $user->assignRole('etudiant');

        return $user;
    }

    /**
     * Le droit sur la STRUCTURE de la classe : ses matieres, sa synchronisation.
     */
    private function avecDroitStructure(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('classes.edit');

        return $user;
    }

    /**
     * Le droit sur les ELEVES : les placer, les retirer.
     *
     * Deux droits distincts, et c'est le coeur de ce que ces cas defendent.
     * Chez nous seule la secretaire a `classes.edit`, alors que le coordinateur
     * — qui a `students.edit` — se sert tous les jours des boutons « Ajouter »
     * et « Retirer » de la fiche de classe. Confondre les deux fermerait au
     * coordinateur une fonction dont il a besoin, ou rouvrirait a l'enseignant
     * une fonction qu'il ne doit pas avoir.
     */
    private function avecDroitEleves(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('students.edit');

        return $user;
    }

    public function test_un_etudiant_ne_peut_pas_retirer_des_eleves_d_une_classe(): void
    {
        $this->actingAs($this->sansDroit())
            ->postJson(route('esbtp.classes.remove-students', $this->classe), [
                'etudiant_ids' => [1],
            ])
            ->assertForbidden();
    }

    public function test_un_etudiant_ne_peut_pas_ajouter_d_eleves_a_une_classe(): void
    {
        $this->actingAs($this->sansDroit())
            ->postJson(route('esbtp.classes.add-students', $this->classe), [
                'etudiant_ids' => [1],
            ])
            ->assertForbidden();
    }

    public function test_un_etudiant_ne_peut_pas_changer_les_matieres_d_une_classe(): void
    {
        $this->actingAs($this->sansDroit())
            ->postJson(route('esbtp.classes.update-matieres', $this->classe), [
                'matieres' => [],
            ])
            ->assertForbidden();
    }

    public function test_un_etudiant_ne_peut_pas_rejouer_la_synchronisation_academique(): void
    {
        // Celle-ci n'a pas de classe en parametre : elle agit sur l'ensemble.
        $this->actingAs($this->sansDroit())
            ->postJson(route('esbtp.classes.sync-systeme-academique'))
            ->assertForbidden();
    }

    public function test_le_droit_sur_les_eleves_ouvre_la_composition(): void
    {
        // Sans ce cas, les quatre precedents passeraient tout aussi bien si la
        // route etait cassee pour tout le monde.
        $reponse = $this->actingAs($this->avecDroitEleves())
            ->postJson(route('esbtp.classes.remove-students', $this->classe), [
                'etudiant_ids' => [],
            ]);

        $this->assertNotSame(403, $reponse->status(), 'Le droit students.edit doit ouvrir la composition.');
    }

    public function test_le_droit_sur_la_structure_ouvre_la_synchronisation(): void
    {
        $reponse = $this->actingAs($this->avecDroitStructure())
            ->postJson(route('esbtp.classes.sync-systeme-academique'));

        $this->assertNotSame(403, $reponse->status(), 'Le droit classes.edit doit ouvrir la synchronisation.');
    }

    public function test_le_droit_sur_la_structure_n_ouvre_pas_la_composition(): void
    {
        // L'inverse compte autant : les deux droits ne se remplacent pas l'un
        // l'autre, sinon la separation posee plus haut ne serait qu'un nom.
        $this->actingAs($this->avecDroitStructure())
            ->postJson(route('esbtp.classes.remove-students', $this->classe), [
                'etudiant_ids' => [1],
            ])
            ->assertForbidden();
    }
}
