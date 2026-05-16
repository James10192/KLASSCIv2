<?php

namespace Tests\Unit\Services\LMD\Tpe;

use App\Enums\TpeDeclarationStatut;
use App\Services\LMD\Tpe\AutoValidateStrategy;
use App\Services\LMD\Tpe\TpeValidationStrategy;
use PHPUnit\Framework\TestCase;

class AutoValidateStrategyTest extends TestCase
{
    public function test_initial_statut_is_valide(): void
    {
        $strategy = new AutoValidateStrategy();

        $this->assertSame(
            TpeDeclarationStatut::VALIDE,
            $strategy->initialStatut(),
            'Option 2 doit créer une déclaration directement VALIDE (pas de workflow prof)',
        );
    }

    public function test_does_not_require_teacher_action(): void
    {
        $strategy = new AutoValidateStrategy();

        $this->assertFalse(
            $strategy->requiresTeacherAction(),
            'Option 2 ne sollicite jamais l\'enseignant — pas de notification, pas d\'écran de validation',
        );
    }

    public function test_implements_strategy_contract(): void
    {
        $this->assertInstanceOf(
            TpeValidationStrategy::class,
            new AutoValidateStrategy(),
            'AutoValidateStrategy doit implémenter le contrat pour être bindable dans le container',
        );
    }
}
