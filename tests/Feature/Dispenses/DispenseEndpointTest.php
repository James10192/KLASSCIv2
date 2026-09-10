<?php

namespace Tests\Feature\Dispenses;

use App\Domain\Dispenses\Models\ESBTPDispense;
use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * Les adresses qui accordent et revoquent une dispense.
 *
 * Ce qui compte ici : qui a le droit, ce qui est refuse, et que le LMD n'y
 * touche pas.
 */
class DispenseEndpointTest extends TestCase
{
    use MonteUneClasseBts;
    use RefreshDatabase;

    private User $gestionnaire;

    private User $lecteur;

    private $etudiant;

    private ESBTPMatiere $matiere;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monterLaClasse();

        foreach (['dispenses.view', 'dispenses.manage'] as $nom) {
            Permission::findOrCreate($nom, 'web');
        }

        // Le garde « installed » renvoie vers l'assistant d'installation tant
        // qu'aucun superAdmin n'existe : sans lui, toutes les adresses
        // repondraient 302 et le test ne prouverait rien.
        $superAdmin = Role::findOrCreate('superAdmin', 'web');
        User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()])
            ->assignRole($superAdmin);

        $this->gestionnaire = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $this->gestionnaire->givePermissionTo(['dispenses.view', 'dispenses.manage']);

        $this->lecteur = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $this->lecteur->givePermissionTo(['dispenses.view']);

        $this->etudiant = $this->etudiantInscrit();
        $this->matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);
        ESBTPMatiereFilierNiveau::create([
            'matiere_id' => $this->matiere->id,
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
        ]);
    }

    /** @param array<string, mixed> $remplace */
    private function accorder(User $acteur, array $remplace = [])
    {
        return $this->actingAs($acteur)->postJson(
            route('esbtp.etudiants.dispenses.store', $this->etudiant),
            array_merge([
                'matiere_id' => $this->matiere->id,
                'annee_universitaire_id' => $this->annee->id,
                'periode' => 'annuel',
                'motif' => 'Validée lors du parcours antérieur',
            ], $remplace)
        );
    }

    public function test_le_catalogue_liste_les_matieres_du_bulletin(): void
    {
        $reponse = $this->actingAs($this->lecteur)->getJson(route(
            'esbtp.etudiants.dispenses.index',
            ['etudiant' => $this->etudiant->id, 'annee_universitaire_id' => $this->annee->id]
        ));

        $reponse->assertOk()
            ->assertJsonPath('dispenses', [])
            ->assertJsonPath('lmd', false)
            ->assertJsonPath('matieres.0.value', (int) $this->matiere->id);
    }

    public function test_une_dispense_accordee_est_enregistree(): void
    {
        $this->accorder($this->gestionnaire)
            ->assertCreated()
            ->assertJsonPath('dispense.portee', 'Année complète')
            ->assertJsonPath('dispense.active', true);

        $this->assertDatabaseHas('esbtp_dispenses', [
            'etudiant_id' => $this->etudiant->id,
            'matiere_id' => $this->matiere->id,
            'motif' => 'Validée lors du parcours antérieur',
            'revoquee_le' => null,
        ]);
    }

    public function test_un_lecteur_ne_peut_pas_accorder(): void
    {
        $this->accorder($this->lecteur)->assertForbidden();

        $this->assertSame(0, ESBTPDispense::query()->count());
    }

    /** Un motif d'un mot ne se defend pas six mois plus tard. */
    public function test_un_motif_trop_court_est_refuse(): void
    {
        $this->accorder($this->gestionnaire, ['motif' => 'ok'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('motif');

        $this->assertSame(0, ESBTPDispense::query()->count());
    }

    public function test_un_doublon_actif_est_refuse_avec_un_message_lisible(): void
    {
        $this->accorder($this->gestionnaire)->assertCreated();

        $reponse = $this->accorder($this->gestionnaire, ['motif' => 'Second motif pour la même matière']);

        $reponse->assertStatus(422);
        $this->assertStringContainsString('couvre déjà', $reponse->json('message'));
        $this->assertSame(1, ESBTPDispense::query()->count());
    }

    /**
     * Les dispenses sont BTS uniquement : le LMD attend la validation des
     * regles de jury.
     */
    public function test_une_classe_lmd_est_refusee(): void
    {
        // La classe derive son systeme de son niveau (hook `saving`) : ecrire
        // « LMD » en dur serait ecrase par « BTS ». C'est le niveau Licence qui
        // en fait une classe LMD, comme en production.
        $licence = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'Licence']);

        $classeLmd = ESBTPClasse::factory()->create([
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $licence->id,
            'annee_universitaire_id' => $this->annee->id,
        ]);

        $this->assertSame('LMD', $classeLmd->fresh()->systeme_academique);

        $etudiantLmd = $this->etudiantInscrit();
        ESBTPInscription::query()
            ->where('etudiant_id', $etudiantLmd->id)
            ->update(['classe_id' => $classeLmd->id]);

        $reponse = $this->actingAs($this->gestionnaire)->postJson(
            route('esbtp.etudiants.dispenses.store', $etudiantLmd),
            [
                'matiere_id' => $this->matiere->id,
                'annee_universitaire_id' => $this->annee->id,
                'periode' => 'annuel',
                'motif' => 'Validée lors du parcours antérieur',
            ]
        );

        $reponse->assertStatus(422);
        $this->assertStringContainsString('LMD', $reponse->json('message'));
        $this->assertSame(0, ESBTPDispense::query()->count());
    }

    public function test_la_revocation_exige_un_motif_et_conserve_la_ligne(): void
    {
        $this->accorder($this->gestionnaire)->assertCreated();
        $dispense = ESBTPDispense::query()->firstOrFail();

        $url = route('esbtp.dispenses.revoquer', $dispense);

        $this->actingAs($this->gestionnaire)->patchJson($url, ['motif' => 'non'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('motif');

        $this->actingAs($this->gestionnaire)->patchJson($url, ['motif' => 'Accordée par erreur, à corriger'])
            ->assertOk()
            ->assertJsonPath('dispense.active', false);

        $this->assertDatabaseHas('esbtp_dispenses', [
            'id' => $dispense->id,
            'motif_revocation' => 'Accordée par erreur, à corriger',
        ]);
    }
}
