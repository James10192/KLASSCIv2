<?php

namespace Tests\Feature\AcademicPilotage;

use App\Http\Controllers\AcademicPilotage\AcademicCoverageController;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * L'adresse qui sert la couverture d'une classe.
 *
 * Ce qui compte ici n'est pas le contenu — il est teste ailleurs, sur le
 * service — mais l'ordre des operations : le perimetre est verifie AVANT toute
 * lecture de cache, et une ecriture de note fait oublier ce qui etait en cache.
 */
class CouvertureEndpointTest extends TestCase
{
    use MonteUneClasseBts, RefreshDatabase;

    private User $acteur;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monterLaClasse();

        foreach (['module.academic_pilotage.access', 'academic_health.view', 'academic_pilotage.view_all'] as $nom) {
            Permission::findOrCreate($nom, 'web');
        }

        $superAdmin = Role::findOrCreate('superAdmin', 'web');
        User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()])
            ->assignRole($superAdmin);

        $this->acteur = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        // `view_all` donne le perimetre global : l'acteur voit toutes les classes.
        $this->acteur->givePermissionTo([
            'module.academic_pilotage.access',
            'academic_health.view',
            'academic_pilotage.view_all',
        ]);

        $matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);
        ESBTPMatiereFilierNiveau::create([
            'matiere_id' => $matiere->id,
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
        ]);
    }

    private function demander(?User $acteur = null)
    {
        return $this->actingAs($acteur ?? $this->acteur)->getJson(route(
            'esbtp.pilotage-academique.classes.couverture',
            ['classe' => $this->classe->id, 'annee_universitaire_id' => $this->annee->id, 'periode' => 'semestre1']
        ));
    }

    public function test_la_couverture_est_servie_et_mise_en_cache(): void
    {
        $reponse = $this->demander()->assertOk();

        self::assertTrue($reponse->json('ok'));
        self::assertSame((int) $this->classe->id, $reponse->json('classe.id'));

        // Le second appel doit trouver la reponse en cache.
        self::assertTrue(Cache::has(
            AcademicCoverageController::cle((int) $this->classe->id, (int) $this->annee->id, 'semestre1')
        ));
    }

    public function test_une_classe_hors_perimetre_est_refusee(): void
    {
        $intrus = User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]);
        // Acces au module, mais aucun perimetre global : sa liste de classes
        // autorisees est vide.
        $intrus->givePermissionTo(['module.academic_pilotage.access', 'academic_health.view']);

        $this->demander($intrus)->assertForbidden();
    }

    public function test_le_perimetre_est_verifie_avant_toute_lecture_de_cache(): void
    {
        // Un acteur legitime remplit le cache.
        $this->demander()->assertOk();
        $cle = AcademicCoverageController::cle((int) $this->classe->id, (int) $this->annee->id, 'semestre1');
        self::assertTrue(Cache::has($cle));

        $intrus = User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]);
        $intrus->givePermissionTo(['module.academic_pilotage.access', 'academic_health.view']);

        // Le cache est chaud : si le perimetre etait verifie apres, l'intrus
        // recevrait le contenu mis en cache par quelqu'un d'autre.
        $this->demander($intrus)->assertForbidden();
    }

    public function test_recalculer_force_le_recalcul(): void
    {
        $this->demander()->assertOk();
        $cle = AcademicCoverageController::cle((int) $this->classe->id, (int) $this->annee->id, 'semestre1');
        Cache::put($cle, ['ok' => false, 'message' => 'valeur perimee'], 600);

        $reponse = $this->actingAs($this->acteur)->getJson(route(
            'esbtp.pilotage-academique.classes.couverture',
            [
                'classe' => $this->classe->id,
                'annee_universitaire_id' => $this->annee->id,
                'periode' => 'semestre1',
                'recalculer' => 1,
            ]
        ))->assertOk();

        self::assertTrue($reponse->json('ok'));
    }

    public function test_une_note_enregistree_fait_oublier_le_cache(): void
    {
        $this->demander()->assertOk();
        $cle = AcademicCoverageController::cle((int) $this->classe->id, (int) $this->annee->id, 'semestre1');
        self::assertTrue(Cache::has($cle));

        // Une note qui arrive change ce que la couverture raconte : l'ecran ne
        // doit pas continuer a servir l'etat d'avant.
        $matiere = $this->matiereConfiguree();
        $etudiant = $this->etudiantInscrit();
        $this->noter($etudiant, $this->evaluationDe($matiere));

        self::assertFalse(Cache::has($cle));
    }
}
