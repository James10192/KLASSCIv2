@extends('layouts.app')

@section('title', 'Prévisualisation Attestation de Fréquentation - ' . $etudiant->nom . ' ' . $etudiant->prenoms)

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
        opacity:.10; width:60%; z-index:0; pointer-events:none; text-align:center;
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

    .doc-divider {
        height:3px;
        background:linear-gradient(90deg, var(--doc-accent) 0%, color-mix(in srgb, var(--doc-accent) 20%, transparent) 100%);
        border-radius:2px; margin:20px 0;
    }

    .doc-title-wrap { text-align:center; margin:0 0 28px; }
    .doc-title {
        display:inline-block; font-size:1.3rem; font-weight:800;
        text-transform:uppercase; letter-spacing:.12em; color:var(--doc-accent);
        border-bottom:3px solid var(--doc-accent); padding-bottom:6px;
    }

    /* Alerte workflow */
    .doc-alert {
        background:#fffbeb; border-left:4px solid #f59e0b;
        border-radius:8px; padding:12px 16px; margin-bottom:20px;
        font-size:.84rem; color:#92400e;
    }
    .doc-alert strong { color:#78350f; }

    /* Corps */
    .doc-body { font-size:.92rem; line-height:1.85; color:var(--doc-body); text-align:justify; }
    .doc-body p { margin-bottom:14px; }
    .doc-hl {
        font-weight:700; color:var(--doc-accent);
        border-bottom:1px solid color-mix(in srgb, var(--doc-accent) 40%, transparent);
    }

    /* Bloc infos étudiant (fond accent) */
    .doc-student-block {
        background: color-mix(in srgb, var(--doc-accent) 8%, white);
        border-left:4px solid var(--doc-accent);
        border-radius:8px; padding:16px 20px; margin:16px 0;
    }
    .doc-student-row {
        display:flex; align-items:baseline; gap:8px;
        margin-bottom:8px; font-size:.88rem;
    }
    .doc-student-row:last-child { margin-bottom:0; }
    .doc-student-lbl {
        font-weight:700; color:var(--doc-accent); min-width:200px; flex-shrink:0;
    }
    .doc-student-val { color:var(--doc-body); }

    /* Bloc statut */
    .doc-status-block {
        background:#f8fafc; border:1px solid var(--doc-border);
        border-radius:8px; padding:12px 20px; margin:16px 0;
        text-align:center; font-size:.86rem; font-style:italic;
        color:var(--doc-muted);
    }
    .doc-status-block strong { color:var(--doc-body); }

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
    .doc-sig-note  { text-align:center; font-size:.73rem; font-style:italic; color:var(--doc-muted); margin:16px 0; }

    .doc-note {
        margin-top:16px; text-align:center; font-size:.73rem;
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
        <div class="doc-toolbar-title"><i class="fas fa-eye"></i>Prévisualisation Attestation de Fréquentation</div>
        <div class="doc-toolbar-sub">{{ $etudiant->nom }} {{ $etudiant->prenoms }} — {{ $etudiant->matricule }}</div>
    </div>
    <div class="doc-toolbar-btns">
        <a href="{{ route('esbtp.etudiants.show', $etudiant->id) }}" class="btn-acasi secondary">
            <i class="fas fa-arrow-left me-1"></i>Retour
        </a>
        <a href="{{ route('esbtp.etudiants.certificat.preview', $etudiant->id) }}" class="btn-acasi info">
            <i class="fas fa-certificate me-1"></i>Certificat
        </a>
        <a href="{{ route('esbtp.etudiants.attestation-frequentation', $etudiant->id) }}" class="btn-acasi success">
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
            <span class="doc-title">Attestation de Fréquentation</span>
        </div>

        {{-- Alerte workflow si inscription non finalisée --}}
        @if(!empty($alerteWorkflow) && !empty($hasFutureSousReserve))
        <div class="doc-alert" style="background:rgba(59,130,246,.08); border-color:#3b82f6; color:#1e40af;">
            <strong><i class="fas fa-info-circle me-1"></i> Information :</strong>
            Cet étudiant a une inscription sous réserve pour une année future.
            L'attestation sera disponible quand l'inscription sera confirmée.
        </div>
        @elseif(!empty($alerteWorkflow))
        <div class="doc-alert">
            <strong><i class="fas fa-exclamation-triangle me-1"></i> Attention :</strong> Aucune inscription active et finalisée trouvée pour cet étudiant.
            Veuillez <a href="{{ route('esbtp.etudiants.show', $etudiant->id) }}" style="color:var(--doc-accent);">valider et finaliser l'inscription</a>
            (étape "Étudiant créé") avant de générer l'attestation.
        </div>
        @endif

        <div class="doc-body">
            <p>Je soussigné(e), {{ $settings['director_title'] ?? '' }} de {{ $settings['name'] ?? '' }}, atteste que&nbsp;:</p>

            <p>
                {{ $etudiant->sexe === 'F' ? 'Mme / M. / Mlle' : 'M.' }}
                <span class="doc-hl">{{ strtoupper($etudiant->nom) }} {{ strtoupper($etudiant->prenoms) }}</span>
            </p>

            @if($etudiant->date_naissance)
            <p>
                Né(e) le <span class="doc-hl">{{ $etudiant->date_naissance->format('d/m/Y') }}</span>
                @if($etudiant->lieu_naissance) à <span class="doc-hl">{{ strtoupper($etudiant->lieu_naissance) }}</span>@endif
            </p>
            @endif

            <p>
                @php
                    $anneeText = $inscription->anneeUniversitaire->name
                        ?? $inscription->anneeUniversitaire->nom
                        ?? $inscription->anneeUniversitaire->libelle ?? '';
                    $anneeFormatted = preg_match('/(\d{4}-\d{4})/', $anneeText, $m) ? $m[1] : $anneeText;
                @endphp
                Est régulièrement inscrit(e) au titre de l'année universitaire
                <span class="doc-hl">{{ $anneeFormatted }}</span>
                @if($inscription->is_sous_reserve)
                sous réserve de son <span class="doc-hl">{{ $inscription->condition_reserve ?? 'diplôme' }}</span>
                @endif
            </p>

            <div class="doc-student-block">
                <div class="doc-student-row">
                    <span class="doc-student-lbl">En classe de&nbsp;:</span>
                    <span class="doc-student-val">{{ $inscription->classe->name ?? ($inscription->niveauEtude->name ?? 'Non renseigné') }}</span>
                </div>
                <div class="doc-student-row">
                    <span class="doc-student-lbl">Filière&nbsp;:</span>
                    <span class="doc-student-val">{{ strtoupper($inscription->filiere->name ?? 'Non renseigné') }}</span>
                </div>
                <div class="doc-student-row">
                    <span class="doc-student-lbl">Sous le numéro Matricule&nbsp;:</span>
                    <span class="doc-student-val">{{ $etudiant->numero_etudiant ?? $etudiant->matricule }}</span>
                </div>
            </div>

            <div class="doc-status-block">
                <strong>Statut* :</strong> Affecté / Non affecté
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <strong>Boursier* :</strong> Oui / Non
            </div>

            <p>En foi de quoi, la présente attestation lui est délivrée pour servir et valoir ce que de droit.</p>
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

        <div class="doc-sig-note">*Rayer la mention inutile</div>

        <div class="doc-note">
            Ce document est un certificat officiel. Toute falsification constitue un délit passible de poursuites judiciaires.
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
