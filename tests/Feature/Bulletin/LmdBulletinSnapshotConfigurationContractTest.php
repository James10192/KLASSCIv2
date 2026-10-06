<?php

namespace Tests\Feature\Bulletin;

use App\Models\ESBTPLMDBulletin;
use Tests\TestCase;

class LmdBulletinSnapshotConfigurationContractTest extends TestCase
{
    public function test_lmd_bulletin_pdf_uses_tenant_branding_affectation_and_teacher_snapshot(): void
    {
        $pdf = file_get_contents(resource_path('views/esbtp/lmd/bulletins/pdf.blade.php'));

        $this->assertStringContainsString('header_text_on_primary', $pdf);
        $this->assertStringContainsString('$tableHeaderText', $pdf);
        $this->assertStringContainsString("\$hdrText = \$pdfCfg['header_text_color_raw'] ?? \$pdfCfg['header_text_color'] ?? '#ffffff';", $pdf);
        $this->assertStringNotContainsString("\$hdrText = \$pdfCfg['header_text_on_bg']", $pdf);
        $this->assertStringContainsString('$bulletin->affectation_label', $pdf);
        $this->assertStringContainsString('$resECUE->enseignant_affiche', $pdf);
        $this->assertStringContainsString('lmd_bulletin_font_table_header', $pdf);
        $this->assertStringContainsString('lmd_bulletin_font_teacher', $pdf);
        $this->assertStringContainsString('$statutEtablissement', $pdf);
        $this->assertStringNotContainsString('Etablissement privé, Côte d\'Ivoire', $pdf);
    }

    public function test_lmd_bulletin_pdf_uses_space_for_readability_and_can_flow_cleanly_to_page_two(): void
    {
        $pdf = file_get_contents(resource_path('views/esbtp/lmd/bulletins/pdf.blade.php'));

        $this->assertStringContainsString('class="official-band"', $pdf);
        $this->assertStringContainsString('class="identity-grid"', $pdf);
        $this->assertStringContainsString('class="closing-grid"', $pdf);
        $this->assertStringContainsString('.bulletin-table thead { display: table-header-group; }', $pdf);
        $this->assertStringContainsString('page-break-inside: avoid', $pdf);
        $this->assertStringContainsString('$academicRowCount', $pdf);
        $this->assertStringContainsString('$rowPadding', $pdf);
        $this->assertStringContainsString("min(32, \$value)", $pdf);
        $this->assertStringContainsString("lmd_bulletin_font_table', 9.5", $pdf);
        $this->assertStringContainsString("lmd_bulletin_font_student', 10.5", $pdf);
    }

    public function test_lmd_bulletin_header_is_compact_half_width_and_signature_keeps_real_signing_space(): void
    {
        $pdf = file_get_contents(resource_path('views/esbtp/lmd/bulletins/pdf.blade.php'));

        $this->assertStringContainsString('class="lmd-document-header"', $pdf);
        $this->assertStringContainsString('.lmd-header-school,', $pdf);
        $this->assertStringContainsString('width: 50%;', $pdf);
        $this->assertStringContainsString('BulletinMentionResolver::editionLabel()', $pdf);
        $this->assertStringContainsString('$editionDate = now()->format(\'d/m/Y\')', $pdf);
        $this->assertStringContainsString('$logoHeight = max(60, min(120', $pdf);
        $this->assertStringContainsString('class="signature-title">Le Directeur des Études</div>', $pdf);
        $this->assertStringContainsString('class="signature-space"', $pdf);
        $this->assertStringContainsString('class="signature-name"', $pdf);
        $this->assertStringNotContainsString('Nom / Signature et cachet du chef', $pdf);
        $this->assertStringNotContainsString("}}, le {{ \$editionDate", $pdf);
    }

    public function test_lmd_direction_is_a_real_setting_and_not_the_directors_name_fallback(): void
    {
        $pdf = file_get_contents(resource_path('views/esbtp/lmd/bulletins/pdf.blade.php'));
        $view = file_get_contents(resource_path('views/esbtp/lmd/bulletins/index.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ESBTPLMDBulletinController.php'));

        $this->assertStringContainsString('setting_lmd_bulletin_direction', $view);
        $this->assertStringContainsString("SettingsHelper::get('lmd_bulletin_direction', '')", $controller);
        $this->assertStringContainsString('$directionEtablissement', $pdf);
        $this->assertStringNotContainsString("\$bCfg['direction'] ?? \$etab['directeur']", $pdf);
    }

