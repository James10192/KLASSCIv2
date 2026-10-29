<?php

namespace Tests\Unit\Services\LMD\Tpe;

use App\Enums\TpeDeclarationStatut;
use App\Services\LMD\Tpe\TeacherValidateStrategy;
use App\Services\LMD\Tpe\TpeValidationStrategy;
use PHPUnit\Framework\TestCase;

class TeacherValidateStrategyTest extends TestCase
{
    public function test_initial_statut_is_en_attente(): void
    {
        $strategy = new TeacherValidateStrategy();

        $this->assertSame(
            TpeDeclarationStatut::EN_ATTENTE,
            $strategy->initialStatut(),
            'Option 3 doit créer une déclaration EN_ATTENTE pour ouvrir le workflow prof',
        );
    }

    public function test_requires_teacher_action(): void
    {
        $strategy = new TeacherValidateStrategy();

        $this->assertTrue(
            $strategy->requiresTeacherAction(),
            'Option 3 requiert validation/rejet par l\'enseignant principal de l\'ECUE',
        );
    }

    public function test_implements_strategy_contract(): void
    {
        $this->assertInstanceOf(
            TpeValidationStrategy::class,
            new TeacherValidateStrategy(),
            'TeacherValidateStrategy doit implémenter le contrat pour être bindable dans le container',
        );
    }

    public function test_initial_statut_is_pending_teacher_action(): void
    {
        $strategy = new TeacherValidateStrategy();

        // Sanity check : le statut initial DOIT être actionnable côté prof
        // (sinon le workflow Option 3 serait cassé d'entrée — déclaration créée
        //  mais aucun bouton Valider/Rejeter ne s'afficherait).
        $this->assertTrue(
            $strategy->initialStatut()->isPendingTeacherAction(),
            'Le statut initial Option 3 doit être actionnable par le prof',
        );
    }
}
