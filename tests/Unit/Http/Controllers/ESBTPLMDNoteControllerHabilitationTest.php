<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Controllers;

use App\Domain\AcademicPilotage\Services\GradeSheetNoteMutationGuard;
use App\Http\Controllers\ESBTPLMDNoteController;
use App\Models\ESBTPEvaluation;
use App\Models\User;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

/**
 * `lmd.notes.manage` ouvre la saisie groupee LMD, sans rien dire de la classe ni
 * de la matiere. Le controleur doit donc borner un enseignant aux evaluations
 * qui lui sont confiees — sans pour autant gener les profils qui coordonnent,
 * qui gardent une vue d'ensemble.
 */
class ESBTPLMDNoteControllerHabilitationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function invoquer(ESBTPEvaluation $evaluation): void
    {
        $methode = new ReflectionMethod(ESBTPLMDNoteController::class, 'assertEvaluationConfieeAEnseignant');
        $methode->setAccessible(true);
        $methode->invoke(new ESBTPLMDNoteController(new GradeSheetNoteMutationGuard), $evaluation);
    }

    private function utilisateur(int $id, bool $enseigne, bool $coordonne): User
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->with('identity.teach')->andReturn($enseigne);
        $user->shouldReceive('can')->with('identity.coordinate')->andReturn($coordonne);
        $user->id = $id;

        return $user;
    }

    private function evaluation(?int $enseignantId): ESBTPEvaluation
    {
        $evaluation = new ESBTPEvaluation();
        $evaluation->id = 1;
        $evaluation->matiere_id = 42;
        $evaluation->annee_universitaire_id = 3;
        $evaluation->enseignant_id = $enseignantId;

        return $evaluation;
    }

    public function test_visiteur_non_authentifie_n_est_pas_concerne(): void
    {
        $this->invoquer($this->evaluation(9));

        $this->expectNotToPerformAssertions();
    }

    public function test_profil_sans_identite_enseignante_garde_la_vue_d_ensemble(): void
    {
        $this->be($this->utilisateur(5, enseigne: false, coordonne: false));

        $this->invoquer($this->evaluation(9));

        $this->expectNotToPerformAssertions();
    }

    public function test_profil_qui_coordonne_garde_la_vue_d_ensemble(): void
    {
        $this->be($this->utilisateur(5, enseigne: true, coordonne: true));

        $this->invoquer($this->evaluation(9));

        $this->expectNotToPerformAssertions();
    }

    public function test_enseignant_nomme_sur_l_evaluation_est_habilite(): void
    {
        $this->be($this->utilisateur(9, enseigne: true, coordonne: false));

        $this->invoquer($this->evaluation(9));

        $this->expectNotToPerformAssertions();
    }
}
