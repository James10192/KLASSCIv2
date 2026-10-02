<?php

namespace Tests\Feature\Performance;

use App\Domain\BtsTroncCommun\BtsAnnualClassMapResolver;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscriptionPhase;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
use App\Models\ESBTPUniteEnseignement;
use App\Models\User;
use App\Services\ESBTP\BtsCurrentResultSnapshotService;
use App\Services\RankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\Feature\Performance\Concerns\ConstruitUneClasseNotee;
use Tests\TestCase;

/**
 * La fiche etudiant affiche le rang de l'eleve dans sa classe. Ce rang se
 * calculait en relisant, pour CHAQUE camarade, ses notes, ses moyennes
 * enregistrees et la configuration des matieres : une quarantaine de requetes
 * par camarade. Mesure sur esbtp-abidjan (octobre 2026) : 8 a 9 s par fiche.
 *
 * Notes, moyennes et types de formation se lisent desormais une fois pour la
 * classe. Ce qui reste par camarade est la note d'assiduite (`BulletinService`,
 * `ESBTPAbsenceService`) : ces tests la bornent, et prouvent que le rang et les
 * moyennes sont ceux du calcul eleve par eleve.
 */
class FicheEtudiantRequetesTest extends TestCase
{
    use RefreshDatabase;
    use ConstruitUneClasseNotee;

    /** Tables dont la lecture ne doit plus suivre la taille de la classe. */
    private const TABLES_PAR_CLASSE = [
        'esbtp_notes', 'esbtp_resultats', 'esbtp_evaluations', 'esbtp_matieres',
        'esbtp_config_matieres', 'esbtp_matiere_filiere_niveau', 'esbtp_classes',
    ];

    /**
     * Ce qui reste lu par camarade, et pourquoi : la note d'assiduite du
     * classement (annee, absences, heures saisies, par semestre). Elle vit
     * dans `BulletinService`, hors du perimetre de ce correctif.
     */
    private const REQUETES_D_ASSIDUITE_PAR_CAMARADE = 6;

