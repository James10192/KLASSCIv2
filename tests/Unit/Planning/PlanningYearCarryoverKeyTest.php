<?php

namespace Tests\Unit\Planning;

use App\Http\Controllers\ESBTPPlanningYearCarryoverController;
use App\Models\ESBTPPlanificationAcademique;
use ReflectionMethod;
use Tests\TestCase;

class PlanningYearCarryoverKeyTest extends TestCase
{
    public function test_identite_d_une_planification_depend_de_la_combinaison_et_du_semestre(): void
    {
        $key = new ReflectionMethod(ESBTPPlanningYearCarryoverController::class, 'key');
        $controller = new ESBTPPlanningYearCarryoverController();
        $a = new ESBTPPlanificationAcademique([
            'filiere_id' => 1, 'niveau_etude_id' => 2, 'matiere_id' => 3, 'semestre' => 1,
        ]);
        $b = new ESBTPPlanificationAcademique([
            'filiere_id' => 1, 'niveau_etude_id' => 2, 'matiere_id' => 3, 'semestre' => 2,
        ]);
        $this->assertNotSame($key->invoke($controller, $a), $key->invoke($controller, $b));
    }
}
