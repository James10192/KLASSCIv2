@extends('layouts.app')

@section('title', 'Bulletin de ' . $bulletin->etudiant->nom . ' ' . $bulletin->etudiant->prenom . ' - KLASSCI')

@php
    $bshMoyenne = $bulletin->moyenne_generale;
    $bshResultat = null;
    if ($bshMoyenne !== null) {
        if ($bshMoyenne >= 12) { $bshResultat = ['Très bien', 'success']; }
        elseif ($bshMoyenne >= 10) { $bshResultat = ['Passable', 'primary']; }
        elseif ($bshMoyenne >= 8) { $bshResultat = ['Insuffisant', 'warning']; }
        else { $bshResultat = ['Faible', 'danger']; }
    }
    $bshPeriodeLabel = match($bulletin->periode) {
        'semestre1' => 'Premier semestre',
        'semestre2' => 'Deuxième semestre',
        'annuel' => 'Annuel',
        default => $bulletin->periode,
    };
    $bshAnnee = '—';
    if ($bulletin->anneeUniversitaire) {
        $bshAu = $bulletin->anneeUniversitaire;
        if (! empty($bshAu->annee_debut) && ! empty($bshAu->annee_fin)) {
            $bshAnnee = $bshAu->annee_debut . '-' . $bshAu->annee_fin;
        } elseif (! empty($bshAu->name)) {
            $bshAnnee = $bshAu->name;
        } elseif (! empty($bshAu->libelle)) {
            $bshAnnee = $bshAu->libelle;
        }
    }
    $bshRoles = [
        'directeur' => 'Directeur',
        'responsable' => 'Responsable pédagogique',
        'parent' => 'Parent / Tuteur',
    ];
    $bshSign = [];
    foreach (array_keys($bshRoles) as $r) {
        $signed = (bool) $bulletin->{'signature_'.$r};
        $dateRaw = $bulletin->{'date_signature_'.$r} ?? null;
        $bshSign[$r] = [
            'signed' => $signed,
            'date' => ($signed && $dateRaw) ? date('d/m/Y à H:i', strtotime($dateRaw)) : null,
        ];
    }
    $bshState = [
        'sign' => $bshSign,
        'signUrls' => [
            'directeur' => route('esbtp.bulletins.signer', ['bulletin' => $bulletin, 'role' => 'directeur']),
            'responsable' => route('esbtp.bulletins.signer', ['bulletin' => $bulletin, 'role' => 'responsable']),
            'parent' => route('esbtp.bulletins.signer', ['bulletin' => $bulletin, 'role' => 'parent']),
        ],
        'isPublished' => (bool) $bulletin->is_published,
        'toggleUrl' => route('esbtp.bulletins.toggle-publication', $bulletin),
        'csrf' => csrf_token(),
        'canEdit' => auth()->user()->can('bulletins.edit'),
    ];
    $bshPdfParams = ['bulletin' => $bulletin->etudiant_id, 'classe_id' => $bulletin->classe_id, 'periode' => $bulletin->periode, 'annee_universitaire_id' => $bulletin->annee_universitaire_id];
