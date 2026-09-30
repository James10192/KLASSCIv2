from pathlib import Path


def load(path: str) -> str:
    return Path(path).read_text()


def save(path: str, text: str) -> None:
    Path(path).write_text(text)


def replace_once(text: str, old: str, new: str, label: str) -> str:
    count = text.count(old)
    if count != 1:
        raise SystemExit(f"{label}: expected 1 occurrence, found {count}: {old[:100]!r}")
    return text.replace(old, new, 1)


# -----------------------------------------------------------------------------
# 1. Configuration UI
# -----------------------------------------------------------------------------
path = 'resources/views/esbtp/bulletins/configuration.blade.php'
text = load(path)
text = replace_once(
    text,
    "$currentHeaderScale = max(80, min(150, (int) ($settings['bulletin_header_scale'] ?? 100)));",
    "$currentHeaderScale = max(70, min(220, (int) ($settings['bulletin_header_scale'] ?? 100)));",
    'config currentHeaderScale',
)
text = replace_once(
    text,
    'min="80" max="150" step="5" x-model.number="headerScale"',
    'min="70" max="220" step="5" x-model.number="headerScale"',
    'config header range',
)
old_hint = "<div class=\"bcfg-hint\" style=\"margin-top:.35rem;\">Agrandit uniquement République, ministère, logo, nom de l'école et titre du bulletin. 100 % = taille actuelle.</div>"
advanced_header = '''<div class="bcfg-hint" style="margin-top:.35rem;">Échelle générale de l'en-tête Yakro. Elle multiplie les tailles détaillées ci-dessous. 100 % = taille de référence.</div>

                                <div style="margin-top:1rem;padding:.85rem;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;">
                                    <div class="bcfg-label" style="margin-bottom:.55rem;">En-tête Yakro — réglages détaillés</div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="bcfg-label">République / ministère</label>
                                            <input type="number" class="bcfg-input" name="bulletin_header_left_font_size" min="6" max="24" step="1"
                                                   value="{{ $settings['bulletin_header_left_font_size'] ?: '11' }}">
                                        </div>
                                        <div class="col-6">
                                            <label class="bcfg-label">Nom de l'école</label>
                                            <input type="number" class="bcfg-input" name="bulletin_header_school_name_font_size" min="8" max="30" step="1"
                                                   value="{{ $settings['bulletin_header_school_name_font_size'] ?: '16' }}">
                                        </div>
                                        <div class="col-6">
                                            <label class="bcfg-label">Coordonnées école</label>
                                            <input type="number" class="bcfg-input" name="bulletin_header_school_meta_font_size" min="6" max="20" step="1"
                                                   value="{{ $settings['bulletin_header_school_meta_font_size'] ?: '10' }}">
                                        </div>
                                        <div class="col-6">
                                            <label class="bcfg-label">Titre du bulletin</label>
                                            <input type="number" class="bcfg-input" name="bulletin_header_title_font_size" min="8" max="30" step="1"
                                                   value="{{ $settings['bulletin_header_title_font_size'] ?: '15' }}">
                                        </div>
                                        <div class="col-6">
                                            <label class="bcfg-label">Période / cycle / année</label>
                                            <input type="number" class="bcfg-input" name="bulletin_header_right_font_size" min="6" max="22" step="1"
                                                   value="{{ $settings['bulletin_header_right_font_size'] ?: '12' }}">
                                        </div>
                                        <div class="col-6">
                                            <label class="bcfg-label">Hauteur du logo</label>
                                            <input type="number" class="bcfg-input" name="bulletin_header_logo_height" min="40" max="180" step="2"
                                                   value="{{ $settings['bulletin_header_logo_height'] ?: '72' }}">
                                        </div>
                                    </div>
                                    <div class="bcfg-hint" style="margin-top:.45rem;">Valeurs en pixels avant application de l'échelle générale. Le PDF reste en mise en page tableau, compatible DomPDF.</div>
                                </div>'''
