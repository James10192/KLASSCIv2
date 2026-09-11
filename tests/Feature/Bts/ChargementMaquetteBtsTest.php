<?php

namespace Tests\Feature\Bts;

use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Le chargement d'une maquette depuis le bulletin officiel d'une ecole.
 *
 * Ce que ces tests tiennent, et qui a motive l'endpoint : l'ecran
 * Classification ne sait qu'ECRASER une liaison existante. Sur Abidjan, la
 * 2e annee n'en avait quasiment aucune — poser un ordre n'y touchait rien.
 */
class ChargementMaquetteBtsTest extends TestCase
{
    use DatabaseTransactions;

    private ESBTPFiliere $filiere;

    private ESBTPNiveauEtude $niveau;

    protected function setUp(): void
    {
        parent::setUp();

        // Sans superAdmin, le middleware « installed » renvoie toute requete
        // vers l'installateur et l'endpoint ne s'execute jamais.
        $admin = User::factory()->create();
        \Spatie\Permission\Models\Role::findOrCreate('superAdmin', 'web');
        $admin->assignRole('superAdmin');

        Sanctum::actingAs(User::factory()->create(), ['cli:admin']);

        $this->filiere = ESBTPFiliere::create([
            'name' => 'Geometre Topographe '.uniqid(),
            'code' => 'GT'.substr(uniqid(), -6),
            'is_active' => true,
        ]);
        $this->niveau = ESBTPNiveauEtude::create([
            'name' => 'Deuxieme Annee BTS '.uniqid(),
            'code' => 'N2'.substr(uniqid(), -6),
            'type' => 'BTS',
            'year' => 2,
            'is_active' => true,
        ]);
    }

    public function test_la_liaison_absente_est_creee_avec_sa_place_et_son_semestre(): void
    {
        $geodesie = $this->matiere('Geodesie '.uniqid());
        $cartographie = $this->matiere('Cartographie '.uniqid());

        $this->assertSame(0, $this->liaisons()->count(), 'Le couple part bien sans aucune liaison.');

        $reponse = $this->postJson('/api/cli/bts/maquette', [
            'filiere' => $this->filiere->id,
            'niveau' => $this->niveau->id,
            'semestre' => 2,
            'appliquer' => true,
            'valider' => true,
            'matieres' => [$geodesie->name, $cartographie->name],
        ]);

        $reponse->assertOk()->assertJsonPath('data.liaisons_a_creer', 2);

        $this->assertDatabaseHas('esbtp_matiere_filiere_niveau', [
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
            'matiere_id' => $geodesie->id,
            'ordre_bulletin' => 1,
            'semestre' => 2,
            'semestre_renseigne' => true,
        ]);
        $this->assertDatabaseHas('esbtp_matiere_filiere_niveau', [
            'matiere_id' => $cartographie->id,
            'ordre_bulletin' => 2,
            'semestre' => 2,
        ]);
    }

    public function test_la_simulation_n_ecrit_rien(): void
    {
        $matiere = $this->matiere('Topometrie Generale '.uniqid());

        $this->postJson('/api/cli/bts/maquette', [
            'filiere' => $this->filiere->id,
            'niveau' => $this->niveau->id,
            'semestre' => 1,
            'matieres' => [$matiere->name],
        ])->assertOk()->assertJsonPath('data.ecrit', false);

        $this->assertSame(0, $this->liaisons()->count(), 'Sans appliquer=true, la base ne doit pas bouger.');
    }

    public function test_un_libelle_ambigu_fait_echouer_tout_le_chargement(): void
    {
        $nom = 'Hydraulique Appliquee '.uniqid();
        $this->matiere($nom);
        $this->matiere($nom); // le meme libelle deux fois : le cas reel d'Abidjan
        $propre = $this->matiere('Genie Rural '.uniqid());

        $reponse = $this->postJson('/api/cli/bts/maquette', [
            'filiere' => $this->filiere->id,
            'niveau' => $this->niveau->id,
            'semestre' => 2,
            'appliquer' => true,
            'matieres' => [$propre->name, $nom],
        ]);

        $reponse->assertStatus(422);
        $this->assertCount(1, $reponse->json('data.ambigus'));
        $this->assertCount(2, $reponse->json('data.ambigus.0.candidats'));

        // Meme la matiere sans ambiguite reste dehors : une maquette a moitie
        // posee serait declaree « renseignee » sur un bulletin incomplet.
        $this->assertSame(0, $this->liaisons()->count());
    }

    public function test_un_libelle_ambigu_est_tranche_par_la_liaison_deja_posee(): void
    {
        $nom = 'Anglais technique '.uniqid();
        $premier = $this->matiere($nom);
        $this->matiere($nom);

        ESBTPMatiereFilierNiveau::create([
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
            'matiere_id' => $premier->id,
        ]);

        $this->postJson('/api/cli/bts/maquette', [
            'filiere' => $this->filiere->id,
            'niveau' => $this->niveau->id,
            'semestre' => 1,
            'appliquer' => true,
            'matieres' => [$nom],
        ])->assertOk()->assertJsonPath('data.liaisons_existantes', 1);

        $this->assertDatabaseHas('esbtp_matiere_filiere_niveau', [
            'matiere_id' => $premier->id,
            'ordre_bulletin' => 1,
            'semestre' => 1,
        ]);
    }

    public function test_le_chargement_se_rejoue_sans_doubler_les_liaisons(): void
    {
        $matiere = $this->matiere('Photogrammetrie '.uniqid());
        $charge = fn () => $this->postJson('/api/cli/bts/maquette', [
            'filiere' => $this->filiere->id,
            'niveau' => $this->niveau->id,
            'semestre' => 2,
            'appliquer' => true,
            'matieres' => [$matiere->name],
        ]);

        $charge()->assertOk();
        $charge()->assertOk()->assertJsonPath('data.liaisons_existantes', 1);

        $this->assertSame(1, $this->liaisons()->count(), 'Rejouer le chargement ne doit pas creer une seconde liaison.');
    }

    private function liaisons()
    {
        return ESBTPMatiereFilierNiveau::query()
            ->where('filiere_id', $this->filiere->id)
            ->where('niveau_etude_id', $this->niveau->id);
    }

    private function matiere(string $nom): ESBTPMatiere
    {
        return ESBTPMatiere::create([
            'name' => $nom,
            'code' => strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $nom), 0, 10)).substr(uniqid(), -4),
            'is_active' => true,
        ]);
    }
}
