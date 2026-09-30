# trigger: Abidjan header title controls
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

replace_once(generic,
"""        $typeScale = \App\Services\BulletinTypography::scale($settings['bulletin_font_size'] ?? 13);
        $marginVertical = max(2, min(25, (int) ($settings['bulletin_margin_vertical'] ?? 5)));
""",
"""        $typeScale = \App\Services\BulletinTypography::scale($settings['bulletin_font_size'] ?? 13);
        $headerTitleFont = max(8, min(30, (int) (($settings['bulletin_header_title_font_size'] ?? '') ?: 18)));
        $headerRightFont = max(6, min(22, (int) (($settings['bulletin_header_right_font_size'] ?? '') ?: 12)));
        $marginVertical = max(2, min(25, (int) ($settings['bulletin_margin_vertical'] ?? 5)));
""")
replace_once(generic,
"""            font-size: {{ $typeScale['title'] }}px;
            text-decoration: underline;
""",
"""            font-size: {{ $headerTitleFont }}px;
            line-height: 1.15;
            letter-spacing: 0.04em;
            text-decoration: underline;
""")
replace_once(generic, "            font-size: {{ min(30, $typeScale['table'] + 4) }}px;\n", "            font-size: {{ $headerRightFont }}px;\n")
replace_once(generic, """        .header-right .year {
            font-size: {{ $typeScale['info'] }}px;
""", """        .header-right .year {
            font-size: {{ $headerRightFont }}px;
""")

replace_once(abidjan,
"""        $typeScale     = \App\Services\BulletinTypography::scale($settings['bulletin_font_size'] ?? 13);
""",
"""        $typeScale     = \App\Services\BulletinTypography::scale($settings['bulletin_font_size'] ?? 13);
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
replace_once(abidjan, "            font-size: {{ min(30, $typeScale['table'] + 4) }}px;\n", "            font-size: {{ $headerRightFont }}px;\n")
replace_once(abidjan, """        .academic-year {
            font-size: {{ $typeScale['info'] }}px;
""", """        .academic-year {
            font-size: {{ $headerRightFont }}px;
""")

replace_once(config,
"""                                </select>

                                <label class="bcfg-label" style="margin-top:.85rem;">Marge haut / bas (mm)</label>
""",
"""                                </select>

                                <div style="margin-top:1rem;padding:.85rem;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;">
                                    <div class="bcfg-label" style="margin-bottom:.55rem;">En-tête — hiérarchie du titre</div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="bcfg-label">Titre « BULLETIN DE NOTES »</label>
                                            <input type="number" class="bcfg-input" name="bulletin_header_title_font_size" min="8" max="30" step="1"
                                                   value="{{ $settings['bulletin_header_title_font_size'] ?? '18' }}">
                                        </div>
                                        <div class="col-6">
                                            <label class="bcfg-label">Semestre / diplôme / niveau / année</label>
                                            <input type="number" class="bcfg-input" name="bulletin_header_right_font_size" min="6" max="22" step="1"
                                                   value="{{ $settings['bulletin_header_right_font_size'] ?? '12' }}">
                                        </div>
                                    </div>
                                    <div class="bcfg-hint" style="margin-top:.45rem;">Le titre du document est volontairement dominant. Le semestre utilise la même taille que les informations secondaires de l'en-tête, notamment le diplôme/cycle lorsque le modèle les affiche. Réglages compatibles Yakro et Abidjan.</div>
                                </div>

                                <label class="bcfg-label" style="margin-top:.85rem;">Marge haut / bas (mm)</label>
""")

replace_once(controller,
"""    public function saveConfiguration(Request $request)
    {
        $effectiveBtsSettings = BtsBulletinPolicy::effectiveSettings(
""",
"""    public function saveConfiguration(Request $request)
    {
        $request->validate([
            'bulletin_header_title_font_size' => ['nullable', 'integer', 'min:8', 'max:30'],
            'bulletin_header_right_font_size' => ['nullable', 'integer', 'min:6', 'max:22'],
        ]);

        $effectiveBtsSettings = BtsBulletinPolicy::effectiveSettings(
""")
replace_once(controller,
"""                'bulletin_font_size',
                'bulletin_margin_vertical',
""",
"""                'bulletin_font_size',
                'bulletin_header_title_font_size',
                'bulletin_header_right_font_size',
                'bulletin_margin_vertical',
""")

replace_once(service,
"""            'bulletin_school_name_custom' => \App\Helpers\SettingsHelper::get('bulletin_school_name_custom', ''),
            'bulletin_font_size' => \App\Helpers\SettingsHelper::get('bulletin_font_size', '13'),
            'bulletin_style' => \App\Helpers\SettingsHelper::get('bulletin_style', 'yakro'),
""",
"""            'bulletin_school_name_custom' => \App\Helpers\SettingsHelper::get('bulletin_school_name_custom', ''),
            'bulletin_font_size' => \App\Helpers\SettingsHelper::get('bulletin_font_size', '13'),
            'bulletin_header_title_font_size' => \App\Helpers\SettingsHelper::get('bulletin_header_title_font_size', '18'),
            'bulletin_header_right_font_size' => \App\Helpers\SettingsHelper::get('bulletin_header_right_font_size', '12'),
            'bulletin_style' => \App\Helpers\SettingsHelper::get('bulletin_style', 'yakro'),
""")

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
