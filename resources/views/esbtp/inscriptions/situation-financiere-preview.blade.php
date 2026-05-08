@extends('layouts.app')

@section('title', 'Situation Financière - ' . $inscription->etudiant->nom . ' ' . $inscription->etudiant->prenoms)

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ── Toolbar ── */
    .sf-toolbar {
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 12px;
        padding: 16px 24px;
        background: #fff; border: 1px solid var(--k-border, #e5e7eb);
        border-radius: var(--k-radius-lg, 12px); margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,.06);
    }
    .sf-toolbar-title { font-size: 1.05rem; font-weight: 700; color: var(--k-text, #1e293b); display: flex; align-items: center; gap: 8px; }
    .sf-toolbar-title i { color: var(--k-blue, #0453cb); }
    .sf-toolbar-sub { font-size: .78rem; color: var(--k-muted, #64748b); margin-top: 2px; }
    .sf-toolbar-actions { display: flex; gap: 8px; flex-wrap: wrap; }

    /* ── Dark Hero (same as etudiants.show finance tab) ── */
    .sf-hero {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #0c1a3a 100%);
        border-radius: var(--k-radius-lg, 14px);
        padding: 28px 28px 20px;
        margin-bottom: 18px;
        box-shadow: 0 8px 40px rgba(4,83,203,.18), 0 2px 8px rgba(0,0,0,.12);
        position: relative;
        overflow: hidden;
    }
    .sf-hero::before {
        content: '';
        position: absolute; inset: 0;
        background-image: radial-gradient(circle at 80% 20%, rgba(94,145,222,.15) 0%, transparent 60%),
                          radial-gradient(circle at 10% 90%, rgba(4,83,203,.1) 0%, transparent 50%);
        pointer-events: none;
    }

    .sf-hero-year-badge {
        display: inline-flex; align-items: center; gap: 8px;
        background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.12);
        border-radius: 20px; padding: 6px 16px;
        font-size: .8rem; font-weight: 500; color: rgba(255,255,255,.6);
        margin-bottom: 20px;
    }
    .sf-hero-year-badge strong { color: rgba(255,255,255,.9); font-weight: 700; }

    .sf-hero-grid {
        display: grid;
        grid-template-columns: 1fr auto 1fr auto 1fr;
        gap: 0;
        position: relative;
        margin-bottom: 24px;
    }
    @media (max-width: 768px) {
        .sf-hero-grid { grid-template-columns: 1fr; gap: 20px; }
        .sf-sep { display: none !important; }
    }
    .sf-sep {
        width: 1px; background: rgba(255,255,255,.12);
        margin: 0 24px; align-self: stretch;
    }
    .sf-kpi-block { padding: 0 8px; }
    .sf-kpi-label {
        font-size: .72rem; font-weight: 600; text-transform: uppercase;
        letter-spacing: .08em; color: rgba(255,255,255,.5);
        margin-bottom: 10px; display: flex; align-items: center; gap: 6px;
    }
    .sf-kpi-amount {
        font-family: Georgia, 'Times New Roman', serif;
        font-size: 2rem; font-weight: 700; line-height: 1;
        letter-spacing: -.02em; color: #fff;
        display: flex; align-items: baseline; gap: 6px; flex-wrap: wrap;
    }
    .sf-kpi-amount.paid   { color: #34d399; }
    .sf-kpi-amount.due    { color: #fbbf24; }
    .sf-kpi-amount.zero   { color: #34d399; }
    .sf-kpi-amount.neutral { color: #e2e8f0; }
    .sf-kpi-currency {
        font-family: system-ui, sans-serif;
        font-size: .75rem; font-weight: 600; letter-spacing: .05em;
        opacity: .7; align-self: flex-end; margin-bottom: 3px;
    }
    .sf-kpi-sub {
        font-size: .75rem; color: rgba(255,255,255,.4);
        margin-top: 8px; display: flex; align-items: center; gap: 5px;
    }
    .sf-kpi-sub-danger { color: #f87171 !important; opacity: .9; }

    /* Progress bar */
    .sf-progress-wrap { position: relative; }
    .sf-progress-header {
        display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;
    }
    .sf-progress-lbl {
        font-size: .72rem; font-weight: 600; text-transform: uppercase;
        letter-spacing: .07em; color: rgba(255,255,255,.45);
    }
    .sf-progress-pct {
        font-size: .9rem; font-weight: 800; font-family: Georgia, serif;
    }
    .sf-progress-track {
        height: 6px; background: rgba(255,255,255,.1);
        border-radius: 3px; overflow: hidden; position: relative; margin-bottom: 10px;
    }
    .sf-progress-segment {
        position: absolute; top: 0; left: 0;
        height: 100%; border-radius: 3px;
        transition: width .8s cubic-bezier(.4,0,.2,1);
    }
    .sf-progress-segment.valide  { background: #34d399; }
    .sf-progress-segment.attente { background: rgba(251,191,36,.45); }
    .sf-progress-legend { display: flex; gap: 16px; flex-wrap: wrap; }
    .sf-progress-legend span {
        font-size: .72rem; color: rgba(255,255,255,.45);
        display: inline-flex; align-items: center; gap: 5px;
    }
    .sf-legend-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .sf-legend-dot.valide   { background: #34d399; }
    .sf-legend-dot.attente  { background: #fbbf24; }
    .sf-legend-dot.reliquat { background: #f87171; }

    /* ── Card sections (same as main-card pattern) ── */
    .sf-card {
        background: #fff; border: 1px solid var(--k-border, #e5e7eb);
        border-radius: var(--k-radius-lg, 14px);
        box-shadow: 0 1px 4px rgba(0,0,0,.05);
        margin-bottom: 16px; overflow: hidden;
    }
    .sf-card-head {
        padding: 14px 20px 12px; border-bottom: 1px solid var(--k-border, #e5e7eb);
        display: flex; align-items: baseline; gap: 10px;
    }
    .sf-card-title {
        font-size: .78rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
        color: var(--k-blue, #0453cb); display: flex; align-items: center; gap: 6px;
    }
    .sf-card-sub { font-size: .74rem; color: var(--k-muted, #64748b); }
    .sf-card-body { padding: 0; }

    /* Student info inside card */
    .sf-student-grid {
        display: grid; grid-template-columns: 130px 1fr 1fr; gap: 24px;
        padding: 20px 24px; align-items: start;
    }
    @media (max-width:768px) { .sf-student-grid { grid-template-columns:1fr; } }
    .sf-photo-wrap { text-align: center; }
    .sf-photo-img {
        width: 100px; height: 100px; border-radius: 50%; object-fit: cover;
        border: 3px solid var(--k-blue, #0453cb); display: block; margin: 0 auto 8px;
    }
    .sf-photo-empty {
        width: 100px; height: 100px; border-radius: 50%;
        background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
        border: 3px solid var(--k-blue, #0453cb);
        display: flex; align-items: center; justify-content: center; margin: 0 auto 8px;
        overflow: hidden;
    }
    .sf-photo-empty svg { width: 54px; height: 54px; opacity: .5; }
    .sf-student-name { font-size: .88rem; font-weight: 700; color: var(--k-text, #1e293b); text-align: center; }
    .sf-student-mat { font-size: .72rem; color: var(--k-muted, #64748b); text-align: center; margin-top: 2px; font-family: 'Courier New', monospace; }

    .sf-info-group h6 {
        font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em;
        color: var(--k-blue, #0453cb); margin-bottom: 10px; display: flex; align-items: center; gap: 6px;
    }
    .sf-info-row { display: flex; font-size: .82rem; margin-bottom: 5px; }
    .sf-info-lbl { font-weight: 600; color: var(--k-text, #1e293b); min-width: 130px; flex-shrink: 0; }
    .sf-info-val { color: var(--k-muted, #64748b); }

    /* ── Table (same as fin-table) ── */
    .sf-table { width: 100%; border-collapse: collapse; font-size: .84rem; }
    .sf-table th {
        padding: 10px 14px; text-align: left;
        font-size: .7rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .05em; color: var(--k-muted, #64748b);
        background: var(--k-surface, #f8fafc);
        border-bottom: 2px solid var(--k-border, #e5e7eb);
    }
    .sf-table td {
        padding: 12px 14px; border-bottom: 1px solid var(--k-border, #e5e7eb);
        vertical-align: middle; color: var(--k-text, #1e293b); font-size: .84rem;
    }
    .sf-table tbody tr:last-child td { border-bottom: none; }
    .sf-table tbody tr:hover td { background: rgba(4,83,203,.03); }
    .sf-table tbody tr.row-rq td { background: #fffbeb; }
    .sf-table tbody tr.row-rq:hover td { background: #fef3c7; }
    .sf-table tfoot td {
        padding: 10px 14px; font-weight: 700; font-size: .86rem;
        border-top: 2px solid var(--k-blue, #0453cb);
        background: rgba(4,83,203,.03);
    }

    /* Badges */
    .sf-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 9px; border-radius: 20px; font-size: .7rem; font-weight: 700; white-space: nowrap;
    }
    .b-danger   { background: #fee2e2; color: #991b1b; }
    .b-info     { background: #dbeafe; color: #1d4ed8; }
    .b-success  { background: #d1fae5; color: #065f46; }
    .b-warning  { background: #fef3c7; color: #92400e; }
    .b-partial  { background: #fce7f3; color: #9d174d; }
    .b-reliquat { background: #fef3c7; color: #d97706; }

    /* Payment row cards (same as fin-pmt-row) */
    .sf-pmt-row {
        display: flex; align-items: stretch;
        border-bottom: 1px solid var(--k-border, #e5e7eb);
        transition: background .15s;
    }
    .sf-pmt-row:last-child { border-bottom: none; }
    .sf-pmt-row:hover { background: rgba(4,83,203,.02); }
    .sf-pmt-bar {
        width: 3px; flex-shrink: 0;
        background: var(--pmt-clr, var(--k-border, #e5e7eb));
        border-radius: 2px 0 0 2px; margin: 10px 0;
    }
    .sf-pmt-content {
        flex: 1; padding: 12px 16px; display: flex;
        align-items: center; justify-content: space-between;
        gap: 12px; flex-wrap: wrap;
    }
    .sf-pmt-left { flex: 1; min-width: 200px; }
    .sf-pmt-cat { font-weight: 600; font-size: .86rem; color: var(--k-text, #1e293b); margin-bottom: 3px; }
    .sf-pmt-meta { font-size: .74rem; color: var(--k-muted, #64748b); display: flex; gap: 12px; flex-wrap: wrap; }
    .sf-pmt-meta span { display: inline-flex; align-items: center; gap: 4px; }
    .sf-pmt-right { text-align: right; flex-shrink: 0; }
    .sf-pmt-amount { font-family: Georgia, serif; font-size: 1.05rem; font-weight: 700; color: #10b981; }
    .sf-pmt-ref { font-size: .7rem; color: var(--k-muted, #64748b); font-family: 'Courier New', monospace; margin-top: 3px; }

    /* Footer */
    .sf-footer {
        text-align: center; padding: 14px 20px; color: var(--k-muted, #64748b); font-size: .74rem;
    }
    .sf-footer-alert {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 16px; border-radius: 8px; font-size: .78rem; font-weight: 700; margin-top: 8px;
    }
    .sf-footer-danger { background: #fee2e2; color: #991b1b; }
    .sf-footer-ok     { background: #d1fae5; color: #065f46; }

    @media print {
        .sf-toolbar, .no-print { display: none !important; }
        .sf-card { box-shadow: none; }
        .main-content { padding: 0 !important; }
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
        <a href="{{ route('esbtp.inscriptions.situation-financiere.pdf-preview', $inscription->id) }}" class="btn-acasi info" target="_blank" title="Aperçu PDF dans un nouvel onglet">
            <i class="fas fa-eye"></i>Aperçu PDF
        </a>
        <a href="{{ route('esbtp.inscriptions.situation-financiere.pdf', $inscription->id) }}" class="btn-acasi danger">
            <i class="fas fa-file-pdf"></i>Télécharger PDF
        </a>
        <a href="{{ route('esbtp.inscriptions.situation-financiere.pdf-preview', $inscription->id) }}" target="_blank" rel="noopener" class="btn-acasi primary">
            <i class="fas fa-print"></i>Imprimer
        </a>
        @php
            $returnUrl = request()->headers->get('referer') ?: route('esbtp.etudiants.show', $inscription->etudiant->id);
            $pu = parse_url($returnUrl); $au = parse_url(config('app.url'));
            if (isset($pu['host']) && $pu['host'] !== ($au['host'] ?? '')) {
                $returnUrl = route('esbtp.etudiants.show', $inscription->etudiant->id);
            }
        @endphp
        <a href="{{ $returnUrl }}" class="btn-acasi secondary">
            <i class="fas fa-arrow-left"></i>Retour
        </a>
    </div>
</div>

{{-- ── DARK HERO ── --}}
@php
    $sfTaux = $statistiques['total_attendu'] > 0 ? min(100, round($statistiques['total_paye'] / $statistiques['total_attendu'] * 100)) : 0;
@endphp
<div class="sf-hero">
    <div class="sf-hero-year-badge">
        <i class="fas fa-calendar-check"></i>
        Année : <strong>{{ $inscription->anneeUniversitaire->name ?? 'N/A' }}</strong>
        &middot; {{ $inscription->classe->name ?? 'N/A' }}
        &middot; {{ $inscription->etudiant->nom }} {{ $inscription->etudiant->prenoms }}
    </div>

    <div class="sf-hero-grid">
        {{-- KPI 1 --}}
        <div class="sf-kpi-block">
            <div class="sf-kpi-label"><i class="fas fa-file-invoice-dollar"></i>Total attendu</div>
            <div class="sf-kpi-amount neutral">
                {{ number_format($statistiques['total_attendu'], 0, ',', ' ') }}
                <span class="sf-kpi-currency">FCFA</span>
            </div>
            <div class="sf-kpi-sub"><i class="fas fa-layer-group"></i> Frais année + reliquats</div>
        </div>
        <div class="sf-sep"></div>
        {{-- KPI 2 --}}
        <div class="sf-kpi-block">
            <div class="sf-kpi-label"><i class="fas fa-check-circle"></i>Total payé</div>
            <div class="sf-kpi-amount paid">
                {{ number_format($statistiques['total_paye'], 0, ',', ' ') }}
                <span class="sf-kpi-currency">FCFA</span>
            </div>
            <div class="sf-kpi-sub">Paiements validés</div>
        </div>
        <div class="sf-sep"></div>
        {{-- KPI 3 --}}
        <div class="sf-kpi-block">
            <div class="sf-kpi-label"><i class="fas fa-balance-scale"></i>Solde restant</div>
            <div class="sf-kpi-amount {{ $statistiques['solde_restant'] > 0 ? 'due' : 'zero' }}">
                {{ number_format($statistiques['solde_restant'], 0, ',', ' ') }}
                <span class="sf-kpi-currency">FCFA</span>
            </div>
            <div class="sf-kpi-sub {{ $statistiques['solde_restant'] > 0 ? 'sf-kpi-sub-danger' : '' }}">
                @if($statistiques['solde_restant'] > 0)
                    <i class="fas fa-exclamation-circle"></i> Reste à régler
                @else
                    <i class="fas fa-check"></i> Situation apurée
                @endif
            </div>
        </div>
    </div>

    {{-- Progress bar --}}
    <div class="sf-progress-wrap">
        <div class="sf-progress-header">
            <span class="sf-progress-lbl">Progression du paiement</span>
            <span class="sf-progress-pct" style="color:{{ $sfTaux >= 100 ? '#34d399' : ($sfTaux >= 50 ? '#fbbf24' : '#f87171') }}">{{ $sfTaux }}%</span>
        </div>
        <div class="sf-progress-track">
            <div class="sf-progress-segment valide" style="width:{{ $sfTaux }}%"></div>
        </div>
        <div class="sf-progress-legend">
            <span><span class="sf-legend-dot valide"></span>Validé ({{ number_format($statistiques['total_paye'], 0, ',', ' ') }} FCFA)</span>
            @if($statistiques['total_reliquats'] > 0)
            <span><span class="sf-legend-dot reliquat"></span>Reliquats ({{ number_format($statistiques['total_reliquats'], 0, ',', ' ') }} FCFA)</span>
            @endif
        </div>
    </div>
</div>

{{-- ── Student Info Card ── --}}
<div class="sf-card">
    <div class="sf-card-head">
        <div class="sf-card-title"><i class="fas fa-user-circle"></i>Informations de l'étudiant</div>
    </div>
    <div class="sf-student-grid">
        <div class="sf-photo-wrap">
            @php
                $sfHasPhoto = false;
                if ($inscription->etudiant->photo) {
                    foreach ([
                        storage_path('app/public/photos/etudiants/' . $inscription->etudiant->photo),
                        storage_path('app/public/' . $inscription->etudiant->photo),
                    ] as $sfPhotoCheck) {
                        if (file_exists($sfPhotoCheck)) { $sfHasPhoto = true; break; }
                    }
                }
            @endphp
            @if($sfHasPhoto)
                <img src="{{ $inscription->etudiant->photo_url }}" alt="Photo" class="sf-photo-img">
            @else
                <div class="sf-photo-empty">
                    <svg viewBox="0 0 24 24" fill="currentColor" style="color:#94a3b8;">
                        <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                    </svg>
                </div>
            @endif
            <div class="sf-student-name">{{ $inscription->etudiant->nom }} {{ $inscription->etudiant->prenoms }}</div>
            <div class="sf-student-mat">{{ $inscription->etudiant->matricule }}</div>
        </div>
        <div class="sf-info-group">
            <h6><i class="fas fa-info-circle"></i>Informations personnelles</h6>
            <div class="sf-info-row"><span class="sf-info-lbl">Genre</span><span class="sf-info-val">{{ $inscription->etudiant->genre == 'M' ? 'Masculin' : 'Féminin' }}</span></div>
            <div class="sf-info-row"><span class="sf-info-lbl">Date de naissance</span><span class="sf-info-val">{{ $inscription->etudiant->date_naissance ? \Carbon\Carbon::parse($inscription->etudiant->date_naissance)->format('d/m/Y') : 'Non renseigné' }}</span></div>
            <div class="sf-info-row"><span class="sf-info-lbl">Lieu de naissance</span><span class="sf-info-val">{{ $inscription->etudiant->lieu_naissance ?? 'Non renseigné' }}</span></div>
            <div class="sf-info-row"><span class="sf-info-lbl">Téléphone</span><span class="sf-info-val">{{ $inscription->etudiant->telephone ?? 'Non renseigné' }}</span></div>
        </div>
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
            <div style="border-top:1px solid var(--k-border,#e5e7eb);margin-top:10px;padding-top:10px;">
                <div class="sf-info-row"><span class="sf-info-lbl" style="color:var(--k-blue,#0453cb);font-weight:700;"><i class="fas fa-users me-1"></i>Parent / Tuteur</span></div>
                <div class="sf-info-row"><span class="sf-info-lbl">Nom</span><span class="sf-info-val">{{ ($parent->nom ?? '') . ' ' . ($parent->prenoms ?? '') }}</span></div>
                <div class="sf-info-row"><span class="sf-info-lbl">Téléphone</span><span class="sf-info-val">{{ $parent->telephone ?? 'Non renseigné' }}</span></div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ── Fees Detail ── --}}
<div class="sf-card">
    <div class="sf-card-head">
        <div class="sf-card-title"><i class="fas fa-money-bill-wave"></i>Détail des Frais — {{ $inscription->anneeUniversitaire->name }}</div>
        <span class="sf-card-sub">{{ $fraisSouscrits->count() }} catégorie(s) souscrite(s)</span>
    </div>
    <div class="sf-card-body">
        @if($fraisSouscrits->count() > 0)
        @php $totalAttenduFrais = 0; $totalPayeFrais = 0; @endphp
        <div class="table-responsive">
            <table class="sf-table">
                <thead>
                    <tr>
                        <th>Catégorie de Frais</th>
                        <th class="text-center" width="100">Type</th>
                        <th class="text-end" width="140">Attendu</th>
                        <th class="text-end" width="120">Payé</th>
                        <th class="text-end" width="120">Solde</th>
                        <th class="text-center" width="90">Statut</th>
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
                        $totalAttenduFrais += $frais->amount;
                        $totalPayeFrais    += $mp;
                    @endphp
                    <tr>
                        <td><strong>{{ $frais->fraisCategory->name ?? '—' }}</strong></td>
                        <td class="text-center">
                            @if($frais->fraisCategory->is_mandatory)
                                <span class="sf-badge b-danger"><i class="fas fa-exclamation-circle"></i>Obligatoire</span>
                            @else
                                <span class="sf-badge b-info"><i class="fas fa-star"></i>Optionnel</span>
                            @endif
                        </td>
                        <td class="text-end"><strong>{{ number_format($frais->amount, 0, ',', ' ') }}</strong></td>
                        <td class="text-end" style="color:#10b981;font-weight:600;">{{ number_format($mp, 0, ',', ' ') }}</td>
                        <td class="text-end"><strong style="color:{{ $sl > 0 ? '#ef4444' : '#10b981' }};">{{ number_format($sl, 0, ',', ' ') }}</strong></td>
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
                        @php $totalAttenduFrais += $rq->montant_reliquat; $totalPayeFrais += $rq->montant_regle; @endphp
                        <tr class="row-rq">
                            <td>
                                <strong>{{ $rq->fraisSubscription->fraisCategory->name ?? '—' }}</strong>
                                <br><small style="color:var(--k-muted,#64748b);">Reliquat {{ $rq->inscriptionSource->anneeUniversitaire->name ?? 'N/A' }}</small>
                            </td>
                            <td class="text-center"><span class="sf-badge b-reliquat"><i class="fas fa-history"></i>Reliquat</span></td>
                            <td class="text-end"><strong>{{ number_format($rq->montant_reliquat, 0, ',', ' ') }}</strong></td>
                            <td class="text-end" style="color:#10b981;font-weight:600;">{{ number_format($rq->montant_regle, 0, ',', ' ') }}</td>
                            <td class="text-end" style="color:#d97706;font-weight:600;">{{ number_format($rq->solde_restant, 0, ',', ' ') }}</td>
                            <td class="text-center"><span class="sf-badge b-reliquat">Reliquat</span></td>
                        </tr>
                        @endif
                        @endforeach
                    @endif
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-end">TOTAL (FCFA)</td>
                        <td class="text-end">{{ number_format($totalAttenduFrais, 0, ',', ' ') }}</td>
                        <td class="text-end" style="color:#10b981;">{{ number_format($totalPayeFrais, 0, ',', ' ') }}</td>
                        <td class="text-end" style="color:{{ ($totalAttenduFrais - $totalPayeFrais) > 0 ? '#ef4444' : '#10b981' }};">{{ number_format($totalAttenduFrais - $totalPayeFrais, 0, ',', ' ') }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @else
        <div class="text-center py-5" style="color:var(--k-muted,#64748b);">
            <i class="fas fa-exclamation-triangle fa-2x mb-3" style="color:#f59e0b;"></i>
            <div style="font-weight:600;margin-bottom:4px;">Aucun frais souscrit</div>
        </div>
        @endif
    </div>
</div>

{{-- ── Payment History (card rows like etudiants.show) ── --}}
<div class="sf-card">
    <div class="sf-card-head">
        <div class="sf-card-title"><i class="fas fa-receipt"></i>Historique des Paiements</div>
        <span class="sf-card-sub">{{ $inscription->paiements->where('status', 'validé')->count() }} paiement(s) validé(s)</span>
    </div>
    <div class="sf-card-body">
        @if($inscription->paiements->where('status', 'validé')->count() > 0)
        <div>
            @foreach($inscription->paiements->where('status', 'validé')->sortByDesc('date_paiement') as $p)
            @php $isReliquat = $p->type_paiement === 'reliquat'; @endphp
            <div class="sf-pmt-row">
                <div class="sf-pmt-bar" style="--pmt-clr:{{ $isReliquat ? '#d97706' : '#10b981' }};"></div>
                <div class="sf-pmt-content">
                    <div class="sf-pmt-left">
                        <div class="sf-pmt-cat">
                            {{ $p->fraisCategory->name ?? '—' }}
                            @if($isReliquat) <span class="sf-badge b-reliquat" style="margin-left:6px;">Reliquat</span> @endif
                        </div>
                        <div class="sf-pmt-meta">
                            <span><i class="fas fa-calendar-alt"></i>{{ $p->date_paiement ? \Carbon\Carbon::parse($p->date_paiement)->format('d/m/Y') : '—' }}</span>
                            <span><i class="fas fa-credit-card"></i>{{ $p->mode_paiement ?? '—' }}</span>
                            <span class="sf-badge b-success">{{ strtoupper($p->status) }}</span>
                        </div>
                    </div>
                    <div class="sf-pmt-right">
                        <div class="sf-pmt-amount">{{ number_format($p->montant, 0, ',', ' ') }} FCFA</div>
                        <div class="sf-pmt-ref">{{ $p->numero_recu ?? '—' }}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-5" style="color:var(--k-muted,#64748b);">
            <i class="fas fa-receipt fa-2x mb-3"></i>
            <div style="font-weight:600;">Aucun paiement validé</div>
        </div>
        @endif
    </div>
</div>

<div class="sf-footer">
    <i class="fas fa-info-circle me-1"></i>
    Document généré le {{ now()->format('d/m/Y à H:i') }}
    <br>
    @if($statistiques['solde_restant'] > 0)
        <span class="sf-footer-alert sf-footer-danger"><i class="fas fa-exclamation-triangle"></i> Solde restant : {{ number_format($statistiques['solde_restant'], 0, ',', ' ') }} FCFA</span>
    @else
        <span class="sf-footer-alert sf-footer-ok"><i class="fas fa-check-circle"></i> Situation financière à jour</span>
    @endif
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
