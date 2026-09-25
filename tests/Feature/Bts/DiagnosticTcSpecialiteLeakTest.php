<?php

namespace Tests\Feature\Bts;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPhase;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPNote;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Le diagnostic des matieres de specialite sur les bulletins de tronc commun.
 *
 * Il EXECUTE le diagnostic sur une base seedee et lit ce qu'il rend. Le decor
 * reproduit les deux pistes du cas AMANI (ESBTP Yamoussoukro) : une matiere
 * classee specialite mais notee sur la classe de tronc commun, et une moyenne
 * restee au bulletin alors que la note vient d'une autre classe.
 */
class DiagnosticTcSpecialiteLeakTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPAnneeUniversitaire $annee;

    private ESBTPNiveauEtude $niveau;

    private ESBTPFiliere $tc;

    private ESBTPFiliere $specialite;

    private ESBTPClasse $classeTc;

    private ESBTPClasse $classeSpecialite;

    private ESBTPClasse $classeEtrangere;

    protected function setUp(): void
    {
        parent::setUp();

        // La fabrique d'evaluation signe `created_by = 1`.
        $admin = User::factory()->create(['id' => 1]);
        \Spatie\Permission\Models\Role::findOrCreate('superAdmin', 'web');
        $admin->assignRole('superAdmin');

        $this->annee = ESBTPAnneeUniversitaire::factory()->create();
        $this->niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $this->tc = ESBTPFiliere::factory()->create(['is_tronc_commun' => true, 'parent_id' => null, 'semestres_tronc_commun' => 1]);
        $this->specialite = ESBTPFiliere::factory()->create(['parent_id' => $this->tc->id]);
        $autreFiliere = ESBTPFiliere::factory()->create();

        $this->classeTc = $this->classe($this->tc, 'TRONC COMMUN A');
        $this->classeSpecialite = $this->classe($this->specialite, 'BATIMENT A');
        $this->classeEtrangere = $this->classe($autreFiliere, 'AUTRE CLASSE');
    }

    public function test_la_commande_et_l_api_signalent_les_deux_fuites_et_ignorent_le_cas_propre(): void
    {
        $securite = $this->matiere('Securite');
        $this->lier($securite, $this->tc, ESBTPMatiereFilierNiveau::SPECIALITE);
        $this->lier($securite, $this->specialite, null);

        $hydrologie = $this->matiere('Hydrologie');
        $this->lier($hydrologie, $this->specialite, null);

        $maths = $this->matiere('Mathematiques');
        $this->lier($maths, $this->tc, ESBTPMatiereFilierNiveau::TRONC_COMMUN);

        $dessin = $this->matiere('Dessin technique');
        $this->lier($dessin, $this->tc, null);
        $this->lier($dessin, $this->specialite, null);
        $this->planifier($dessin);

        $amani = $this->etudiantTc('AMANI');
        $this->noter($amani, $this->evaluation($securite, $this->classeTc), 12.75);
        $this->noter($amani, $this->evaluation($maths, $this->classeTc), 14);
        $this->noter($amani, $this->evaluation($dessin, $this->classeTc), 11);

        // La note d'Hydrologie vient d'une classe ou AMANI n'a aucune phase ;
        // sa moyenne, elle, est restee enregistree sur la classe de tronc commun.
        $evalEtrangere = $this->evaluation($hydrologie, $this->classeEtrangere);
        $this->noter($amani, $evalEtrangere, 15);
        $this->moyenneEnregistree($amani, $hydrologie, 15);

        $kouame = $this->etudiantReoriente('KOUAME');
        $this->noter($kouame, $this->evaluation($hydrologie, $this->classeSpecialite), 9);
        $this->moyenneEnregistree($kouame, $hydrologie, 9);

        $avant = $this->empreinte();

        Sanctum::actingAs(User::factory()->create(), ['cli:read']);
        $reponse = $this->getJson('/api/cli/diagnostics/tc-specialite-leak?annee_universitaire_id='.$this->annee->id);

        $reponse->assertOk()->assertJsonPath('success', true);
        $data = $reponse->json('data');

        $s1 = collect($data['classes'])->firstWhere('classe_id', $this->classeTc->id)['semestres'][0];
        $this->assertSame(1, $s1['semestre']);
        $matieres = collect($s1['matieres'])->keyBy('matiere');

        // Le cas propre ne remonte pas : classee tronc commun, ou planifiee.
        $this->assertFalse($matieres->has('Mathematiques'));
        $this->assertFalse($matieres->has('Dessin technique'));

        // (a) classee specialite, notee sur la classe de tronc commun.
        $this->assertSame('classee_specialite', $matieres['Securite']['type_suspicion']);
        $noteSecurite = $matieres['Securite']['etudiants'][0]['notes'][0];
        $this->assertSame(12.75, $noteSecurite['note']);
        $this->assertFalse($noteSecurite['evaluation_autre_classe']);
        $this->assertTrue($noteSecurite['retenue_au_bulletin']);
        $this->assertSame('autre', $noteSecurite['cause']);

        // (b) non classee, hors planification, portee par des moyennes enregistrees.
        $hydro = $matieres['Hydrologie'];
        $this->assertSame('non_classee_hors_planification', $hydro['type_suspicion']);
        $this->assertFalse($hydro['sur_combo_tc']);
        $parEtudiant = collect($hydro['etudiants'])->keyBy('nom');

        $noteAmani = $parEtudiant['AMANI']['notes'][0];
        $this->assertTrue($noteAmani['evaluation_autre_classe']);
        $this->assertFalse($noteAmani['retenue_au_bulletin']);
        $this->assertSame($this->classeEtrangere->id, $noteAmani['evaluation_classe_id']);
        $this->assertSame('evaluation_autre_classe', $noteAmani['cause']);
        $this->assertSame('evaluation_autre_classe', $parEtudiant['AMANI']['moyennes_enregistrees'][0]['cause']);
        $this->assertNull($parEtudiant['AMANI']['phase_specialite']);

        $kouameLigne = $parEtudiant['KOUAME'];
        $this->assertSame('etudiant_reoriente', $kouameLigne['notes'][0]['cause']);
        $this->assertSame($this->classeSpecialite->id, $kouameLigne['phase_specialite']['classe_id']);
        $this->assertSame(2, (int) $kouameLigne['phase_specialite']['semestre_debut']);

        $resume = $data['resume'];
        $this->assertSame(2, $resume['matieres_suspectes'], json_encode(collect($data['classes'])->flatMap(fn ($c) => collect($c['semestres'])->map(fn ($s) => [$s['semestre'] => array_column($s['matieres'], 'matiere')]))));
        $this->assertSame(2, $resume['etudiants_touches']);
        $this->assertSame(1, $resume['matieres_par_cause']['autre']);
        $this->assertSame(2, $resume['par_cause']['etudiant_reoriente']);
        $this->assertGreaterThanOrEqual(2, $resume['par_cause']['evaluation_autre_classe']);
        $this->assertSame($this->classeTc->id, $resume['par_classe'][0]['classe_id']);
        $this->assertNotEmpty($hydro['action_suggeree']);

        // Lecture seule : rien n'a bouge.
        $this->assertSame($avant, $this->empreinte());

        // La commande rend le meme rapport.
        $this->artisan('diagnostics:tc-specialite-leak', ['--annee' => $this->annee->id, '--json' => true])
            ->expectsOutputToContain('"matieres_suspectes": 2')
            ->assertExitCode(0);

        $this->assertSame($avant, $this->empreinte());
    }

    /** @return array<string, mixed> */
    private function empreinte(): array
    {
        return [
            'notes' => DB::table('esbtp_notes')->orderBy('id')->get(['id', 'note', 'classe_id', 'matiere_id'])->toJson(),
            'resultats' => DB::table('esbtp_resultats')->orderBy('id')->get(['id', 'moyenne', 'classe_id', 'matiere_id'])->toJson(),
            'pivot' => DB::table('esbtp_matiere_filiere_niveau')->orderBy('id')->get(['id', 'classification'])->toJson(),
            'bulletins' => DB::table('esbtp_bulletins')->count(),
        ];
    }

    public function test_une_base_propre_ne_rend_rien(): void
    {
        $maths = $this->matiere('Mathematiques');
        $this->lier($maths, $this->tc, ESBTPMatiereFilierNiveau::TRONC_COMMUN);
        $etudiant = $this->etudiantTc('PROPRE');
        $this->noter($etudiant, $this->evaluation($maths, $this->classeTc), 13);

        Sanctum::actingAs(User::factory()->create(), ['cli:read']);
        $this->getJson('/api/cli/diagnostics/tc-specialite-leak?annee_universitaire_id='.$this->annee->id)
            ->assertOk()
            ->assertJsonPath('data.resume.matieres_suspectes', 0)
            ->assertJsonPath('data.classes', []);
    }

    public function test_sans_planification_une_matiere_partagee_n_est_pas_signalee(): void
    {
        // Matiere partagee, non classee, presente sur la filiere fille : sans
        // planification du tronc commun, rien ne permet d'y voir une fuite.
        $maths = $this->matiere('Mathematiques');
        $this->lier($maths, $this->tc, null);
        $this->lier($maths, $this->specialite, null);
        $etudiant = $this->etudiantTc('SANSPLANIF');
        $this->noter($etudiant, $this->evaluation($maths, $this->classeTc), 12);

        Sanctum::actingAs(User::factory()->create(), ['cli:read']);
        $data = $this->getJson('/api/cli/diagnostics/tc-specialite-leak?annee_universitaire_id='.$this->annee->id)
            ->assertOk()
            ->json('data');

        $this->assertSame(0, $data['resume']['matieres_suspectes']);
        $this->assertContains(
            $this->classeTc->id,
            array_column($data['resume']['classes_sans_planification'], 'classe_id')
        );
    }

    public function test_un_jeton_sans_cli_read_est_refuse(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['cli:write']);

        $this->getJson('/api/cli/diagnostics/tc-specialite-leak')->assertStatus(403);
    }

    private function classe(ESBTPFiliere $filiere, string $nom): ESBTPClasse
    {
        return ESBTPClasse::factory()->create([
            'name' => $nom,
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $this->niveau->id,
            'annee_universitaire_id' => $this->annee->id,
            'systeme_academique' => 'BTS',
        ]);
    }

    private function matiere(string $nom): ESBTPMatiere
    {
        return ESBTPMatiere::factory()->create(['name' => $nom, 'unite_enseignement_id' => null, 'is_active' => true]);
    }

    private function lier(ESBTPMatiere $matiere, ESBTPFiliere $filiere, ?string $classification): void
    {
        ESBTPMatiereFilierNiveau::create([
            'matiere_id' => $matiere->id,
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $this->niveau->id,
            'classification' => $classification,
        ]);
    }

    private function planifier(ESBTPMatiere $matiere): void
    {
        ESBTPPlanificationAcademique::create([
            'annee_universitaire_id' => $this->annee->id,
            'filiere_id' => $this->tc->id,
            'niveau_etude_id' => $this->niveau->id,
            'matiere_id' => $matiere->id,
            'semestre' => 1,
            'volume_horaire_total' => 30,
        ]);
    }

    private function etudiantTc(string $nom): ESBTPEtudiant
    {
        $etudiant = ESBTPEtudiant::factory()->create(['nom' => $nom]);
        $this->inscrire($etudiant, $this->classeTc);

        return $etudiant;
    }

    /** Tronc commun au semestre 1, specialite a partir du semestre 2. */
    private function etudiantReoriente(string $nom): ESBTPEtudiant
    {
        $etudiant = ESBTPEtudiant::factory()->create(['nom' => $nom]);
        $inscription = $this->inscrire($etudiant, $this->classeSpecialite);

        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'tronc_commun',
            'classe_id' => $this->classeTc->id,
            'filiere_id' => $this->tc->id,
            'semestre_debut' => 1,
            'semestre_fin' => 1,
            'is_active' => false,
        ]);
        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'specialisation',
            'classe_id' => $this->classeSpecialite->id,
            'filiere_id' => $this->specialite->id,
            'semestre_debut' => 2,
            'is_active' => true,
        ]);

        return $etudiant;
    }

    private function inscrire(ESBTPEtudiant $etudiant, ESBTPClasse $classe): ESBTPInscription
    {
        return ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $this->tc->id,
            'niveau_id' => $this->niveau->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
        ]);
    }

    private function evaluation(ESBTPMatiere $matiere, ESBTPClasse $classe): ESBTPEvaluation
    {
        return ESBTPEvaluation::factory()->create([
            'matiere_id' => $matiere->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'status' => 'published',
            'bareme' => 20,
            'coefficient' => 1,
        ]);
    }

    /** Sans la fabrique : elle ecrit une colonne que la table ne porte plus. */
    private function noter(ESBTPEtudiant $etudiant, ESBTPEvaluation $evaluation, float $note): void
    {
        ESBTPNote::create([
            'evaluation_id' => $evaluation->id,
            'etudiant_id' => $etudiant->id,
            'matiere_id' => $evaluation->matiere_id,
            'classe_id' => $evaluation->classe_id,
            'note' => $note,
            'is_absent' => false,
        ]);
    }

    /** Une moyenne restee enregistree sur la classe de tronc commun. */
    private function moyenneEnregistree(ESBTPEtudiant $etudiant, ESBTPMatiere $matiere, float $moyenne): void
    {
        DB::table('esbtp_resultats')->insert([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $this->classeTc->id,
            'matiere_id' => $matiere->id,
            'periode' => 'semestre1',
            'annee_universitaire_id' => $this->annee->id,
            'moyenne' => $moyenne,
            'coefficient' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
