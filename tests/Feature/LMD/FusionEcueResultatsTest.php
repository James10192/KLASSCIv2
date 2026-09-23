<?php

namespace Tests\Feature\LMD;

use App\Domain\LMD\Actions\MergeDuplicateEcue;
use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\PaywallMiddleware;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPUniteEnseignement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Ce que la fusion d'ECUE sous `force` fait — et ne fait délibérément pas —
 * aux résultats enregistrés.
 *
 *  - Les lignes de bulletin LMD (`esbtp_lmd_resultats_ecues`) sont REPORTÉES
 *    sur la canonique avec leur note de rattrapage, qu'aucune note ne
 *    reconstruit ; une collision sur un même bulletin reste en place, nommée.
 *  - `esbtp_resultats` n'est PAS recalculé : aucun écran LMD ne lit ces lignes,
 *    et le repli du certificat de scolarité, qui les moyenne toutes, compterait
 *    l'ECUE deux fois si la fusion en créait une pour la canonique.
 *
 * Base réelle (MySQL / MariaDB) : MySQL revérifie les clés étrangères des
 * lignes de bulletin à chaque mise à jour.
 */
class FusionEcueResultatsTest extends TestCase
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

    public function test_la_fusion_ne_cree_aucune_moyenne_enregistree(): void
    {
        // L'élève n'a de note que sur la doublure : 8, avec sa ligne de résultat.
        // Recalculer créerait une ligne à 8 pour la canonique À CÔTÉ de celle de
        // l'absorbée, laissée faute de note — et le certificat de scolarité, qui
        // moyenne toutes les lignes de l'année, compterait l'ECUE deux fois.
        $this->noter($this->evaluation($this->absorbee), 8);
        $this->resultat($this->absorbee, 8);

        $rapport = $this->fusionner();

        $this->assertTrue($rapport['committed']);
        $this->assertArrayNotHasKey('resultats', $rapport);
        $this->assertSame(1, DB::table('esbtp_resultats')->where('etudiant_id', $this->etudiant->id)->count());
        $this->assertSame(8.0, $this->moyenne($this->absorbee));
        $this->assertNull($this->moyenne($this->canonique));
        // Les notes, elles, ont bien suivi.
        $this->assertSame(1, DB::table('esbtp_notes')->where('matiere_id', $this->canonique->id)->count());
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
        // Rien n'a bougé sur ce bulletin : il n'est pas à régénérer.
        $this->assertSame([], $rapport['lmd_resultats_ecues']['bulletins_a_regenerer']);
    }

    public function test_l_apercu_ne_compte_qu_une_ligne_par_bulletin(): void
    {
        // Deux ECUE absorbées sur le même bulletin, sans la canonique : la
        // première est reportée, la seconde entre alors en collision avec elle.
        $seconde = ESBTPMatiere::create([
            'name' => 'RDM', 'code' => 'GCRDM', 'unite_enseignement_id' => $this->absorbee->unite_enseignement_id,
            'niveau_etude_id' => $this->absorbee->niveau_etude_id, 'is_active' => true,
        ]);
        $this->ligneLmd(1, 50, $this->absorbee, 8, 12);
        $this->ligneLmd(2, 50, $seconde, 9, null);

        $absorbees = [$this->absorbee->id, $seconde->id];
        $apercu = app(MergeDuplicateEcue::class)->execute($this->canonique->id, $absorbees, ['dry_run' => true, 'force' => true]);
        $rapport = app(MergeDuplicateEcue::class)->execute($this->canonique->id, $absorbees, ['dry_run' => false, 'force' => true]);

        $this->assertSame(1, $apercu['repointed']['lmd_resultats_ecues']);
        $this->assertSame(1, $rapport['lmd_resultats_ecues']['repointes']);
        $this->assertCount(1, $rapport['lmd_resultats_ecues']['conflits']);
        // Une ligne a bougé sur ce bulletin, même si une autre y est restée.
        $this->assertSame([50], $rapport['lmd_resultats_ecues']['bulletins_a_regenerer']);
    }

    public function test_le_lien_vers_un_bulletin_n_est_donne_qu_a_qui_peut_l_ouvrir(): void
    {
        $this->withoutMiddleware([
            CheckInstalled::class,
            EnsureInstalled::class,
            PaywallMiddleware::class,
        ]);
        $droits = ['admin.access', 'module.lmd.access', 'lmd.reconciliation.manage'];
        foreach ([...$droits, 'lmd.bulletins.view'] as $p) {
            Permission::findOrCreate($p, 'web');
        }

        $sansBulletins = User::factory()->create();
        $sansBulletins->givePermissionTo($droits);
        $this->actingAs($sansBulletins)->get(route('esbtp.lmd.reconciliation.index'))
            ->assertOk()->assertSee('bulletinUrlGabarit: null', false);

        $avecBulletins = User::factory()->create();
        $avecBulletins->givePermissionTo([...$droits, 'lmd.bulletins.view']);
        $this->actingAs($avecBulletins)->get(route('esbtp.lmd.reconciliation.index'))
            ->assertOk()->assertDontSee('bulletinUrlGabarit: null', false);
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