text = replace_once(text, old_hint, advanced_header, 'config header advanced block')
text = replace_once(text, 'min="20" max="160" step="2"', 'min="70" max="240" step="2"', 'config signature range')
text = replace_once(
    text,
    "value=\"{{ $settings['bulletin_signature_height'] ?: '44' }}\">",
    "value=\"{{ $settings['bulletin_signature_height'] ?: '70' }}\">",
    'config signature default',
)
sig_marker = "value=\"{{ $settings['bulletin_signature_height'] ?: '70' }}\">"
footer_controls = '''value="{{ $settings['bulletin_signature_height'] ?: '70' }}">

                                <div class="row g-2" style="margin-top:.15rem;">
                                    <div class="col-6">
                                        <label class="bcfg-label">Largeur signature (px)</label>
                                        <input type="number" class="bcfg-input" name="bulletin_signature_width" min="180" max="520" step="5"
                                               value="{{ $settings['bulletin_signature_width'] ?: '250' }}">
                                    </div>
                                    <div class="col-6">
                                        <label class="bcfg-label">Police signature (px)</label>
                                        <input type="number" class="bcfg-input" name="bulletin_signature_font_size" min="6" max="20" step="1"
                                               value="{{ $settings['bulletin_signature_font_size'] ?: '11' }}">
                                    </div>
                                </div>

                                <div style="margin-top:1rem;padding:.85rem;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;">
                                    <div class="bcfg-label" style="margin-bottom:.55rem;">Bas de page</div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="bcfg-label">Police « Édition du »</label>
                                            <input type="number" class="bcfg-input" name="bulletin_edition_font_size" min="6" max="18" step="1"
                                                   value="{{ $settings['bulletin_edition_font_size'] ?: '11' }}">
                                        </div>
                                        <div class="col-6">
                                            <label class="bcfg-label">Opacité « Édition du »</label>
                                            <input type="number" class="bcfg-input" name="bulletin_edition_opacity" min="10" max="100" step="5"
                                                   value="{{ $settings['bulletin_edition_opacity'] ?: '100' }}">
                                        </div>
                                        <div class="col-6">
                                            <label class="bcfg-label">Police « aucun duplicata »</label>
                                            <input type="number" class="bcfg-input" name="bulletin_authenticity_font_size" min="6" max="18" step="1"
                                                   value="{{ $settings['bulletin_authenticity_font_size'] ?: '11' }}">
                                        </div>
                                        <div class="col-6">
                                            <label class="bcfg-label">Opacité « aucun duplicata »</label>
                                            <input type="number" class="bcfg-input" name="bulletin_authenticity_opacity" min="10" max="100" step="5"
                                                   value="{{ $settings['bulletin_authenticity_opacity'] ?: '100' }}">
                                        </div>
                                    </div>
                                    <div class="bcfg-hint" style="margin-top:.45rem;">Opacité : 100 % = texte normal, 10 % = très discret.</div>
                                </div>'''
text = replace_once(text, sig_marker, footer_controls, 'config footer/signature controls')
text = replace_once(
    text,
    'headerScale: Math.max(80, Math.min(150, Number(initialHeaderScale || 100))),',
    'headerScale: Math.max(70, Math.min(220, Number(initialHeaderScale || 100))),',
    'config js header clamp',
)
save(path, text)


# -----------------------------------------------------------------------------
# 2. Controller validation + allow-list
# -----------------------------------------------------------------------------
path = 'app/Http/Controllers/ESBTPBulletinController.php'
text = load(path)
old_validation = '''        $request->validate([
            'bulletin_header_scale' => ['nullable', 'integer', 'min:80', 'max:150'],
        ]);'''
new_validation = '''        $request->validate([
            'bulletin_header_scale' => ['nullable', 'integer', 'min:70', 'max:220'],
            'bulletin_header_left_font_size' => ['nullable', 'integer', 'min:6', 'max:24'],
            'bulletin_header_school_name_font_size' => ['nullable', 'integer', 'min:8', 'max:30'],
            'bulletin_header_school_meta_font_size' => ['nullable', 'integer', 'min:6', 'max:20'],
            'bulletin_header_title_font_size' => ['nullable', 'integer', 'min:8', 'max:30'],
            'bulletin_header_right_font_size' => ['nullable', 'integer', 'min:6', 'max:22'],
            'bulletin_header_logo_height' => ['nullable', 'integer', 'min:40', 'max:180'],
            'bulletin_signature_height' => ['nullable', 'integer', 'min:70', 'max:240'],
            'bulletin_signature_width' => ['nullable', 'integer', 'min:180', 'max:520'],
            'bulletin_signature_font_size' => ['nullable', 'integer', 'min:6', 'max:20'],
            'bulletin_edition_font_size' => ['nullable', 'integer', 'min:6', 'max:18'],
            'bulletin_edition_opacity' => ['nullable', 'integer', 'min:10', 'max:100'],
            'bulletin_authenticity_font_size' => ['nullable', 'integer', 'min:6', 'max:18'],
            'bulletin_authenticity_opacity' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);'''
