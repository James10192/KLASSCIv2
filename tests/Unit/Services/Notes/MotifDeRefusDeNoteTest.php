<?php

namespace Tests\Unit\Services\Notes;

use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use App\Services\Notes\MotifDeRefusDeNote;
use App\Services\Notes\NoteStudentCohortService;
use Mockery;
use Tests\TestCase;

/**
 * Chaque raison que la saisie groupée renvoie à l'écran, dans l'ordre où
 * elle est testée. L'écran affiche ces libellés et garde la note en rouge :
 * les changer est un changement de contrat.
 */
class MotifDeRefusDeNoteTest extends TestCase
{
    private function evaluation(array $attributs = []): ESBTPEvaluation
    {
        return (new ESBTPEvaluation())->forceFill(array_merge(['id' => 58, 'is_published' => true, 'bareme' => 20], $attributs));
    }

    private function motifs(array $cohorte = [1014]): MotifDeRefusDeNote
    {
        $cohortes = Mockery::mock(NoteStudentCohortService::class);
        $cohortes->shouldReceive('allowedStudentIdsForEvaluation')->andReturn(collect($cohorte));

        return new MotifDeRefusDeNote($cohortes);
    }

    private function saisie(array $valeurs = []): array
    {
        return array_merge(['etudiant_id' => '1014', 'evaluation_id' => '58', 'note' => '13'], $valeurs);
    }

    public function test_une_saisie_correcte_n_est_pas_refusee(): void
    {
        $motifs = $this->motifs();

        $this->assertNull($motifs->pour($this->saisie(), $this->evaluation(), null, false, fn () => true));
        $this->assertSame([58], $motifs->evaluationsAutorisees());
    }

    public function test_chaque_refus_porte_sa_raison(): void
    {
        $oui = fn () => true;

        $this->assertSame('évaluation introuvable ou non publiée', $this->motifs()->pour($this->saisie(), null, null, false, $oui));
        $this->assertSame('évaluation introuvable ou non publiée', $this->motifs()->pour($this->saisie(), $this->evaluation(['is_published' => false]), null, false, $oui));
        $this->assertSame('non autorisé sur cette évaluation', $this->motifs()->pour($this->saisie(), $this->evaluation(), null, false, fn () => false));
        $this->assertSame('étudiant hors de la classe', $this->motifs([7])->pour($this->saisie(), $this->evaluation(), null, false, $oui));
        $this->assertSame('note hors barème', $this->motifs()->pour($this->saisie(['note' => '25']), $this->evaluation(), null, false, $oui));

        $validee = (new ESBTPNote())->forceFill(['submission_status' => ESBTPNote::SUBMISSION_SUBMITTED]);
        $this->assertSame('note déjà validée', $this->motifs()->pour($this->saisie(), $this->evaluation(), $validee, false, $oui));
        $this->assertNull($this->motifs()->pour($this->saisie(), $this->evaluation(), $validee, true, $oui));
    }

    public function test_une_absence_ignore_le_bareme(): void
    {
        $this->assertNull($this->motifs()->pour($this->saisie(['note' => '99', 'is_absent' => true]), $this->evaluation(), null, false, fn () => true));
    }

    public function test_seules_les_evaluations_autorisees_sont_synchronisables(): void
    {
        $motifs = $this->motifs();
        $motifs->pour($this->saisie(), $this->evaluation(['id' => 58]), null, false, fn () => true);
        $motifs->pour($this->saisie(['evaluation_id' => '59']), $this->evaluation(['id' => 59]), null, false, fn () => false);

        $this->assertSame([58], $motifs->evaluationsAutorisees());
    }
}
