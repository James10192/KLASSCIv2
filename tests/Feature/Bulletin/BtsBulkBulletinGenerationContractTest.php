<?php

namespace Tests\Feature\Bulletin;

use Tests\TestCase;

class BtsBulkBulletinGenerationContractTest extends TestCase
{
    public function test_bulk_generation_has_structured_json_preflight_and_recalculate_contract(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ESBTPBulletinController.php'));
        $request = file_get_contents(app_path('Http/Requests/Bulletin/GenerateClasseBulletinsRequest.php'));
        $routes = file_get_contents(base_path('routes/web.php'));
        $resultDto = file_get_contents(app_path('Domain/AcademicPilotage/DTO/BulkBulletinGenerationResult.php'));

        $this->assertStringContainsString('BtsBulkBulletinGenerationService', $controller);
        $this->assertStringContainsString('response()->json($result->toArray(), $result->statusCode())', $controller);
        $this->assertStringContainsString('preflightClasseBulletins', $controller);
        $this->assertStringContainsString("'recalculer'             => 'sometimes|boolean'", $request);
        $this->assertStringContainsString("'incomplete_reason'      => 'nullable|string|min:8|max:1000'", $request);
        $this->assertStringContainsString("name('esbtp.bulletins.generer-classe.preflight')", $routes);
        $this->assertStringContainsString("'created' => \$this->created", $resultDto);
        $this->assertStringContainsString("'blocking_errors' => \$this->blockingErrors", $resultDto);
        $this->assertStringContainsString('public function statusCode(): int', $resultDto);
    }

    public function test_bulk_generation_cohort_is_bound_to_active_year_inscriptions(): void
    {
        $service = file_get_contents(app_path('Domain/AcademicPilotage/Services/BtsBulkBulletinGenerationService.php'));

        $this->assertStringContainsString('private function activeStudentsForClass', $service);
        $this->assertStringContainsString('ESBTPInscription::query()', $service);
        $this->assertStringContainsString("->where('classe_id', \$classeId)", $service);
        $this->assertStringContainsString("->where('annee_universitaire_id', \$academicYearId)", $service);
        $this->assertStringContainsString("->where('status', 'active')", $service);
        $this->assertStringContainsString("->where('workflow_step', 'etudiant_cree')", $service);
        $this->assertStringNotContainsString("ESBTPEtudiant::where('classe_id'", $service);
    }

    public function test_select_page_does_not_treat_redirect_or_failure_as_success(): void
    {
        $view = $this->selectPageSource();

        $this->assertStringContainsString("route('esbtp.bulletins.generer-classe.preflight')", $view);
        $this->assertStringContainsString('parseJsonResponse', $view);
        $this->assertStringContainsString('Le serveur a redirige la requete au lieu de retourner le resultat JSON.', $view);
        $this->assertStringContainsString('this.lastGeneration = data', $view);
        $this->assertStringContainsString('Aucun bulletin genere.', $view);
        $this->assertStringContainsString('generationStudentsLabel()', $view);
        $this->assertStringContainsString('this.preflight?.students_count', $view);
        $this->assertStringNotContainsString('Bulletins générés pour la classe. Redirection…', $view);
    }

    public function test_preview_and_select_accessibility_contracts_are_visible(): void
    {
        $selectView = $this->selectPageSource();
        $blockedView = file_get_contents(resource_path('views/esbtp/bulletins/preview-blocked.blade.php'));
        $component = file_get_contents(resource_path('views/components/au-select.blade.php'));

        $this->assertStringContainsString('previewIssue', $selectView);
        $this->assertStringContainsString('resolvePreviewUrl', $selectView);
        $this->assertStringContainsString('Ouvrir la configuration requise', $blockedView);
        $this->assertStringContainsString('aria-label="{{ $label ?: $placeholder }}"', $component);
        $this->assertStringContainsString('@keydown.arrow-down.prevent="openAndFocusNext()"', $component);
        $this->assertStringContainsString('@keydown.enter.prevent="open ? selectFocused() : openAndFocusNext()"', $component);
        $this->assertStringContainsString('role="combobox"', $component);
    }

