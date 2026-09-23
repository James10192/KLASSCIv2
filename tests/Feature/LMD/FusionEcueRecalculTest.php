<?php

namespace Tests\Feature\LMD;

use App\Domain\LMD\Actions\MergeDuplicateEcue;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPUniteEnseignement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Une fusion d'ECUE sous `force` déplace les notes de l'ECUE absorbée vers la
 * canonique par un `update()` de query builder, qui ne réveille aucun
 * observateur. Ces tests verrouillent ce qui doit suivre :
 *
 *  - la moyenne enregistrée de la canonique est recalculée ;
 *  - celle de l'absorbée, restée sans note, n'est PAS remise à 0/20 : elle
 *    est laissée et nommée dans le compte rendu ;
 *  - les lignes de bulletin LMD sont reportées avec leur note de rattrapage,
 *    et une collision sur un même bulletin reste en place, nommée.
 *
 * Base réelle (MySQL / MariaDB) : le recalcul passe par
 * `PerimetreDeRecalcul`, qui lit les notes par les mêmes requêtes que le job.
 */
class FusionEcueRecalculTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPAnneeUniversitaire $annee;

    private ESBTPClasse $classe;

    private ESBTPMatiere $canonique;

    private ESBTPMatiere $absorbee;

    private ESBTPEtudiant $etudiant;

    protected function setUp(): void
    {
        parent::setUp();

        $niveau = ESBTPNiveauEtude::create([
            'name' => 'Licence 2', 'code' => 'L2', 'type' => 'Licence', 'year' => 2, 'is_active' => true,
        ]);
        $this->annee = ESBTPAnneeUniversitaire::create([
            'name' => '2025-2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-07-31', 'is_active' => true, 'is_current' => true,
        ]);
        $filiere = ESBTPFiliere::create(['name' => 'GC', 'code' => 'GC', 'is_active' => true]);
        $this->classe = ESBTPClasse::create([
            'name' => 'L2 GC A', 'code' => 'L2GCA', 'filiere_id' => $filiere->id, 'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $this->annee->id, 'systeme_academique' => 'LMD', 'is_active' => true,
        ]);

        $ueA = ESBTPUniteEnseignement::create(['name' => 'UE A', 'code' => 'UEA', 'credit' => 6, 'semestre' => 3, 'niveau_id' => $niveau->id, 'is_active' => true]);
        $ueB = ESBTPUniteEnseignement::create(['name' => 'UE B', 'code' => 'UEB', 'credit' => 6, 'semestre' => 3, 'niveau_id' => $niveau->id, 'is_active' => true]);
        $this->canonique = ESBTPMatiere::create(['name' => 'RDM', 'code' => 'BRDM', 'unite_enseignement_id' => $ueA->id, 'niveau_etude_id' => $niveau->id, 'is_active' => true]);
        $this->absorbee = ESBTPMatiere::create(['name' => 'RDM', 'code' => 'TPRDM', 'unite_enseignement_id' => $ueB->id, 'niveau_etude_id' => $niveau->id, 'is_active' => true]);

        $this->etudiant = ESBTPEtudiant::factory()->create(['nom' => 'KOUASSI', 'prenoms' => 'Aya']);
    }

    public function test_une_fusion_forcee_recalcule_la_moyenne_de_la_canonique(): void
    {
        // État d'avant, tel que l'observateur l'a laissé : 16 sur la canonique,
        // 4 sur la doublure, chacune avec sa ligne de résultat.
        $this->noter($this->evaluation($this->canonique), 16);
        $this->noter($this->evaluation($this->absorbee), 4);
        $this->resultat($this->canonique, 16);
        $this->resultat($this->absorbee, 4);

        $rapport = $this->fusionner();

        $this->assertTrue($rapport['committed']);
        // Même coefficient, même barème : la canonique porte désormais 16 et 4.
        $this->assertSame(10.0, $this->moyenne($this->canonique));
        $this->assertGreaterThan(0, $rapport['resultats']['recalculs_tentes']);
        $this->assertFalse($rapport['resultats']['reporte']);
    }

    public function test_la_moyenne_videe_est_laissee_et_nommee_jamais_remise_a_zero(): void
    {
        $this->noter($this->evaluation($this->canonique), 16);
        $this->noter($this->evaluation($this->absorbee), 4);
        $this->resultat($this->canonique, 16);
        $this->resultat($this->absorbee, 4);

        $rapport = $this->fusionner();

        // Recalculer ici écrirait 0,00 : la ligne n'a plus de note.
        $this->assertSame(4.0, $this->moyenne($this->absorbee));

        $orphelins = $rapport['resultats']['orphelins'];
        $this->assertCount(1, $orphelins);
        $this->assertSame($this->absorbee->id, $orphelins[0]['matiere_id']);
        $this->assertSame(4.0, $orphelins[0]['moyenne']);
        // Lisible par la personne qui tranche, à l'écran de réconciliation.
        $this->assertSame('KOUASSI Aya', $orphelins[0]['etudiant']);
        $this->assertSame('L2 GC A', $orphelins[0]['classe']);
    }

    public function test_la_fusion_reporte_les_lignes_de_bulletin_lmd_et_leur_rattrapage(): void
    {
        $this->ligneLmd(1, 50, $this->absorbee, 8, 13);

        $rapport = $this->fusionner();

        // La note de seconde session ne se reconstruit depuis aucune note : si
        // la ligne restait sur la doublure, la régénération ne la retrouverait
        // plus jamais.
        $this->assertDatabaseHas('esbtp_lmd_resultats_ecues', [
            'id' => 1, 'matiere_id' => $this->canonique->id, 'note_rattrapage' => 13,
        ]);
        $this->assertSame(1, $rapport['lmd_resultats_ecues']['repointes']);
        $this->assertSame([50], $rapport['lmd_resultats_ecues']['bulletins_a_regenerer']);
    }

    public function test_une_ligne_lmd_en_collision_reste_en_place_et_est_nommee(): void
    {
        // Les deux ECUE figurent sur le MÊME bulletin : deux moyennes, et
        // peut-être deux rattrapages. Laquelle garder n'est pas au code.
        $this->ligneLmd(1, 50, $this->canonique, 15, null);
        $this->ligneLmd(2, 50, $this->absorbee, 6, 11);

        $apercu = app(MergeDuplicateEcue::class)->execute($this->canonique->id, [$this->absorbee->id], ['dry_run' => true, 'force' => true]);
        // L'aperçu ne compte pas comme « repointée » la ligne qui restera en place.
        $this->assertSame(0, $apercu['repointed']['lmd_resultats_ecues']);

        $rapport = $this->fusionner();

        $this->assertDatabaseHas('esbtp_lmd_resultats_ecues', ['id' => 2, 'matiere_id' => $this->absorbee->id, 'note_rattrapage' => 11]);
        $this->assertSame(0, $rapport['lmd_resultats_ecues']['repointes']);
        $this->assertCount(1, $rapport['lmd_resultats_ecues']['conflits']);
        $this->assertSame(2, $rapport['lmd_resultats_ecues']['conflits'][0]['id']);
    }

    // ── outillage ─────────────────────────────────────────────────────────

    private function fusionner(): array
    {
        return app(MergeDuplicateEcue::class)->execute($this->canonique->id, [$this->absorbee->id], ['dry_run' => false, 'force' => true]);
    }

    private function evaluation(ESBTPMatiere $matiere): int
    {
        return DB::table('esbtp_evaluations')->insertGetId([
            'titre' => 'Devoir', 'matiere_id' => $matiere->id, 'classe_id' => $this->classe->id,
            'type' => 'devoir', 'date_evaluation' => '2025-11-01', 'coefficient' => 1, 'bareme' => 20,
            'periode' => 'semestre1', 'status' => 'completed', 'annee_universitaire_id' => $this->annee->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function noter(int $evaluationId, float $valeur): void
    {
        $matiereId = DB::table('esbtp_evaluations')->where('id', $evaluationId)->value('matiere_id');

        DB::table('esbtp_notes')->insert([
            'evaluation_id' => $evaluationId, 'etudiant_id' => $this->etudiant->id, 'matiere_id' => $matiereId,
            'classe_id' => $this->classe->id, 'note' => $valeur, 'is_absent' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function resultat(ESBTPMatiere $matiere, float $moyenne): void
    {
        DB::table('esbtp_resultats')->insert([
            'etudiant_id' => $this->etudiant->id, 'classe_id' => $this->classe->id, 'matiere_id' => $matiere->id,
            'annee_universitaire_id' => $this->annee->id, 'periode' => 'semestre1', 'moyenne' => $moyenne, 'coefficient' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /**
     * Une ligne de bulletin LMD, avec ses deux parents réels : MySQL revérifie
     * leurs clés étrangères à chaque mise à jour de la ligne, y compris celle
     * que fait la fusion.
     */
    private function ligneLmd(int $id, int $bulletinId, ESBTPMatiere $matiere, float $moyenne, ?float $rattrapage): void
    {
        if (! DB::table('esbtp_lmd_bulletins')->where('id', $bulletinId)->exists()) {
            DB::table('esbtp_lmd_bulletins')->insert([
                'id' => $bulletinId, 'etudiant_id' => $this->etudiant->id, 'classe_id' => $this->classe->id,
                'annee_universitaire_id' => $this->annee->id, 'semestre' => 3,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('esbtp_lmd_resultats_ues')->insert([
                'id' => $bulletinId, 'bulletin_id' => $bulletinId, 'etudiant_id' => $this->etudiant->id,
                'unite_enseignement_id' => $matiere->unite_enseignement_id,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        DB::table('esbtp_lmd_resultats_ecues')->insert([
            'id' => $id, 'bulletin_id' => $bulletinId, 'resultat_ue_id' => $bulletinId, 'etudiant_id' => $this->etudiant->id,
            'matiere_id' => $matiere->id, 'moyenne' => $moyenne, 'note_rattrapage' => $rattrapage,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function moyenne(ESBTPMatiere $matiere): ?float
    {
        $v = DB::table('esbtp_resultats')
            ->where('etudiant_id', $this->etudiant->id)->where('matiere_id', $matiere->id)->value('moyenne');

        return $v === null ? null : (float) $v;
    }
}
