<?php

namespace Tests\Unit\Services\LMD;

use App\Models\ESBTPPlanificationAcademique;
use Tests\TestCase;

class TeacherPoolEmptySelectionTest extends TestCase
{
    public function test_empty_explicit_pool_does_not_restore_historical_pivot_teachers(): void
    {
        $planification = new ESBTPPlanificationAcademique();
        $planification->enseignant_principal_id = null;
        $planification->enseignants_secondaires = [];
        $planification->setRelation('teachers', collect([(object) ['user_id' => 99]]));

        $this->assertSame([], $planification->poolUserIds()->all());
    }

    public function test_modal_save_preserves_empty_json_and_synchronizes_legacy_pivot(): void
    {
        $controller = file_get_contents(dirname(__DIR__, 4).'/app/Http/Controllers/ESBTPLMDEvaluationTeacherController.php');
        $this->assertNotFalse($controller);

        $start = strpos($controller, 'public function savePool(');
        $end = strpos($controller, 'public function professeurs(', $start);
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $save = substr($controller, $start, $end - $start);

        $this->assertStringContainsString('enseignants_secondaires = $ids->slice(1)->values()->all();', $save);
        $this->assertStringNotContainsString('all() ?: null', $save);
        $this->assertStringContainsString('ESBTPPlanificationTeacherPoolObserver::class)->synchroniser($planif)', $save);
    }
}
