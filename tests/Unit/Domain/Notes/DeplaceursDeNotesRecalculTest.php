<?php

namespace Tests\Unit\Domain\Notes;

use App\Domain\Notes\RecalculApresDeplacement;
use App\Http\Controllers\API\CLI\CLIEvaluationDeplacementController;
use App\Http\Controllers\API\CLI\CLIEvaluationPeriodeController;
use App\Http\Controllers\API\CLI\CLIMaintenanceController;
use App\Http\Controllers\ESBTPEvaluationController;
use App\Jobs\RecomputeStudentResultatJob;
use App\Models\ESBTPEvaluation;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Les quatre écrans et commandes qui déplacent une évaluation — et ses notes
 * par un `update()` que l'observateur ne voit pas — doivent laisser
 * `esbtp_resultats` d'accord avec les notes, des DEUX côtés.
 *
 * Scénario commun : deux évaluations sur la même coordonnée, notées 18 et 2.
 * La ligne de résultat porte leur moyenne, 10. On déplace celle notée 2.
 *  - la coordonnée quittée garde une note : elle doit passer à 18 ;
 *  - la coordonnée rejointe doit passer à 2.
 * Non branché, le déplaceur laisse 10 d'un côté et rien de l'autre.
 *
 * Schéma : {@see SchemaDesMoyennes}.
 */
class DeplaceursDeNotesRecalculTest extends TestCase
{
    use SchemaDesMoyennes;

    private const ANNEE = 1;

    private const CLASSE = 10;

    private const ETUDIANT = 100;

    private const MATIERE = 1;

    private const AUTRE_MATIERE = 2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->monterLeSchemaDesMoyennes();

