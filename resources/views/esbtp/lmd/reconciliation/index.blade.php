@extends('layouts.app')

@section('title', 'Réconciliation des doublons UE/ECUE — KLASSCI')

@php
    $recMentionOptions = $mentions->mapWithKeys(fn ($m) => [$m->id => trim(($m->code ? $m->code . ' — ' : '') . $m->name)])->all();
    $recParcoursOptions = $parcours->mapWithKeys(fn ($p) => [$p->id => trim(($p->code ? $p->code . ' — ' : '') . $p->name)])->all();
@endphp

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ══════════════════════════════════════════════
       LMD Réconciliation doublons — namespace rec-*
       Palette monochrome bleu KLASSCI #0453cb
       ══════════════════════════════════════════════ */
    .rec-page { max-width: 1320px; margin: 0 auto; padding: 0 1rem 2.5rem; }

    /* Hero (référence .re-hero : pas d'overflow:hidden, pas de ::before) */
    .rec-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px; padding: 2rem 2.5rem 1.75rem;
        color: #fff; margin-bottom: 1.5rem;
        box-shadow: 0 8px 30px rgba(4,83,203,.18);
    }
    .rec-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
    .rec-hero-left { display: flex; align-items: center; gap: 1rem; }
    .rec-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0; }
    .rec-hero h1 { font-size: 1.45rem; font-weight: 700; margin: 0 0 .2rem; color: #fff; letter-spacing: -.02em; }
    .rec-hero p { margin: 0; opacity: .8; font-size: .88rem; color: rgba(255,255,255,.8); }
    .rec-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
    .rec-kpi { flex: 1; min-width: 150px; background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15); border-radius: 12px; padding: .9rem 1rem; display: flex; align-items: center; gap: .75rem; }
    .rec-kpi-icon { width: 38px; height: 38px; border-radius: 9px; background: rgba(255,255,255,.18); color: #fff; display: flex; align-items: center; justify-content: center; font-size: .95rem; flex-shrink: 0; }
    .rec-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; line-height: 1; }
    .rec-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; }

    /* Card */
    .rec-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); margin-bottom: 1.25rem; }
    .rec-card-head { display: flex; align-items: center; gap: .75rem; padding: 1.1rem 1.4rem; border-bottom: 1px solid #eef2f7; }
    .rec-card-icon { width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff; display: flex; align-items: center; justify-content: center; font-size: .95rem; flex-shrink: 0; }
    .rec-card-title { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0; }
    .rec-card-sub { font-size: .8rem; color: #64748b; margin: 0; }
    .rec-card-body { padding: 1.2rem 1.4rem; }

    /* Filters */
    .rec-filters { display: flex; flex-wrap: wrap; gap: .85rem; align-items: flex-end; }
    .rec-field { display: flex; flex-direction: column; gap: .35rem; min-width: 200px; }
    .rec-field label { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #64748b; }
    .rec-range { display: flex; align-items: center; gap: .6rem; }
    .rec-range input[type=range] { accent-color: #0453cb; }
    .rec-range-val { font-weight: 700; color: #0453cb; min-width: 48px; }
    .rec-check { display: inline-flex; align-items: center; gap: .45rem; font-size: .82rem; color: #1e293b; cursor: pointer; }
    .rec-check input { accent-color: #0453cb; }
    .rec-btn { display: inline-flex; align-items: center; gap: .45rem; padding: .6rem 1.15rem; border-radius: 10px; font-size: .84rem; font-weight: 600; border: none; cursor: pointer; transition: background .15s, color .15s, box-shadow .15s; }
    .rec-btn--primary { background: #0453cb; color: #fff; }
    .rec-btn--primary:hover:not(:disabled) { background: #033a8e; }
    .rec-btn--ghost { background: rgba(4,83,203,.06); color: #0453cb; border: 1px solid rgba(4,83,203,.18); }
    .rec-btn--ghost:hover:not(:disabled) { background: rgba(4,83,203,.12); }
    .rec-btn--danger { background: #dc2626; color: #fff; }
    .rec-btn--danger:hover:not(:disabled) { background: #b91c1c; }
    .rec-btn:disabled { opacity: .6; cursor: not-allowed; }
    .rec-disabled-hint { margin: .5rem 1.25rem 0; font-size: .76rem; color: #b45309; text-align: right; }

    /* Group card */
    .rec-group { border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 1rem; overflow: hidden; }
    .rec-group-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .9rem 1.1rem; background: #f8fafc; border-bottom: 1px solid #eef2f7; flex-wrap: wrap; }
    .rec-group-name { font-weight: 700; color: #1e293b; font-size: .95rem; }
    .rec-group-meta { display: flex; gap: .4rem; flex-wrap: wrap; }
    .rec-badge { display: inline-flex; align-items: center; gap: .3rem; padding: .2rem .55rem; border-radius: 6px; font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; }
    .rec-badge--count { background: rgba(4,83,203,.1); color: #0453cb; border: 1px solid rgba(4,83,203,.22); }
    .rec-badge--diff { background: rgba(245,158,11,.12); color: #b45309; border: 1px solid rgba(245,158,11,.25); }
    .rec-cands { padding: .8rem 1.1rem; }
    .rec-cand { display: flex; align-items: center; gap: .8rem; padding: .65rem .75rem; border: 1px solid #eef2f7; border-radius: 9px; margin-bottom: .55rem; transition: border-color .15s, background .15s; }
    .rec-cand:last-child { margin-bottom: 0; }
    .rec-cand--canonical { border-color: #0453cb; background: rgba(4,83,203,.04); }
    .rec-cand input[type=radio] { accent-color: #0453cb; width: 18px; height: 18px; flex-shrink: 0; }
    .rec-cand-main { flex: 1; min-width: 0; }
    .rec-cand-name { font-weight: 600; color: #1e293b; font-size: .9rem; }
    .rec-cand-attrs { display: flex; gap: .4rem; flex-wrap: wrap; margin-top: .3rem; }
    .rec-chip { display: inline-flex; align-items: center; gap: .25rem; padding: .15rem .5rem; border-radius: 5px; font-size: .68rem; font-weight: 600; background: #f1f5f9; color: #475569; font-family: 'Courier New', monospace; }
    .rec-chip--code { background: rgba(4,83,203,.08); color: #0453cb; }
    .rec-chip--mismatch { background: rgba(245,158,11,.12); color: #b45309; }
    .rec-cand-parcours { display: flex; gap: .3rem; flex-wrap: wrap; margin-top: .3rem; }
    .rec-pchip { font-size: .65rem; padding: .1rem .45rem; border-radius: 5px; background: rgba(59,125,219,.1); color: #3b7ddb; font-weight: 600; }
    .rec-canonical-tag { font-size: .65rem; font-weight: 700; color: #0453cb; text-transform: uppercase; letter-spacing: .4px; }
    .rec-group-actions { padding: .8rem 1.1rem; border-top: 1px solid #eef2f7; display: flex; gap: .6rem; justify-content: flex-end; flex-wrap: wrap; }

    /* Empty / loading */
    .rec-empty { text-align: center; padding: 2.5rem 1rem; color: #64748b; }
    .rec-empty i { font-size: 2.2rem; color: #cbd5e1; margin-bottom: .6rem; display: block; }
    .rec-loading { text-align: center; padding: 2rem; color: #64748b; }

    /* Modal aperçu */
    .rec-modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,.55); display: flex; align-items: center; justify-content: center; z-index: 1080; padding: 1rem; }
    .rec-modal { background: #fff; border-radius: 16px; max-width: 560px; width: 100%; max-height: 88vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(15,23,42,.3); }
    .rec-modal-head { padding: 1.2rem 1.4rem; border-bottom: 1px solid #eef2f7; display: flex; align-items: center; gap: .7rem; }
    .rec-modal-head i { width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff; display: flex; align-items: center; justify-content: center; }
    .rec-modal-body { padding: 1.3rem 1.4rem; }
    .rec-impact-row { display: flex; justify-content: space-between; padding: .55rem 0; border-bottom: 1px dashed #eef2f7; font-size: .88rem; }
    .rec-impact-row strong { color: #0453cb; }
    .rec-warn { background: rgba(245,158,11,.1); border: 1px solid rgba(245,158,11,.28); color: #b45309; border-radius: 9px; padding: .7rem .9rem; font-size: .82rem; margin-top: .9rem; }
    .rec-block { background: rgba(220,38,38,.08); border: 1px solid rgba(220,38,38,.28); color: #b91c1c; border-radius: 9px; padding: .7rem .9rem; font-size: .82rem; margin-top: .9rem; }
    .rec-modal-foot { padding: 1rem 1.4rem; border-top: 1px solid #eef2f7; display: flex; justify-content: flex-end; gap: .6rem; flex-wrap: wrap; }

    /* Tabs */
    .rec-tabs { display: flex; gap: .5rem; margin-bottom: 1rem; }
    .rec-tab { padding: .55rem 1.1rem; border-radius: 9px; font-size: .85rem; font-weight: 600; cursor: pointer; background: #fff; border: 1px solid #e2e8f0; color: #475569; }
    .rec-tab--active { background: #0453cb; color: #fff; border-color: #0453cb; }

    /* Toast */
    .rec-toasts { position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 1090; display: flex; flex-direction: column; gap: .6rem; }
    .rec-toast { padding: .8rem 1.1rem; border-radius: 10px; color: #fff; font-size: .85rem; font-weight: 600; box-shadow: 0 8px 24px rgba(15,23,42,.18); display: flex; align-items: center; gap: .5rem; min-width: 240px; }
    .rec-toast--success { background: #10b981; }
    .rec-toast--error { background: #dc2626; }
    .rec-toast--info { background: #0453cb; }

    @@media (max-width: 768px) {
        .rec-hero { padding: 1.5rem 1.25rem; }
        .rec-field { min-width: 100%; }
        .rec-toasts { left: 1rem; right: 1rem; }
    }
</style>
@endpush

@section('content')
<div class="rec-page" x-data="recManager()" x-init="init()">

    {{-- Hero --}}
    <div class="rec-hero">
        <div class="rec-hero-top">
            <div class="rec-hero-left">
                <div class="rec-hero-icon"><i class="fas fa-object-group"></i></div>
                <div>
                    <h1>Réconciliation des doublons UE/ECUE</h1>
                    <p>Détectez les UE et ECUE en double issues d'imports multi-parcours, puis fusionnez-les en une entité partagée.</p>
                </div>
            </div>
        </div>
        <div class="rec-kpis">
            <div class="rec-kpi">
                <div class="rec-kpi-icon"><i class="fas fa-cubes"></i></div>
                <div><div class="rec-kpi-value" x-text="kpis.ue_duplicate_groups"></div><div class="rec-kpi-label">Groupes UE en double</div></div>
            </div>
            <div class="rec-kpi">
                <div class="rec-kpi-icon"><i class="fas fa-book"></i></div>
                <div><div class="rec-kpi-value" x-text="kpis.ecue_duplicate_groups"></div><div class="rec-kpi-label">Groupes ECUE en double</div></div>
            </div>
            <div class="rec-kpi">
                <div class="rec-kpi-icon"><i class="fas fa-route"></i></div>
                <div><div class="rec-kpi-value" x-text="kpis.parcours_concerned"></div><div class="rec-kpi-label">Parcours concernés</div></div>
            </div>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="rec-card">
        <div class="rec-card-head">
            <div class="rec-card-icon"><i class="fas fa-filter"></i></div>
            <div>
                <p class="rec-card-title">Critères de détection</p>
                <p class="rec-card-sub">Restreindre le périmètre puis lancer la détection par similarité de nom.</p>
            </div>
        </div>
        <div class="rec-card-body">
            <div class="rec-filters">
                <div class="rec-field">
                    <label>Mention</label>
                    <x-au-select name="rec_mention" x-model="filters.mention_id" icon="fa-graduation-cap"
                        placeholder="Toutes les mentions" :options="$recMentionOptions" :searchable="true" />
                </div>
                <div class="rec-field">
                    <label>Parcours</label>
                    <x-au-select name="rec_parcours" x-model="filters.parcours_id" icon="fa-route"
                        placeholder="Tous les parcours" :options="$recParcoursOptions" :searchable="true" />
                </div>
                <div class="rec-field">
                    <label>Seuil de similarité</label>
                    <div class="rec-range">
                        <input type="range" min="50" max="100" step="1" x-model.number="filters.threshold">
                        <span class="rec-range-val" x-text="filters.threshold + '%'"></span>
                    </div>
                </div>
                <div class="rec-field" style="min-width:auto;">
                    <label>&nbsp;</label>
                    <label class="rec-check"><input type="checkbox" x-model="filters.same_level_only"> Même niveau + semestre</label>
                </div>
                <div class="rec-field" style="min-width:auto;">
                    <label>&nbsp;</label>
                    <button class="rec-btn rec-btn--primary" @click="detect()" :disabled="loading">
                        <i class="fas fa-search" x-show="!loading"></i>
                        <i class="fas fa-spinner fa-spin" x-show="loading" x-cloak></i>
                        <span x-text="loading ? 'Analyse…' : 'Détecter les doublons'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Onglets UE / ECUE --}}
    <div class="rec-tabs">
        <div class="rec-tab" :class="tab === 'ue' ? 'rec-tab--active' : ''" @click="tab = 'ue'">
            <i class="fas fa-cubes"></i> UE <span x-text="'(' + ueGroups.length + ')'"></span>
        </div>
        <div class="rec-tab" :class="tab === 'ecue' ? 'rec-tab--active' : ''" @click="tab = 'ecue'">
            <i class="fas fa-book"></i> ECUE <span x-text="'(' + ecueGroups.length + ')'"></span>
        </div>
    </div>

    {{-- Loading / empty / groupes --}}
    <div x-show="loading" class="rec-loading"><i class="fas fa-spinner fa-spin"></i> Analyse en cours…</div>

    <template x-if="!loading">
        <div>
            {{-- UE --}}
            <div x-show="tab === 'ue'">
                <template x-if="ueGroups.length === 0">
                    <div class="rec-card"><div class="rec-empty"><i class="fas fa-check-circle"></i>Aucun doublon d'UE détecté pour ce périmètre.</div></div>
                </template>
                <template x-for="group in ueGroups" :key="group.group_id">
                    <div class="rec-group" x-data="{ canonical: group.candidates[0].id }">
                        <div class="rec-group-head">
                            <div class="rec-group-name" x-text="prettyName(group.normalized_name)"></div>
                            <div class="rec-group-meta">
                                <span class="rec-badge rec-badge--count"><i class="fas fa-clone"></i> <span x-text="group.count + ' candidates'"></span></span>
                                <template x-for="(on, key) in group.discrepancies" :key="key">
                                    <span class="rec-badge rec-badge--diff" x-show="on" x-text="'écart ' + key"></span>
                                </template>
                            </div>
                        </div>
                        <div class="rec-cands">
                            <template x-for="cand in group.candidates" :key="cand.id">
                                <label class="rec-cand" :class="canonical === cand.id ? 'rec-cand--canonical' : ''">
                                    <input type="radio" :value="cand.id" x-model.number="canonical">
                                    <div class="rec-cand-main">
                                        <div class="rec-cand-name" x-text="cand.name"></div>
                                        <div class="rec-cand-attrs">
                                            <span class="rec-chip rec-chip--code" x-text="'Code: ' + (cand.code || '—')"></span>
                                            <span class="rec-chip" :class="group.discrepancies.credit ? 'rec-chip--mismatch' : ''" x-text="'Crédits: ' + (cand.credit ?? '—')"></span>
                                            <span class="rec-chip" x-text="'S' + (cand.semestre ?? '?')"></span>
                                            <span class="rec-chip" x-text="cand.niveau || '—'"></span>
                                        </div>
                                        <div class="rec-cand-parcours" x-show="cand.parcours && cand.parcours.length">
                                            <template x-for="p in cand.parcours" :key="p.id">
                                                <span class="rec-pchip" x-text="p.code || p.name"></span>
                                            </template>
                                        </div>
                                    </div>
                                    <span class="rec-canonical-tag" x-show="canonical === cand.id">Canonique</span>
                                </label>
                            </template>
                        </div>
                        <div class="rec-group-actions">
                            <button class="rec-btn rec-btn--ghost" @click="preview('ue', group, canonical)" :disabled="busy">
                                <i class="fas fa-eye"></i> Prévisualiser la fusion
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            {{-- ECUE --}}
            <div x-show="tab === 'ecue'">
                <template x-if="ecueGroups.length === 0">
                    <div class="rec-card"><div class="rec-empty"><i class="fas fa-check-circle"></i>Aucun doublon d'ECUE détecté pour ce périmètre.</div></div>
                </template>
                <template x-for="group in ecueGroups" :key="group.group_id">
                    <div class="rec-group" x-data="{ canonical: group.candidates[0].id }">
                        <div class="rec-group-head">
                            <div class="rec-group-name" x-text="prettyName(group.normalized_name)"></div>
                            <div class="rec-group-meta">
                                <span class="rec-badge rec-badge--count"><i class="fas fa-clone"></i> <span x-text="group.count + ' candidates'"></span></span>
                                <template x-for="(on, key) in group.discrepancies" :key="key">
                                    <span class="rec-badge rec-badge--diff" x-show="on" x-text="'écart ' + key"></span>
                                </template>
                            </div>
                        </div>
                        <div class="rec-cands">
                            <template x-for="cand in group.candidates" :key="cand.id">
                                <label class="rec-cand" :class="canonical === cand.id ? 'rec-cand--canonical' : ''">
                                    <input type="radio" :value="cand.id" x-model.number="canonical">
                                    <div class="rec-cand-main">
                                        <div class="rec-cand-name" x-text="cand.name"></div>
                                        <div class="rec-cand-attrs">
                                            <span class="rec-chip rec-chip--code" x-text="'Code: ' + (cand.code || '—')"></span>
                                            <span class="rec-chip" :class="group.discrepancies.credit ? 'rec-chip--mismatch' : ''" x-text="'Crédit: ' + (cand.credit_ecue ?? '—')"></span>
                                            <span class="rec-chip" :class="group.discrepancies.coefficient ? 'rec-chip--mismatch' : ''" x-text="'Coef: ' + (cand.coefficient_ecue ?? '—')"></span>
                                            <span class="rec-chip" x-show="cand.ue" x-text="'UE: ' + (cand.ue ? cand.ue.code || cand.ue.name : '')"></span>
                                        </div>
                                    </div>
                                    <span class="rec-canonical-tag" x-show="canonical === cand.id">Canonique</span>
                                </label>
                            </template>
                        </div>
                        <div class="rec-group-actions">
                            <button class="rec-btn rec-btn--ghost" @click="preview('ecue', group, canonical)" :disabled="busy">
                                <i class="fas fa-eye"></i> Prévisualiser la fusion
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>

    {{-- Modal aperçu d'impact (dry-run) --}}
    <div class="rec-modal-overlay" x-show="modal.open" x-cloak @keydown.escape.window="closeModal()" style="display:none;">
        <div class="rec-modal" @click.outside="closeModal()">
            <div class="rec-modal-head">
                <i class="fas fa-object-group"></i>
                <div>
                    <p class="rec-card-title">Aperçu de la fusion</p>
                    <p class="rec-card-sub" x-text="modal.typeLabel"></p>
                </div>
            </div>
            <div class="rec-modal-body">
                <template x-if="modal.report && modal.report.blocked">
                    <div class="rec-block">
                        <i class="fas fa-ban"></i> <span x-text="modal.report.reason"></span>
                        <div style="margin-top:.4rem;font-size:.78rem;">
                            <template x-if="modal.report.blocking">
                                <span x-text="blockingSummary(modal.report.blocking)"></span>
                            </template>
                        </div>
                    </div>
                </template>

                <template x-if="modal.report && !modal.report.blocked">
                    <div>
                        <div class="rec-impact-row"><span>Entité canonique conservée</span><strong x-text="'#' + modal.report.canonical_id"></strong></div>
                        <div class="rec-impact-row"><span>Entités absorbées (soft-delete)</span><strong x-text="modal.report.soft_deleted_count"></strong></div>
                        <template x-for="(val, key) in (modal.report.repointed || {})" :key="key">
                            <div class="rec-impact-row"><span x-text="'Liens repointés — ' + repointLabel(key)"></span><strong x-text="val"></strong></div>
                        </template>
                        <template x-if="modal.report.uemoa_check && modal.report.uemoa_check.warnings && modal.report.uemoa_check.warnings.length">
                            <div class="rec-warn">
                                <i class="fas fa-exclamation-triangle"></i> Après fusion, certains parcours/semestres ne totalisent plus 30 crédits UEMOA :
                                <template x-for="w in modal.report.uemoa_check.warnings" :key="w.parcours_id + '-' + w.semestre">
                                    <div x-text="'Parcours #' + w.parcours_id + ' / S' + w.semestre + ' : ' + w.total_credits + ' crédits (attendu ' + w.expected + ')'"></div>
                                </template>
                            </div>
                        </template>
                        <template x-if="modal.report.blocking && (modal.report.blocking.evaluations || modal.report.blocking.notes || modal.report.blocking.resultats_ue)">
                            <div class="rec-warn">
                                <i class="fas fa-flask"></i> Données pédagogiques liées : <span x-text="blockingSummary(modal.report.blocking)"></span>. Confirmez explicitement pour les repointer.
                            </div>
                        </template>
                    </div>
                </template>
            </div>
            <div class="rec-modal-foot">
                <button class="rec-btn rec-btn--ghost" @click="closeModal()">Annuler</button>
                <template x-if="modal.report && modal.report.blocked">
                    <label class="rec-check"><input type="checkbox" x-model="modal.force"> Forcer (repointe évaluations / notes / résultats)</label>
                </template>
                <button class="rec-btn rec-btn--danger" @click="confirmMerge()" :disabled="mergeDisabled">
                    <i class="fas fa-object-group" x-show="!busy"></i>
                    <i class="fas fa-spinner fa-spin" x-show="busy" x-cloak></i>
                    Fusionner
                </button>
            </div>
            <p class="rec-disabled-hint" x-show="mergeDisabled && mergeDisabledReason" x-cloak x-text="mergeDisabledReason"></p>
        </div>
    </div>

    {{-- Toasts --}}
    <div class="rec-toasts">
        <template x-for="t in toasts" :key="t.id">
            <div class="rec-toast" :class="'rec-toast--' + t.type">
                <i class="fas" :class="t.type === 'success' ? 'fa-check' : (t.type === 'error' ? 'fa-times' : 'fa-info-circle')"></i>
                <span x-text="t.message"></span>
            </div>
        </template>
    </div>
</div>
@endsection

@push('scripts')
<script>
function recManager() {
    return {
        filters: { mention_id: '', parcours_id: '', threshold: 85, same_level_only: true },
        ueGroups: [],
        ecueGroups: [],
        kpis: { ue_duplicate_groups: 0, ecue_duplicate_groups: 0, parcours_concerned: 0 },
        tab: 'ue',
        loading: false,
        busy: false,
        modal: { open: false, type: null, typeLabel: '', group: null, canonical: null, report: null, force: false },
        toasts: [],
        _tid: 0,

        init() { /* no auto-detect : l'utilisateur lance explicitement */ },

        prettyName(n) { return (n || '').replace(/\b\w/g, c => c.toUpperCase()); },

        repointLabel(key) {
            const map = {
                parcours_ue_links: 'parcours ↔ UE',
                ue_matiere_links: 'UE ↔ ECUE',
                ecue_fk: 'ECUE (FK directe)',
                planifications: 'planifications',
                matiere_filiere_links: 'matière ↔ filière',
            };
            return map[key] || key;
        },

        blockingSummary(b) {
            const parts = [];
            if (b.evaluations) parts.push(b.evaluations + ' évaluation(s)');
            if (b.notes) parts.push(b.notes + ' note(s)');
            if (b.resultats_ue) parts.push(b.resultats_ue + ' résultat(s) UE');
            return parts.length ? parts.join(', ') : 'aucune';
        },

        get mergeDisabled() {
            if (this.busy) return true;
            if (!this.modal.report) return true;
            if (this.modal.report.blocked && !this.modal.force) return true;
            return false;
        },
        get mergeDisabledReason() {
            if (this.busy) return 'Fusion en cours…';
            if (!this.modal.report) return 'Aperçu en cours…';
            if (this.modal.report.blocked && !this.modal.force) {
                return 'Données pédagogiques liées (' + this.blockingSummary(this.modal.report.blocking || {}) + ') — cochez « Forcer » pour fusionner quand même.';
            }
            return '';
        },

        async detect() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.filters.mention_id) params.set('mention_id', this.filters.mention_id);
                if (this.filters.parcours_id) params.set('parcours_id', this.filters.parcours_id);
                params.set('threshold', this.filters.threshold);
                params.set('same_level_only', this.filters.same_level_only ? 1 : 0);

                const res = await fetch('{{ route('esbtp.lmd.reconciliation.detect') }}?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok) throw new Error('Erreur HTTP ' + res.status);
                const data = await res.json();
                this.ueGroups = data.ue_groups || [];
                this.ecueGroups = data.ecue_groups || [];
                this.kpis = data.kpis || this.kpis;
                this.toast('info', (this.kpis.ue_duplicate_groups + this.kpis.ecue_duplicate_groups) + ' groupe(s) détecté(s).');
            } catch (err) {
                this.toast('error', err.message || 'Échec de la détection.');
            } finally {
                this.loading = false;
            }
        },

        async preview(type, group, canonicalId) {
            const absorbed = group.candidates.map(c => c.id).filter(id => id !== canonicalId);
            if (!absorbed.length) { this.toast('error', 'Sélectionnez au moins une entité à absorber.'); return; }
            this.modal = {
                open: true, type, group, canonical: canonicalId,
                typeLabel: (type === 'ue' ? 'Unité d\'enseignement' : 'ECUE') + ' — ' + this.prettyName(group.normalized_name),
                report: null, force: false,
            };
            const report = await this.callMerge(type, canonicalId, absorbed, true, false);
            this.modal.report = report;
        },

        async confirmMerge() {
            if (!this.modal.group) return;
            const absorbed = this.modal.group.candidates.map(c => c.id).filter(id => id !== this.modal.canonical);
            this.busy = true;
            try {
                const report = await this.callMerge(this.modal.type, this.modal.canonical, absorbed, false, this.modal.force);
                if (report && report.committed) {
                    this.toast('success', 'Fusion effectuée : ' + report.soft_deleted_count + ' entité(s) absorbée(s).');
                    this.closeModal();
                    await this.detect();
                } else if (report && report.blocked) {
                    this.modal.report = report;
                    this.toast('error', report.reason || 'Fusion bloquée.');
                } else {
                    this.toast('error', 'Fusion non effectuée.');
                }
            } catch (err) {
                this.toast('error', err.message || 'Échec de la fusion.');
            } finally {
                this.busy = false;
            }
        },

        async callMerge(type, canonicalId, absorbedIds, dryRun, force) {
            const res = await fetch('{{ route('esbtp.lmd.reconciliation.merge') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ type, canonical_id: canonicalId, absorbed_ids: absorbedIds, dry_run: dryRun, force }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok && !data.blocked) {
                throw new Error(data.message || data.reason || ('Erreur HTTP ' + res.status));
            }
            return data;
        },

        closeModal() { this.modal.open = false; this.modal.group = null; this.modal.report = null; this.modal.force = false; },

        toast(type, message) {
            const id = ++this._tid;
            this.toasts.push({ id, type, message });
            setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 4000);
        },
    };
}
</script>
@endpush