    public function test_preflight_exposes_authoritative_status_and_hard_soft_classification(): void
    {
        $service = $this->bulkServiceSource();

        // Statut unique faisant autorité, dont les booléens dérivent.
        $this->assertStringContainsString('private function resolveStatus(', $service);
        $this->assertStringContainsString("'status' => \$status,", $service);
        $this->assertStringContainsString("'ok' => \$status === 'ready',", $service);
        $this->assertStringContainsString("'requires_incomplete_reason' => \$status === 'needs_reason',", $service);
        $this->assertStringContainsString("'nothing_to_generate' => \$status === 'nothing_to_generate',", $service);

        // Le motif ne débloque QUE des blocages soft (sinon la génération échouerait).
        // HARD_BLOCK_CODES est l'unique source de vérité : la sévérité dérive du code.
        $this->assertStringContainsString('public const HARD_BLOCK_CODES', $service);
        $this->assertStringContainsString("return (! \$hasHardBlocks && \$canOverrideIncomplete) ? 'needs_reason' : 'blocked';", $service);
        $this->assertStringContainsString("in_array(\$b['code'] ?? '', self::HARD_BLOCK_CODES, true)", $service);
        $this->assertStringNotContainsString("'severity' =>", $service);
    }

    public function test_preflight_zero_students_and_all_existing_are_not_treated_as_ready(): void
    {
        $service = $this->bulkServiceSource();

        // Newline-agnostic (le repo est en CRLF sous Windows) : on vérifie les états, pas la mise en page.
        $this->assertStringContainsString("return 'no_students';", $service);
        $this->assertStringContainsString("return 'nothing_to_generate';", $service);
        $this->assertStringContainsString('if ($studentsCount === 0)', $service);
        $this->assertStringContainsString('if ($generatableCount === 0)', $service);
        // Un bulletin existant sans moyenne est signalé, pas silencieusement ignoré.
        $this->assertStringContainsString("'bulletin_exists_empty'", $service);
        $this->assertStringContainsString("'existing_empty_count' => \$existingEmptyCount,", $service);
    }

    public function test_professeurs_requirement_ignores_unselected_matieres(): void
    {
        $service = $this->bulkServiceSource();

        // La liste des professeurs manquants part des matières retenues (payload
        // générales + techniques), donc exclut les matières « Ignorer » (type none).
        $this->assertStringContainsString('$payload = $this->configMatieresPayload($classeId, $academicYearId, $period);', $service);
        $this->assertStringContainsString("array_merge(\$payload['generales'], \$payload['techniques'])", $service);
    }

    public function test_snapshot_and_generation_share_period_aliases_and_exclude_cancelled(): void
    {
        $bulletinService = file_get_contents(app_path('Services/BulletinService.php'));
        $snapshot = file_get_contents(app_path('Services/ESBTP/BtsCurrentResultSnapshotService.php'));

        // Helper canonique unique d'aliases de période.
        $this->assertStringContainsString('public function periodeAliases(string $periode): array', $bulletinService);
        // Le snapshot de pré-contrôle et la génération réelle partagent le même filtre.
        $this->assertStringContainsString("->where('status', '!=', 'cancelled')", $snapshot);
        $this->assertStringContainsString('$this->bulletinService->periodeAliases($periode)', $snapshot);
        $this->assertStringContainsString('$this->periodeAliases((string) $periode)', $bulletinService);
        $this->assertStringContainsString('whereIn(\'periode\', $periodeAliases)', $bulletinService);
    }

    public function test_select_view_is_status_driven_with_motif_hint(): void
    {
        $view = $this->selectPageSource();

        $this->assertStringContainsString('panelClass()', $view);
        $this->assertStringContainsString('panelIcon()', $view);
        // Le blocage du bouton dérive du statut serveur, avec fallback rétrocompat.
        $this->assertStringContainsString("if (p.status === 'ready') return false;", $view);
        $this->assertStringContainsString("if (p.status === 'needs_reason') return !this.hasIncompleteReason();", $view);
        // Motif : hint minimum 8 caractères + compteur live.
        $this->assertStringContainsString('Minimum 8 caracteres requis pour debloquer.', $view);
        $this->assertStringContainsString("preflight?.existing_empty_count > 0 && !preflight?.recalculer", $view);
    }

    public function test_legacy_annuel_banner_filter_is_selectable_and_scoped(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ESBTPBulletinController.php'));
        $indexView = file_get_contents(resource_path('views/esbtp/bulletins/index.blade.php'));

        $this->assertStringContainsString("\$periodes->push((object) ['id' => 'annuel', 'nom' => 'Annuel (legacy)']);", $controller);
        $this->assertStringContainsString("array_filter(['annee_universitaire_id' => \$annee_id, 'periode_id' => 'annuel'])", $indexView);
    }

