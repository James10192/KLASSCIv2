import sys
import os
from pypdf import PdfReader

sys.stdout.reconfigure(encoding='utf-8')

DOWNLOADS = r'C:\Users\yabla\Downloads'
OUT_DIR = r'C:\Users\yabla\Downloads\dev\KLASSCIv2\.pdf_audit_tmp'
os.makedirs(OUT_DIR, exist_ok=True)

pdfs = [
    'LICENCE DROIT MAQUETTES.pdf',
    'LICENCE SCIENCES ECONOMIQUES ET DE GESTION MAQUETTES.pdf',
    'LICENCE SCIENCES DE LA VIE ET DE LA TERRE MAQUETTES.pdf',
    'LICENCE LETTRES MODERNES MAQUETTE.pdf',
]

for pdf in pdfs:
    path = os.path.join(DOWNLOADS, pdf)
    try:
        r = PdfReader(path)
        nb = len(r.pages)
        print(f'=== {pdf} ===')
        print(f'PAGES: {nb}')
        text = ''
        for i, p in enumerate(r.pages):
            t = p.extract_text() or ''
            text += f'\n--- PAGE {i+1} ---\n{t}'
        out_path = os.path.join(OUT_DIR, pdf + '.txt')
        with open(out_path, 'w', encoding='utf-8') as f:
            f.write(text)
        print(f'Saved to {out_path}')
        print(f'Length: {len(text)} chars')
        print()
    except Exception as e:
        print(f'ERROR {pdf}: {e}')
