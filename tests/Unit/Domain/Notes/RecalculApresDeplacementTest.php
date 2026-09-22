<?php

namespace Tests\Unit\Domain\Notes;

use App\Domain\Notes\RecalculApresDeplacement;
use App\Http\Controllers\API\CLI\CLIMaintenanceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Le recalcul d'`esbtp_resultats` après un déplacement de notes : les deux
 * côtés gardés contre le zéro, et son branchement sur la rebascule CLI
 * d'une évaluation.
 *
 * Classe BTS, matière BTS « Maths » (5) et ECUE « OGC » (9) — la famille 2 que
 * `POST /api/cli/evaluations/{id}/matiere` sert à réparer.
 */
class RecalculApresDeplacementTest extends TestCase
{
    use SchemaDesMoyennes;

    private const ETUDIANT = 100;

    private const CLASSE = 10;

    private const MATHS = 5;

    private const PHYSIQUE = 6;

    private const ECUE = 9;

    protected function setUp(): void
    {
        parent::setUp();

        $this->monterLeSchemaDesMoyennes();

        DB::table('esbtp_classes')->insert(['id' => self::CLASSE, 'name' => '2BTS GBAT E', 'systeme_academique' => 'BTS']);
        DB::table('esbtp_matieres')->insert([
            ['id' => self::MATHS, 'name' => 'Maths', 'unite_enseignement_id' => null, 'is_active' => 1],
            ['id' => self::PHYSIQUE, 'name' => 'Physique', 'unite_enseignement_id' => null, 'is_active' => 1],
            ['id' => self::ECUE, 'name' => 'OGC', 'unite_enseignement_id' => 3, 'is_active' => 1],
        ]);
    }

    protected function tearDown(): void
    {
        $this->demonterLeSchemaDesMoyennes();

        parent::tearDown();
    }

    public function test_la_rebascule_cli_recalcule_la_matiere_rejointe(): void
    {
        $this->note($this->evaluation(self::MATHS), 16);
        $mal = $this->evaluation(self::ECUE);
        $this->note($mal, 4);
        $this->resultat(self::MATHS, 16);
        $this->resultat(self::ECUE, 4);

        $reponse = app(CLIMaintenanceController::class)->evaluationChangeMatiere($this->requeteCli(['matiere_id' => self::MATHS]), $mal);

        $data = $reponse->getData(true)['data'];
        // Maths porte désormais 16 et 4 : le bulletin BTS lit cette ligne en priorité.
        $this->assertSame(10.0, $this->moyenne(self::MATHS));
        $this->assertSame(1, $data['recalculs_lances']);
        // L'ECUE n'a plus de note ici : sa ligne n'est pas remise à zéro, elle est nommée.
        $this->assertSame(4.0, $this->moyenne(self::ECUE));
        $this->assertSame(self::ECUE, $data['lignes_sans_note'][0]['matiere_id']);
        // La trace existe, sous une source que l'ENUM MySQL accepte.
        $this->assertDatabaseHas('esbtp_resultats_recompute_log', ['matiere_id' => self::MATHS, 'source' => 'manual']);
    }

    public function test_la_coordonnee_quittee_est_recalculee_s_il_y_reste_une_note(): void
    {
        $restante = $this->evaluation(self::MATHS);
        $partante = $this->evaluation(self::MATHS);
        $this->note($restante, 18);
        $this->note($partante, 2);
        $this->resultat(self::MATHS, 10);

        $rapport = $this->deplacer($partante, ['matiere_id' => self::PHYSIQUE]);

        $this->assertSame(2, $rapport['recalculs_lances']);
        $this->assertSame([], $rapport['lignes_sans_note']);
        $this->assertSame(18.0, $this->moyenne(self::MATHS));
        $this->assertSame(2.0, $this->moyenne(self::PHYSIQUE));
    }

    public function test_la_coordonnee_rejointe_sans_note_n_est_pas_remise_a_zero(): void
    {
        // Une évaluation ANNULÉE qu'on déplace : ses notes ne comptent nulle part,
        // donc la matière rejointe n'en reçoit aucune. Sa ligne existante (saisie
        // à la main, par exemple) ne doit pas tomber à 0.
        $annulee = $this->evaluation(self::MATHS, 'cancelled');
        $this->note($annulee, 12);
        $this->resultat(self::PHYSIQUE, 14);

        $rapport = $this->deplacer($annulee, ['matiere_id' => self::PHYSIQUE]);

        $this->assertSame(0, $rapport['recalculs_lances']);
        $this->assertSame(14.0, $this->moyenne(self::PHYSIQUE));
        $this->assertSame(self::PHYSIQUE, $rapport['lignes_sans_note'][0]['matiere_id']);
    }

    // ── outillage ─────────────────────────────────────────────────────────

    private function deplacer(int $evaluationId, array $changement): array
    {
        $recalcul = app(RecalculApresDeplacement::class);

        return DB::transaction(function () use ($recalcul, $evaluationId, $changement) {
            $releve = $recalcul->releverAvant([$evaluationId]);
            DB::table('esbtp_evaluations')->where('id', $evaluationId)->update($changement);

            return $recalcul->apres($releve, 'test');
        });
    }

    /** Une requête portée par un jeton `cli:admin`, sans Sanctum ni table d'utilisateurs. */
    private function requeteCli(array $corps): Request
    {
        $requete = Request::create('/api/cli/evaluations/x/matiere', 'POST', $corps);
        $requete->setUserResolver(fn () => new class
        {
            public int $id = 1;

            public function tokenCan(string $capacite): bool
            {
                return true;
            }
        });

        return $requete;
    }

    private function evaluation(int $matiereId, string $status = 'completed'): int
    {
        return DB::table('esbtp_evaluations')->insertGetId([
            'titre' => 'Devoir', 'matiere_id' => $matiereId, 'classe_id' => self::CLASSE,
            'annee_universitaire_id' => 1, 'periode' => 'semestre1', 'status' => $status,
            'bareme' => 20, 'coefficient' => 1,
        ]);
    }

    private function note(int $evaluationId, float $valeur): void
    {
        DB::table('esbtp_notes')->insert([
            'evaluation_id' => $evaluationId, 'etudiant_id' => self::ETUDIANT,
            'matiere_id' => DB::table('esbtp_evaluations')->where('id', $evaluationId)->value('matiere_id'),
            'classe_id' => self::CLASSE, 'note' => $valeur, 'is_absent' => 0,
        ]);
    }

    private function resultat(int $matiereId, float $moyenne): void
    {
        DB::table('esbtp_resultats')->insert([
            'etudiant_id' => self::ETUDIANT, 'classe_id' => self::CLASSE, 'matiere_id' => $matiereId,
            'annee_universitaire_id' => 1, 'periode' => 'semestre1', 'moyenne' => $moyenne, 'coefficient' => 1,
        ]);
    }

    private function moyenne(int $matiereId): ?float
    {
        $v = DB::table('esbtp_resultats')->where('etudiant_id', self::ETUDIANT)->where('matiere_id', $matiereId)->value('moyenne');

        return $v === null ? null : (float) $v;
    }
}