    /**
     * L'export groupé passe par le découpage : ouvrir, tranche, assembler,
     * telecharger. Le chemin en une seule requête a disparu — il plafonnait à
     * six bulletins alors qu'une classe en compte soixante-dix.
     *
     * Ce test interroge le routeur et la classe, il ne relit pas leur source.
     */
    public function test_grouped_export_is_chunked_and_serves_the_document_separately(): void
    {
        foreach (['ouvrir', 'tranche', 'assembler', 'telecharger'] as $etape) {
            $this->assertTrue(
                \Illuminate\Support\Facades\Route::has("esbtp.bulletins.export-pdf.$etape"),
                "La route esbtp.bulletins.export-pdf.$etape doit exister."
            );
        }

        // Produire le document et le servir sont deux gestes distincts : le
        // second est rejouable, donc recharger l'onglet ne perd pas le travail.
        $assembler = \Illuminate\Support\Facades\Route::getRoutes()->getByName('esbtp.bulletins.export-pdf.assembler');
        $telecharger = \Illuminate\Support\Facades\Route::getRoutes()->getByName('esbtp.bulletins.export-pdf.telecharger');
        $this->assertContains('POST', $assembler->methods());
        $this->assertContains('GET', $telecharger->methods());

        foreach (['export-pdf', 'export-pdf-preview', 'export-precheck'] as $mort) {
            $this->assertFalse(
                \Illuminate\Support\Facades\Route::has("esbtp.bulletins.$mort"),
                "La route esbtp.bulletins.$mort plafonnait l'export : elle doit avoir disparu."
            );
        }

        $controleur = new \ReflectionClass(\App\Http\Controllers\ESBTPBulletinController::class);
        foreach ([
            'ouvrirExportParTranches',
            'rendreTrancheExport',
            'assemblerExportParTranches',
            'telechargerExportParTranches',
        ] as $methode) {
            $this->assertTrue($controleur->hasMethod($methode), "Le contrôleur doit exposer $methode().");
        }

        foreach (['exportBulkPdf', 'exportBulkPdfPreview', 'prepareBulkExport', 'exportPrecheck'] as $mort) {
            $this->assertFalse($controleur->hasMethod($mort), "$mort() appartient au chemin retiré.");
        }
    }
    public function test_official_generation_persists_subject_rows_and_partial_config_cannot_hide_the_table(): void
    {
        $service = file_get_contents(app_path('Services/BulletinService.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ESBTPBulletinController.php'));
        $settingsController = file_get_contents(app_path('Http/Controllers/ESBTP/ESBTPSettingsController.php'));
        $configurationView = file_get_contents(resource_path('views/esbtp/bulletins/configuration.blade.php'));
        $settingsView = file_get_contents(resource_path('views/esbtp/settings/index.blade.php'));

        $this->assertStringContainsString('private function persistOfficialSubjectRows(ESBTPBulletin $bulletin, array $resultatsParMatiere): void', $service);
        $this->assertStringContainsString('ESBTPResultatMatiere::updateOrCreate(', $service);
        $this->assertStringContainsString('$this->persistOfficialSubjectRows($bulletin, $resultatsParMatiere);', $service);
        $this->assertStringContainsString("name=\"bulletin_save_display\"", $configurationView);
        $this->assertStringContainsString("name=\"settings_save_display\"", $settingsView);
        $this->assertStringContainsString("\$request->boolean('bulletin_save_display')", $controller);
        $this->assertStringContainsString("\$request->boolean('settings_save_display')", $settingsController);
        $this->assertStringContainsString('if (! $treatMissingCheckboxesAsOff && ! $request->exists($formKey))', $settingsController);
    }

    public function test_official_pdf_gives_canonical_bulletin_data_priority_over_renderer_defaults(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ESBTPBulletinController.php'));

        $this->assertStringContainsString(
            '$data = array_replace($data, $this->getOfficialBulletinTemplateDefaults($bulletin, $persist));',
            $controller
        );
        $this->assertStringContainsString('genererDonneesBulletinPreview(', $controller);
        $this->assertStringNotContainsString('Fallback defaults unavailable for official bulletin template', $controller);
    }

