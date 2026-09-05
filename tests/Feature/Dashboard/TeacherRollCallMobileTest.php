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
use App\Models\ESBTPSessionWorkflow;
use App\Models\ESBTPTeacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Appel mobile de l'enseignant (shell mobile, profil « enseignant »).
 *
 * La page rend un DOM mobile (m-only-mobile) à côté du DOM de bureau, et
 * `storeRollCall` répond en JSON {success, message, counts, redirect} quand
 * le client le demande (fetch), tout en gardant la redirection pour le
 * formulaire classique. Le contrat des valeurs (present|late|absent) ne change pas.
 */
class TeacherRollCallMobileTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPAnneeUniversitaire $annee;

    private ESBTPMatiere $matiere;

    private ESBTPTeacher $teacher;

    private User $enseignant;

    private ESBTPClasse $classe;

    /** @var ESBTPEtudiant[] */
    private array $etudiants = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin.access', 'dashboard.view', 'identity.teach', 'attendances.create', 'attendances.edit', 'attendances.view'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        Role::findOrCreate('superAdmin', 'web');
        Role::findOrCreate('enseignant', 'web');
        // Le garde « installed » du groupe de routes exige qu'un superAdmin existe.
        User::factory()->create()->assignRole('superAdmin');
        InstallationHelper::flushCachedStatus();

        Carbon::setTestNow(Carbon::parse('2026-02-04 09:00:00'));

        $this->annee = ESBTPAnneeUniversitaire::factory()->create([
            'is_current' => true,
            'start_date' => '2026-01-01',
            'end_date' => '2026-08-31',
        ]);
        $this->matiere = ESBTPMatiere::factory()->create(['name' => 'Droit des obligations', 'code' => 'DRO201']);

        $this->enseignant = User::factory()->create([
            'name' => 'Dr Kouamé Yves',
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $this->enseignant->assignRole('enseignant');
        $this->enseignant->givePermissionTo(['admin.access', 'dashboard.view', 'identity.teach', 'attendances.create', 'attendances.edit', 'attendances.view']);
        $this->teacher = ESBTPTeacher::create([
            'user_id' => $this->enseignant->id,
            'matricule' => 'ENS-APPEL',
            'status' => 'active',
        ]);

        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 2, 'type' => 'BTS']);
        $filiere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => null]);
        $this->classe = ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $this->annee->id,
            'systeme_academique' => 'BTS',
        ]);

        foreach ([['Bamba', 'Aïcha'], ['Coulibaly', 'Rokia'], ['Koffi', 'Jean']] as [$nom, $prenoms]) {
            $etudiant = ESBTPEtudiant::factory()->create([
                'user_id' => User::factory()->create()->id,
                'nom' => $nom,
                'prenoms' => $prenoms,
            ]);
            ESBTPInscription::factory()->create([
                'etudiant_id' => $etudiant->id,
                'classe_id' => $this->classe->id,
                'annee_universitaire_id' => $this->annee->id,
                'status' => 'active',
            ]);
            $this->etudiants[] = $etudiant;
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_la_page_rend_le_dom_mobile_a_cote_du_dom_de_bureau(): void
    {
        $seance = $this->seance();
        $this->workflowPret($seance);

        $response = $this->actingAs($this->enseignant)->get(route('teacher.roll-call', $seance->id).'?type=start');

        $response->assertOk();
        $response->assertSee('m-only-desktop', false);
        $response->assertSee('m-only-mobile m-screen trm-screen', false);
        $response->assertSee('function trmAppel()', false);
        // Sous-titre : matière · classe · heure, et le bouton gardé par la permission.
        $response->assertSee('Droit des obligations', false);
        $response->assertSee('id="trmForm"', false);
        $response->assertSee('form="trmForm"', false);
        // Trois étudiants avec leur statut initial « present ».
        $response->assertSee('Bamba Aïcha', false);
        $response->assertSee('"initiales":"BA"', false);
    }

    public function test_sans_permission_le_bouton_enregistrer_est_absent(): void
    {
        $seance = $this->seance();
        $this->workflowPret($seance);
        $this->enseignant->revokePermissionTo(['attendances.create', 'attendances.edit']);

        $response = $this->actingAs($this->enseignant)->get(route('teacher.roll-call', $seance->id).'?type=start');

        $response->assertOk();
        $response->assertDontSee('form="trmForm"', false);
    }

    public function test_l_envoi_en_json_repond_les_compteurs_et_la_redirection(): void
    {
        $seance = $this->seance();
        $this->workflowPret($seance);
        [$a, $b, $c] = $this->etudiants;

        $response = $this->actingAs($this->enseignant)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('teacher.roll-call.store', $seance->id), [
                'call_type' => 'start',
                'attendances' => [
                    $a->id => 'present',
                    $b->id => 'late',
                    $c->id => 'absent',
                ],
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'counts' => ['present' => 1, 'late' => 1, 'absent' => 1, 'total' => 3],
            'redirect' => route('teacher.dashboard'),
        ]);
        $this->assertNotEmpty($response->json('message'));

        // Le contrat des valeurs est intact : « late » est écrit tel quel.
        $this->assertSame('late', ESBTPAttendance::where('seance_cours_id', $seance->id)
            ->where('etudiant_id', $b->id)->where('call_type', 'start')->value('statut'));
        $this->assertSame(3, ESBTPAttendance::where('seance_cours_id', $seance->id)->where('call_type', 'start')->count());
        $this->assertTrue((bool) ESBTPSessionWorkflow::where('seance_cours_id', $seance->id)->value('call_start_done'));
    }

    public function test_le_formulaire_classique_garde_sa_redirection(): void
    {
        $seance = $this->seance();
        $this->workflowPret($seance);

        $response = $this->actingAs($this->enseignant)
            ->post(route('teacher.roll-call.store', $seance->id), [
                'call_type' => 'start',
                'attendances' => [$this->etudiants[0]->id => 'present'],
            ]);

        $response->assertRedirect(route('teacher.dashboard'));
        $response->assertSessionHas('success');
    }

    public function test_un_appel_bloque_par_le_workflow_repond_422_en_json(): void
    {
        $seance = $this->seance();
        // Émargement de début non signé : l'appel de début est interdit.
        ESBTPSessionWorkflow::getOrCreateForSession($seance->id, $this->enseignant->id);

        $response = $this->actingAs($this->enseignant)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('teacher.roll-call.store', $seance->id), [
                'call_type' => 'start',
                'attendances' => [$this->etudiants[0]->id => 'present'],
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'redirect' => route('teacher.select-call-type', $seance->id),
        ]);
        $this->assertSame(0, ESBTPAttendance::where('seance_cours_id', $seance->id)->count());
    }

    public function test_un_appel_deja_fait_precoche_les_statuts_existants(): void
    {
        $seance = $this->seance();
        $this->workflowPret($seance);
        [$a, $b] = $this->etudiants;
        foreach ([[$a, 'absent'], [$b, 'retard']] as [$etudiant, $statut]) {
            ESBTPAttendance::create([
                'seance_cours_id' => $seance->id,
                'etudiant_id' => $etudiant->id,
                'classe_id' => $this->classe->id,
                'matiere_id' => $this->matiere->id,
                'teacher_id' => $this->teacher->id,
                'annee_universitaire_id' => $this->annee->id,
                'date' => '2026-02-04',
                'heure_debut' => '10:00:00',
                'heure_fin' => '12:00:00',
                'statut' => $statut,
                'call_type' => 'start',
            ]);
        }

        $response = $this->actingAs($this->enseignant)->get(route('teacher.roll-call', $seance->id).'?type=start');

        $response->assertOk();
        $response->assertSee('Appel déjà effectué', false);
        // Mobile : statut initial issu de la base, « retard » normalisé en « late ».
        $response->assertSee('"matricule":"'.$a->matricule.'","initiales":"BA","statut":"absent"', false);
        $response->assertSee('"initiales":"CR","statut":"late"', false);
        // Bureau : la bonne case est cochée (colonne `statut`, pas `status`).
        $response->assertSee('id="absent_'.$a->id.'" style="display: none;" checked', false);
        $response->assertSee('id="late_'.$b->id.'" style="display: none;" checked', false);
    }

    private function seance(): ESBTPSeanceCours
    {
        $emploiTemps = ESBTPEmploiTemps::create([
            'titre' => 'Planning appel',
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'semestre' => 'semestre1',
            'date_debut' => $this->annee->start_date,
            'date_fin' => $this->annee->end_date,
            'is_active' => true,
            'is_current' => true,
        ]);

        return ESBTPSeanceCours::create([
            'emploi_temps_id' => $emploiTemps->id,
            'classe_id' => $this->classe->id,
            'matiere_id' => $this->matiere->id,
            'teacher_id' => $this->teacher->id,
            'jour' => 'mercredi',
            'heure_debut' => '2026-02-04 10:00:00',
            'heure_fin' => '2026-02-04 12:00:00',
            'salle' => 'Amphi A',
            'annee_universitaire_id' => $this->annee->id,
            'date_seance' => '2026-02-04',
            'type' => ESBTPSeanceCours::TYPE_COURSE,
            'type_seance' => 'cours',
            'is_active' => true,
        ]);
    }

    /** Émargement de début signé : l'appel de début devient possible. */
    private function workflowPret(ESBTPSeanceCours $seance): ESBTPSessionWorkflow
    {
        $workflow = ESBTPSessionWorkflow::getOrCreateForSession($seance->id, $this->enseignant->id);
        $workflow->attendance_start_signed = true;
        $workflow->attendance_start_signed_at = now();
        $workflow->current_step = 'call_start';
        $workflow->save();

        return $workflow;
    }
}
