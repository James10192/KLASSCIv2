from pathlib import Path

p = Path('resources/views/esbtp/bulletins/pdf-configurable.blade.php')
text = p.read_text()

def once(old: str, new: str) -> None:
    global text
    count = text.count(old)
    if count != 1:
        raise SystemExit(f'expected one occurrence, found {count}: {old[:100]!r}')
    text = text.replace(old, new, 1)

# Footer: keep configured opacity, but use the same text color as the bulletin body.
once(
    """        .edition-footer {
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
        }
""",
    """        .edition-footer {
            margin-top: 10px;
            font-size: {{ $editionFontSize }}px;
            color: {{ $pdfText }};
            opacity: {{ $editionOpacity }};
            text-align: left;
        }
        .edition-authenticity {
            margin-top: 4px;
            font-size: {{ $authenticityFontSize }}px;
            color: {{ $pdfText }};
            opacity: {{ $authenticityOpacity }};
            text-align: center;
        }
""",
)

# Header metadata: stop using hard-coded grays; inherit the PDF text color from /esbtp/settings.
once(
    """        .header-left {
            width: 26%;
            font-size: {{ $headerLeftFont }}px;
            line-height: 1.5;
            color: #374151;
            border-right: 1px solid #e5e7eb;
            padding-right: 8px;
        }
""",
    """        .header-left {
            width: 26%;
            font-size: {{ $headerLeftFont }}px;
            line-height: 1.5;
            color: {{ $pdfText }};
            border-right: 1px solid #e5e7eb;
            padding-right: 8px;
        }
""",
)
once(
    """        .school-address {
            font-size: {{ $headerSchoolMetaFont }}px;
            color: #6b7280;
        }
""",
    """        .school-address {
            font-size: {{ $headerSchoolMetaFont }}px;
            color: {{ $pdfText }};
        }
""",
)

# Semester: stronger hierarchy while remaining DomPDF-safe (plain font/spacing properties only).
once(
    """        .header-right .period {
            font-size: {{ $headerRightFont }}px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 2px;
        }
        .header-right .year {
            font-size: {{ $headerRightFont }}px;
            color: #374151;
        }
""",
    """        .header-right .period {
            font-size: {{ min(32, $headerRightFont + 2) }}px;
            font-weight: 700;
            color: {{ $pdfText }};
            margin: 2px 0 4px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .header-right .year {
            font-size: {{ $headerRightFont }}px;
            color: {{ $pdfText }};
        }
""",
)

p.write_text(text)

# Contract checks: fail the patch job if the intended readable styling is missing.
assert 'font-size: {{ min(32, $headerRightFont + 2) }}px;' in text
assert 'text-transform: uppercase;' in text
assert '.school-address {' in text and 'color: {{ $pdfText }};' in text
assert 'opacity: {{ $authenticityOpacity }};' in text
assert text.count('color: #6b7280;') == 0, 'hard-coded muted gray still present in this template'
