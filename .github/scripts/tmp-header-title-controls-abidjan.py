from pathlib import Path


def replace_once(path: str, old: str, new: str) -> None:
    p = Path(path)
    text = p.read_text()
    count = text.count(old)
    if count != 1:
        raise SystemExit(f"{path}: expected one occurrence, found {count}: {old[:140]!r}")
    p.write_text(text.replace(old, new, 1))


generic = 'resources/views/esbtp/bulletins/pdf-configurable.blade.php'
abidjan = 'resources/views/esbtp/bulletins/pdf-configurable-abidjan.blade.php'
config = 'resources/views/esbtp/bulletins/configuration.blade.php'
controller = 'app/Http/Controllers/ESBTPBulletinController.php'
service = 'app/Services/BulletinService.php'

# Generic/Yakro model selectable on the Abidjan tenant.
replace_once(
    generic,
    """        $typeScale = \\App\\Services\\BulletinTypography::scale($settings['bulletin_font_size'] ?? 13);\n        $marginVertical = max(2, min(25, (int) ($settings['bulletin_margin_vertical'] ?? 5)));\n""",
    """        $typeScale = \\App\\Services\\BulletinTypography::scale($settings['bulletin_font_size'] ?? 13);\n        $headerTitleFont = max(8, min(30, (int) (($settings['bulletin_header_title_font_size'] ?? '') ?: 18)));\n        $headerRightFont = max(6, min(22, (int) (($settings['bulletin_header_right_font_size'] ?? '') ?: 12)));\n        $marginVertical = max(2, min(25, (int) ($settings['bulletin_margin_vertical'] ?? 5)));\n""",
)
replace_once(generic, """            font-size: {{ $typeScale['title'] }}px;\n            text-decoration: underline;\n""", """            font-size: {{ $headerTitleFont }}px;\n            line-height: 1.15;\n            letter-spacing: 0.04em;\n            text-decoration: underline;\n""")
replace_once(generic, """            font-size: {{ min(30, $typeScale['table'] + 4) }}px;\n""", """            font-size: {{ $headerRightFont }}px;\n""")
replace_once(generic, """        .header-right .year {\n            font-size: {{ $typeScale['info'] }}px;\n""", """        .header-right .year {\n            font-size: {{ $headerRightFont }}px;\n""")

# Real Abidjan model: same two controls, title prominent, semester same size as
# the secondary right-side information rather than receiving a +4px boost.
replace_once(
    abidjan,
    """        $typeScale     = \\App\\Services\\BulletinTypography::scale($settings['bulletin_font_size'] ?? 13);\n""",
    """        $typeScale     = \\App\\Services\\BulletinTypography::scale($settings['bulletin_font_size'] ?? 13);\n        $headerTitleFont = max(8, min(30, (int) (($settings['bulletin_header_title_font_size'] ?? '') ?: 18)));\n        $headerRightFont = max(6, min(22, (int) (($settings['bulletin_header_right_font_size'] ?? '') ?: 12)));\n""",
)
replace_once(
    abidjan,
    """            font-size: {{ $typeScale['title'] }}px;\n            text-transform: uppercase;\n            letter-spacing: 0.05em;\n            color: {{ $pdfPrimary }};\n""",
    """            font-size: {{ $headerTitleFont }}px;\n            text-transform: uppercase;\n            letter-spacing: 0.05em;\n            color: {{ $pdfPrimary }};\n""",
)
replace_once(abidjan, """            font-size: {{ min(30, $typeScale['table'] + 4) }}px;\n""", """            font-size: {{ $headerRightFont }}px;\n""")
replace_once(abidjan, """        .academic-year {\n            font-size: {{ $typeScale['info'] }}px;\n""", """        .academic-year {\n            font-size: {{ $headerRightFont }}px;\n""")

