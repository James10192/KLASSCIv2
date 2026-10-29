<?php

namespace Tests\Unit\Admissions;

use App\Helpers\SettingsHelper;
use App\Models\Setting;
use App\Services\Admissions\InscriptionWorkflowSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class InscriptionWorkflowSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_workflow_is_disabled_and_legacy_by_default(): void
    {
        $settings = app(InscriptionWorkflowSettings::class);
        $settings->ensureDefaults();

        $this->assertFalse($settings->enabled());
        $this->assertSame(InscriptionWorkflowSettings::MODE_LEGACY, $settings->mode());
        $this->assertFalse($settings->usesManagedWorkflow());
        $this->assertSame('legacy', $settings->firstPhysicalStep());
    }

    public function test_yamoussoukro_mode_starts_with_cashier_without_changing_global_default(): void
    {
        $settings = app(InscriptionWorkflowSettings::class);
        $settings->ensureDefaults();

        $this->set(InscriptionWorkflowSettings::ENABLED, '1');
        $this->set(InscriptionWorkflowSettings::MODE, InscriptionWorkflowSettings::MODE_CAISSE_AVANT_PIECES);

        $this->assertTrue($settings->usesManagedWorkflow());
        $this->assertSame('caisse', $settings->firstPhysicalStep());
        $this->assertStringContainsString('caisse', mb_strtolower($settings->acceptanceMessage()));
    }

    public function test_another_tenant_can_reverse_physical_order(): void
    {
        $settings = app(InscriptionWorkflowSettings::class);
        $settings->ensureDefaults();

        $this->set(InscriptionWorkflowSettings::ENABLED, '1');
        $this->set(InscriptionWorkflowSettings::MODE, InscriptionWorkflowSettings::MODE_PIECES_AVANT_CAISSE);

        $this->assertSame('pieces', $settings->firstPhysicalStep());
        $this->assertStringContainsString('pièces', $settings->acceptanceMessage());
    }

    public function test_student_class_choice_and_activation_step_are_independent_settings(): void
    {
        $settings = app(InscriptionWorkflowSettings::class);
        $settings->ensureDefaults();

        $this->set(InscriptionWorkflowSettings::CLASS_CHOICE_ACTOR, InscriptionWorkflowSettings::CLASS_ACTOR_STUDENT);
        $this->set(InscriptionWorkflowSettings::ACCOUNT_ACTIVATION_STEP, InscriptionWorkflowSettings::ACTIVATION_AFTER_DOCUMENTS);
        $this->set(InscriptionWorkflowSettings::CLASS_CHOICE_ONCE, '1');

        $this->assertSame(InscriptionWorkflowSettings::CLASS_ACTOR_STUDENT, $settings->classChoiceActor());
        $this->assertSame(InscriptionWorkflowSettings::ACTIVATION_AFTER_DOCUMENTS, $settings->accountActivationStep());
        $this->assertTrue($settings->classChoiceOnce());
    }

    private function set(string $key, string $value): void
    {
        Setting::where('key', $key)->update(['value' => $value]);
        Cache::forget('setting_'.$key);
    }
}
