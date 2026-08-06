<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use Tests\TestCase;

class AttendanceNoteConfigurableContractTest extends TestCase
{
    public function test_bulletin_service_delegates_to_rule_value_object(): void
    {
        $service = file_get_contents(app_path('Services/BulletinService.php'));

        $this->assertStringContainsString('use App\Support\Attendance\AttendanceNoteRule;', $service);
        $this->assertStringContainsString('public function getAttendanceNoteRule(): AttendanceNoteRule', $service);
        $this->assertStringContainsString('return $this->getAttendanceNoteRule()->resolve(', $service);
        // Fallback legacy garanti quand le setting JSON est absent.
        $this->assertStringContainsString('AttendanceNoteRule::fromLegacySettings(', $service);
        // Plus de seuils codés en dur dans resolveAttendanceNote.
        $this->assertStringNotContainsString('if ($heuresNonJustifiees < 2.0)', $service);
    }

    public function test_settings_controller_seeds_json_rule_and_validates(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ESBTP/ESBTPSettingsController.php'));

        // Seed JSON depuis les valeurs legacy DU tenant.
        $this->assertStringContainsString("'attendance_note_rules'", $controller);
        $this->assertStringContainsString('AttendanceNoteRule::fromLegacySettings(', $controller);
        // Validation structurelle dédiée au save.
        $this->assertStringContainsString('AttendanceNoteRule::validationErrors($decoded)', $controller);
        $this->assertStringContainsString("if (\$settingKey === 'attendance_note_rules')", $controller);
    }

    public function test_settings_view_has_dynamic_bracket_editor(): void
    {
        $view = file_get_contents(resource_path('views/esbtp/settings/index.blade.php'));

        $this->assertStringContainsString('attendanceBaremeEditor()', $view);
        $this->assertStringContainsString('name="setting_attendance_note_rules"', $view);
        $this->assertStringContainsString(':value="serialize()"', $view);
        $this->assertStringContainsString('addRow(', $view);
        $this->assertStringContainsString('simulate()', $view);
        // Plus d'inputs à seuils figés.
        $this->assertStringNotContainsString('name="setting_attendance_note_two_unjustified"', $view);
    }

    public function test_edit_absences_views_are_bracket_driven(): void
    {
        $editAbsences = file_get_contents(resource_path('views/esbtp/bulletins/edit-absences.blade.php'));
        $modal = file_get_contents(resource_path('views/esbtp/resultats/modals/edit-absences.blade.php'));
        $configController = file_get_contents(app_path('Http/Controllers/ESBTPBulletinConfigController.php'));
        $resultatController = file_get_contents(app_path('Http/Controllers/ESBTPResultatController.php'));

        // JS générique sur les tranches, plus de seuils 2/3/5 codés en dur.
        $this->assertStringContainsString('function bracketNote(', $editAbsences);
        $this->assertStringContainsString('const attendanceRule = @json($_attRule);', $editAbsences);
        $this->assertStringNotContainsString('if (nonJust < 2)', $editAbsences);
        $this->assertStringContainsString('$attendanceRule ?? ', $modal);

        // Les deux controllers passent la règle dynamique.
        $this->assertStringContainsString("'attendanceRule' => \$attendanceRule", $configController);
        $this->assertStringContainsString('$attendanceRule = $this->bulletinService->getAttendanceNoteRule()->toArray();', $resultatController);
    }
}
