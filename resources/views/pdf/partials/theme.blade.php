@php
    $pdfSettings = \App\Helpers\SettingsHelper::getPdfSettings();
    $pdfPrimary = $pdfSettings['primary_color'] ?? '#0453cb';
    $pdfSecondary = $pdfSettings['secondary_color'] ?? '#64748b';
    $pdfAccent = $pdfSettings['accent_color'] ?? '#f59e0b';
    $pdfText = $pdfSettings['text_color'] ?? '#1f2937';
    $pdfHeaderBg = $pdfSettings['header_bg_color'] ?? $pdfPrimary;
    $pdfHeaderText = $pdfSettings['header_text_color'] ?? '#ffffff';
@endphp
<style>
    :root {
        --pdf-primary: {{ $pdfPrimary }};
        --pdf-secondary: {{ $pdfSecondary }};
        --pdf-accent: {{ $pdfAccent }};
        --pdf-text: {{ $pdfText }};
        --pdf-header-bg: {{ $pdfHeaderBg }};
        --pdf-header-text: {{ $pdfHeaderText }};
    }

    body {
        color: var(--pdf-text);
    }

    h1, h2, h3, h4, h5 {
        color: var(--pdf-primary);
    }

    .pdf-title,
    .header-title {
        color: var(--pdf-primary);
    }

    .pdf-subtitle,
    .header-subtitle {
        color: var(--pdf-secondary);
    }

    .badge,
    .status-badge {
        background: var(--pdf-accent);
        color: #ffffff;
    }

    table thead th,
    .attendance-table th,
    .table-header th,
    .header-table th {
        background: var(--pdf-header-bg) !important;
        color: var(--pdf-header-text) !important;
    }

    .table-header,
    .section-header,
    .header-section,
    .document-title-section {
        background: var(--pdf-header-bg) !important;
        color: var(--pdf-header-text) !important;
    }

    .student-number,
    .kpi-value,
    .header-badge {
        background: var(--pdf-primary) !important;
        color: var(--pdf-header-text) !important;
    }

    .kpi-title,
    .small-muted,
    .meta-text {
        color: var(--pdf-secondary) !important;
    }

    .border-accent {
        border-color: var(--pdf-primary) !important;
    }
</style>
