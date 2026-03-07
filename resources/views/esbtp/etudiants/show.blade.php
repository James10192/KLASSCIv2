@extends('layouts.app')

@section('title', $etudiant->nom . ' ' . $etudiant->prenoms . ' — Fiche étudiant — KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* ======================================================
   FICHE ÉTUDIANT — DESIGN SYSTEM KLASSCI
====================================================== */

:root {
    --klassci-blue: #0453cb;
    --klassci-blue-light: #5e91de;
    --klassci-gradient: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
    --surface: #f8fafc;
    --card-radius: 14px;
    --tab-height: 52px;
}

.fiche-etudiant { background: var(--surface); min-height: 100vh; }

/* ---- Hero ---- */
.fiche-hero {
    background: var(--klassci-gradient);
    color: #fff;
    padding: 28px 32px 0;
    border-radius: 0 0 24px 24px;
}
.fiche-hero .hero-content { display: flex; align-items: flex-end; gap: 24px; flex-wrap: wrap; }
.fiche-hero .hero-avatar {
    width: 96px; height: 96px; border-radius: 50%;
    border: 4px solid rgba(255,255,255,0.6);
    object-fit: cover; flex-shrink: 0;
    background: rgba(255,255,255,0.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 2.8rem; color: rgba(255,255,255,0.8);
    overflow: hidden;
}
.fiche-hero .hero-avatar img { width: 100%; height: 100%; object-fit: cover; }
.fiche-hero .hero-info { flex: 1; min-width: 200px; padding-bottom: 20px; }
.fiche-hero .hero-name { font-size: 1.65rem; font-weight: 700; margin: 0 0 4px; }
.fiche-hero .hero-sub { font-size: 0.9rem; opacity: 0.85; margin: 0 0 10px; }
.fiche-hero .hero-badges { display: flex; gap: 8px; flex-wrap: wrap; }
.fiche-hero .hero-badge {
    font-size: 0.75rem; padding: 3px 10px; border-radius: 20px;
    background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.35);
}
.fiche-hero .hero-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: flex-start; padding-bottom: 20px; margin-left: auto; }
.hero-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: 8px; font-size: 0.82rem; font-weight: 600;
    text-decoration: none; border: none; cursor: pointer; transition: all 0.2s;
}
.hero-btn-white { background: #fff; color: var(--klassci-blue); }
.hero-btn-white:hover { background: #f0f4ff; color: var(--klassci-blue); }
.hero-btn-ghost { background: rgba(255,255,255,0.15); color: #fff; border: 1px solid rgba(255,255,255,0.4); }
.hero-btn-ghost:hover { background: rgba(255,255,255,0.25); color: #fff; }
.hero-btn-danger { background: rgba(220,53,69,0.85); color: #fff; }
.hero-btn-danger:hover { background: #dc3545; color:#fff; }

/* ---- KPI strip ---- */
.kpi-strip {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 12px; margin: 20px 0;
}
.kpi-card {
    background: #fff; border-radius: var(--card-radius);
    padding: 16px 18px; box-shadow: 0 1px 6px rgba(0,0,0,0.06);
    border-left: 4px solid transparent; display: flex; align-items: center; gap: 14px;
}
.kpi-card.kpi-blue  { border-color: var(--klassci-blue); }
.kpi-card.kpi-green { border-color: #10b981; }
.kpi-card.kpi-amber { border-color: #f59e0b; }
.kpi-card.kpi-red   { border-color: #ef4444; }
.kpi-card .kpi-icon { font-size: 1.6rem; opacity: 0.75; }
.kpi-card .kpi-label { font-size: 0.72rem; color: #64748b; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 2px; }
.kpi-card .kpi-value { font-size: 1.25rem; font-weight: 700; color: #1e293b; line-height: 1.1; }
.kpi-card .kpi-sub   { font-size: 0.72rem; color: #94a3b8; margin-top: 2px; }

/* ---- Tabs ---- */
.fiche-tabs-wrapper { background: #fff; position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin: 0 -32px; }
.fiche-tabs { display: flex; overflow-x: auto; scrollbar-width: none; border-bottom: 2px solid #e2e8f0; }
.fiche-tabs::-webkit-scrollbar { display: none; }
.fiche-tab {
    flex-shrink: 0; padding: 0 22px; height: var(--tab-height); display: flex; align-items: center; gap: 8px;
    font-size: 0.875rem; font-weight: 600; color: #64748b; cursor: pointer;
    border-bottom: 3px solid transparent; margin-bottom: -2px; transition: all 0.2s; white-space: nowrap;
    background: none; border-top: none; border-left: none; border-right: none;
}
.fiche-tab:hover { color: var(--klassci-blue); }
.fiche-tab.active { color: var(--klassci-blue); border-bottom-color: var(--klassci-blue); }
.fiche-tab .tab-badge {
    min-width: 18px; height: 18px; border-radius: 9px; background: var(--klassci-blue);
    color: #fff; font-size: 0.68rem; padding: 0 5px; display: flex; align-items: center; justify-content: center;
}

/* ---- Panes ---- */
.fiche-pane { display: none; }
.fiche-pane.active { display: block; }

/* ---- Section card ---- */
.section-card {
    background: #fff; border-radius: var(--card-radius);
    box-shadow: 0 1px 6px rgba(0,0,0,0.06); margin-bottom: 20px; overflow: hidden;
}
.section-card-header {
    padding: 16px 24px; border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; justify-content: space-between;
}
.section-card-title { font-size: 0.95rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px; }
.section-card-title i { color: var(--klassci-blue); }
.section-card-body { padding: 20px 24px; }

/* ---- Semestres ---- */
.semestre-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media (max-width: 640px) { .semestre-grid { grid-template-columns: 1fr; } }
.semestre-card { border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
.semestre-card-header {
    padding: 12px 16px; background: var(--klassci-gradient); color: #fff;
    display: flex; justify-content: space-between; align-items: center;
}
.semestre-title { font-weight: 700; font-size: 0.88rem; }
.semestre-moy { font-size: 1.5rem; font-weight: 800; letter-spacing: -0.02em; }
.semestre-moy-label { font-size: 0.7rem; opacity: 0.85; }
.semestre-body { padding: 14px 16px; }
.semestre-meta { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 12px; }
.semestre-meta-item { font-size: 0.78rem; color: #64748b; display: flex; align-items: center; gap: 4px; }

/* Mention badges */
.badge-mention { font-size: 0.72rem; padding: 3px 9px; border-radius: 6px; font-weight: 600; }
.badge-tres-bien  { background: #d1fae5; color: #065f46; }
.badge-bien       { background: #dbeafe; color: #1e40af; }
.badge-assez-bien { background: #e0e7ff; color: #3730a3; }
.badge-passable   { background: #fef3c7; color: #92400e; }
.badge-insuffisant{ background: #fee2e2; color: #991b1b; }
.badge-na         { background: #f1f5f9; color: #64748b; }

/* Notes table */
.notes-table { width: 100%; font-size: 0.8rem; border-collapse: collapse; }
.notes-table th { padding: 5px 8px; background: #f8fafc; font-weight: 600; color: #475569; border-bottom: 1px solid #e2e8f0; text-align: left; }
.notes-table td { padding: 5px 8px; border-bottom: 1px solid #f1f5f9; color: #334155; }
.notes-table tr:last-child td { border-bottom: none; }

/* ---- Présences ---- */
.presence-annee { display: grid; grid-template-columns: 200px 1fr; gap: 20px; align-items: center; padding: 16px 0; border-bottom: 1px solid #f1f5f9; }
.presence-annee:last-child { border-bottom: none; }
.presence-label { font-size: 0.85rem; font-weight: 600; color: #1e293b; }
.presence-year-sub { font-size: 0.75rem; color: #94a3b8; }
.presence-stats { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 8px; }
.presence-stat { font-size: 0.78rem; color: #64748b; }
.progress-track { height: 10px; background: #f1f5f9; border-radius: 5px; overflow: hidden; }
.progress-fill { height: 100%; border-radius: 5px; transition: width 0.6s ease; }
@media (max-width: 640px) { .presence-annee { grid-template-columns: 1fr; } }

/* ---- Paiements ---- */
.paiement-row { display: grid; grid-template-columns: auto 1fr auto auto; gap: 12px 20px; align-items: center; padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
.paiement-row:last-child { border-bottom: none; }
.paiement-icon { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; flex-shrink: 0; }
.paiement-icon.valide  { background: #d1fae5; color: #065f46; }
.paiement-icon.attente { background: #fef3c7; color: #92400e; }
.paiement-icon.rejete  { background: #fee2e2; color: #991b1b; }
.paiement-motif { font-size: 0.85rem; font-weight: 600; color: #1e293b; }
.paiement-ref   { font-size: 0.75rem; color: #94a3b8; }
.paiement-date  { font-size: 0.78rem; color: #64748b; white-space: nowrap; }
.paiement-montant { font-size: 0.9rem; font-weight: 700; color: var(--klassci-blue); white-space: nowrap; text-align: right; }
@media (max-width: 640px) {
    .paiement-row { grid-template-columns: auto 1fr; }
    .paiement-date, .paiement-montant { grid-column: 2; }
    .paiement-montant { text-align: left; }
}

/* ---- Infos perso ---- */
.info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 14px 24px; }
.info-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: .04em; color: #94a3b8; margin-bottom: 3px; }
.info-value { font-size: 0.9rem; font-weight: 600; color: #1e293b; }
.info-value.empty { color: #cbd5e1; font-weight: 400; font-style: italic; }

/* ---- Parents ---- */
.parent-card { border: 1px solid #e2e8f0; border-radius: 10px; margin-bottom: 10px; overflow: hidden; }
.parent-card-header { padding: 12px 16px; background: #f8fafc; cursor: pointer; display: flex; justify-content: space-between; align-items: center; user-select: none; }
.parent-card-header:hover { background: #f0f4ff; }
.parent-card-body { padding: 16px; display: none; }
.parent-card.open .parent-card-body { display: block; }
.parent-card.open .parent-chevron { transform: rotate(180deg); }
.parent-chevron { transition: transform 0.2s; color: #94a3b8; }

/* ---- Empty state ---- */
.empty-state { text-align: center; padding: 48px 20px; color: #94a3b8; }
.empty-state i { font-size: 2.5rem; margin-bottom: 12px; display: block; opacity: 0.5; }
.empty-state p { font-size: 0.9rem; margin: 0; }

/* ---- Responsive ---- */
@media (max-width: 768px) {
    .fiche-hero { padding: 20px 16px 0; }
    .fiche-hero .hero-name { font-size: 1.3rem; }
    .fiche-tabs-wrapper { margin: 0 -16px; }
    .section-card-body { padding: 14px 16px; }
    .section-card-header { padding: 12px 16px; }
}
</style>
@endsection

@section('content')
<div class="fiche-etudiant">

    {{-- ============================================================
         HERO HEADER
    ============================================================ --}}
    <div class="fiche-hero">
        <div class="hero-content">

            {{-- Avatar --}}
            <div class="hero-avatar">
                @if($etudiant->photo_url)
                    <img src="{{ $etudiant->photo_url }}" alt="Photo">
                @else
                    <i class="fas fa-user"></i>
                @endif
            </div>

            {{-- Infos --}}
            <div class="hero-info">
                <h1 class="hero-name">{{ $etudiant->nom }} {{ $etudiant->prenoms }}</h1>
                <p class="hero-sub">Matricule : <strong>{{ $etudiant->matricule }}</strong></p>
                <div class="hero-badges">
                    <span class="hero-badge">
                        <i class="fas fa-circle me-1" style="font-size:0.5rem; color:{{ $etudiant->statut === 'actif' ? '#4ade80' : '#f87171' }}"></i>
                        {{ ucfirst($etudiant->statut ?? 'Inconnu') }}
                    </span>
                    @php $inscActive = $dossier['financier']['inscription_active']; @endphp
                    @if($inscActive)
                        @if($inscActive->filiere)
                            <span class="hero-badge"><i class="fas fa-layer-group me-1"></i>{{ $inscActive->filiere->name }}</span>
                        @endif
                        @if($inscActive->niveauEtude)
                            <span class="hero-badge"><i class="fas fa-graduation-cap me-1"></i>{{ $inscActive->niveauEtude->name }}</span>
                        @endif
                        @if($inscActive->classe)
                            <span class="hero-badge"><i class="fas fa-door-open me-1"></i>{{ $inscActive->classe->name }}</span>
                        @endif
                    @endif
                    @if($etudiant->date_naissance)
                        <span class="hero-badge"><i class="fas fa-birthday-cake me-1"></i>{{ $etudiant->age }} ans</span>
                    @endif
                </div>
            </div>

            {{-- Actions --}}
            <div class="hero-actions">
                @can('edit_students')
                <a href="{{ route('esbtp.etudiants.edit', $etudiant->id) }}" class="hero-btn hero-btn-white">
                    <i class="fas fa-edit"></i><span class="d-none d-sm-inline">Modifier</span>
                </a>
                @endcan
                <div class="dropdown">
                    <button class="hero-btn hero-btn-ghost dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-file-alt"></i><span class="d-none d-md-inline ms-1">Documents</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('esbtp.etudiants.certificat.preview', $etudiant->id) }}">
                            <i class="fas fa-eye me-2"></i>Prévisualiser Certificat
                        </a></li>
                        <li><a class="dropdown-item" href="{{ route('esbtp.etudiants.certificat', $etudiant->id) }}" target="_blank">
                            <i class="fas fa-download me-2"></i>Télécharger Certificat
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('esbtp.etudiants.attestation-frequentation.preview', $etudiant->id) }}">
                            <i class="fas fa-eye me-2"></i>Prévisualiser Attestation
                        </a></li>
                        <li><a class="dropdown-item" href="{{ route('esbtp.etudiants.attestation-frequentation', $etudiant->id) }}" target="_blank">
                            <i class="fas fa-download me-2"></i>Télécharger Attestation
                        </a></li>
                    </ul>
                </div>
                <a href="{{ route('esbtp.paiements.create') }}?etudiant={{ $etudiant->id }}" class="hero-btn hero-btn-ghost">
                    <i class="fas fa-plus"></i><span class="d-none d-lg-inline ms-1">Paiement</span>
                </a>
                @can('delete_students')
                <button class="hero-btn hero-btn-danger" data-bs-toggle="modal" data-bs-target="#deleteStudentModal">
                    <i class="fas fa-trash"></i>
                </button>
                @endcan
                <a href="{{ route('esbtp.etudiants.index') }}" class="hero-btn hero-btn-ghost">
                    <i class="fas fa-arrow-left"></i>
                </a>
            </div>
        </div>

        {{-- Tabs ancrés au bas du hero --}}
        <div class="fiche-tabs-wrapper mt-3">
            <div class="fiche-tabs" id="ficheTabs">
                <button class="fiche-tab active" data-pane="pane-overview">
                    <i class="fas fa-th-large"></i> Vue d'ensemble
                </button>
                <button class="fiche-tab" data-pane="pane-academique">
                    <i class="fas fa-graduation-cap"></i> Parcours académique
                    @if(count($dossier['academique']) > 0)
                        <span class="tab-badge">{{ count($dossier['academique']) }}</span>
                    @endif
                </button>
                <button class="fiche-tab" data-pane="pane-presences">
                    <i class="fas fa-calendar-check"></i> Présences
                </button>
                <button class="fiche-tab" data-pane="pane-finances">
                    <i class="fas fa-wallet"></i> Finances
                    @if($dossier['financier']['nombre_paiements'] > 0)
                        <span class="tab-badge">{{ $dossier['financier']['nombre_paiements'] }}</span>
                    @endif
                </button>
                <button class="fiche-tab" data-pane="pane-infos">
                    <i class="fas fa-user-circle"></i> Profil
                </button>
            </div>
        </div>
    </div>{{-- /fiche-hero --}}

    {{-- ============================================================
         CONTENU
    ============================================================ --}}
    <div class="container-fluid px-3 px-md-4 py-4">

        {{-- Flash messages --}}
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            @if(session('new_password'))
                <hr class="my-1">
                <p class="mb-0 small">Nouveau mot de passe : <code>{{ session('new_password') }}</code></p>
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-times-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        {{-- ===========================================================
             TAB 1 : VUE D'ENSEMBLE
        =========================================================== --}}
        <div class="fiche-pane active" id="pane-overview">
            @php
                $fin = $dossier['financier'];
                $anneeAcadCourante = $dossier['academique'][0] ?? null;
                $moyenneCourante = null;
                if ($anneeAcadCourante) {
                    $moyS1 = $anneeAcadCourante['semestres']['semestre1']['moyenne'] ?? null;
                    $moyS2 = $anneeAcadCourante['semestres']['semestre2']['moyenne'] ?? null;
                    $vals  = array_filter([$moyS1, $moyS2], fn($v) => $v !== null);
                    $moyenneCourante = count($vals) > 0 ? round(array_sum($vals) / count($vals), 2) : null;
                }
                $tauxCourant = $dossier['presences'][0]['taux_presence'] ?? null;
            @endphp

            {{-- KPI Cards --}}
            <div class="kpi-strip">
                <div class="kpi-card kpi-blue">
                    <div class="kpi-icon"><i class="fas fa-star" style="color:var(--klassci-blue)"></i></div>
                    <div>
                        <div class="kpi-label">Moyenne générale</div>
                        <div class="kpi-value">{{ $moyenneCourante !== null ? number_format($moyenneCourante, 2) . '/20' : 'N/A' }}</div>
                        <div class="kpi-sub">Année en cours</div>
                    </div>
                </div>
                <div class="kpi-card kpi-green">
                    <div class="kpi-icon"><i class="fas fa-calendar-check" style="color:#10b981"></i></div>
                    <div>
                        <div class="kpi-label">Taux de présence</div>
                        <div class="kpi-value">{{ $tauxCourant !== null ? $tauxCourant . '%' : 'N/A' }}</div>
                        <div class="kpi-sub">Année en cours</div>
                    </div>
                </div>
                <div class="kpi-card kpi-green">
                    <div class="kpi-icon"><i class="fas fa-check-circle" style="color:#10b981"></i></div>
                    <div>
                        <div class="kpi-label">Paiements validés</div>
                        <div class="kpi-value">{{ number_format($fin['paiements_valides'], 0, ',', ' ') }}</div>
                        <div class="kpi-sub">FCFA</div>
                    </div>
                </div>
                @if($fin['paiements_en_attente'] > 0)
                <div class="kpi-card kpi-amber">
                    <div class="kpi-icon"><i class="fas fa-clock" style="color:#f59e0b"></i></div>
                    <div>
                        <div class="kpi-label">En attente</div>
                        <div class="kpi-value">{{ number_format($fin['paiements_en_attente'], 0, ',', ' ') }}</div>
                        <div class="kpi-sub">FCFA</div>
                    </div>
                </div>
                @endif
                @if($fin['total_reliquats_entrants'] > 0)
                <div class="kpi-card kpi-red">
                    <div class="kpi-icon"><i class="fas fa-exclamation-triangle" style="color:#ef4444"></i></div>
                    <div>
                        <div class="kpi-label">Reliquats à payer</div>
                        <div class="kpi-value">{{ number_format($fin['total_reliquats_entrants'], 0, ',', ' ') }}</div>
                        <div class="kpi-sub">FCFA</div>
                    </div>
                </div>
                @endif
                <div class="kpi-card kpi-blue">
                    <div class="kpi-icon"><i class="fas fa-graduation-cap" style="color:var(--klassci-blue)"></i></div>
                    <div>
                        <div class="kpi-label">Inscriptions</div>
                        <div class="kpi-value">{{ $etudiant->inscriptions->count() }}</div>
                        <div class="kpi-sub">années</div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                {{-- Résultats année courante --}}
                <div class="col-12 col-lg-7">
                    <div class="section-card">
                        <div class="section-card-header">
                            <span class="section-card-title"><i class="fas fa-chart-line"></i> Résultats de l'année en cours</span>
                            <button class="btn btn-link btn-sm p-0" style="font-size:0.78rem;" onclick="switchTab('pane-academique')">Voir tout →</button>
                        </div>
                        <div class="section-card-body">
                            @if($anneeAcadCourante)
                                @php $insc = $anneeAcadCourante['inscription']; @endphp
                                <div class="mb-3" style="font-size:0.82rem; color:#64748b;">
                                    <strong style="color:#1e293b">{{ optional($anneeAcadCourante['annee'])->name ?? 'N/A' }}</strong>
                                    @if($insc->classe) — {{ $insc->classe->name }} @endif
                                    @if($insc->filiere) · {{ $insc->filiere->name }} @endif
                                </div>
                                <div class="semestre-grid">
                                    @foreach(['semestre1' => 'Semestre 1', 'semestre2' => 'Semestre 2'] as $semKey => $semLabel)
                                        @php $sem = $anneeAcadCourante['semestres'][$semKey]; @endphp
                                        <div class="semestre-card">
                                            <div class="semestre-card-header">
                                                <span class="semestre-title">{{ $semLabel }}</span>
                                                <div class="text-end">
                                                    <div class="semestre-moy">{{ $sem['moyenne'] !== null ? number_format($sem['moyenne'], 2) : '—' }}</div>
                                                    <div class="semestre-moy-label">/20</div>
                                                </div>
                                            </div>
                                            <div class="semestre-body">
                                                <div class="semestre-meta">
                                                    @if($sem['mention'])
                                                        @php
                                                            $bc = match(true) {
                                                                str_contains($sem['mention'], 'Tres')       => 'badge-tres-bien',
                                                                str_contains($sem['mention'], 'Bien') && !str_contains($sem['mention'], 'Assez') => 'badge-bien',
                                                                str_contains($sem['mention'], 'Assez')      => 'badge-assez-bien',
                                                                str_contains($sem['mention'], 'Passable')   => 'badge-passable',
                                                                str_contains($sem['mention'], 'Insuffisant')=> 'badge-insuffisant',
                                                                default => 'badge-na',
                                                            };
                                                        @endphp
                                                        <span class="badge-mention {{ $bc }}">{{ $sem['mention'] }}</span>
                                                    @endif
                                                    @if($sem['rang'])
                                                        <span class="semestre-meta-item">
                                                            <i class="fas fa-trophy text-warning"></i>
                                                            {{ $sem['rang'] }}{{ $sem['rang'] == 1 ? 'er' : 'ème' }}
                                                            @if($sem['bulletin'] && $sem['bulletin']->effectif_classe)
                                                                / {{ $sem['bulletin']->effectif_classe }}
                                                            @endif
                                                        </span>
                                                    @endif
                                                    @if($sem['bulletin'])
                                                        <a href="{{ route('esbtp.bulletins.show', $sem['bulletin']->id) }}" class="semestre-meta-item" style="color:var(--klassci-blue); text-decoration:none;">
                                                            <i class="fas fa-file-pdf"></i> Bulletin
                                                        </a>
                                                    @endif
                                                </div>
                                                @if($sem['notes']->isNotEmpty())
                                                    <table class="notes-table">
                                                        <thead><tr><th>Matière</th><th class="text-end">Moy.</th><th class="text-end">Coef.</th></tr></thead>
                                                        <tbody>
                                                            @foreach($sem['notes']->take(5) as $noteItem)
                                                            <tr>
                                                                <td>{{ optional($noteItem['matiere'])->name ?? 'N/A' }}</td>
                                                                <td class="text-end" style="font-weight:700; color:{{ ($noteItem['moyenne'] ?? 0) >= 10 ? 'var(--klassci-blue)' : '#ef4444' }}">{{ number_format($noteItem['moyenne'] ?? 0, 2) }}</td>
                                                                <td class="text-end" style="color:#94a3b8;">{{ $noteItem['coefficient'] }}</td>
                                                            </tr>
                                                            @endforeach
                                                            @if($sem['notes']->count() > 5)
                                                            <tr><td colspan="3" class="text-center" style="font-size:0.75rem; color:#94a3b8; padding-top:4px;">
                                                                +{{ $sem['notes']->count() - 5 }} matière(s) —
                                                                <a href="#" onclick="switchTab('pane-academique'); return false;" style="color:var(--klassci-blue)">voir tout</a>
                                                            </td></tr>
                                                            @endif
                                                        </tbody>
                                                    </table>
                                                @elseif($sem['moyenne'] === null)
                                                    <p style="font-size:0.8rem; color:#94a3b8; margin:0;">Aucune note saisie</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="empty-state">
                                    <i class="fas fa-chart-line"></i>
                                    <p>Aucun résultat académique disponible.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Inscription active + derniers paiements --}}
                <div class="col-12 col-lg-5">
                    @if($fin['inscription_active'])
                    <div class="section-card mb-4">
                        <div class="section-card-header">
                            <span class="section-card-title"><i class="fas fa-id-card"></i> Inscription active</span>
                            <a href="{{ route('esbtp.inscriptions.show', $fin['inscription_active']->id) }}" style="font-size:0.78rem; color:var(--klassci-blue);">Détails →</a>
                        </div>
                        <div class="section-card-body">
                            <div class="info-grid" style="grid-template-columns: 1fr 1fr;">
                                @php $ia = $fin['inscription_active']; @endphp
                                <div class="info-item">
                                    <div class="info-label">Filière</div>
                                    <div class="info-value">{{ optional($ia->filiere)->name ?? '—' }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Niveau</div>
                                    <div class="info-value">{{ optional($ia->niveauEtude)->name ?? '—' }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Classe</div>
                                    <div class="info-value">{{ optional($ia->classe)->name ?? 'Non assigné' }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Année</div>
                                    <div class="info-value">{{ optional($ia->anneeUniversitaire)->name ?? '—' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="section-card">
                        <div class="section-card-header">
                            <span class="section-card-title"><i class="fas fa-receipt"></i> Derniers paiements</span>
                            <button class="btn btn-link btn-sm p-0" style="font-size:0.78rem;" onclick="switchTab('pane-finances')">Voir tout →</button>
                        </div>
                        <div class="section-card-body" style="padding-top:8px; padding-bottom:8px;">
                            @forelse($etudiant->paiements->take(5) as $paiement)
                                @php $st = $paiement->status ?? 'en_attente'; @endphp
                                <div class="paiement-row">
                                    <div class="paiement-icon {{ $st === 'validé' ? 'valide' : ($st === 'rejeté' ? 'rejete' : 'attente') }}">
                                        <i class="fas fa-{{ $st === 'validé' ? 'check' : ($st === 'rejeté' ? 'times' : 'clock') }}"></i>
                                    </div>
                                    <div>
                                        <div class="paiement-motif">{{ Str::limit($paiement->motif ?: 'Paiement', 30) }}</div>
                                        <div class="paiement-ref">{{ $paiement->numero_recu ?: ($paiement->reference_paiement ?: 'N/A') }}</div>
                                    </div>
                                    <div class="paiement-date">{{ optional($paiement->date_paiement)->format('d/m/Y') ?? 'N/A' }}</div>
                                    <div class="paiement-montant">{{ number_format($paiement->montant, 0, ',', ' ') }} F</div>
                                </div>
                            @empty
                                <div class="empty-state" style="padding:24px;"><p>Aucun paiement.</p></div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===========================================================
             TAB 2 : PARCOURS ACADÉMIQUE
        =========================================================== --}}
        <div class="fiche-pane" id="pane-academique">
            @if(count($dossier['academique']) === 0)
                <div class="section-card">
                    <div class="section-card-body">
                        <div class="empty-state">
                            <i class="fas fa-graduation-cap"></i>
                            <p>Aucun historique académique disponible.</p>
                        </div>
                    </div>
                </div>
            @else
                @foreach($dossier['academique'] as $anneeData)
                <div class="section-card">
                    <div class="section-card-header">
                        <div>
                            <span class="section-card-title">
                                <i class="fas fa-calendar-alt"></i>
                                {{ optional($anneeData['annee'])->name ?? 'Année N/A' }}
                            </span>
                            @php $insc = $anneeData['inscription']; @endphp
                            <div style="font-size:0.78rem; color:#64748b; margin-top:3px;">
                                @if($insc->filiere) {{ $insc->filiere->name }} @endif
                                @if($insc->niveauEtude) · {{ $insc->niveauEtude->name }} @endif
                                @if($insc->classe) · <strong>{{ $insc->classe->name }}</strong> @endif
                            </div>
                        </div>
                        <div style="display:flex; gap:8px; align-items:center;">
                            @php
                                $statusMap = ['active' => ['bg-success', 'Active'], 'pending' => ['bg-warning', 'En attente'], 'en_attente' => ['bg-warning', 'En attente'], 'annulée' => ['bg-danger', 'Annulée']];
                                [$badgeCl, $badgeLbl] = $statusMap[$insc->status] ?? ['bg-secondary', $insc->status];
                            @endphp
                            <span class="badge {{ $badgeCl }}">{{ $badgeLbl }}</span>
                            <a href="{{ route('esbtp.inscriptions.show', $insc->id) }}" class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size:0.78rem;">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </div>
                    <div class="section-card-body">
                        <div class="semestre-grid">
                            @foreach(['semestre1' => 'Semestre 1', 'semestre2' => 'Semestre 2'] as $semKey => $semLabel)
                                @php $sem = $anneeData['semestres'][$semKey]; @endphp
                                <div class="semestre-card">
                                    <div class="semestre-card-header">
                                        <span class="semestre-title">{{ $semLabel }}</span>
                                        <div class="text-end">
                                            <div class="semestre-moy">{{ $sem['moyenne'] !== null ? number_format($sem['moyenne'], 2) : '—' }}</div>
                                            <div class="semestre-moy-label">/20</div>
                                        </div>
                                    </div>
                                    <div class="semestre-body">
                                        <div class="semestre-meta">
                                            @if($sem['mention'])
                                                @php
                                                    $bc = match(true) {
                                                        str_contains($sem['mention'], 'Tres')       => 'badge-tres-bien',
                                                        str_contains($sem['mention'], 'Bien') && !str_contains($sem['mention'], 'Assez') => 'badge-bien',
                                                        str_contains($sem['mention'], 'Assez')      => 'badge-assez-bien',
                                                        str_contains($sem['mention'], 'Passable')   => 'badge-passable',
                                                        str_contains($sem['mention'], 'Insuffisant')=> 'badge-insuffisant',
                                                        default => 'badge-na',
                                                    };
                                                @endphp
                                                <span class="badge-mention {{ $bc }}">{{ $sem['mention'] }}</span>
                                            @endif
                                            @if($sem['rang'])
                                                <span class="semestre-meta-item">
                                                    <i class="fas fa-trophy text-warning"></i>
                                                    {{ $sem['rang'] }}{{ $sem['rang'] == 1 ? 'er' : 'ème' }}
                                                    @if($sem['bulletin'] && $sem['bulletin']->effectif_classe)
                                                        / {{ $sem['bulletin']->effectif_classe }}
                                                    @endif
                                                </span>
                                            @endif
                                            @if($sem['bulletin'])
                                                @if($sem['bulletin']->total_absences !== null)
                                                <span class="semestre-meta-item">
                                                    <i class="fas fa-calendar-times text-danger"></i>
                                                    {{ $sem['bulletin']->total_absences }}h abs.
                                                </span>
                                                @endif
                                                <a href="{{ route('esbtp.bulletins.show', $sem['bulletin']->id) }}" class="semestre-meta-item" style="color:var(--klassci-blue); text-decoration:none;">
                                                    <i class="fas fa-file-pdf"></i> Bulletin PDF
                                                </a>
                                            @endif
                                        </div>

                                        @if($sem['notes']->isNotEmpty())
                                            <table class="notes-table">
                                                <thead>
                                                    <tr>
                                                        <th>Matière</th>
                                                        <th class="text-end">Moy/20</th>
                                                        <th class="text-end">Coef.</th>
                                                        <th class="text-end">Pts pond.</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($sem['notes'] as $noteItem)
                                                    @php
                                                        $moy  = $noteItem['moyenne'] ?? 0;
                                                        $coef = $noteItem['coefficient'] ?? 1;
                                                    @endphp
                                                    <tr>
                                                        <td>{{ optional($noteItem['matiere'])->name ?? 'N/A' }}</td>
                                                        <td class="text-end" style="font-weight:700; color:{{ $moy >= 10 ? 'var(--klassci-blue)' : '#ef4444' }}">{{ number_format($moy, 2) }}</td>
                                                        <td class="text-end" style="color:#94a3b8;">{{ $coef }}</td>
                                                        <td class="text-end" style="color:#475569;">{{ number_format($moy * $coef, 2) }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr style="background:#f8fafc;">
                                                        <td colspan="2" style="font-size:0.75rem; color:#64748b; padding:6px 8px;"><strong>Moyenne générale</strong></td>
                                                        <td class="text-end" style="font-size:0.75rem; color:#64748b; padding:6px 8px;">{{ $sem['notes']->sum(fn($n) => $n['coefficient']) }}</td>
                                                        <td class="text-end" style="font-weight:700; color:var(--klassci-blue); padding:6px 8px;">
                                                            {{ $sem['moyenne'] !== null ? number_format($sem['moyenne'], 2) . '/20' : '—' }}
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        @elseif($sem['bulletin'])
                                            <p style="font-size:0.8rem; color:#94a3b8; margin:0;">Bulletin disponible — notes détaillées non disponibles.</p>
                                        @else
                                            <p style="font-size:0.8rem; color:#94a3b8; margin:0;">Aucune note saisie pour ce semestre.</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Récap annuel --}}
                        @php
                            $moyS1a = $anneeData['semestres']['semestre1']['moyenne'];
                            $moyS2a = $anneeData['semestres']['semestre2']['moyenne'];
                            $valsAnnee = array_filter([$moyS1a, $moyS2a], fn($v) => $v !== null);
                            $moyAnnuelle = count($valsAnnee) > 0 ? round(array_sum($valsAnnee) / count($valsAnnee), 2) : null;
                        @endphp
                        @if($moyAnnuelle !== null)
                        <div style="margin-top:16px; padding:12px 16px; background:#f0f4ff; border-radius:8px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                            <span style="font-size:0.85rem; font-weight:600; color:#1e293b;">
                                <i class="fas fa-calculator me-2" style="color:var(--klassci-blue)"></i>Moyenne annuelle
                            </span>
                            <span style="font-size:1.2rem; font-weight:800; color:var(--klassci-blue);">{{ number_format($moyAnnuelle, 2) }} / 20</span>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach

                {{-- Carte réinscription --}}
                @if($anneeCourante && !$etudiant->inscriptions->contains('annee_universitaire_id', $anneeCourante->id))
                <div class="section-card" style="border: 2px dashed var(--klassci-blue); cursor:pointer;"
                     onclick="window.location='{{ route('esbtp.reinscription.show', $etudiant->id) }}?annee_academique={{ $anneeCourante->name }}'">
                    <div class="section-card-body text-center py-4">
                        <i class="fas fa-plus-circle fa-3x mb-3" style="color:var(--klassci-blue)"></i>
                        <h6 style="color:var(--klassci-blue); font-weight:700;">Nouvelle Réinscription</h6>
                        <p class="text-muted mb-0">Réinscrire pour <strong>{{ $anneeCourante->name }}</strong></p>
                    </div>
                </div>
                @endif
            @endif
        </div>

        {{-- ===========================================================
             TAB 3 : PRÉSENCES
        =========================================================== --}}
        <div class="fiche-pane" id="pane-presences">
            <div class="section-card">
                <div class="section-card-header">
                    <span class="section-card-title"><i class="fas fa-calendar-check"></i> Historique des présences par année</span>
                </div>
                <div class="section-card-body">
                    @if(count($dossier['presences']) === 0)
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <p>Aucun relevé de présences disponible.</p>
                        </div>
                    @else
                        @foreach($dossier['presences'] as $pres)
                        <div class="presence-annee">
                            <div>
                                <div class="presence-label">{{ optional($pres['annee'])->name ?? 'N/A' }}</div>
                                @if($pres['total'] > 0)
                                    <div class="presence-year-sub">{{ $pres['total'] }} séances enregistrées</div>
                                @endif
                            </div>
                            <div>
                                <div class="presence-stats">
                                    <div class="presence-stat"><strong style="color:#10b981;">{{ $pres['presences'] }}</strong> présent(s)</div>
                                    <div class="presence-stat"><strong style="color:#ef4444;">{{ $pres['absences'] }}</strong> absent(s)</div>
                                    @if($pres['absences_just'] > 0)
                                    <div class="presence-stat"><strong style="color:#f59e0b;">{{ $pres['absences_just'] }}</strong> justifié(s)</div>
                                    @endif
                                    @if($pres['retards'] > 0)
                                    <div class="presence-stat"><strong style="color:#6366f1;">{{ $pres['retards'] }}</strong> retard(s)</div>
                                    @endif
                                    @if($pres['taux_presence'] !== null)
                                    <div class="presence-stat ms-auto" style="font-weight:700; font-size:0.9rem; color:#1e293b;">{{ $pres['taux_presence'] }}%</div>
                                    @endif
                                </div>
                                @if($pres['taux_presence'] !== null)
                                <div class="progress-track">
                                    @php
                                        $tp = $pres['taux_presence'];
                                        $gradFill = $tp >= 75 ? 'var(--klassci-gradient)' : ($tp >= 50 ? 'linear-gradient(90deg,#f59e0b,#fbbf24)' : 'linear-gradient(90deg,#ef4444,#f87171)');
                                    @endphp
                                    <div class="progress-fill" style="width:{{ min($tp, 100) }}%; background:{{ $gradFill }};"></div>
                                </div>
                                @else
                                <div class="progress-track"><div class="progress-fill" style="width:0%;"></div></div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        {{-- ===========================================================
             TAB 4 : FINANCES
        =========================================================== --}}
        <div class="fiche-pane" id="pane-finances">
            @php $fin = $dossier['financier']; @endphp

            <div class="kpi-strip mb-4">
                <div class="kpi-card kpi-green">
                    <div class="kpi-icon"><i class="fas fa-check-circle" style="color:#10b981"></i></div>
                    <div>
                        <div class="kpi-label">Total validé</div>
                        <div class="kpi-value">{{ number_format($fin['paiements_valides'], 0, ',', ' ') }}</div>
                        <div class="kpi-sub">FCFA</div>
                    </div>
                </div>
                @if($fin['paiements_en_attente'] > 0)
                <div class="kpi-card kpi-amber">
                    <div class="kpi-icon"><i class="fas fa-hourglass-half" style="color:#f59e0b"></i></div>
                    <div>
                        <div class="kpi-label">En attente</div>
                        <div class="kpi-value">{{ number_format($fin['paiements_en_attente'], 0, ',', ' ') }}</div>
                        <div class="kpi-sub">FCFA</div>
                    </div>
                </div>
                @endif
                <div class="kpi-card kpi-blue">
                    <div class="kpi-icon"><i class="fas fa-list" style="color:var(--klassci-blue)"></i></div>
                    <div>
                        <div class="kpi-label">Transactions</div>
                        <div class="kpi-value">{{ $fin['nombre_paiements'] }}</div>
                        <div class="kpi-sub">paiements</div>
                    </div>
                </div>
                @if($fin['total_reliquats_entrants'] > 0)
                <div class="kpi-card kpi-red">
                    <div class="kpi-icon"><i class="fas fa-exclamation-triangle" style="color:#ef4444"></i></div>
                    <div>
                        <div class="kpi-label">Reliquats</div>
                        <div class="kpi-value">{{ number_format($fin['total_reliquats_entrants'], 0, ',', ' ') }}</div>
                        <div class="kpi-sub">FCFA à payer</div>
                    </div>
                </div>
                @endif
            </div>

            <div class="section-card">
                <div class="section-card-header">
                    <span class="section-card-title"><i class="fas fa-money-bill-wave"></i> Historique des paiements</span>
                    <a href="{{ route('esbtp.paiements.create') }}?etudiant={{ $etudiant->id }}" class="hero-btn hero-btn-white" style="background:#f0f4ff; color:var(--klassci-blue); padding:6px 12px; font-size:0.78rem;">
                        <i class="fas fa-plus me-1"></i>Nouveau paiement
                    </a>
                </div>
                <div class="section-card-body" style="padding-top:8px; padding-bottom:8px;">
                    @forelse($etudiant->paiements as $paiement)
                        @php $st = $paiement->status ?? 'en_attente'; @endphp
                        <div class="paiement-row">
                            <div class="paiement-icon {{ $st === 'validé' ? 'valide' : ($st === 'rejeté' ? 'rejete' : 'attente') }}">
                                <i class="fas fa-{{ $st === 'validé' ? 'check' : ($st === 'rejeté' ? 'times' : 'clock') }}"></i>
                            </div>
                            <div>
                                <div class="paiement-motif">{{ $paiement->motif ?: 'Paiement' }}</div>
                                <div class="paiement-ref">
                                    {{ $paiement->numero_recu ? 'Reçu #' . $paiement->numero_recu : ($paiement->reference_paiement ?: 'N/A') }}
                                    @if($paiement->mode_paiement) · {{ $paiement->mode_paiement }} @endif
                                </div>
                            </div>
                            <div class="paiement-date">{{ optional($paiement->date_paiement)->format('d/m/Y') ?? 'N/A' }}</div>
                            <div style="display:flex; flex-direction:column; align-items:flex-end; gap:4px;">
                                <div class="paiement-montant">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</div>
                                <div style="display:flex; gap:4px;">
                                    <a href="{{ route('esbtp.paiements.show', $paiement->id) }}" class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size:0.72rem;">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($paiement->status === 'validé' && $paiement->numero_recu)
                                    <a href="{{ route('esbtp.paiements.recu', $paiement->id) }}" class="btn btn-sm btn-outline-success py-1 px-2" style="font-size:0.72rem;" target="_blank">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state"><i class="fas fa-receipt"></i><p>Aucun paiement enregistré.</p></div>
                    @endforelse
                </div>
            </div>

            {{-- Reliquats entrants --}}
            @if($fin['reliquats_entrants']->isNotEmpty())
            <div class="section-card mt-4">
                <div class="section-card-header">
                    <span class="section-card-title"><i class="fas fa-exclamation-triangle" style="color:#ef4444"></i> Reliquats à payer</span>
                    <span class="badge bg-danger">{{ number_format($fin['total_reliquats_entrants'], 0, ',', ' ') }} FCFA</span>
                </div>
                <div class="section-card-body" style="padding-top:8px; padding-bottom:8px;">
                    @foreach($fin['reliquats_entrants'] as $reliquat)
                    @php $frais = optional(optional($reliquat->fraisSubscription)->fraisCategory); @endphp
                    <div class="paiement-row">
                        <div class="paiement-icon rejete"><i class="fas fa-arrow-down"></i></div>
                        <div>
                            <div class="paiement-motif">{{ $frais->name ?? 'Reliquat' }}</div>
                            <div class="paiement-ref">Depuis {{ optional(optional($reliquat->inscriptionSource)->anneeUniversitaire)->name ?? 'N/A' }}</div>
                        </div>
                        <div class="paiement-date">
                            <span class="badge {{ $reliquat->statut === 'actif' ? 'bg-danger' : 'bg-success' }}">{{ ucfirst($reliquat->statut) }}</span>
                        </div>
                        <div class="paiement-montant" style="color:#ef4444;">{{ number_format($reliquat->solde_restant, 0, ',', ' ') }} FCFA</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- ===========================================================
             TAB 5 : PROFIL
        =========================================================== --}}
        <div class="fiche-pane" id="pane-infos">
            <div class="row g-4">
                <div class="col-12 col-lg-6">
                    {{-- Informations personnelles --}}
                    <div class="section-card">
                        <div class="section-card-header">
                            <span class="section-card-title"><i class="fas fa-user"></i> Informations personnelles</span>
                            @can('edit_students')
                            <a href="{{ route('esbtp.etudiants.edit', $etudiant->id) }}" style="font-size:0.78rem; color:var(--klassci-blue);">
                                <i class="fas fa-pencil-alt me-1"></i>Modifier
                            </a>
                            @endcan
                        </div>
                        <div class="section-card-body">
                            {{-- Photo avec upload --}}
                            <div class="text-center mb-4">
                                <div class="position-relative d-inline-block" style="cursor:pointer;" onclick="document.getElementById('photo-upload').click()">
                                    @if($etudiant->photo_url)
                                        <img src="{{ $etudiant->photo_url }}" alt="Photo" class="rounded-circle" style="width:100px;height:100px;object-fit:cover;border:3px solid var(--klassci-blue);">
                                    @else
                                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width:100px;height:100px;border:3px solid #e2e8f0;">
                                            <i class="fas fa-user fa-3x text-secondary"></i>
                                        </div>
                                    @endif
                                    <div class="position-absolute bottom-0 end-0 bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width:30px;height:30px;border:2px solid white;">
                                        <i class="fas fa-camera text-white" style="font-size:0.7rem;"></i>
                                    </div>
                                </div>
                                <input type="file" id="photo-upload" accept="image/*" style="display:none;" onchange="uploadPhoto(this)">
                            </div>

                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">Nom complet</div>
                                    <div class="info-value">{{ $etudiant->nom }} {{ $etudiant->prenoms }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Matricule</div>
                                    <div class="info-value">{{ $etudiant->matricule }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Genre</div>
                                    <div class="info-value">{{ $etudiant->genre == 'M' ? 'Masculin' : 'Féminin' }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Date de naissance</div>
                                    <div class="info-value {{ !$etudiant->date_naissance ? 'empty' : '' }}">
                                        {{ $etudiant->date_naissance ? $etudiant->date_naissance->format('d/m/Y') . ' (' . $etudiant->age . ' ans)' : 'Non renseigné' }}
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Lieu de naissance</div>
                                    <div class="info-value {{ !$etudiant->lieu_naissance ? 'empty' : '' }}">{{ $etudiant->lieu_naissance ?: 'Non renseigné' }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Nationalité</div>
                                    <div class="info-value {{ !$etudiant->nationalite ? 'empty' : '' }}">{{ $etudiant->nationalite ?: 'Non renseigné' }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Téléphone</div>
                                    <div class="info-value {{ !$etudiant->telephone ? 'empty' : '' }}">{{ $etudiant->telephone ?: 'Non renseigné' }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Email</div>
                                    <div class="info-value {{ !$etudiant->email_personnel ? 'empty' : '' }}">{{ $etudiant->email_personnel ?: 'Non renseigné' }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Ville</div>
                                    <div class="info-value {{ !$etudiant->ville ? 'empty' : '' }}">{{ $etudiant->ville ?: 'Non renseigné' }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Commune</div>
                                    <div class="info-value {{ !$etudiant->commune ? 'empty' : '' }}">{{ $etudiant->commune ?: 'Non renseigné' }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Statut</div>
                                    <div class="info-value">
                                        <span class="badge {{ $etudiant->statut === 'actif' ? 'bg-success' : 'bg-danger' }}">{{ ucfirst($etudiant->statut ?? 'N/A') }}</span>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Date d'admission</div>
                                    <div class="info-value">{{ $etudiant->created_at?->format('d/m/Y') ?? 'N/A' }}</div>
                                </div>
                                @if($etudiant->urgence_contact_nom)
                                <div class="info-item" style="grid-column: 1 / -1;">
                                    <div class="info-label">Contact urgence</div>
                                    <div class="info-value">{{ $etudiant->urgence_contact_nom }} ({{ $etudiant->urgence_contact_relation }}) — {{ $etudiant->urgence_contact_telephone }}</div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Compte utilisateur --}}
                    <div class="section-card mt-4">
                        <div class="section-card-header">
                            <span class="section-card-title"><i class="fas fa-user-cog"></i> Compte utilisateur</span>
                        </div>
                        <div class="section-card-body">
                            @if(session('account_created'))
                            <div class="alert alert-success alert-dismissible fade show">
                                <strong>Compte créé !</strong> Identifiant : <code>{{ session('new_username') }}</code> — Mot de passe : <code>{{ session('new_password') }}</code>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            @endif
                            @if($etudiant->user)
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <span class="badge bg-success">Actif</span>
                                    <span style="font-size:0.88rem;">{{ $etudiant->user->email }}</span>
                                </div>
                                <p style="font-size:0.88rem; margin-bottom:12px;"><strong>Identifiant :</strong> {{ $etudiant->user->username ?: $etudiant->user->email }}</p>
                                <a href="{{ route('esbtp.etudiants.reset-password', $etudiant->id) }}" class="btn btn-sm btn-outline-secondary w-100" onclick="return confirm('Réinitialiser le mot de passe ?')">
                                    <i class="fas fa-key me-1"></i>Réinitialiser le mot de passe
                                </a>
                            @else
                                <div class="alert alert-warning mb-3">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Aucun compte utilisateur associé.
                                </div>
                                <button class="btn btn-sm btn-primary w-100" data-bs-toggle="modal" data-bs-target="#createAccountModal">
                                    <i class="fas fa-user-plus me-1"></i>Créer un compte
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    {{-- Parents / tuteurs --}}
                    <div class="section-card">
                        <div class="section-card-header">
                            <span class="section-card-title"><i class="fas fa-users"></i> Parents / Tuteurs</span>
                            <span class="badge bg-secondary">{{ $etudiant->parents->count() }}</span>
                        </div>
                        <div class="section-card-body">
                            @forelse($etudiant->parents as $index => $parent)
                            <div class="parent-card {{ $index === 0 ? 'open' : '' }}" id="parent-card-{{ $index }}">
                                <div class="parent-card-header" onclick="toggleParent({{ $index }})">
                                    <div>
                                        <strong style="font-size:0.9rem;">{{ $parent->nom }} {{ $parent->prenoms }}</strong>
                                        <div style="font-size:0.78rem; color:#64748b;">
                                            {{ $parent->pivot->relation ?? 'N/A' }}
                                            @if($parent->pivot->is_tuteur)
                                                <span class="badge bg-primary ms-1" style="font-size:0.65rem;">Tuteur principal</span>
                                            @endif
                                        </div>
                                    </div>
                                    <i class="fas fa-chevron-down parent-chevron"></i>
                                </div>
                                <div class="parent-card-body">
                                    <div class="info-grid" style="grid-template-columns: 1fr 1fr;">
                                        <div class="info-item">
                                            <div class="info-label">Téléphone</div>
                                            <div class="info-value {{ !$parent->telephone ? 'empty' : '' }}">{{ $parent->telephone ?: '—' }}</div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Email</div>
                                            <div class="info-value {{ !$parent->email ? 'empty' : '' }}">{{ $parent->email ?: '—' }}</div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Profession</div>
                                            <div class="info-value {{ !$parent->profession ? 'empty' : '' }}">{{ $parent->profession ?: '—' }}</div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Adresse</div>
                                            <div class="info-value {{ !$parent->adresse ? 'empty' : '' }}">{{ $parent->adresse ?: '—' }}</div>
                                        </div>
                                    </div>
                                    @php $autresEtudiants = $parent->etudiants->where('id', '!=', $etudiant->id); @endphp
                                    @if($autresEtudiants->count() > 0)
                                    <div class="mt-2" style="font-size:0.78rem; color:#64748b;">
                                        <strong>Autres enfants :</strong>
                                        @foreach($autresEtudiants as $autre)
                                            <a href="{{ route('esbtp.etudiants.show', $autre->id) }}" style="color:var(--klassci-blue); margin-left:4px;">{{ $autre->nom }} {{ $autre->prenoms }}</a>@if(!$loop->last),@endif
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @empty
                                <div class="empty-state">
                                    <i class="fas fa-user-friends"></i>
                                    <p>Aucun parent ou tuteur associé.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- /container --}}
</div>{{-- /fiche-etudiant --}}

{{-- ============================================================
     MODALS
============================================================ --}}

{{-- Création compte --}}
@if(!$etudiant->user)
<div class="modal fade" id="createAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Créer un compte utilisateur</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3"><i class="fas fa-info-circle me-2"></i>Un compte étudiant sera créé automatiquement.</div>
                <ul class="list-unstyled mb-3">
                    <li class="mb-1"><i class="fas fa-user text-primary me-2"></i>Identifiant basé sur prénom.nom</li>
                    <li class="mb-1"><i class="fas fa-key text-primary me-2"></i>Mot de passe généré automatiquement</li>
                    <li><i class="fas fa-id-badge text-primary me-2"></i>Rôle : Étudiant</li>
                </ul>
                <div class="alert alert-warning mb-0"><small><i class="fas fa-exclamation-triangle me-1"></i>Le mot de passe sera affiché une seule fois.</small></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form action="{{ route('esbtp.etudiants.create-account', $etudiant) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i>Créer</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Suppression étudiant --}}
@can('delete_students')
<div class="modal fade" id="deleteStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Suppression définitive</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger border-0 mb-3">
                    <strong>ATTENTION</strong> — Cette action supprime définitivement toutes les données. Elle ne peut pas être annulée.
                </div>
                <p><strong>{{ $etudiant->nom }} {{ $etudiant->prenoms }}</strong> — Matricule : {{ $etudiant->matricule }}</p>
                <p class="text-muted small">Seront supprimés : {{ $etudiant->inscriptions->count() }} inscription(s), {{ $etudiant->paiements->count() }} paiement(s), notes, absences, bulletins...</p>
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="confirmDeletion">
                    <label class="form-check-label fw-bold" for="confirmDeletion">Je confirme la suppression définitive.</label>
                </div>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" id="keepUserAccount">
                    <label class="form-check-label" for="keepUserAccount">Conserver le compte utilisateur</label>
                </div>
                <div class="text-center d-none py-3" id="deletionProgress">
                    <div class="spinner-border text-danger"></div>
                    <p class="mt-2 text-muted">Suppression en cours...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button class="btn btn-danger" id="confirmDeleteBtn" disabled onclick="deleteStudent()">
                    <i class="fas fa-trash me-1"></i>Supprimer définitivement
                </button>
            </div>
        </div>
    </div>
</div>
@endcan

@endsection

@section('scripts')
<script>
// ---- Tabs ----
function switchTab(paneId) {
    document.querySelectorAll('.fiche-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.fiche-tab').forEach(t => t.classList.remove('active'));
    const pane = document.getElementById(paneId);
    const tab  = document.querySelector('[data-pane="' + paneId + '"]');
    if (pane) pane.classList.add('active');
    if (tab)  tab.classList.add('active');
}

document.querySelectorAll('.fiche-tab').forEach(tab => {
    tab.addEventListener('click', () => switchTab(tab.dataset.pane));
});

// Hash URL support
const hashMap = {
    '#academique': 'pane-academique',
    '#presences':  'pane-presences',
    '#finances':   'pane-finances',
    '#profil':     'pane-infos'
};
if (hashMap[location.hash]) switchTab(hashMap[location.hash]);

// ---- Parents accordion ----
function toggleParent(index) {
    document.getElementById('parent-card-' + index)?.classList.toggle('open');
}

// ---- Photo upload ----
async function uploadPhoto(input) {
    if (!input.files[0]) return;
    const formData = new FormData();
    formData.append('photo', input.files[0]);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    try {
        const res = await fetch('{{ route("esbtp.etudiants.update-photo", $etudiant->id) }}', { method: 'POST', body: formData });
        if (res.ok) location.reload();
        else alert('Erreur lors du téléchargement de la photo.');
    } catch(e) { alert('Erreur réseau.'); }
}

// ---- Suppression ----
document.getElementById('confirmDeletion')?.addEventListener('change', function() {
    document.getElementById('confirmDeleteBtn').disabled = !this.checked;
});

async function deleteStudent() {
    const btn      = document.getElementById('confirmDeleteBtn');
    const progress = document.getElementById('deletionProgress');
    const body     = document.querySelector('#deleteStudentModal .modal-body');
    const keepUser = document.getElementById('keepUserAccount')?.checked || false;
    btn.disabled = true;
    body.classList.add('d-none');
    progress.classList.remove('d-none');
    try {
        const res = await fetch('{{ route("esbtp.etudiants.destroy", $etudiant->id) }}', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ keep_user: keepUser })
        });
        if (res.ok) {
            window.location.href = '{{ route("esbtp.etudiants.index") }}';
        } else {
            const data = await res.json();
            alert(data.message || 'Erreur lors de la suppression.');
            btn.disabled = false;
            body.classList.remove('d-none');
            progress.classList.add('d-none');
        }
    } catch(e) {
        alert('Erreur réseau.');
        btn.disabled = false;
        body.classList.remove('d-none');
        progress.classList.add('d-none');
    }
}
</script>
@endsection
