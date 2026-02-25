@extends('layouts.app')

@section('title', 'Situation Financière - ' . $inscription->etudiant->nom . ' ' . $inscription->etudiant->prenoms)

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
        --sf-accent:   {{ $accentColor }};
        --sf-atext:    {{ $accentText }};
        --sf-body:     {{ $bodyText }};
        --sf-muted:    #6b7280;
        --sf-border:   #e5e7eb;
        --sf-bg:       #f8fafc;
        --sf-card:     #ffffff;
        --sf-radius:   12px;
        --sf-shadow:   0 1px 3px rgba(0,0,0,.07), 0 4px 16px rgba(0,0,0,.05);
    }

    /* Toolbar */
    .sf-toolbar {
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 12px;
        padding: 16px 24px;
        background: var(--sf-card); border: 1px solid var(--sf-border);
        border-radius: var(--sf-radius); margin-bottom: 24px;
        box-shadow: var(--sf-shadow);
    }
    .sf-toolbar-title { font-size: 1.1rem; font-weight: 700; color: var(--sf-body); display: flex; align-items: center; gap: 8px; }
    .sf-toolbar-title i { color: var(--sf-accent); }
    .sf-toolbar-sub   { font-size: .8rem; color: var(--sf-muted); margin-top: 2px; }
    .sf-toolbar-actions { display: flex; gap: 8px; flex-wrap: wrap; }

    /* KPI Grid */
    .sf-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 14px; margin-bottom: 24px;
    }
    .sf-kpi {
        background: var(--sf-card); border: 1px solid var(--sf-border);
        border-radius: var(--sf-radius); padding: 18px 20px;
        border-left: 4px solid var(--kpi-clr, var(--sf-accent));
        box-shadow: var(--sf-shadow); position: relative; overflow: hidden;
    }
    .sf-kpi::after {
        content:''; position: absolute; top:-24px; right:-24px;
        width:80px; height:80px; border-radius:50%;
        background: color-mix(in srgb, var(--kpi-clr, var(--sf-accent)) 8%, transparent);
        pointer-events:none;
    }
    .sf-kpi-label { font-size:.7rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--sf-muted); margin-bottom:5px; }
    .sf-kpi-amount { font-size:1.6rem; font-weight:800; color:var(--kpi-clr, var(--sf-accent)); line-height:1.1; }
    .sf-kpi-unit   { font-size:.85rem; font-weight:600; }
    .sf-kpi-sub    { font-size:.73rem; color:var(--sf-muted); display:flex; align-items:center; gap:5px; margin-top:4px; }
    .sf-kpi-sub i  { color:var(--kpi-clr, var(--sf-accent)); }

    /* Main card */
    .sf-card {
        background: var(--sf-card); border: 1px solid var(--sf-border);
        border-radius: var(--sf-radius); box-shadow: var(--sf-shadow);
        margin-bottom: 20px; overflow: hidden;
    }

    /* Document header (établissement) */
    .sf-doc-header {
        background: var(--sf-accent); color: var(--sf-atext);
        padding: 24px 28px 0; position: relative; overflow: hidden;
    }
    .sf-doc-header::before {
        content:''; position:absolute; bottom:-40px; right:-40px;
        width:160px; height:160px; border-radius:50%;
        background:rgba(255,255,255,.07); pointer-events:none;
    }
    .sf-doc-header-top { display:flex; align-items:center; gap:18px; position:relative; z-index:1; }
    .sf-doc-logo img   { height:52px; filter:brightness(0) invert(1); }
    .sf-school-name    { font-size:1.2rem; font-weight:800; letter-spacing:.01em; }
    .sf-school-meta    { font-size:.78rem; opacity:.8; margin-top:3px; line-height:1.5; }

    .sf-doc-title-strip {
        position:relative; z-index:1;
        background:rgba(0,0,0,.18); backdrop-filter:blur(4px);
        margin-top:18px; padding:13px 0 13px;
        display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;
    }
    .sf-doc-title-text {
        font-size:.95rem; font-weight:800; letter-spacing:.12em; text-transform:uppercase;
        color: var(--sf-atext);
    }
    .sf-meta-pills { display:flex; gap:8px; flex-wrap:wrap; }
    .sf-pill {
        background:rgba(255,255,255,.18); color:var(--sf-atext);
        border:1px solid rgba(255,255,255,.25); border-radius:20px;
        padding:3px 11px; font-size:.73rem; font-weight:600;
    }

    /* Section head */
    .sf-section-head {
        padding: 14px 24px 12px; border-bottom: 1px solid var(--sf-border);
        display: flex; align-items: baseline; gap: 10px;
    }
    .sf-section-title {
        font-size:.78rem; font-weight:700; letter-spacing:.07em; text-transform:uppercase;
        color: var(--sf-accent); display:flex; align-items:center; gap:6px;
    }
    .sf-section-sub { font-size:.75rem; color:var(--sf-muted); }
    .sf-body { padding: 20px 24px; }

    /* Student profile */
    .sf-student-grid {
        display: grid; grid-template-columns: 130px 1fr 1fr; gap: 28px; align-items: start;
    }
    @media (max-width:768px) { .sf-student-grid { grid-template-columns:1fr; } }
    .sf-photo-wrap { text-align:center; }
    .sf-photo-img  {
        width:110px; height:110px; border-radius:50%; object-fit:cover;
        border:3px solid var(--sf-accent); display:block; margin:0 auto 10px;
    }
    .sf-photo-empty {
        width:110px; height:110px; border-radius:50%;
        background:var(--sf-bg); border:3px solid var(--sf-border);
        display:flex; align-items:center; justify-content:center; margin:0 auto 10px;
    }
    .sf-photo-empty i { font-size:2.4rem; color:#d1d5db; }
    .sf-student-name { font-size:.9rem; font-weight:700; color:var(--sf-body); text-align:center; }
    .sf-student-mat  { font-size:.73rem; color:var(--sf-muted); text-align:center; margin-top:2px; }
    .sf-info-group h6 {
        font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em;
        color:var(--sf-accent); margin-bottom:10px; display:flex; align-items:center; gap:6px;
    }
    .sf-info-row  { display:flex; font-size:.8rem; margin-bottom:6px; }
    .sf-info-lbl  { font-weight:600; color:var(--sf-body); min-width:130px; flex-shrink:0; }
    .sf-info-val  { color:var(--sf-muted); }

    /* Table */
    .sf-table { width:100%; border-collapse:collapse; font-size:.8rem; }
    .sf-table thead th {
        background: var(--sf-accent); color: var(--sf-atext);
        padding:9px 14px; font-weight:700; font-size:.68rem;
        letter-spacing:.07em; text-transform:uppercase; border:none;
    }
    .sf-table thead th:first-child { border-radius:8px 0 0 0; }
    .sf-table thead th:last-child  { border-radius:0 8px 0 0; }
    .sf-table tbody tr { border-bottom:1px solid var(--sf-border); }
    .sf-table tbody tr:last-child { border-bottom:none; }
    .sf-table tbody tr:hover { background:var(--sf-bg); }
    .sf-table tbody tr.row-rq { background:#fffbeb; }
    .sf-table tbody tr.row-rq:hover { background:#fef3c7; }
    .sf-table td { padding:10px 14px; color:var(--sf-body); vertical-align:middle; }

    /* Badges */
    .sf-badge {
        display:inline-flex; align-items:center; gap:4px;
        padding:3px 9px; border-radius:20px; font-size:.7rem; font-weight:700; white-space:nowrap;
    }
    .b-danger   { background:#fee2e2; color:#991b1b; }
    .b-info     { background:#dbeafe; color:#1d4ed8; }
    .b-success  { background:#d1fae5; color:#065f46; }
    .b-warning  { background:#fef3c7; color:#92400e; }
    .b-partial  { background:#fce7f3; color:#9d174d; }
    .b-reliquat { background:#fef3c7; color:#d97706; }

    /* Footer */
    .sf-footer { text-align:center; padding:14px; color:var(--sf-muted); font-size:.74rem; }

    @media print {
        .sf-toolbar, .no-print { display:none !important; }
        .sf-card { box-shadow:none; }
        .main-content { padding:0 !important; }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
<div class="main-content">

{{-- Toolbar --}}
<div class="sf-toolbar no-print">
    <div>
        <div class="sf-toolbar-title"><i class="fas fa-chart-line"></i>Situation Financière</div>
        <div class="sf-toolbar-sub">{{ $inscription->etudiant->nom }} {{ $inscription->etudiant->prenoms }} — {{ $inscription->etudiant->matricule }}</div>
    </div>
    <div class="sf-toolbar-actions">
        <a href="{{ route('esbtp.inscriptions.situation-financiere.pdf', $inscription->id) }}" class="btn-acasi danger">
            <i class="fas fa-file-pdf"></i>Télécharger PDF
        </a>
        <button onclick="window.print()" class="btn-acasi primary">
            <i class="fas fa-print"></i>Imprimer
        </button>
        @php
            $returnUrl = request()->headers->get('referer') ?: route('esbtp.inscriptions.show', $inscription->id);
            $pu = parse_url($returnUrl); $au = parse_url(config('app.url'));
            if (isset($pu['host']) && $pu['host'] !== ($au['host'] ?? '')) {
                $returnUrl = route('esbtp.inscriptions.show', $inscription->id);
            }
        @endphp
        <a href="{{ $returnUrl }}" class="btn-acasi secondary">
            <i class="fas fa-arrow-left"></i>Retour
        </a>
    </div>
</div>

{{-- KPI Cards --}}
<div class="sf-kpi-grid">
    <div class="sf-kpi" style="--kpi-clr:var(--sf-accent);">
        <div class="sf-kpi-label">Total Attendu</div>
        <div class="sf-kpi-amount">{{ number_format($statistiques['total_attendu'], 0, ',', ' ') }} <span class="sf-kpi-unit">FCFA</span></div>
        <div class="sf-kpi-sub"><i class="fas fa-money-bill-wave"></i> Frais année + reliquats</div>
    </div>
    <div class="sf-kpi" style="--kpi-clr:#059669; border-left-color:#059669;">
        <div class="sf-kpi-label">Total Payé</div>
        <div class="sf-kpi-amount">{{ number_format($statistiques['total_paye'], 0, ',', ' ') }} <span class="sf-kpi-unit">FCFA</span></div>
        <div class="sf-kpi-sub"><i class="fas fa-check-circle"></i> Paiements validés</div>
    </div>
    @php $soldeColor = $statistiques['solde_restant'] > 0 ? '#dc2626' : '#059669'; @endphp
    <div class="sf-kpi" style="--kpi-clr:{{ $soldeColor }}; border-left-color:{{ $soldeColor }};">
        <div class="sf-kpi-label">Solde</div>
        <div class="sf-kpi-amount">{{ number_format($statistiques['solde_restant'], 0, ',', ' ') }} <span class="sf-kpi-unit">FCFA</span></div>
        <div class="sf-kpi-sub"><i class="fas fa-balance-scale"></i> {{ $statistiques['solde_restant'] > 0 ? 'Restant à payer' : 'Soldé' }}</div>
    </div>
    @if($statistiques['total_reliquats'] > 0)
    <div class="sf-kpi" style="--kpi-clr:#d97706; border-left-color:#d97706;">
        <div class="sf-kpi-label">Reliquats</div>
        <div class="sf-kpi-amount">{{ number_format($statistiques['total_reliquats'], 0, ',', ' ') }} <span class="sf-kpi-unit">FCFA</span></div>
        <div class="sf-kpi-sub"><i class="fas fa-history"></i> Années précédentes</div>
    </div>
    @endif
</div>

{{-- Document card --}}
<div class="sf-card">
    {{-- Header établissement --}}
    <div class="sf-doc-header">
        <div class="sf-doc-header-top">
            @if($etablissement['logo'] && file_exists(storage_path('app/public/' . $etablissement['logo'])))
            <div class="sf-doc-logo">
                <img src="data:image/{{ pathinfo($etablissement['logo'], PATHINFO_EXTENSION) }};base64,{{ base64_encode(file_get_contents(storage_path('app/public/' . $etablissement['logo']))) }}" alt="Logo">
            </div>
            @endif
            <div>
                <div class="sf-school-name">{{ $etablissement['nom'] ?? 'KLASSCI' }}</div>
                <div class="sf-school-meta">
                    @if($etablissement['adresse']){{ $etablissement['adresse'] }}@endif
                    @if($etablissement['telephone']) &nbsp;|&nbsp; Tél: {{ $etablissement['telephone'] }}@endif
                    @if($etablissement['email']) &nbsp;|&nbsp; {{ $etablissement['email'] }}@endif
                </div>
            </div>
        </div>
        <div class="sf-doc-title-strip">
            <span class="sf-doc-title-text">Bulletin de Situation Financière</span>
            <div class="sf-meta-pills">
                <span class="sf-pill"><i class="fas fa-calendar-alt me-1"></i>{{ $inscription->anneeUniversitaire->name ?? 'N/A' }}</span>
                <span class="sf-pill"><i class="fas fa-calendar-day me-1"></i>{{ now()->format('d/m/Y') }}</span>
                <span class="sf-pill"><i class="fas fa-users me-1"></i>{{ $inscription->classe->name ?? 'N/A' }}</span>
            </div>
        </div>
    </div>

    {{-- Infos étudiant --}}
    <div class="sf-section-head">
        <div class="sf-section-title"><i class="fas fa-user-circle"></i>Informations de l'étudiant</div>
        <span class="sf-section-sub">Données personnelles et académiques</span>
    </div>
    <div class="sf-body">
        <div class="sf-student-grid">
            {{-- Photo --}}
            <div class="sf-photo-wrap">
                @if($inscription->etudiant->photo_url)
                    <img src="{{ $inscription->etudiant->photo_url }}" alt="Photo" class="sf-photo-img">
                @else
                    <div class="sf-photo-empty"><i class="fas fa-user"></i></div>
                @endif
                <div class="sf-student-name">{{ $inscription->etudiant->nom }}<br>{{ $inscription->etudiant->prenoms }}</div>
                <div class="sf-student-mat">{{ $inscription->etudiant->matricule }}</div>
            </div>

            {{-- Infos perso --}}
            <div class="sf-info-group">
                <h6><i class="fas fa-info-circle"></i>Informations personnelles</h6>
                <div class="sf-info-row"><span class="sf-info-lbl">Genre</span><span class="sf-info-val">{{ $inscription->etudiant->genre == 'M' ? 'Masculin' : 'Féminin' }}</span></div>
                <div class="sf-info-row"><span class="sf-info-lbl">Date de naissance</span><span class="sf-info-val">{{ $inscription->etudiant->date_naissance ? \Carbon\Carbon::parse($inscription->etudiant->date_naissance)->format('d/m/Y') : 'Non renseigné' }}</span></div>
                <div class="sf-info-row"><span class="sf-info-lbl">Lieu de naissance</span><span class="sf-info-val">{{ $inscription->etudiant->lieu_naissance ?? 'Non renseigné' }}</span></div>
                <div class="sf-info-row"><span class="sf-info-lbl">Téléphone</span><span class="sf-info-val">{{ $inscription->etudiant->telephone ?? 'Non renseigné' }}</span></div>
                <div class="sf-info-row"><span class="sf-info-lbl">Adresse</span><span class="sf-info-val">{{ $inscription->etudiant->adresse ?? 'Non renseigné' }}</span></div>
            </div>

            {{-- Infos académiques --}}
            <div class="sf-info-group">
                <h6><i class="fas fa-graduation-cap"></i>Informations académiques</h6>
                <div class="sf-info-row"><span class="sf-info-lbl">Filière</span><span class="sf-info-val">{{ $inscription->classe->filiere->name ?? 'Non renseigné' }}</span></div>
                <div class="sf-info-row"><span class="sf-info-lbl">Niveau</span><span class="sf-info-val">{{ $inscription->classe->niveau->name ?? 'Non renseigné' }}</span></div>
                <div class="sf-info-row"><span class="sf-info-lbl">Classe</span><span class="sf-info-val">{{ $inscription->classe->name ?? 'Non renseigné' }}</span></div>
                <div class="sf-info-row">
                    <span class="sf-info-lbl">Statut</span>
                    <span class="sf-info-val">
                        <span class="sf-badge {{ $inscription->status == 'active' ? 'b-success' : 'b-warning' }}">{{ ucfirst($inscription->status) }}</span>
                    </span>
                </div>
                @if($inscription->etudiant->parents && $inscription->etudiant->parents->count() > 0)
                @php $parent = $inscription->etudiant->parents->first(); @endphp
                <div style="border-top:1px solid var(--sf-border);margin-top:10px;padding-top:10px;">
                    <div class="sf-info-row"><span class="sf-info-lbl" style="color:var(--sf-accent);font-weight:700;"><i class="fas fa-users me-1"></i>Parent / Tuteur</span></div>
                    <div class="sf-info-row"><span class="sf-info-lbl">Nom</span><span class="sf-info-val">{{ ($parent->nom ?? '') . ' ' . ($parent->prenoms ?? '') }}</span></div>
                    <div class="sf-info-row"><span class="sf-info-lbl">Téléphone</span><span class="sf-info-val">{{ $parent->telephone ?? 'Non renseigné' }}</span></div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Frais souscrits --}}
<div class="sf-card">
    <div class="sf-section-head">
        <div class="sf-section-title"><i class="fas fa-money-bill-wave"></i>Détail des Frais — Année {{ $inscription->anneeUniversitaire->name }}</div>
        <span class="sf-section-sub">Frais de l'année universitaire en cours</span>
    </div>
    <div class="sf-body" style="padding-top:0;">
        @if($fraisSouscrits->count() > 0)
        <div class="table-responsive">
            <table class="sf-table">
                <thead>
                    <tr>
                        <th>Catégorie de Frais</th>
                        <th class="text-center" width="110">Type</th>
                        <th class="text-end" width="150">Attendu</th>
                        <th class="text-end" width="130">Payé</th>
                        <th class="text-end" width="130">Solde</th>
                        <th class="text-center" width="100">Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($fraisSouscrits as $frais)
                    @php
                        $mp = $inscription->paiements
                            ->where('frais_category_id', $frais->frais_category_id)
                            ->where('status', 'validé')
                            ->filter(fn($p) => $p->type_paiement != 'reliquat' || is_null($p->type_paiement))
                            ->sum('montant');
                        $sl = $frais->amount - $mp;
                    @endphp
                    <tr>
                        <td><div style="display:flex;align-items:center;gap:8px;"><i class="fas fa-tag" style="color:var(--sf-accent);font-size:.72rem;"></i><strong>{{ $frais->fraisCategory->name ?? '—' }}</strong></div></td>
                        <td class="text-center">
                            @if($frais->fraisCategory->is_mandatory)
                                <span class="sf-badge b-danger"><i class="fas fa-exclamation-circle"></i>Obligatoire</span>
                            @else
                                <span class="sf-badge b-info"><i class="fas fa-star"></i>Optionnel</span>
                            @endif
                        </td>
                        <td class="text-end"><strong>{{ number_format($frais->amount, 0, ',', ' ') }} FCFA</strong></td>
                        <td class="text-end" style="color:#059669;font-weight:600;">{{ number_format($mp, 0, ',', ' ') }} FCFA</td>
                        <td class="text-end"><strong style="color:{{ $sl > 0 ? '#dc2626' : '#059669' }};">{{ number_format($sl, 0, ',', ' ') }} FCFA</strong></td>
                        <td class="text-center">
                            @if($sl <= 0) <span class="sf-badge b-success">Soldé</span>
                            @elseif($mp > 0) <span class="sf-badge b-partial">Partiel</span>
                            @else <span class="sf-badge b-danger">Impayé</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach

                    @if($reliquatsEntrants->count() > 0)
                        @foreach($reliquatsEntrants as $rq)
                        @if($rq->solde_restant > 0)
                        <tr class="row-rq">
                            <td>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <i class="fas fa-history" style="color:#d97706;font-size:.72rem;"></i>
                                    <div>
                                        <strong>{{ $rq->fraisSubscription->fraisCategory->name ?? '—' }}</strong>
                                        <br><small style="color:var(--sf-muted);">Reliquat {{ $rq->inscriptionSource->anneeUniversitaire->name ?? 'N/A' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center"><span class="sf-badge b-warning"><i class="fas fa-exclamation-circle"></i>{{ $rq->fraisSubscription->fraisCategory->is_mandatory ? 'Obligatoire' : 'Optionnel' }}</span></td>
                            <td class="text-end"><strong>{{ number_format($rq->montant_reliquat, 0, ',', ' ') }} FCFA</strong></td>
                            <td class="text-end" style="color:#059669;font-weight:600;">{{ number_format($rq->montant_regle, 0, ',', ' ') }} FCFA</td>
                            <td class="text-end" style="color:#d97706;font-weight:600;">{{ number_format($rq->solde_restant, 0, ',', ' ') }} FCFA</td>
                            <td class="text-center"><span class="sf-badge b-reliquat">Reliquat</span></td>
                        </tr>
                        @endif
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-5" style="color:var(--sf-muted);">
            <i class="fas fa-exclamation-triangle fa-2x mb-3" style="color:#f59e0b;"></i>
            <div style="font-weight:600;margin-bottom:4px;">Aucun frais souscrit</div>
            <div style="font-size:.8rem;">Aucun frais souscrit pour cette inscription.</div>
        </div>
        @endif
    </div>
</div>

{{-- Historique paiements --}}
<div class="sf-card">
    <div class="sf-section-head">
        <div class="sf-section-title"><i class="fas fa-receipt"></i>Historique des Paiements</div>
        <span class="sf-section-sub">{{ $inscription->paiements->where('status', 'validé')->count() }} paiement(s) validé(s)</span>
    </div>
    <div class="sf-body" style="padding-top:0;">
        @if($inscription->paiements->where('status', 'validé')->count() > 0)
        <div class="table-responsive">
            <table class="sf-table">
                <thead>
                    <tr>
                        <th>Date</th><th>Catégorie</th><th>Mode</th>
                        <th class="text-end" width="140">Montant</th>
                        <th class="text-center" width="100">Statut</th>
                        <th>Référence</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($inscription->paiements->where('status', 'validé')->sortByDesc('date_paiement') as $p)
                    <tr>
                        <td>{{ $p->date_paiement ? \Carbon\Carbon::parse($p->date_paiement)->format('d/m/Y') : '—' }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:7px;">
                                @if($p->type_paiement == 'reliquat')
                                    <i class="fas fa-history" style="color:#d97706;font-size:.72rem;"></i>
                                    {{ $p->fraisCategory->name ?? '—' }}
                                    <span class="sf-badge b-reliquat">Reliquat</span>
                                @else
                                    <i class="fas fa-money-bill" style="color:#059669;font-size:.72rem;"></i>
                                    {{ $p->fraisCategory->name ?? '—' }}
                                @endif
                            </div>
                        </td>
                        <td style="color:var(--sf-muted);">{{ $p->mode_paiement ?? '—' }}</td>
                        <td class="text-end" style="color:#059669;font-weight:700;">{{ number_format($p->montant, 0, ',', ' ') }} FCFA</td>
                        <td class="text-center"><span class="sf-badge b-success">{{ strtoupper($p->status) }}</span></td>
                        <td><code style="font-size:.73rem;color:var(--sf-muted);">{{ $p->numero_recu ?? '—' }}</code></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-5" style="color:var(--sf-muted);">
            <i class="fas fa-receipt fa-2x mb-3"></i>
            <div style="font-weight:600;margin-bottom:4px;">Aucun paiement</div>
            <div style="font-size:.8rem;">Aucun paiement validé pour cette inscription.</div>
        </div>
        @endif
    </div>
</div>

<div class="sf-footer">
    <i class="fas fa-info-circle me-1"></i>
    Document généré automatiquement le {{ now()->format('d/m/Y à H:i') }}
</div>

</div>
</div>
@endsection
