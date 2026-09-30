from pathlib import Path

# Trigger rerun after fixing shell validation quoting.
p = Path('resources/views/esbtp/bulletins/pdf-configurable.blade.php')
text = p.read_text()

def once(old: str, new: str) -> None:
    global text
    count = text.count(old)
    if count != 1:
        raise SystemExit(f'expected one occurrence, found {count}: {old[:120]!r}')
    text = text.replace(old, new, 1)

# Stronger semester hierarchy, using only DomPDF-safe properties.
once(
    """        .header-right .period {
            font-size: {{ min(32, $headerRightFont + 2) }}px;
            font-weight: 700;
            color: {{ $pdfText }};
            margin: 2px 0 4px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
""",
    """        .header-right .period {
            display: inline-block;
            font-size: {{ min(34, $headerRightFont + 4) }}px;
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
""",
)

# Honor PDF margins from /esbtp/settings in DomPDF and mirror them in the HTML preview.
once(
    """        /* ── Mode PDF export ──────────────────────────────────── */
        @if($isPdfExport ?? false)
        @page {
            size: A4 portrait;
            margin: 2mm 2mm;
        }
        body.pdf-export {
            margin: 0;
            padding: 0;
            background: #fff;
        }
        body.pdf-export .container {
            width: 100%;
            max-width: none;
            padding: 0;
        }
        @endif

        @media print {
            body { margin: 0; padding: 0; background: #fff; }
            .container { box-shadow: none; width: 100%; max-width: none; }
            .print-button, .pdf-toggle { display: none !important; }
        }
""",
    """        /* ── Mode PDF export ──────────────────────────────────── */
        @page {
            size: A4 portrait;
            margin: {{ $pdfSettings['margin_top'] ?? 20 }}mm
                    {{ $pdfSettings['margin_right'] ?? 15 }}mm
                    {{ $pdfSettings['margin_bottom'] ?? 20 }}mm
                    {{ $pdfSettings['margin_left'] ?? 15 }}mm;
        }
        @if($isPdfExport ?? false)
        body.pdf-export {
            margin: 0;
            padding: 0;
            background: #fff;
        }
        body.pdf-export .container {
            width: 100%;
            max-width: none;
            padding: 0;
        }
        @else
        body .container {
            padding: {{ $pdfSettings['margin_top'] ?? 20 }}mm
                     {{ $pdfSettings['margin_right'] ?? 15 }}mm
                     {{ $pdfSettings['margin_bottom'] ?? 20 }}mm
                     {{ $pdfSettings['margin_left'] ?? 15 }}mm;
        }
        @endif

        @media print {
            body { margin: 0; padding: 0; background: #fff; }
            .container { box-shadow: none; width: 100%; max-width: none; padding: 0 !important; }
            .print-button, .pdf-toggle { display: none !important; }
        }
""",
)

p.write_text(text)

assert "margin: {{ $pdfSettings['margin_top'] ?? 20 }}mm" in text
assert "padding: {{ $pdfSettings['margin_top'] ?? 20 }}mm" in text
assert 'font-size: {{ min(34, $headerRightFont + 4) }}px;' in text
assert 'border: 1.5px solid {{ $pdfPrimary }};' in text
assert 'margin: 2mm 2mm;' not in text