text = replace_once(text, old_validation, new_validation, 'controller validation')
text = replace_once(
    text,
    "                'bulletin_header_scale',\n                'bulletin_margin_vertical',",
    "                'bulletin_header_scale',\n                'bulletin_header_left_font_size',\n                'bulletin_header_school_name_font_size',\n                'bulletin_header_school_meta_font_size',\n                'bulletin_header_title_font_size',\n                'bulletin_header_right_font_size',\n                'bulletin_header_logo_height',\n                'bulletin_margin_vertical',",
    'controller header allow-list',
)
text = replace_once(
    text,
    "                'bulletin_signature_height',\n                'bulletin_school_name_custom',",
    "                'bulletin_signature_height',\n                'bulletin_signature_width',\n                'bulletin_signature_font_size',\n                'bulletin_edition_font_size',\n                'bulletin_edition_opacity',\n                'bulletin_authenticity_font_size',\n                'bulletin_authenticity_opacity',\n                'bulletin_school_name_custom',",
    'controller footer allow-list',
)
save(path, text)


# -----------------------------------------------------------------------------
# 3. Canonical PDF settings
# -----------------------------------------------------------------------------
path = 'app/Services/BulletinService.php'
text = load(path)
text = replace_once(
    text,
    "            'bulletin_header_scale' => \\App\\Helpers\\SettingsHelper::get('bulletin_header_scale', '100'),\n            'bulletin_style' =>",
    "            'bulletin_header_scale' => \\App\\Helpers\\SettingsHelper::get('bulletin_header_scale', '100'),\n            'bulletin_header_left_font_size' => \\App\\Helpers\\SettingsHelper::get('bulletin_header_left_font_size', ''),\n            'bulletin_header_school_name_font_size' => \\App\\Helpers\\SettingsHelper::get('bulletin_header_school_name_font_size', ''),\n            'bulletin_header_school_meta_font_size' => \\App\\Helpers\\SettingsHelper::get('bulletin_header_school_meta_font_size', ''),\n            'bulletin_header_title_font_size' => \\App\\Helpers\\SettingsHelper::get('bulletin_header_title_font_size', ''),\n            'bulletin_header_right_font_size' => \\App\\Helpers\\SettingsHelper::get('bulletin_header_right_font_size', ''),\n            'bulletin_header_logo_height' => \\App\\Helpers\\SettingsHelper::get('bulletin_header_logo_height', ''),\n            'bulletin_style' =>",
    'service header settings',
)
text = replace_once(
    text,
    "            'bulletin_signature_height' => \\App\\Helpers\\SettingsHelper::get('bulletin_signature_height', '44'),\n            'bulletin_show_general_subjects' =>",
    "            'bulletin_signature_height' => \\App\\Helpers\\SettingsHelper::get('bulletin_signature_height', '70'),\n            'bulletin_signature_width' => \\App\\Helpers\\SettingsHelper::get('bulletin_signature_width', '250'),\n            'bulletin_signature_font_size' => \\App\\Helpers\\SettingsHelper::get('bulletin_signature_font_size', ''),\n            'bulletin_edition_font_size' => \\App\\Helpers\\SettingsHelper::get('bulletin_edition_font_size', ''),\n            'bulletin_edition_opacity' => \\App\\Helpers\\SettingsHelper::get('bulletin_edition_opacity', '100'),\n            'bulletin_authenticity_font_size' => \\App\\Helpers\\SettingsHelper::get('bulletin_authenticity_font_size', ''),\n            'bulletin_authenticity_opacity' => \\App\\Helpers\\SettingsHelper::get('bulletin_authenticity_opacity', '100'),\n            'bulletin_show_general_subjects' =>",
    'service footer settings',
)
save(path, text)


# -----------------------------------------------------------------------------
# 4. Yakro DomPDF template
# -----------------------------------------------------------------------------
path = 'resources/views/esbtp/bulletins/pdf-configurable.blade.php'
text = load(path)
old_php = '''        $typeScale = \\App\\Services\\BulletinTypography::scale($settings['bulletin_font_size'] ?? 13);
        $headerScale = max(80, min(150, (int) ($settings['bulletin_header_scale'] ?? 100)));
        $headerRatio = $headerScale / 100;
        $headerFont = static fn ($px) => round(((float) $px) * $headerRatio, 2);
        $headerLogoSize = max(58, min(108, (int) round(72 * $headerRatio)));'''
