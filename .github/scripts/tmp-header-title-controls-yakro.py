from pathlib import Path


def replace_once(path: str, old: str, new: str) -> None:
    p = Path(path)
    text = p.read_text()
    count = text.count(old)
    if count != 1:
        raise SystemExit(f"{path}: expected one occurrence, found {count}: {old[:140]!r}")
    p.write_text(text.replace(old, new, 1))


yakro = 'resources/views/esbtp/bulletins/pdf-configurable.blade.php'
abidjan = 'resources/views/esbtp/bulletins/pdf-configurable-abidjan.blade.php'
config = 'resources/views/esbtp/bulletins/configuration.blade.php'
service = 'app/Services/BulletinService.php'
test = 'tests/Feature/Bulletin/BulletinYakroTypographyContractTest.php'

# 1) Yakro: the document title is the visual hierarchy. The semester keeps the
# existing bordered design, but uses exactly the same configurable size as the
# diploma/cycle lines (Brevet de Technicien Supérieur, BTS, year).
replace_once(
    yakro,
    """        .header-right .title {\n            font-weight: 700;\n            font-size: {{ $headerTitleFont }}px;\n            text-decoration: underline;\n            color: {{ $pdfPrimary }};\n            text-transform: uppercase;\n            margin-bottom: 4px;\n        }\n""",
    """        .header-right .title {\n            font-weight: 700;\n            font-size: {{ $headerTitleFont }}px;\n            line-height: 1.15;\n            letter-spacing: 0.04em;\n            text-decoration: underline;\n            color: {{ $pdfPrimary }};\n            text-transform: uppercase;\n            margin-bottom: 5px;\n        }\n""",
)
replace_once(
    yakro,
    """            font-size: {{ min(34, $headerRightFont + 4) }}px;\n""",
    """            font-size: {{ $headerRightFont }}px;\n""",
)

# 2) Abidjan model shipped in this tenant: use the same two controls so switching
# model does not silently change typography behaviour.
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
replace_once(
    abidjan,
    """            font-size: {{ min(30, $typeScale['table'] + 4) }}px;\n""",
    """            font-size: {{ $headerRightFont }}px;\n""",
)
replace_once(
    abidjan,
    """        .academic-year {\n            font-size: {{ $typeScale['info'] }}px;\n""",
    """        .academic-year {\n            font-size: {{ $headerRightFont }}px;\n""",
)

# 3) Configuration UI: make the intent explicit and give useful defaults.
replace_once(
    config,
    """                                            <label class=\"bcfg-label\">Titre du bulletin</label>\n                                            <input type=\"number\" class=\"bcfg-input\" name=\"bulletin_header_title_font_size\" min=\"8\" max=\"30\" step=\"1\"\n                                                   value=\"{{ $settings['bulletin_header_title_font_size'] ?: '15' }}\">\n""",
    """                                            <label class=\"bcfg-label\">Titre « BULLETIN DE NOTES »</label>\n                                            <input type=\"number\" class=\"bcfg-input\" name=\"bulletin_header_title_font_size\" min=\"8\" max=\"30\" step=\"1\"\n                                                   value=\"{{ $settings['bulletin_header_title_font_size'] ?: '18' }}\">\n""",
)
replace_once(
    config,
    """                                            <label class=\"bcfg-label\">Période / cycle / année</label>\n                                            <input type=\"number\" class=\"bcfg-input\" name=\"bulletin_header_right_font_size\" min=\"6\" max=\"22\" step=\"1\"\n                                                   value=\"{{ $settings['bulletin_header_right_font_size'] ?: '12' }}\">\n""",
    """                                            <label class=\"bcfg-label\">Semestre / diplôme / niveau / année</label>\n                                            <input type=\"number\" class=\"bcfg-input\" name=\"bulletin_header_right_font_size\" min=\"6\" max=\"22\" step=\"1\"\n                                                   value=\"{{ $settings['bulletin_header_right_font_size'] ?: '12' }}\">\n""",
)
replace_once(
    config,
    """                                    <div class=\"bcfg-hint\" style=\"margin-top:.45rem;\">Valeurs en pixels avant application de l'échelle générale. Le PDF reste en mise en page tableau, compatible DomPDF.</div>\n""",
    """                                    <div class=\"bcfg-hint\" style=\"margin-top:.45rem;\">Le titre « BULLETIN DE NOTES » se règle séparément. Le semestre reprend la même taille que « Brevet de Technicien Supérieur », BTS et l'année. Valeurs en pixels avant application de l'échelle générale, en mise en page tableau compatible DomPDF.</div>\n""",
)

# 4) Canonical settings returned by BulletinService: non-empty defaults make the
# new hierarchy effective even before the school saves the form once.
replace_once(
    service,
    """            'bulletin_header_title_font_size' => \\App\\Helpers\\SettingsHelper::get('bulletin_header_title_font_size', ''),\n            'bulletin_header_right_font_size' => \\App\\Helpers\\SettingsHelper::get('bulletin_header_right_font_size', ''),\n""",
    """            'bulletin_header_title_font_size' => \\App\\Helpers\\SettingsHelper::get('bulletin_header_title_font_size', '18'),\n            'bulletin_header_right_font_size' => \\App\\Helpers\\SettingsHelper::get('bulletin_header_right_font_size', '12'),\n""",
)

# 5) Contract test: title and right-side text are independently configurable,
# and the semester no longer has the former +4px boost.
replace_once(
    test,
    """        self::assertStringContainsString('bulletin_header_school_name_font_size', $view);\n        self::assertStringContainsString('bulletin_header_logo_height', $view);\n""",
    """        self::assertStringContainsString('bulletin_header_school_name_font_size', $view);\n        self::assertStringContainsString('bulletin_header_title_font_size', $view);\n        self::assertStringContainsString('bulletin_header_right_font_size', $view);\n        self::assertStringContainsString('font-size: {{ $headerRightFont }}px', $view);\n        self::assertStringNotContainsString('min(34, $headerRightFont + 4)', $view);\n        self::assertStringContainsString('bulletin_header_logo_height', $view);\n""",
)

# Static sanity checks.
y = Path(yakro).read_text()
a = Path(abidjan).read_text()
c = Path(config).read_text()
s = Path(service).read_text()
assert 'font-size: {{ $headerRightFont }}px;' in y
assert 'min(34, $headerRightFont + 4)' not in y
assert 'font-size: {{ $headerTitleFont }}px;' in y
assert 'font-size: {{ $headerTitleFont }}px;' in a
assert 'font-size: {{ $headerRightFont }}px;' in a
assert 'Titre « BULLETIN DE NOTES »' in c
assert 'Semestre / diplôme / niveau / année' in c
assert "get('bulletin_header_title_font_size', '18')" in s
assert "get('bulletin_header_right_font_size', '12')" in s
