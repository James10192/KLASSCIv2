<?php

namespace Tests\Unit\Domain\Notes;

use App\Http\Controllers\ESBTPEvaluationController;
use App\Http\Controllers\ESBTPSeanceCoursController;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPSeanceCours;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Trois écritures qui font entrer ou sortir des notes d'une moyenne sans
 * passer par l'observateur des notes, et qui doivent pourtant laisser
 * `esbtp_resultats` d'accord avec elles :
 *  - la modification d'une séance de devoir, qui déplace le devoir lié ;
 *  - l'annulation et la réactivation d'une évaluation ;
 *  - `esbtp:check-evaluations-annees`, qui donne une année à une évaluation.
 *
 * Scénario commun : deux évaluations sur la même coordonnée, notées 18 et 2.
 * La ligne de résultat porte leur moyenne, 10.
 *
 * Schéma : {@see SchemaDesMoyennes} (SQLite en mémoire : la suite MySQL de
 * `tests/Feature/Notes/RecalculApresDeplacementTest.php` couvre les
 * déplacements par l'écran et par l'API CLI).
 */
class EntreesEtSortiesDeMoyenneTest extends TestCase
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

    // ── ESBTPSeanceCoursController::update() → AlignementDuDevoir ────────

    public function test_deplacer_la_seance_de_devoir_recalcule_les_deux_matieres(): void
    {
        [, $devoir] = $this->deuxEvaluationsEtUneMoyenne();

        $reponse = $this->modifierLaSeanceDeDevoir($devoir, self::AUTRE_MATIERE);

        $this->assertNull($reponse->getSession()->get('error'));
        $this->assertNull($reponse->getSession()->get('warning'));
        $this->assertSame(18.0, $this->moyenne(self::MATIERE, 'semestre1'));
        $this->assertSame(2.0, $this->moyenne(self::AUTRE_MATIERE, 'semestre1'));
        // La copie dénormalisée suit, comme depuis l'écran d'édition.
        $this->assertSame(self::AUTRE_MATIERE, (int) DB::table('esbtp_notes')->where('evaluation_id', $devoir)->value('matiere_id'));
    }

    public function test_retoucher_la_seance_ne_defait_pas_une_periode_corrigee_sur_le_devoir(): void
    {
        // Le devoir a été reporté au semestre 2 sur l'écran de l'évaluation.
        // Retoucher la séance sans en changer la date ni la matière ne doit
        // ni le ramener au semestre 1, ni toucher à aucune moyenne.
        [, $devoir] = $this->deuxEvaluationsEtUneMoyenne();
        DB::table('esbtp_evaluations')->where('id', $devoir)->update(['periode' => 'semestre2']);
        DB::table('esbtp_resultats')->update(['moyenne' => 18]);
        $this->resultat(self::MATIERE, 'semestre2', 2);

        $reponse = $this->modifierLaSeanceDeDevoir($devoir, self::MATIERE);

        $this->assertNull($reponse->getSession()->get('error'));
        $this->assertSame('semestre2', DB::table('esbtp_evaluations')->where('id', $devoir)->value('periode'));
        $this->assertSame(18.0, $this->moyenne(self::MATIERE, 'semestre1'));
        $this->assertSame(2.0, $this->moyenne(self::MATIERE, 'semestre2'));
        $this->assertDatabaseCount('esbtp_resultats_recompute_log', 0);
    }

    public function test_changer_le_jour_de_la_seance_ne_defait_pas_une_periode_corrigee(): void
    {
        // Le devoir a été reporté au semestre 2 à la main ; l'emploi du temps,
        // lui, dit « Semestre 1 ». Passer la séance du lundi au mardi ne doit
        // pas ramener le devoir au semestre 1 : la date d'une séance se déduit
        // de son emploi du temps, elle ne dit rien d'un autre semestre.
        [, $devoir] = $this->deuxEvaluationsEtUneMoyenne();
        DB::table('esbtp_evaluations')->where('id', $devoir)->update(['periode' => 'semestre2']);
        DB::table('esbtp_resultats')->update(['moyenne' => 18]);
        $this->resultat(self::MATIERE, 'semestre2', 2);

        $this->modifierLaSeanceDeDevoir($devoir, self::MATIERE, jour: 2);

        $this->assertSame('2026-10-06', substr((string) DB::table('esbtp_evaluations')->where('id', $devoir)->value('date_evaluation'), 0, 10));
        $this->assertSame('semestre2', DB::table('esbtp_evaluations')->where('id', $devoir)->value('periode'));
        $this->assertDatabaseCount('esbtp_resultats_recompute_log', 0);
    }

    public function test_deplacer_la_seance_de_devoir_signale_la_moyenne_videe(): void
    {
        $devoir = $this->evaluation(self::MATIERE, 'semestre1');
        $this->note($devoir, 12);
        $this->resultat(self::MATIERE, 'semestre1', 12);

        $reponse = $this->modifierLaSeanceDeDevoir($devoir, self::AUTRE_MATIERE);

        // Recalculer écrirait 0,00 : la matière quittée n'a plus de note.
        $this->assertSame(12.0, $this->moyenne(self::MATIERE, 'semestre1'));
        $this->assertStringContainsString('1 moyenne(s)', (string) $reponse->getSession()->get('warning'));
    }

    public function test_un_echec_d_ecriture_annule_la_seance_avec_son_devoir(): void
    {
        [, $devoir] = $this->deuxEvaluationsEtUneMoyenne();
        // La recopie sur les notes ne peut plus écrire : elle lève, dans la transaction.
        Schema::drop('esbtp_notes');

        $reponse = $this->modifierLaSeanceDeDevoir($devoir, self::AUTRE_MATIERE);

        $this->assertNotNull($reponse->getSession()->get('error'));
        // Rien n'a bougé : ni la séance, ni son devoir.
        $this->assertSame(self::MATIERE, (int) DB::table('esbtp_seance_cours')->where('id', 500)->value('matiere_id'));
        $this->assertSame(self::MATIERE, (int) DB::table('esbtp_evaluations')->where('id', $devoir)->value('matiere_id'));
    }

    public function test_un_recalcul_en_echec_est_dit_sans_defaire_le_deplacement(): void
    {
        [, $devoir] = $this->deuxEvaluationsEtUneMoyenne();
        // Le job ne peut plus écrire : le recalcul échoue, sans lever.
        Schema::drop('esbtp_resultats');

        $reponse = $this->modifierLaSeanceDeDevoir($devoir, self::AUTRE_MATIERE);

        $this->assertNull($reponse->getSession()->get('error'));
        $this->assertStringContainsString('en échec', (string) $reponse->getSession()->get('warning'));
        $this->assertSame(self::AUTRE_MATIERE, (int) DB::table('esbtp_evaluations')->where('id', $devoir)->value('matiere_id'));
    }

    // ── ESBTPEvaluationController::cancel() / restore() / updateStatus() ─

    public function test_annuler_une_evaluation_la_retire_de_la_moyenne(): void
    {
        [, $annulee] = $this->deuxEvaluationsEtUneMoyenne();

        $reponse = $this->actionDeLaListe('cancel', $annulee);

        $this->assertTrue($reponse->getData(true)['success']);
        $this->assertNull($reponse->getData(true)['warning']);
        $this->assertSame(18.0, $this->moyenne(self::MATIERE, 'semestre1'));
    }

    public function test_annuler_la_seule_evaluation_signale_la_moyenne_sans_la_mettre_a_zero(): void
    {
        $seule = $this->evaluation(self::MATIERE, 'semestre1');
        $this->note($seule, 12);
        $this->resultat(self::MATIERE, 'semestre1', 12);

        $reponse = $this->actionDeLaListe('cancel', $seule);

        // Recalculer écrirait 0,00 : plus aucune note ne compte.
        $this->assertSame(12.0, $this->moyenne(self::MATIERE, 'semestre1'));
        $this->assertStringContainsString('désormais annulée', (string) $reponse->getData(true)['warning']);
    }

    public function test_l_avertissement_de_la_liste_mene_au_pre_controle_de_la_classe(): void
    {
        $seule = $this->evaluation(self::MATIERE, 'semestre1');
        $this->note($seule, 12);
        $this->resultat(self::MATIERE, 'semestre1', 12);
        $this->actingAs($this->utilisateur(autorise: true));

        $liens = $this->actionDeLaListe('cancel', $seule)->getData(true)['warning_links'];

        $this->assertCount(1, $liens);
        $this->assertStringStartsWith(route('esbtp.bulletins.select'), $liens[0]['url']);
        $this->assertStringContainsString('classe_id='.self::CLASSE, $liens[0]['url']);
        $this->assertStringContainsString('BTS GC 1', $liens[0]['libelle']);
    }

    public function test_sans_droit_sur_le_pre_controle_aucun_lien_n_est_rendu(): void
    {
        $seule = $this->evaluation(self::MATIERE, 'semestre1');
        $this->note($seule, 12);
        $this->resultat(self::MATIERE, 'semestre1', 12);
        $this->actingAs($this->utilisateur(autorise: false));

        $reponse = $this->actionDeLaListe('cancel', $seule)->getData(true);

        $this->assertSame([], $reponse['warning_links']);
        $this->assertStringContainsString('désormais annulée', (string) $reponse['warning']);
    }

    public function test_une_moyenne_laissee_par_une_annulation_est_journalisee(): void
    {
        $seule = $this->evaluation(self::MATIERE, 'semestre1');
        $this->note($seule, 12);
        $this->resultat(self::MATIERE, 'semestre1', 12);
        $journal = [];
        Event::listen(MessageLogged::class, function (MessageLogged $e) use (&$journal) {
            if ($e->level === 'warning') {
                $journal[] = $e->message;
            }
        });

        $this->actionDeLaListe('cancel', $seule);

        $this->assertCount(1, array_filter($journal, fn ($m) => str_contains($m, 'Changement de statut')));
    }

    public function test_un_recalcul_interrompu_n_annonce_pas_d_erreur_pour_une_annulation_acquise(): void
    {
        [, $annulee] = $this->deuxEvaluationsEtUneMoyenne();
        // La lecture des élèves notés lève : l'annulation, elle, est déjà écrite.
        Schema::drop('esbtp_notes');

        $reponse = $this->actionDeLaListe('cancel', $annulee);

        $this->assertTrue($reponse->getData(true)['success']);
        $this->assertStringContainsString('en échec', (string) $reponse->getData(true)['warning']);
        $this->assertSame('cancelled', DB::table('esbtp_evaluations')->where('id', $annulee)->value('status'));
    }

    public function test_reactiver_une_evaluation_remet_ses_notes_dans_la_moyenne(): void
    {
        $restante = $this->evaluation(self::MATIERE, 'semestre1');
        $annulee = $this->evaluation(self::MATIERE, 'semestre1', 'cancelled');
        $this->note($restante, 18);
        $this->note($annulee, 2);
        $this->resultat(self::MATIERE, 'semestre1', 18);

        $this->actionDeLaListe('restore', $annulee);

        $this->assertSame(10.0, $this->moyenne(self::MATIERE, 'semestre1'));
    }

    public function test_la_route_de_statut_recalcule_aussi_l_annulation(): void
    {
        [, $annulee] = $this->deuxEvaluationsEtUneMoyenne();

        $this->changerLeStatut($annulee, 'cancelled');

        $this->assertSame(18.0, $this->moyenne(self::MATIERE, 'semestre1'));
    }

    public function test_un_changement_de_statut_sans_annulation_ne_recalcule_rien(): void
    {
        [, $evaluation] = $this->deuxEvaluationsEtUneMoyenne();

        $this->changerLeStatut($evaluation, 'in_progress');

        $this->assertDatabaseCount('esbtp_resultats_recompute_log', 0);
    }

    // ── CheckEvaluationsAnnees (esbtp:check-evaluations-annees) ──────────

    public function test_donner_son_annee_a_une_evaluation_recalcule_la_moyenne_rejointe(): void
    {
        $datee = $this->evaluation(self::MATIERE, 'semestre1');
        $this->note($datee, 18);
        $this->resultat(self::MATIERE, 'semestre1', 18);
        $sansAnnee = $this->evaluation(self::MATIERE, 'semestre1');
        DB::table('esbtp_evaluations')->where('id', $sansAnnee)->update(['annee_universitaire_id' => null]);
        $this->note($sansAnnee, 14);
        DB::table('esbtp_inscriptions')->insert([
            'etudiant_id' => self::ETUDIANT, 'classe_id' => self::CLASSE, 'annee_universitaire_id' => self::ANNEE,
        ]);
        // Le reliquat de la classe désigne une autre année : il ne doit pas être lu.
        DB::table('esbtp_annee_universitaires')->insert(['id' => 2]);
        DB::table('esbtp_classes')->where('id', self::CLASSE)->update(['annee_universitaire_id' => 2]);

        $code = Artisan::call('esbtp:check-evaluations-annees', ['--fix' => true]);

        $this->assertSame(0, $code);
        $this->assertSame(self::ANNEE, (int) DB::table('esbtp_evaluations')->where('id', $sansAnnee)->value('annee_universitaire_id'));
        $this->assertSame(16.0, $this->moyenne(self::MATIERE, 'semestre1'));
    }

    public function test_deux_evaluations_d_une_meme_moyenne_ne_la_recalculent_qu_une_fois(): void
    {
        $premiere = $this->evaluation(self::MATIERE, 'semestre1');
        $seconde = $this->evaluation(self::MATIERE, 'semestre1');
        DB::table('esbtp_evaluations')->whereIn('id', [$premiere, $seconde])->update(['annee_universitaire_id' => null]);
        $this->note($premiere, 18);
        $this->note($seconde, 14);
        $this->resultat(self::MATIERE, 'semestre1', 18);
        DB::table('esbtp_inscriptions')->insert([
            'etudiant_id' => self::ETUDIANT, 'classe_id' => self::CLASSE, 'annee_universitaire_id' => self::ANNEE,
        ]);

        Artisan::call('esbtp:check-evaluations-annees', ['--fix' => true]);

        $this->assertSame(16.0, $this->moyenne(self::MATIERE, 'semestre1'));
        $this->assertStringContainsString('1 recalcul(s) de moyenne lancé(s)', Artisan::output());
        $this->assertDatabaseCount('esbtp_resultats_recompute_log', 1);
    }

    public function test_sans_inscription_l_annee_vient_de_la_date_et_sinon_rien_n_est_devine(): void
    {
        DB::table('esbtp_annee_universitaires')->where('id', self::ANNEE)
            ->update(['start_date' => '2026-09-01', 'end_date' => '2027-07-31']);
        $datee = $this->evaluation(self::MATIERE, 'semestre1');
        $horsAnnee = $this->evaluation(self::MATIERE, 'semestre1');
        DB::table('esbtp_evaluations')->where('id', $datee)
            ->update(['annee_universitaire_id' => null, 'date_evaluation' => '2026-10-05 08:00:00']);
        DB::table('esbtp_evaluations')->where('id', $horsAnnee)
            ->update(['annee_universitaire_id' => null, 'date_evaluation' => '2020-10-05 08:00:00']);

        Artisan::call('esbtp:check-evaluations-annees', ['--fix' => true]);

        $this->assertSame(self::ANNEE, (int) DB::table('esbtp_evaluations')->where('id', $datee)->value('annee_universitaire_id'));
        // L'ancienne version posait l'année courante : une supposition.
        $this->assertNull(DB::table('esbtp_evaluations')->where('id', $horsAnnee)->value('annee_universitaire_id'));
        $this->assertStringContainsString('#'.$horsAnnee, Artisan::output());
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

    /**
     * Par l'écran : `update()` enregistre la séance puis aligne son devoir,
     * dans une même transaction — c'est ce qu'on prouve.
     */
    private function modifierLaSeanceDeDevoir(int $evaluationId, int $matiereId, int $jour = 1)
    {
        Schema::create('esbtp_emploi_temps', function ($t) {
            $t->id();
            $t->unsignedBigInteger('classe_id');
            $t->string('semestre')->nullable();
            $t->unsignedBigInteger('annee_universitaire_id')->nullable();
            $t->date('date_debut')->nullable();
            $t->date('date_fin')->nullable();
            $t->boolean('is_active')->default(true);
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('esbtp_seance_cours', function ($t) {
            $t->id();
            foreach (['emploi_temps_id', 'classe_id', 'matiere_id', 'teacher_id', 'homework_evaluation_id', 'annee_universitaire_id'] as $colonne) {
                $t->unsignedBigInteger($colonne)->nullable();
            }
            $t->string('jour')->nullable();
            $t->time('heure_debut');
            $t->time('heure_fin');
            foreach (['salle', 'description', 'type', 'color', 'type_seance'] as $colonne) {
                $t->string($colonne)->nullable();
            }
            $t->text('homework_description')->nullable();
            $t->date('homework_due_date')->nullable();
            $t->date('date_seance')->nullable();
            $t->boolean('is_recurring')->default(false);
            $t->text('recurrence_days')->nullable();
            $t->integer('priority')->default(0);
            $t->boolean('is_active')->default(true);
            $t->softDeletes();
            $t->timestamps();
        });

        // Un lundi : le jour 1 tombe le 5 octobre, donc au semestre 1.
        DB::table('esbtp_emploi_temps')->insert([
            'id' => 50, 'classe_id' => self::CLASSE, 'annee_universitaire_id' => self::ANNEE,
            'date_debut' => '2026-10-05', 'is_active' => 1, 'semestre' => 'Semestre 1',
        ]);
        DB::table('esbtp_seance_cours')->insert([
            'id' => 500, 'emploi_temps_id' => 50, 'classe_id' => self::CLASSE, 'matiere_id' => self::MATIERE,
            'homework_evaluation_id' => $evaluationId, 'annee_universitaire_id' => self::ANNEE,
            'type' => ESBTPSeanceCours::TYPE_HOMEWORK, 'jour' => '1', 'date_seance' => '2026-10-05',
            'heure_debut' => '08:00:00', 'heure_fin' => '10:00:00', 'homework_description' => 'Devoir',
        ]);

        $requete = Request::create('/esbtp/seances-cours/500', 'PUT', [
            'jour' => $jour, 'heure_debut' => '08:00', 'heure_fin' => '10:00',
            'matiere_id' => $matiereId, 'homework_description' => 'Devoir',
            'homework_due_date' => '2099-01-01',
        ]);
        $requete->setLaravelSession(app('session.store'));
        app()->instance('request', $requete);

        return app(ESBTPSeanceCoursController::class)->update($requete, ESBTPSeanceCours::findOrFail(500));
    }

    /** Les boutons Annuler / Réactiver de la liste des évaluations. */
    private function actionDeLaListe(string $action, int $evaluationId)
    {
        $requete = Request::create('/esbtp/evaluations/'.$evaluationId.'/'.$action, 'PATCH');
        $requete->headers->set('Accept', 'application/json');

        return app(ESBTPEvaluationController::class)->{$action}($requete, ESBTPEvaluation::findOrFail($evaluationId));
    }

    private function changerLeStatut(int $evaluationId, string $statut)
    {
        $requete = Request::create('/esbtp/evaluations/'.$evaluationId.'/status', 'PATCH', ['status' => $statut]);
        $requete->headers->set('Accept', 'application/json');

        return app(ESBTPEvaluationController::class)->updateStatus($requete, ESBTPEvaluation::findOrFail($evaluationId));
    }

    /**
     * Un utilisateur qui répond lui-même à `can()` : le garde de Spatie, qui
     * passe avant tout `Gate::before()` du test, lit une table de
     * permissions que ce schéma ne porte pas.
     */
    private function utilisateur(bool $autorise): User
    {
        $utilisateur = new class extends User
        {
            public bool $autorise = true;

            public function can($abilities, $arguments = [])
            {
                return $this->autorise;
            }

            public function canAny($abilities, $arguments = [])
            {
                return $this->autorise;
            }
        };
        $utilisateur->autorise = $autorise;

        return $utilisateur->forceFill(['id' => 7, 'name' => 'Scolarité']);
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