        DB::table('esbtp_annee_universitaires')->insert(['id' => self::ANNEE, 'is_current' => 1]);
        DB::table('esbtp_etudiants')->insert(['id' => self::ETUDIANT, 'nom' => 'KOUASSI', 'prenoms' => 'Aya']);
        DB::table('esbtp_classes')->insert([
            'id' => self::CLASSE, 'name' => 'BTS GC 1', 'systeme_academique' => 'BTS',
        ]);
        DB::table('esbtp_matieres')->insert([
            ['id' => self::MATIERE, 'name' => 'RDM', 'code' => 'RDM', 'unite_enseignement_id' => null, 'is_active' => 1],
            ['id' => self::AUTRE_MATIERE, 'name' => 'Topographie', 'code' => 'TOPO', 'unite_enseignement_id' => null, 'is_active' => 1],
        ]);
    }

    protected function tearDown(): void
    {
        $this->demonterLeSchemaDesMoyennes();

        parent::tearDown();
    }

    // ── ESBTPEvaluationController::update() ──────────────────────────────

    public function test_l_ecran_d_edition_recalcule_les_deux_semestres(): void
    {
        [, $partante] = $this->deuxEvaluationsEtUneMoyenne();

        $reponse = $this->modifierDepuisLEcran($partante, ['periode' => 'semestre2']);

        $this->assertNull($reponse->getSession()->get('error'));
        $this->assertSame(18.0, $this->moyenne(self::MATIERE, 'semestre1'));
        $this->assertSame(2.0, $this->moyenne(self::MATIERE, 'semestre2'));
    }

    public function test_l_ecran_d_edition_recalcule_apres_un_changement_de_matiere(): void
    {
        [, $partante] = $this->deuxEvaluationsEtUneMoyenne();

        $reponse = $this->modifierDepuisLEcran($partante, ['matiere_id' => self::AUTRE_MATIERE]);

        $this->assertNull($reponse->getSession()->get('error'));
        $this->assertSame(18.0, $this->moyenne(self::MATIERE, 'semestre1'));
        $this->assertSame(2.0, $this->moyenne(self::AUTRE_MATIERE, 'semestre1'));
    }

    public function test_l_ecran_d_edition_signale_la_moyenne_videe_sans_la_mettre_a_zero(): void
    {
        $seule = $this->evaluation(self::MATIERE, 'semestre1');
        $this->note($seule, 12);
        $this->resultat(self::MATIERE, 'semestre1', 12);

        $reponse = $this->modifierDepuisLEcran($seule, ['periode' => 'semestre2']);

        // Recalculer ici écrirait 0,00 : la ligne n'a plus de note.
        $this->assertSame(12.0, $this->moyenne(self::MATIERE, 'semestre1'));
        $this->assertSame(12.0, $this->moyenne(self::MATIERE, 'semestre2'));
        $this->assertStringContainsString('1 moyenne(s)', (string) $reponse->getSession()->get('warning'));
    }

    public function test_une_retouche_de_titre_ne_recalcule_rien(): void
    {
        [, $partante] = $this->deuxEvaluationsEtUneMoyenne();

        $this->modifierDepuisLEcran($partante, ['titre' => 'Nouveau titre']);

        $this->assertDatabaseCount('esbtp_resultats_recompute_log', 0);
        $this->assertSame(10.0, $this->moyenne(self::MATIERE, 'semestre1'));
    }

    // ── CLIMaintenanceController::evaluationChangeMatiere() ──────────────

    public function test_le_rebasculement_de_matiere_recalcule_les_deux_matieres(): void
    {
        [, $partante] = $this->deuxEvaluationsEtUneMoyenne();

        $reponse = app(CLIMaintenanceController::class)->evaluationChangeMatiere(
            $this->requeteCli(['matiere_id' => self::AUTRE_MATIERE, 'dry_run' => false]),
            $partante,
        );

        $this->assertSame(200, $reponse->getStatusCode());
        $this->assertSame(18.0, $this->moyenne(self::MATIERE, 'semestre1'));
        $this->assertSame(2.0, $this->moyenne(self::AUTRE_MATIERE, 'semestre1'));
        $this->assertSame(2, $reponse->getData(true)['data']['resultats']['recalcules']);
    }

    public function test_une_evaluation_en_periode_heritee_rejoint_sa_matiere_sans_zero(): void
    {
        // '1' et 'semestre1' coexistent en base. Sans les alias, le recalcul ne
        // trouvait pas la note arrivée et écrivait 0/20 sur la ligne existante.
        $evaluation = $this->evaluation(self::MATIERE, '1');
        $this->note($evaluation, 14);
        $this->resultat(self::AUTRE_MATIERE, 'semestre1', 9);

        $reponse = app(CLIMaintenanceController::class)->evaluationChangeMatiere(
            $this->requeteCli(['matiere_id' => self::AUTRE_MATIERE, 'dry_run' => false]),
            $evaluation,
        );

        $this->assertSame(200, $reponse->getStatusCode());
        $this->assertSame(14.0, $this->moyenne(self::AUTRE_MATIERE, 'semestre1'));
    }

    // ── CLIEvaluationDeplacementController::deplacer() ───────────────────

    public function test_le_deplacement_de_semestre_recalcule_les_deux_semestres(): void
    {
        [, $partante] = $this->deuxEvaluationsEtUneMoyenne();

        $reponse = app(CLIEvaluationDeplacementController::class)->deplacer(
            $this->requeteCli(['evaluation_ids' => [$partante], 'periode' => 'semestre2', 'dry_run' => false])
        );

        $this->assertSame(200, $reponse->getStatusCode());
        $this->assertSame(18.0, $this->moyenne(self::MATIERE, 'semestre1'));
        $this->assertSame(2.0, $this->moyenne(self::MATIERE, 'semestre2'));
        $this->assertSame(2, $reponse->getData(true)['data']['resultats']['recalcules']);
    }

    public function test_le_deplacement_de_semestre_nomme_les_orphelins(): void
    {
        $seule = $this->evaluation(self::MATIERE, 'semestre1');
        $this->note($seule, 12);
        $this->resultat(self::MATIERE, 'semestre1', 12);

        $reponse = app(CLIEvaluationDeplacementController::class)->deplacer(
            $this->requeteCli(['evaluation_ids' => [$seule], 'periode' => 'semestre2', 'dry_run' => false])
        );

        $orphelins = $reponse->getData(true)['data']['resultats']['orphelins'];
        $this->assertCount(1, $orphelins);
        $this->assertSame('semestre1', $orphelins[0]['periode']);
        // Lisible par la personne qui tranche, pas seulement par un script.
        $this->assertSame('KOUASSI Aya', $orphelins[0]['etudiant']);
        $this->assertSame('BTS GC 1', $orphelins[0]['classe']);
        $this->assertSame(12.0, $this->moyenne(self::MATIERE, 'semestre1'));
    }

    // ── CLIEvaluationPeriodeController::repair() ─────────────────────────

    public function test_la_reparation_de_periode_recalcule_les_deux_semestres(): void
    {
        [, $partante] = $this->deuxEvaluationsEtUneMoyenne();

        // La classe ne s'ouvre qu'au semestre 2 : la réparation déplacera
        // toute évaluation de semestre 1 qu'elle y trouve. On sort donc la
        // restante vers une autre classe, pour que seule `$partante` bouge.
        DB::table('esbtp_classe_orientation_targets')->insert([
            'target_classe_id' => self::CLASSE, 'semestre_activation' => 2, 'is_active' => 1,
        ]);
        DB::table('esbtp_evaluations')->where('id', '!=', $partante)->update(['classe_id' => 99]);
        DB::table('esbtp_classes')->insert(['id' => 99, 'name' => 'BTS GC 2', 'systeme_academique' => 'BTS']);
        DB::table('esbtp_resultats')->delete();
        // Un résultat ancien sur la coordonnée quittée, sans autre note : orphelin.
        $this->resultat(self::MATIERE, 'semestre1', 7);

        $reponse = app(CLIEvaluationPeriodeController::class)->repair(
            $this->requeteCli(['dry_run' => false])
        );

        $this->assertSame(200, $reponse->getStatusCode());
        $this->assertSame(2.0, $this->moyenne(self::MATIERE, 'semestre2'));
        $this->assertSame(7.0, $this->moyenne(self::MATIERE, 'semestre1'));
        $resultats = $reponse->getData(true)['data']['resultats'];
        $this->assertSame(1, $resultats['recalcules']);
        $this->assertCount(1, $resultats['orphelins']);
    }

    // ── RecalculApresDeplacement::releverEvaluations() ───────────────────

    public function test_une_evaluation_annulee_deplacee_ne_recalcule_rien(): void
    {
        // Ses notes ne comptent nulle part : la déplacer ne change aucune
        // moyenne. La recalculer poserait 0/20 sur une ligne sans note valide.
        $annulee = $this->evaluation(self::MATIERE, 'semestre1', 'cancelled');
        $this->note($annulee, 15);
        $this->resultat(self::MATIERE, 'semestre2', 11);

        $service = app(RecalculApresDeplacement::class);
        $rapport = DB::transaction(function () use ($service, $annulee) {
            $releve = $service->releverEvaluations([$annulee]);
            DB::table('esbtp_evaluations')->where('id', $annulee)->update(['periode' => 'semestre2']);

            return $service->apresEvaluations($releve, 'test');
        });

        $this->assertSame(0, $rapport['recalcules']);
        $this->assertSame(11.0, $this->moyenne(self::MATIERE, 'semestre2'));
    }

    public function test_sur_une_file_asynchrone_les_recalculs_attendent_le_commit(): void
    {
        // Tous les autres tests tournent sur la file `sync`, où Laravel 9
        // ignore `afterCommit()`. En production la file peut être `database` :
        // les jobs doivent alors partir APRÈS le commit, sur l'état déplacé.
        Queue::fake();
        [, $partante] = $this->deuxEvaluationsEtUneMoyenne();

        app(CLIEvaluationDeplacementController::class)->deplacer(
            $this->requeteCli(['evaluation_ids' => [$partante], 'periode' => 'semestre2', 'dry_run' => false])
        );

        Queue::assertPushed(RecomputeStudentResultatJob::class, 2);
        foreach (['semestre1', 'semestre2'] as $periode) {
            Queue::assertPushed(RecomputeStudentResultatJob::class, fn (RecomputeStudentResultatJob $job) => $job->periode === $periode
                && $job->etudiantId === self::ETUDIANT
                && $job->matiereId === self::MATIERE
                && $job->source === 'manual'
                && $job->triggeredBy === 1
                && $job->afterCommit === true);
        }
    }

    // ── outillage ─────────────────────────────────────────────────────────

    /** @return array{int, int} [restante, partante] */
    private function deuxEvaluationsEtUneMoyenne(): array
    {
        $restante = $this->evaluation(self::MATIERE, 'semestre1');
        $partante = $this->evaluation(self::MATIERE, 'semestre1');
        $this->note($restante, 18);
        $this->note($partante, 2);
        $this->resultat(self::MATIERE, 'semestre1', 10);

        return [$restante, $partante];
    }

    private function modifierDepuisLEcran(int $evaluationId, array $changements)
    {
        config()->set('permissions.superadmin_gate_before', false);
        Gate::define('evaluations.edit_locked', fn () => true);

        $utilisateur = new class extends Authenticatable
        {
            protected $table = 'users';
        };
        $utilisateur->id = 1;
        $this->actingAs($utilisateur);

        $evaluation = ESBTPEvaluation::findOrFail($evaluationId);
        $donnees = $changements + [
            'titre' => $evaluation->titre,
            'type' => 'devoir',
            'date_evaluation' => '2026-10-05',
            'heure_debut' => '08:00',
            'heure_fin' => '10:00',
            'classe_id' => $evaluation->classe_id,
            'matiere_id' => $evaluation->matiere_id,
            'bareme' => 20,
            'coefficient' => 1,
            'periode' => $evaluation->periode,
        ];

        $requete = Request::create('/esbtp/evaluations/'.$evaluationId, 'PUT', $donnees);
        $requete->setLaravelSession(app('session.store'));

        return app(ESBTPEvaluationController::class)->update($requete, $evaluation);
    }

    private function requeteCli(array $donnees): Request
    {
        $requete = Request::create('/api/cli', 'POST', $donnees);
        $requete->setUserResolver(fn () => new class
        {
            public int $id = 1;

            public function tokenCan(string $ability): bool
            {
                return true;
            }
        });

        return $requete;
    }

    private function evaluation(int $matiereId, string $periode, string $status = 'completed'): int
    {
        return DB::table('esbtp_evaluations')->insertGetId([
            'titre' => 'Devoir', 'matiere_id' => $matiereId, 'classe_id' => self::CLASSE,
            'annee_universitaire_id' => self::ANNEE, 'periode' => $periode, 'status' => $status,
            'bareme' => 20, 'coefficient' => 1,
        ]);
    }

    private function note(int $evaluationId, float $valeur): void
    {
        $evaluation = DB::table('esbtp_evaluations')->where('id', $evaluationId)->first();

        DB::table('esbtp_notes')->insert([
            'evaluation_id' => $evaluationId, 'etudiant_id' => self::ETUDIANT, 'matiere_id' => $evaluation->matiere_id,
            'classe_id' => $evaluation->classe_id, 'note' => $valeur, 'is_absent' => 0,
        ]);
    }

    private function resultat(int $matiereId, string $periode, float $moyenne): void
    {
        DB::table('esbtp_resultats')->insert([
            'etudiant_id' => self::ETUDIANT, 'classe_id' => self::CLASSE, 'matiere_id' => $matiereId,
            'annee_universitaire_id' => self::ANNEE, 'periode' => $periode, 'moyenne' => $moyenne, 'coefficient' => 1,
        ]);
    }

    private function moyenne(int $matiereId, string $periode): ?float
    {
        $v = DB::table('esbtp_resultats')
            ->where('etudiant_id', self::ETUDIANT)->where('classe_id', self::CLASSE)
            ->where('matiere_id', $matiereId)->where('periode', $periode)->value('moyenne');

        return $v === null ? null : (float) $v;
    }
}
