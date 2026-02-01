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
    body {
        color: {{ $pdfText }};
    }

    h1, h2, h3, h4, h5 {
        color: {{ $pdfPrimary }};
    }

    .pdf-title,
    .header-title {
        color: {{ $pdfPrimary }};
    }

    .pdf-subtitle,
    .header-subtitle {
        color: {{ $pdfSecondary }};
    }

    .badge,
    .status-badge {
        background: {{ $pdfAccent }};
        color: #ffffff;
    }

    table thead th,
    .attendance-table th,
    .table-header th,
    .header-table th {
        background: {{ $pdfHeaderBg }} !important;
        color: {{ $pdfHeaderText }} !important;
    }

    .table-header,
    .section-header,
    .header-section,
    .document-title-section {
        background: {{ $pdfHeaderBg }} !important;
        color: {{ $pdfHeaderText }} !important;
    }

    .student-number,
    .kpi-value,
    .header-badge {
        background: {{ $pdfPrimary }} !important;
        color: {{ $pdfHeaderText }} !important;
    }

    .kpi-title,
    .small-muted,
    .meta-text {
        color: {{ $pdfSecondary }} !important;
    }

    /* Override common hard-coded colors (inline styles) */
    [style*="background: #007bff"],
    [style*="background:#007bff"],
    [style*="background-color: #007bff"],
    [style*="background-color:#007bff"],
    [style*="background: #0453cb"],
    [style*="background:#0453cb"],
    [style*="background-color: #0453cb"],
    [style*="background-color:#0453cb"] {
        background: {{ $pdfPrimary }} !important;
        color: {{ $pdfHeaderText }} !important;
    }

    [style*="color: #007bff"],
    [style*="color:#007bff"],
    [style*="color: #0453cb"],
    [style*="color:#0453cb"] {
        color: {{ $pdfPrimary }} !important;
    }

    [style*="border: 1px solid #007bff"],
    [style*="border:1px solid #007bff"],
    [style*="border: 2px solid #007bff"],
    [style*="border:2px solid #007bff"],
    [style*="border: 1px solid #0453cb"],
    [style*="border:1px solid #0453cb"],
    [style*="border: 2px solid #0453cb"],
    [style*="border:2px solid #0453cb"] {
        border-color: {{ $pdfPrimary }} !important;
    }

    [style*="color: #6b7280"],
    [style*="color:#6b7280"],
    [style*="color: #64748b"],
    [style*="color:#64748b"],
    [style*="color: #374151"],
    [style*="color:#374151"] {
        color: {{ $pdfSecondary }} !important;
    }

    [style*="background: #f3f4f6"],
    [style*="background:#f3f4f6"],
    [style*="background-color: #f3f4f6"],
    [style*="background-color:#f3f4f6"],
    [style*="background: #f8f9fa"],
    [style*="background:#f8f9fa"],
    [style*="background-color: #f8f9fa"],
    [style*="background-color:#f8f9fa"] {
        background: #ffffff !important;
    }

    /* Keep alternating row stripes if defined */
    table tbody tr:nth-child(even) {
        background-color: rgba(0, 0, 0, 0.02);
    }

    .border-accent {
        border-color: var(--pdf-primary) !important;
    }
</style>
