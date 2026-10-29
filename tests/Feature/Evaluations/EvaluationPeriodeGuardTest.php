<?php

namespace Tests\Feature\Evaluations;

use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Une classe de spécialité issue du tronc commun ne s'ouvre qu'au semestre
 * porté par `semestre_activation`. Y créer une évaluation sur un semestre
 * antérieur produit une note que l'étudiant n'aurait pas dû avoir, et qui
 * remonte ensuite sur son bulletin de tronc commun.
 *
 * C'est exactement ce qui est arrivé à l'ESBTP Yamoussoukro : une évaluation
 * de « Sécurité » en semestre 1 sur une classe de spécialité s'affichait sur
 * les bulletins de tronc commun.
 */
class EvaluationPeriodeGuardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ESBTPEvaluationFactory pose created_by à 1 en dur : sans cet
     * utilisateur, chaque insertion casse sur la clé étrangère et l'échec
     * ressemble à tort à un rejet du garde-fou.
     */
    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create(['id' => 1]);
    }

    private function classeBts(): ESBTPClasse
    {
        $niveau = ESBTPNiveauEtude::factory()->create(['type' => 'BTS']);

        return ESBTPClasse::factory()->create(['niveau_etude_id' => $niveau->id]);
    }

    private function matiereBts(): ESBTPMatiere
    {
        return ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);
    }

    /** Déclare $cible comme classe de spécialité ouverte au semestre donné. */
    private function declarerCible(ESBTPClasse $source, ESBTPClasse $cible, int $semestre): void
    {
        DB::table('esbtp_classe_orientation_targets')->insert([
            'source_classe_id' => $source->id,
            'target_classe_id' => $cible->id,
            'semestre_activation' => $semestre,
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_une_classe_ordinaire_accepte_les_deux_semestres(): void
    {
        $classe = $this->classeBts();

        foreach (['semestre1', 'semestre2'] as $periode) {
            $evaluation = ESBTPEvaluation::factory()->create([
                'classe_id' => $classe->id,
                'matiere_id' => $this->matiereBts()->id,
                'periode' => $periode,
            ]);

            $this->assertTrue($evaluation->exists);
        }
    }

    public function test_une_classe_de_specialite_refuse_un_semestre_anterieur_a_son_ouverture(): void
    {
        $troncCommun = $this->classeBts();
        $specialite = $this->classeBts();
        $this->declarerCible($troncCommun, $specialite, 2);

        $this->expectException(ValidationException::class);

        ESBTPEvaluation::factory()->create([
            'classe_id' => $specialite->id,
            'matiere_id' => $this->matiereBts()->id,
            'periode' => 'semestre1',
        ]);
    }

    public function test_une_classe_de_specialite_accepte_son_semestre_d_ouverture(): void
    {
        $troncCommun = $this->classeBts();
        $specialite = $this->classeBts();
        $this->declarerCible($troncCommun, $specialite, 2);

        $evaluation = ESBTPEvaluation::factory()->create([
            'classe_id' => $specialite->id,
            'matiere_id' => $this->matiereBts()->id,
            'periode' => 'semestre2',
        ]);

        $this->assertTrue($evaluation->exists);
    }

    /**
     * Une cible désactivée ne contraint plus rien : l'école a pu retirer cette
     * sortie d'orientation.
     */
    public function test_une_cible_desactivee_ne_bloque_pas(): void
    {
        $troncCommun = $this->classeBts();
        $specialite = $this->classeBts();
        $this->declarerCible($troncCommun, $specialite, 2);
        DB::table('esbtp_classe_orientation_targets')
            ->where('target_classe_id', $specialite->id)
            ->update(['is_active' => false]);

        $evaluation = ESBTPEvaluation::factory()->create([
            'classe_id' => $specialite->id,
            'matiere_id' => $this->matiereBts()->id,
            'periode' => 'semestre1',
        ]);

        $this->assertTrue($evaluation->exists);
    }

    /**
     * Le contrôle se déclenche aussi au déplacement d'une évaluation
     * existante, sinon on contournerait le garde-fou en deux temps.
     */
    public function test_deplacer_une_evaluation_vers_un_semestre_interdit_est_refuse(): void
    {
        $troncCommun = $this->classeBts();
        $specialite = $this->classeBts();
        $this->declarerCible($troncCommun, $specialite, 2);

        $evaluation = ESBTPEvaluation::factory()->create([
            'classe_id' => $specialite->id,
            'matiere_id' => $this->matiereBts()->id,
            'periode' => 'semestre2',
        ]);

        $this->expectException(ValidationException::class);

        $evaluation->periode = 'semestre1';
        $evaluation->save();
    }

    /**
     * Les valeurs héritées '1' et '2' coexistent avec les libellés actuels.
     */
    public function test_les_valeurs_heritees_sont_reconnues(): void
    {
        $this->assertSame(1, ESBTPEvaluation::numeroDeSemestre('1'));
        $this->assertSame(2, ESBTPEvaluation::numeroDeSemestre('2'));
        $this->assertSame(1, ESBTPEvaluation::numeroDeSemestre('semestre1'));
        $this->assertNull(ESBTPEvaluation::numeroDeSemestre('annuel'));
    }
}