    public function test_lmd_bulletins_page_exposes_its_own_configuration_and_keeps_infinite_scroll(): void
    {
        $view = file_get_contents(resource_path('views/esbtp/lmd/bulletins/index.blade.php'));

        $this->assertStringContainsString('Configuration du bulletin LMD', $view);
        $this->assertStringContainsString("route('esbtp.settings.update')", $view);
        $this->assertStringContainsString('setting_lmd_bulletin_statut', $view);
        $this->assertStringContainsString('setting_lmd_bulletin_direction', $view);
        $this->assertStringContainsString('setting_lmd_bulletin_font_student', $view);
        $this->assertStringContainsString('setting_lmd_bulletin_font_table_header', $view);
        $this->assertStringContainsString('setting_lmd_bulletin_font_signature', $view);
        $this->assertStringContainsString('max="32"', $view);
        $this->assertStringContainsString('6 à 32 px', $view);
        $this->assertStringContainsString('<x-liste-infinie', $view);
        $this->assertStringContainsString('form.submit=function(){if(!suspendre)filtrer()}', $view);
    }

    public function test_lmd_teacher_snapshot_is_scoped_to_semester_and_supports_external_teachers(): void
    {
        $model = file_get_contents(app_path('Models/ESBTPLMDResultatECUE.php'));

        $this->assertStringContainsString("'enseignant_snapshot_nom'", $model);
        $this->assertStringContainsString("'semestre'.\$semestre", $model);
        $this->assertStringContainsString("'S'.\$semestre", $model);
        $this->assertStringContainsString('enseignant_externe_nom', $model);
        $this->assertStringContainsString('resoudreEnseignantDuSemestre', $model);
        $this->assertStringContainsString('getEnseignantAfficheAttribute', $model);
    }

    public function test_published_lmd_bulletin_is_explicitly_frozen(): void
    {
        $model = file_get_contents(app_path('Models/ESBTPLMDBulletin.php'));
        $ue = file_get_contents(app_path('Models/ESBTPLMDResultatUE.php'));
        $ecue = file_get_contents(app_path('Models/ESBTPLMDResultatECUE.php'));

        $this->assertStringContainsString("getOriginal('is_published')", $model);
        $this->assertStringContainsString('cohorte contient déjà un bulletin LMD publié', $model);
        $this->assertStringContainsString('Un bulletin LMD publié ne peut pas être supprimé', $model);
        $this->assertStringContainsString('ses résultats UE sont figés', $ue);
        $this->assertStringContainsString('ses résultats ECUE et ses enseignants sont figés', $ecue);
    }

    public function test_affectation_labels_match_bts_vocabulary(): void
    {
        $bulletin = new ESBTPLMDBulletin();

        $bulletin->affectation_status = 'affecté';
        $this->assertSame('Affecté', $bulletin->affectation_label);

        $bulletin->affectation_status = 'réaffecté';
        $this->assertSame('Réaffecté', $bulletin->affectation_label);

        $bulletin->affectation_status = 'non_affecté';
        $this->assertSame('Non affecté', $bulletin->affectation_label);
    }

    public function test_migration_preserves_existing_tenant_settings_and_backfills_affectation(): void
    {
        $migration = file_get_contents(database_path('migrations/2026_10_03_130000_harden_lmd_bulletin_snapshots_and_typography.php'));

        $this->assertStringContainsString("'affectation_status'", $migration);
        $this->assertStringContainsString("'enseignant_snapshot_nom'", $migration);
        $this->assertStringContainsString("where('key', \$key)->exists()", $migration);
        $this->assertStringContainsString("value('affectation_status')", $migration);
        $this->assertStringNotContainsString('updateOrInsert(', $migration);
    }

    public function test_readability_migration_only_upgrades_untouched_defaults_and_guarantees_direction_setting(): void
    {
        $migration = file_get_contents(database_path('migrations/2026_10_05_150500_improve_lmd_bulletin_print_layout_defaults.php'));

        $this->assertStringContainsString("'lmd_bulletin_font_table' => ['8.5', '9.5']", $migration);
        $this->assertStringContainsString("'lmd_bulletin_font_school_name' => ['13', '15']", $migration);
        $this->assertStringContainsString("'max:32'", $migration);
        $this->assertStringContainsString("'validation_rules' => \$fontValidationRules", $migration);
        $this->assertStringContainsString("(string) \$existing->value === \$oldDefault", $migration);
        $this->assertStringContainsString("where('key', 'lmd_bulletin_direction')->exists()", $migration);
        $this->assertStringContainsString("Direction affichée dans le bandeau du bulletin LMD", $migration);
    }
}
