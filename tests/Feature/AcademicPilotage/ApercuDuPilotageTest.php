<?php

namespace Tests\Feature\AcademicPilotage;

use App\Domain\AcademicPilotage\Services\ApercuDuPilotage;
use App\Domain\AcademicPilotage\Services\AcademicNoteCoverageService;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * Le tableau de bord pédagogique.
 *
 * Ce qu'il doit garantir : il montre quelque chose SANS synchronisation
 * préalable, il relance l'enseignant désigné sur l'évaluation, il laisse à
 * l'enseignant le temps de corriger une évaluation récente, et il s'ouvre sur
 * la période des dernières évaluations plutôt que sur le semestre 1.
 */
class ApercuDuPilotageTest extends TestCase
{
    use MonteUneClasseBts, RefreshDatabase;

    private User $acteur;

    private User $enseignant;

    private ESBTPMatiere $matiere;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monterLaClasse();

        foreach (['module.academic_pilotage.access', 'academic_pilotage.view', 'academic_health.view', 'academic_pilotage.view_all'] as $nom) {
            Permission::findOrCreate($nom, 'web');
        }
        User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()])
            ->assignRole(Role::findOrCreate('superAdmin', 'web'));

        $this->acteur = User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]);
        $this->acteur->givePermissionTo(['module.academic_pilotage.access', 'academic_pilotage.view', 'academic_health.view', 'academic_pilotage.view_all']);

        $this->enseignant = User::factory()->create(['name' => 'Aminata KONE', 'phone' => '+225 07 00 00 00 01']);

        $this->matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null, 'name' => 'Topographie']);
        ESBTPMatiereFilierNiveau::create([
            'matiere_id' => $this->matiere->id,
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
        ]);
    }

    private function evaluationIlYA(int $jours, string $periode = 'semestre1'): ESBTPEvaluation
    {
        $evaluation = $this->evaluationDe($this->matiere);
        $evaluation->forceFill([
            'date_evaluation' => now()->subDays($jours),
            'periode' => $periode,
            'enseignant_id' => $this->enseignant->id,
        ])->save();

        return $evaluation;
    }

    private function apercu(array $parametres = [])
    {
        return $this->actingAs($this->acteur)->getJson(route('esbtp.pilotage-academique.apercu', $parametres + [
            'annee' => $this->annee->id,
            'periode' => 'semestre1',
        ]));
    }

    public function test_la_page_s_ouvre_sans_rien_calculer(): void
    {
        $this->actingAs($this->acteur)
            ->get(route('esbtp.pilotage-academique.index'))
            ->assertOk()
            ->assertSee('Pilotage académique')
            ->assertSee(route('esbtp.pilotage-academique.fiches'), false);
    }

    public function test_une_note_manquante_ancienne_designe_l_enseignant_a_relancer(): void
    {
        $noteParLui = $this->etudiantInscrit();
        $this->etudiantInscrit();
        $this->noter($noteParLui, $this->evaluationIlYA(20));

        $reponse = $this->apercu()->assertOk();

        $html = $reponse->json('html');
        self::assertStringContainsString('Aminata KONE', $html);
        self::assertStringContainsString('Topographie', $html);
        self::assertStringContainsString('tel:+22507000000', $html);
        self::assertStringNotContainsString('Rien à relancer', $html);
    }

    public function test_une_evaluation_recente_est_laissee_a_l_enseignant(): void
    {
        $this->etudiantInscrit();
        $this->evaluationIlYA(2);

        $donnees = app(ApercuDuPilotage::class)->construire((int) $this->annee->id, 'semestre1', null, null, null);

        self::assertSame([], $donnees['relances']);
        self::assertSame(1, $donnees['saisies_en_cours']);
        self::assertSame(1, $donnees['kpis']['notes_manquantes']);
    }

    public function test_la_periode_par_defaut_est_celle_des_dernieres_evaluations(): void
    {
        $this->etudiantInscrit();
        $this->evaluationIlYA(40, 'semestre1');
        $this->evaluationIlYA(10, 'semestre2');

        self::assertSame(['semestre2', 'semestre1'], app(ApercuDuPilotage::class)->periodesRecentes((int) $this->annee->id, null));
        self::assertSame('semestre2', $this->apercu(['periode' => ''])->assertOk()->json('periode'));
    }

    public function test_une_periode_recente_sans_note_attendue_ne_masque_pas_le_travail_en_retard(): void
    {
        // Sur presentation, une seule évaluation de semestre 3 ouvrait la page
        // sur un constat vide, pendant que le semestre 1 attendait trente notes.
        $this->etudiantInscrit();
        $this->evaluationIlYA(40, 'semestre1');
        // Le semestre 3 n'a qu'une évaluation, dans une classe sans inscrit :
        // elle n'attend aucune note.
        $sansInscrit = \App\Models\ESBTPClasse::factory()->create([
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
            'annee_universitaire_id' => $this->annee->id,
        ]);
        $this->evaluationIlYA(3, 'semestre3')->forceFill(['classe_id' => $sansInscrit->id])->save();

        $choix = app(ApercuDuPilotage::class)->choisirLaPeriode((int) $this->annee->id, null, null, null);
        $vide = app(ApercuDuPilotage::class)->construire((int) $this->annee->id, 'semestre3', null, null, null);

        self::assertSame(0, $vide['kpis']['notes_attendues'], 'Le cas à reproduire : le semestre 3 n\'attend rien ici.');
        self::assertSame('semestre1', $choix['periode']);
        self::assertSame('semestre1', $this->apercu(['periode' => ''])->assertOk()->json('periode'));
    }

    public function test_le_bandeau_de_couverture_nomme_l_auteur_de_l_evaluation(): void
    {
        $this->etudiantInscrit();
        $this->evaluationIlYA(20);

        $constat = app(AcademicNoteCoverageService::class)->summarize((int) $this->annee->id, 'semestre1', null, (int) $this->classe->id);
        $ligne = collect($constat['subjects'])->firstWhere('id', $this->matiere->id);

        self::assertSame('Aminata KONE', $ligne['enseignant']['name'] ?? null);
        self::assertSame('evaluation', $ligne['enseignant']['source'] ?? null);
    }

    public function test_la_presence_du_semestre_lit_l_emploi_du_temps_tel_qu_il_est_saisi(): void
    {
        // L'écran de saisie écrit « Semestre 1 » : ne comparer que « semestre1 »
        // faisait retomber la fenêtre sur l'année entière, sans le dire.
        \Illuminate\Support\Facades\DB::table('esbtp_emploi_temps')->insert([
            'titre' => 'EDT', 'classe_id' => $this->classe->id, 'semestre' => 'Semestre 1',
            'date_debut' => '2026-01-05', 'date_fin' => '2026-03-27',
            'annee_universitaire_id' => $this->annee->id, 'is_active' => 1, 'is_current' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $fenetre = app(\App\Domain\AcademicPilotage\Services\PresenceDuPerimetre::class)->fenetre((int) $this->annee->id, 'semestre1');

        self::assertSame('emploi_du_temps', $fenetre['source']);
        self::assertSame('2026-01-05', substr($fenetre['debut'], 0, 10));
    }

    public function test_une_seance_close_ne_compte_que_son_releve_final(): void
    {
        // La clôture garde l'appel de début et écrit le relevé fusionné : deux
        // lignes pour un même étudiant. Absent au début, présent au relevé,
        // il était là — et la séance ne compte qu'une fois.
        $this->annee->forceFill(['start_date' => now()->subMonths(2)->toDateString(), 'end_date' => now()->addMonths(6)->toDateString()])->save();
        $etudiant = $this->etudiantInscrit();
        $edt = \Illuminate\Support\Facades\DB::table('esbtp_emploi_temps')->insertGetId([
            'titre' => 'EDT', 'classe_id' => $this->classe->id, 'semestre' => 'semestre1',
            'date_debut' => now()->subMonth()->toDateString(), 'date_fin' => now()->addMonth()->toDateString(),
            'annee_universitaire_id' => $this->annee->id, 'is_active' => 1, 'is_current' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $seance = \Illuminate\Support\Facades\DB::table('esbtp_seance_cours')->insertGetId([
            'emploi_temps_id' => $edt, 'classe_id' => $this->classe->id, 'jour' => 'lundi',
            'date_seance' => now()->toDateString(), 'heure_debut' => '08:00:00', 'heure_fin' => '10:00:00',
            'annee_universitaire_id' => $this->annee->id, 'type' => 'course', 'is_recurring' => 0,
            'priority' => 0, 'is_active' => 1, 'type_seance' => 'CM', 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach ([['start', 'absent'], ['merged', 'present']] as [$type, $statut]) {
            \Illuminate\Support\Facades\DB::table('esbtp_attendances')->insert([
                'etudiant_id' => $etudiant->id, 'classe_id' => $this->classe->id, 'seance_cours_id' => $seance,
                'annee_universitaire_id' => $this->annee->id, 'date' => now()->toDateString(),
                'statut' => $statut, 'call_type' => $type, 'heure_debut' => '08:00:00', 'heure_fin' => '10:00:00', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $taux = app(\App\Domain\AcademicPilotage\Services\PresenceDuPerimetre::class)
            ->parClasse((int) $this->annee->id, 'annuel', collect([$this->classe->id]));

        self::assertSame(1, $taux[$this->classe->id]['appels']);
        self::assertSame(100.0, $taux[$this->classe->id]['taux']);
    }

    public function test_une_classe_hors_perimetre_est_refusee(): void
    {
        $enseignantSeul = User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]);
        $enseignantSeul->givePermissionTo(['module.academic_pilotage.access', 'academic_pilotage.view']);

        $this->actingAs($enseignantSeul)
            ->getJson(route('esbtp.pilotage-academique.apercu', ['annee' => $this->annee->id, 'classe' => $this->classe->id]))
            ->assertForbidden();
    }
}
