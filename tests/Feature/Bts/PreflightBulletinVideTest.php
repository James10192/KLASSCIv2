<?php

namespace Tests\Feature\Bts;

use App\Domain\AcademicPilotage\Services\BtsBulkBulletinGenerationService;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPConfigMatiere;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereCoefficient;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPNote;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    use RefreshDatabase;

    private ESBTPClasse $classe;

    private ESBTPAnneeUniversitaire $annee;

    private ESBTPMatiere $matiere;

    private ESBTPEvaluation $evaluation;

    protected function setUp(): void
    {
        parent::setUp();

        // La fabrique d evaluation signe `created_by = 1` en dur. L identifiant
        // est donc impose : l auto-increment ne repart pas de un entre deux
        // tests, et un auteur cree librement porterait le numero suivant.
        \App\Models\User::factory()->create(['id' => 1]);

        $this->annee = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $filiere = ESBTPFiliere::factory()->create(['is_tronc_commun' => true, 'parent_id' => null]);
        $this->classe = ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $this->annee->id,
        ]);

        // Une classe generable, c'est une matiere configuree, notee et
        // coefficientee : sans ces trois, le pre-controle bloque avant meme
        // d'avoir a trancher le sort du bulletin existant.
        $this->matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);
        ESBTPConfigMatiere::create([
            'matiere_id' => $this->matiere->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'config' => ['type' => 'general', 'coefficient' => 2],
        ]);
        ESBTPMatiereCoefficient::create([
            'matiere_id' => $this->matiere->id,
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'coefficient' => 2,
        ]);
        $this->evaluation = ESBTPEvaluation::factory()->create([
            'matiere_id' => $this->matiere->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'status' => 'published',
            'bareme' => 20,
            'coefficient' => 1,
        ]);
    }

    private function etudiantAvecBulletin(?float $moyenne, bool $publie = false, bool $avecNote = true): ESBTPEtudiant
    {
        $etudiant = ESBTPEtudiant::factory()->create();
        ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
        ]);

        if ($avecNote) {
            // Note posee sans la fabrique : celle-ci ecrit une colonne
            // `observation` que la table ne porte plus.
            ESBTPNote::create([
                'evaluation_id' => $this->evaluation->id,
                'etudiant_id' => $etudiant->id,
                'matiere_id' => $this->matiere->id,
                'classe_id' => $this->classe->id,
                'note' => 13,
                'is_absent' => false,
            ]);
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
