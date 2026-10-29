<?php

namespace Tests\Unit\AcademicPilotage;

use App\Domain\AcademicPilotage\Enums\GradeSheetEntryStatus;
use App\Domain\AcademicPilotage\Services\AcademicNoteCoverageService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * « Traitée » ou « manquante » : la décision ligne par ligne.
 *
 * Ce test existe parce que son absence a laissé passer un garde INERTE.
 * `resolvedEntries()` interroge la base par `DB::table()` : les lignes sont
 * des `stdClass`, sans cast. Le garde lisait `$entry->metadata['cohort_active']`
 * alors que (a) `metadata` n'était même pas dans le `select`, et (b) elle
 * serait revenue en chaîne JSON. Les deux rendaient `null` sans lever la
 * moindre erreur — un garde muet est indiscernable d'un garde qui marche, et
 * c'est précisément ce qui le rend coûteux.
 *
 * Les entrées sont donc construites ICI comme `DB::table()` les rend :
 * `stdClass`, `metadata` en CHAÎNE.
 */
class StatutDUneLigneDeNoteTest extends TestCase
{
    private function statut(?object $note, ?object $entry): string
    {
        $service = (new ReflectionClass(AcademicNoteCoverageService::class))->newInstanceWithoutConstructor();
        $m = new ReflectionMethod(AcademicNoteCoverageService::class, 'studentResultStatus');
        $m->setAccessible(true);

        return $m->invoke($service, $note, $entry);
    }

    /** Une entrée telle que `DB::table()` la rend : metadata en chaîne JSON. */
    private function entree(string $status, ?array $metadata = null): object
    {
        $entry = new \stdClass();
        $entry->status = $status;
        $entry->metadata = $metadata === null ? null : json_encode($metadata);

        return $entry;
    }

    /**
     * LE CAS QUI ÉTAIT CASSÉ. `deactivateMissing()` marque « non applicable »
     * l'élève qui n'était pas encore dans la classe, avec `cohort_active =
     * false`. Le compter comme traité faisait annoncer « toutes les notes sont
     * reçues » sur une classe où rien n'était saisi — le seul sens d'erreur
     * qui fasse générer des bulletins à tort.
     */
    public function test_une_ligne_desactivee_automatiquement_reste_manquante(): void
    {
        $entree = $this->entree(
            GradeSheetEntryStatus::NOT_APPLICABLE->value,
            ['cohort_active' => false]
        );

        $this->assertSame('missing', $this->statut(null, $entree));
    }

    /** Le retrait DÉCIDÉ par l'école, lui, compte bien comme traité. */
    public function test_un_retrait_decide_par_l_ecole_compte_comme_traite(): void
    {
        $entree = $this->entree(GradeSheetEntryStatus::NOT_APPLICABLE->value, ['motif' => 'dispense']);

        $this->assertSame(
            GradeSheetEntryStatus::NOT_APPLICABLE->value,
            $this->statut(null, $entree)
        );
    }

    public function test_un_retrait_sans_metadonnees_compte_comme_traite(): void
    {
        $this->assertSame(
            GradeSheetEntryStatus::NOT_APPLICABLE->value,
            $this->statut(null, $this->entree(GradeSheetEntryStatus::NOT_APPLICABLE->value))
        );
    }

    /**
     * Un tableau déjà décodé doit passer aussi : si un appelant vient un jour
     * d'Eloquent, dont le cast rend un tableau, le garde ne doit pas redevenir
     * muet dans l'autre sens.
     */
    public function test_des_metadonnees_deja_en_tableau_sont_lues_de_meme(): void
    {
        $entree = new \stdClass();
        $entree->status = GradeSheetEntryStatus::NOT_APPLICABLE->value;
        $entree->metadata = ['cohort_active' => false];

        $this->assertSame('missing', $this->statut(null, $entree));
    }

    /** `cohort_active = true` désigne un élève bien présent : rien à neutraliser. */
    public function test_une_cohorte_active_ne_neutralise_pas_le_retrait(): void
    {
        $entree = $this->entree(GradeSheetEntryStatus::NOT_APPLICABLE->value, ['cohort_active' => true]);

        $this->assertSame(
            GradeSheetEntryStatus::NOT_APPLICABLE->value,
            $this->statut(null, $entree)
        );
    }

    /** Les autres états ne sont pas concernés par le garde. */
    public function test_absent_et_dispense_restent_traites(): void
    {
        $this->assertSame(
            GradeSheetEntryStatus::ABSENT->value,
            $this->statut(null, $this->entree(GradeSheetEntryStatus::ABSENT->value, ['cohort_active' => false]))
        );
        $this->assertSame(
            GradeSheetEntryStatus::EXEMPT->value,
            $this->statut(null, $this->entree(GradeSheetEntryStatus::EXEMPT->value))
        );
    }

    public function test_une_note_saisie_prime_sur_l_entree(): void
    {
        $note = new \stdClass();
        $note->is_absent = false;
        $note->note = 12.0;

        $this->assertSame('numeric', $this->statut($note, $this->entree(GradeSheetEntryStatus::NOT_APPLICABLE->value)));
    }

    public function test_sans_note_ni_entree_la_ligne_est_manquante(): void
    {
        $this->assertSame('missing', $this->statut(null, null));
    }
}
