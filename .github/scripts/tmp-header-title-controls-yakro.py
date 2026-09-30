# trigger: final Yakro header title controls
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

replace_once(yakro,
"""        .header-right .title {
            font-weight: 700;
            font-size: {{ $headerTitleFont }}px;
            text-decoration: underline;
            color: {{ $pdfPrimary }};
            text-transform: uppercase;
            margin-bottom: 4px;
        }
""",
"""        .header-right .title {
            font-weight: 700;
            font-size: {{ $headerTitleFont }}px;
            line-height: 1.15;
            letter-spacing: 0.04em;
            text-decoration: underline;
            color: {{ $pdfPrimary }};
            text-transform: uppercase;
            margin-bottom: 5px;
        }
""")
replace_once(yakro, "            font-size: {{ min(34, $headerRightFont + 4) }}px;\n", "            font-size: {{ $headerRightFont }}px;\n")

replace_once(abidjan,
"""        $typeScale     = \\App\\Services\\BulletinTypography::scale($settings['bulletin_font_size'] ?? 13);
""",
"""        $typeScale     = \\App\\Services\\BulletinTypography::scale($settings['bulletin_font_size'] ?? 13);
        $headerTitleFont = max(8, min(30, (int) (($settings['bulletin_header_title_font_size'] ?? '') ?: 18)));
        $headerRightFont = max(6, min(22, (int) (($settings['bulletin_header_right_font_size'] ?? '') ?: 12)));
""")
replace_once(abidjan,
"""            font-size: {{ $typeScale['title'] }}px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: {{ $pdfPrimary }};
""",
"""            font-size: {{ $headerTitleFont }}px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: {{ $pdfPrimary }};
""")
replace_once(abidjan,
"""        .bulletin-period {
            font-size: {{ $typeScale['info'] }}px;
            color: #374151;
            margin-bottom: 1px;
        }
""",
"""        .bulletin-period {
            display: inline-block;
            font-size: {{ $headerRightFont }}px;
            font-weight: 700;
            color: {{ $pdfText }};
            margin: 3px 0 5px;
            padding: 3px 7px;
            border: 1.5px solid {{ $pdfPrimary }};
            border-radius: 4px;
            background-color: #f8fafc;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
""")
replace_once(abidjan, """        .academic-year {
            font-size: {{ $typeScale['info'] }}px;
""", """        .academic-year {
            font-size: {{ $headerRightFont }}px;
""")

replace_once(config,
"""                                            <label class="bcfg-label">Titre du bulletin</label>
                                            <input type="number" class="bcfg-input" name="bulletin_header_title_font_size" min="8" max="30" step="1"
                                                   value="{{ $settings['bulletin_header_title_font_size'] ?: '15' }}">
""",
"""                                            <label class="bcfg-label">Titre « BULLETIN DE NOTES »</label>
                                            <input type="number" class="bcfg-input" name="bulletin_header_title_font_size" min="8" max="30" step="1"
                                                   value="{{ $settings['bulletin_header_title_font_size'] ?: '18' }}">
""")
replace_once(config,
"""                                            <label class="bcfg-label">Période / cycle / année</label>
                                            <input type="number" class="bcfg-input" name="bulletin_header_right_font_size" min="6" max="22" step="1"
                                                   value="{{ $settings['bulletin_header_right_font_size'] ?: '12' }}">
""",
"""                                            <label class="bcfg-label">Semestre / diplôme / niveau / année</label>
                                            <input type="number" class="bcfg-input" name="bulletin_header_right_font_size" min="6" max="22" step="1"
                                                   value="{{ $settings['bulletin_header_right_font_size'] ?: '12' }}">
""")
replace_once(config,
"""                                    <div class="bcfg-hint" style="margin-top:.45rem;">Valeurs en pixels avant application de l'échelle générale. Le PDF reste en mise en page tableau, compatible DomPDF.</div>
""",
"""                                    <div class="bcfg-hint" style="margin-top:.45rem;">Le titre « BULLETIN DE NOTES » se règle séparément. Le semestre reprend la même taille que « Brevet de Technicien Supérieur », BTS et l'année. Valeurs en pixels avant application de l'échelle générale, en mise en page tableau compatible DomPDF.</div>
""")

replace_once(service,
"""            'bulletin_header_title_font_size' => \\App\\Helpers\\SettingsHelper::get('bulletin_header_title_font_size', ''),
            'bulletin_header_right_font_size' => \\App\\Helpers\\SettingsHelper::get('bulletin_header_right_font_size', ''),
""",
"""            'bulletin_header_title_font_size' => \\App\\Helpers\\SettingsHelper::get('bulletin_header_title_font_size', '18'),
            'bulletin_header_right_font_size' => \\App\\Helpers\\SettingsHelper::get('bulletin_header_right_font_size', '12'),
""")

replace_once(test,
"""use App\Domain\BtsTroncCommun\BulletinSubjectOrder;
""",
"""use App\Domain\BtsTroncCommun\BulletinSubjectOrder;
use App\Domain\BtsTroncCommun\BulletinSubjectRowsCompleter;
""")
replace_once(test,
"""            new ClasseOuvertureResolver(),
            new BulletinSubjectOrder(new BtsBulletinSubjectResolver())
        );
""",
"""            new ClasseOuvertureResolver(),
            new BulletinSubjectOrder(new BtsBulletinSubjectResolver()),
            Mockery::mock(BulletinSubjectRowsCompleter::class)
        );
""")
replace_once(test,
"""        self::assertStringContainsString('bulletin_header_school_name_font_size', $view);
        self::assertStringContainsString('bulletin_header_logo_height', $view);
""",
"""        self::assertStringContainsString('bulletin_header_school_name_font_size', $view);
        self::assertStringContainsString('bulletin_header_title_font_size', $view);
        self::assertStringContainsString('bulletin_header_right_font_size', $view);
        self::assertStringContainsString('font-size: {{ $headerRightFont }}px', $view);
        self::assertStringNotContainsString('min(34, $headerRightFont + 4)', $view);
        self::assertStringContainsString('bulletin_header_logo_height', $view);
""")

y = Path(yakro).read_text(); a = Path(abidjan).read_text(); c = Path(config).read_text(); s = Path(service).read_text(); t = Path(test).read_text()
assert 'font-size: {{ $headerRightFont }}px;' in y
assert 'min(34, $headerRightFont + 4)' not in y
assert 'font-size: {{ $headerTitleFont }}px;' in y
assert 'font-size: {{ $headerTitleFont }}px;' in a
assert 'font-size: {{ $headerRightFont }}px;' in a
assert 'Titre « BULLETIN DE NOTES »' in c
assert 'Semestre / diplôme / niveau / année' in c
assert "get('bulletin_header_title_font_size', '18')" in s
assert "get('bulletin_header_right_font_size', '12')" in s
assert 'BulletinSubjectRowsCompleter::class' in t
