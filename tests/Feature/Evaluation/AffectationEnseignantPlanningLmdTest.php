<?php

namespace Tests\Feature\Evaluation;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPLMDResultatECUE;
use App\Models\ESBTPLMDResultatUE;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPUniteEnseignement;
use App\Models\User;
use App\Services\LMD\LMDImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le planning LMD est l'affectation officielle d'un ECUE.
 *
 * Les evaluations peuvent encore porter un enseignant pour les donnees
 * historiques, mais des qu'une affectation existe dans le planning du bon
 * semestre, de la bonne classe et de la bonne annee, elle gagne. Le bulletin
 * brouillon suit cette affectation ; la publication la fige.
 */
class AffectationEnseignantPlanningLmdTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPAnneeUniversitaire $annee;

    private ESBTPClasse $classe;

    private ESBTPMatiere $ecue;

    private ESBTPUniteEnseignement $ue;

    protected function setUp(): void
    {
        parent::setUp();

        $this->annee = ESBTPAnneeUniversitaire::factory()->create([
            'is_current' => true,
            'name' => '2025-2026',
        ]);

        app(LMDImportService::class)->import([
            'domaine' => ['name' => 'Sciences et Technologies', 'code' => 'ST'],
            'mention' => ['name' => 'Genie Civil', 'code' => 'GC'],
            'parcours' => ['name' => 'Batiment et Urbanisme', 'code' => 'BU', 'credits_licence' => 180],
            'filiere' => ['name' => 'Batiment', 'code' => 'FBU'],
            'niveaux' => [['name' => 'Licence 1', 'year' => 1]],
            'ues' => [[
                'code' => 'BMI1',
                'name' => 'Mathematiques',
                'credit' => 4,
                'niveau_year' => 1,
                'semestre' => 1,
                'ecues' => [[
                    'code' => 'BMI11',
                    'name' => 'Algebre',
                    'credit_ecue' => 4,
                ]],
            ]],
        ]);

        $parcours = ESBTPLMDParcours::where('code', 'BU')->firstOrFail();
        $niveauId = ESBTPNiveauEtude::where('year', 1)->where('type', 'Licence')->value('id');

        $this->classe = ESBTPClasse::factory()->create([
            'name' => 'L1A Batiment',
            'parcours_id' => $parcours->id,
            'filiere_id' => $parcours->filiere_id,
            'niveau_etude_id' => $niveauId,
            'systeme_academique' => 'LMD',
            'is_active' => true,
        ]);

        $this->ecue = ESBTPMatiere::where('code', 'BMI11')->firstOrFail();
        $this->ue = ESBTPUniteEnseignement::where('code', 'BMI1')->firstOrFail();
    }

    public function test_une_evaluation_lmd_prend_l_enseignant_du_planning_du_bon_semestre(): void
    {
        $planifie = User::factory()->create(['name' => 'Mme Awa Kone']);
        $envoyeParLeFormulaire = User::factory()->create(['name' => 'M. Jean Yao']);
        $this->planifier($planifie, 1);

        $evaluation = $this->evaluation('semestre1', $envoyeParLeFormulaire);

        $this->assertSame((int) $planifie->id, (int) $evaluation->fresh()->enseignant_id);
        $this->assertNull($evaluation->fresh()->enseignant_externe_nom);
    }

    public function test_un_enseignant_du_semestre_un_n_est_pas_invente_au_semestre_deux(): void
    {
        $s1 = User::factory()->create(['name' => 'Mme S1']);
        $expliciteS2 = User::factory()->create(['name' => 'Mme S2']);
        $this->planifier($s1, 1);

        $evaluation = $this->evaluation('semestre2', $expliciteS2);

        $this->assertSame((int) $expliciteS2->id, (int) $evaluation->fresh()->enseignant_id);
    }

    public function test_modifier_ou_retirer_le_professeur_du_planning_propage_aux_evaluations_existantes(): void
    {
        $initial = User::factory()->create(['name' => 'Mme Initiale']);
        $nouveau = User::factory()->create(['name' => 'M. Nouveau']);
        $planification = $this->planifier($initial, 1);
        $evaluation = $this->evaluation('semestre1', $initial);

        $planification->enseignant_principal_id = $nouveau->id;
        $planification->save();
        $this->assertSame((int) $nouveau->id, (int) $evaluation->fresh()->enseignant_id);

        $planification->enseignant_principal_id = null;
        $planification->save();
        $this->assertNull($evaluation->fresh()->enseignant_id);
        $this->assertNull($evaluation->fresh()->enseignant_externe_nom);
    }

    public function test_le_bulletin_brouillon_suit_le_planning_puis_la_publication_fige_le_nom(): void
    {
        $premier = User::factory()->create(['name' => 'Mme Premiere']);
        $auMomentDePublier = User::factory()->create(['name' => 'M. Prof Officiel']);
        $apresPublication = User::factory()->create(['name' => 'Mme Nouvelle']);
        $planification = $this->planifier($premier, 1);

        $etudiant = ESBTPEtudiant::factory()->create();
        $bulletin = ESBTPLMDBulletin::create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $this->classe->id,
            'parcours_id' => $this->classe->parcours_id,
            'annee_universitaire_id' => $this->annee->id,
            'semestre' => 1,
            'credits_totaux' => 4,
        ]);
        $resultatUe = ESBTPLMDResultatUE::create([
            'bulletin_id' => $bulletin->id,
            'unite_enseignement_id' => $this->ue->id,
            'etudiant_id' => $etudiant->id,
            'credit' => 4,
        ]);
        $resultat = ESBTPLMDResultatECUE::create([
            'bulletin_id' => $bulletin->id,
            'resultat_ue_id' => $resultatUe->id,
            'matiere_id' => $this->ecue->id,
            'etudiant_id' => $etudiant->id,
            'credit' => 4,
        ]);

        $this->assertSame('Mme Premiere', $resultat->fresh()->enseignant_snapshot_nom);

        $planification->enseignant_principal_id = $auMomentDePublier->id;
        $planification->save();
        $this->assertSame('M. Prof Officiel', $resultat->fresh()->enseignant_affiche);

        $bulletin->is_published = true;
        $bulletin->save();
        $this->assertSame('M. Prof Officiel', $resultat->fresh()->enseignant_snapshot_nom);

        $planification->enseignant_principal_id = $apresPublication->id;
        $planification->save();

        $this->assertSame(
            'M. Prof Officiel',
            $resultat->fresh()->enseignant_affiche,
            'Un bulletin publie ne doit jamais changer quand le planning change ensuite.',
        );
    }

    private function evaluation(string $periode, User $enseignant): ESBTPEvaluation
    {
        return ESBTPEvaluation::create([
            'titre' => 'Evaluation '.$periode,
            'classe_id' => $this->classe->id,
            'matiere_id' => $this->ecue->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => $periode,
            'type' => ESBTPEvaluation::TYPE_EXAMEN,
            'date_evaluation' => '2026-03-02 08:00:00',
            'duree_minutes' => 120,
            'coefficient' => 1,
            'bareme' => 20,
            'status' => ESBTPEvaluation::STATUS_COMPLETED,
            'is_published' => true,
            'enseignant_id' => $enseignant->id,
        ]);
    }

    private function planifier(User $enseignant, int $semestre): ESBTPPlanificationAcademique
    {
        return ESBTPPlanificationAcademique::create([
            'annee_universitaire_id' => $this->annee->id,
            'filiere_id' => $this->classe->filiere_id,
            'niveau_etude_id' => $this->classe->niveau_etude_id,
            'semestre' => $semestre,
            'matiere_id' => $this->ecue->id,
            'volume_horaire_total' => 30,
            'volume_horaire_cm' => 30,
            'volume_horaire_td' => 0,
            'volume_horaire_tp' => 0,
            'coefficient' => 1,
            'credits_ects' => 4,
            'enseignant_principal_id' => $enseignant->id,
            'statut' => ESBTPPlanificationAcademique::STATUT_PLANIFIE,
            'is_active' => true,
        ]);
    }
}
