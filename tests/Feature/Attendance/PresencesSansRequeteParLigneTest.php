<?php

namespace Tests\Feature\Attendance;

use App\Helpers\InstallationHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPAttendance;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPhase;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPSessionWorkflow;
use App\Models\ESBTPTeacher;
use App\Models\ESBTPTeacherAttendance;
use App\Models\User;
use App\Services\Notes\NoteStudentCohortService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * /esbtp/attendances et /coordinateur/attendance-dashboard faisaient des
 * requetes par classe (quatre comptages + une cohorte) et par seance (trois
 * lectures). Ces tests verifient que le nombre de requetes ne croit plus avec
 * les classes ni les seances, et que les chiffres affiches restent ceux d'avant.
 */
class PresencesSansRequeteParLigneTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPAnneeUniversitaire $annee;

    private ESBTPNiveauEtude $niveau;

    private ESBTPTeacher $enseignant;

    private User $utilisateur;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin.access', 'attendances.view', 'module.presences.access', 'identity.coordinate'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        foreach (['superAdmin', 'coordinateur', 'directeurEtudes', 'secretaire'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        InstallationHelper::flushCachedStatus();

        $this->annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $this->niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $this->enseignant = ESBTPTeacher::create([
            'user_id' => User::factory()->create()->id,
            'matricule' => 'ENS-PERF',
            'status' => 'active',
        ]);
        $this->utilisateur = User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]);
        $this->utilisateur->assignRole('superAdmin');
        $this->utilisateur->givePermissionTo(['admin.access', 'attendances.view', 'module.presences.access']);
    }

    // ---- /esbtp/attendances -------------------------------------------------

    public function test_la_liste_des_presences_ne_fait_pas_de_requete_par_classe(): void
    {
        // Seize presences des le depart : la page (15 lignes) est pleine dans les
        // deux mesures, seul le nombre de classes change.
        $this->classeAvecPresences(array_fill(0, 8, 'present'));
        $this->classeAvecPresences(array_merge(array_fill(0, 7, 'absent'), ['late']));
        $this->requetes(route('esbtp.attendances.index')); // caches de permissions et reglages
        $avecDeux = $this->requetes(route('esbtp.attendances.index'));

        foreach (range(1, 4) as $i) {
            $this->classeAvecPresences(['present', 'excuse', 'absent']);
        }
        $avecSix = $this->requetes(route('esbtp.attendances.index'));

        $this->assertSame($avecDeux, $avecSix, "Presences : {$avecDeux} requetes pour 2 classes, {$avecSix} pour 6.");
    }

    public function test_les_statistiques_par_classe_restent_justes(): void
    {
        $a = $this->classeAvecPresences(['present', 'present', 'absent', 'late']);
        $b = $this->classeAvecPresences(['excuse']);
        // Une presence de l'annee precedente ne compte pas.
        $autreAnnee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => false]);
        $seanceAncienne = $this->seance($a['classe'], ESBTPMatiere::factory()->create(), Carbon::today()->subYear());
        $this->presence($seanceAncienne, $a['etudiants'][0], 'absent', $autreAnnee);

        $reponse = $this->actingAs($this->utilisateur)->get(route('esbtp.attendances.index'))->assertOk();

        $parNom = collect($reponse->viewData('classeStats'))->keyBy('name');
        $this->assertSame(
            ['present' => 2, 'absent' => 1, 'retard' => 1, 'excuse' => 0, 'total_attendance' => 4, 'total_students' => 4],
            collect($parNom[$a['classe']->name])->only(['present', 'absent', 'retard', 'excuse', 'total_attendance', 'total_students'])->all()
        );
        $this->assertSame(1, $parNom[$b['classe']->name]['excuse']);
        $this->assertSame(1, $parNom[$b['classe']->name]['total_students']);

        $stats = $reponse->viewData('stats');
        $this->assertSame([2, 1, 1, 1], [$stats['present'], $stats['absent'], $stats['retard'], $stats['excuse']]);
        // Le filtre etudiant ne propose que ceux qui ont une presence cette annee.
        $this->assertCount(5, $reponse->viewData('etudiants'));
    }

    public function test_la_cohorte_groupee_egale_la_cohorte_classe_par_classe(): void
    {
        $tronc = ESBTPFiliere::factory()->create(['is_tronc_commun' => true, 'semestres_tronc_commun' => 1]);
        $specialite = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => $tronc->id]);
        $classeTronc = $this->classe($tronc);
        $classeSpe = $this->classe($specialite);
        $classeVide = $this->classe($specialite);
        $directe = $this->classe($tronc);

        $oriente = ESBTPInscription::factory()->create([
            'classe_id' => $classeSpe->id, 'filiere_id' => $tronc->id, 'annee_universitaire_id' => $this->annee->id,
        ]);
        ESBTPInscriptionPhase::create([
            'inscription_id' => $oriente->id, 'type_phase' => ESBTPInscriptionPhase::TYPE_TRONC_COMMUN,
            'classe_id' => $classeTronc->id, 'filiere_id' => $tronc->id, 'semestre_debut' => 1, 'semestre_fin' => 1, 'is_active' => false,
        ]);
        ESBTPInscriptionPhase::create([
            'inscription_id' => $oriente->id, 'type_phase' => ESBTPInscriptionPhase::TYPE_SPECIALISATION,
            'classe_id' => $classeSpe->id, 'filiere_id' => $specialite->id, 'semestre_debut' => 2, 'is_active' => true,
        ]);
        ESBTPInscription::factory()->count(3)->create(['classe_id' => $directe->id, 'annee_universitaire_id' => $this->annee->id]);
        ESBTPInscription::factory()->create([
            'classe_id' => $directe->id, 'annee_universitaire_id' => $this->annee->id, 'workflow_step' => 'prospect',
        ]);

        $service = app(NoteStudentCohortService::class);
        $classes = collect([$classeTronc, $classeSpe, $classeVide, $directe]);
        foreach ([[1], [2], [1, 2]] as $semestres) {
            $attendu = $classes->mapWithKeys(fn ($c) => [$c->id => $service->countStudentsForClass($c, $this->annee, $semestres)])->all();
            $this->assertSame($attendu, $service->countStudentsForClasses($classes, $this->annee, $semestres));
        }
    }

    // ---- /coordinateur/attendance-dashboard ---------------------------------

    public function test_le_tableau_de_bord_coordinateur_ne_fait_pas_de_requete_par_seance(): void
    {
        $this->journeeType();
        // Premiere visite : elle peut creer les notifications d'alerte du jour.
        $this->requetes(route('coordinateur.attendance-dashboard'));
        $avecTrois = $this->requetes(route('coordinateur.attendance-dashboard'));

        $this->journeeType();
        $this->journeeType();
        $avecNeuf = $this->requetes(route('coordinateur.attendance-dashboard'));

        $this->assertSame($avecTrois, $avecNeuf, "Coordinateur : {$avecTrois} requetes pour 3 seances, {$avecNeuf} pour 9.");
    }

    public function test_les_indicateurs_du_coordinateur_restent_justes(): void
    {
        $matiere = $this->journeeType();

        $stats = $this->actingAs($this->utilisateur)->get(route('coordinateur.attendance-dashboard'))
            ->assertOk()->viewData('stats');

        $this->assertSame(3, $stats['scheduled_courses_today']);
        $this->assertSame(1, $stats['teacher_attendances_today']);
        $this->assertSame(2, $stats['teacher_start_attendances_today']);
        $this->assertSame(1, $stats['teacher_end_attendances_today']);
        $this->assertSame(1, $stats['courses_completed_today']);
        $this->assertSame(1, $stats['call_start_done_today']);
        $this->assertSame(1, $stats['call_end_done_today']);
        $this->assertSame(1, $stats['roll_calls_completed_today']);
        $this->assertSame([1, 1, 0, 2], [
            $stats['presences_today'], $stats['absences_today'], $stats['retards_today'], $stats['total_calls_today'],
        ]);
        $this->assertSame(2, $stats['delays_today']);

        $matiereStats = collect($stats['subjects_stats'])->firstWhere('matiere_name', $matiere->name);
        $this->assertSame(
            ['total_seances' => 3, 'emargements_debut' => 2, 'emargements_fin' => 1, 'appels_debut' => 1, 'appels_fin' => 1],
            collect($matiereStats)->only(['total_seances', 'emargements_debut', 'emargements_fin', 'appels_debut', 'appels_fin'])->all()
        );

        $alertes = collect($stats['alerts'])->keyBy('title');
        $this->assertSame('1 cours sans émargement enseignant', $alertes['Émargements manquants']['message']);
        $this->assertSame("1 cours émargés sans appel d'étudiants", $alertes['Appels en attente']['message']);
        $this->assertSame(['1 présents sur 2 étudiants'], $alertes['Taux de présence critique']['details']);
    }

    public function test_une_journee_passee_compte_ses_appels(): void
    {
        $hier = Carbon::yesterday();
        $matiere = $this->journeeType($hier);

        $stats = $this->actingAs($this->utilisateur)
            ->get(route('coordinateur.attendance-dashboard', ['date' => $hier->toDateString()]))
            ->assertOk()->viewData('stats');

        // Avant, la repartition par matiere testait isToday() : 0 appel pour un jour passe.
        $matiereStats = collect($stats['subjects_stats'])->firstWhere('matiere_name', $matiere->name);
        $this->assertSame(1, $matiereStats['appels_debut']);
        $this->assertSame(1, $stats['call_start_done_today']);
    }

    // ---- fabriques ------------------------------------------------------------

    private function requetes(string $url): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->utilisateur)->get($url)->assertOk();
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    }

    private function classe(?ESBTPFiliere $filiere = null): ESBTPClasse
    {
        return ESBTPClasse::factory()->create([
            'filiere_id' => ($filiere ?? ESBTPFiliere::factory()->create())->id,
            'niveau_etude_id' => $this->niveau->id,
            'annee_universitaire_id' => $this->annee->id,
        ]);
    }

    private function seance(ESBTPClasse $classe, ESBTPMatiere $matiere, Carbon $jour): ESBTPSeanceCours
    {
        $emploi = ESBTPEmploiTemps::create([
            'titre' => 'Planning', 'classe_id' => $classe->id, 'annee_universitaire_id' => $this->annee->id,
            'semestre' => 'semestre1', 'date_debut' => $jour->copy()->subMonth()->toDateString(),
            'date_fin' => $jour->copy()->addMonth()->toDateString(), 'is_active' => true, 'is_current' => true,
        ]);

        return ESBTPSeanceCours::create([
            'emploi_temps_id' => $emploi->id, 'classe_id' => $classe->id, 'matiere_id' => $matiere->id,
            'teacher_id' => $this->enseignant->id, 'jour' => 'lundi', 'heure_debut' => '08:00:00', 'heure_fin' => '10:00:00',
            'annee_universitaire_id' => $this->annee->id, 'date_seance' => $jour->toDateString(),
            'type' => ESBTPSeanceCours::TYPE_COURSE, 'type_seance' => 'cours', 'is_active' => true,
        ]);
    }

    private function etudiantInscrit(ESBTPClasse $classe): ESBTPEtudiant
    {
        $etudiant = ESBTPEtudiant::factory()->create();
        ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id, 'classe_id' => $classe->id, 'annee_universitaire_id' => $this->annee->id,
        ]);

        return $etudiant;
    }

    private function presence(ESBTPSeanceCours $seance, ESBTPEtudiant $etudiant, string $statut, ?ESBTPAnneeUniversitaire $annee = null): void
    {
        ESBTPAttendance::create([
            'seance_cours_id' => $seance->id, 'etudiant_id' => $etudiant->id, 'classe_id' => $seance->classe_id,
            'matiere_id' => $seance->matiere_id, 'annee_universitaire_id' => ($annee ?? $this->annee)->id,
            'date' => $seance->date_seance, 'heure_debut' => '08:00:00', 'heure_fin' => '10:00:00',
            'statut' => $statut, 'call_type' => 'merged',
        ]);
    }

    /** @return array{classe: ESBTPClasse, seance: ESBTPSeanceCours, etudiants: array<int, ESBTPEtudiant>} */
    private function classeAvecPresences(array $statuts): array
    {
        $classe = $this->classe();
        $seance = $this->seance($classe, ESBTPMatiere::factory()->create(), Carbon::today()->subDays(2));
        $etudiants = [];
        foreach ($statuts as $statut) {
            $etudiant = $this->etudiantInscrit($classe);
            $this->presence($seance, $etudiant, $statut);
            $etudiants[] = $etudiant;
        }

        return ['classe' => $classe, 'seance' => $seance, 'etudiants' => $etudiants];
    }

    private function emargement(ESBTPSeanceCours $seance, string $type, Carbon $jour): void
    {
        ESBTPTeacherAttendance::create([
            'teacher_id' => $this->enseignant->user_id, 'course_id' => $seance->id, 'date' => $jour->toDateString(),
            'status' => 'present', 'type' => $type, 'validated_at' => $jour->copy()->setTime(8, 5),
        ]);
    }

    /**
     * Trois seances d'une meme matiere : la premiere complete (deux emargements,
     * deux appels, un present et un absent), la deuxieme emargee au debut sans
     * appel, la troisieme sans rien.
     */
    private function journeeType(?Carbon $jour = null): ESBTPMatiere
    {
        $jour ??= Carbon::today();
        $matiere = ESBTPMatiere::factory()->create();
        $classe = $this->classe();

        $complete = $this->seance($classe, $matiere, $jour);
        $this->emargement($complete, 'start', $jour);
        $this->emargement($complete, 'end', $jour);
        $this->presence($complete, $this->etudiantInscrit($classe), 'present');
        $this->presence($complete, $this->etudiantInscrit($classe), 'absent');
        ESBTPSessionWorkflow::create([
            'seance_cours_id' => $complete->id, 'teacher_id' => $this->enseignant->user_id,
            'call_start_done' => true, 'call_start_done_at' => $jour->copy()->setTime(8, 10),
            'call_end_done' => true, 'call_end_done_at' => $jour->copy()->setTime(9, 55),
        ]);

        $debutSeul = $this->seance($classe, $matiere, $jour);
        $this->emargement($debutSeul, 'start', $jour);

        $this->seance($classe, $matiere, $jour);

        return $matiere;
    }
}
