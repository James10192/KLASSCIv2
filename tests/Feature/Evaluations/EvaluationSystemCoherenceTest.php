<?php

namespace Tests\Feature\Evaluations;

use App\Models\ESBTPClasse;
use App\Models\User;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPUniteEnseignement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * La table esbtp_evaluations est partagee par le BTS et le LMD. Une evaluation
 * n'est coherente que si la nature de sa matiere suit le systeme de sa classe.
 *
 * Ces tests fixent le contrat du garde-fou pose sur le modele, y compris son
 * exception deliberee : une evaluation historiquement incoherente doit rester
 * modifiable sur ses autres champs, sinon on ne pourrait meme plus la corriger.
 */
class EvaluationSystemCoherenceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ESBTPEvaluationFactory pose created_by et updated_by a 1 en dur. Sans un
     * utilisateur portant cet identifiant, chaque insertion casse sur la
     * contrainte de cle etrangere, et l'echec ressemble a tort a un bug du
     * garde-fou.
     */
    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create(['id' => 1]);
    }

    private function matiereBts(): ESBTPMatiere
    {
        return ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);
    }

    /**
     * Il n'existe pas de factory pour les unites d'enseignement : on cree la
     * ligne minimale qui rend une matiere reconnaissable comme ECUE.
     */
    private function ecue(): ESBTPMatiere
    {
        static $suite = 0;
        $suite++;

        $ue = ESBTPUniteEnseignement::create([
            'name' => 'UE de test '.$suite,
            'code' => 'UET'.$suite,
            'credit' => 6,
            'semestre' => 1,
            'is_active' => true,
        ]);

        return ESBTPMatiere::factory()->create(['unite_enseignement_id' => $ue->id]);
    }

    /**
     * ESBTPClasse derive son systeme academique du TYPE de son niveau d'etudes
     * (Licence, Master et Doctorat donnent LMD). Forcer la colonne a la main ne
     * sert a rien : le hook de sauvegarde la recalcule. On passe donc par le
     * niveau, ce qui est aussi la regle metier reelle.
     */
    private function classe(string $systeme): ESBTPClasse
    {
        $niveau = ESBTPNiveauEtude::factory()->create([
            'type' => $systeme === 'LMD' ? 'Licence' : 'BTS',
        ]);

        return ESBTPClasse::factory()->create(['niveau_etude_id' => $niveau->id]);
    }

    public function test_une_classe_bts_accepte_une_matiere_bts(): void
    {
        $evaluation = ESBTPEvaluation::factory()->create([
            'classe_id' => $this->classe('BTS')->id,
            'matiere_id' => $this->matiereBts()->id,
        ]);

        $this->assertTrue($evaluation->exists);
    }

    public function test_une_classe_lmd_accepte_une_ecue(): void
    {
        $evaluation = ESBTPEvaluation::factory()->create([
            'classe_id' => $this->classe('LMD')->id,
            'matiere_id' => $this->ecue()->id,
        ]);

        $this->assertTrue($evaluation->exists);
    }

    public function test_une_classe_bts_refuse_une_ecue(): void
    {
        $this->expectException(ValidationException::class);

        ESBTPEvaluation::factory()->create([
            'classe_id' => $this->classe('BTS')->id,
            'matiere_id' => $this->ecue()->id,
        ]);
    }

    public function test_une_classe_lmd_refuse_une_matiere_bts(): void
    {
        $this->expectException(ValidationException::class);

        ESBTPEvaluation::factory()->create([
            'classe_id' => $this->classe('LMD')->id,
            'matiere_id' => $this->matiereBts()->id,
        ]);
    }

    public function test_changer_la_matiere_pour_une_ecue_sur_une_classe_bts_est_refuse(): void
    {
        $evaluation = ESBTPEvaluation::factory()->create([
            'classe_id' => $this->classe('BTS')->id,
            'matiere_id' => $this->matiereBts()->id,
        ]);

        $this->expectException(ValidationException::class);

        $evaluation->matiere_id = $this->ecue()->id;
        $evaluation->save();
    }

    /**
     * L'exception deliberee : les evaluations deja en base peuvent etre
     * incoherentes. Bloquer toute sauvegarde les rendrait intouchables, alors
     * qu'il faut justement pouvoir les corriger.
     */
    public function test_une_evaluation_incoherente_reste_modifiable_sur_ses_autres_champs(): void
    {
        $classe = $this->classe('BTS');
        $ecue = $this->ecue();

        // On contourne le garde-fou pour reproduire une ligne heritee.
        $evaluation = ESBTPEvaluation::factory()->make([
            'classe_id' => $classe->id,
            'matiere_id' => $this->matiereBts()->id,
        ]);
        $evaluation->save();
        ESBTPEvaluation::withoutEvents(fn () => $evaluation->update(['matiere_id' => $ecue->id]));

        $evaluation->refresh();
        $evaluation->titre = 'Titre corrige';
        $evaluation->save();

        $this->assertSame('Titre corrige', $evaluation->fresh()->titre);
    }

    public function test_rebasculer_une_evaluation_incoherente_vers_la_bonne_matiere_est_accepte(): void
    {
        $classe = $this->classe('BTS');
        $cible = $this->matiereBts();

        $evaluation = ESBTPEvaluation::factory()->make([
            'classe_id' => $classe->id,
            'matiere_id' => $this->matiereBts()->id,
        ]);
        $evaluation->save();
        ESBTPEvaluation::withoutEvents(fn () => $evaluation->update(['matiere_id' => $this->ecue()->id]));

        $evaluation->refresh();
        $evaluation->matiere_id = $cible->id;
        $evaluation->save();

        $this->assertSame($cible->id, $evaluation->fresh()->matiere_id);
    }

    public function test_le_scope_bts_only_ecarte_les_ecue(): void
    {
        $bts = $this->matiereBts();
        $ecue = $this->ecue();

        $ids = ESBTPMatiere::btsOnly()->pluck('id');

        $this->assertTrue($ids->contains($bts->id));
        $this->assertFalse($ids->contains($ecue->id));
    }
}