@endphp

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    .bsh { --bsh-primary:#0453cb; --bsh-primary-d:#033a8e; --bsh-text:#1e293b; --bsh-muted:#64748b; --bsh-border:#e2e8f0; --bsh-surface:#f8fafc; }

    /* Hero */
    .bsh-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px; padding: 2rem 2.5rem 1.5rem; color: #fff; margin-bottom: 1.25rem;
        box-shadow: 0 8px 30px rgba(4,83,203,.18);
    }
    .bsh-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
    .bsh-hero-left { display: flex; align-items: center; gap: 1rem; min-width: 0; }
    .bsh-hero-icon {
        width: 54px; height: 54px; border-radius: 14px; background: rgba(255,255,255,.12);
        backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; color: #fff;
    }
    .bsh-hero h1 { font-size: 1.4rem; font-weight: 700; color: #fff; margin: 0; line-height: 1.2; }
    .bsh-hero-sub { color: rgba(255,255,255,.72); font-size: .85rem; margin: .25rem 0 0; display: flex; flex-wrap: wrap; gap: .35rem .85rem; }
    .bsh-hero-sub span { display: inline-flex; align-items: center; gap: .3rem; }
    .bsh-hero-actions { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }

    .bsh-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
    .bsh-kpi {
        flex: 1; min-width: 150px; background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15);
        border-radius: 12px; padding: .9rem 1rem;
    }
    .bsh-kpi-label { font-size: .7rem; color: rgba(255,255,255,.65); text-transform: uppercase; letter-spacing: .4px; margin-bottom: .3rem; }
    .bsh-kpi-value { font-size: 1.5rem; font-weight: 800; color: #fff; line-height: 1; }
    .bsh-kpi-value small { font-size: .9rem; font-weight: 600; color: rgba(255,255,255,.7); }
    .bsh-kpi-chip {
        display: inline-flex; align-items: center; gap: .35rem; padding: .25rem .6rem; border-radius: 8px;
        font-size: .82rem; font-weight: 700;
    }
    .bsh-chip--success { background: rgba(52,211,153,.2); color: #d1fae5; border: 1px solid rgba(52,211,153,.4); }
    .bsh-chip--danger  { background: rgba(248,113,113,.2); color: #fee2e2; border: 1px solid rgba(248,113,113,.4); }
    .bsh-chip--warning { background: rgba(251,191,36,.2); color: #fef3c7; border: 1px solid rgba(251,191,36,.4); }
    .bsh-chip--neutral { background: rgba(255,255,255,.14); color: #fff; border: 1px solid rgba(255,255,255,.2); }

    /* Buttons */
    .bsh-btn {
        display: inline-flex; align-items: center; gap: .45rem; padding: .55rem 1rem; border-radius: 10px;
        font-size: .82rem; font-weight: 600; border: 1px solid transparent; cursor: pointer; text-decoration: none;
        transition: all .15s ease; white-space: nowrap;
    }
    .bsh-btn:disabled { opacity: .6; cursor: wait; }
    .bsh-btn--glass { background: rgba(255,255,255,.15); color: #fff; border-color: rgba(255,255,255,.22); }
    .bsh-btn--glass:hover { background: rgba(255,255,255,.25); color: #fff; }
    .bsh-btn--white { background: #fff; color: var(--bsh-primary); }
    .bsh-btn--white:hover { background: #eef3fb; color: var(--bsh-primary-d); }
    .bsh-btn--primary { background: var(--bsh-primary); color: #fff; }
    .bsh-btn--primary:hover { background: var(--bsh-primary-d); color: #fff; }
    .bsh-btn--ghost { background: #fff; color: var(--bsh-text); border-color: var(--bsh-border); }
    .bsh-btn--ghost:hover { border-color: #c7d4e5; color: var(--bsh-primary); background: var(--bsh-surface); }
    .bsh-btn--success { background: #10b981; color: #fff; }
    .bsh-btn--success:hover { background: #0e9f70; color: #fff; }
    .bsh-btn--warning { background: #f59e0b; color: #fff; }
    .bsh-btn--warning:hover { background: #d97f08; color: #fff; }
    .bsh-btn--danger { background: #dc2626; color: #fff; }
    .bsh-btn--danger:hover { background: #b91c1c; color: #fff; }
    .bsh-btn--danger-ghost { background: #fff; color: #dc2626; border-color: rgba(220,38,38,.3); }
    .bsh-btn--danger-ghost:hover { background: rgba(220,38,38,.06); color: #b91c1c; }

    /* Cards */
    .bsh-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem; }
    .bsh-card { background: #fff; border: 1px solid var(--bsh-border); border-radius: 14px; box-shadow: 0 1px 3px rgba(15,23,42,.04); overflow: hidden; margin-bottom: 1.25rem; }
    .bsh-card-head { display: flex; align-items: center; gap: .65rem; padding: .9rem 1.25rem; border-bottom: 1px solid var(--bsh-border); }
    .bsh-card-ico { width: 34px; height: 34px; border-radius: 9px; background: linear-gradient(135deg, var(--bsh-primary), #3b7ddb); color: #fff; display: flex; align-items: center; justify-content: center; font-size: .8rem; flex-shrink: 0; }
    .bsh-card-title { font-size: .92rem; font-weight: 700; color: var(--bsh-text); margin: 0; }
    .bsh-card-body { padding: 1.1rem 1.25rem; }

    /* Info rows */
    .bsh-info-row { display: flex; justify-content: space-between; gap: 1rem; padding: .5rem 0; border-bottom: 1px dashed var(--bsh-border); }
    .bsh-info-row:last-child { border-bottom: none; }
    .bsh-info-label { color: var(--bsh-muted); font-size: .8rem; font-weight: 500; }
    .bsh-info-value { color: var(--bsh-text); font-size: .85rem; font-weight: 600; text-align: right; }

    /* Table */
    .bsh-table-wrap { overflow-x: auto; }
    .bsh-table { width: 100%; border-collapse: collapse; font-size: .85rem; }
    .bsh-table thead th {
        text-align: left; padding: .65rem .85rem; background: var(--bsh-surface); color: var(--bsh-muted);
        font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; border-bottom: 1px solid var(--bsh-border); white-space: nowrap;
    }
    .bsh-table tbody td { padding: .7rem .85rem; border-bottom: 1px solid #f1f5f9; color: var(--bsh-text); vertical-align: middle; }
    .bsh-table tbody tr:last-child td { border-bottom: none; }
    .bsh-table tbody tr:hover { background: rgba(4,83,203,.025); }
    .bsh-table .text-c { text-align: center; }
    .bsh-code { font-family: 'Courier New', monospace; font-size: .72rem; font-weight: 700; color: var(--bsh-primary); background: rgba(4,83,203,.08); padding: .15rem .45rem; border-radius: 5px; }
    .bsh-badge { display: inline-flex; align-items: center; padding: .2rem .55rem; border-radius: 6px; font-size: .72rem; font-weight: 700; white-space: nowrap; }
    .bsh-badge--success { background: rgba(16,185,129,.12); color: #047857; border: 1px solid rgba(16,185,129,.28); }
    .bsh-badge--danger  { background: rgba(220,38,38,.1); color: #b91c1c; border: 1px solid rgba(220,38,38,.25); }
    .bsh-badge--warning { background: rgba(245,158,11,.12); color: #b45309; border: 1px solid rgba(245,158,11,.28); }
    .bsh-badge--primary { background: rgba(4,83,203,.1); color: var(--bsh-primary); border: 1px solid rgba(4,83,203,.25); }
    .bsh-badge--neutral { background: #f1f5f9; color: #475569; border: 1px solid var(--bsh-border); }
    .bsh-muted { color: #94a3b8; font-style: italic; }

    /* Signatures */
    .bsh-sign-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
    .bsh-sign { border: 1px solid var(--bsh-border); border-radius: 12px; overflow: hidden; transition: all .2s; }
    .bsh-sign--signed { border-color: rgba(16,185,129,.4); }
    .bsh-sign-head { padding: .65rem 1rem; font-size: .82rem; font-weight: 700; color: #fff; display: flex; align-items: center; gap: .5rem; background: #94a3b8; transition: background .2s; }
    .bsh-sign--signed .bsh-sign-head { background: linear-gradient(135deg, #0e9f70, #10b981); }
    .bsh-sign-body { padding: 1.25rem 1rem; text-align: center; }
    .bsh-sign-ico { font-size: 2.1rem; margin-bottom: .5rem; }
    .bsh-sign-ico--ok { color: #10b981; }
    .bsh-sign-ico--no { color: #cbd5e1; }
    .bsh-sign-text { font-size: .82rem; color: var(--bsh-muted); margin-bottom: .75rem; }

    /* Appreciation */
    .bsh-appreciation { font-size: .88rem; color: var(--bsh-text); line-height: 1.6; margin: 0; }

    /* Actions footer */
    .bsh-actions { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; margin-top: .25rem; }
    .bsh-actions-group { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }

    /* Modal premium */
    .bsh-modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,.55); backdrop-filter: blur(2px); z-index: 1080; display: flex; align-items: center; justify-content: center; padding: 1rem; }
    .bsh-modal { background: #fff; border-radius: 16px; width: 100%; max-width: 440px; overflow: hidden; box-shadow: 0 20px 60px rgba(15,23,42,.3); }
    .bsh-modal-head { background: linear-gradient(135deg, #b91c1c, #dc2626); color: #fff; padding: 1.1rem 1.25rem; display: flex; align-items: center; gap: .65rem; }
    .bsh-modal-head i { font-size: 1.1rem; }
    .bsh-modal-head h5 { margin: 0; font-size: 1rem; font-weight: 700; }
    .bsh-modal-body { padding: 1.25rem; color: var(--bsh-text); font-size: .88rem; line-height: 1.55; }
    .bsh-modal-body .bsh-warn { background: rgba(220,38,38,.07); border: 1px solid rgba(220,38,38,.2); border-radius: 10px; padding: .75rem .9rem; margin-top: .85rem; color: #b91c1c; font-size: .82rem; }
    .bsh-modal-foot { display: flex; justify-content: flex-end; gap: .6rem; padding: 1rem 1.25rem; border-top: 1px solid var(--bsh-border); }

    /* Toast */
    .bsh-toasts { position: fixed; bottom: 1.25rem; right: 1.25rem; z-index: 1090; display: flex; flex-direction: column; gap: .6rem; max-width: 360px; }
    .bsh-toast { display: flex; align-items: flex-start; gap: .6rem; padding: .8rem 1rem; border-radius: 11px; background: #fff; box-shadow: 0 10px 30px rgba(15,23,42,.18); border-left: 4px solid var(--bsh-primary); font-size: .85rem; color: var(--bsh-text); }
    .bsh-toast--success { border-left-color: #10b981; }
    .bsh-toast--error { border-left-color: #dc2626; }
    .bsh-toast i { margin-top: .1rem; }
    .bsh-toast--success i { color: #10b981; }
    .bsh-toast--error i { color: #dc2626; }

    @media (max-width: 768px) {
        .bsh-hero { padding: 1.5rem 1.25rem; }
        .bsh-grid-2 { grid-template-columns: 1fr; }
        .bsh-sign-grid { grid-template-columns: 1fr; }
        .bsh-hero-actions { width: 100%; }
    }
</style>
@endpush

@section('content')
<div class="bsh container-fluid"
     x-data="bulletinShow(@js($bshState))"
     @keydown.escape.window="deleteOpen = false">

    {{-- HERO --}}
    <div class="bsh-hero">
        <div class="bsh-hero-top">
            <div class="bsh-hero-left">
                <div class="bsh-hero-icon"><i class="fas fa-file-alt"></i></div>
                <div style="min-width:0;">
                    <h1>Bulletin de {{ $bulletin->etudiant->nom }} {{ $bulletin->etudiant->prenom }}</h1>
                    <p class="bsh-hero-sub">
                        <span><i class="fas fa-id-card"></i>{{ $bulletin->etudiant->matricule }}</span>
                        <span><i class="fas fa-users"></i>{{ $bulletin->classe->name }}</span>
                        <span><i class="fas fa-calendar-alt"></i>{{ $bshPeriodeLabel }}</span>
                        <span><i class="fas fa-graduation-cap"></i>{{ $bshAnnee }}</span>
                    </p>
                </div>
            </div>
            <div class="bsh-hero-actions">
                <x-pdf-actions
                    :preview-url="route('esbtp.bulletins.pdf-params-preview', $bshPdfParams)"
                    :download-url="route('esbtp.bulletins.pdf-params', $bshPdfParams)"
                    label="Bulletin"
                    buttonClass="bsh-btn bsh-btn--glass" />
                <a href="{{ route('esbtp.bulletins.index') }}" class="bsh-btn bsh-btn--white">
                    <i class="fas fa-arrow-left"></i> Liste
                </a>
            </div>
        </div>

        <div class="bsh-kpis">
            <div class="bsh-kpi">
                <div class="bsh-kpi-label">Moyenne générale</div>
                <div class="bsh-kpi-value">
                    @if($bshMoyenne !== null)
                        <span class="bsh-kpi-chip {{ $bshMoyenne >= 10 ? 'bsh-chip--success' : 'bsh-chip--danger' }}">
                            {{ number_format($bshMoyenne, 2) }}<small>/20</small>
                        </span>
                    @else
                        <span class="bsh-kpi-chip bsh-chip--neutral">Non calculée</span>
                    @endif
                </div>
            </div>
            <div class="bsh-kpi">
                <div class="bsh-kpi-label">Rang</div>
                <div class="bsh-kpi-value">
                    @if($bulletin->rang)
                        {{ $bulletin->rang }}<small>{{ $bulletin->rang == 1 ? 'er' : 'e' }}@if(!empty($bulletin->total_etudiants)) / {{ $bulletin->total_etudiants }}@endif</small>
                    @else
                        <span class="bsh-kpi-chip bsh-chip--neutral">Non classé</span>
                    @endif
                </div>
            </div>
            <div class="bsh-kpi">
                <div class="bsh-kpi-label">Mention</div>
                <div class="bsh-kpi-value">
                    @if($bshResultat)
                        <span class="bsh-kpi-chip bsh-chip--{{ $bshResultat[1] === 'primary' ? 'neutral' : $bshResultat[1] }}">{{ $bshResultat[0] }}</span>
                    @else
                        <span class="bsh-kpi-chip bsh-chip--neutral">Non évalué</span>
                    @endif
                </div>
            </div>
            <div class="bsh-kpi">
                <div class="bsh-kpi-label">Statut</div>
                <div class="bsh-kpi-value">
                    <span class="bsh-kpi-chip bsh-chip--success" x-show="isPublished" x-cloak><i class="fas fa-check"></i> Publié</span>
                    <span class="bsh-kpi-chip bsh-chip--warning" x-show="!isPublished"><i class="fas fa-clock"></i> Non publié</span>
                </div>
            </div>
        </div>
    </div>

    {{-- IDENTITÉ + DÉTAILS --}}
    <div class="bsh-grid-2">
        <div class="bsh-card" style="margin-bottom:0;">
            <div class="bsh-card-head">
                <div class="bsh-card-ico"><i class="fas fa-user-graduate"></i></div>
                <h6 class="bsh-card-title">Identité de l'étudiant</h6>
            </div>
            <div class="bsh-card-body">
                <div class="bsh-info-row"><span class="bsh-info-label">Matricule</span><span class="bsh-info-value">{{ $bulletin->etudiant->matricule }}</span></div>
                <div class="bsh-info-row"><span class="bsh-info-label">Nom &amp; prénom</span><span class="bsh-info-value">{{ $bulletin->etudiant->nom }} {{ $bulletin->etudiant->prenom }}</span></div>
                <div class="bsh-info-row"><span class="bsh-info-label">Date de naissance</span><span class="bsh-info-value">{{ $bulletin->etudiant->date_naissance ? date('d/m/Y', strtotime($bulletin->etudiant->date_naissance)) : 'Non renseignée' }}</span></div>
                <div class="bsh-info-row"><span class="bsh-info-label">Classe</span><span class="bsh-info-value">{{ $bulletin->classe->name }}</span></div>
                <div class="bsh-info-row"><span class="bsh-info-label">Filière</span><span class="bsh-info-value">{{ $bulletin->classe->filiere->name ?? '—' }}</span></div>
                <div class="bsh-info-row"><span class="bsh-info-label">Niveau d'étude</span><span class="bsh-info-value">{{ $bulletin->classe->niveau->name ?? '—' }}</span></div>
            </div>
        </div>
        <div class="bsh-card" style="margin-bottom:0;">
            <div class="bsh-card-head">
                <div class="bsh-card-ico"><i class="fas fa-clipboard-list"></i></div>
                <h6 class="bsh-card-title">Détails du bulletin</h6>
            </div>
            <div class="bsh-card-body">
                <div class="bsh-info-row"><span class="bsh-info-label">Période</span><span class="bsh-info-value">{{ $bshPeriodeLabel }}</span></div>
                <div class="bsh-info-row"><span class="bsh-info-label">Année scolaire</span><span class="bsh-info-value">{{ $bshAnnee }}</span></div>
                <div class="bsh-info-row"><span class="bsh-info-label">Date de génération</span><span class="bsh-info-value">{{ date('d/m/Y H:i', strtotime($bulletin->created_at)) }}</span></div>
                <div class="bsh-info-row">
                    <span class="bsh-info-label">Statut de publication</span>
                    <span class="bsh-info-value">
                        <span class="bsh-badge bsh-badge--success" x-show="isPublished" x-cloak>Publié</span>
                        <span class="bsh-badge bsh-badge--warning" x-show="!isPublished">Non publié</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- RÉSULTATS PAR MATIÈRE --}}
    <div class="bsh-card">
        <div class="bsh-card-head">
            <div class="bsh-card-ico"><i class="fas fa-table"></i></div>
            <h6 class="bsh-card-title">Résultats par matière</h6>
        </div>
        <div class="bsh-card-body" style="padding:0;">
            <div class="bsh-table-wrap">
                <table class="bsh-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Matière</th>
                            <th>Enseignant</th>
                            <th class="text-c">Coef.</th>
                            <th class="text-c">Moyenne</th>
                            <th class="text-c">Mention</th>
                            <th>Appréciation</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bulletin->resultats as $resultat)
                            <tr>
                                <td><span class="bsh-code">{{ $resultat->matiere->code ?? '—' }}</span></td>
                                <td style="font-weight:600;">{{ $resultat->matiere->name ?? 'Matière' }}</td>
                                <td>
                                    @if($resultat->matiere && $resultat->matiere->enseignants->count() > 0)
                                        {{ $resultat->matiere->enseignants->first()->nom }} {{ $resultat->matiere->enseignants->first()->prenom }}
                                    @else
                                        <span class="bsh-muted">Non assigné</span>
                                    @endif
                                </td>
                                <td class="text-c">{{ $resultat->coefficient }}</td>
                                <td class="text-c">
                                    <span class="bsh-badge {{ $resultat->moyenne >= 10 ? 'bsh-badge--success' : 'bsh-badge--danger' }}">
                                        {{ number_format($resultat->moyenne, 2) }}/20
                                    </span>
                                </td>
                                <td class="text-c">
                                    @php
                                        $m = $resultat->moyenne;
                                        if ($m >= 16) { $mention = ['Excellent', 'success']; }
                                        elseif ($m >= 14) { $mention = ['Très bien', 'primary']; }
                                        elseif ($m >= 12) { $mention = ['Bien', 'primary']; }
                                        elseif ($m >= 10) { $mention = ['Passable', 'neutral']; }
                                        elseif ($m >= 8) { $mention = ['Insuffisant', 'warning']; }
                                        else { $mention = ['Faible', 'danger']; }
                                    @endphp
                                    <span class="bsh-badge bsh-badge--{{ $mention[1] }}">{{ $mention[0] }}</span>
                                </td>
                                <td>{{ $resultat->commentaire ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-c bsh-muted" style="padding:2rem;">Aucun résultat disponible pour cette période.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ASSIDUITÉ + APPRÉCIATION --}}
    <div class="bsh-grid-2">
        <div class="bsh-card" style="margin-bottom:0;">
            <div class="bsh-card-head">
                <div class="bsh-card-ico"><i class="fas fa-user-clock"></i></div>
                <h6 class="bsh-card-title">Assiduité</h6>
            </div>
            <div class="bsh-card-body">
                <div class="bsh-info-row"><span class="bsh-info-label">Absences justifiées</span><span class="bsh-info-value">{{ $bulletin->absences_justifiees ?? 0 }} h</span></div>
                <div class="bsh-info-row"><span class="bsh-info-label">Absences non justifiées</span><span class="bsh-info-value">{{ $bulletin->absences_non_justifiees ?? 0 }} h</span></div>
                <div class="bsh-info-row"><span class="bsh-info-label">Retards</span><span class="bsh-info-value">{{ $bulletin->retards ?? 0 }}</span></div>
            </div>
        </div>
        <div class="bsh-card" style="margin-bottom:0;">
            <div class="bsh-card-head">
                <div class="bsh-card-ico"><i class="fas fa-comment-dots"></i></div>
                <h6 class="bsh-card-title">Appréciation générale</h6>
            </div>
            <div class="bsh-card-body">
                @if($bulletin->appreciation_generale)
                    <p class="bsh-appreciation">{{ $bulletin->appreciation_generale }}</p>
                @else
                    <p class="bsh-appreciation bsh-muted">Aucune appréciation générale.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- SIGNATURES --}}
    <div class="bsh-card">
        <div class="bsh-card-head">
            <div class="bsh-card-ico"><i class="fas fa-signature"></i></div>
            <h6 class="bsh-card-title">Signatures du bulletin</h6>
        </div>
        <div class="bsh-card-body">
            <div class="bsh-sign-grid">
                @foreach($bshRoles as $role => $label)
                    <div class="bsh-sign" :class="signatures['{{ $role }}'].signed ? 'bsh-sign--signed' : ''">
                        <div class="bsh-sign-head">
                            <i class="fas" :class="signatures['{{ $role }}'].signed ? 'fa-check-circle' : 'fa-pen'"></i>
                            {{ $label }}
                        </div>
                        <div class="bsh-sign-body">
                            <template x-if="signatures['{{ $role }}'].signed">
                                <div>
                                    <div class="bsh-sign-ico bsh-sign-ico--ok"><i class="fas fa-check-circle"></i></div>
                                    <div class="bsh-sign-text">Signé le <span x-text="signatures['{{ $role }}'].date"></span></div>
                                </div>
                            </template>
                            <template x-if="!signatures['{{ $role }}'].signed">
                                <div>
                                    <div class="bsh-sign-ico bsh-sign-ico--no"><i class="fas fa-times-circle"></i></div>
                                    <div class="bsh-sign-text">Non signé</div>
                                    @can('bulletins.edit')
                                        <button type="button" class="bsh-btn bsh-btn--primary" style="margin:0 auto;"
                                                @click="sign('{{ $role }}')" :disabled="busy === '{{ $role }}'">
                                            <i class="fas" :class="busy === '{{ $role }}' ? 'fa-spinner fa-spin' : 'fa-signature'"></i>
                                            <span x-text="busy === '{{ $role }}' ? 'Signature…' : 'Signer'"></span>
                                        </button>
                                    @endcan
                                </div>
                            </template>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ACTIONS --}}
    <div class="bsh-card">
        <div class="bsh-card-body">
            <div class="bsh-actions">
                <div class="bsh-actions-group">
                    @can('bulletins.configure')
                        <a href="{{ route('esbtp.bulletins.config-matieres', ['bulletin' => $bulletin->etudiant_id, 'classe_id' => $bulletin->classe_id, 'periode' => $bulletin->periode, 'annee_universitaire_id' => $bulletin->annee_universitaire_id]) }}" class="bsh-btn bsh-btn--ghost">
                            <i class="fas fa-cogs"></i> Configurer matières
                        </a>
                        <a href="{{ route('esbtp.bulletins.edit-professeurs') }}?bulletin={{ $bulletin->etudiant_id }}&classe_id={{ $bulletin->classe_id }}&periode={{ $bulletin->periode }}&annee_universitaire_id={{ $bulletin->annee_universitaire_id }}" class="bsh-btn bsh-btn--ghost">
                            <i class="fas fa-chalkboard-teacher"></i> Éditer professeurs
                        </a>
                    @endcan
                    @can('bulletins.edit')
                        <a href="{{ route('esbtp.bulletins.edit', $bulletin) }}" class="bsh-btn bsh-btn--ghost">
                            <i class="fas fa-edit"></i> Modifier
                        </a>
                    @endcan
                    <a href="{{ route('esbtp.bulletins.download', $bulletin) }}" class="bsh-btn bsh-btn--ghost" target="_blank" rel="noopener">
                        <i class="fas fa-file-pdf"></i> Générer PDF
                    </a>
                </div>
                <div class="bsh-actions-group">
                    @can('bulletins.delete')
                        <button type="button" class="bsh-btn bsh-btn--danger-ghost" @click="deleteOpen = true">
                            <i class="fas fa-trash"></i> Supprimer
                        </button>
                    @endcan
                    @can('bulletins.edit')
                        <button type="button"
                                class="bsh-btn"
                                :class="isPublished ? 'bsh-btn--warning' : 'bsh-btn--success'"
                                @click="togglePublish()" :disabled="busy === 'publish'">
                            <i class="fas" :class="busy === 'publish' ? 'fa-spinner fa-spin' : (isPublished ? 'fa-eye-slash' : 'fa-eye')"></i>
                            <span x-text="busy === 'publish' ? '…' : (isPublished ? 'Dépublier' : 'Publier')"></span>
                        </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL SUPPRESSION (premium, Alpine) --}}
    @can('bulletins.delete')
    <div class="bsh-modal-overlay" x-show="deleteOpen" x-cloak x-transition.opacity @click.self="deleteOpen = false">
        <div class="bsh-modal" x-transition>
            <div class="bsh-modal-head">
                <i class="fas fa-exclamation-triangle"></i>
                <h5>Confirmer la suppression</h5>
            </div>
            <div class="bsh-modal-body">
                Êtes-vous sûr de vouloir supprimer le bulletin de <strong>{{ $bulletin->etudiant->nom }} {{ $bulletin->etudiant->prenom }}</strong> ?
                <div class="bsh-warn">
                    <i class="fas fa-exclamation-triangle"></i>
                    Action irréversible : le bulletin et tous ses détails associés seront définitivement supprimés.
                </div>
            </div>
            <div class="bsh-modal-foot">
                <button type="button" class="bsh-btn bsh-btn--ghost" @click="deleteOpen = false">Annuler</button>
                <form action="{{ route('esbtp.bulletins.destroy', $bulletin) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bsh-btn bsh-btn--danger"><i class="fas fa-trash"></i> Supprimer définitivement</button>
                </form>
            </div>
        </div>
    </div>
    @endcan

    {{-- TOASTS --}}
    <div class="bsh-toasts">
        <template x-for="t in toasts" :key="t.id">
            <div class="bsh-toast" :class="'bsh-toast--' + t.type">
                <i class="fas" :class="t.type === 'success' ? 'fa-check-circle' : (t.type === 'error' ? 'fa-times-circle' : 'fa-info-circle')"></i>
                <span x-text="t.message"></span>
            </div>
        </template>
    </div>
</div>
@endsection

@push('scripts')
<script>
function bulletinShow(state) {
    return {
        signatures: state.sign,
        signUrls: state.signUrls,
        isPublished: state.isPublished,
        toggleUrl: state.toggleUrl,
        csrf: state.csrf,
        busy: null,
        deleteOpen: false,
        toasts: [],
        _tid: 0,

        toast(type, message) {
            const id = ++this._tid;
            this.toasts.push({ id, type, message });
            setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 4500);
        },

        async sign(role) {
            if (this.busy) return;
            this.busy = role;
            try {
                const res = await fetch(this.signUrls[role], {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success) throw new Error(data.message || ('Erreur HTTP ' + res.status));
                this.signatures[role] = { signed: true, date: data.signed_at };
                this.toast('success', data.message || 'Bulletin signé.');
            } catch (e) {
                this.toast('error', e.message || 'Échec de la signature.');
            } finally {
                this.busy = null;
            }
        },

        async togglePublish() {
            if (this.busy) return;
            this.busy = 'publish';
            try {
                const res = await fetch(this.toggleUrl, {
                    method: 'PUT',
                    headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success) throw new Error(data.message || ('Erreur HTTP ' + res.status));
                this.isPublished = data.is_published;
                this.toast('success', data.message);
            } catch (e) {
                this.toast('error', e.message || 'Échec du changement de statut.');
            } finally {
                this.busy = null;
            }
        },
    };
}
</script>
@endpush