    public function test_bulk_generation_recalculates_the_whole_class_rank_after_every_student_is_ready(): void
    {
        $bulkService = $this->bulkServiceSource();
        $bulletinService = file_get_contents(app_path('Services/BulletinService.php'));

        $this->assertStringContainsString(
            '$this->bulletinService->calculerRangsPourClasse($classe->id, $academicYearId, $period);',
            $bulkService
        );
        $this->assertStringContainsString('public function recalculerRangsClasse(', $bulletinService);
        $this->assertStringContainsString('resolveRankCohortClasseId($bulletin)', $bulletinService);
        $this->assertStringContainsString('private function recalculateRanksForCohort(', $bulletinService);
        $this->assertStringContainsString('getEffectiveBulletinAverage($bulletin)', $bulletinService);
    }

    public function test_bts_council_decision_uses_the_configured_field_in_both_pdf_templates(): void
    {
        $yakro = file_get_contents(resource_path('views/esbtp/bulletins/pdf-configurable.blade.php'));
        $abidjan = file_get_contents(resource_path('views/esbtp/bulletins/pdf-configurable-abidjan.blade.php'));

        $this->assertStringContainsString("{{ \$decisionConseil ?? \$councilDecision['text'] ?? \$bulletin->decision_conseil ?? '' }}", $yakro);
        $this->assertStringContainsString("{{ \$decisionConseil ?? \$councilDecision['text'] ?? \$bulletin->decision_conseil ?? '' }}", $abidjan);
        $this->assertStringNotContainsString('decision_conseil = $automaticCouncilDecision', $bulletinService = file_get_contents(app_path('Services/BulletinService.php')));
    }

    public function test_bulletin_configuration_page_can_edit_bts_tenant_policy(): void
    {
        $view = file_get_contents(resource_path('views/esbtp/bulletins/configuration.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ESBTPBulletinController.php'));
        $service = file_get_contents(app_path('Services/BulletinService.php'));
        $settingsController = file_get_contents(app_path('Http/Controllers/ESBTP/ESBTPSettingsController.php'));
        $policy = file_get_contents(app_path('Services/BtsBulletinPolicy.php'));

        $this->assertStringContainsString("@foreach([1 => 'BTS 1', 2 => 'BTS 2'] as \$btsYear => \$btsLabel)", $view);
        $this->assertStringContainsString('name="bulletin_bts{{ $btsYear }}_semester1_weight"', $view);
        $this->assertStringContainsString('name="bulletin_bts{{ $btsYear }}_semester2_weight"', $view);
        $this->assertStringContainsString('name="bulletin_bts1_council_mode"', $view);
        $this->assertStringContainsString('name="bulletin_bts1_council_average_source"', $view);
        $this->assertStringContainsString('name="bulletin_bts1_council_below_text"', $view);
        $this->assertStringContainsString('name="bulletin_bts1_council_at_or_above_text"', $view);
        $this->assertStringContainsString('name="bulletin_bts2_council_mode"', $view);
        $this->assertStringContainsString('name="bulletin_bts2_council_fixed_text"', $view);

        $this->assertStringContainsString("private const SETTING_DEFINITIONS", $policy);
        $this->assertStringContainsString("'required_if:bulletin_bts1_council_mode,threshold'", $policy);
        $this->assertStringContainsString("'required_if:bulletin_bts2_council_mode,fixed'", $policy);
        $this->assertStringContainsString('BtsBulletinPolicy::validationRules()', $controller);
        $this->assertStringContainsString('BtsBulletinPolicy::effectiveSettings(', $controller);
        $this->assertStringContainsString('BtsBulletinPolicy::invalidWeightPairYears($effectiveBtsSettings)', $controller);
        $this->assertStringContainsString('public static function effectiveSettings(array $input, callable $reader): array', $policy);
        $this->assertStringContainsString('public static function invalidWeightPairYears(array $settings): array', $policy);
        $this->assertStringContainsString('fn (string $key, string $default) => SettingsHelper::get($key, $default)', $controller);
        $this->assertStringContainsString('La pondération BTS {$year} doit garder au moins un semestre actif.', $controller);
        $this->assertStringContainsString("'bulletin_bts1_council_mode',", $controller);
        $this->assertStringContainsString("'bulletin_bts2_council_fixed_text',", $controller);

        $this->assertStringContainsString('...BtsBulletinPolicy::readSettings(', $service);
        $this->assertStringContainsString('fn (string $key, string $default) => \\App\\Helpers\\SettingsHelper::get($key, $default)', $service);
        $this->assertStringContainsString('BtsBulletinPolicy::settingDefinitions()', $settingsController);
    }

