<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Convocation au guichet</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: {{ $settings['text_color'] ?? '#1f2937' }};
            line-height: 1.45;
            background: #ffffff;
        }
        @page {
            margin: {{ $settings['margin_top'] ?? 15 }}mm
                    {{ $settings['margin_right'] ?? 10 }}mm
                    {{ $settings['margin_bottom'] ?? 15 }}mm
                    {{ $settings['margin_left'] ?? 10 }}mm;
        }
        .bloc td { padding: 8px 12px; border-bottom: 1px solid #e5e7eb; }
        .lib { color: #6b7280; width: 38%; font-size: 11px; }
        .val { font-weight: bold; font-size: 12px; }
        .titre-cell {
            background-color: {{ $settings['header_bg_color'] ?? $settings['primary_color'] ?? '#0453cb' }};
            color: {{ $settings['header_text_on_bg'] ?? $settings['header_text_color'] ?? '#ffffff' }};
            padding: 10px 12px;
            font-weight: bold;
            font-size: 13px;
        }
        .creneau-cell {
            background-color: {{ $settings['primary_color'] ?? '#0453cb' }};
            color: {{ $settings['header_text_on_primary'] ?? '#ffffff' }};
            padding: 16px 12px;
            text-align: center;
        }
        .pied {
            margin-top: 18px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            font-size: 9px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
    @include('pdf.partials.theme')
</head>
<body>
    @include('pdf.partials.banner', [
        'title' => 'Convocation au guichet',
        'subtitle' => 'Inscriptions physiques — rendez-vous',
        'school' => $ecole,
        'pdfSettings' => $settings,
        'logo' => $logo,
    ])

    <table width="100%" cellspacing="0" cellpadding="0" style="margin-top: 16px; border: 1px solid #d1d5db;">
        <tr>
            <td class="titre-cell">Votre rendez-vous</td>
        </tr>
        <tr>
            <td class="creneau-cell">
                <div style="font-size: 11px; letter-spacing: 0.4px; text-transform: uppercase; margin-bottom: 6px;">Présentez-vous</div>
                <div style="font-size: 18px; font-weight: bold;">
                    {{ $creneau?->date?->translatedFormat('l j F Y') ?? '—' }}
                </div>
                <div style="font-size: 16px; margin-top: 4px;">
                    {{ $creneau ? ($creneau->heureDebutHi().' – '.$creneau->heureFinHi()) : '—' }}
                </div>
            </td>
        </tr>
    </table>

    <table width="100%" cellspacing="0" cellpadding="0" class="bloc" style="margin-top: 14px; border: 1px solid #d1d5db;">
        <tr>
            <td class="lib">Nom</td>
            <td class="val">{{ $reservation->nom }} {{ $reservation->prenoms }}</td>
        </tr>
        <tr>
            <td class="lib">Téléphone</td>
            <td class="val">{{ $reservation->telephone }}</td>
        </tr>
        @if(!empty($reference))
        <tr>
            <td class="lib">Référence</td>
            <td class="val">{{ $reference }}</td>
        </tr>
        @endif
    </table>

    <table width="100%" cellspacing="0" cellpadding="0" style="margin-top: 14px;">
        <tr>
            <td style="padding: 10px 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; font-size: 11px; color: #334155;">
                Apportez vos pièces. En cas d'empêchement, modifiez ou annulez votre rendez-vous depuis klassci.com avec votre référence.
            </td>
        </tr>
    </table>

    <div class="pied">
        {{ $settings['footer_text'] ?? ($ecole['name'] ?? 'KLASSCI') }}
        — Généré le {{ now()->format('d/m/Y à H:i') }}
    </div>
</body>
</html>
