<?php

namespace Tests\Feature\Bts;

use App\Domain\AcademicPilotage\Services\BtsBulkBulletinGenerationService;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * Un bulletin qui existe sans moyenne n'est pas un travail fait.
 *
 * Il etait ecarte comme « deja genere ». Sur TRONC COMMUN F d'esbtp-yakro, les
 * soixante-dix bulletins de la classe existaient sans aucune moyenne, l'ecran
 * annoncait « rien a generer », et l'ecole pouvait croire le travail fait. Sur
 * TRONC COMMUN L, soixante-neuf vides sur soixante-dix passaient meme sous un
 * ecran vert : un seul etudiant restait generable, donc le statut etait `ready`
 * et les autres etaient silencieusement sautes.
 *
 * Ces cas se jouent dans le tri du pre-controle, pas dans le statut d'ecran :
 * ce test pose de vrais bulletins et regarde ou ils atterrissent.
 */
class PreflightBulletinVideTest extends TestCase
{
    use MonteUneClasseBts, RefreshDatabase;

    private ESBTPMatiere $matiere;

    private ESBTPEvaluation $evaluation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monterLaClasse();

        // Une classe generable, c'est une matiere configuree, notee et
        // coefficientee : sans ces trois, le pre-controle bloque avant meme
        // d'avoir a trancher le sort du bulletin existant.
        $this->matiere = $this->matiereConfiguree();
        $this->evaluation = $this->evaluationDe($this->matiere);
    }

    private function etudiantAvecBulletin(?float $moyenne, bool $publie = false, bool $avecNote = true): ESBTPEtudiant
    {
        $etudiant = $this->etudiantInscrit();

        if ($avecNote) {
            $this->noter($etudiant, $this->evaluation);
        }

        $bulletin = ESBTPBulletin::factory()->create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'moyenne_generale' => $moyenne,
            'is_published' => $publie,
        ]);
        // Le professeur est lu depuis n'importe quel bulletin de la classe :
        // sans lui, la classe entiere serait bloquee avant le tri.
        $bulletin->professeurs = json_encode([$this->matiere->id => 'M. Kone']);
        $bulletin->save();

        return $etudiant;
    }

    private function preControle(): array
    {
        return app(BtsBulkBulletinGenerationService::class)
            ->preflight($this->classe, (int) $this->annee->id, 'semestre1', null);
    }

    private function motifPour(array $preflight, int $etudiantId): ?string
    {
        $ligne = collect($preflight['skipped'] ?? [])->firstWhere('student_id', $etudiantId);

        return $ligne['code'] ?? null;
    }

    private function blocagePour(array $preflight, int $etudiantId): ?string
    {
        $ligne = collect($preflight['blocking_errors'] ?? [])->firstWhere('student_id', $etudiantId);

        return $ligne['code'] ?? null;
    }

    public function test_un_bulletin_vide_est_a_refaire_et_non_ecarte(): void
    {
        $etudiant = $this->etudiantAvecBulletin(null);

        $preflight = $this->preControle();

        $this->assertNull($this->motifPour($preflight, (int) $etudiant->id),
            'Un bulletin sans moyenne ne doit pas etre ecarte comme deja fait.');
        $this->assertNull($this->blocagePour($preflight, (int) $etudiant->id));
        $this->assertSame(1, $preflight['generatable_count']);
        $this->assertSame(1, $preflight['existing_empty_count']);
        $this->assertSame('ready', $preflight['status']);
    }

    public function test_un_bulletin_rempli_reste_ecarte(): void
    {
        $etudiant = $this->etudiantAvecBulletin(12.5);

        $preflight = $this->preControle();

        $this->assertSame('bulletin_exists', $this->motifPour($preflight, (int) $etudiant->id));
        $this->assertSame(0, $preflight['generatable_count']);
        $this->assertSame(0, $preflight['existing_empty_count']);
        $this->assertSame('nothing_to_generate', $preflight['status']);
    }

    /**
     * Un bulletin est souvent vide PARCE QUE les donnees manquent. L'annonce
     * « ils seront repris » ne doit couvrir que ceux qui franchissent tous les
     * controles : promettre les autres gonflerait le nombre a chaque classe
     * mixte, et rien ne serait repris.
     */
    public function test_un_vide_sans_note_n_est_pas_promis_a_l_ecran(): void
    {
        $sansNote = $this->etudiantAvecBulletin(null, avecNote: false);

        $preflight = $this->preControle();

        $this->assertSame('incomplete_academic_data', $this->blocagePour($preflight, (int) $sansNote->id));
        $this->assertSame(0, $preflight['generatable_count']);
        $this->assertSame(0, $preflight['existing_empty_count'],
            'Un bulletin vide bloque ne sera pas repris : il ne doit pas etre promis.');
    }

    /**
     * Le cas qui manquait : un seul generable rendait l'ecran vert et les vides
     * partaient a la trappe.
     */
    public function test_un_seul_generable_ne_fait_plus_oublier_les_vides(): void
    {
        $vide = $this->etudiantAvecBulletin(null);
        $autre = $this->etudiantAvecBulletin(null);

        $preflight = $this->preControle();

        $this->assertSame('ready', $preflight['status']);
        $this->assertSame(2, $preflight['generatable_count']);
        foreach ([$vide, $autre] as $etudiant) {
            $this->assertNull($this->motifPour($preflight, (int) $etudiant->id));
        }
    }

    /**
     * Un bulletin vide mais publie ne peut pas etre repris. Le pre-controle et
     * la generation doivent le classer PAREIL : sinon l'ecran l'ecarte sans
     * bruit pendant que la generation en fait un blocage dur, et la boucle du
     * front s'arrete sur la tranche qui le contient.
     */
    public function test_un_bulletin_vide_mais_publie_est_classe_pareil_des_deux_cotes(): void
    {
        $etudiant = $this->etudiantAvecBulletin(null, publie: true);

        $preflight = $this->preControle();
        $this->assertSame('bulletin_exists_empty_locked', $this->motifPour($preflight, (int) $etudiant->id));
        $this->assertSame(0, $preflight['generatable_count']);
        $this->assertSame(0, $preflight['existing_empty_count'], 'Un verrouille ne sera pas repris : il ne doit pas etre promis.');
        $this->assertSame(1, $preflight['existing_empty_locked_count']);

        $resultat = app(BtsBulkBulletinGenerationService::class)
            ->generate($this->classe, (int) $this->annee->id, 'semestre1', null);

        $bloques = collect($resultat->blockingErrors ?? [])->pluck('student_id')->all();
        $this->assertNotContains((int) $etudiant->id, $bloques,
            'La generation ne doit pas en faire un blocage dur : la tranche entiere s arreterait.');
    }
}
