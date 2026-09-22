<?php

namespace Tests\Unit\Domain\LMD;

use App\Domain\LMD\Actions\MergeDuplicateEcue;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Unit\Domain\Notes\SchemaDesMoyennes;

/**
 * Ce que la fusion forcée d'ECUE fait — et ne fait pas — des agrégats.
 *
 * Elle ne recalcule PAS `esbtp_resultats` : la moyenne d'une ECUE se relit sur
 * les notes, et recalculer l'élément conservé en laissant la ligne de l'absorbé
 * ferait compter deux fois les notes absorbées au seul lecteur trouvé (voir
 * l'en-tête de `MergeDuplicateEcue`). Elle REPORTE les lignes de bulletin LMD,
 * dont la note de rattrapage ne se reconstruit depuis aucune note, et nomme les
 * bulletins à régénérer.
 *
 * Schéma : {@see SchemaDesMoyennes}.
 */
class MergeDuplicateEcueSousForceTest extends TestCase
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

        DB::table('esbtp_classes')->insert(['id' => self::CLASSE_LMD, 'name' => 'L2 GC', 'systeme_academique' => 'LMD']);
        DB::table('esbtp_etudiants')->insert(['id' => self::ETUDIANT, 'nom' => 'KOUASSI', 'prenoms' => 'Ama', 'matricule' => 'ET-100']);
        DB::table('esbtp_matieres')->insert([
            ['id' => self::CANONIQUE, 'name' => 'RDM', 'code' => 'BRDM', 'unite_enseignement_id' => 3, 'is_active' => 1],
            ['id' => self::ABSORBEE, 'name' => 'RDM', 'code' => 'TPRDM', 'unite_enseignement_id' => 4, 'is_active' => 1],
        ]);
        DB::table('esbtp_lmd_bulletins')->insert([
            'id' => 50, 'etudiant_id' => self::ETUDIANT, 'classe_id' => self::CLASSE_LMD,
            'annee_universitaire_id' => self::ANNEE, 'semestre' => 3,
        ]);
    }

    protected function tearDown(): void
    {
        $this->demonterLeSchemaDesMoyennes();

        parent::tearDown();
    }

    public function test_la_fusion_forcee_laisse_esbtp_resultats_intacte(): void
    {
        $this->note($this->evaluation(self::CANONIQUE), 16);
        $this->note($this->evaluation(self::ABSORBEE), 4);
        $this->resultat(self::CANONIQUE, 16);
        $this->resultat(self::ABSORBEE, 4);

        $rapport = $this->fusionner();

        $this->assertTrue($rapport['committed']);
        // Chaque note reste comptée une fois : 16 sur une ligne, 4 sur l'autre.
        // Recalculer la canonique à 10 en gardant le 4 compterait le 4 deux fois.
        $this->assertSame(16.0, $this->moyenne(self::CANONIQUE));
        $this->assertSame(4.0, $this->moyenne(self::ABSORBEE));
        $this->assertDatabaseCount('esbtp_resultats_recompute_log', 0);
        // Les notes, elles, ont bien suivi : c'est d'elles que le bulletin LMD relit la moyenne.
        $this->assertSame(2, DB::table('esbtp_notes')->where('matiere_id', self::CANONIQUE)->count());
    }

    public function test_la_fusion_reporte_les_lignes_de_bulletin_lmd_et_leur_rattrapage(): void
    {
        DB::table('esbtp_lmd_resultats_ecues')->insert([
            'id' => 1, 'bulletin_id' => 50, 'resultat_ue_id' => 1, 'etudiant_id' => self::ETUDIANT,
            'matiere_id' => self::ABSORBEE, 'moyenne' => 8, 'note_rattrapage' => 13,
        ]);

        $rapport = $this->fusionner();

        // Laissée sur la doublure, la régénération ne retrouverait plus jamais cette note.
        $this->assertDatabaseHas('esbtp_lmd_resultats_ecues', [
            'id' => 1, 'matiere_id' => self::CANONIQUE, 'note_rattrapage' => 13,
        ]);
        $this->assertSame(1, $rapport['lmd_resultats_ecues']['repointes']);

        // Décrit de quoi retrouver le bulletin sur l'écran des bulletins LMD.
        $this->assertSame([[
            'id' => 50, 'etudiant' => 'KOUASSI Ama', 'matricule' => 'ET-100', 'classe' => 'L2 GC',
            'classe_id' => self::CLASSE_LMD, 'annee_universitaire_id' => self::ANNEE, 'semestre' => 3,
        ]], $rapport['lmd_resultats_ecues']['bulletins_a_regenerer']);
    }

    public function test_une_ligne_lmd_en_collision_reste_en_place_et_est_nommee(): void
    {
        // Deux moyennes, et peut-être deux rattrapages : laquelle garder n'est pas au code.
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
        $this->assertSame(50, $rapport['lmd_resultats_ecues']['bulletins_a_regenerer'][0]['id']);
    }

    public function test_le_refus_sans_force_montre_l_impact_sur_lequel_on_decide(): void
    {
        // « Forcer » se coche sur cet écran : l'impact doit y être, pas seulement le refus.
        $this->note($this->evaluation(self::ABSORBEE), 4);
        DB::table('esbtp_lmd_resultats_ecues')->insert([
            'id' => 1, 'bulletin_id' => 50, 'resultat_ue_id' => 1, 'etudiant_id' => self::ETUDIANT,
            'matiere_id' => self::ABSORBEE, 'moyenne' => 4, 'note_rattrapage' => null,
        ]);

        $rapport = app(MergeDuplicateEcue::class)->execute(self::CANONIQUE, [self::ABSORBEE], ['dry_run' => true]);

        $this->assertTrue($rapport['blocked']);
        $this->assertSame(1, $rapport['blocking']['notes']);
        $this->assertSame(1, $rapport['repointed']['lmd_resultats_ecues']);
        $this->assertDatabaseHas('esbtp_lmd_resultats_ecues', ['id' => 1, 'matiere_id' => self::ABSORBEE]);
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
        DB::table('esbtp_notes')->insert([
            'evaluation_id' => $evaluationId, 'etudiant_id' => self::ETUDIANT,
            'matiere_id' => DB::table('esbtp_evaluations')->where('id', $evaluationId)->value('matiere_id'),
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
        $v = DB::table('esbtp_resultats')->where('etudiant_id', self::ETUDIANT)->where('matiere_id', $matiereId)->value('moyenne');

        return $v === null ? null : (float) $v;
    }
}
