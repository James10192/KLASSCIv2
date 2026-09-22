<?php

namespace Tests\Unit\Domain\LMD;

use App\Domain\LMD\Actions\MergeDuplicateEcue;
use App\Domain\Notes\RecalculApresDeplacement;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Unit\Domain\Notes\SchemaDesMoyennes;

/**
 * Une fusion d'ECUE sous `force` déplace des notes par un `update()` de query
 * builder, qui ne réveille aucun observateur. Ces tests verrouillent ce qui
 * doit suivre : le recalcul de la coordonnée rejointe, le refus d'écrire un
 * zéro sur la coordonnée vidée, et le report des lignes de bulletin LMD.
 *
 * Schéma : {@see SchemaDesMoyennes}.
 */
class MergeDuplicateEcueRecalculTest extends TestCase
{
    use SchemaDesMoyennes;

    private const ANNEE = 1;

    private const CLASSE_LMD = 10;

    private const ETUDIANT = 100;

    private const CANONIQUE = 7;

    private const ABSORBEE = 12;

    protected function setUp(): void
    {
        parent::setUp();

        $this->monterLeSchemaDesMoyennes();

        DB::table('esbtp_classes')->insert([
            'id' => self::CLASSE_LMD, 'name' => 'L2 GC', 'systeme_academique' => 'LMD',
        ]);
        DB::table('esbtp_matieres')->insert([
            ['id' => self::CANONIQUE, 'name' => 'RDM', 'code' => 'BRDM', 'unite_enseignement_id' => 3, 'is_active' => 1],
            ['id' => self::ABSORBEE, 'name' => 'RDM', 'code' => 'TPRDM', 'unite_enseignement_id' => 4, 'is_active' => 1],
        ]);
    }

    protected function tearDown(): void
    {
        $this->demonterLeSchemaDesMoyennes();

        parent::tearDown();
    }

    public function test_une_fusion_forcee_recalcule_la_moyenne_de_la_canonique(): void
    {
        // État d'avant, tel que l'observateur l'a laissé : 16 sur la canonique,
        // 4 sur la doublure, chacune avec sa ligne de résultat.
        $this->note($this->evaluation(self::CANONIQUE), 16);
        $this->note($this->evaluation(self::ABSORBEE), 4);
        $this->resultat(self::CANONIQUE, 16);
        $this->resultat(self::ABSORBEE, 4);

        $rapport = $this->fusionner();

        $this->assertTrue($rapport['committed']);
        // Même coefficient, même barème : la canonique porte désormais 16 et 4.
        $this->assertSame(10.0, $this->moyenne(self::CANONIQUE));
        $this->assertSame(1, $rapport['resultats']['recalcules']);

        // La trace existe, et sous une source que l'ENUM MySQL accepte. Une
        // autre valeur y ferait échouer l'insertion sans que rien ne le dise.
        $this->assertDatabaseHas('esbtp_resultats_recompute_log', [
            'etudiant_id' => self::ETUDIANT,
            'matiere_id' => self::CANONIQUE,
            'source' => 'manual',
        ]);
    }

    public function test_la_coordonnee_videe_n_est_jamais_remise_a_zero(): void
    {
        $this->note($this->evaluation(self::CANONIQUE), 16);
        $this->note($this->evaluation(self::ABSORBEE), 4);
        $this->resultat(self::CANONIQUE, 16);
        $this->resultat(self::ABSORBEE, 4);

        $rapport = $this->fusionner();

        // Relancer le calcul ici écrirait 0,00 : la ligne n'a plus de note.
        $this->assertSame(4.0, $this->moyenne(self::ABSORBEE));

        // Elle n'est pas passée sous silence pour autant.
        $this->assertCount(1, $rapport['resultats']['orphelins']);
        $this->assertSame(self::ABSORBEE, $rapport['resultats']['orphelins'][0]['matiere_id']);
        $this->assertSame(4.0, $rapport['resultats']['orphelins'][0]['moyenne']);
    }

    public function test_la_coordonnee_quittee_est_recalculee_s_il_y_reste_une_note(): void
    {
        // Le cas qu'une fusion ne produit pas (elle déplace tout) mais qu'un
        // déplaceur d'UNE évaluation produit : deux évaluations sur la même
        // coordonnée, une seule s'en va. La ligne quittée garde une note, elle
        // doit donc suivre — ni zéro, ni moyenne figée.
        $restante = $this->evaluation(self::ABSORBEE);
        $partante = $this->evaluation(self::ABSORBEE);
        $this->note($restante, 18);
        $this->note($partante, 2);
        $this->resultat(self::ABSORBEE, 10);

        DB::transaction(function () use ($partante, &$rapport) {
            DB::table('esbtp_evaluations')->where('id', $partante)->update(['matiere_id' => self::CANONIQUE]);

            $avant = [
                'classe_id' => self::CLASSE_LMD, 'matiere_id' => self::ABSORBEE,
                'annee_universitaire_id' => self::ANNEE, 'periode' => 'semestre1',
            ];
            $rapport = app(RecalculApresDeplacement::class)->apres([[
                'etudiant_id' => self::ETUDIANT,
                'avant' => $avant,
                'apres' => ['matiere_id' => self::CANONIQUE] + $avant,
            ]], 'test');
        });

        $this->assertSame(2, $rapport['recalcules']);
        $this->assertSame([], $rapport['orphelins']);
        $this->assertSame(18.0, $this->moyenne(self::ABSORBEE));
        $this->assertSame(2.0, $this->moyenne(self::CANONIQUE));
    }