new_php = '''        $typeScale = \\App\\Services\\BulletinTypography::scale($settings['bulletin_font_size'] ?? 13);
        $headerScale = max(70, min(220, (int) ($settings['bulletin_header_scale'] ?? 100)));
        $headerRatio = $headerScale / 100;
        $headerSetting = static function (string $key, float $fallback, float $min, float $max) use ($settings, $headerRatio): float {
            $raw = $settings[$key] ?? null;
            $base = is_numeric($raw) && (float) $raw > 0 ? (float) $raw : $fallback;
            return round(max($min, min($max, $base * $headerRatio)), 2);
        };
        $headerLeftFont = $headerSetting('bulletin_header_left_font_size', $typeScale['table_head'], 6, 32);
        $headerSchoolNameFont = $headerSetting('bulletin_header_school_name_font_size', $typeScale['heading'], 8, 34);
        $headerSchoolMetaFont = $headerSetting('bulletin_header_school_meta_font_size', $typeScale['meta'], 6, 24);
        $headerTitleFont = $headerSetting('bulletin_header_title_font_size', $typeScale['title'], 8, 34);
        $headerRightFont = $headerSetting('bulletin_header_right_font_size', $typeScale['info'], 6, 26);
        $logoBaseHeight = is_numeric($settings['bulletin_header_logo_height'] ?? null) ? (int) $settings['bulletin_header_logo_height'] : 72;
        $headerLogoSize = max(40, min(180, (int) round($logoBaseHeight * $headerRatio)));
        $signatureHeight = max(70, min(240, (int) ($settings['bulletin_signature_height'] ?? 70)));
        $signatureWidth = max(180, min(520, (int) ($settings['bulletin_signature_width'] ?? 250)));
        $signatureFontSize = max(6, min(20, (int) (($settings['bulletin_signature_font_size'] ?? '') ?: $typeScale['signature'])));
        $editionFontSize = max(6, min(18, (int) (($settings['bulletin_edition_font_size'] ?? '') ?: $typeScale['body'])));
        $editionOpacity = max(10, min(100, (int) ($settings['bulletin_edition_opacity'] ?? 100))) / 100;
        $authenticityFontSize = max(6, min(18, (int) (($settings['bulletin_authenticity_font_size'] ?? '') ?: $typeScale['body'])));
        $authenticityOpacity = max(10, min(100, (int) ($settings['bulletin_authenticity_opacity'] ?? 100))) / 100;'''
text = replace_once(text, old_php, new_php, 'pdf php settings')
old_footer_css = '''        .edition-footer {
            margin-top: 10px;
            font-size: {{ $typeScale['body'] }}px;
            color: #6b7280;
            text-align: left;
        }
        .edition-authenticity {
            margin-top: 4px;
            font-size: {{ $typeScale['body'] }}px;
            color: #6b7280;
            text-align: center;
        }'''
new_footer_css = '''        .edition-footer {
            margin-top: 10px;
            font-size: {{ $editionFontSize }}px;
            color: #6b7280;
            opacity: {{ $editionOpacity }};
            text-align: left;
        }
        .edition-authenticity {
            margin-top: 4px;
            font-size: {{ $authenticityFontSize }}px;
            color: #6b7280;
            opacity: {{ $authenticityOpacity }};
            text-align: center;
        }'''
text = replace_once(text, old_footer_css, new_footer_css, 'pdf footer css')
text = replace_once(text, "font-size: {{ $headerFont($typeScale['table_head']) }}px;", "font-size: {{ $headerLeftFont }}px;", 'pdf left font')
text = replace_once(text, "font-size: {{ $headerFont($typeScale['heading']) }}px;", "font-size: {{ $headerSchoolNameFont }}px;", 'pdf school name font')
text = replace_once(text, "font-size: {{ $headerFont($typeScale['meta']) }}px;", "font-size: {{ $headerSchoolMetaFont }}px;", 'pdf school meta font')
text = replace_once(text, "font-size: {{ $headerFont($typeScale['title']) }}px;", "font-size: {{ $headerTitleFont }}px;", 'pdf title font')
text = replace_once(text, "font-size: {{ $headerFont($typeScale['table']) }}px;", "font-size: {{ $headerRightFont }}px;", 'pdf period font')
# Two header right/info occurrences: base column and .year. Replace both intentionally.
count = text.count("font-size: {{ $headerFont($typeScale['info']) }}px;")
if count != 2:
    raise SystemExit(f'pdf right font: expected 2 occurrences, found {count}')
text = text.replace("font-size: {{ $headerFont($typeScale['info']) }}px;", "font-size: {{ $headerRightFont }}px;")
old_sig_css = '''        .signature-box {
            display: inline-block;
            text-align: center;
            min-width: 250px;
        }
        .signature-line {
            width: 250px;
            height: 70px;
            border-bottom: 1.5px solid {{ $pdfPrimary }};
            margin-top: 4px;
        }'''
