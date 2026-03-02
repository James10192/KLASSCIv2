@extends('layouts.app')

@section('title', 'Prévisualisation Certificat - ' . $etudiant->nom . ' ' . $etudiant->prenoms)

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@include('pdf.partials.theme')
@php
    $pdfSettings  = \App\Helpers\SettingsHelper::getPdfSettings();
    $accentColor  = $pdfSettings['header_bg_color']   ?? '#0453cb';
    $accentText   = $pdfSettings['header_text_color'] ?? '#ffffff';
    $bodyText     = $pdfSettings['text_color']        ?? '#1f2937';
@endphp
<style>
    :root {
        --doc-accent: {{ $accentColor }};
        --doc-atext:  {{ $accentText }};
        --doc-body:   {{ $bodyText }};
        --doc-muted:  #6b7280;
        --doc-border: #e5e7eb;
        --doc-radius: 12px;
        --doc-shadow: 0 1px 3px rgba(0,0,0,.07), 0 6px 24px rgba(0,0,0,.07);
    }
    .doc-toolbar {
        display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap;
        gap:12px; padding:14px 22px; background:#fff;
        border:1px solid var(--doc-border); border-radius:var(--doc-radius);
        margin-bottom:28px; box-shadow:0 1px 4px rgba(0,0,0,.06);
    }
    .doc-toolbar-title { font-size:1rem; font-weight:700; color:var(--doc-body); display:flex; align-items:center; gap:8px; }
    .doc-toolbar-title i { color:var(--doc-accent); }
    .doc-toolbar-sub { font-size:.78rem; color:var(--doc-muted); margin-top:1px; }
    .doc-toolbar-btns { display:flex; gap:8px; flex-wrap:wrap; }

    .doc-page-wrap { max-width:820px; margin:0 auto; }
    .doc-paper {
        background:#fff; border:1px solid var(--doc-border);
        border-radius:var(--doc-radius); box-shadow:var(--doc-shadow);
        overflow:hidden; position:relative;
    }
    .doc-watermark {
        position:absolute; top:50%; left:50%; transform:translate(-50%,-50%);
        opacity:.05; width:55%; z-index:0; pointer-events:none; text-align:center;
    }
    .doc-watermark img { max-width:100%; }
    .doc-inner { position:relative; z-index:1; padding:36px 42px 32px; }

    /* Header établissement */
    .doc-header {
        background:var(--doc-accent); color:var(--doc-atext);
        border-radius:10px; padding:20px 24px;
        display:flex; align-items:center; gap:18px;
        position:relative; overflow:hidden;
    }
    .doc-header::after {
        content:''; position:absolute; bottom:-28px; right:-28px;
        width:110px; height:110px; border-radius:50%;
        background:rgba(255,255,255,.08); pointer-events:none;
    }
    .doc-header-logo img { max-height:60px; max-width:100px; }
    .doc-header-info { flex:1; }
    .doc-school-name {
        font-size:1.1rem; font-weight:800; text-transform:uppercase;
        color:var(--doc-atext); letter-spacing:.02em; margin-bottom:4px;
    }
    .doc-school-meta { font-size:.78rem; opacity:.82; line-height:1.55; color:var(--doc-atext); }

    /* Diviseur */
    .doc-divider {
        height:3px;
        background:linear-gradient(90deg, var(--doc-accent) 0%, color-mix(in srgb, var(--doc-accent) 20%, transparent) 100%);
        border-radius:2px; margin:20px 0;
    }

    /* Titre document */
    .doc-title-wrap { text-align:center; margin:0 0 28px; }
    .doc-title {
        display:inline-block; font-size:1.3rem; font-weight:800;
        text-transform:uppercase; letter-spacing:.12em; color:var(--doc-accent);
        border-bottom:3px solid var(--doc-accent); padding-bottom:6px;
    }

    /* Corps */
    .doc-body { font-size:.92rem; line-height:1.85; color:var(--doc-body); text-align:justify; }
    .doc-body p { margin-bottom:14px; }
    .doc-hl {
        font-weight:700; color:var(--doc-accent);
        border-bottom:1px solid color-mix(in srgb, var(--doc-accent) 40%, transparent);
    }

    /* Table */
    .doc-table { width:100%; border-collapse:collapse; font-size:.84rem; margin:18px 0 6px; }
    .doc-table thead th {
        background:var(--doc-accent); color:var(--doc-atext);
        padding:9px 12px; font-weight:700; font-size:.73rem;
        letter-spacing:.07em; text-transform:uppercase; border:none; text-align:center;
    }
    .doc-table thead th:first-child { border-radius:8px 0 0 0; }
    .doc-table thead th:last-child  { border-radius:0 8px 0 0; }
    .doc-table tbody tr { border-bottom:1px solid var(--doc-border); }
    .doc-table tbody tr:last-child { border-bottom:none; }
    .doc-table tbody td { padding:9px 12px; text-align:center; color:var(--doc-body); }

    /* Footer */
    .doc-footer {
        display:flex; justify-content:space-between; align-items:flex-end;
        margin-top:40px; gap:20px;
    }
    .doc-date { font-style:italic; font-size:.85rem; color:var(--doc-muted); }
    .doc-signature {
        text-align:right; border-top:2px solid var(--doc-accent);
        padding-top:12px; min-width:200px;
    }
    .doc-sig-title { font-weight:700; color:var(--doc-accent); font-size:.88rem; margin-bottom:8px; }
    .doc-sig-name  { font-style:italic; color:var(--doc-muted); font-size:.84rem; margin-top:24px; }

    .doc-note {
        margin-top:28px; text-align:center; font-size:.73rem;
        font-style:italic; color:var(--doc-muted);
        border-top:1px solid var(--doc-border); padding-top:12px;
    }

    @media print {
        .doc-toolbar, .no-print { display:none !important; }
        .doc-paper { box-shadow:none; border:none; }
        .doc-page-wrap { max-width:100%; }
        .doc-inner { padding:20px; }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
<div class="main-content">

<div class="doc-toolbar no-print">
    <div>
        <div class="doc-toolbar-title"><i class="fas fa-eye"></i>Prévisualisation Certificat de Scolarité</div>
        <div class="doc-toolbar-sub">{{ $etudiant->nom }} {{ $etudiant->prenoms }} — {{ $etudiant->matricule }}</div>
    </div>
    <div class="doc-toolbar-btns">
        <a href="{{ route('esbtp.etudiants.show', $etudiant->id) }}" class="btn-acasi secondary">
            <i class="fas fa-arrow-left me-1"></i>Retour
        </a>
        <a href="{{ route('esbtp.etudiants.certificat', $etudiant->id) }}" class="btn-acasi success">
            <i class="fas fa-file-pdf me-1"></i>Générer PDF
        </a>
        <button onclick="window.print()" class="btn-acasi info">
            <i class="fas fa-print me-1"></i>Imprimer
        </button>
    </div>
</div>

<div class="doc-page-wrap">
<div class="doc-paper">
    @if(!empty($settings['logo_base64']))
    <div class="doc-watermark"><img src="{{ $settings['logo_base64'] }}" alt=""></div>
    @endif

    <div class="doc-inner">
        <div class="doc-header">
            @if(!empty($settings['show_logo']) && !empty($settings['logo_base64']))
            <div class="doc-header-logo"><img src="{{ $settings['logo_base64'] }}" alt="Logo"></div>
            @endif
            <div class="doc-header-info">
                <div class="doc-school-name">{{ $settings['name'] ?? '' }}</div>
                <div class="doc-school-meta">
                    @if(!empty($settings['address'])){{ $settings['address'] }}@endif
                    @if(!empty($settings['phone'])) &nbsp;·&nbsp; Tél: {{ $settings['phone'] }}@endif
                    @if(!empty($settings['email'])) &nbsp;·&nbsp; {{ $settings['email'] }}@endif
                </div>
            </div>
        </div>

        <div class="doc-divider"></div>

        <div class="doc-title-wrap">
            <span class="doc-title">Certificat de Scolarité</span>
        </div>

        <div class="doc-body">
            <p>Je soussigné(e), {{ $settings['director_title'] ?? '' }} de {{ $settings['name'] ?? '' }}, certifie que&nbsp;:</p>
            <p>L'étudiant(e) <span class="doc-hl">{{ $etudiant->nom }} {{ $etudiant->prenoms }}</span></p>

            @if($etudiant->date_naissance)
            <p>
                Né(e) le <span class="doc-hl">{{ $etudiant->date_naissance->format('d/m/Y') }}</span>
                @if($etudiant->lieu_naissance) à <span class="doc-hl">{{ $etudiant->lieu_naissance }}</span>@endif
            </p>
            @endif

            <p>Matricule&nbsp;: <span class="doc-hl">{{ $etudiant->matricule }}</span></p>
            <p>Est régulièrement inscrit(e) sur le registre des effectifs de l'année académique&nbsp;:</p>

            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Année scolaire</th>
                        <th>Classe suivie</th>
                        <th>Niveau d'étude</th>
                        <th>Filière</th>
                        <th>Moyenne/20</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inscriptions as $inscription)
                    <tr>
                        <td>@php
                            $rawYear = $inscription->anneeUniversitaire?->libelle
                                ?? $inscription->anneeUniversitaire?->name ?? null;
                            echo $rawYear
                                ? (preg_match('/(\d{4}-\d{4})/', $rawYear, $m) ? $m[1] : $rawYear)
                                : 'Non renseigné';
                        @endphp</td>
                        <td>{{ $inscription->classe->name ?? 'Non renseigné' }}</td>
                        <td>{{ $inscription->niveauEtude->name ?? 'Non renseigné' }}</td>
                        <td>{{ strtoupper($inscription->filiere->name ?? 'Non renseigné') }}</td>
                        <td>{{ $inscription->moyenne_generale ? number_format($inscription->moyenne_generale, 2) : '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5">Aucune inscription trouvée</td></tr>
                    @endforelse
                </tbody>
            </table>

            <p style="font-style:italic;margin-top:16px;">Suivant l'horaire du programme complet.</p>
            <p>Ce certificat est délivré à l'intéressé(e) pour servir et valoir ce que de droit.</p>
        </div>

        <div class="doc-footer">
            <div class="doc-date">Fait à {{ $settings['city'] ?? '' }}, le {{ now()->format('d/m/Y') }}</div>
            <div class="doc-signature">
                <div class="doc-sig-title">{{ $settings['director_title'] ?? '' }}</div>
                @if(!empty($settings['director_name']))
                <div class="doc-sig-name">{{ $settings['director_name'] }}</div>
                @endif
            </div>
        </div>

        <div class="doc-note">
            Ce certificat est un document officiel. Toute falsification constitue un délit passible de poursuites judiciaires.
        </div>
    </div>
</div>
</div>

</div>
</div>
@endsection

@push('scripts')
<script>
window.addEventListener('beforeprint', () => document.body.classList.add('printing'));
window.addEventListener('afterprint',  () => document.body.classList.remove('printing'));
</script>
@endpush
