<?php

namespace Tests\Unit\Domain\LMD;

use App\Domain\LMD\Actions\MergeDuplicateEcue;
use App\Domain\LMD\Actions\RetirerMoyennesEnCollision;
use App\Http\Controllers\ESBTPEtudiantController;
use App\Models\ESBTPNote;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Unit\Domain\Notes\SchemaDesMoyennes;

/**
 * Ce que la fusion forcée d'ECUE fait — et ne fait pas — des agrégats.
 *
 * Elle ne recalcule PAS `esbtp_resultats` : elle REPORTE les moyennes
 * enregistrées sur l'élément conservé (voir l'en-tête de `MergeDuplicateEcue`),
 * et les lignes de bulletin LMD, dont la note de rattrapage ne se reconstruit
 * depuis aucune note, puis nomme les bulletins à régénérer.
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

    private const SECONDE_ABSORBEE = 13;

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

    public function test_la_moyenne_enregistree_de_l_absorbee_est_reportee_et_le_certificat_ne_compte_pas_double(): void
    {
        // L'élève n'avait de notes — et de moyenne — que sur l'élément absorbé.
        $this->note($this->evaluation(self::ABSORBEE), 4);
        $this->resultat(self::ABSORBEE, 4);

        $rapport = $this->fusionner();

        // Reportée telle quelle, sans recalcul : une seule ligne pour cet élève.
        $this->assertSame(1, $rapport['moyennes_enregistrees']['repointees']);
        $this->assertSame(4.0, $this->moyenne(self::CANONIQUE));
        $this->assertNull($this->moyenne(self::ABSORBEE));
        $this->assertDatabaseCount('esbtp_resultats_recompute_log', 0);

        // Le geste ordinaire qui suit : un enseignant saisit une note sur l'élément
        // conservé. L'observateur recalcule la ligne depuis TOUTES les notes. Laissée
        // sur l'absorbée, l'ancienne ligne à 4 se serait ajoutée : (4 + 10) / 2 = 7.
        ESBTPNote::create([
            'evaluation_id' => $this->evaluation(self::CANONIQUE), 'etudiant_id' => self::ETUDIANT,
            'matiere_id' => self::CANONIQUE, 'classe_id' => self::CLASSE_LMD, 'note' => 16, 'is_absent' => false,
        ]);

        $this->assertSame(10.0, $this->moyenne(self::CANONIQUE));
        $this->assertSame(10.0, $this->moyenneDuCertificat());
    }

    public function test_une_moyenne_enregistree_en_collision_reste_en_place_et_est_nommee(): void
    {
        $this->note($this->evaluation(self::CANONIQUE), 16);
        $this->note($this->evaluation(self::ABSORBEE), 4);
        $this->resultat(self::CANONIQUE, 16);
        $this->resultat(self::ABSORBEE, 4);

        $rapport = $this->fusionner();

        $this->assertTrue($rapport['committed']);
        // Deux moyennes, peut-être saisies à la main : laquelle garder n'est pas au code.
        $this->assertSame(16.0, $this->moyenne(self::CANONIQUE));
        $this->assertSame(4.0, $this->moyenne(self::ABSORBEE));
        $this->assertSame(0, $rapport['moyennes_enregistrees']['repointees']);
        $this->assertCount(1, $rapport['moyennes_enregistrees']['conflits']);
        $this->assertDatabaseCount('esbtp_resultats_recompute_log', 0);
        // Les notes, elles, ont bien suivi : c'est d'elles que le bulletin LMD relit la moyenne.
        $this->assertSame(2, DB::table('esbtp_notes')->where('matiere_id', self::CANONIQUE)->count());
    }

    public function test_retirer_une_collision_met_de_cote_l_absorbee_et_recalcule_la_conservee(): void
    {
        $this->note($this->evaluation(self::CANONIQUE), 16);
        $this->note($this->evaluation(self::ABSORBEE), 4);
        $this->resultat(self::CANONIQUE, 16);
        $this->resultat(self::ABSORBEE, 4);
        $conflit = $this->fusionner()['moyennes_enregistrees']['conflits'][0]['id'];

        $rapport = app(RetirerMoyennesEnCollision::class)->execute(self::CANONIQUE, [$conflit], [$conflit]);

        $this->assertSame(1, $rapport['retirees']);
        $this->assertSame(1, $rapport['recalculees']);
        // Mise de côté, pas détruite.
        $this->assertNotNull(DB::table('esbtp_resultats')->where('id', $conflit)->value('deleted_at'));
        // La conservée porte maintenant les deux notes : (16 + 4) / 2 — une seule ligne.
        $this->assertSame(10.0, $this->moyenne(self::CANONIQUE));
        $this->assertSame(1, DB::table('esbtp_resultats')->whereNull('deleted_at')->count());
    }

    public function test_retirer_refuse_une_ligne_qui_n_est_pas_une_collision_de_fusion(): void
    {
        // Une moyenne ordinaire de l'élément conservé : rien à régler ici.
        $this->resultat(self::CANONIQUE, 16);
        $id = (int) DB::table('esbtp_resultats')->value('id');

        $rapport = app(RetirerMoyennesEnCollision::class)->execute(self::CANONIQUE, [$id], [$id]);

        $this->assertSame(0, $rapport['retirees']);
        $this->assertSame('pas_un_element_absorbe', $rapport['refusees'][0]['raison']);
        $this->assertNull(DB::table('esbtp_resultats')->where('id', $id)->value('deleted_at'));
    }

    public function test_retirer_refuse_une_ecue_supprimee_sans_fusion_a_cote_d_une_ecue_sans_rapport(): void
    {
        // « Hydraulique » supprimée depuis l'écran des matières, jamais fusionnée,
        // avec sa moyenne ; « Topographie », vivante, porte une moyenne saisie à
        // la main. Rien ne relie les deux : la même coordonnée ne suffit pas.
        DB::table('esbtp_matieres')->insert(['id' => 20, 'name' => 'Hydraulique', 'unite_enseignement_id' => 5, 'is_active' => 1, 'deleted_at' => now()]);
        $this->resultat(20, 13);
        $this->note($this->evaluation(self::CANONIQUE), 8);
        $this->resultat(self::CANONIQUE, 17);
        $hydraulique = (int) DB::table('esbtp_resultats')->where('matiere_id', 20)->value('id');

        // La liste gardée par le serveur ne la contient pas : aucune fusion ne l'a produite.
        $rapport = app(RetirerMoyennesEnCollision::class)->execute(self::CANONIQUE, [$hydraulique], []);

        $this->assertSame(0, $rapport['retirees']);
        $this->assertSame('hors_de_la_fusion', $rapport['refusees'][0]['raison']);
        $this->assertSame(13.0, $this->moyenne(20));
        $this->assertSame(17.0, $this->moyenne(self::CANONIQUE));
    }

    public function test_retirer_refuse_un_element_absorbe_qui_porte_encore_des_evaluations(): void
    {
        // Une fusion sans « Forcer » ne déplace pas les évaluations : il n'y a
        // pas de moyennes reportées, donc rien à régler ici.
        DB::table('esbtp_matieres')->where('id', self::ABSORBEE)->update(['deleted_at' => now()]);
        $this->evaluation(self::ABSORBEE);
        $this->resultat(self::ABSORBEE, 4);
        $this->resultat(self::CANONIQUE, 16);
        $id = (int) DB::table('esbtp_resultats')->where('matiere_id', self::ABSORBEE)->value('id');

        $rapport = app(RetirerMoyennesEnCollision::class)->execute(self::CANONIQUE, [$id], [$id]);

        $this->assertSame('element_encore_evalue', $rapport['refusees'][0]['raison']);
        $this->assertSame(4.0, $this->moyenne(self::ABSORBEE));
    }

    public function test_une_collision_est_vue_meme_quand_la_periode_s_ecrit_autrement(): void
    {
        // « 1 » et « semestre1 » désignent la même période : reportée, la ligne de
        // l'absorbée serait une seconde ligne vivante pour la même coordonnée.
        $this->resultat(self::CANONIQUE, 16);
        $this->resultat(self::ABSORBEE, 4, '1');

        $rapport = $this->fusionner();

        $this->assertSame(0, $rapport['moyennes_enregistrees']['repointees']);
        $this->assertCount(1, $rapport['moyennes_enregistrees']['conflits']);
    }

    public function test_sans_force_les_moyennes_enregistrees_ne_bougent_pas(): void
    {
        $this->resultat(self::ABSORBEE, 12);

        $rapport = app(MergeDuplicateEcue::class)->execute(self::CANONIQUE, [self::ABSORBEE], ['dry_run' => false]);

        $this->assertTrue($rapport['committed']);
        $this->assertSame(0, $rapport['moyennes_enregistrees']['repointees']);
        $this->assertSame(12.0, $this->moyenne(self::ABSORBEE));
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

    public function test_l_apercu_ne_compte_pas_une_ligne_lmd_qui_restera_en_collision(): void
    {
        // « Forcer » se coche sur ce chiffre : il doit annoncer ce que la fusion fera.
        DB::table('esbtp_lmd_resultats_ecues')->insert([
            ['id' => 1, 'bulletin_id' => 50, 'resultat_ue_id' => 1, 'etudiant_id' => self::ETUDIANT,
                'matiere_id' => self::CANONIQUE, 'moyenne' => 15, 'note_rattrapage' => null],
            ['id' => 2, 'bulletin_id' => 50, 'resultat_ue_id' => 1, 'etudiant_id' => self::ETUDIANT,
                'matiere_id' => self::ABSORBEE, 'moyenne' => 6, 'note_rattrapage' => 11],
        ]);

        $apercu = $this->apercu([self::ABSORBEE]);
        $rapport = $this->fusionner();

        $this->assertSame(0, $apercu['repointed']['lmd_resultats_ecues']);
        $this->assertSame($rapport['lmd_resultats_ecues']['repointes'], $apercu['repointed']['lmd_resultats_ecues']);
    }

    public function test_l_apercu_compte_une_ligne_lmd_par_bulletin_quand_deux_absorbees_s_y_croisent(): void
    {
        // La première est reportée, la seconde entre alors en collision avec elle.
        DB::table('esbtp_matieres')->insert([
            'id' => self::SECONDE_ABSORBEE, 'name' => 'RDM', 'code' => 'GCRDM', 'unite_enseignement_id' => 5, 'is_active' => 1,
        ]);
        DB::table('esbtp_lmd_resultats_ecues')->insert([
            ['id' => 1, 'bulletin_id' => 50, 'resultat_ue_id' => 1, 'etudiant_id' => self::ETUDIANT,
                'matiere_id' => self::ABSORBEE, 'moyenne' => 8, 'note_rattrapage' => 12],
            ['id' => 2, 'bulletin_id' => 50, 'resultat_ue_id' => 1, 'etudiant_id' => self::ETUDIANT,
                'matiere_id' => self::SECONDE_ABSORBEE, 'moyenne' => 9, 'note_rattrapage' => null],
        ]);

        $absorbees = [self::ABSORBEE, self::SECONDE_ABSORBEE];
        $apercu = $this->apercu($absorbees);
        $rapport = app(MergeDuplicateEcue::class)->execute(self::CANONIQUE, $absorbees, ['dry_run' => false, 'force' => true]);

        $this->assertSame(1, $apercu['repointed']['lmd_resultats_ecues']);
        $this->assertSame(1, $rapport['lmd_resultats_ecues']['repointes']);
        $this->assertCount(1, $rapport['lmd_resultats_ecues']['conflits']);
    }

    public function test_l_apercu_ne_compte_pas_une_moyenne_qui_restera_en_collision(): void
    {
        // La période s'écrit « 1 » d'un côté, « semestre1 » de l'autre : c'est la
        // même coordonnée, et la fusion la traite comme une collision.
        $this->resultat(self::CANONIQUE, 14, '1');
        $this->resultat(self::ABSORBEE, 6, 'semestre1');

        $apercu = $this->apercu([self::ABSORBEE]);
        $rapport = $this->fusionner();

        $this->assertSame(0, $apercu['repointed']['moyennes_enregistrees']);
        $this->assertSame($rapport['moyennes_enregistrees']['repointees'], $apercu['repointed']['moyennes_enregistrees']);
    }

    // ── outillage ─────────────────────────────────────────────────────────

    private function apercu(array $absorbees): array
    {
        return app(MergeDuplicateEcue::class)->execute(self::CANONIQUE, $absorbees, ['dry_run' => true, 'force' => true]);
    }

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

    private function resultat(int $matiereId, float $moyenne, string $periode = 'semestre1'): void
    {
        DB::table('esbtp_resultats')->insert([
            'etudiant_id' => self::ETUDIANT, 'classe_id' => self::CLASSE_LMD, 'matiere_id' => $matiereId,
            'annee_universitaire_id' => self::ANNEE, 'periode' => $periode, 'moyenne' => $moyenne, 'coefficient' => 1,
        ]);
    }

    private function moyenneDuCertificat(): float
    {
        $inscription = (object) ['anneeUniversitaire' => (object) ['id' => self::ANNEE]];
        $controleur = app(ESBTPEtudiantController::class);
        $methode = new \ReflectionMethod($controleur, 'attachMoyenneCalculee');
        $methode->setAccessible(true);
        $methode->invoke($controleur, collect([$inscription]), self::ETUDIANT);

        return (float) $inscription->moyenne_generale_calculee;
    }

    private function moyenne(int $matiereId): ?float
    {
        $v = DB::table('esbtp_resultats')->where('etudiant_id', self::ETUDIANT)->where('matiere_id', $matiereId)->whereNull('deleted_at')->value('moyenne');

        return $v === null ? null : (float) $v;
    }
}
