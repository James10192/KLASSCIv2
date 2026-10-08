@extends('layouts.app')

@section('title', 'Prévisualisation Certificat - ' . $etudiant->nom . ' ' . $etudiant->prenoms)

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}?v={{ @filemtime(public_path('css/dashboard-moderne.css')) ?: '1' }}">
@include('pdf.partials.theme')
@php
    $pdfSettings  = \App\Helpers\SettingsHelper::getPdfSettings();
    $headerBg     = $pdfSettings['header_bg_color']   ?? '#0453cb';
    $accentColor  = $pdfSettings['primary_color']     ?? $headerBg;
    $accentText   = $pdfSettings['header_text_color'] ?? '#ffffff';
    $bodyText     = $pdfSettings['text_color']        ?? '#1f2937';
@endphp
<style>
    :root {
        --doc-header-bg: {{ $headerBg }};
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
    .doc-history {
        max-width:820px; margin:0 auto 20px; padding:16px 18px;
        background:#fff; border:1px solid var(--doc-border); border-radius:var(--doc-radius);
        box-shadow:0 1px 4px rgba(0,0,0,.05);
    }
    .doc-history-head { display:flex; gap:10px; align-items:flex-start; margin-bottom:12px; }
    .doc-history-head i { color:var(--doc-accent); margin-top:3px; }
    .doc-history-title { font-weight:800; color:var(--doc-body); }
    .doc-history-help { font-size:.78rem; color:var(--doc-muted); margin-top:2px; }
    .doc-history-row {
        display:grid; grid-template-columns:minmax(0,1fr) 150px auto; gap:10px;
        align-items:center; padding:10px 0; border-top:1px solid var(--doc-border);
    }
    .doc-history-label { min-width:0; font-size:.84rem; color:var(--doc-body); }
    .doc-history-label strong { display:block; }
    .doc-history-input { width:100%; border:1px solid #d1d5db; border-radius:8px; padding:8px 10px; }
    @media (max-width:640px) {
        .doc-history-row { grid-template-columns:1fr; }
    }

    .doc-page-wrap { max-width:820px; margin:0 auto; }
    .doc-paper {
        background:#fff; border:1px solid var(--doc-border);
        border-radius:var(--doc-radius); box-shadow:var(--doc-shadow);
        overflow:hidden; position:relative;
    }
    .doc-watermark {
        position:absolute; top:50%; left:50%; transform:translate(-50%,-50%);
        opacity:.10; width:60%; z-index:0; pointer-events:none; text-align:center;
    }
    .doc-watermark img { max-width:100%; }
    .doc-inner { position:relative; z-index:1; padding:36px 42px 32px; }

    /* Header établissement */
    .doc-header {
        background:var(--doc-header-bg); color:var(--doc-atext);
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
        background:var(--doc-header-bg); color:var(--doc-atext);
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
        margin-top:80px; gap:20px;
    }
    .doc-date { font-style:italic; font-size:.85rem; color:var(--doc-muted); }
    .doc-signature {
        text-align:right; border-top:2px solid var(--doc-accent);
        padding-top:24px; min-width:200px;
    }
    .doc-sig-title { font-weight:700; color:var(--doc-accent); font-size:.88rem; margin-bottom:8px; }
    .doc-sig-name  { font-style:italic; color:var(--doc-muted); font-size:.84rem; margin-top:48px; }

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
        <a href="{{ route('esbtp.etudiants.attestation-frequentation.preview', $etudiant->id) }}" class="btn-acasi info">
            <i class="fas fa-file-contract me-1"></i>Attestation
        </a>
        @php $printDecision = app(\App\Services\DocumentPrintGuard::class)->decide(auth()->user(), 'certificat', (int) $etudiant->id); $printAllowed = $printDecision->allowed; @endphp
        @include('esbtp.documents._request-approval', [
            'documentType' => 'certificat',
            'etudiantId' => $etudiant->id,
            'printDecision' => $printDecision,
        ])
        @if($printAllowed)
        <a href="{{ route('esbtp.etudiants.certificat.preview-pdf', $etudiant->id) }}" class="btn-acasi info" target="_blank" title="Aperçu PDF dans un nouvel onglet">
            <i class="fas fa-eye me-1"></i>Aperçu PDF
        </a>
        <a href="{{ route('esbtp.etudiants.certificat', $etudiant->id) }}" class="btn-acasi success">
            <i class="fas fa-file-pdf me-1"></i>Générer PDF
        </a>
        <a href="{{ route('esbtp.etudiants.certificat.preview-pdf', $etudiant->id) }}" target="_blank" rel="noopener" class="btn-acasi info">
            <i class="fas fa-print me-1"></i>Imprimer
        </a>
        @endif
    </div>
</div>

@php
    $moyennesHistoriques = $inscriptions->filter(
        fn ($inscription) => (bool) ($inscription->moyenne_historique_saisissable ?? false)
    );
@endphp
@can('bulletins.edit')
@if($moyennesHistoriques->isNotEmpty())
<div class="doc-history no-print">
    <div class="doc-history-head">
        <i class="fas fa-clock-rotate-left"></i>
        <div>
            <div class="doc-history-title">Completer une ancienne moyenne annuelle</div>
            <div class="doc-history-help">Uniquement pour une annee BTS terminee dont la moyenne annuelle ne peut pas etre reconstruite depuis les semestres. Cette saisie ne modifie aucune note et devient secondaire des que S1/S2 permettent un calcul officiel.</div>
        </div>
    </div>
    @foreach($moyennesHistoriques as $inscription)
    <form method="POST"
          action="{{ route('esbtp.etudiants.certificat.moyenne-historique', ['etudiant' => $etudiant->id, 'inscription' => $inscription->id]) }}"
          class="doc-history-row">
        @csrf
        @method('PATCH')
        <div class="doc-history-label">
            <strong>{{ $inscription->anneeUniversitaire?->display_name ?? 'Annee historique' }}</strong>
            <span>{{ $inscription->classe?->name ?? $inscription->niveauEtude?->name ?? 'BTS' }}</span>
        </div>
        <input class="doc-history-input" type="number" name="moyenne" min="0" max="20" step="0.01"
               value="{{ $inscription->moyenne_historique_existante !== null ? number_format((float) $inscription->moyenne_historique_existante, 2, '.', '') : '' }}"
               placeholder="Moyenne /20" required aria-label="Moyenne annuelle sur 20">
        <button class="btn-acasi primary" type="submit"><i class="fas fa-save me-1"></i>Enregistrer</button>
    </form>
    @endforeach
</div>
@endif
@endcan

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
            <p>Est régulièrement inscrit(e) sur le registre des effectifs de l'année universitaire&nbsp;:</p>

            @php
                $showClasse  = $settings['show_classe']  ?? true;
                $showNiveau  = $settings['show_niveau']  ?? true;
                $showFiliere = $settings['show_filiere'] ?? true;
                $colCount = 2 + ($showClasse ? 1 : 0) + ($showNiveau ? 1 : 0) + ($showFiliere ? 1 : 0);
            @endphp
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Année universitaire</th>
                        @if($showClasse)<th>Classe suivie</th>@endif
                        @if($showNiveau)<th>Niveau d'étude</th>@endif
                        @if($showFiliere)<th>Filière</th>@endif
                        <th>Moyenne/20</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inscriptions as $inscription)
                    <tr>
                        <td>@php
                            $rawYear = $inscription->anneeUniversitaire?->display_name
                                ?? $inscription->anneeUniversitaire?->name ?? null;
                            echo $rawYear
                                ? (preg_match('/(\d{4}-\d{4})/', $rawYear, $m) ? $m[1] : $rawYear)
                                : 'Non renseigné';
                        @endphp
                        @if($inscription->is_sous_reserve)
                            <br><small style="color:#d97706;font-weight:600;">Sous réserve{{ $inscription->condition_reserve ? ' de son ' . $inscription->condition_reserve : '' }}</small>
                        @endif
                        </td>
                        @if($showClasse)<td>{{ $inscription->classe->name ?? 'Non renseigné' }}</td>@endif
                        @if($showNiveau)<td>{{ $inscription->niveauEtude->name ?? 'Non renseigné' }}</td>@endif
                        @if($showFiliere)<td>{{ strtoupper($inscription->filiere->name ?? 'Non renseigné') }}</td>@endif
                        <td>{{ $inscription->moyenne_generale_calculee !== null ? number_format($inscription->moyenne_generale_calculee, 2) : '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="{{ $colCount }}">Aucune inscription trouvée</td></tr>
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