new_sig_css = '''        .signature-box {
            display: inline-block;
            text-align: center;
            min-width: {{ $signatureWidth }}px;
        }
        .signature-line {
            width: {{ $signatureWidth }}px;
            height: {{ $signatureHeight }}px;
            border-bottom: 1.5px solid {{ $pdfPrimary }};
            margin-top: 4px;
        }'''
text = replace_once(text, old_sig_css, new_sig_css, 'pdf signature css')
text = replace_once(text, "font-size: {{ $typeScale['signature'] }}px;\">{{ $directorTitle }}", "font-size: {{ $signatureFontSize }}px;\">{{ $directorTitle }}", 'pdf signature title font')
text = replace_once(text, "font-size: {{ $typeScale['signature'] }}px;\">{{ $directorName }}", "font-size: {{ $signatureFontSize }}px;\">{{ $directorName }}", 'pdf signature name font')
save(path, text)


# -----------------------------------------------------------------------------
# 5. Tests
# -----------------------------------------------------------------------------
path = 'tests/Feature/Bulletin/BtsBulletinConfigurationHttpTest.php'
text = load(path)
text = replace_once(text, "'bulletin_header_scale' => '175',", "'bulletin_header_scale' => '225',", 'test unsafe scale')
text = replace_once(
    text,
    "            'bulletin_header_scale' => '125',\n            'bulletin_bts1_s1_council_title' =>",
    "            'bulletin_header_scale' => '125',\n            'bulletin_header_left_font_size' => '14',\n            'bulletin_header_school_name_font_size' => '20',\n            'bulletin_header_school_meta_font_size' => '11',\n            'bulletin_header_title_font_size' => '19',\n            'bulletin_header_right_font_size' => '13',\n            'bulletin_header_logo_height' => '110',\n            'bulletin_signature_height' => '120',\n            'bulletin_signature_width' => '330',\n            'bulletin_signature_font_size' => '12',\n            'bulletin_edition_font_size' => '10',\n            'bulletin_edition_opacity' => '75',\n            'bulletin_authenticity_font_size' => '12',\n            'bulletin_authenticity_opacity' => '55',\n            'bulletin_bts1_s1_council_title' =>",
    'test request payload',
)
text = replace_once(
    text,
    "        $response->assertJsonPath('settings.bulletin_header_scale', '125');\n        $response->assertJsonPath(",
    "        $response->assertJsonPath('settings.bulletin_header_scale', '125');\n        $response->assertJsonPath('settings.bulletin_header_left_font_size', '14');\n        $response->assertJsonPath('settings.bulletin_header_school_name_font_size', '20');\n        $response->assertJsonPath('settings.bulletin_header_logo_height', '110');\n        $response->assertJsonPath('settings.bulletin_signature_height', '120');\n        $response->assertJsonPath('settings.bulletin_signature_width', '330');\n        $response->assertJsonPath('settings.bulletin_authenticity_font_size', '12');\n        $response->assertJsonPath('settings.bulletin_authenticity_opacity', '55');\n        $response->assertJsonPath(",
    'test json assertions',
)
text = replace_once(
    text,
    "        self::assertSame('125', SettingsHelper::get('bulletin_header_scale'));\n        self::assertSame(",
    "        self::assertSame('125', SettingsHelper::get('bulletin_header_scale'));\n        self::assertSame('14', SettingsHelper::get('bulletin_header_left_font_size'));\n        self::assertSame('110', SettingsHelper::get('bulletin_header_logo_height'));\n        self::assertSame('120', SettingsHelper::get('bulletin_signature_height'));\n        self::assertSame('55', SettingsHelper::get('bulletin_authenticity_opacity'));\n        self::assertSame(",
    'test settings assertions',
)
save(path, text)

path = 'tests/Feature/Bulletin/BulletinYakroTypographyContractTest.php'
text = load(path)
text = replace_once(
    text,
    '''        self::assertStringContainsString("font-size: {{ \\$typeScale['signature'] }}px", $view);''',
    '''        self::assertStringContainsString('bulletin_header_left_font_size', $view);
        self::assertStringContainsString('bulletin_header_school_name_font_size', $view);
        self::assertStringContainsString('bulletin_header_logo_height', $view);
        self::assertStringContainsString("height: {{ \\$signatureHeight }}px", $view);
        self::assertStringContainsString("width: {{ \\$signatureWidth }}px", $view);
        self::assertStringContainsString("font-size: {{ \\$signatureFontSize }}px", $view);
        self::assertStringContainsString("font-size: {{ \\$authenticityFontSize }}px", $view);
        self::assertStringContainsString("opacity: {{ \\$authenticityOpacity }}", $view);''',
    'typography contract',
)
save(path, text)

print('Yakro PDF controls patch v2 applied successfully')
