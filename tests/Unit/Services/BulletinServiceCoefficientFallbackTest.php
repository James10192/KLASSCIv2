<?php

namespace Tests\Unit\Services;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPhase;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereCoefficient;
use App\Models\ESBTPNiveauEtude;
use App\Services\BulletinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests du fallback Tronc Commun (P1-a) dans
 * BulletinService::getCoefficientForCombination().
 *
 * Pour un étudiant orienté TC → spécialité, les matières du Semestre 1 portent leur
 * coefficient sur la classe TC (filière TC parente), pas sur la classe de spécialité
 * demandée. Quand on passe $etudiantId, le service résout la classe S1 via le
 * BtsAnnualClassMapResolver et cherche le coefficient là-bas.
 *
 * BTS uniquement. DB klassci_testing (RefreshDatabase).
 */
class BulletinServiceCoefficientFallbackTest extends TestCase
{
    use RefreshDatabase;

    private BulletinService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BulletinService::class);
    }

    /** @test */
    public function it_resolves_tc_coefficient_when_etudiant_id_passed(): void
    {
        [$matiere, $tcClasse, $specClasse, $annee, $inscription] = $this->makeOrientedJourneyWithTcCoefficient(4.0);

        // Coefficient demandé sur la classe de SPÉCIALITÉ (pas de ligne coef dessus),
        // mais avec $etudiantId → fallback résout la classe TC et trouve 4.0.
        $coef = $this->service->getCoefficientForCombination(
            $matiere->id,
            $specClasse->id,
            $annee->id,
            'semestre1',
            $inscription->etudiant_id
        );

        $this->assertSame(4.0, $coef);
    }

    /** @test */
    public function it_throws_when_no_etudiant_id_even_if_tc_coefficient_exists(): void
    {
        [$matiere, $tcClasse, $specClasse, $annee] = $this->makeOrientedJourneyWithTcCoefficient(4.0);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Coefficient manquant');

        // Sans $etudiantId → pas de fallback TC → coef introuvable sur la classe spé.
        $this->service->getCoefficientForCombination(
            $matiere->id,
            $specClasse->id,
            $annee->id,
            'semestre1'
        );
    }

    /** @test */
    public function it_throws_when_tc_classe_equals_requested_classe(): void
    {
        // Étudiant TC pur : la classe S1 résolue est la classe demandée elle-même.
        // Aucun fallback distinct ne s'applique → coef introuvable doit throw.
        [$annee, $niveau, $tcFiliere] = $this->makeAcademicContext();
        $tcClasse = ESBTPClasse::factory()->create([
            'filiere_id' => $tcFiliere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $matiere = ESBTPMatiere::factory()->create();
        $etudiant = ESBTPEtudiant::factory()->create();

        $inscription = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $tcFiliere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $tcClasse->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'tronc_commun',
            'classe_id' => $tcClasse->id,
            'filiere_id' => $tcFiliere->id,
            'semestre_debut' => 1,
            'semestre_fin' => 2,
            'is_active' => true,
        ]);

        // Aucun coefficient créé du tout → throw quel que soit le fallback.
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Coefficient manquant');

        $this->service->getCoefficientForCombination(
            $matiere->id,
            $tcClasse->id,
            $annee->id,
            'semestre1',
            $etudiant->id
        );
    }

    /** @test */
    public function cache_key_includes_etudiant_id(): void
    {
        // Même (matiere, classe spé, annee, periode) mais $etudiantId distinct :
        // - sans $etudiantId → throw (pas de fallback)
        // - avec $etudiantId → 4.0 (fallback TC)
        // Si la clé de cache n'incluait PAS $etudiantId, le 1er appel polluerait/
        // partagerait l'entrée avec le 2e et l'on n'observerait pas deux résultats
        // distincts. On valide donc que les deux chemins coexistent correctement.
        [$matiere, $tcClasse, $specClasse, $annee, $inscription] = $this->makeOrientedJourneyWithTcCoefficient(4.0);

        // 1) avec etudiantId : met en cache l'entrée "...|{etudiantId}"
        $withEtudiant = $this->service->getCoefficientForCombination(
            $matiere->id,
            $specClasse->id,
            $annee->id,
            'semestre1',
            $inscription->etudiant_id
        );
        $this->assertSame(4.0, $withEtudiant);

        // 2) sans etudiantId : entrée de cache "...|" séparée → doit toujours throw,
        //    prouvant que le cache n'a PAS réutilisé l'entrée avec etudiantId.
        $threw = false;
        try {
            $this->service->getCoefficientForCombination(
                $matiere->id,
                $specClasse->id,
                $annee->id,
                'semestre1'
            );
        } catch (\RuntimeException $e) {
            $threw = str_contains($e->getMessage(), 'Coefficient manquant');
        }

        $this->assertTrue($threw, 'La clé de cache doit inclure etudiantId : sans lui, le coef reste introuvable.');
    }

    /**
     * @return array{0: ESBTPAnneeUniversitaire, 1: ESBTPNiveauEtude, 2: ESBTPFiliere}
     */
    private function makeAcademicContext(): array
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $tcFiliere = ESBTPFiliere::factory()->create(['is_tronc_commun' => true, 'semestres_tronc_commun' => 1]);

        return [$annee, $niveau, $tcFiliere];
    }

    /**
     * Construit un parcours orienté (phase TC S1 → phase spé S2) et place le
     * coefficient UNIQUEMENT sur la combinaison (matiere, filière TC, niveau).
     *
     * @return array{0: ESBTPMatiere, 1: ESBTPClasse, 2: ESBTPClasse, 3: ESBTPAnneeUniversitaire, 4: ESBTPInscription}
     */
    private function makeOrientedJourneyWithTcCoefficient(float $coefficient): array
    {
        [$annee, $niveau, $tcFiliere] = $this->makeAcademicContext();
        $specFiliere = ESBTPFiliere::factory()->create(['parent_id' => $tcFiliere->id]);

        $tcClasse = ESBTPClasse::factory()->create([
            'filiere_id' => $tcFiliere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $specClasse = ESBTPClasse::factory()->create([
            'filiere_id' => $specFiliere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);

        $matiere = ESBTPMatiere::factory()->create();
        $etudiant = ESBTPEtudiant::factory()->create();

        $inscription = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $tcFiliere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $specClasse->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'tronc_commun',
            'classe_id' => $tcClasse->id,
            'filiere_id' => $tcFiliere->id,
            'semestre_debut' => 1,
            'semestre_fin' => 1,
            'is_active' => false,
        ]);
        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'specialisation',
            'classe_id' => $specClasse->id,
            'filiere_id' => $specFiliere->id,
            'semestre_debut' => 2,
            'is_active' => true,
        ]);

        // Coefficient UNIQUEMENT sur la filière TC (pas sur la filière spé).
        ESBTPMatiereCoefficient::create([
            'matiere_id' => $matiere->id,
            'filiere_id' => $tcFiliere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
            'periode' => 'semestre1',
            'coefficient' => $coefficient,
        ]);

        return [$matiere, $tcClasse, $specClasse, $annee, $inscription];
    }
}
