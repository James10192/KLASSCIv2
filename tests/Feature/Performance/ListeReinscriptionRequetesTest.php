<?php

namespace Tests\Feature\Performance;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use App\Models\ESBTPRegleAcademique;
use App\Models\ESBTPUniteEnseignement;
use App\Models\User;
use App\Services\ReeinscriptionService;
use App\Services\Reinscription\ReinscriptionDashboardStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\Feature\Performance\Concerns\ConstruitUneClasseNotee;
use Tests\TestCase;

/**
 * La liste de reinscription (/esbtp/reinscription, onglets charges par
 * `load-category`) relisait les notes et la regle academique etudiant par
 * etudiant : six requetes par etudiant, pour chaque onglet. Mesure sur
 * esbtp-abidjan (2000 inscrits, octobre 2026) : 10,4 s.
 *
 * Ces tests tiennent deux choses ensemble : le nombre de requetes ne suit plus
 * la taille de la promotion, et la decision rendue reste celle de l'analyse
 * etudiant par etudiant.
 */
class ListeReinscriptionRequetesTest extends TestCase
{
    use RefreshDatabase;
    use ConstruitUneClasseNotee;

    private ESBTPAnneeUniversitaire $precedente;

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

        $this->precedente = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2024-2025', 'start_date' => '2024-09-01', 'end_date' => '2025-07-31', 'is_current' => false,
        ]);
        ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2025-2026', 'start_date' => '2025-09-01', 'end_date' => '2026-07-31', 'is_current' => true,
        ]);
    }

    /** Tables dont la lecture ne doit pas suivre la taille de la promotion. */
    private const TABLES_PAR_PROMOTION = [
        'esbtp_notes', 'esbtp_resultats', 'esbtp_evaluations', 'esbtp_bulletins',
        'esbtp_inscriptions', 'esbtp_inscription_phases', 'esbtp_regles_academiques', 'esbtp_classes',
    ];

    /**
     * Ce qui suit le nombre de MATIERES de la classe, pas d'eleves : le
     * second appel a `classeNotee()` cree deux matieres de plus.
     */
    private const TABLES_PAR_MATIERE = ['esbtp_matieres', 'esbtp_config_matieres', 'esbtp_matiere_filiere_niveau'];

    /**
     * Ce qui reste lu par eleve quand AUCUN bulletin n'est enregistre : la note
     * d'assiduite du calcul courant de chaque semestre (annee, absences,
     * heures saisies — trois requetes par semestre), dans `BulletinService`.
     * C'est le prix de decider sur la moyenne annuelle du bulletin plutot que
     * sur une moyenne simple des notes ; la fiche etudiant le paie deja
     * (`FicheEtudiantRequetesTest`). Bulletins generes, il tombe a zero : voir
     * le test suivant.
     */
    private const REQUETES_D_ASSIDUITE_PAR_ETUDIANT = 6;

    public function test_les_onglets_ne_relisent_ni_notes_ni_regles_par_etudiant(): void
    {
        $classe = $this->classeNotee($this->precedente, 2)['classe'];
        $this->ouvrirLesOnglets(); // rechauffe les caches de la requete, hors mesure

        $avec2 = $this->requetesParTable(fn () => $this->analyserLaPromotion());

        $this->classeNotee($this->precedente, 6, $classe);
        $avec8 = $this->requetesParTable(fn () => $this->analyserLaPromotion());
        $this->ouvrirLesOnglets();

        foreach (self::TABLES_PAR_PROMOTION as $table) {
            $this->assertSame(
                $avec2[$table] ?? 0,
                $avec8[$table] ?? 0,
                "La liste lit `{$table}` une fois par etudiant."
            );
        }

        $total = fn (array $parTable) => array_sum(array_diff_key(
            $parTable,
            array_flip([...self::TABLES_PAR_MATIERE, 'settings'])
        ));
        $this->assertLessThanOrEqual(
            6 * self::REQUETES_D_ASSIDUITE_PAR_ETUDIANT,
            $total($avec8) - $total($avec2),
            "Six etudiants de plus ne doivent couter que leur note d'assiduite."
        );
    }

    public function test_bulletins_generes_la_liste_ne_coute_rien_de_plus_par_etudiant(): void
    {
        $premiers = $this->classeNotee($this->precedente, 2);
        $this->genererLesBulletins($premiers['classe'], $premiers['etudiants']);
        $this->ouvrirLesOnglets();

        $avec2 = $this->requetesDe(fn () => $this->analyserLaPromotion());

        $suivants = $this->classeNotee($this->precedente, 6, $premiers['classe']);
        $this->genererLesBulletins($premiers['classe'], $suivants['etudiants']);
        $avec8 = $this->requetesDe(fn () => $this->analyserLaPromotion());

        $this->assertSame(
            $avec2,
            $avec8,
            'Bulletins enregistres, la moyenne annuelle se lit en une requete par classe : '
            .'une difference signifie qu\'une lecture est repassee par etudiant.'
        );
    }

    private function genererLesBulletins(ESBTPClasse $classe, $etudiants): void
    {
        foreach ($etudiants as $etudiant) {
            foreach (['semestre1' => 11.0, 'semestre2' => 9.5] as $periode => $moyenne) {
                ESBTPBulletin::factory()->create([
                    'etudiant_id' => $etudiant->id,
                    'classe_id' => $classe->id,
                    'annee_universitaire_id' => $this->precedente->id,
                    'periode' => $periode,
                    'moyenne_generale' => $moyenne,
                    'note_assiduite' => 0,
                ]);
            }
        }
    }

    /** @return array<string, int> */
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

    public function test_la_decision_reste_celle_de_l_analyse_etudiant_par_etudiant(): void
    {
        $classeRegle = $this->classeBts();
        ESBTPRegleAcademique::create([
            'niveau' => $classeRegle->niveau->name,
            'filiere' => $classeRegle->filiere->name,
            'moyenne_passage' => 10,
            'moyenne_rattrapage' => 7,
            'max_matieres_rattrapage' => 1,
            'autoriser_redoublement' => true,
            'max_redoublements' => 2,
            'actif' => true,
        ]);
        $this->classeNotee($this->precedente, 5, $classeRegle);
        $autre = $this->classeNotee($this->precedente, 5);

        // Une ECUE du LMD notee 2/20 dans la classe BTS : elle ne doit pas
        // peser, par le chemin groupe comme par le chemin unitaire.
        $etudiantEcue = $autre['etudiants']->first();
        $this->noterUneEcue($etudiantEcue, $autre['classe'], 2.0);
        $this->assertTrue(
            ESBTPNote::where('etudiant_id', $etudiantEcue->id)->whereHas('matiere', fn ($q) => $q->where('code', 'TPGC641'))->exists(),
            "La note d'ECUE doit exister en base, sinon le test ne prouve rien."
        );

        $service = app(ReeinscriptionService::class);
        $resultat = $service->getEtudiantsParDecision('2025-2026');

        $analyses = collect(['passages', 'rattrapages', 'redoublements'])
            ->flatMap(fn ($categorie) => $resultat[$categorie]);
        $this->assertCount(10, $analyses, 'Chaque etudiant de N-1 doit recevoir une decision.');

        foreach ($analyses as $analyse) {
            $unitaire = app(ReeinscriptionService::class)
                ->analyserSituationEtudiantParInscription($analyse['inscription'], '2025-2026');

            $this->assertSame($unitaire['decision'], $analyse['decision']);
            $this->assertEqualsWithDelta($unitaire['moyenne_generale'], $analyse['moyenne_generale'], 0.0);
            $this->assertSame(
                $unitaire['matieres_echouees']->pluck('matiere.id')->all(),
                $analyse['matieres_echouees']->pluck('matiere.id')->all()
            );
            $this->assertSame($unitaire['notes']->pluck('id')->all(), $analyse['notes']->pluck('id')->all());
            $this->assertSame((float) $unitaire['regle']->moyenne_passage, (float) $analyse['regle']->moyenne_passage);
        }

        // Les compteurs de la page lisent les memes notes par le meme chargeur :
        // ils doivent annoncer ce que les onglets listent.
        $compteurs = app(ReinscriptionDashboardStats::class)->calculate();
        foreach (['passages', 'rattrapages', 'redoublements'] as $categorie) {
            $this->assertSame(count($resultat[$categorie]), $compteurs[$categorie], "Compteur {$categorie}.");
        }

        $analyseEcue = $analyses->first(fn ($a) => $a['etudiant']->id === $etudiantEcue->id);
        $this->assertNotContains(
            'OGC',
            $analyseEcue['notes']->map(fn ($n) => $n->matiere?->name)->all(),
            "L'ECUE du LMD ne doit pas entrer dans la decision BTS."
        );
    }

    /**
     * Ce que chaque onglet de la liste refait : l'analyse de toute la promotion.
     * Le solde, lui, ne se calcule que pour les lignes de la page affichee
     * (50 au plus) : son cout ne suit pas la taille de l'ecole.
     */
    private function analyserLaPromotion(): void
    {
        app(ReeinscriptionService::class)->getEtudiantsParDecision('2025-2026');
    }

    private function ouvrirLesOnglets(): void
    {
        foreach (['passages', 'rattrapages', 'redoublements', 'errors'] as $categorie) {
            $this->getJson(route('esbtp.reinscription.load-category', $categorie))->assertOk();
        }
    }

    private function noterUneEcue(ESBTPEtudiant $etudiant, ESBTPClasse $classe, float $note): void
    {
        $ue = ESBTPUniteEnseignement::create([
            'name' => 'UE Ouvrages', 'code' => 'UE-TPGC641', 'credit' => 6, 'semestre' => 1, 'is_active' => true,
        ]);
        $ecue = ESBTPMatiere::factory()->create([
            'name' => 'OGC', 'code' => 'TPGC641', 'is_active' => true, 'unite_enseignement_id' => $ue->id,
        ]);

        ESBTPNote::create([
            'etudiant_id' => $etudiant->id,
            'matiere_id' => $ecue->id,
            'evaluation_id' => null,
            'classe_id' => $classe->id,
            'annee_universitaire' => $this->precedente->name,
            'note' => $note,
            'is_absent' => false,
        ]);
    }
}
