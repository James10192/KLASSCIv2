@props([
    'title',
    'subtitle' => null,
    'filters' => [],
    'orientation' => 'portrait',
])
@php
    $school = \App\Helpers\SettingsHelper::getSchoolInfo();
    $pdf = \App\Helpers\SettingsHelper::getPdfSettings();
    $logoPath = $school['logo'] ?? '';
    $logoFile = '';
    if ($pdf['show_logo'] && $logoPath) {
        $candidate = public_path('storage/' . ltrim($logoPath, '/'));
        if (file_exists($candidate)) {
            $logoFile = $candidate;
        } else {
            $candidate = public_path($logoPath);
            if (file_exists($candidate)) {
                $logoFile = $candidate;
            }
        }
    }
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    @include('pdf.partials.theme')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: {{ ($pdf['margin_top'] ?? 20) }}mm
                    {{ ($pdf['margin_right'] ?? 15) }}mm
                    {{ ($pdf['margin_bottom'] ?? 20) }}mm
                    {{ ($pdf['margin_left'] ?? 15) }}mm;
        }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: {{ ($pdf['font_size'] ?? 11) }}px;
            color: {{ $pdf['text_color'] ?? '#1f2937' }};
            margin: 0;
            line-height: 1.4;
        }

        .pdf-header {
            width: 100%;
            border-bottom: 2px solid {{ $pdf['primary_color'] ?? '#0453cb' }};
            padding-bottom: 8px;
            margin-bottom: 16px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .pdf-header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .pdf-header-logo-cell {
            width: 70px;
            vertical-align: middle;
            padding-right: 12px;
        }
        .pdf-header-logo {
            max-height: 60px;
            max-width: 70px;
        }
        .pdf-header-info-cell {
            vertical-align: middle;
        }
        .pdf-school-name {
            font-size: 16px;
            font-weight: 700;
            color: {{ $pdf['primary_color'] ?? '#0453cb' }};
            margin: 0 0 2px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .pdf-school-meta {
            font-size: 9px;
            color: {{ $pdf['secondary_color'] ?? '#64748b' }};
            margin: 0;
            line-height: 1.4;
        }
        .pdf-doc-meta-cell {
            text-align: right;
            vertical-align: middle;
            width: 200px;
            font-size: 9px;
            color: {{ $pdf['secondary_color'] ?? '#64748b' }};
        }
        .pdf-doc-meta-cell strong {
            color: {{ $pdf['primary_color'] ?? '#0453cb' }};
            font-size: 10px;
        }

        .pdf-title-block {
            margin-bottom: 14px;
            text-align: center;
        }
        .pdf-title {
            font-size: 18px;
            font-weight: 700;
            color: {{ $pdf['primary_color'] ?? '#0453cb' }};
            margin: 0 0 4px;
            text-transform: uppercase;
            letter-spacing: .8px;
        }
        .pdf-subtitle {
            font-size: 10px;
            color: {{ $pdf['secondary_color'] ?? '#64748b' }};
            margin: 0;
            font-style: italic;
        }

        .pdf-filters-recap {
            background: #f1f5f9;
            border-left: 3px solid {{ $pdf['primary_color'] ?? '#0453cb' }};
            padding: 8px 12px;
            margin-bottom: 14px;
            font-size: 9px;
            border-radius: 2px;
        }
        .pdf-filters-recap-title {
            font-weight: 700;
            color: {{ $pdf['primary_color'] ?? '#0453cb' }};
            margin-right: 6px;
        }
        .pdf-filters-recap-item {
            margin-right: 10px;
            color: {{ $pdf['text_color'] ?? '#1f2937' }};
        }
        .pdf-filters-recap-item strong {
            color: {{ $pdf['secondary_color'] ?? '#64748b' }};
            font-weight: 600;
        }

        .pdf-body { margin-top: 8px; }

        .pdf-footer {
            position: fixed;
            bottom: -12mm;
            left: 0;
            right: 0;
            font-size: 8px;
            color: {{ $pdf['secondary_color'] ?? '#64748b' }};
            border-top: 1px solid #e5e7eb;
            padding-top: 4px;
            text-align: center;
        }
        .pdf-footer-page::after {
            content: counter(page) " / " counter(pages);
        }

        @if(!empty($pdf['watermark']))
            .pdf-watermark {
                position: fixed; top: 50%; left: 50%;
                transform: translate(-50%, -50%) rotate(-30deg);
                font-size: 90px; color: rgba(0,0,0,0.05);
                font-weight: 900; z-index: -1;
                white-space: nowrap;
            }
        @endif
    </style>
</head>
<body>
    @if(!empty($pdf['watermark']))
        <div class="pdf-watermark">{{ $pdf['watermark'] }}</div>
    @endif

    <div class="pdf-header">
        <table class="pdf-header-table">
            <tr>
                @if($logoFile)
                    <td class="pdf-header-logo-cell">
                        <img src="{{ $logoFile }}" alt="logo" class="pdf-header-logo">
                    </td>
                @endif
                <td class="pdf-header-info-cell">
                    <p class="pdf-school-name">{{ $school['name'] ?? config('app.name') }}</p>
                    <p class="pdf-school-meta">
                        @if($school['address']){{ $school['address'] }}@endif
                        @if($school['city']) · {{ $school['city'] }}@endif
                        @if($school['country']) · {{ $school['country'] }}@endif
                        <br>
                        @if($school['phone']) Tél : {{ $school['phone'] }}@endif
                        @if($school['email']) · {{ $school['email'] }}@endif
                        @if($school['website']) · {{ $school['website'] }}@endif
                    </p>
                </td>
                <td class="pdf-doc-meta-cell">
                    <strong>{{ $school['acronym'] ?? '' }}</strong><br>
                    Édité le {{ now()->locale('fr')->translatedFormat('d F Y à H:i') }}<br>
                    @auth Par {{ auth()->user()->name }} @endauth
                </td>
            </tr>
        </table>
    </div>

    <div class="pdf-title-block">
        <h1 class="pdf-title">{{ $title }}</h1>
        @if($subtitle)
            <p class="pdf-subtitle">{{ $subtitle }}</p>
        @endif
    </div>

    @if(!empty($filters))
        <x-pdf-filters-recap :filters="$filters" />
    @endif

    <div class="pdf-body">
        {{ $slot }}
    </div>

    <div class="pdf-footer">
        {{ $pdf['footer_text'] ?? ($school['name'] ?? config('app.name')) }}
        @if($school['director_name']) · Directeur : {{ $school['director_name'] }} @endif
        · Page <span class="pdf-footer-page"></span>
    </div>
</body>
</html>
