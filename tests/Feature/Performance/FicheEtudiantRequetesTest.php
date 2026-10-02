<?php

namespace Tests\Feature\Performance;

use App\Models\ESBTPAnneeUniversitaire;
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

    public function test_le_rang_et_les_moyennes_sont_ceux_du_calcul_eleve_par_eleve(): void
    {
        $classe = $this->classeNotee($this->annee, 7);
        $ids = $classe['etudiants']->pluck('id')->all();
        $classeId = $classe['classe']->id;

        $groupes = app(BtsCurrentResultSnapshotService::class)
            ->getPeriodeSnapshotsPourCohorte($ids, $classeId, $this->annee->id, 'annuel');

        foreach ($ids as $id) {
            $unitaire = app(BtsCurrentResultSnapshotService::class)
                ->getPeriodeSnapshot($id, $classeId, $this->annee->id, 'annuel');
            $this->assertSame($unitaire, $groupes[$id], "Snapshot different pour l'etudiant {$id}.");
        }

        foreach (['semestre1', 'semestre2'] as $semestre) {
            $groupes = app(BtsCurrentResultSnapshotService::class)
                ->getPeriodeSnapshotsPourCohorte($ids, $classeId, $this->annee->id, $semestre);
            foreach ($ids as $id) {
                $this->assertSame(
                    app(BtsCurrentResultSnapshotService::class)->getPeriodeSnapshot($id, $classeId, $this->annee->id, $semestre),
                    $groupes[$id]
                );
            }
        }

        // Le rang suit la moyenne affichee : on le recalcule a la main.
        $attendus = collect($ids)
            ->mapWithKeys(fn ($id) => [$id => app(BtsCurrentResultSnapshotService::class)
                ->getPeriodeSnapshot($id, $classeId, $this->annee->id, 'annuel')['effective_total']])
            ->sortDesc();
        $rangs = app(RankingService::class)->calculerRangsClasse($classeId, $this->annee->id, 'annuel');
        $this->assertSame(7, $rangs['total']);
        foreach ($rangs['rows'] as $ligne) {
            $mieux = $attendus->filter(fn ($moyenne) => $moyenne > $attendus[$ligne['etudiant_id']])->count();
            $this->assertSame($mieux + 1, $ligne['rang'], "Rang faux pour l'etudiant {$ligne['etudiant_id']}.");
        }
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
