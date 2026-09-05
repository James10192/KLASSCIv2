<?php

namespace Tests\Feature\Dashboard;

use App\Enums\JustificationStatus;
use App\Helpers\InstallationHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPAttendance;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPLMDResultatUE;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPNote;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPTeacher;
use App\Models\ESBTPUniteEnseignement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Accueil mobile de l'étudiant (shell mobile, profil « etudiant »).
 *
 * Le tableau de bord expose `mobileAccueil` : prochain cours du jour,
 * absences à justifier, reste dû, dernières notes, et — selon le système de la
 * classe — une moyenne (BTS) ou les crédits acquis (LMD). Chaque lien du DOM
 * mobile est gardé par la permission de l'écran visé.
 */
class EtudiantAccueilMobileTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPAnneeUniversitaire $annee;

    private ESBTPMatiere $matiere;

    private ESBTPTeacher $teacher;

    private int $compteurSeances = 0;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['identity.student', 'notes.view_own', 'attendances.view_own', 'bulletins.view_own', 'profile.view_own'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        Role::findOrCreate('superAdmin', 'web');
        // Le garde « installed » du groupe de routes exige qu'un superAdmin existe.
        User::factory()->create()->assignRole('superAdmin');
        InstallationHelper::flushCachedStatus();

        // Un mercredi matin, pendant l'année courante : la séance du jour est à venir.
        Carbon::setTestNow(Carbon::parse('2026-02-04 09:00:00'));

        $this->annee = ESBTPAnneeUniversitaire::factory()->create([
            'is_current' => true,
            'start_date' => '2026-01-01',
            'end_date' => '2026-08-31',
        ]);
        $this->matiere = ESBTPMatiere::factory()->create(['name' => 'Droit constitutionnel', 'code' => 'DRC101']);
        $this->teacher = ESBTPTeacher::create([
            'user_id' => User::factory()->create(['name' => 'Pr Anoh'])->id,
            'matricule' => 'ENS-ACCUEIL',
            'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_l_accueil_mobile_expose_le_prochain_cours_les_absences_a_justifier_et_les_notes(): void
    {
        $classe = $this->classe('BTS');
        [$user, $etudiant] = $this->etudiantInscrit($classe, ['notes.view_own', 'attendances.view_own', 'profile.view_own']);

        // Séance du jour (mercredi) 10:00–12:00 : c'est le prochain cours.
        $this->seance($classe, 'mercredi', '10:00:00', '12:00:00');
        // Une séance du même jour déjà terminée ne compte pas.
        $this->seance($classe, 'mercredi', '07:00:00', '08:30:00');

        // Trois absences finales : sans justification, rejetée, approuvée.
        $this->absence($etudiant, $classe, '2026-02-02', null);
        $this->absence($etudiant, $classe, '2026-02-03', JustificationStatus::REJECTED->value);
        $this->absence($etudiant, $classe, '2026-02-01', JustificationStatus::APPROVED->value);

        $evaluation = ESBTPEvaluation::factory()->create([
            'matiere_id' => $this->matiere->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'titre' => 'CC1',
            'coefficient' => 2,
            'bareme' => 20,
            'periode' => 'semestre1',
            'status' => ESBTPEvaluation::STATUS_COMPLETED,
        ]);
        ESBTPNote::factory()->create([
            'evaluation_id' => $evaluation->id,
            'etudiant_id' => $etudiant->id,
            'matiere_id' => $this->matiere->id,
            'classe_id' => $classe->id,
            'note' => 15,
            'valeur' => 15,
            'is_absent' => false,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $accueil = $response->viewData('mobileAccueil');

        $this->assertFalse($accueil['est_lmd']);
        $this->assertSame($classe->name, $accueil['classe']);

        $this->assertSame('10:00', $accueil['prochain_cours']['heure']);
        $this->assertSame('Droit constitutionnel', $accueil['prochain_cours']['matiere']);
        $this->assertFalse($accueil['prochain_cours']['en_cours']);

        // Sans justification + rejetée = 2 ; l'approuvée n'attend rien.
        $this->assertSame(2, $accueil['a_justifier']['total']);
        $this->assertSame('2026-02-03', $accueil['a_justifier']['derniere']['date']->toDateString());

        // Aucun frais configuré : rien n'est dû, ce n'est pas « indisponible ».
        $this->assertSame(0.0, $accueil['finances']['reste_du']);
        $this->assertNull($accueil['finances']['prochaine_echeance']);

        $this->assertNull($accueil['credits']);
        $this->assertNotNull($accueil['moyenne']);

        $this->assertCount(1, $accueil['notes']);
        $this->assertSame('Droit constitutionnel', $accueil['notes'][0]['matiere']);
        $this->assertSame(15.0, $accueil['notes'][0]['note']);
        $this->assertSame('CC1', $accueil['notes'][0]['titre']);

        // Le DOM mobile est rendu (le shell est actif par défaut) avec ses liens gardés.
        $response->assertSee('m-only-mobile', false);
        $response->assertSee('Justifier l&#039;absence du 3 février', false);
        $response->assertSee(route('esbtp.mes-absences.index'), false);
        $response->assertSee(route('esbtp.mes-notes.index'), false);
    }

    public function test_en_lmd_les_credits_acquis_remplacent_la_moyenne(): void
    {
        $classe = $this->classe('Licence');
        [$user, $etudiant] = $this->etudiantInscrit($classe, ['notes.view_own', 'bulletins.view_own']);

        $bulletin = ESBTPLMDBulletin::create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'semestre' => 1,
            'credits_capitalises' => 6,
            'credits_totaux' => 10,
            'is_published' => true,
        ]);
        $ueAcquise = ESBTPUniteEnseignement::create(['name' => 'Droit privé fondamental', 'code' => 'DRP21']);
        $ueManquee = ESBTPUniteEnseignement::create(['name' => 'Langues', 'code' => 'LSH21']);
        ESBTPLMDResultatUE::create([
            'bulletin_id' => $bulletin->id,
            'unite_enseignement_id' => $ueAcquise->id,
            'etudiant_id' => $etudiant->id,
            'statut' => ESBTPLMDResultatUE::STATUT_AQ,
            'credit' => 6,
        ]);
        ESBTPLMDResultatUE::create([
            'bulletin_id' => $bulletin->id,
            'unite_enseignement_id' => $ueManquee->id,
            'etudiant_id' => $etudiant->id,
            'statut' => ESBTPLMDResultatUE::STATUT_NAQ,
            'credit' => 4,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $accueil = $response->viewData('mobileAccueil');

        $this->assertTrue($accueil['est_lmd']);
        $this->assertSame(['acquis' => 6, 'total' => 10], $accueil['credits']);
        $this->assertNull($accueil['moyenne']);

        $response->assertSee('Crédits acquis', false);
        $response->assertSee('6 / 10', false);
        $response->assertSee(route('esbtp.mon-bulletin.index'), false);
    }

    public function test_un_bulletin_lmd_non_publie_ne_donne_pas_de_credits(): void
    {
        $classe = $this->classe('Licence');
        [$user, $etudiant] = $this->etudiantInscrit($classe, ['notes.view_own']);

        $bulletin = ESBTPLMDBulletin::create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'semestre' => 1,
            'is_published' => false,
        ]);
        ESBTPLMDResultatUE::create([
            'bulletin_id' => $bulletin->id,
            'unite_enseignement_id' => ESBTPUniteEnseignement::create(['name' => 'UE en délibération'])->id,
            'etudiant_id' => $etudiant->id,
            'statut' => ESBTPLMDResultatUE::STATUT_AQ,
            'credit' => 6,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $this->assertNull($response->viewData('mobileAccueil')['credits']);
        $response->assertDontSee('Crédits acquis', false);
    }

    public function test_sans_droit_sur_les_notes_ni_les_absences_l_accueil_n_y_renvoie_pas(): void
    {
        $classe = $this->classe('BTS');
        [$user, $etudiant] = $this->etudiantInscrit($classe, []);

        $this->absence($etudiant, $classe, '2026-02-02', null);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('m-only-mobile', false);
        $response->assertDontSee('Dernières notes', false);
        $response->assertDontSee('Justifier l&#039;absence', false);
        $response->assertDontSee(route('esbtp.mes-notes.index'), false);
        $response->assertDontSee(route('esbtp.mes-absences.index'), false);
        $response->assertDontSee(route('esbtp.mes-paiements.index'), false);
    }

    private function classe(string $typeNiveau): ESBTPClasse
    {
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => $typeNiveau]);
        $filiere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => null]);

        return ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $this->annee->id,
            'systeme_academique' => $typeNiveau === 'BTS' ? 'BTS' : 'LMD',
        ]);
    }

    /**
     * @param string[] $permissions
     * @return array{0: User, 1: ESBTPEtudiant}
     */
    private function etudiantInscrit(ESBTPClasse $classe, array $permissions): array
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $user->givePermissionTo(array_merge(['identity.student'], $permissions));

        $etudiant = ESBTPEtudiant::factory()->create(['user_id' => $user->id, 'prenoms' => 'Aya']);
        ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'status' => 'active',
        ]);

        return [$user, $etudiant];
    }

    private function seance(ESBTPClasse $classe, string $jour, string $debut, string $fin): ESBTPSeanceCours
    {
        $this->compteurSeances++;

        $emploiTemps = ESBTPEmploiTemps::create([
            'titre' => 'Planning '.$this->compteurSeances,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'semestre' => 'semestre1',
            'date_debut' => $this->annee->start_date,
            'date_fin' => $this->annee->end_date,
            'is_active' => true,
            'is_current' => true,
        ]);

        return ESBTPSeanceCours::create([
            'emploi_temps_id' => $emploiTemps->id,
            'classe_id' => $classe->id,
            'matiere_id' => $this->matiere->id,
            'teacher_id' => $this->teacher->id,
            'jour' => $jour,
            'heure_debut' => $debut,
            'heure_fin' => $fin,
            'salle' => 'Amphi A',
            'annee_universitaire_id' => $this->annee->id,
            'date_seance' => '2026-02-04',
            'type' => ESBTPSeanceCours::TYPE_COURSE,
            'type_seance' => 'cours',
            'is_active' => true,
        ]);
    }

    private function absence(ESBTPEtudiant $etudiant, ESBTPClasse $classe, string $date, ?string $justification): ESBTPAttendance
    {
        $seance = $this->seance($classe, 'lundi', '08:00:00', '10:00:00');

        return ESBTPAttendance::create([
            'seance_cours_id' => $seance->id,
            'etudiant_id' => $etudiant->id,
            'classe_id' => $classe->id,
            'matiere_id' => $this->matiere->id,
            'teacher_id' => $this->teacher->id,
            'annee_universitaire_id' => $this->annee->id,
            'date' => $date,
            'heure_debut' => '08:00:00',
            'heure_fin' => '10:00:00',
            'statut' => 'absent',
            'call_type' => 'merged',
            'justification_status' => $justification,
        ]);
    }
}
