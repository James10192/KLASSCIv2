# trigger: Abidjan bulletin margin source fix
from pathlib import Path


def replace_once(path: str, old: str, new: str) -> None:
    p = Path(path)
    text = p.read_text()
    count = text.count(old)
    if count != 1:
        raise SystemExit(f"{path}: expected one occurrence, found {count}: {old[:120]!r}")
    p.write_text(text.replace(old, new, 1))


yakro = 'resources/views/esbtp/bulletins/pdf-configurable.blade.php'
abidjan = 'resources/views/esbtp/bulletins/pdf-configurable-abidjan.blade.php'
settings_view = 'resources/views/esbtp/settings/index.blade.php'
bulletin_config = 'resources/views/esbtp/bulletins/configuration.blade.php'

# Generic/Yakro template on the Abidjan tenant must also honor bulletin-specific
# margins when this style is selected.
replace_once(
    yakro,
    """        $typeScale = \\App\\Services\\BulletinTypography::scale($settings['bulletin_font_size'] ?? 13);\n""",
    """        $typeScale = \\App\\Services\\BulletinTypography::scale($settings['bulletin_font_size'] ?? 13);\n        $marginVertical = max(2, min(25, (int) ($settings['bulletin_margin_vertical'] ?? 5)));\n        $marginHorizontal = max(2, min(25, (int) ($settings['bulletin_margin_horizontal'] ?? 5)));\n""",
)
replace_once(
    yakro,
    """        @page {\n            size: A4 portrait;\n            margin: {{ $pdfSettings['margin_top'] ?? 20 }}mm\n                    {{ $pdfSettings['margin_right'] ?? 15 }}mm\n                    {{ $pdfSettings['margin_bottom'] ?? 20 }}mm\n                    {{ $pdfSettings['margin_left'] ?? 15 }}mm;\n        }\n""",
    """        @page {\n            size: A4 portrait;\n            margin: {{ $marginVertical }}mm {{ $marginHorizontal }}mm;\n        }\n""",
)
replace_once(
    yakro,
    """        body .container {\n            padding: {{ $pdfSettings['margin_top'] ?? 20 }}mm\n                     {{ $pdfSettings['margin_right'] ?? 15 }}mm\n                     {{ $pdfSettings['margin_bottom'] ?? 20 }}mm\n                     {{ $pdfSettings['margin_left'] ?? 15 }}mm;\n        }\n""",
    """        body .container {\n            padding: {{ $marginVertical }}mm {{ $marginHorizontal }}mm;\n        }\n""",
)

# The actual Abidjan model already used the dedicated margins for DomPDF export,
# but the HTML preview ignored them. Mirror the same margins in preview.
replace_once(
    abidjan,
    """        body.pdf-export .container {\n            width: 100%;\n            max-width: none;\n            padding: 0;\n            border: none;\n        }\n        @endif\n""",
    """        body.pdf-export .container {\n            width: 100%;\n            max-width: none;\n            padding: 0;\n            border: none;\n        }\n        @else\n        body .container {\n            width: 100%;\n            max-width: none;\n            padding: {{ $marginVertical }}mm {{ $marginHorizontal }}mm;\n        }\n        @endif\n""",
)

# Stronger semester hierarchy in the real Abidjan template.
replace_once(
    abidjan,
    """        .bulletin-period {\n            font-size: {{ $typeScale['info'] }}px;\n            color: #374151;\n            margin-bottom: 1px;\n        }\n""",
    """        .bulletin-period {\n            display: inline-block;\n            font-size: {{ min(30, $typeScale['table'] + 4) }}px;\n            font-weight: 700;\n            color: {{ $pdfText }};\n            margin: 3px 0 5px;\n            padding: 3px 7px;\n            border: 1.5px solid {{ $pdfPrimary }};\n            border-radius: 4px;\n            background-color: #f8fafc;\n            text-transform: uppercase;\n            letter-spacing: 0.04em;\n        }\n""",
)

# Clarify the two margin domains in both UIs.
replace_once(
    settings_view,
    """                <h4 style=\"margin-top: 24px; font-size: 0.95rem; color: #64748b; font-weight: 600;\">\n                    <i class=\"fas fa-arrows-alt text-primary\"></i> Marges (mm)\n                </h4>\n""",
    """                <h4 style=\"margin-top: 24px; font-size: 0.95rem; color: #64748b; font-weight: 600;\">\n                    <i class=\"fas fa-arrows-alt text-primary\"></i> Marges générales des PDF (mm)\n                </h4>\n                <div class=\"alert alert-info py-2 px-3 mb-3\" style=\"font-size:.8rem;\">\n                    Ces marges concernent les documents PDF généraux. Les bulletins BTS utilisent leurs marges dédiées dans\n                    <a href=\"{{ route('esbtp.bulletins.configuration') }}\" class=\"fw-semibold\">Configuration des bulletins</a>.\n                </div>\n""",
)
replace_once(
    bulletin_config,
    """                                <div class=\"bcfg-hint\" style=\"margin-top:.35rem;\">Plus la marge est petite, plus le contenu du bulletin est grand. En dessous de 5 mm, certaines imprimantes rognent les bords.</div>\n""",
    """                                <div class=\"bcfg-hint\" style=\"margin-top:.35rem;\">Plus la marge est petite, plus le contenu du bulletin est grand. En dessous de 5 mm, certaines imprimantes rognent les bords.</div>\n                                <div class=\"bcfg-hint\" style=\"margin-top:.25rem;font-weight:600;\">Ces marges sont propres aux bulletins BTS et priment sur les marges PDF générales de /esbtp/settings.</div>\n""",
)

# Contract checks.
y = Path(yakro).read_text()
a = Path(abidjan).read_text()
s = Path(settings_view).read_text()
c = Path(bulletin_config).read_text()
assert 'margin: {{ $marginVertical }}mm {{ $marginHorizontal }}mm;' in y
assert 'padding: {{ $marginVertical }}mm {{ $marginHorizontal }}mm;' in y
assert "margin: {{ $pdfSettings['margin_top'] ?? 20 }}mm" not in y
assert 'margin: {{ $marginVertical }}mm {{ $marginHorizontal }}mm;' in a
assert 'padding: {{ $marginVertical }}mm {{ $marginHorizontal }}mm;' in a
assert 'font-size: {{ min(30, $typeScale[\'table\'] + 4) }}px;' in a
assert 'Marges générales des PDF (mm)' in s
assert 'Ces marges sont propres aux bulletins BTS' in c