    private ESBTPAnneeUniversitaire $annee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('superAdmin', 'web'));
        $this->actingAs($user);

        $this->annee = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2025-2026', 'start_date' => '2025-09-01', 'end_date' => '2026-07-31', 'is_current' => true,
        ]);
    }

    public function test_la_fiche_ne_relit_plus_les_notes_de_chaque_camarade(): void
    {
        $classe = $this->classeNotee($this->annee, 2);
        $eleve = $classe['etudiants']->first();
        $this->ouvrirLaFiche($eleve); // rechauffe les caches de la requete, hors mesure

        $avec2 = $this->requetesParTable(fn () => $this->ouvrirLaFiche($eleve));
        $this->classeNotee($this->annee, 6, $classe['classe']);
        $avec8 = $this->requetesParTable(fn () => $this->ouvrirLaFiche($eleve));

        foreach (self::TABLES_PAR_CLASSE as $table) {
            $this->assertSame(
                $avec2[$table] ?? 0,
                $avec8[$table] ?? 0,
                "La fiche lit `{$table}` une fois par camarade de classe."
            );
        }

        // `settings` est mis de cote : un reglage absent n'est pas mis en cache
        // (Setting::get), ce qui releve d'un autre correctif.
        $total = fn (array $parTable) => array_sum($parTable) - ($parTable['settings'] ?? 0);
        $this->assertLessThanOrEqual(
            6 * self::REQUETES_D_ASSIDUITE_PAR_CAMARADE,
            $total($avec8) - $total($avec2),
            'Six camarades de plus ne doivent couter que leur note d\'assiduite.'
        );
    }

    /**
     * La cohorte melange expres ce que le prechargement doit trier eleve par
     * eleve : un oriente du tronc commun (classe du S1 differente de celle du
     * S2) a cote de camarades restes en specialite, dont l'un porte une note
     * et une moyenne egarees dans le tronc commun ; une ECUE effacee en douceur
     * et notee dans la classe BTS ; une moyenne enregistree sur une ECUE ; une
     * evaluation annulee ; une evaluation d'une autre annee. Le calcul groupe
     * doit rendre exactement le calcul unitaire.
     */
    public function test_le_rang_et_les_moyennes_sont_ceux_du_calcul_eleve_par_eleve(): void
    {
        $classe = $this->classeNotee($this->annee, 7);
        $specialite = $classe['classe'];
        $camarades = $classe['etudiants'];
        $matiere = $classe['matieres']->first();
        $classeId = (int) $specialite->id;

        $troncCommun = $this->classeBts();
        $oriente = $this->etudiantOriente($specialite, $troncCommun, $matiere);
        $this->camaradeEgareDansLeTroncCommun($camarades->first(), $troncCommun, $matiere);
        $this->bruitQueLeCalculDoitEcarter($specialite, $camarades->get(1), $matiere);

        $ids = $camarades->pluck('id')->push($oriente->id)->map(fn ($id) => (int) $id)->all();
        $service = fn () => app(BtsCurrentResultSnapshotService::class);

        // Chaque calcul part d'un conteneur neuf : `BtsAnnualClassMapResolver`,
        // `BtsPhaseResolver` et le compteur de cohorte sont `scoped`, donc
        // partages par tous les appels d'un meme test. Sans cela, le calcul
        // unitaire relirait la carte des classes que le calcul groupe a deja
        // posee (`prechargerPourCohorte()`), et ne la confronterait jamais a
        // celle que `resolveUncached()` aurait elue.
        $this->app->forgetScopedInstances();
        $this->assertNotSame(
            $classeId,
            (int) app(BtsAnnualClassMapResolver::class)
                ->resolve($oriente->id, $classeId, $this->annee->id)['semestre1_classe_id'],
            'Le decor doit orienter l\'eleve : son semestre 1 est en tronc commun.'
        );

        foreach (['annuel', 'semestre1', 'semestre2'] as $periode) {
            $this->app->forgetScopedInstances();
            $groupes = $service()->getPeriodeSnapshotsPourCohorte($ids, $classeId, $this->annee->id, $periode);
            foreach ($ids as $id) {
                $this->app->forgetScopedInstances();
                $this->assertSame(
                    $service()->getPeriodeSnapshot($id, $classeId, $this->annee->id, $periode),
                    $groupes[$id],
                    "Snapshot {$periode} different pour l'etudiant {$id}."
                );
            }
        }

        // Le rang suit la moyenne affichee : on le recalcule a la main.
        $attendus = collect($ids)
            ->mapWithKeys(fn ($id) => [$id => $service()
                ->getPeriodeSnapshot($id, $classeId, $this->annee->id, 'annuel')['effective_total']])
            ->sortDesc();
        $rangs = app(RankingService::class)->calculerRangsClasse($classeId, $this->annee->id, 'annuel');
        $this->assertSame(count($ids), $rangs['total']);
        foreach ($rangs['rows'] as $ligne) {
            $mieux = $attendus->filter(fn ($moyenne) => $moyenne > $attendus[$ligne['etudiant_id']])->count();
            $this->assertSame($mieux + 1, $ligne['rang'], "Rang faux pour l'etudiant {$ligne['etudiant_id']}.");
        }
    }

    /** Inscrit en specialite, semestre 1 en tronc commun, note des deux cotes. */
    private function etudiantOriente(ESBTPClasse $specialite, ESBTPClasse $troncCommun, ESBTPMatiere $matiere): ESBTPEtudiant
    {
        $etudiant = ESBTPEtudiant::factory()->create();
        $inscription = $this->inscrire($etudiant, $specialite, $this->annee);
        foreach ([[$troncCommun, 'tronc_commun', 1, 1, false], [$specialite, 'specialisation', 2, null, true]] as [$c, $type, $debut, $fin, $actif]) {
            ESBTPInscriptionPhase::create([
                'inscription_id' => $inscription->id,
                'type_phase' => $type,
                'classe_id' => $c->id,
                'filiere_id' => $c->filiere_id,
                'semestre_debut' => $debut,
                'semestre_fin' => $fin,
                'is_active' => $actif,
            ]);
        }
        $this->noterDans($etudiant, $this->evaluation($troncCommun, $matiere, 'semestre1'), 17);
        $this->noterDans($etudiant, $this->evaluation($specialite, $matiere, 'semestre2'), 9);

        return $etudiant;
    }

    /**
     * Note et moyenne d'un eleve NON oriente, posees dans le tronc commun : son
     * calcul unitaire ne les lit pas, le prechargement de la cohorte (qui lit
     * le tronc commun pour l'oriente) doit donc les lui retirer.
     */
    private function camaradeEgareDansLeTroncCommun(ESBTPEtudiant $etudiant, ESBTPClasse $troncCommun, ESBTPMatiere $matiere): void
    {
        $this->noterDans($etudiant, $this->evaluation($troncCommun, $matiere, 'semestre1'), 2);
        ESBTPResultat::create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $troncCommun->id,
            'matiere_id' => $matiere->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'moyenne' => 1,
            'coefficient' => 1,
        ]);
    }

    /** ECUE effacee notee, moyenne sur une ECUE, evaluation annulee, autre annee. */
    private function bruitQueLeCalculDoitEcarter(ESBTPClasse $classe, ESBTPEtudiant $etudiant, ESBTPMatiere $matiere): void
    {
        $ue = ESBTPUniteEnseignement::create([
            'name' => 'UE perf', 'code' => 'UE-PERF', 'credit' => 6, 'semestre' => 1, 'is_active' => true,
        ]);
        $ecue = ESBTPMatiere::factory()->create(['unite_enseignement_id' => $ue->id, 'is_active' => true]);

        // Les gardes refusent aujourd'hui ces ecritures : on reproduit un heritage.
        $evaluationEcue = ESBTPEvaluation::withoutEvents(fn () => $this->evaluation($classe, $ecue, 'semestre1'));
        $this->noterDans($etudiant, $evaluationEcue, 3);
        ESBTPResultat::withoutEvents(fn () => ESBTPResultat::create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $classe->id,
            'matiere_id' => $ecue->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre2',
            'moyenne' => 4,
            'coefficient' => 1,
        ]));
        $ecue->delete();

        $annulee = $this->evaluation($classe, $matiere, 'semestre1');
        $annulee->update(['status' => 'cancelled']);
        $this->noterDans($etudiant, $annulee, 0.5);

        $autreAnnee = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2024-2025', 'start_date' => '2024-09-01', 'end_date' => '2025-07-31', 'is_current' => false,
        ]);
        $this->noterDans($etudiant, $this->evaluation($classe, $matiere, 'semestre2', $autreAnnee), 1);
    }

    private function evaluation(ESBTPClasse $classe, ESBTPMatiere $matiere, string $periode, ?ESBTPAnneeUniversitaire $annee = null): ESBTPEvaluation
    {
        return ESBTPEvaluation::factory()->create([
            'matiere_id' => $matiere->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => ($annee ?? $this->annee)->id,
            'periode' => $periode,
            'coefficient' => 1,
            'bareme' => 20,
            'status' => 'completed',
        ]);
    }

    private function noterDans(ESBTPEtudiant $etudiant, ESBTPEvaluation $evaluation, float $note): void
    {
        ESBTPNote::factory()->create([
            'evaluation_id' => $evaluation->id,
            'etudiant_id' => $etudiant->id,
            'matiere_id' => $evaluation->matiere_id,
            'classe_id' => $evaluation->classe_id,
            'annee_universitaire' => $this->annee->name,
            'note' => $note,
            'valeur' => $note,
            'is_absent' => false,
        ]);
    }

    private function ouvrirLaFiche($etudiant): void
    {
        $this->get(route('esbtp.etudiants.show', $etudiant))->assertOk();
    }

    /** @return array<string, int> nombre de requetes par table lue */
    private function requetesParTable(callable $operation): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            $operation();
        } finally {
            $log = DB::getQueryLog();
            DB::disableQueryLog();
        }

        return collect($log)
            ->map(fn ($q) => preg_match('/from `([a-z_]+)`/', $q['query'], $m) ? $m[1] : 'autre')
            ->countBy()
            ->all();
    }
}