    public function test_grouped_export_cover_uses_tenant_pdf_colors(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ESBTPBulletinController.php'));
        $cover = file_get_contents(resource_path('views/esbtp/bulletins/pdf-export-cover.blade.php'));

        // Le contrôleur passe les réglages PDF du tenant à la page de garde.
        $this->assertStringContainsString("'pdfSettings' => SettingsHelper::getPdfSettings()", $controller);
        // La page de garde applique les couleurs configurées (plus de bleu KLASSCI codé en dur).
        $this->assertStringContainsString("\$pdfSettings['header_bg_color']", $cover);
        $this->assertStringContainsString('{{ $covHeaderBg }}', $cover);
        $this->assertStringNotContainsString('background: #0453cb;', $cover);
    }

    public function test_abidjan_subject_table_headers_use_plain_french_and_white_text(): void
    {
        $abidjan = file_get_contents(resource_path('views/esbtp/bulletins/pdf-configurable-abidjan.blade.php'));
        $theme = file_get_contents(resource_path('views/pdf/partials/theme.blade.php'));

        $this->assertStringContainsString('<th>Mati&egrave;re</th>', $abidjan);
        $this->assertStringNotContainsString('MatiÃ', $abidjan);
        $this->assertStringContainsString('color: #ffffff;', $abidjan);
        $this->assertStringContainsString('background: {{ $pdfPrimary }};', $abidjan);
        $this->assertStringContainsString('color: #ffffff !important;', $theme);
    }

    public function test_config_modal_has_cross_semester_copy_and_save_scope(): void
    {
        $view = $this->selectPageSource();

        // Bouton copie + panneau Écraser/Compléter.
        $this->assertStringContainsString('fetchOtherSemester()', $view);
        $this->assertStringContainsString("applyCopy('overwrite')", $view);
        $this->assertStringContainsString("applyCopy('merge')", $view);
        $this->assertStringContainsString('Copier depuis le ', $view);
        // La copie relit inline-data avec l'autre semestre (pas de nouvel endpoint).
        $this->assertStringContainsString('periode: this.otherPeriode()', $view);
        // Écraser ne blanchit jamais : copie uniquement quand la source a une valeur.
        $this->assertStringContainsString("src.coeff !== null && (mode === 'overwrite' || coeffEmpty)", $view);
        $this->assertStringContainsString("src.prof !== '' && (mode === 'overwrite' || profEmpty)", $view);
        // Portée du save : les deux semestres = periode 'annuel' (chemin backend existant).
        $this->assertStringContainsString("this.saveScope === 'both' ? 'annuel' : this.configModal.context.periode", $view);
        $this->assertStringContainsString('Les deux semestres', $view);
        $this->assertStringContainsString('Enregistrer (S1 + S2)', $view);
        // Flash visuel des cellules copiées.
        $this->assertStringContainsString('bus-config-cell--copied', $view);
    }

    public function test_inline_save_service_writes_both_semesters_for_annuel(): void
    {
        // Le save « les deux semestres » dépend de ce contrat backend : periode=annuel
        // boucle S1+S2 et écrit types + coefficients + template professeurs par semestre.
        $service = file_get_contents(app_path('Services/BulletinInlineConfigurationService.php'));

        $this->assertStringContainsString("return \$periode === 'annuel' ? ['semestre1', 'semestre2'] : [\$periode];", $service);
        $this->assertStringContainsString('foreach ($this->periodsFor($periode) as $targetPeriode)', $service);
        $this->assertStringContainsString('saveProfesseursTemplate(', $service);
        $this->assertStringContainsString('ESBTPMatiereCoefficient::updateOrCreate($coefficientQuery', $service);
    }

    private function bulkServiceSource(): string
    {
        return file_get_contents(app_path('Domain/AcademicPilotage/Services/BtsBulkBulletinGenerationService.php'));
    }

    private function selectPageSource(): string
    {
        return file_get_contents(resource_path('views/esbtp/bulletins/select.blade.php'))
            ."\n"
            .file_get_contents(resource_path('views/esbtp/bulletins/partials/select-config-modal.blade.php'))
            ."\n"
            .file_get_contents(resource_path('views/esbtp/bulletins/partials/select-scripts.blade.php'));
    }
}