    public function test_la_fusion_reporte_les_lignes_de_bulletin_lmd_et_leur_rattrapage(): void
    {
        DB::table('esbtp_lmd_resultats_ecues')->insert([
            'id' => 1, 'bulletin_id' => 50, 'resultat_ue_id' => 1, 'etudiant_id' => self::ETUDIANT,
            'matiere_id' => self::ABSORBEE, 'moyenne' => 8, 'note_rattrapage' => 13,
        ]);

        $rapport = $this->fusionner();

        // La note de seconde session ne se reconstruit depuis aucune note : si
        // la ligne restait sur la doublure, la régénération ne la retrouverait
        // plus jamais.
        $this->assertDatabaseHas('esbtp_lmd_resultats_ecues', [
            'id' => 1, 'matiere_id' => self::CANONIQUE, 'note_rattrapage' => 13,
        ]);
        $this->assertSame(1, $rapport['lmd_resultats_ecues']['repointes']);
        $this->assertSame([50], $rapport['lmd_resultats_ecues']['bulletins_a_regenerer']);
    }

    public function test_une_ligne_lmd_en_collision_reste_en_place_et_est_nommee(): void
    {
        // Les deux ECUE figurent sur le MÊME bulletin : deux moyennes, et
        // peut-être deux rattrapages. Laquelle garder n'est pas au code.
        DB::table('esbtp_lmd_resultats_ecues')->insert([
            ['id' => 1, 'bulletin_id' => 50, 'resultat_ue_id' => 1, 'etudiant_id' => self::ETUDIANT,
                'matiere_id' => self::CANONIQUE, 'moyenne' => 15, 'note_rattrapage' => null],
            ['id' => 2, 'bulletin_id' => 50, 'resultat_ue_id' => 1, 'etudiant_id' => self::ETUDIANT,
                'matiere_id' => self::ABSORBEE, 'moyenne' => 6, 'note_rattrapage' => 11],
        ]);

        $rapport = $this->fusionner();

        $this->assertDatabaseHas('esbtp_lmd_resultats_ecues', ['id' => 2, 'matiere_id' => self::ABSORBEE, 'note_rattrapage' => 11]);
        $this->assertSame(0, $rapport['lmd_resultats_ecues']['repointes']);
        $this->assertCount(1, $rapport['lmd_resultats_ecues']['conflits']);
        $this->assertSame(2, $rapport['lmd_resultats_ecues']['conflits'][0]['id']);
    }

    public function test_la_simulation_annonce_les_lignes_lmd_sans_rien_ecrire(): void
    {
        $this->note($this->evaluation(self::ABSORBEE), 4);
        $this->resultat(self::ABSORBEE, 4);
        DB::table('esbtp_lmd_resultats_ecues')->insert([
            'id' => 1, 'bulletin_id' => 50, 'resultat_ue_id' => 1, 'etudiant_id' => self::ETUDIANT,
            'matiere_id' => self::ABSORBEE, 'moyenne' => 4, 'note_rattrapage' => null,
        ]);

        $rapport = app(MergeDuplicateEcue::class)->execute(self::CANONIQUE, [self::ABSORBEE], ['dry_run' => true, 'force' => true]);

        $this->assertFalse($rapport['committed']);
        $this->assertSame(1, $rapport['repointed']['lmd_resultats_ecues']);
        $this->assertDatabaseHas('esbtp_lmd_resultats_ecues', ['id' => 1, 'matiere_id' => self::ABSORBEE]);
        $this->assertDatabaseCount('esbtp_resultats_recompute_log', 0);
    }

    // ── outillage ─────────────────────────────────────────────────────────

    private function fusionner(): array
    {
        return app(MergeDuplicateEcue::class)->execute(self::CANONIQUE, [self::ABSORBEE], ['dry_run' => false, 'force' => true]);
    }

    private function evaluation(int $matiereId): int
    {
        return DB::table('esbtp_evaluations')->insertGetId([
            'titre' => 'Devoir', 'matiere_id' => $matiereId, 'classe_id' => self::CLASSE_LMD,
            'annee_universitaire_id' => self::ANNEE, 'periode' => 'semestre1', 'status' => 'completed',
            'bareme' => 20, 'coefficient' => 1,
        ]);
    }

    private function note(int $evaluationId, float $valeur): void
    {
        $matiereId = DB::table('esbtp_evaluations')->where('id', $evaluationId)->value('matiere_id');

        DB::table('esbtp_notes')->insert([
            'evaluation_id' => $evaluationId, 'etudiant_id' => self::ETUDIANT, 'matiere_id' => $matiereId,
            'classe_id' => self::CLASSE_LMD, 'note' => $valeur, 'is_absent' => 0,
        ]);
    }

    private function resultat(int $matiereId, float $moyenne): void
    {
        DB::table('esbtp_resultats')->insert([
            'etudiant_id' => self::ETUDIANT, 'classe_id' => self::CLASSE_LMD, 'matiere_id' => $matiereId,
            'annee_universitaire_id' => self::ANNEE, 'periode' => 'semestre1', 'moyenne' => $moyenne, 'coefficient' => 1,
        ]);
    }

    private function moyenne(int $matiereId): ?float
    {
        $v = DB::table('esbtp_resultats')
            ->where('etudiant_id', self::ETUDIANT)->where('matiere_id', $matiereId)->value('moyenne');

        return $v === null ? null : (float) $v;
    }
}
