<?php

namespace Tests\Feature\Performance;

use App\Models\ESBTPAnneeUniversitaire;
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

    public function test_les_onglets_ne_coutent_pas_une_requete_de_plus_par_etudiant(): void
    {
        $classe = $this->classeNotee($this->precedente, 2)['classe'];
        $this->ouvrirLesOnglets(); // rechauffe les caches de la requete, hors mesure

        $avec2 = $this->requetesDe(fn () => $this->analyserLaPromotion());

        $this->classeNotee($this->precedente, 6, $classe);
        $avec8 = $this->requetesDe(fn () => $this->analyserLaPromotion());
        $this->ouvrirLesOnglets();

        $this->assertSame(
            $avec2,
            $avec8,
            "La liste doit couter autant de requetes pour 8 etudiants que pour 2. "
            .'Une difference signifie que les notes ou la regle se relisent de nouveau par etudiant.'
        );
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