# Configuration UI: expose the controls on Abidjan too.
replace_once(
    config,
    """                                </select>\n\n                                <label class=\"bcfg-label\" style=\"margin-top:.85rem;\">Marge haut / bas (mm)</label>\n""",
    """                                </select>\n\n                                <div style=\"margin-top:1rem;padding:.85rem;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;\">\n                                    <div class=\"bcfg-label\" style=\"margin-bottom:.55rem;\">En-tête — hiérarchie du titre</div>\n                                    <div class=\"row g-2\">\n                                        <div class=\"col-6\">\n                                            <label class=\"bcfg-label\">Titre « BULLETIN DE NOTES »</label>\n                                            <input type=\"number\" class=\"bcfg-input\" name=\"bulletin_header_title_font_size\" min=\"8\" max=\"30\" step=\"1\"\n                                                   value=\"{{ $settings['bulletin_header_title_font_size'] ?? '18' }}\">\n                                        </div>\n                                        <div class=\"col-6\">\n                                            <label class=\"bcfg-label\">Semestre / diplôme / niveau / année</label>\n                                            <input type=\"number\" class=\"bcfg-input\" name=\"bulletin_header_right_font_size\" min=\"6\" max=\"22\" step=\"1\"\n                                                   value=\"{{ $settings['bulletin_header_right_font_size'] ?? '12' }}\">\n                                        </div>\n                                    </div>\n                                    <div class=\"bcfg-hint\" style=\"margin-top:.45rem;\">Le titre du document est volontairement dominant. Le semestre utilise la même taille que les informations secondaires de l'en-tête, notamment le diplôme/cycle lorsque le modèle les affiche. Réglages compatibles Yakro et Abidjan.</div>\n                                </div>\n\n                                <label class=\"bcfg-label\" style=\"margin-top:.85rem;\">Marge haut / bas (mm)</label>\n""",
)

# Controller: validate and persist both keys.
replace_once(
    controller,
    """    public function saveConfiguration(Request $request)\n    {\n        $effectiveBtsSettings = BtsBulletinPolicy::effectiveSettings(\n""",
    """    public function saveConfiguration(Request $request)\n    {\n        $request->validate([\n            'bulletin_header_title_font_size' => ['nullable', 'integer', 'min:8', 'max:30'],\n            'bulletin_header_right_font_size' => ['nullable', 'integer', 'min:6', 'max:22'],\n        ]);\n\n        $effectiveBtsSettings = BtsBulletinPolicy::effectiveSettings(\n""",
)
replace_once(
    controller,
    """                'bulletin_font_size',\n                'bulletin_margin_vertical',\n""",
    """                'bulletin_font_size',\n                'bulletin_header_title_font_size',\n                'bulletin_header_right_font_size',\n                'bulletin_margin_vertical',\n""",
)

# Canonical settings payload consumed by both models.
replace_once(
    service,
    """            'bulletin_school_name_custom' => \\App\\Helpers\\SettingsHelper::get('bulletin_school_name_custom', ''),\n            'bulletin_font_size' => \\App\\Helpers\\SettingsHelper::get('bulletin_font_size', '13'),\n            'bulletin_style' => \\App\\Helpers\\SettingsHelper::get('bulletin_style', 'yakro'),\n""",
    """            'bulletin_school_name_custom' => \\App\\Helpers\\SettingsHelper::get('bulletin_school_name_custom', ''),\n            'bulletin_font_size' => \\App\\Helpers\\SettingsHelper::get('bulletin_font_size', '13'),\n            'bulletin_header_title_font_size' => \\App\\Helpers\\SettingsHelper::get('bulletin_header_title_font_size', '18'),\n            'bulletin_header_right_font_size' => \\App\\Helpers\\SettingsHelper::get('bulletin_header_right_font_size', '12'),\n            'bulletin_style' => \\App\\Helpers\\SettingsHelper::get('bulletin_style', 'yakro'),\n""",
)

# Static checks.
g = Path(generic).read_text(); a = Path(abidjan).read_text(); c = Path(config).read_text(); ctl = Path(controller).read_text(); s = Path(service).read_text()
assert 'font-size: {{ $headerTitleFont }}px;' in g
assert 'font-size: {{ $headerRightFont }}px;' in g
assert "min(30, $typeScale['table'] + 4)" not in g
assert 'font-size: {{ $headerTitleFont }}px;' in a
assert 'font-size: {{ $headerRightFont }}px;' in a
assert "min(30, $typeScale['table'] + 4)" not in a
assert 'Titre « BULLETIN DE NOTES »' in c
assert 'Semestre / diplôme / niveau / année' in c
assert "'bulletin_header_title_font_size' => ['nullable', 'integer', 'min:8', 'max:30']" in ctl
assert "'bulletin_header_right_font_size' => ['nullable', 'integer', 'min:6', 'max:22']" in ctl
assert "get('bulletin_header_title_font_size', '18')" in s
assert "get('bulletin_header_right_font_size', '12')" in s
