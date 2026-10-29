<?php

namespace Tests\Feature\Dashboard;

use App\Helpers\InstallationHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPAttendance;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPTeacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Le tableau de bord étudiant lit l'assiduité dans `esbtp_attendances.statut`.
 *
 * Avant : il lisait `status`, colonne fantôme à 'present' par défaut sur chaque
 * ligne, et affichait 100 % de présence à tout le monde.
 */
class EtudiantAssiduiteTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPAnneeUniversitaire $annee;

    private ESBTPClasse $classe;

    private ESBTPMatiere $matiere;

    private ESBTPTeacher $teacher;

    private int $compteurSeances = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('identity.student', 'web');
        Role::findOrCreate('superAdmin', 'web');
        // Le garde « installed » du groupe de routes exige qu'un superAdmin existe.
        User::factory()->create()->assignRole('superAdmin');
        InstallationHelper::flushCachedStatus();

        $this->annee = ESBTPAnneeUniversitaire::factory()->create([
            'is_current' => true,
            'start_date' => '2026-01-01',
            'end_date' => '2026-08-31',
        ]);
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $filiere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => null]);
        $this->classe = ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $this->annee->id,
        ]);
        $this->matiere = ESBTPMatiere::factory()->create();
        $this->teacher = ESBTPTeacher::create([
            'user_id' => User::factory()->create()->id,
            'matricule' => 'ENS-ASSIDUITE',
            'status' => 'active',
        ]);
    }

    public function test_le_taux_compte_le_retard_comme_une_presence_et_ne_lit_que_l_annee_courante(): void
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $user->givePermissionTo('identity.student');
        $etudiant = ESBTPEtudiant::factory()->create(['user_id' => $user->id]);
        ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'status' => 'active',
        ]);

        // Quatre appels finaux sur l'année courante : 2 présents, 1 retard, 1 absent.
        $premiereSeance = $this->seance($this->annee, '2026-02-02');
        $this->presence($etudiant, $this->annee, $premiereSeance, 'present');
        $this->presence($etudiant, $this->annee, $this->seance($this->annee, '2026-02-03'), 'present');
        $this->presence($etudiant, $this->annee, $this->seance($this->annee, '2026-02-04'), 'retard');
        $this->presence($etudiant, $this->annee, $this->seance($this->annee, '2026-02-05'), 'absent');

        // L'appel de début de la première séance, supplanté par la fusion : ne compte pas deux fois.
        $this->presence($etudiant, $this->annee, $premiereSeance, 'absent', 'start');

        // Une absence de l'année passée ne pèse pas sur l'année en cours.
        $anneePassee = ESBTPAnneeUniversitaire::factory()->create([
            'is_current' => false,
            'start_date' => '2025-01-01',
            'end_date' => '2025-08-31',
        ]);
        $this->presence($etudiant, $anneePassee, $this->seance($anneePassee, '2025-02-10'), 'absent');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $stats = $response->viewData('attendanceStats');

        $this->assertSame(4, $stats['total']);
        $this->assertSame(2, $stats['present']);
        $this->assertSame(1, $stats['retard']);
        $this->assertSame(1, $stats['absent']);
        $this->assertSame(0, $stats['excuse']);
        // (2 présents + 1 retard) / 4 appels : un retard est une présence.
        $this->assertSame(75.0, $stats['rate']);
    }

    public function test_sans_aucun_appel_le_taux_est_absent_et_non_zero(): void
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $user->givePermissionTo('identity.student');
        ESBTPEtudiant::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $stats = $response->viewData('attendanceStats');

        $this->assertSame(0, $stats['total']);
        $this->assertNull($stats['rate']);
    }

    private function seance(ESBTPAnneeUniversitaire $annee, string $dateSeance): ESBTPSeanceCours
    {
        $this->compteurSeances++;

        $emploiTemps = ESBTPEmploiTemps::create([
            'titre' => 'Planning '.$this->compteurSeances,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $annee->id,
            'semestre' => 'semestre1',
            'date_debut' => $annee->start_date,
            'date_fin' => $annee->end_date,
            'is_active' => true,
            'is_current' => true,
        ]);

        return ESBTPSeanceCours::create([
            'emploi_temps_id' => $emploiTemps->id,
            'classe_id' => $this->classe->id,
            'matiere_id' => $this->matiere->id,
            'teacher_id' => $this->teacher->id,
            'jour' => 'lundi',
            'heure_debut' => '08:00:00',
            'heure_fin' => '10:00:00',
            'annee_universitaire_id' => $annee->id,
            'date_seance' => $dateSeance,
            'type' => ESBTPSeanceCours::TYPE_COURSE,
            'type_seance' => 'cours',
            'is_active' => true,
        ]);
    }

    private function presence(
        ESBTPEtudiant $etudiant,
        ESBTPAnneeUniversitaire $annee,
        ESBTPSeanceCours $seance,
        string $statut,
        string $callType = 'merged'
    ): ESBTPAttendance {
        return ESBTPAttendance::create([
            'seance_cours_id' => $seance->id,
            'etudiant_id' => $etudiant->id,
            'classe_id' => $this->classe->id,
            'matiere_id' => $this->matiere->id,
            'teacher_id' => $this->teacher->id,
            'annee_universitaire_id' => $annee->id,
            'date' => $seance->date_seance,
            'heure_debut' => '08:00:00',
            'heure_fin' => '10:00:00',
            'statut' => $statut,
            'call_type' => $callType,
        ]);
    }
}
