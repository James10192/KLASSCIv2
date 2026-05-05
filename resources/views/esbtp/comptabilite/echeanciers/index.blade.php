@extends('layouts.app')

@section('title', 'Configuration des echeanciers - KLASSCI')

@push('styles')
<style>
.ech-shell{position:relative}.ech-grid{display:grid;grid-template-columns:minmax(320px,.9fr) minmax(540px,1.35fr);gap:1.1rem;align-items:start}.ech-stack{display:grid;gap:1rem}.ech-card{background:rgba(255,255,255,.95);border:1px solid rgba(148,163,184,.3);border-radius:10px;box-shadow:0 14px 34px rgba(15,23,42,.07);overflow:hidden}.ech-card-head{padding:1rem 1.1rem;border-bottom:1px solid rgba(226,232,240,.95);display:flex;align-items:center;justify-content:space-between;gap:.8rem}.ech-title{margin:0;font-size:.92rem;font-weight:800;color:#0f172a}.ech-sub{font-size:.75rem;color:#64748b}.ech-body{padding:1rem 1.1rem}.ech-scope-list{max-height:390px;overflow:auto;display:grid;gap:.45rem;padding-right:.2rem}.ech-scope{display:grid;grid-template-columns:1fr auto;gap:.75rem;align-items:center;padding:.78rem;border:1px solid #e2e8f0;border-radius:9px;background:#fff;color:inherit;text-decoration:none;transition:background .15s ease,border-color .15s ease,box-shadow .15s ease}.ech-scope:hover{background:#f8fbff;border-color:rgba(4,83,203,.3);box-shadow:0 8px 20px rgba(15,23,42,.06);text-decoration:none}.ech-scope.is-selected{background:rgba(4,83,203,.06);border-color:rgba(4,83,203,.38);box-shadow:inset 3px 0 0 #0453cb}.ech-scope-name{font-weight:800;color:#0f172a;font-size:.82rem}.ech-scope-meta{font-size:.73rem;color:#64748b;margin-top:.12rem}.ech-badges{display:flex;flex-wrap:wrap;gap:.25rem;margin-top:.45rem}.ech-badge{display:inline-flex;align-items:center;gap:.25rem;padding:.18rem .45rem;border-radius:999px;font-size:.66rem;font-weight:800;border:1px solid transparent}.ech-badge-on{background:rgba(16,185,129,.08);color:#047857;border-color:rgba(16,185,129,.24)}.ech-badge-off{background:rgba(100,116,139,.1);color:#334155;border-color:rgba(100,116,139,.22)}.ech-scope-icon{width:2rem;height:2rem;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;background:rgba(4,83,203,.08);color:#0453cb}.ech-empty{border:1px dashed #cbd5e1;border-radius:10px;background:#f8fafc;padding:1rem;color:#64748b;font-size:.78rem}.ech-editor-head{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1rem}.ech-kicker{font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:0;color:#0453cb}.ech-editor-title{font-size:1rem;font-weight:900;color:#0f172a;margin:.15rem 0}.ech-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}.ech-field label,.ech-label{display:block;font-size:.72rem;font-weight:800;color:#334155;margin-bottom:.32rem}.ech-input,.ech-select,.ech-textarea{width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:.52rem .65rem;font-size:.78rem;color:#0f172a;background:#fff;min-height:2.35rem}.ech-select{padding-right:2rem;text-overflow:ellipsis}.ech-textarea{min-height:72px;resize:vertical}.ech-toggle{display:inline-flex;align-items:center;gap:.45rem;font-size:.77rem;font-weight:700;color:#334155}.ech-lines-wrap{overflow:auto;border:1px solid #e2e8f0;border-radius:10px;background:#fff}.ech-lines{width:100%;min-width:940px;border-collapse:collapse;table-layout:fixed}.ech-lines th,.ech-lines td{border-bottom:1px solid #eef2f7;padding:.55rem}.ech-lines th{background:#f8fbff;color:#475569;font-size:.66rem;font-weight:900;text-transform:uppercase;letter-spacing:0;text-align:left}.ech-lines th:nth-child(2),.ech-lines th:nth-child(4),.ech-lines th:nth-child(6),.ech-lines th:nth-child(7),.ech-lines th:nth-child(8),.ech-lines th:nth-child(9){text-align:center}.ech-lines td:nth-child(2),.ech-lines td:nth-child(7),.ech-lines td:nth-child(8),.ech-lines td:nth-child(9){text-align:center}.ech-lines .ech-input,.ech-lines .ech-select{min-height:2.2rem;padding:.45rem .55rem}.ech-actions{display:flex;align-items:center;gap:.55rem;flex-wrap:wrap;margin-top:1rem}.ech-icon-btn{width:2rem;height:2rem;border-radius:8px;border:1px solid #cbd5e1;background:#fff;color:#64748b;display:inline-flex;align-items:center;justify-content:center}.ech-icon-btn:hover{color:#dc2626;border-color:rgba(220,38,38,.25);background:rgba(220,38,38,.06)}.ech-loading{position:absolute;inset:0;z-index:20;display:none;align-items:flex-start;justify-content:center;padding-top:7rem;background:rgba(241,245,249,.66);backdrop-filter:blur(3px)}.ech-shell.is-loading .ech-loading{display:flex}.ech-loader{display:inline-flex;align-items:center;gap:.55rem;padding:.72rem .95rem;border:1px solid rgba(148,163,184,.3);border-radius:10px;background:#fff;color:#0f172a;font-size:.78rem;font-weight:800;box-shadow:0 16px 36px rgba(15,23,42,.12)}.ech-loader i{color:#0453cb}.ech-help{font-size:.74rem;color:#64748b}.ech-help code{color:#be123c;background:rgba(244,63,94,.08);padding:.1rem .25rem;border-radius:5px}@media(max-width:1100px){.ech-grid{grid-template-columns:1fr}.ech-form-grid{grid-template-columns:1fr}}
.ech-grid{grid-template-columns:minmax(430px,.95fr) minmax(560px,1.25fr)}.ech-card-head{padding:.85rem 1rem}.ech-body{padding:.8rem 1rem}.ech-scope-list{max-height:calc(100vh - 315px);min-height:260px;gap:.28rem}.ech-scope{grid-template-columns:1fr 2rem;padding:.52rem .6rem;border-radius:8px}.ech-scope-name{font-size:.78rem}.ech-scope-meta{font-size:.68rem;margin-top:.05rem}.ech-badges{margin-top:.25rem}.ech-scope-icon{width:1.75rem;height:1.75rem}.ech-scope-tools{display:grid;grid-template-columns:1fr auto;gap:.5rem;align-items:center;margin-bottom:.65rem}.ech-search{position:relative}.ech-search i{position:absolute;left:.65rem;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.76rem}.ech-search input{width:100%;height:2.15rem;border:1px solid #dbe3ef;border-radius:8px;padding:.45rem .7rem .45rem 1.9rem;font-size:.76rem;background:#f8fbff;color:#0f172a}.ech-count-pill{display:inline-flex;align-items:center;height:2.15rem;padding:0 .65rem;border-radius:8px;background:#eef5ff;color:#0453cb;font-size:.72rem;font-weight:800;white-space:nowrap}.ech-hidden{display:none!important}@media(max-width:1180px){.ech-grid{grid-template-columns:1fr}.ech-scope-list{max-height:430px}}
.ech-diagnostics{display:grid;grid-template-columns:repeat(6,minmax(110px,1fr));gap:.65rem;margin-bottom:1rem}.ech-diag{background:#fff;border:1px solid rgba(148,163,184,.28);border-radius:10px;padding:.72rem .8rem}.ech-diag-value{font-size:1rem;font-weight:900;color:#0453cb}.ech-diag-label{font-size:.68rem;color:#64748b;font-weight:800;text-transform:uppercase;letter-spacing:0}.ech-filter-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.45rem;margin-bottom:.65rem}.ech-filter-grid .ech-select{min-height:2.15rem;padding:.42rem .6rem;font-size:.75rem}.ech-filter-reset{height:2.15rem;border:1px solid #dbe3ef;border-radius:8px;background:#fff;color:#64748b;font-weight:800;font-size:.74rem}.ech-scope[data-status="unconfigured"]{border-color:rgba(245,158,11,.34);background:linear-gradient(90deg,rgba(245,158,11,.06),#fff 42%)}.ech-state{display:inline-flex;align-items:center;gap:.25rem;font-size:.66rem;font-weight:900;color:#92400e}.ech-panel{border:1px solid #e2e8f0;background:#f8fbff;border-radius:10px;padding:.75rem;margin:.75rem 0}.ech-panel-title{font-size:.78rem;font-weight:900;color:#0f172a;margin-bottom:.55rem}.ech-presets{display:flex;gap:.4rem;flex-wrap:wrap}.ech-chip-btn{border:1px solid rgba(4,83,203,.18);background:#fff;color:#0453cb;border-radius:999px;padding:.35rem .58rem;font-size:.72rem;font-weight:800}.ech-chip-btn:hover{background:#0453cb;color:#fff}.ech-total{display:flex;align-items:center;justify-content:space-between;gap:.8rem;margin:.65rem 0;padding:.55rem .65rem;border-radius:9px;background:#fff;border:1px solid #e2e8f0}.ech-total strong{color:#0f172a}.ech-total.is-bad{border-color:rgba(220,38,38,.35);background:rgba(254,242,242,.72)}.ech-total.is-ok{border-color:rgba(16,185,129,.35);background:rgba(240,253,244,.72)}.ech-preview-list{display:grid;gap:.35rem}.ech-preview-line{display:grid;grid-template-columns:1fr auto;gap:.75rem;padding:.45rem .55rem;border-radius:8px;background:#fff;border:1px solid #e2e8f0;font-size:.75rem}.ech-copy-grid,.ech-sim-grid{display:grid;grid-template-columns:1fr auto;gap:.5rem;align-items:end}.ech-copy-grid{grid-template-columns:1fr auto}.ech-sim-result{margin-top:.55rem;display:grid;gap:.3rem;font-size:.75rem;color:#334155}.ech-sim-result div{display:flex;justify-content:space-between;gap:.75rem;border-bottom:1px dashed #dbe3ef;padding-bottom:.25rem}@media(max-width:1200px){.ech-diagnostics{grid-template-columns:repeat(3,1fr)}.ech-filter-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:680px){.ech-diagnostics,.ech-filter-grid,.ech-copy-grid,.ech-sim-grid{grid-template-columns:1fr}}
.ech-scope-check{display:inline-flex;align-items:center;justify-content:center;margin-right:.35rem}.ech-scope-main{display:flex;align-items:flex-start;gap:.25rem}.ech-bulk{display:grid;grid-template-columns:1fr auto auto;gap:.45rem;align-items:center;margin-bottom:.65rem}.ech-bulk .ech-select{height:2.15rem;font-size:.75rem}.ech-bulk button{height:2.15rem;padding:.35rem .6rem;font-size:.72rem}@media(max-width:680px){.ech-bulk{grid-template-columns:1fr 1fr}}
.ech-help-button{display:inline-flex;align-items:center;gap:.45rem;border-color:rgba(4,83,203,.22)!important;background:#fff!important;color:#0453cb!important}.ech-help-modal{position:fixed;inset:0;z-index:1055;display:none;align-items:center;justify-content:center;padding:1.25rem;background:rgba(15,23,42,.45);backdrop-filter:blur(4px)}.ech-help-modal.is-open{display:flex}.ech-help-dialog{width:min(920px,100%);max-height:min(760px,92vh);display:flex;flex-direction:column;background:#fff;border:1px solid rgba(148,163,184,.3);border-radius:12px;box-shadow:0 28px 70px rgba(15,23,42,.28);overflow:hidden}.ech-help-head{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;padding:1rem 1.15rem;border-bottom:1px solid #e2e8f0;background:linear-gradient(180deg,#f8fbff,#fff)}.ech-help-title{margin:0;font-size:1rem;font-weight:900;color:#0f172a}.ech-help-subtitle{margin:.18rem 0 0;color:#64748b;font-size:.78rem}.ech-help-close{width:2.15rem;height:2.15rem;border-radius:8px;border:1px solid #dbe3ef;background:#fff;color:#64748b;display:inline-flex;align-items:center;justify-content:center}.ech-help-close:hover{color:#dc2626;border-color:rgba(220,38,38,.28);background:#fff5f5}.ech-help-body{overflow:auto;padding:1rem 1.15rem}.ech-help-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem}.ech-help-section{border:1px solid #e2e8f0;border-radius:10px;background:#f8fbff;padding:.8rem}.ech-help-section h4{margin:0 0 .45rem;font-size:.82rem;font-weight:900;color:#0f172a}.ech-help-section ul{margin:0;padding-left:1rem;color:#334155;font-size:.77rem;line-height:1.55}.ech-help-section li+li{margin-top:.25rem}.ech-help-flow{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.5rem;margin-bottom:.85rem}.ech-help-step{border:1px solid rgba(4,83,203,.16);border-radius:10px;background:#fff;padding:.65rem}.ech-help-step strong{display:block;color:#0453cb;font-size:.75rem;margin-bottom:.2rem}.ech-help-step span{display:block;color:#475569;font-size:.72rem;line-height:1.35}.ech-help-note{margin-top:.85rem;border-left:3px solid #0453cb;background:#eef5ff;color:#1e3a8a;border-radius:8px;padding:.72rem .85rem;font-size:.77rem;line-height:1.45}@media(max-width:760px){.ech-help-grid,.ech-help-flow{grid-template-columns:1fr}.ech-help-dialog{max-height:94vh}.ech-help-modal{padding:.65rem}}
.ech-tour-button{display:inline-flex;align-items:center;gap:.45rem}.ech-tour-mask{position:fixed;inset:0;z-index:1060;background:rgba(15,23,42,.52);backdrop-filter:blur(1px)}.ech-tour-highlight{position:relative!important;z-index:1061!important;box-shadow:0 0 0 4px rgba(4,83,203,.28),0 22px 55px rgba(15,23,42,.24)!important}.ech-tour-pop{position:fixed;z-index:1062;width:min(360px,calc(100vw - 24px));background:#fff;border:1px solid rgba(148,163,184,.34);border-radius:12px;box-shadow:0 28px 70px rgba(15,23,42,.28);padding:1rem}.ech-tour-kicker{font-size:.68rem;font-weight:900;color:#0453cb;text-transform:uppercase;letter-spacing:0;margin-bottom:.3rem}.ech-tour-title{margin:0;color:#0f172a;font-size:.95rem;font-weight:900}.ech-tour-text{margin:.45rem 0 0;color:#475569;font-size:.78rem;line-height:1.45}.ech-tour-actions{display:flex;align-items:center;justify-content:space-between;gap:.65rem;margin-top:.85rem}.ech-tour-actions-left,.ech-tour-actions-right{display:flex;align-items:center;gap:.45rem}.ech-tour-btn{height:2rem;border-radius:8px;border:1px solid #dbe3ef;background:#fff;color:#334155;font-size:.73rem;font-weight:800;padding:0 .65rem}.ech-tour-btn.primary{background:#0453cb;border-color:#0453cb;color:#fff}.ech-tour-btn:hover{border-color:rgba(4,83,203,.35)}.ech-tour-dots{display:flex;gap:.22rem}.ech-tour-dot{width:.42rem;height:.42rem;border-radius:999px;background:#cbd5e1}.ech-tour-dot.is-active{background:#0453cb}.ech-tour-skip{color:#64748b;background:transparent;border:0;font-size:.73rem;font-weight:800}.ech-tour-demo-note{border:1px dashed rgba(4,83,203,.32);background:#f8fbff;color:#0453cb;border-radius:8px;padding:.45rem .55rem;font-size:.72rem;font-weight:800}.ech-tour-demo .ech-scope{border-style:dashed;background:linear-gradient(90deg,rgba(4,83,203,.05),#fff 45%)}@media(max-width:760px){.ech-tour-pop{left:12px!important;right:12px!important;top:auto!important;bottom:14px!important;width:auto}.ech-tour-highlight{box-shadow:0 0 0 3px rgba(4,83,203,.32)!important}}
</style>
@endpush

@section('content')
@php
    $statusLabels = ['all' => 'Tous les statuts', 'affecté' => 'Affecte', 'réaffecté' => 'Reaffecte', 'non_affecté' => 'Non affecte'];
    $amountModeLabels = ['percent' => 'Pourcentage', 'fixed' => 'Montant fixe'];
    $dueModeLabels = ['days_after_inscription' => 'Apres inscription', 'fixed_mm_dd' => 'Date fixe'];
@endphp

<div class="dashboard-acasi">
    <div class="main-content ech-shell" data-ech-page>
        <div class="ech-loading" data-ech-loading>
            <div class="ech-loader"><i class="fas fa-circle-notch fa-spin"></i><span>Chargement du scope</span></div>
        </div>

        <div class="dashboard-header">
            <div class="header-left">
                <h1>Echeanciers de paiement</h1>
                <p class="header-subtitle">Tranches de paiement par frais obligatoire et optionnel.</p>
            </div>
            <div class="header-actions">
                <button type="button" class="btn-acasi primary ech-tour-button" data-ech-tour-open><i class="fas fa-route"></i>Guide</button>
                <button type="button" class="btn-acasi secondary ech-help-button" data-ech-help-open><i class="fas fa-question-circle"></i>Aide</button>
                <a href="{{ route('esbtp.frais.configure') }}" class="btn-acasi secondary"><i class="fas fa-layer-group"></i>Frais par classe</a>
                <a href="{{ route('esbtp.frais.optional-config') }}" class="btn-acasi secondary"><i class="fas fa-puzzle-piece"></i>Optionnels</a>
                <a href="{{ route('esbtp.comptabilite.dashboard') }}" class="btn-acasi primary"><i class="fas fa-chart-line"></i>Dashboard</a>
            </div>
        </div>

        <div class="ech-help-modal" data-ech-help-modal role="dialog" aria-modal="true" aria-labelledby="ech-help-title">
            <div class="ech-help-dialog">
                <div class="ech-help-head">
                    <div>
                        <h2 class="ech-help-title" id="ech-help-title">Comprendre les echeanciers</h2>
                        <p class="ech-help-subtitle">Cette page sert a definir quand chaque tranche devient exigible, par type de frais et statut d'affectation.</p>
                    </div>
                    <button type="button" class="ech-help-close" data-ech-help-close aria-label="Fermer l'aide"><i class="fas fa-times"></i></button>
                </div>
                <div class="ech-help-body">
                    <div class="ech-help-flow">
                        <div class="ech-help-step"><strong>1. Choisir un scope</strong><span>Selectionnez un frais obligatoire ou une option a gauche.</span></div>
                        <div class="ech-help-step"><strong>2. Choisir le statut</strong><span>Regle globale ou specifique aux affectes, reaffectes, non affectes.</span></div>
                        <div class="ech-help-step"><strong>3. Definir les tranches</strong><span>Pourcentage ou montant fixe avec une date relative ou fixe.</span></div>
                        <div class="ech-help-step"><strong>4. Verifier l'impact</strong><span>Utilisez la previsualisation ou la simulation inscription.</span></div>
                    </div>

                    <div class="ech-help-grid">
                        <div class="ech-help-section">
                            <h4>Diagnostics en haut</h4>
                            <ul>
                                <li><strong>Scopes</strong> : nombre total de frais ou options visibles.</li>
                                <li><strong>Sans regle</strong> : le systeme applique encore le fallback.</li>
                                <li><strong>A verifier</strong> : tranche inactive vide ou total pourcentage different de 100%.</li>
                            </ul>
                        </div>
                        <div class="ech-help-section">
                            <h4>Liste de gauche</h4>
                            <ul>
                                <li>Les filtres reduisent la liste sans recharger la page.</li>
                                <li>Les badges indiquent les regles deja configurees par statut.</li>
                                <li>Le badge fallback signifie qu'aucune regle active specifique n'existe encore.</li>
                            </ul>
                        </div>
                        <div class="ech-help-section">
                            <h4>Editeur de regle</h4>
                            <ul>
                                <li>Un total en pourcentage doit faire exactement 100%.</li>
                                <li>Une date relative part de la date d'inscription.</li>
                                <li>Une date fixe utilise le format <strong>MM-DD</strong>, par exemple <strong>10-15</strong>.</li>
                            </ul>
                        </div>
                        <div class="ech-help-section">
                            <h4>Actions rapides</h4>
                            <ul>
                                <li>Les presets remplissent les tranches les plus courantes.</li>
                                <li>La copie propage une regle vers une filiere, un niveau ou les scopes sans regle.</li>
                                <li>L'activation en masse agit seulement sur les scopes coches.</li>
                            </ul>
                        </div>
                        <div class="ech-help-section">
                            <h4>Previsualisation</h4>
                            <ul>
                                <li>Elle calcule les montants et dates sans enregistrer.</li>
                                <li>Changez le montant de simulation pour tester BTS, Licence, Master, etc.</li>
                                <li>Elle sert a verifier rapidement la coherence avant sauvegarde.</li>
                            </ul>
                        </div>
                        <div class="ech-help-section">
                            <h4>Simulation inscription</h4>
                            <ul>
                                <li>Entrez un ID d'inscription reel pour voir l'attendu a date.</li>
                                <li>Le resultat tient compte des paiements deja connus.</li>
                                <li>C'est le meilleur controle avant relances et analytics.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="ech-help-note">
                        Regle pratique : configurez d'abord les frais obligatoires les plus frequents, testez avec une inscription representative, puis copiez la regle vers les scopes similaires.
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert-kl alert-kl-success mb-3" style="border-radius:10px;padding:12px 14px;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-check-circle"></i><span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="alert-kl alert-kl-danger mb-3" style="border-radius:10px;padding:12px 14px;display:flex;align-items:flex-start;gap:8px;">
                <i class="fas fa-exclamation-triangle" style="margin-top:2px;"></i>
                <div>
                    <strong>Veuillez corriger les erreurs du formulaire.</strong>
                    <ul style="margin:.35rem 0 0 1rem;padding:0;">
                        @foreach($errors->all() as $error)
                            <li style="font-size:.8rem;">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="ech-diagnostics">
            <div class="ech-diag"><div class="ech-diag-value">{{ $scopeDiagnostics['total'] }}</div><div class="ech-diag-label">Scopes</div></div>
            <div class="ech-diag"><div class="ech-diag-value">{{ $scopeDiagnostics['configured'] }}</div><div class="ech-diag-label">Configures</div></div>
            <div class="ech-diag"><div class="ech-diag-value">{{ $scopeDiagnostics['unconfigured'] }}</div><div class="ech-diag-label">Sans regle</div></div>
            <div class="ech-diag"><div class="ech-diag-value">{{ $scopeDiagnostics['active_rules'] }}</div><div class="ech-diag-label">Actives</div></div>
            <div class="ech-diag"><div class="ech-diag-value">{{ $scopeDiagnostics['inactive_rules'] }}</div><div class="ech-diag-label">Inactives</div></div>
            <div class="ech-diag"><div class="ech-diag-value">{{ $scopeDiagnostics['invalid_totals'] }}</div><div class="ech-diag-label">A verifier</div></div>
        </div>

        <div class="ech-grid">
            <div class="ech-stack">
                <div class="ech-card">
                    <div class="ech-card-head">
                        <h3 class="ech-title">Configurations obligatoires</h3>
                        <span class="ech-sub">{{ $configurations->count() }} scopes</span>
                    </div>
                    <div class="ech-body">
                        <div class="ech-scope-tools">
                            <label class="ech-search">
                                <i class="fas fa-search"></i>
                                <input type="search" data-scope-search data-target="mandatory" placeholder="Rechercher frais, filiere, niveau">
                            </label>
                            <span class="ech-count-pill" data-scope-count="mandatory">{{ $configurations->count() }}</span>
                        </div>
                        <div class="ech-filter-grid" data-filter-group="mandatory">
                            <select class="ech-select" data-scope-filter="filiere"><option value="">Toutes filieres</option>@foreach($filieres as $filiere)<option value="{{ $filiere->id }}">{{ $filiere->name }}</option>@endforeach</select>
                            <select class="ech-select" data-scope-filter="niveau"><option value="">Tous niveaux</option>@foreach($niveaux as $niveau)<option value="{{ $niveau->id }}">{{ $niveau->name }}</option>@endforeach</select>
                            <select class="ech-select" data-scope-filter="category"><option value="">Tous frais</option>@foreach($fraisCategories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
                            <select class="ech-select" data-scope-filter="status"><option value="">Tous etats</option><option value="configured">Configures</option><option value="unconfigured">Sans regle</option><option value="inactive">Avec inactive</option><option value="invalid">Total a verifier</option></select>
                        </div>
                        <form method="POST" action="{{ route('esbtp.comptabilite.echeanciers.bulk-status') }}" class="ech-bulk" data-bulk-form="mandatory">
                            @csrf
                            <input type="hidden" name="affectation_status" value="{{ $selectedStatus }}">
                            <select name="is_active" class="ech-select"><option value="1">Activer selection</option><option value="0">Desactiver selection</option></select>
                            <button type="submit" class="btn-acasi secondary"><i class="fas fa-toggle-on"></i>Appliquer</button>
                            <button type="button" class="btn-acasi secondary" data-select-visible="mandatory"><i class="fas fa-check-square"></i>Visibles</button>
                        </form>
                        <div class="ech-scope-list">
                            @forelse($configurations as $configuration)
                                @php
                                    $scopeKey = 'configuration:' . $configuration->id;
                                    $scopeRules = $rulesByScope->get($scopeKey, collect());
                                    $isSelected = $selectedScopeType === 'configuration' && (int) $selectedScopeId === (int) $configuration->id;
                                @endphp
                                @php
                                    $hasRules = $scopeRules->isNotEmpty();
                                    $hasInactive = $scopeRules->where('is_active', false)->isNotEmpty();
                                    $hasInvalid = $scopeRules->filter(function ($rule) {
                                        $active = $rule->lines->where('is_active', true);
                                        $percent = $active->where('amount_mode', 'percent');
                                        return $active->isEmpty() || ($percent->isNotEmpty() && abs((float) $percent->sum('amount_value') - 100) >= 0.01);
                                    })->isNotEmpty();
                                    $scopeState = $hasInvalid ? 'invalid' : ($hasInactive ? 'inactive' : ($hasRules ? 'configured' : 'unconfigured'));
                                @endphp
                                <a class="ech-scope {{ $isSelected ? 'is-selected' : '' }}" data-scope-item="mandatory" data-filiere="{{ $configuration->filiere_id }}" data-niveau="{{ $configuration->niveau_id }}" data-category="{{ $configuration->frais_category_id }}" data-status="{{ $scopeState }}" data-search-text="{{ \Illuminate\Support\Str::lower(($configuration->fraisCategory->name ?? '') . ' ' . ($configuration->filiere->name ?? '') . ' ' . ($configuration->niveau->name ?? '')) }}" data-ech-scope-link href="{{ route('esbtp.comptabilite.echeanciers.index', ['scope_type' => 'configuration', 'scope_id' => $configuration->id, 'affectation_status' => $selectedStatus]) }}">
                                    <span class="ech-scope-main">
                                        <span class="ech-scope-check"><input type="checkbox" data-scope-checkbox="mandatory" data-target-value="configuration:{{ $configuration->id }}" onclick="event.stopPropagation()"></span>
                                        <span>
                                        <span class="ech-scope-name">{{ $configuration->fraisCategory->name ?? 'Frais' }}</span>
                                        <span class="ech-scope-meta d-block">{{ $configuration->filiere->name ?? 'N/A' }} / {{ $configuration->niveau->name ?? 'N/A' }}</span>
                                        <span class="ech-badges">
                                            @forelse($scopeRules as $rule)
                                                <span class="ech-badge {{ $rule->is_active ? 'ech-badge-on' : 'ech-badge-off' }}">{{ $statusLabels[$rule->affectation_status] ?? $rule->affectation_status }} · {{ $rule->lines->count() }}</span>
                                            @empty
                                                <span class="ech-state"><i class="fas fa-clock"></i>Fallback J+{{ $configuration->payment_deadline_days ?? 30 }}</span>
                                            @endforelse
                                        </span>
                                        </span>
                                    </span>
                                    <span class="ech-scope-icon"><i class="fas fa-sliders-h"></i></span>
                                </a>
                            @empty
                                <div class="ech-empty">Aucune configuration active trouvee.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="ech-card">
                    <div class="ech-card-head">
                        <h3 class="ech-title">Assignations optionnelles</h3>
                        <span class="ech-sub">{{ $optionAssignments->count() }} scopes</span>
                    </div>
                    <div class="ech-body">
                        <div class="ech-scope-tools">
                            <label class="ech-search">
                                <i class="fas fa-search"></i>
                                <input type="search" data-scope-search data-target="optional" placeholder="Rechercher option, filiere, niveau">
                            </label>
                            <span class="ech-count-pill" data-scope-count="optional">{{ $optionAssignments->count() }}</span>
                        </div>
                        <div class="ech-filter-grid" data-filter-group="optional">
                            <select class="ech-select" data-scope-filter="filiere"><option value="">Toutes filieres</option>@foreach($filieres as $filiere)<option value="{{ $filiere->id }}">{{ $filiere->name }}</option>@endforeach</select>
                            <select class="ech-select" data-scope-filter="niveau"><option value="">Tous niveaux</option>@foreach($niveaux as $niveau)<option value="{{ $niveau->id }}">{{ $niveau->name }}</option>@endforeach</select>
                            <select class="ech-select" data-scope-filter="category"><option value="">Tous frais</option>@foreach($fraisCategories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
                            <select class="ech-select" data-scope-filter="status"><option value="">Tous etats</option><option value="configured">Configures</option><option value="unconfigured">Sans regle</option><option value="inactive">Avec inactive</option><option value="invalid">Total a verifier</option></select>
                        </div>
                        <form method="POST" action="{{ route('esbtp.comptabilite.echeanciers.bulk-status') }}" class="ech-bulk" data-bulk-form="optional">
                            @csrf
                            <input type="hidden" name="affectation_status" value="{{ $selectedStatus }}">
                            <select name="is_active" class="ech-select"><option value="1">Activer selection</option><option value="0">Desactiver selection</option></select>
                            <button type="submit" class="btn-acasi secondary"><i class="fas fa-toggle-on"></i>Appliquer</button>
                            <button type="button" class="btn-acasi secondary" data-select-visible="optional"><i class="fas fa-check-square"></i>Visibles</button>
                        </form>
                        <div class="ech-scope-list">
                            @forelse($optionAssignments as $assignment)
                                @php
                                    $scopeKey = 'option_assignment:' . $assignment->id;
                                    $scopeRules = $rulesByScope->get($scopeKey, collect());
                                    $isSelected = $selectedScopeType === 'option_assignment' && (int) $selectedScopeId === (int) $assignment->id;
                                @endphp
                                @php
                                    $hasRules = $scopeRules->isNotEmpty();
                                    $hasInactive = $scopeRules->where('is_active', false)->isNotEmpty();
                                    $hasInvalid = $scopeRules->filter(function ($rule) {
                                        $active = $rule->lines->where('is_active', true);
                                        $percent = $active->where('amount_mode', 'percent');
                                        return $active->isEmpty() || ($percent->isNotEmpty() && abs((float) $percent->sum('amount_value') - 100) >= 0.01);
                                    })->isNotEmpty();
                                    $scopeState = $hasInvalid ? 'invalid' : ($hasInactive ? 'inactive' : ($hasRules ? 'configured' : 'unconfigured'));
                                @endphp
                                <a class="ech-scope {{ $isSelected ? 'is-selected' : '' }}" data-scope-item="optional" data-filiere="{{ $assignment->filiere_id }}" data-niveau="{{ $assignment->niveau_id }}" data-category="{{ $assignment->option->frais_category_id ?? '' }}" data-status="{{ $scopeState }}" data-search-text="{{ \Illuminate\Support\Str::lower(($assignment->option->fraisCategory->name ?? '') . ' ' . ($assignment->option->name ?? '') . ' ' . ($assignment->display_label ?? '')) }}" data-ech-scope-link href="{{ route('esbtp.comptabilite.echeanciers.index', ['scope_type' => 'option_assignment', 'scope_id' => $assignment->id, 'affectation_status' => $selectedStatus]) }}">
                                    <span class="ech-scope-main">
                                        <span class="ech-scope-check"><input type="checkbox" data-scope-checkbox="optional" data-target-value="option_assignment:{{ $assignment->id }}" onclick="event.stopPropagation()"></span>
                                        <span>
                                        <span class="ech-scope-name">{{ $assignment->option->fraisCategory->name ?? 'Option' }} - {{ $assignment->option->name ?? 'N/A' }}</span>
                                        <span class="ech-scope-meta d-block">{{ $assignment->display_label }}</span>
                                        <span class="ech-badges">
                                            @forelse($scopeRules as $rule)
                                                <span class="ech-badge {{ $rule->is_active ? 'ech-badge-on' : 'ech-badge-off' }}">{{ $statusLabels[$rule->affectation_status] ?? $rule->affectation_status }} · {{ $rule->lines->count() }}</span>
                                            @empty
                                                <span class="ech-state"><i class="fas fa-clock"></i>Fallback J+30</span>
                                            @endforelse
                                        </span>
                                        </span>
                                    </span>
                                    <span class="ech-scope-icon"><i class="fas fa-sliders-h"></i></span>
                                </a>
                            @empty
                                <div class="ech-empty">Aucune assignation active trouvee.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="ech-card">
                <div class="ech-card-head">
                    <h3 class="ech-title">Editeur de regle</h3>
                    @if($selectedScopeDescriptor)<span class="ech-sub">{{ $selectedScopeDescriptor['title'] }}</span>@endif
                </div>
                <div class="ech-body">
                    @if(!$selectedScopeType || !$selectedScopeId)
                        <div class="ech-empty">Selectionnez un scope a gauche pour creer ou modifier une regle d'echeancier.</div>
                    @else
                        @php
                            $initialLines = old('lines');
                            if (!is_array($initialLines) || count($initialLines) === 0) {
                                $initialLines = $selectedRule
                                    ? $selectedRule->lines->map(fn ($line) => [
                                        'label' => $line->label,
                                        'sort_order' => $line->sort_order,
                                        'amount_mode' => $line->amount_mode,
                                        'amount_value' => $line->amount_value,
                                        'due_mode' => $line->due_mode,
                                        'due_value' => $line->due_value,
                                        'grace_days' => $line->grace_days,
                                        'is_active' => $line->is_active,
                                    ])->toArray()
                                    : [[
                                        'label' => 'Tranche 1',
                                        'sort_order' => 1,
                                        'amount_mode' => 'percent',
                                        'amount_value' => 100,
                                        'due_mode' => 'days_after_inscription',
                                        'due_value' => 30,
                                        'grace_days' => 0,
                                        'is_active' => true,
                                    ]];
                            }
                        @endphp

                        <div class="ech-editor-head">
                            <div>
                                <div class="ech-kicker">{{ $selectedScopeDescriptor['title'] ?? 'Scope selectionne' }}</div>
                                <div class="ech-editor-title">{{ $selectedScopeDescriptor['subtitle'] ?? '' }}</div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('esbtp.comptabilite.echeanciers.upsert') }}">
                            @csrf
                            <input type="hidden" name="scope_type" value="{{ $selectedScopeType }}">
                            <input type="hidden" name="scope_id" value="{{ $selectedScopeId }}">

                            <div class="ech-form-grid mb-2">
                                <div class="ech-field">
                                    <label>Statut d'affectation</label>
                                    <select name="affectation_status" class="ech-select">
                                        @foreach(['all', 'affecté', 'réaffecté', 'non_affecté'] as $status)
                                            <option value="{{ $status }}" {{ old('affectation_status', $selectedStatus) === $status ? 'selected' : '' }}>{{ $statusLabels[$status] ?? $status }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="ech-field">
                                    <label>Priorite</label>
                                    <input type="number" name="priority" class="ech-input" min="1" max="9999" value="{{ old('priority', $selectedRule->priority ?? 100) }}">
                                </div>
                            </div>

                            <div class="ech-form-grid mb-2">
                                <div class="ech-field">
                                    <label>Actif des le</label>
                                    <input type="date" name="effective_from" class="ech-input" value="{{ old('effective_from', optional($selectedRule?->effective_from)->format('Y-m-d')) }}">
                                </div>
                                <div class="ech-field">
                                    <label>Actif jusqu'au</label>
                                    <input type="date" name="effective_to" class="ech-input" value="{{ old('effective_to', optional($selectedRule?->effective_to)->format('Y-m-d')) }}">
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="ech-toggle"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $selectedRule->is_active ?? true) ? 'checked' : '' }}>Regle active</label>
                            </div>

                            <div class="ech-field mb-3">
                                <label>Notes</label>
                                <textarea name="notes" class="ech-textarea" placeholder="Commentaire interne optionnel">{{ old('notes', $selectedRule->notes ?? '') }}</textarea>
                            </div>

                            <div class="ech-panel">
                                <div class="ech-panel-title">Presets rapides</div>
                                <div class="ech-presets">
                                    <button type="button" class="ech-chip-btn" data-preset="single">Paiement unique</button>
                                    <button type="button" class="ech-chip-btn" data-preset="half">50 / 50</button>
                                    <button type="button" class="ech-chip-btn" data-preset="three">30 / 40 / 30</button>
                                    <button type="button" class="ech-chip-btn" data-preset="monthly">Mensuel x4</button>
                                    <button type="button" class="ech-chip-btn" data-preset="quarterly">Trimestriel</button>
                                </div>
                            </div>

                            <div class="mb-2" style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;">
                                <label class="ech-label" style="margin:0;">Tranches</label>
                                <button type="button" data-add-line class="btn-acasi secondary" style="padding:.38rem .68rem;font-size:.72rem;"><i class="fas fa-plus"></i>Ajouter</button>
                            </div>

                            <div class="ech-lines-wrap">
                                <table class="ech-lines">
                                    <thead>
                                        <tr>
                                            <th style="width:145px;">Libelle</th>
                                            <th style="width:75px;">Ordre</th>
                                            <th style="width:145px;">Montant</th>
                                            <th style="width:115px;">Valeur</th>
                                            <th style="width:160px;">Echeance</th>
                                            <th style="width:115px;">Valeur</th>
                                            <th style="width:90px;">Grace</th>
                                            <th style="width:70px;">Actif</th>
                                            <th style="width:62px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody data-lines-body>
                                        @foreach($initialLines as $index => $line)
                                            <tr>
                                                <td><input type="text" class="ech-input" name="lines[{{ $index }}][label]" value="{{ $line['label'] ?? '' }}" required></td>
                                                <td><input type="number" class="ech-input" name="lines[{{ $index }}][sort_order]" min="1" max="99" value="{{ $line['sort_order'] ?? ($index + 1) }}"></td>
                                                <td><select class="ech-select" name="lines[{{ $index }}][amount_mode]"><option value="percent" {{ ($line['amount_mode'] ?? '') === 'percent' ? 'selected' : '' }}>{{ $amountModeLabels['percent'] }}</option><option value="fixed" {{ ($line['amount_mode'] ?? '') === 'fixed' ? 'selected' : '' }}>{{ $amountModeLabels['fixed'] }}</option></select></td>
                                                <td><input type="number" step="0.01" min="0" class="ech-input" name="lines[{{ $index }}][amount_value]" value="{{ $line['amount_value'] ?? '' }}" required></td>
                                                <td><select class="ech-select" name="lines[{{ $index }}][due_mode]"><option value="days_after_inscription" {{ ($line['due_mode'] ?? '') === 'days_after_inscription' ? 'selected' : '' }}>{{ $dueModeLabels['days_after_inscription'] }}</option><option value="fixed_mm_dd" {{ ($line['due_mode'] ?? '') === 'fixed_mm_dd' ? 'selected' : '' }}>{{ $dueModeLabels['fixed_mm_dd'] }}</option></select></td>
                                                <td><input type="text" class="ech-input" name="lines[{{ $index }}][due_value]" value="{{ $line['due_value'] ?? '' }}" required></td>
                                                <td><input type="number" min="0" max="365" class="ech-input" name="lines[{{ $index }}][grace_days]" value="{{ $line['grace_days'] ?? 0 }}"></td>
                                                <td><input type="checkbox" name="lines[{{ $index }}][is_active]" value="1" {{ !isset($line['is_active']) || $line['is_active'] ? 'checked' : '' }}></td>
                                                <td><button type="button" class="ech-icon-btn" data-remove-line title="Retirer"><i class="fas fa-times"></i></button></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="ech-total" data-total-box>
                                <span><strong>Total pourcentage</strong> <span data-percent-total>0%</span></span>
                                <span data-total-message>Les tranches en pourcentage doivent totaliser 100%.</span>
                            </div>

                            <div class="ech-panel">
                                <div class="ech-panel-title">Previsualisation</div>
                                <div class="ech-form-grid mb-2">
                                    <div class="ech-field">
                                        <label>Montant de simulation</label>
                                        <input type="number" class="ech-input" data-preview-amount value="{{ round($selectedPreviewAmount) }}" min="0" step="1000">
                                    </div>
                                    <div class="ech-field">
                                        <label>Date de reference</label>
                                        <input type="date" class="ech-input" data-preview-date value="{{ now()->toDateString() }}">
                                    </div>
                                </div>
                                <div class="ech-preview-list" data-preview-list></div>
                            </div>

                            <div class="ech-actions">
                                <button type="submit" class="btn-acasi primary"><i class="fas fa-save"></i>Enregistrer la regle</button>
                                <span class="ech-help">Pour une date fixe, format attendu: <code>MM-DD</code> (ex: <code>10-15</code>).</span>
                            </div>
                        </form>

                        @if($selectedRule)
                            <div class="ech-panel">
                                <div class="ech-panel-title">Copier cette regle</div>
                                <form method="POST" action="{{ route('esbtp.comptabilite.echeanciers.copy') }}" class="ech-copy-grid">
                                    @csrf
                                    <input type="hidden" name="source_scope_type" value="{{ $selectedScopeType }}">
                                    <input type="hidden" name="source_scope_id" value="{{ $selectedScopeId }}">
                                    <input type="hidden" name="affectation_status" value="{{ $selectedStatus }}">
                                    <select name="copy_mode" class="ech-select">
                                        <option value="same_filiere">Vers la meme filiere</option>
                                        <option value="same_niveau">Vers le meme niveau</option>
                                        <option value="all_unconfigured">Vers toutes les configurations sans regle</option>
                                    </select>
                                    <button type="submit" class="btn-acasi secondary"><i class="fas fa-copy"></i>Copier</button>
                                </form>
                            </div>
                        @endif

                        <div class="ech-panel">
                            <div class="ech-panel-title">Simulation inscription</div>
                            <form class="ech-sim-grid" data-sim-form action="{{ route('esbtp.comptabilite.echeanciers.simulate') }}">
                                <div class="ech-field">
                                    <label>ID inscription</label>
                                    <input type="number" class="ech-input" name="inscription_id" min="1" placeholder="Ex: 1250">
                                </div>
                                <button type="submit" class="btn-acasi secondary"><i class="fas fa-vial"></i>Simuler</button>
                            </form>
                            <div class="ech-sim-result" data-sim-result></div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    function initEcheanciersPage(root) {
        root = root || document;
        const shell = root.matches && root.matches('[data-ech-page]') ? root : document.querySelector('[data-ech-page]');
        const linesBody = shell ? shell.querySelector('[data-lines-body]') : null;
        const addBtn = shell ? shell.querySelector('[data-add-line]') : null;

        if (addBtn && linesBody && !addBtn.dataset.bound) {
            addBtn.dataset.bound = '1';
            addBtn.addEventListener('click', function () {
                const index = linesBody.querySelectorAll('tr').length;
                linesBody.insertAdjacentHTML('beforeend', lineRow(index));
                initEcheanciersPage(root);
                updateRuleDiagnostics(root);
            });
        }

        if (linesBody && !linesBody.dataset.bound) {
            linesBody.dataset.bound = '1';
            linesBody.addEventListener('click', function (event) {
                const button = event.target.closest('[data-remove-line]');
                if (!button || linesBody.querySelectorAll('tr').length <= 1) return;
                button.closest('tr').remove();
                updateRuleDiagnostics(root);
            });
        }

        root.querySelectorAll('[data-scope-search]').forEach(function (input) {
            if (input.dataset.bound) return;
            input.dataset.bound = '1';
            input.addEventListener('input', function () {
                filterScopes(root, input.dataset.target, input.value);
            });
        });

        root.querySelectorAll('[data-scope-filter]').forEach(function (select) {
            if (select.dataset.bound) return;
            select.dataset.bound = '1';
            select.addEventListener('change', function () {
                const group = select.closest('[data-filter-group]');
                if (group) applyStructuredFilters(root, group.dataset.filterGroup);
            });
        });

        root.querySelectorAll('[data-preset]').forEach(function (button) {
            if (button.dataset.bound) return;
            button.dataset.bound = '1';
            button.addEventListener('click', function () {
                applyPreset(root, button.dataset.preset);
            });
        });

        root.querySelectorAll('[data-lines-body] input, [data-lines-body] select, [data-preview-amount], [data-preview-date]').forEach(function (field) {
            if (field.dataset.calcBound) return;
            field.dataset.calcBound = '1';
            field.addEventListener('input', function () {
                updateRuleDiagnostics(root);
            });
            field.addEventListener('change', function () {
                updateRuleDiagnostics(root);
            });
        });

        const simForm = root.querySelector('[data-sim-form]');
        if (simForm && !simForm.dataset.bound) {
            simForm.dataset.bound = '1';
            simForm.addEventListener('submit', function (event) {
                event.preventDefault();
                simulateInscription(root, simForm);
            });
        }

        root.querySelectorAll('[data-select-visible]').forEach(function (button) {
            if (button.dataset.bound) return;
            button.dataset.bound = '1';
            button.addEventListener('click', function () {
                const target = button.dataset.selectVisible;
                root.querySelectorAll(`[data-scope-checkbox="${target}"]`).forEach(function (checkbox) {
                    const item = checkbox.closest('[data-scope-item]');
                    checkbox.checked = item && !item.classList.contains('ech-hidden');
                });
            });
        });

        root.querySelectorAll('[data-bulk-form]').forEach(function (form) {
            if (form.dataset.bound) return;
            form.dataset.bound = '1';
            form.addEventListener('submit', function (event) {
                form.querySelectorAll('[data-generated-target]').forEach(el => el.remove());
                const target = form.dataset.bulkForm;
                const selected = Array.from(root.querySelectorAll(`[data-scope-checkbox="${target}"]:checked`));
                if (!selected.length) {
                    event.preventDefault();
                    return;
                }
                selected.forEach(function (checkbox) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'targets[]';
                    input.value = checkbox.dataset.targetValue;
                    input.dataset.generatedTarget = '1';
                    form.appendChild(input);
                });
            });
        });

        root.querySelectorAll('[data-ech-help-open]').forEach(function (button) {
            if (button.dataset.bound) return;
            button.dataset.bound = '1';
            button.addEventListener('click', function () {
                openHelpModal(root);
            });
        });

        root.querySelectorAll('[data-ech-help-close]').forEach(function (button) {
            if (button.dataset.bound) return;
            button.dataset.bound = '1';
            button.addEventListener('click', function () {
                closeHelpModal(root);
            });
        });

        root.querySelectorAll('[data-ech-help-modal]').forEach(function (modal) {
            if (modal.dataset.bound) return;
            modal.dataset.bound = '1';
            modal.addEventListener('click', function (event) {
                if (event.target === modal) closeHelpModal(root);
            });
        });

        root.querySelectorAll('[data-ech-tour-open]').forEach(function (button) {
            if (button.dataset.bound) return;
            button.dataset.bound = '1';
            button.addEventListener('click', function () {
                startTour(root);
            });
        });

        updateRuleDiagnostics(root);
    }

    function openHelpModal(root) {
        const modal = root.querySelector('[data-ech-help-modal]');
        if (!modal) return;
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        modal.querySelector('[data-ech-help-close]')?.focus();
    }

    function closeHelpModal(root) {
        const modal = root.querySelector('[data-ech-help-modal]');
        if (!modal) return;
        modal.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    function tourDefinitions() {
        return [
            {
                selector: '.dashboard-header',
                title: 'Deux niveaux d’aide',
                text: 'Guide vous accompagne étape par étape. Aide ouvre la documentation complète quand vous voulez relire calmement.',
            },
            {
                selector: '.ech-diagnostics',
                title: 'Lire l’état global',
                text: 'Ces compteurs montrent vite combien de scopes restent sans règle, inactifs ou à vérifier avant les relances et analytics.',
            },
            {
                selector: '.ech-stack .ech-card:first-child',
                title: 'Choisir le scope',
                text: 'La colonne de gauche liste les frais obligatoires. Filtrez, cherchez, puis cliquez un scope pour ouvrir son éditeur.',
            },
            {
                selector: '[data-filter-group="mandatory"]',
                title: 'Réduire la liste',
                text: 'Combinez filière, niveau, catégorie de frais et état pour retrouver rapidement les configurations importantes.',
            },
            {
                selector: '[data-bulk-form="mandatory"]',
                title: 'Actions en masse',
                text: 'Cochez plusieurs scopes, puis activez ou desactivez leurs regles pour le statut courant. Le bouton Visibles coche seulement les lignes filtrees.',
            },
            {
                selector: '.ech-scope-list',
                title: 'Comprendre les badges',
                text: 'Fallback signifie qu’aucune règle active spécifique n’existe encore. Les badges indiquent les règles déjà créées par statut.',
            },
            {
                selector: '.ech-stack .ech-card:nth-child(2)',
                title: 'Assignations optionnelles',
                text: 'Les options suivent le meme principe que les frais obligatoires. Pendant le guide, une ligne demo apparait si aucune option active n existe encore.',
            },
            {
                selector: '[data-bulk-form="optional"]',
                title: 'Meme logique pour les options',
                text: 'Les actions en masse fonctionnent aussi sur les frais optionnels, avec selection visible et activation/desactivation par statut.',
            },
            {
                selector: '.ech-grid > .ech-card:last-child',
                title: 'Éditer la règle',
                text: 'La zone de droite sert à régler le statut d’affectation, la priorité, les dates d’activité et les notes internes.',
            },
            {
                selector: '.ech-presets',
                title: 'Accélérer avec les presets',
                text: 'Les presets créent les découpages courants. Vous pouvez ensuite ajuster les montants, dates et jours de grâce.',
            },
            {
                selector: '.ech-lines-wrap',
                title: 'Définir les tranches',
                text: 'Chaque ligne représente une tranche exigible. En mode pourcentage, le total actif doit atteindre 100%.',
            },
            {
                selector: '[data-total-box]',
                title: 'Bloquer les incohérences',
                text: 'Le total est recalculé en direct et le serveur refuse aussi l’enregistrement si les pourcentages sont incohérents.',
            },
            {
                selector: '[data-preview-amount]',
                title: 'Tester un montant réel',
                text: 'Changez le montant de simulation pour vérifier les cas BTS, Licence, Master, affecté ou non affecté avant sauvegarde.',
            },
            {
                selector: '[data-sim-form]',
                title: 'Simuler une inscription',
                text: 'Avec un ID d’inscription réel, vous voyez l’attendu à date, le payé à date, le retard et le risque utilisé par les relances.',
            },
        ];
    }

    function startTour(root) {
        const shell = root.matches && root.matches('[data-ech-page]') ? root : document.querySelector('[data-ech-page]');
        if (!shell) return;
        closeHelpModal(shell);
        cleanupTour(shell);
        applyTourDemos(shell);

        const steps = tourDefinitions()
            .map(function (step) {
                return { ...step, target: shell.querySelector(step.selector) };
            })
            .filter(function (step) {
                return step.target && step.target.offsetParent !== null;
            });

        if (!steps.length) return;
        renderTour(shell, steps, 0);
    }

    function renderTour(root, steps, index) {
        cleanupTour(root, false);

        const step = steps[index];
        step.target.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });

        window.setTimeout(function () {
            const mask = document.createElement('div');
            mask.className = 'ech-tour-mask';
            mask.dataset.echTourNode = '1';

            const pop = document.createElement('div');
            pop.className = 'ech-tour-pop';
            pop.dataset.echTourNode = '1';
            pop.innerHTML = tourMarkup(step, index, steps.length);

            document.body.appendChild(mask);
            document.body.appendChild(pop);
            step.target.classList.add('ech-tour-highlight');
            positionTourPop(pop, step.target);

            pop.querySelector('[data-tour-close]')?.addEventListener('click', function () {
                cleanupTour(root);
            });
            pop.querySelector('[data-tour-prev]')?.addEventListener('click', function () {
                renderTour(root, steps, Math.max(0, index - 1));
            });
            pop.querySelector('[data-tour-next]')?.addEventListener('click', function () {
                if (index >= steps.length - 1) {
                    cleanupTour(root);
                    return;
                }
                renderTour(root, steps, index + 1);
            });
        }, 180);
    }

    function tourMarkup(step, index, total) {
        const dots = Array.from({ length: total }, function (_, dotIndex) {
            return `<span class="ech-tour-dot ${dotIndex === index ? 'is-active' : ''}"></span>`;
        }).join('');

        return `
            <div class="ech-tour-kicker">Etape ${index + 1} / ${total}</div>
            <h3 class="ech-tour-title">${escapeHtml(step.title)}</h3>
            <p class="ech-tour-text">${escapeHtml(step.text)}</p>
            <div class="ech-tour-actions">
                <div class="ech-tour-actions-left">
                    <button type="button" class="ech-tour-skip" data-tour-close>Quitter</button>
                    <span class="ech-tour-dots">${dots}</span>
                </div>
                <div class="ech-tour-actions-right">
                    ${index > 0 ? '<button type="button" class="ech-tour-btn" data-tour-prev>Retour</button>' : ''}
                    <button type="button" class="ech-tour-btn primary" data-tour-next>${index >= total - 1 ? 'Terminer' : 'Suivant'}</button>
                </div>
            </div>
        `;
    }

    function positionTourPop(pop, target) {
        const rect = target.getBoundingClientRect();
        const gap = 12;
        const width = pop.offsetWidth;
        const height = pop.offsetHeight;
        let top = rect.bottom + gap;
        let left = Math.min(Math.max(rect.left, gap), window.innerWidth - width - gap);

        if (top + height > window.innerHeight - gap) {
            top = rect.top - height - gap;
        }
        if (top < gap) {
            top = gap;
        }

        pop.style.top = `${top}px`;
        pop.style.left = `${left}px`;
    }

    function cleanupTour(root, removeDemos = true) {
        document.querySelectorAll('[data-ech-tour-node]').forEach(function (node) {
            node.remove();
        });
        if (removeDemos) {
            root.querySelectorAll('[data-ech-tour-demo]').forEach(function (node) {
                node.remove();
            });
        }
        root.querySelectorAll('.ech-tour-highlight').forEach(function (node) {
            node.classList.remove('ech-tour-highlight');
        });
    }

    function applyTourDemos(root) {
        root.querySelectorAll('.ech-scope-list').forEach(function (list) {
            if (list.querySelector('[data-scope-item]')) return;
            const empty = list.querySelector('.ech-empty');
            if (!empty) return;

            const card = list.closest('.ech-card');
            const isOptional = card?.querySelector('[data-bulk-form="optional"]');
            const type = isOptional ? 'optional' : 'mandatory';
            const title = isOptional ? 'Transport mensuel - Demo' : 'Frais de scolarite - Demo';
            const meta = isOptional ? 'Informatique / Master 2' : 'Transport et Infrastructure / Master 2';

            const demo = document.createElement('div');
            demo.className = 'ech-tour-demo';
            demo.dataset.echTourDemo = '1';
            demo.innerHTML = `
                <div class="ech-tour-demo-note">Exemple affiche seulement pendant le guide</div>
                <div class="ech-scope" data-scope-item="${type}" data-status="unconfigured">
                    <span class="ech-scope-main">
                        <span class="ech-scope-check"><input type="checkbox" disabled></span>
                        <span>
                            <span class="ech-scope-name">${escapeHtml(title)}</span>
                            <span class="ech-scope-meta d-block">${escapeHtml(meta)}</span>
                            <span class="ech-badges">
                                <span class="ech-state"><i class="fas fa-clock"></i>Fallback J+30</span>
                            </span>
                        </span>
                    </span>
                    <span class="ech-scope-icon"><i class="fas fa-sliders-h"></i></span>
                </div>
            `;

            list.prepend(demo);
        });
    }

    function filterScopes(root, target, value) {
        applyStructuredFilters(root, target, value);
    }

    function applyStructuredFilters(root, target, searchValue) {
        const group = root.querySelector(`[data-filter-group="${target}"]`);
        const filters = {};
        if (group) {
            group.querySelectorAll('[data-scope-filter]').forEach(function (field) {
                filters[field.dataset.scopeFilter] = field.value;
            });
        }

        const search = searchValue !== undefined
            ? searchValue
            : (root.querySelector(`[data-scope-search][data-target="${target}"]`)?.value || '');
        let visible = 0;

        root.querySelectorAll(`[data-scope-item="${target}"]`).forEach(function (item) {
            const haystack = item.dataset.searchText || item.textContent.toLowerCase();
            const queryMatch = !search.trim() || haystack.includes(search.trim().toLowerCase());
            const filterMatch = Object.entries(filters).every(function ([key, expected]) {
                return !expected || (item.dataset[key] || '') === expected;
            });
            const match = queryMatch && filterMatch;
            item.classList.toggle('ech-hidden', !match);
            if (match) visible++;
        });

        const counter = root.querySelector(`[data-scope-count="${target}"]`);
        if (counter) counter.textContent = visible;
    }

    function applyPreset(root, preset) {
        const presets = {
            single: [[100, 30]],
            half: [[50, 0], [50, 30]],
            three: [[30, 0], [40, 30], [30, 60]],
            monthly: [[25, 0], [25, 30], [25, 60], [25, 90]],
            quarterly: [[34, 0], [33, 90], [33, 180]],
        };
        const lines = presets[preset] || presets.single;
        const body = root.querySelector('[data-lines-body]');
        if (!body) return;

        body.innerHTML = lines.map(function (line, index) {
            return lineRow(index, line[0], line[1]);
        }).join('');

        body.dataset.bound = '';
        initEcheanciersPage(root);
        updateRuleDiagnostics(root);
    }

    function updateRuleDiagnostics(root) {
        const body = root.querySelector('[data-lines-body]');
        if (!body) return;

        const rows = Array.from(body.querySelectorAll('tr'));
        const percentTotal = rows.reduce(function (sum, row) {
            const mode = row.querySelector('select[name*="[amount_mode]"]')?.value;
            const active = row.querySelector('input[name*="[is_active]"]')?.checked !== false;
            const value = parseFloat(row.querySelector('input[name*="[amount_value]"]')?.value || '0');
            return mode === 'percent' && active ? sum + value : sum;
        }, 0);

        const totalBox = root.querySelector('[data-total-box]');
        const totalValue = root.querySelector('[data-percent-total]');
        const totalMessage = root.querySelector('[data-total-message]');
        if (totalBox && totalValue && totalMessage) {
            totalValue.textContent = `${percentTotal.toFixed(2).replace('.00', '')}%`;
            totalBox.classList.toggle('is-ok', Math.abs(percentTotal - 100) < 0.01);
            totalBox.classList.toggle('is-bad', Math.abs(percentTotal - 100) >= 0.01);
            totalMessage.textContent = Math.abs(percentTotal - 100) < 0.01
                ? 'Total coherent.'
                : 'Ajustez les lignes pour atteindre 100%.';
        }

        updatePreview(root, rows);
    }

    function updatePreview(root, rows) {
        const list = root.querySelector('[data-preview-list]');
        if (!list) return;

        const amount = parseFloat(root.querySelector('[data-preview-amount]')?.value || '0');
        const baseDateValue = root.querySelector('[data-preview-date]')?.value;
        const baseDate = baseDateValue ? new Date(baseDateValue + 'T00:00:00') : new Date();

        list.innerHTML = rows.map(function (row) {
            const label = row.querySelector('input[name*="[label]"]')?.value || 'Tranche';
            const amountMode = row.querySelector('select[name*="[amount_mode]"]')?.value;
            const amountValue = parseFloat(row.querySelector('input[name*="[amount_value]"]')?.value || '0');
            const dueMode = row.querySelector('select[name*="[due_mode]"]')?.value;
            const dueValue = row.querySelector('input[name*="[due_value]"]')?.value || '0';
            const lineAmount = amountMode === 'percent' ? amount * amountValue / 100 : amountValue;
            const dueDate = resolveDueDate(baseDate, dueMode, dueValue);

            return `<div class="ech-preview-line"><span>${escapeHtml(label)} · ${formatAmount(lineAmount)} FCFA</span><strong>${dueDate}</strong></div>`;
        }).join('');
    }

    function resolveDueDate(baseDate, mode, value) {
        if (mode === 'fixed_mm_dd' && /^\d{2}-\d{2}$/.test(value)) {
            const year = baseDate.getFullYear();
            return `${String(value).replace('-', '/')}/${year}`;
        }
        const days = parseInt(value || '0', 10);
        const date = new Date(baseDate);
        date.setDate(date.getDate() + (Number.isFinite(days) ? days : 0));
        return date.toLocaleDateString('fr-FR');
    }

    async function simulateInscription(root, form) {
        const result = root.querySelector('[data-sim-result]');
        if (!result) return;
        result.innerHTML = '<div><span>Simulation</span><strong>Chargement...</strong></div>';

        try {
            const params = new URLSearchParams(new FormData(form));
            const response = await fetch(`${form.action}?${params.toString()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await response.json();
            if (!data.success) throw new Error('Simulation impossible');
            result.innerHTML = [
                ['Etudiant', data.student || 'N/A'],
                ['Classe', data.classe || 'N/A'],
                ['Total du', `${formatAmount(data.total_due)} FCFA`],
                ['Attendu a date', `${formatAmount(data.expected_due_to_date)} FCFA`],
                ['Paye a date', `${formatAmount(data.paid_due_to_date)} FCFA`],
                ['Echu impaye', `${formatAmount(data.overdue_amount)} FCFA`],
                ['Retard', `${data.overdue_days || 0} j`],
                ['Risque', data.risk_label || 'N/A'],
            ].map(([k, v]) => `<div><span>${k}</span><strong>${v}</strong></div>`).join('');
        } catch (error) {
            result.innerHTML = '<div><span>Erreur</span><strong>Inscription introuvable ou non simulable</strong></div>';
        }
    }

    function formatAmount(value) {
        return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(Number(value || 0));
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function (char) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
        });
    }

    function lineRow(index, percent = 0, days = 30) {
        return `
            <tr>
                <td><input type="text" class="ech-input" name="lines[${index}][label]" value="Tranche ${index + 1}" required></td>
                <td><input type="number" class="ech-input" name="lines[${index}][sort_order]" min="1" max="99" value="${index + 1}"></td>
                <td><select class="ech-select" name="lines[${index}][amount_mode]"><option value="percent">Pourcentage</option><option value="fixed">Montant fixe</option></select></td>
                <td><input type="number" step="0.01" min="0" class="ech-input" name="lines[${index}][amount_value]" value="${percent}" required></td>
                <td><select class="ech-select" name="lines[${index}][due_mode]"><option value="days_after_inscription">Apres inscription</option><option value="fixed_mm_dd">Date fixe</option></select></td>
                <td><input type="text" class="ech-input" name="lines[${index}][due_value]" value="${days}" required></td>
                <td><input type="number" min="0" max="365" class="ech-input" name="lines[${index}][grace_days]" value="0"></td>
                <td><input type="checkbox" name="lines[${index}][is_active]" value="1" checked></td>
                <td><button type="button" class="ech-icon-btn" data-remove-line title="Retirer"><i class="fas fa-times"></i></button></td>
            </tr>
        `;
    }

    document.addEventListener('click', async function (event) {
        if (event.target.closest('[data-scope-checkbox]')) return;
        const link = event.target.closest('[data-ech-scope-link]');
        if (!link || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        event.preventDefault();
        const shell = document.querySelector('[data-ech-page]');
        if (!shell) {
            window.location.href = link.href;
            return;
        }

        shell.classList.add('is-loading');
        shell.querySelectorAll('a,button,input,select,textarea').forEach(el => el.disabled = true);

        try {
            const response = await fetch(link.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const next = doc.querySelector('[data-ech-page]');
            if (!next) throw new Error('Invalid response');

            shell.replaceWith(next);
            window.history.pushState({}, '', link.href);
            initEcheanciersPage(next);
        } catch (error) {
            window.location.href = link.href;
        }
    });

    window.addEventListener('popstate', function () {
        window.location.reload();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        const shell = document.querySelector('[data-ech-page]');
        if (shell?.querySelector('[data-ech-help-modal].is-open')) {
            closeHelpModal(shell);
        }
        if (document.querySelector('[data-ech-tour-node]')) {
            cleanupTour(shell || document);
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        initEcheanciersPage(document);
    });
})();
</script>
@endpush
