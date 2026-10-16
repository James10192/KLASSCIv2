<?php

namespace Tests\Unit\Views;

use PHPUnit\Framework\TestCase;

class BulletinSettingsNavigationTest extends TestCase
{
    public function test_bulletin_settings_are_grouped_without_changing_submission_contracts(): void
    {
        $view = file_get_contents(__DIR__.'/../../../resources/views/esbtp/settings/index.blade.php');
        $this->assertNotFalse($view);
        $this->assertSame(12, substr_count($view, 'data-settings-bulletin-group="'));
        $this->assertStringContainsString('data-settings-bulletin-view="apparence"', $view);
        $this->assertStringContainsString('data-settings-bulletin-view="pedagogie"', $view);
        $this->assertStringContainsString('data-settings-bulletin-view="administration"', $view);
        $this->assertStringContainsString('id="bts-bulletin-policy"', $view);
        $this->assertStringContainsString("route('esbtp.settings.update')", $view);
        $this->assertStringContainsString('settings_save_display', $view);
        $this->assertStringContainsString("section.hidden = section.dataset.settingsBulletinGroup !== group", $view);
        $this->assertStringContainsString("const subsection = target.closest('[data-settings-bulletin-group]')", $view);
        $this->assertStringNotContainsString("section.querySelectorAll('input').forEach", $view);
    }
}
