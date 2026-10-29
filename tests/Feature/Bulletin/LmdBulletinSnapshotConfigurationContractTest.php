<?php

namespace Tests\Feature\Bulletin;

use App\Models\ESBTPFiliere;
use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPNiveauEtude;
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
        $this->assertStringContainsString('$paysEtablissement', $pdf);
        $this->assertStringContainsString("lmd_bulletin_bottom_width_percent", $pdf);
        $this->assertStringContainsString('class="bottom-note-line"', $pdf);
        $this->assertStringContainsString('white-space: nowrap', $pdf);
        $this->assertStringContainsString('lmd_bulletin_bottom_single_line', $pdf);
        $this->assertStringContainsString('lmd-header-frame', $pdf);
        $this->assertStringNotContainsString("}}, Côte d'Ivoire", $pdf);
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
        $this->assertStringContainsString("lmd_bulletin_logo_height", $pdf);
        $this->assertStringContainsString("lmd_bulletin_header_padding_y", $pdf);
        $this->assertStringContainsString("lmd_bulletin_signature_space_height", $pdf);
        $this->assertStringContainsString('class="signature-title">Le Directeur des Études</div>', $pdf);
        $this->assertStringContainsString('class="signature-space"', $pdf);
        $this->assertStringContainsString('class="signature-name"', $pdf);
        $this->assertStringNotContainsString('Nom / Signature et cachet du chef', $pdf);
        $this->assertStringNotContainsString("}}, le {{ \$editionDate", $pdf);
    }

    public function test_standalone_lmd_configuration_exposes_header_styling_and_student_fields(): void
    {
        $view = file_get_contents(resource_path('views/esbtp/lmd/bulletins/index.blade.php'));
        $pdf = file_get_contents(resource_path('views/esbtp/lmd/bulletins/pdf.blade.php'));

        foreach ([
            'lmd_bulletin_official_header_bg',
            'lmd_bulletin_header_label_color',
            'lmd_bulletin_meta_label_color',
            'lmd_bulletin_header_label_bold',
            'lmd_bulletin_ministry_bold',
            'lmd_bulletin_meta_label_bold',
            'effectif',
            'redoublant',
        ] as $setting) {
            $this->assertStringContainsString($setting, $view);
        }

        $this->assertStringContainsString('<div class="lmd-header-frame">', $pdf);
        $this->assertStringContainsString('border: 2px solid {{ $primary }};', $pdf);
    }

    public function test_lmd_identity_balances_and_metadata_has_configurable_values(): void
    {
        $pdf = file_get_contents(resource_path('views/esbtp/lmd/bulletins/pdf.blade.php'));
        $index = file_get_contents(resource_path('views/esbtp/lmd/bulletins/index.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ESBTPBulletinController.php'));

        $this->assertStringContainsString('$identityColumns', $pdf);
        $this->assertStringContainsString('array_slice($identityRows', $pdf);
        $this->assertStringContainsString('show_affectation', $pdf);
        $this->assertStringContainsString('lmd_bulletin_meta_value_color', $index);
        $this->assertStringContainsString('lmd_bulletin_meta_value_bold', $controller);
        $this->assertStringContainsString('lmd_bulletin_label_affectation', $controller);
    }

    public function test_establishment_status_can_be_hidden_without_empty_official_band(): void
    {
        $pdf = file_get_contents(resource_path('views/esbtp/lmd/bulletins/pdf.blade.php'));
        $standalone = file_get_contents(resource_path('views/esbtp/lmd/bulletins/index.blade.php'));
        $central = file_get_contents(resource_path('views/esbtp/bulletins/partials/_configuration-lmd.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ESBTPLMDBulletinController.php'));
        $settings = file_get_contents(app_path('Http/Controllers/ESBTPBulletinController.php'));

        $this->assertStringContainsString("show_establishment_status", $pdf);
        $this->assertStringContainsString("show_establishment_status", $controller);
        $this->assertStringContainsString("lmd_bulletin_show_establishment_status", $settings);
        $this->assertStringContainsString("lmd_bulletin_show_establishment_status", $standalone);
        $this->assertStringContainsString("lmd_bulletin_show_establishment_status", $central);
        $this->assertStringContainsString('$officialBandItems->isNotEmpty()', $pdf);
        $this->assertStringContainsString('->filter(fn (array $item)', $pdf);
    }

    public function test_lmd_direction_is_a_real_setting_and_not_the_directors_name_fallback(): void
    {
        $pdf = file_get_contents(resource_path('views/esbtp/lmd/bulletins/pdf.blade.php'));
        $view = file_get_contents(resource_path('views/esbtp/lmd/bulletins/index.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ESBTPLMDBulletinController.php'));

        $this->assertStringContainsString('name="lmd_bulletin_direction"', $view);
        $this->assertStringContainsString("SettingsHelper::get('lmd_bulletin_direction', '')", $controller);
        $this->assertStringContainsString('$directionEtablissement', $pdf);
        $this->assertStringNotContainsString("\$bCfg['direction'] ?? \$etab['directeur']", $pdf);
    }

    public function test_lmd_official_band_hides_empty_code_and_direction_labels(): void
    {
        $pdf = file_get_contents(resource_path('views/esbtp/lmd/bulletins/pdf.blade.php'));

        $this->assertStringContainsString('$officialBandItems = collect([', $pdf);
        $this->assertStringContainsString("trim((string) \$item['value']) !== ''", $pdf);
        $this->assertStringContainsString('$officialBandItems->isNotEmpty()', $pdf);
        $this->assertStringContainsString('@foreach($officialBandItems as $officialItem)', $pdf);
        $this->assertStringContainsString('$officialColumnWidth', $pdf);
        $this->assertStringNotContainsString("{{ \$codeEtablissement !== '' ? \$codeEtablissement : '—' }}", $pdf);
        $this->assertStringNotContainsString("{{ \$directionEtablissement !== '' ? \$directionEtablissement : '—' }}", $pdf);
    }

    public function test_lmd_bulletins_page_exposes_its_own_configuration_and_keeps_infinite_scroll(): void
    {
        $view = file_get_contents(resource_path('views/esbtp/lmd/bulletins/index.blade.php'));

        $this->assertStringContainsString('Configuration du bulletin LMD', $view);
        $this->assertStringContainsString("route('esbtp.bulletins.save-configuration')", $view);
        $this->assertStringNotContainsString("route('esbtp.settings.update')", $view);
        $this->assertStringContainsString('name="lmd_bulletin_statut"', $view);
        $this->assertStringContainsString('name="lmd_bulletin_direction"', $view);
        $this->assertStringContainsString('LMDBulletinPrintSettings::fontFields()', $view);
        $this->assertStringContainsString('LMDBulletinPrintSettings::layoutFields()', $view);
        $this->assertStringContainsString('lmd-bulletin-config-form', $view);
        $this->assertStringContainsString('max="32"', $view);
        $this->assertStringContainsString('6 à 32 px', $view);
        $this->assertStringContainsString('<x-liste-infinie', $view);
        $this->assertStringContainsString('form.submit=function(){if(!suspendre)filtrer()}', $view);
    }

    public function test_central_configuration_exposes_real_lmd_typography_and_layout_controls(): void
    {
        $partial = file_get_contents(resource_path('views/esbtp/bulletins/partials/_configuration-lmd.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ESBTPBulletinController.php'));
        $configuration = file_get_contents(resource_path('views/esbtp/bulletins/configuration.blade.php'));
        $support = file_get_contents(app_path('Support/LMDBulletinPrintSettings.php'));

        $this->assertStringContainsString('LMDBulletinPrintSettings::fontFields()', $partial);
        $this->assertStringContainsString('LMDBulletinPrintSettings::layoutFields()', $partial);
        $this->assertStringContainsString('lmd_bulletin_logo_height', $support);
        $this->assertStringContainsString('lmd_bulletin_signature_space_height', $support);
        $this->assertStringContainsString('lmd_bulletin_bottom_width_percent', $support);
        $this->assertStringContainsString('Largeur du pied de page', $support);
        $this->assertStringContainsString('lmd_bulletin_show_effectif', $partial);
        $this->assertStringContainsString('lmd_bulletin_show_redoublant', $partial);
        $this->assertStringContainsString('lmd_bulletin_ministry_bold', $partial);
        $this->assertStringContainsString('lmd_bulletin_meta_label_color', $support);
        $this->assertStringContainsString('lmd_bulletin_official_header_bg', $support);
        $this->assertStringContainsString('LMDBulletinPrintSettings::validationRules()', $controller);
        $this->assertStringContainsString('...LMDBulletinPrintSettings::fieldKeys()', $controller);
        $this->assertStringContainsString("initialTab === 'lmd' ? 'lmd' : 'bts'", $configuration);
    }

    public function test_parcours_officiel_ne_montre_jamais_le_code_court_de_filiere(): void
    {
        $parcours = new ESBTPLMDParcours(['name' => 'Bâtiment et Urbanisme']);
        $filiere = new ESBTPFiliere();
        $filiere->code = 'BU';
        $filiere->name = 'Bâtiment et Urbanisme';
        $niveau = new ESBTPNiveauEtude();
        $niveau->name = 'Licence 1';

        $parcours->setRelation('filiere', $filiere);

        $this->assertSame(
            'LICENCE 1 BÂTIMENT ET URBANISME',
            $parcours->genererLabelBulletin($niveau)
        );
        $this->assertSame(
            'LICENCE 1 BÂTIMENT ET URBANISME',
            $parcours->nettoyerLabelBulletin('LICENCE 1 BU BÂTIMENT ET URBANISME')
        );
        $this->assertSame(
            'LICENCE 1 BUREAUTIQUE ET URBANISME',
            $parcours->nettoyerLabelBulletin('LICENCE 1 BUREAUTIQUE ET URBANISME')
        );

        $service = file_get_contents(app_path('Services/LMDBulletinService.php'));
        $this->assertStringContainsString('parcours.filiere', $service);
        $this->assertStringContainsString('nettoyerLabelBulletin($bulletin->parcours_label)', $service);
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
