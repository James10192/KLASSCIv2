@extends('layouts.app')

@section('title', 'Notes LMD | KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ══════════════════════════════════════════════
       LMD Notes Index — Premium Redesign v2
       Prefix: ln- (lmd-notes)
       ══════════════════════════════════════════════ */

    .ln-page { max-width: 1440px; margin: 0 auto; padding: 0 1rem 2rem; }

    /* ── Hero ── */
    .ln-hero {
        position: relative;
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px; padding: 2rem 2.5rem 1.5rem;
        color: #fff; margin-bottom: 1.5rem; overflow: hidden;
        animation: ln-fadeDown .5s ease-out;
    }
    .ln-hero::before {
        content: ''; position: absolute; top: -60%; right: -10%;
        width: 420px; height: 420px;
        background: radial-gradient(circle, rgba(255,255,255,.07) 0%, transparent 70%);
        pointer-events: none;
    }
    .ln-hero::after {
        content: ''; position: absolute; bottom: -40%; left: 5%;
        width: 300px; height: 300px;
        background: radial-gradient(circle, rgba(255,255,255,.04) 0%, transparent 70%);
        pointer-events: none;
    }
    .ln-hero-top {
        display: flex; align-items: flex-start; justify-content: space-between;
        flex-wrap: wrap; gap: 1rem; position: relative; z-index: 1;
    }
    .ln-hero-left { display: flex; align-items: center; gap: 1rem; }
    .ln-hero-icon {
        width: 52px; height: 52px; border-radius: 14px;
        background: rgba(255,255,255,.12); backdrop-filter: blur(8px);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; border: 1px solid rgba(255,255,255,.15); flex-shrink: 0;
    }
    .ln-hero-info h1 { font-size: 1.45rem; font-weight: 700; margin: 0 0 .2rem; color: #fff; letter-spacing: -.02em; }
    .ln-hero-info p { margin: 0; opacity: .8; font-size: .88rem; }

    /* KPIs in hero */
    .ln-hero-kpis {
        display: flex; gap: .75rem; margin-top: 1.5rem;
        position: relative; z-index: 1; flex-wrap: wrap;
    }
    .ln-kpi {
        flex: 1; min-width: 140px;
        background: rgba(255,255,255,.1); backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,.15); border-radius: 12px;
        padding: .9rem 1rem; display: flex; align-items: center; gap: .75rem;
        transition: background .2s;
    }
    .ln-kpi:hover { background: rgba(255,255,255,.15); }
    .ln-kpi-icon {
        width: 38px; height: 38px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        font-size: .95rem; flex-shrink: 0;
    }
    .ln-kpi--classes .ln-kpi-icon   { background: rgba(255,255,255,.18); color: #fff; }
    .ln-kpi--etudiants .ln-kpi-icon { background: rgba(16,185,129,.25); color: #6ee7b7; }
    .ln-kpi--evals .ln-kpi-icon     { background: rgba(129,140,248,.25); color: #a5b4fc; }
    .ln-kpi-value { font-size: 1.35rem; font-weight: 700; line-height: 1; color: #fff; }
    .ln-kpi-label { font-size: .75rem; color: rgba(255,255,255,.65); margin-top: .15rem; }

    /* ── Classes grid ── */
    .ln-section-header {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 1rem;
        animation: ln-fadeUp .45s ease-out .1s both;
    }
    .ln-section-title {
        font-size: 1.05rem; font-weight: 700; color: #1e293b;
        display: flex; align-items: center; gap: .5rem;
    }
    .ln-section-title i { color: #0453cb; }
    .ln-section-count {
        font-size: .8rem; color: #94a3b8; font-weight: 500;
        background: #f1f5f9; padding: .25rem .6rem; border-radius: 20px;
    }

    .ln-cards {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 1rem; animation: ln-fadeUp .45s ease-out .15s both;
    }
    .ln-card {
        background: #fff; border-radius: 14px; border: 1px solid #e8ecf1;
        box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.03);
        overflow: hidden; transition: all .25s; display: flex; flex-direction: column;
    }
    .ln-card:hover { box-shadow: 0 4px 20px rgba(4,83,203,.08); transform: translateY(-2px); }

    .ln-card-head {
        display: flex; align-items: center; gap: .75rem;
        padding: 1.15rem 1.25rem; border-bottom: 1px solid #f1f5f9;
    }
    .ln-card-icon {
        width: 42px; height: 42px; border-radius: 11px;
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: .95rem; flex-shrink: 0;
    }
    .ln-card-title { font-size: .95rem; font-weight: 700; color: #1e293b; }
    .ln-card-sub { font-size: .78rem; color: #94a3b8; margin-top: .1rem; }

    .ln-card-metrics {
        display: grid; grid-template-columns: 1fr 1fr 1fr;
        padding: .85rem 1.25rem; gap: .5rem;
    }
    .ln-metric { display: flex; flex-direction: column; }
    .ln-metric-label { font-size: .68rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; }
    .ln-metric-value { font-size: 1.1rem; font-weight: 700; color: #1e293b; }

    .ln-card-foot {
        padding: .85rem 1.25rem; border-top: 1px solid #f1f5f9;
        display: flex; gap: .5rem; margin-top: auto;
    }
    .ln-card-btn {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .5rem 1rem; border-radius: 9px; font-size: .82rem;
        font-weight: 600; border: none; cursor: pointer; transition: all .2s;
        text-decoration: none; flex: 1; justify-content: center;
    }
    .ln-card-btn--primary {
        background: #0453cb; color: #fff;
        box-shadow: 0 2px 6px rgba(4,83,203,.2);
    }
    .ln-card-btn--primary:hover { background: #0340a0; color: #fff; text-decoration: none; }
    .ln-card-btn--outline {
        background: #fff; color: #0453cb; border: 1.5px solid #dbeafe;
    }
    .ln-card-btn--outline:hover { background: #eff6ff; border-color: #0453cb; color: #0453cb; text-decoration: none; }

    /* ── Empty ── */
    .ln-empty-card {
        background: #fff; border-radius: 14px; border: 1px solid #e8ecf1;
        box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.03);
    }
    .ln-empty { text-align: center; padding: 4rem 2rem; }
    .ln-empty-icon {
        width: 76px; height: 76px; border-radius: 20px; background: #f1f5f9;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 2rem; color: #cbd5e1; margin-bottom: 1.15rem;
    }
    .ln-empty-title { font-size: 1.1rem; font-weight: 700; color: #334155; margin-bottom: .4rem; }
    .ln-empty-text { font-size: .88rem; color: #94a3b8; max-width: 380px; margin: 0 auto; }

    /* ── Premium Modal ── */
    .ln-modal .modal-dialog { max-width: 95vw !important; width: 95vw !important; margin: 1rem auto; }
    .ln-modal .modal-content {
        border-radius: 18px; border: none;
        box-shadow: 0 25px 80px rgba(0,0,0,.2), 0 8px 24px rgba(4,83,203,.1);
        overflow: hidden;
    }
    .ln-modal .modal-header { position: relative; padding: 0; border: none; }
    .ln-modal-hero {
        padding: 1.5rem 2rem 1.25rem;
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 50%, #3b7ddb 100%);
        color: #fff; position: relative; overflow: hidden;
    }
    .ln-modal-hero::before {
        content: ''; position: absolute; top: -50%; right: -15%;
        width: 320px; height: 320px;
        background: radial-gradient(circle, rgba(255,255,255,.08) 0%, transparent 70%);
        pointer-events: none;
    }
    .ln-modal-hero-top {
        display: flex; align-items: center; justify-content: space-between;
        position: relative; z-index: 1;
    }
    .ln-modal-hero-left { display: flex; align-items: center; gap: .85rem; }
    .ln-modal-icon {
        width: 46px; height: 46px; border-radius: 12px;
        background: rgba(255,255,255,.15); backdrop-filter: blur(6px);
        border: 1px solid rgba(255,255,255,.2);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; color: #fff; flex-shrink: 0;
    }
    .ln-modal-title { font-size: 1.15rem; font-weight: 700; margin: 0; color: #fff; }
    .ln-modal-subtitle { font-size: .78rem; opacity: .7; margin-top: .15rem; }
    .ln-modal .btn-close { filter: brightness(0) invert(1); opacity: .7; position: relative; z-index: 2; }
    .ln-modal .btn-close:hover { opacity: 1; }

    /* Hero mini KPIs */
    .ln-modal-kpis {
        display: flex; gap: .6rem; margin-top: 1rem; position: relative; z-index: 1;
    }
    .ln-modal-kpi {
        background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15);
        border-radius: 8px; padding: .45rem .75rem;
        display: flex; align-items: center; gap: .5rem;
    }
    .ln-modal-kpi-val { font-size: 1rem; font-weight: 700; color: #fff; }
    .ln-modal-kpi-lbl { font-size: .7rem; color: rgba(255,255,255,.6); }

    /* Modal body */
    .ln-modal .modal-body { padding: 0; }

    .ln-modal-toolbar {
        display: flex; align-items: center; gap: .75rem; padding: 1rem 1.5rem;
        border-bottom: 1px solid #e8ecf1; flex-wrap: wrap; background: #fafbfc;
    }
    .ln-modal-toolbar select {
        border-radius: 9px; border: 1.5px solid #e2e8f0; padding: .45rem .75rem;
        font-size: .84rem; background: #fff; transition: all .2s;
    }
    .ln-modal-toolbar select:focus {
        border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.08); outline: none;
    }
    .ln-modal-add-btn {
        display: inline-flex; align-items: center; gap: .3rem;
        padding: .45rem .85rem; border-radius: 9px; font-size: .82rem;
        font-weight: 600; background: #059669; color: #fff; border: none;
        cursor: pointer; transition: all .2s; margin-left: auto;
        text-decoration: none;
    }
    .ln-modal-add-btn:hover { background: #047857; color: #fff; text-decoration: none; }

    /* ── Callout intro ── */
    .ln-callout {
        display: flex; align-items: center; gap: .75rem;
        padding: .85rem 1.5rem; background: #eff6ff; border-bottom: 1px solid #dbeafe;
    }
    .ln-callout-icon {
        width: 36px; height: 36px; border-radius: 10px;
        background: #0453cb; color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: .85rem; flex-shrink: 0;
    }
    .ln-callout-title { font-size: .88rem; font-weight: 700; color: #1e293b; }
    .ln-callout-text { font-size: .78rem; color: #64748b; }

    /* ── Notes grid (BTS-like premium) ── */
    .ln-grid-wrap {
        overflow-x: auto; max-height: 60vh; overflow-y: auto;
        border-bottom: 1px solid #e8ecf1;
    }
    .ln-grid {
        width: 100%; border-collapse: separate; border-spacing: 0; font-size: .84rem;
    }

    /* Period row */
    .ln-grid .ln-period-row th {
        padding: .45rem .65rem; font-size: .72rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: .06em;
        border-bottom: 2px solid #e2e8f0; white-space: nowrap; text-align: center;
        position: sticky; top: 0; z-index: 10;
    }
    .ln-period-student { background: #f1f5f9 !important; color: #475569; text-align: left !important; min-width: 185px; position: sticky; left: 0; z-index: 12 !important; }
    .ln-period-sem { background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff; }
    .ln-period-synth { background: #1e293b; color: #fff; }

    /* Eval header row */
    .ln-grid .ln-eval-row th {
        padding: .5rem .5rem .6rem; background: #f8fafc;
        border-bottom: 2px solid #e2e8f0; vertical-align: top;
        position: sticky; top: 33px; z-index: 9; white-space: nowrap; text-align: center;
    }
    .ln-eval-row .ln-col-student { text-align: left !important; min-width: 185px; position: sticky; left: 0; z-index: 11 !important; background: #f8fafc; font-size: .72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .04em; }
    .ln-eval-row .ln-col-avg { position: sticky; right: 110px; z-index: 11 !important; background: #f8fafc; font-size: .72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .04em; }
    .ln-eval-row .ln-col-appr { position: sticky; right: 0; z-index: 11 !important; background: #f8fafc; font-size: .72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .04em; width: 110px; }

    .ln-eval-th { min-width: 130px; border-left: 3px solid #e2e8f0; }
    .ln-eval-title { font-size: .74rem; font-weight: 700; color: #1e293b; text-transform: none; letter-spacing: 0; max-width: 120px; overflow: hidden; text-overflow: ellipsis; margin: 0 auto; }
    .ln-eval-controls { display: flex; gap: .35rem; justify-content: center; margin-top: .3rem; }
    .ln-eval-ctrl { display: flex; align-items: center; gap: .15rem; }
    .ln-eval-ctrl-label { font-size: .6rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; }
    .ln-eval-ctrl-val { font-size: .72rem; font-weight: 700; color: #0453cb; background: #eff6ff; padding: .1rem .35rem; border-radius: 4px; }
    .ln-eval-type-badge {
        display: inline-block; font-size: .62rem; font-weight: 600; text-transform: uppercase;
        padding: .12rem .4rem; border-radius: 4px; margin-top: .25rem;
        background: #f1f5f9; color: #64748b;
    }
    .ln-eval-sem-badge {
        display: inline-block; font-size: .58rem; font-weight: 700; text-transform: uppercase;
        padding: .1rem .35rem; border-radius: 4px; margin-top: .2rem;
    }
    .ln-sem-odd { background: #dbeafe; color: #1d4ed8; }
    .ln-sem-even { background: #d1fae5; color: #065f46; }

    /* Student column */
    .ln-grid tbody td {
        padding: .4rem .5rem; border-bottom: 1px solid #f1f5f9;
        vertical-align: middle; text-align: center;
    }
    .ln-grid tbody td:first-child { text-align: left; position: sticky; left: 0; z-index: 3; background: #fff; min-width: 185px; }
    .ln-grid tbody tr:hover td { background: #f8fbff; }
    .ln-grid tbody tr:hover td:first-child { background: #f0f6ff; }
    .ln-grid tbody .ln-td-avg { position: sticky; right: 110px; z-index: 2; background: #fff; }
    .ln-grid tbody .ln-td-appr { position: sticky; right: 0; z-index: 2; background: #fff; width: 110px; }
    .ln-grid tbody tr:hover .ln-td-avg,
    .ln-grid tbody tr:hover .ln-td-appr { background: #f8fbff; }

    /* Student info cell */
    .ln-stu-cell { display: flex; align-items: center; gap: .55rem; }
    .ln-stu-avatar {
        width: 32px; height: 32px; border-radius: 8px;
        background: linear-gradient(135deg, #0453cb, #5e91de);
        color: #fff; display: flex; align-items: center; justify-content: center;
        font-size: .7rem; font-weight: 700; flex-shrink: 0;
    }
    .ln-student-name { font-weight: 600; color: #1e293b; font-size: .82rem; white-space: nowrap; }
    .ln-student-mat { font-size: .68rem; color: #94a3b8; font-family: 'SF Mono', SFMono-Regular, monospace; }

    /* Note input */
    .ln-note-input {
        width: 52px; padding: .28rem .3rem; border: 1.5px solid #e2e8f0;
        border-radius: 6px; font-size: .84rem; text-align: center;
        font-weight: 600; transition: all .2s; background: #fff;
        font-variant-numeric: tabular-nums;
    }
    .ln-note-input:focus { border-color: #0453cb; box-shadow: 0 0 0 2px rgba(4,83,203,.1); outline: none; }
    .ln-note-input:disabled { background: #f1f5f9; color: #94a3b8; }
    .ln-note-input.ln-saved { border-color: #10b981; background: #f0fdf4; }

    .ln-abs-wrap { display: flex; align-items: center; }
    .ln-abs-check { display: none; }
    .ln-abs-label {
        width: 18px; height: 18px; border-radius: 4px; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        font-size: .6rem; color: #cbd5e1; transition: all .15s;
    }
    .ln-abs-label:hover { color: #ef4444; background: #fef2f2; }
    .ln-abs-check:checked + .ln-abs-label { color: #fff; background: #ef4444; }

    .ln-note-cell { display: flex; align-items: center; gap: .2rem; justify-content: center; }

    /* Average & Appreciation columns */
    .ln-avg { font-weight: 700; font-size: .88rem; }
    .ln-avg--pass { color: #059669; }
    .ln-avg--fail { color: #dc2626; }

    .ln-appr {
        display: inline-block; font-size: .68rem; font-weight: 600;
        padding: .15rem .5rem; border-radius: 20px; white-space: nowrap;
    }
    .ln-appr--excellent { background: rgba(16,185,129,.12); color: #065f46; }
    .ln-appr--tres-bien { background: rgba(4,83,203,.1); color: #1e40af; }
    .ln-appr--bien { background: rgba(59,130,246,.1); color: #1d4ed8; }
    .ln-appr--assez-bien { background: #f1f5f9; color: #475569; }
    .ln-appr--passable { background: #fef3c7; color: #92400e; }
    .ln-appr--insuffisant { background: rgba(239,68,68,.08); color: #991b1b; }
    .ln-appr--default { color: #cbd5e1; }

    /* Class averages row */
    .ln-grid tfoot td {
        padding: .55rem .65rem; background: #f0f6ff; border-top: 2px solid #dbeafe;
        font-weight: 700; color: #0453cb; font-size: .84rem; text-align: center;
    }
    .ln-grid tfoot td:first-child { text-align: left; position: sticky; left: 0; z-index: 3; background: #f0f6ff; }
    .ln-grid tfoot .ln-foot-avg { position: sticky; right: 110px; z-index: 3; background: #f0f6ff; }
    .ln-grid tfoot .ln-foot-appr { position: sticky; right: 0; z-index: 3; background: #f0f6ff; width: 110px; }

    /* Empty / loading states */
    .ln-modal-empty { text-align: center; padding: 3rem 2rem; color: #94a3b8; }
    .ln-modal-empty i { font-size: 2rem; opacity: .4; display: block; margin-bottom: .75rem; }
    .ln-modal-loading { text-align: center; padding: 3rem 2rem; color: #94a3b8; }
    .ln-modal-loading i { font-size: 2rem; }
    .ln-autosave-info {
        padding: .6rem 1.5rem; background: #f0fdf4; border-top: 1px solid #bbf7d0;
        font-size: .78rem; color: #059669; display: flex; align-items: center; gap: .4rem;
    }

    /* Modal footer */
    .ln-modal .modal-footer {
        border-top: 1px solid #e8ecf1; padding: .85rem 1.5rem;
        background: #fafbfc; display: flex; gap: .5rem; justify-content: flex-end;
    }
    .ln-modal-fbtn {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .5rem 1rem; border-radius: 9px; font-size: .82rem;
        font-weight: 600; border: none; cursor: pointer; transition: all .2s;
    }
    .ln-modal-fbtn--close { background: #fff; color: #64748b; border: 1.5px solid #e2e8f0; }
    .ln-modal-fbtn--close:hover { background: #f1f5f9; }
    .ln-modal-fbtn--action { background: #0453cb; color: #fff; text-decoration: none; }
    .ln-modal-fbtn--action:hover { background: #0340a0; color: #fff; text-decoration: none; }

    /* ── Eval create modal ── */
    .ln-eval-modal .modal-content { border-radius: 16px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,.18); overflow: hidden; }
    .ln-eval-modal-hero {
        padding: 1.25rem 1.5rem;
        background: linear-gradient(135deg, #059669 0%, #10b981 100%);
        color: #fff;
    }
    .ln-eval-modal-hero h5 { font-size: 1.05rem; font-weight: 700; margin: 0; color: #fff; }
    .ln-eval-modal-hero p { font-size: .78rem; opacity: .8; margin: .15rem 0 0; }
    .ln-eval-modal .btn-close { filter: brightness(0) invert(1); opacity: .7; }
    .ln-eval-autopub {
        display: flex; align-items: center; gap: .5rem;
        padding: .6rem 1.25rem; background: #f0fdf4; border-bottom: 1px solid #bbf7d0;
        font-size: .78rem; color: #059669;
    }
    .ln-eval-section {
        padding: .85rem 1.25rem; border-bottom: 1px solid #f1f5f9;
    }
    .ln-eval-section-title {
        font-size: .72rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;
        letter-spacing: .05em; margin-bottom: .6rem;
    }
    .ln-eval-row-fields { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
    .ln-eval-row-fields-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: .75rem; }
    .ln-eval-field label {
        font-size: .78rem; font-weight: 600; color: #475569; margin-bottom: .25rem; display: block;
    }
    .ln-eval-field input, .ln-eval-field select, .ln-eval-field textarea {
        width: 100%; border: 1.5px solid #e2e8f0; border-radius: 8px;
        padding: .4rem .65rem; font-size: .84rem; transition: all .2s;
    }
    .ln-eval-field input:focus, .ln-eval-field select:focus, .ln-eval-field textarea:focus {
        border-color: #059669; box-shadow: 0 0 0 3px rgba(5,150,105,.08); outline: none;
    }
    .ln-eval-field .is-invalid { border-color: #ef4444 !important; }
    .ln-eval-field .invalid-feedback { font-size: .72rem; color: #ef4444; margin-top: .15rem; }
    .ln-eval-duree-badge {
        display: inline-block; font-size: .72rem; font-weight: 600;
        padding: .15rem .5rem; border-radius: 20px; background: #f0fdf4; color: #059669;
        margin-top: .3rem;
    }
    .ln-eval-errors {
        margin: .75rem 1.25rem; padding: .6rem .85rem; border-radius: 8px;
        background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; font-size: .8rem;
        display: none;
    }
    .ln-eval-submit-btn {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .55rem 1.25rem; border-radius: 9px; font-size: .88rem;
        font-weight: 600; background: #059669; color: #fff; border: none;
        cursor: pointer; transition: all .2s;
    }
    .ln-eval-submit-btn:hover { background: #047857; }
    .ln-eval-submit-btn:disabled { opacity: .6; cursor: not-allowed; }

    /* Modal slide animation */
    .ln-modal.fade .modal-dialog { transform: translateY(20px) scale(.98); transition: transform .25s ease-out, opacity .2s; }
    .ln-modal.show .modal-dialog { transform: translateY(0) scale(1); }
    .ln-eval-modal.fade .modal-dialog { transform: translateY(20px) scale(.98); transition: transform .25s ease-out, opacity .2s; }
    .ln-eval-modal.show .modal-dialog { transform: translateY(0) scale(1); }

    /* ── Animations ── */
    @keyframes ln-fadeDown { from { opacity: 0; transform: translateY(-12px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes ln-fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    @media (max-width: 768px) {
        .ln-hero { padding: 1.5rem; border-radius: 14px; }
        .ln-hero-top { flex-direction: column; }
        .ln-hero-kpis { flex-direction: column; }
        .ln-cards { grid-template-columns: 1fr; }
        .ln-modal-toolbar { flex-direction: column; align-items: stretch; }
        .ln-modal-add-btn { margin-left: 0; }
    }
</style>
@endpush

@section('content')
<div class="ln-page">

    {{-- ══ Hero ══ --}}
    @php
        $totalClasses = $classes->count();
        $totalEtudiants = $classes->sum('etudiants_count');
        $totalEvals = $evalCounts->sum();
    @endphp

    <div class="ln-hero">
        <div class="ln-hero-top">
            <div class="ln-hero-left">
                <div class="ln-hero-icon"><i class="fas fa-edit"></i></div>
                <div class="ln-hero-info">
                    <h1>Notes LMD</h1>
                    <p>Gestion des notes par classe — {{ $anneeCourante->name ?? 'Aucune année' }}</p>
                </div>
            </div>
        </div>

        <div class="ln-hero-kpis">
            <div class="ln-kpi ln-kpi--classes">
                <div class="ln-kpi-icon"><i class="fas fa-layer-group"></i></div>
                <div>
                    <div class="ln-kpi-value">{{ $totalClasses }}</div>
                    <div class="ln-kpi-label">Classes LMD</div>
                </div>
            </div>
            <div class="ln-kpi ln-kpi--etudiants">
                <div class="ln-kpi-icon"><i class="fas fa-users"></i></div>
                <div>
                    <div class="ln-kpi-value">{{ $totalEtudiants }}</div>
                    <div class="ln-kpi-label">Étudiants actifs</div>
                </div>
            </div>
            <div class="ln-kpi ln-kpi--evals">
                <div class="ln-kpi-icon"><i class="fas fa-clipboard-check"></i></div>
                <div>
                    <div class="ln-kpi-value">{{ $totalEvals }}</div>
                    <div class="ln-kpi-label">Évaluations</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Flash messages --}}
    @foreach(['success' => 'check-circle', 'error' => 'exclamation-circle'] as $type => $icon)
        @if(session($type))
            <div class="alert alert-{{ $type === 'error' ? 'danger' : $type }} alert-dismissible fade show" role="alert" style="border-radius:10px;">
                <i class="fas fa-{{ $icon }} me-2"></i>{{ session($type) }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
    @endforeach

    {{-- ══ Classes grid ══ --}}
    @if($classes->isEmpty())
        <div class="ln-empty-card">
            <div class="ln-empty">
                <div class="ln-empty-icon"><i class="fas fa-layer-group"></i></div>
                <div class="ln-empty-title">Aucune classe LMD</div>
                <div class="ln-empty-text">Aucune classe utilisant le système LMD n'a été trouvée. Configurez d'abord vos classes.</div>
            </div>
        </div>
    @else
        <div class="ln-section-header">
            <div class="ln-section-title">
                <i class="fas fa-th-large"></i>
                Classes LMD
            </div>
            <span class="ln-section-count">{{ $totalClasses }} classe{{ $totalClasses > 1 ? 's' : '' }}</span>
        </div>

        <div class="ln-cards">
            @foreach($classes as $classe)
                @php $nbEvals = $evalCounts[$classe->id] ?? 0; @endphp
                <div class="ln-card">
                    <div class="ln-card-head">
                        <div class="ln-card-icon"><i class="fas fa-graduation-cap"></i></div>
                        <div>
                            <div class="ln-card-title">{{ $classe->name }}</div>
                            <div class="ln-card-sub">
                                {{ $classe->filiere->name ?? '' }}
                                @if($classe->niveau) &middot; {{ $classe->niveau->name ?? '' }} @endif
                            </div>
                        </div>
                    </div>

                    <div class="ln-card-metrics">
                        <div class="ln-metric">
                            <span class="ln-metric-label">Étudiants</span>
                            <span class="ln-metric-value">{{ $classe->etudiants_count }}</span>
                        </div>
                        <div class="ln-metric">
                            <span class="ln-metric-label">Évaluations</span>
                            <span class="ln-metric-value">{{ $nbEvals }}</span>
                        </div>
                        <div class="ln-metric">
                            <span class="ln-metric-label">Année</span>
                            <span class="ln-metric-value" style="font-size:.82rem; color:#64748b;">{{ $anneeCourante->name ?? '—' }}</span>
                        </div>
                    </div>

                    <div class="ln-card-foot">
                        <button type="button" class="ln-card-btn ln-card-btn--primary"
                                onclick="openNotesModal({{ $classe->id }}, @js($classe->name))">
                            <i class="fas fa-edit"></i>Gérer les notes
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>

{{-- ══════════════════════════════════════════════════════════ --}}
{{-- ══ MODAL — Gestion des notes (pattern BTS avec UE → ECUE) ══ --}}
{{-- ══════════════════════════════════════════════════════════ --}}
<div class="modal fade ln-modal" id="modalNotes" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div class="ln-modal-hero w-100">
                    <div class="ln-modal-hero-top">
                        <div class="ln-modal-hero-left">
                            <div class="ln-modal-icon"><i class="fas fa-edit"></i></div>
                            <div>
                                <h5 class="ln-modal-title">Gestion des Notes — <span id="notesModalTitle">Sélectionnez une classe</span></h5>
                                <div class="ln-modal-subtitle" id="notesModalSubtitle">Sélectionnez une UE puis un ECUE pour saisir les notes</div>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="ln-modal-kpis">
                        <div class="ln-modal-kpi">
                            <span class="ln-modal-kpi-val" id="nkpi_etudiants">—</span>
                            <span class="ln-modal-kpi-lbl">Étudiants</span>
                        </div>
                        <div class="ln-modal-kpi">
                            <span class="ln-modal-kpi-val" id="nkpi_evals">—</span>
                            <span class="ln-modal-kpi-lbl">Évaluations</span>
                        </div>
                        <div class="ln-modal-kpi">
                            <span class="ln-modal-kpi-val" id="nkpi_matieres">—</span>
                            <span class="ln-modal-kpi-lbl">ECUEs</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-body">
                {{-- Callout intro --}}
                <div class="ln-callout">
                    <div class="ln-callout-icon"><i class="fas fa-edit"></i></div>
                    <div>
                        <div class="ln-callout-title">Saisie intelligente des notes</div>
                        <div class="ln-callout-text">Choisissez une UE, un ECUE, créez des évaluations et saisissez les notes en temps réel.</div>
                    </div>
                </div>

                {{-- Toolbar: UE → ECUE selectors + dynamic periods --}}
                <div class="ln-modal-toolbar">
                    <select id="ueSelect" style="min-width:220px;">
                        <option value="">— Choisir une UE —</option>
                    </select>
                    <select id="ecueSelect" style="min-width:220px;" disabled>
                        <option value="">— Choisir un ECUE —</option>
                    </select>
                    <select id="periodeFilter" style="min-width:120px;">
                        <option value="all">Toutes</option>
                        {{-- Options dynamiques peuplées par JS selon classe.semestres --}}
                    </select>
                    <button type="button" id="createEvalBtn" class="ln-modal-add-btn" style="display:none;"
                            onclick="openEvalCreateModal()">
                        <i class="fas fa-plus"></i> Créer évaluation
                    </button>
                </div>

                {{-- Loading --}}
                <div class="ln-modal-loading" id="notesLoading" style="display:none;">
                    <i class="fas fa-spinner fa-spin"></i>
                    <div style="margin-top:.75rem; font-size:.88rem;">Chargement des notes...</div>
                </div>

                {{-- Notes grid --}}
                <div class="ln-grid-wrap" id="notesGridWrap" style="display:none;">
                    <table class="ln-grid" id="notesGrid">
                        <thead></thead>
                        <tbody id="studentsRows"></tbody>
                        <tfoot id="classAvgRow"></tfoot>
                    </table>
                </div>

                {{-- Empty / initial state --}}
                <div class="ln-modal-empty" id="notesEmpty">
                    <i class="fas fa-hand-pointer"></i>
                    Sélectionnez une UE puis un ECUE pour afficher la grille de notes.
                </div>

                {{-- Auto-save info --}}
                <div class="ln-autosave-info" id="autosaveInfo" style="display:none;">
                    <i class="fas fa-check-circle"></i>
                    Les notes sont automatiquement enregistrées à chaque modification.
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="ln-modal-fbtn ln-modal-fbtn--close" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Fermer
                </button>
                <button type="button" class="ln-modal-fbtn ln-modal-fbtn--action" id="saveAllNotesBtn"
                        style="display:none;" onclick="saveAllNotes()">
                    <i class="fas fa-save"></i> Enregistrer tout
                </button>
            </div>
        </div>
    </div>
</div>
{{-- ══════════════════════════════════════════════════════════ --}}
{{-- ══ MODAL — Créer une évaluation LMD (auto-suffisant)  ══ --}}
{{-- ══════════════════════════════════════════════════════════ --}}
<div class="modal fade ln-eval-modal" id="lmdEvalCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header p-0 border-0">
                <div class="ln-eval-modal-hero w-100 d-flex align-items-start justify-content-between">
                    <div>
                        <h5><i class="fas fa-plus-circle me-2"></i>Créer une évaluation</h5>
                        <p id="evalModalContext">Classe — ECUE</p>
                    </div>
                    <button type="button" class="btn-close mt-1" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <div class="ln-eval-autopub">
                <i class="fas fa-check-circle"></i>
                <span><strong>Publication automatique</strong> — L'évaluation sera publiée immédiatement.</span>
            </div>

            <div class="ln-eval-errors" id="evalErrors"></div>

            <form id="evalCreateForm">
                @csrf
                <input type="hidden" name="classe_id" id="evalClasseId">
                <input type="hidden" name="matiere_id" id="evalMatiereId">
                <input type="hidden" name="embed" value="1">
                <input type="hidden" name="is_published" value="1">

                {{-- Section 1: Général --}}
                <div class="ln-eval-section">
                    <div class="ln-eval-section-title">Informations générales</div>
                    <div class="ln-eval-row-fields" style="margin-bottom:.65rem;">
                        <div class="ln-eval-field" style="grid-column: 1 / -1;">
                            <label for="evalTitre">Titre <span class="text-danger">*</span></label>
                            <input type="text" name="titre" id="evalTitre" maxlength="255" required
                                   placeholder="Ex: Examen final, Devoir surveillé 1...">
                        </div>
                    </div>
                    <div class="ln-eval-row-fields">
                        <div class="ln-eval-field">
                            <label for="evalType">Type <span class="text-danger">*</span></label>
                            <select name="type" id="evalType" required>
                                <option value="">— Choisir —</option>
                                <option value="devoir">Devoir</option>
                                <option value="examen">Examen</option>
                                <option value="controle">Contrôle</option>
                                <option value="quiz">Quiz</option>
                                <option value="tp">TP</option>
                                <option value="projet">Projet</option>
                            </select>
                        </div>
                        <div class="ln-eval-field">
                            <label for="evalPeriode">Période <span class="text-danger">*</span></label>
                            <select name="periode" id="evalPeriode" required>
                                <option value="">— Choisir —</option>
                                {{-- Options dynamiques peuplées par JS selon classe.semestres --}}
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Section 2: Date & Horaires --}}
                <div class="ln-eval-section">
                    <div class="ln-eval-section-title">Date & Horaires</div>
                    <div class="ln-eval-row-fields-3">
                        <div class="ln-eval-field">
                            <label for="evalDate">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date_evaluation" id="evalDate" required>
                        </div>
                        <div class="ln-eval-field">
                            <label for="evalDebut">Heure début <span class="text-danger">*</span></label>
                            <input type="time" name="heure_debut" id="evalDebut" value="08:00" required>
                        </div>
                        <div class="ln-eval-field">
                            <label for="evalFin">Heure fin <span class="text-danger">*</span></label>
                            <input type="time" name="heure_fin" id="evalFin" value="10:00" required>
                        </div>
                    </div>
                    <input type="hidden" name="duree_minutes" id="evalDuree">
                    <div class="ln-eval-duree-badge" id="evalDureeBadge">120 min</div>
                </div>

                {{-- Section 3: Barème & Coefficient --}}
                <div class="ln-eval-section">
                    <div class="ln-eval-section-title">Barème & Coefficient</div>
                    <div class="ln-eval-row-fields">
                        <div class="ln-eval-field">
                            <label for="evalBareme">Barème <span class="text-danger">*</span></label>
                            <input type="number" name="bareme" id="evalBareme" value="20" min="1" step="0.5" required>
                        </div>
                        <div class="ln-eval-field">
                            <label for="evalCoeff">Coefficient <span class="text-danger">*</span></label>
                            <input type="number" name="coefficient" id="evalCoeff" value="1" min="0.1" max="10" step="0.1" required>
                        </div>
                    </div>
                </div>

                {{-- Section 4: Description --}}
                <div class="ln-eval-section">
                    <div class="ln-eval-section-title">Description (optionnel)</div>
                    <div class="ln-eval-field">
                        <textarea name="description" id="evalDescription" rows="2"
                                  placeholder="Chapitres concernés, consignes aux étudiants..."></textarea>
                    </div>
                </div>
            </form>

            <div class="modal-footer" style="border-top:1px solid #e8ecf1; padding:.85rem 1.25rem;">
                <button type="button" class="ln-modal-fbtn ln-modal-fbtn--close" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button type="button" class="ln-eval-submit-btn" id="evalSubmitBtn" onclick="submitEvaluation()">
                    <i class="fas fa-plus-circle"></i> Créer l'évaluation
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// ══ State ══
let notesModal = null;
let evalCreateModal = null;
let currentClasseId = null;
let currentClasseData = null;
let currentMatiereId = null;
let currentMatiereName = '';
let evaluationsData = {};
let notesData = {};
let evalParamsCache = {};
let classeSemestres = []; // Dynamic semesters from class level
const canEditExistingNotes = @json(auth()->user()->can('notes.edit'));

document.addEventListener('DOMContentLoaded', function() {
    notesModal = new bootstrap.Modal(document.getElementById('modalNotes'));
    evalCreateModal = new bootstrap.Modal(document.getElementById('lmdEvalCreateModal'));

    // Auto-calc duration on time change
    ['evalDebut', 'evalFin'].forEach(id => {
        document.getElementById(id).addEventListener('change', updateEvalDuree);
    });
});

// ══ Open modal for a class ══
async function openNotesModal(classeId, classeName) {
    currentClasseId = classeId;
    currentMatiereId = null;
    currentMatiereName = '';

    // Reset UI
    document.getElementById('notesModalTitle').textContent = classeName;
    document.getElementById('notesModalSubtitle').textContent = 'Chargement...';
    document.getElementById('notesLoading').style.display = 'block';
    document.getElementById('notesGridWrap').style.display = 'none';
    document.getElementById('notesEmpty').style.display = 'none';
    document.getElementById('autosaveInfo').style.display = 'none';
    document.getElementById('createEvalBtn').style.display = 'none';
    document.getElementById('saveAllNotesBtn').style.display = 'none';
    document.getElementById('nkpi_etudiants').textContent = '—';
    document.getElementById('nkpi_evals').textContent = '—';
    document.getElementById('nkpi_matieres').textContent = '—';

    // Reset selects
    const ueSelect = document.getElementById('ueSelect');
    const ecueSelect = document.getElementById('ecueSelect');
    ueSelect.innerHTML = '<option value="">— Choisir une UE —</option>';
    ecueSelect.innerHTML = '<option value="">— Choisir un ECUE —</option>';
    ecueSelect.disabled = true;

    notesModal.show();

    try {
        const resp = await fetch('/esbtp/lmd/notes/classe/' + classeId + '/data', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        currentClasseData = await resp.json();
        const data = currentClasseData;

        // Store class semestres for dynamic period options
        classeSemestres = data.classe.semestres || [1, 2];

        // Update hero
        const sub = [data.classe.filiere, data.classe.niveau].filter(Boolean).join(' · ');
        document.getElementById('notesModalSubtitle').textContent = sub || 'Sélectionnez une UE puis un ECUE';

        // KPIs
        document.getElementById('nkpi_etudiants').textContent = data.etudiants.length;
        document.getElementById('nkpi_evals').textContent = data.evaluations.length;
        document.getElementById('nkpi_matieres').textContent = data.matieres.length;

        // Build dynamic period filter options
        const periodeFilter = document.getElementById('periodeFilter');
        periodeFilter.innerHTML = '<option value="all">Toutes</option>';
        classeSemestres.forEach(s => {
            const opt = document.createElement('option');
            opt.value = String(s);
            opt.textContent = 'Semestre ' + s;
            periodeFilter.appendChild(opt);
        });

        // Build UE list from matières (group by ue_code)
        const ueMap = {};
        data.matieres.forEach(m => {
            const key = m.ue_code || 'SANS_UE';
            if (!ueMap[key]) ueMap[key] = { code: m.ue_code, name: m.ue_name, ecues: [] };
            ueMap[key].ecues.push(m);
        });

        Object.values(ueMap).forEach(ue => {
            const opt = document.createElement('option');
            opt.value = ue.code || '';
            opt.textContent = (ue.code ? ue.code + ' — ' : '') + (ue.name || 'Sans UE');
            ueSelect.appendChild(opt);
        });

        currentClasseData._ueMap = ueMap;
        document.getElementById('notesLoading').style.display = 'none';

        // Si aucune UE liée → message explicatif
        if (data.matieres.length === 0) {
            document.getElementById('notesEmpty').innerHTML =
                '<i class="fas fa-exclamation-triangle" style="color:#d97706; font-size:2rem; opacity:.7; display:block; margin-bottom:.75rem;"></i>' +
                '<strong style="color:#1e293b;">Aucune UE liée à cette classe</strong><br>' +
                '<span style="font-size:.84rem;">Pour saisir des notes, vous devez d\'abord :</span>' +
                '<div style="text-align:left; max-width:360px; margin:.75rem auto 0; font-size:.84rem; color:#475569;">' +
                '1. Lier la classe à un <strong>parcours</strong> (dans Gestion des classes)<br>' +
                '2. Lier des <strong>UEs au parcours</strong> (dans <a href="{{ route("esbtp.lmd.parcours-domain.index") }}" style="color:#0453cb; text-decoration:underline;">Parcours LMD</a>)<br>' +
                '3. Les UEs doivent avoir des <strong>ECUEs (matières)</strong> rattachés' +
                '</div>';
        } else {
            document.getElementById('notesEmpty').innerHTML =
                '<i class="fas fa-hand-pointer"></i>Sélectionnez une UE puis un ECUE pour afficher la grille de notes.';
        }
        document.getElementById('notesEmpty').style.display = 'block';

    } catch (err) {
        console.error('Error loading classe data:', err);
        document.getElementById('notesLoading').innerHTML =
            '<i class="fas fa-exclamation-triangle" style="color:#dc2626;"></i>' +
            '<div style="margin-top:.75rem; color:#dc2626;">Erreur de chargement.</div>';
    }
}

// ══ UE selection → populate ECUEs ══
document.getElementById('ueSelect').addEventListener('change', function() {
    const ecueSelect = document.getElementById('ecueSelect');
    ecueSelect.innerHTML = '<option value="">— Choisir un ECUE —</option>';
    ecueSelect.disabled = true;
    currentMatiereId = null;
    currentMatiereName = '';

    document.getElementById('notesGridWrap').style.display = 'none';
    document.getElementById('autosaveInfo').style.display = 'none';
    document.getElementById('createEvalBtn').style.display = 'none';
    document.getElementById('saveAllNotesBtn').style.display = 'none';
    document.getElementById('notesEmpty').style.display = 'block';
    document.getElementById('notesEmpty').innerHTML =
        '<i class="fas fa-hand-pointer"></i>Sélectionnez un ECUE pour afficher la grille de notes.';

    const ueCode = this.value;
    if (!ueCode || !currentClasseData?._ueMap) return;

    const ue = currentClasseData._ueMap[ueCode];
    if (!ue) return;

    ue.ecues.forEach(m => {
        const opt = document.createElement('option');
        opt.value = m.id;
        opt.textContent = (m.code ? m.code + ' — ' : '') + m.name;
        ecueSelect.appendChild(opt);
    });
    ecueSelect.disabled = false;
});

// ══ ECUE selection → load evaluations & build grid ══
document.getElementById('ecueSelect').addEventListener('change', function() {
    const matiereId = this.value;
    if (!matiereId || !currentClasseId) return;
    currentMatiereId = parseInt(matiereId);
    currentMatiereName = this.options[this.selectedIndex]?.textContent || '';

    // Show create eval button
    document.getElementById('createEvalBtn').style.display = 'inline-flex';

    loadEvaluationsAndBuildGrid(currentClasseId, matiereId);
});

// ══ Period filter ══
document.getElementById('periodeFilter').addEventListener('change', function() {
    if (document.getElementById('ecueSelect').value) {
        buildNotesGrid();
    }
});

// ══ Load evaluations for class + matière (same API as BTS) ══
async function loadEvaluationsAndBuildGrid(classeId, matiereId) {
    document.getElementById('notesEmpty').style.display = 'none';
    document.getElementById('notesLoading').style.display = 'block';
    document.getElementById('notesGridWrap').style.display = 'none';
    document.getElementById('autosaveInfo').style.display = 'none';

    try {
        const resp = await fetch(`/esbtp/notes/api/evaluations/by-class-matiere/${classeId}/${matiereId}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await resp.json();

        if (!data.success) throw new Error(data.message || 'Erreur');

        evaluationsData = data.evaluations || {};
        notesData = data.notes || {};

        // Cache params
        evalParamsCache = {};
        Object.values(evaluationsData).forEach(ev => {
            evalParamsCache[ev.id] = { bareme: parseFloat(ev.bareme) || 20, coefficient: parseFloat(ev.coefficient) || 1 };
        });

        document.getElementById('notesLoading').style.display = 'none';

        if (Object.keys(evaluationsData).length === 0) {
            // No evaluations yet — show prompt to create one
            document.getElementById('notesEmpty').style.display = 'block';
            document.getElementById('notesEmpty').innerHTML =
                '<i class="fas fa-clipboard-list" style="color:#0453cb; font-size:2.5rem; opacity:.5; display:block; margin-bottom:.75rem;"></i>' +
                '<strong style="color:#1e293b; font-size:.95rem;">Aucune évaluation pour cet ECUE</strong><br>' +
                '<span style="font-size:.84rem; color:#64748b;">Cliquez sur le bouton vert ci-dessus pour créer votre première évaluation.</span>';
            return;
        }

        buildNotesGrid();

    } catch (err) {
        console.error('Error loading evaluations:', err);
        document.getElementById('notesLoading').style.display = 'none';
        document.getElementById('notesEmpty').style.display = 'block';
        document.getElementById('notesEmpty').innerHTML =
            '<i class="fas fa-exclamation-triangle" style="color:#dc2626;"></i>' +
            '<div style="margin-top:.5rem; color:#dc2626;">Erreur de chargement des évaluations.</div>';
    }
}

// ══ Build the notes grid (BTS premium pattern with period row + appreciation) ══
function buildNotesGrid() {
    const periodeFilter = document.getElementById('periodeFilter').value;
    const students = currentClasseData?.etudiants || [];

    // Filter & sort evaluations
    let evals = Object.values(evaluationsData);
    if (periodeFilter !== 'all') {
        evals = evals.filter(ev => String(parseSemestre(ev.periode)) === periodeFilter);
    }
    evals.sort((a, b) => {
        const sa = parseSemestre(a.periode), sb = parseSemestre(b.periode);
        if (sa !== sb) return sa - sb;
        return (a.date_evaluation || '').localeCompare(b.date_evaluation || '');
    });

    if (evals.length === 0) {
        document.getElementById('notesGridWrap').style.display = 'none';
        document.getElementById('notesEmpty').style.display = 'block';
        document.getElementById('saveAllNotesBtn').style.display = 'none';
        document.getElementById('notesEmpty').innerHTML =
            '<i class="fas fa-filter" style="color:#94a3b8;"></i>' +
            '<div style="margin-top:.5rem;">Aucune évaluation pour ce filtre de période.</div>';
        return;
    }

    // Group evals by semester
    const semGroups = {};
    evals.forEach(ev => {
        const s = parseSemestre(ev.periode);
        if (!semGroups[s]) semGroups[s] = [];
        semGroups[s].push(ev);
    });
    const semKeys = Object.keys(semGroups).sort((a, b) => a - b);

    // ── Period row ──
    let periodRowHtml = '<tr class="ln-period-row">';
    periodRowHtml += '<th class="ln-period-student">ÉTUDIANTS</th>';
    semKeys.forEach(s => {
        periodRowHtml += `<th colspan="${semGroups[s].length}" class="ln-period-sem">SEMESTRE ${s}</th>`;
    });
    periodRowHtml += '<th colspan="2" class="ln-period-synth">SYNTHÈSE</th>';
    periodRowHtml += '</tr>';

    // ── Eval header row ──
    let evalRowHtml = '<tr class="ln-eval-row">';
    evalRowHtml += '<th class="ln-col-student">ÉTUDIANTS</th>';
    evals.forEach(ev => {
        const sem = parseSemestre(ev.periode);
        const semClass = sem % 2 === 1 ? 'ln-sem-odd' : 'ln-sem-even';
        evalRowHtml += `<th class="ln-eval-th">
            <div class="ln-eval-title" title="${escHtml(ev.titre || '')}">${escHtml(ev.titre || ev.type_evaluation || 'Eval')}</div>
            <div class="ln-eval-controls">
                <div class="ln-eval-ctrl"><span class="ln-eval-ctrl-label">Bar</span><span class="ln-eval-ctrl-val">${ev.bareme || 20}</span></div>
                <div class="ln-eval-ctrl"><span class="ln-eval-ctrl-label">Coef</span><span class="ln-eval-ctrl-val">${ev.coefficient || 1}</span></div>
            </div>
            <div class="ln-eval-type-badge">${escHtml(ev.type_evaluation || ev.type || '')}</div>
            <div class="ln-eval-sem-badge ${semClass}">S${sem}</div>
        </th>`;
    });
    evalRowHtml += '<th class="ln-col-avg">MOYENNE</th>';
    evalRowHtml += '<th class="ln-col-appr">APPRÉCIATION</th>';
    evalRowHtml += '</tr>';

    // ── Student rows ──
    let bodyHtml = '';
    students.forEach(stu => {
        const stuNotes = notesData[stu.id] || {};
        const initials = ((stu.nom || '')[0] || '') + ((stu.prenoms || '')[0] || '');
        bodyHtml += `<tr data-student-id="${stu.id}">`;
        bodyHtml += `<td><div class="ln-stu-cell">
            <div class="ln-stu-avatar">${escHtml(initials.toUpperCase())}</div>
            <div><div class="ln-student-name">${escHtml(stu.nom)} ${escHtml(stu.prenoms)}</div>
            <div class="ln-student-mat">${escHtml(stu.matricule || '')}</div></div>
        </div></td>`;

        evals.forEach(ev => {
            const noteVal = stuNotes[ev.id] ?? '';
            const isAbsent = stuNotes[ev.id + '_absent'] || false;
            const hasExistingNote = noteVal !== '' && noteVal !== null;
            const isLocked = hasExistingNote && !canEditExistingNotes;
            const isDisabled = isAbsent || isLocked;
            const bareme = ev.bareme || 20;
            const uid = stu.id + '-' + ev.id;
            bodyHtml += `<td><div class="ln-note-cell">
                <input type="number" class="ln-note-input" step="0.25" min="0" max="${bareme}"
                       value="${isAbsent ? '' : noteVal}"
                       data-student-id="${stu.id}" data-eval-id="${ev.id}"
                       ${isDisabled ? 'disabled' : ''}
                       ${isLocked ? 'title="Vous n\'avez pas la permission de modifier les notes existantes"' : ''}
                       onchange="saveNote(${stu.id}, ${ev.id}, this.value)">
                <div class="ln-abs-wrap">
                    <input type="checkbox" class="ln-abs-check" id="abs-${uid}"
                           data-student-id="${stu.id}" data-eval-id="${ev.id}"
                           ${isAbsent ? 'checked' : ''} ${isLocked ? 'disabled' : ''}
                           onchange="toggleAbsence(${stu.id}, ${ev.id}, this.checked)">
                    <label class="ln-abs-label" for="abs-${uid}" title="${isLocked ? 'Verrouillé' : 'Absent'}">
                        <i class="fas fa-${isLocked ? 'lock' : 'user-slash'}"></i>
                    </label>
                </div>
            </div></td>`;
        });

        bodyHtml += `<td class="ln-td-avg"><span class="ln-avg" id="avg-${stu.id}">--</span></td>`;
        bodyHtml += `<td class="ln-td-appr"><span class="ln-appr ln-appr--default" id="appr-${stu.id}">--</span></td>`;
        bodyHtml += '</tr>';
    });

    // ── Class averages footer ──
    let footHtml = '<tr><td><strong>Moyenne Classe</strong></td>';
    evals.forEach(ev => {
        footHtml += `<td id="class-avg-${ev.id}">--</td>`;
    });
    footHtml += `<td class="ln-foot-avg" id="class-overall-avg">--</td>`;
    footHtml += '<td class="ln-foot-appr"></td></tr>';

    document.querySelector('#notesGrid thead').innerHTML = periodRowHtml + evalRowHtml;
    document.getElementById('studentsRows').innerHTML = bodyHtml;
    document.getElementById('classAvgRow').innerHTML = footHtml;
    document.getElementById('notesGridWrap').style.display = 'block';
    document.getElementById('notesEmpty').style.display = 'none';
    document.getElementById('autosaveInfo').style.display = 'flex';
    document.getElementById('saveAllNotesBtn').style.display = 'inline-flex';

    // Calculate
    students.forEach(stu => calculateStudentAverage(stu.id));
    calculateClassAverages();
}

// ══ Save a single note (AJAX) ══
function saveNote(studentId, evaluationId, noteValue) {
    const absCheckbox = document.querySelector(`.ln-abs-check[data-student-id="${studentId}"][data-eval-id="${evaluationId}"]`);
    const isAbsent = absCheckbox?.checked || false;
    const input = document.querySelector(`.ln-note-input[data-student-id="${studentId}"][data-eval-id="${evaluationId}"]`);

    fetch('{{ route("esbtp.notes.save-ajax") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            etudiant_id: studentId,
            evaluation_id: evaluationId,
            note: isAbsent ? 0 : (noteValue || 0),
            is_absent: isAbsent ? 'on' : '',
        })
    }).then(r => r.json()).then(data => {
        if (data.success && input) {
            input.classList.add('ln-saved');
            setTimeout(() => input.classList.remove('ln-saved'), 1200);
        }
        if (!notesData[studentId]) notesData[studentId] = {};
        notesData[studentId][evaluationId] = isAbsent ? 0 : parseFloat(noteValue || 0);
        notesData[studentId][evaluationId + '_absent'] = isAbsent;
        calculateStudentAverage(studentId);
        calculateClassAverages();
    }).catch(err => console.error('Save error:', err));
}

// ══ Save all notes at once ══
function saveAllNotes() {
    const inputs = document.querySelectorAll('.ln-note-input');
    const notes = [];
    inputs.forEach(inp => {
        const sid = inp.dataset.studentId;
        const eid = inp.dataset.evalId;
        const absCheck = document.querySelector(`.ln-abs-check[data-student-id="${sid}"][data-eval-id="${eid}"]`);
        const isAbsent = absCheck?.checked || false;
        const val = parseFloat(inp.value);
        if (!isNaN(val) || isAbsent) {
            notes.push({
                etudiant_id: parseInt(sid),
                evaluation_id: parseInt(eid),
                note: isAbsent ? 0 : (val || 0),
                is_absent: isAbsent ? 'on' : '',
            });
        }
    });
    if (notes.length === 0) return;

    const btn = document.getElementById('saveAllNotesBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';

    fetch('{{ route("esbtp.notes.save-ajax-bulk") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ notes })
    }).then(r => r.json()).then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Enregistrer tout';
        if (data.success) {
            // Flash all inputs green
            inputs.forEach(inp => {
                inp.classList.add('ln-saved');
                setTimeout(() => inp.classList.remove('ln-saved'), 1500);
            });
        }
    }).catch(err => {
        console.error('Bulk save error:', err);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Enregistrer tout';
    });
}

// ══ Toggle absence ══
function toggleAbsence(studentId, evaluationId, isAbsent) {
    const input = document.querySelector(`.ln-note-input[data-student-id="${studentId}"][data-eval-id="${evaluationId}"]`);
    if (input) {
        input.disabled = isAbsent;
        if (isAbsent) input.value = '';
    }
    saveNote(studentId, evaluationId, isAbsent ? 0 : (input?.value || 0));
}

// ══ Calculate student average + appreciation ══
function calculateStudentAverage(studentId) {
    const inputs = document.querySelectorAll(`.ln-note-input[data-student-id="${studentId}"]`);
    let totalPoints = 0, totalCoeff = 0;

    inputs.forEach(inp => {
        const evalId = inp.dataset.evalId;
        const absCheck = document.querySelector(`.ln-abs-check[data-student-id="${studentId}"][data-eval-id="${evalId}"]`);
        if (absCheck?.checked) return;
        const val = parseFloat(inp.value);
        if (isNaN(val)) return;
        const params = evalParamsCache[evalId] || { bareme: 20, coefficient: 1 };
        const normalized = (val / params.bareme) * 20;
        totalPoints += normalized * params.coefficient;
        totalCoeff += params.coefficient;
    });

    const avgEl = document.getElementById('avg-' + studentId);
    const apprEl = document.getElementById('appr-' + studentId);
    if (!avgEl) return;

    if (totalCoeff > 0) {
        const avg = totalPoints / totalCoeff;
        avgEl.textContent = avg.toFixed(2);
        avgEl.className = 'ln-avg ' + (avg >= 10 ? 'ln-avg--pass' : 'ln-avg--fail');

        // Appreciation badge
        const { text, cls } = getAppreciation(avg);
        if (apprEl) {
            apprEl.textContent = text;
            apprEl.className = 'ln-appr ' + cls;
        }
    } else {
        avgEl.textContent = '--';
        avgEl.className = 'ln-avg';
        if (apprEl) { apprEl.textContent = '--'; apprEl.className = 'ln-appr ln-appr--default'; }
    }
}

// ══ Calculate class averages ══
function calculateClassAverages() {
    const evals = Object.values(evaluationsData);

    evals.forEach(ev => {
        const inputs = document.querySelectorAll(`.ln-note-input[data-eval-id="${ev.id}"]`);
        let sum = 0, count = 0;
        inputs.forEach(inp => {
            const sid = inp.dataset.studentId;
            const absCheck = document.querySelector(`.ln-abs-check[data-student-id="${sid}"][data-eval-id="${ev.id}"]`);
            if (absCheck?.checked) return;
            const val = parseFloat(inp.value);
            if (!isNaN(val)) { sum += val; count++; }
        });
        const el = document.getElementById('class-avg-' + ev.id);
        if (el) el.textContent = count > 0 ? (sum / count).toFixed(2) : '--';
    });

    const students = currentClasseData?.etudiants || [];
    let totalAvg = 0, avgCount = 0;
    students.forEach(stu => {
        const avgEl = document.getElementById('avg-' + stu.id);
        if (avgEl && avgEl.textContent !== '--') {
            totalAvg += parseFloat(avgEl.textContent);
            avgCount++;
        }
    });
    const overallEl = document.getElementById('class-overall-avg');
    if (overallEl) overallEl.textContent = avgCount > 0 ? (totalAvg / avgCount).toFixed(2) : '--';
}

// ══ Evaluation creation ══
function openEvalCreateModal() {
    if (!currentClasseId || !currentMatiereId) return;

    // Set hidden fields
    document.getElementById('evalClasseId').value = currentClasseId;
    document.getElementById('evalMatiereId').value = currentMatiereId;

    // Set context label
    const classeName = document.getElementById('notesModalTitle').textContent;
    document.getElementById('evalModalContext').textContent = classeName + ' — ' + currentMatiereName;

    // Populate periode options from class semestres
    const periodeSelect = document.getElementById('evalPeriode');
    periodeSelect.innerHTML = '<option value="">— Choisir —</option>';
    classeSemestres.forEach(s => {
        const opt = document.createElement('option');
        opt.value = String(s);
        opt.textContent = 'Semestre ' + s;
        periodeSelect.appendChild(opt);
    });

    // Pre-select current period filter if not "all"
    const currentFilter = document.getElementById('periodeFilter').value;
    if (currentFilter !== 'all') {
        periodeSelect.value = currentFilter;
    }

    // Set default date to today
    document.getElementById('evalDate').value = new Date().toISOString().split('T')[0];

    // Reset form
    document.getElementById('evalTitre').value = '';
    document.getElementById('evalType').value = '';
    document.getElementById('evalDescription').value = '';
    document.getElementById('evalBareme').value = '20';
    document.getElementById('evalCoeff').value = '1';
    document.getElementById('evalDebut').value = '08:00';
    document.getElementById('evalFin').value = '10:00';
    document.getElementById('evalErrors').style.display = 'none';

    // Remove validation classes
    document.querySelectorAll('#evalCreateForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('#evalCreateForm .invalid-feedback').forEach(el => el.remove());

    updateEvalDuree();
    evalCreateModal.show();
}

function updateEvalDuree() {
    const debut = document.getElementById('evalDebut').value;
    const fin = document.getElementById('evalFin').value;
    if (!debut || !fin) return;
    const [dh, dm] = debut.split(':').map(Number);
    const [fh, fm] = fin.split(':').map(Number);
    let diff = (fh * 60 + fm) - (dh * 60 + dm);
    if (diff <= 0) diff += 24 * 60;
    document.getElementById('evalDuree').value = diff;
    document.getElementById('evalDureeBadge').textContent = diff + ' min';
}

async function submitEvaluation() {
    const btn = document.getElementById('evalSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Création...';

    // Clear previous errors
    document.getElementById('evalErrors').style.display = 'none';
    document.querySelectorAll('#evalCreateForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('#evalCreateForm .invalid-feedback').forEach(el => el.remove());

    const form = document.getElementById('evalCreateForm');
    const formData = new FormData(form);

    try {
        const resp = await fetch('{{ route("esbtp.evaluations.store") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: formData
        });

        const data = await resp.json();

        if (!resp.ok || !data.success) {
            // Show validation errors
            if (data.errors) {
                const errDiv = document.getElementById('evalErrors');
                errDiv.innerHTML = '<strong>Erreurs :</strong><ul style="margin:.25rem 0 0; padding-left:1.2rem;">' +
                    Object.values(data.errors).flat().map(e => '<li>' + escHtml(e) + '</li>').join('') + '</ul>';
                errDiv.style.display = 'block';

                // Mark invalid fields
                Object.keys(data.errors).forEach(field => {
                    const input = form.querySelector(`[name="${field}"]`);
                    if (input) {
                        input.classList.add('is-invalid');
                        const fb = document.createElement('div');
                        fb.className = 'invalid-feedback';
                        fb.style.display = 'block';
                        fb.textContent = data.errors[field][0];
                        input.parentNode.appendChild(fb);
                    }
                });
            }
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus-circle"></i> Créer l\'évaluation';
            return;
        }

        // Success → close modal, reload evaluations
        evalCreateModal.hide();
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plus-circle"></i> Créer l\'évaluation';

        // Reload grid with new evaluation
        if (currentClasseId && currentMatiereId) {
            loadEvaluationsAndBuildGrid(currentClasseId, currentMatiereId);
        }

        // Update eval count KPI
        const kpiEl = document.getElementById('nkpi_evals');
        if (kpiEl) kpiEl.textContent = parseInt(kpiEl.textContent || 0) + 1;

    } catch (err) {
        console.error('Eval create error:', err);
        document.getElementById('evalErrors').textContent = 'Erreur réseau. Veuillez réessayer.';
        document.getElementById('evalErrors').style.display = 'block';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plus-circle"></i> Créer l\'évaluation';
    }
}

// ══ Helpers ══
function parseSemestre(p) {
    if (!p) return 1;
    const s = String(p).toLowerCase().replace(/[^0-9]/g, '');
    return parseInt(s) || 1;
}

function getAppreciation(avg) {
    if (avg >= 16) return { text: 'Excellent', cls: 'ln-appr--excellent' };
    if (avg >= 14) return { text: 'Très bien', cls: 'ln-appr--tres-bien' };
    if (avg >= 12) return { text: 'Bien', cls: 'ln-appr--bien' };
    if (avg >= 10) return { text: 'Assez bien', cls: 'ln-appr--assez-bien' };
    if (avg >= 8)  return { text: 'Passable', cls: 'ln-appr--passable' };
    return { text: 'Insuffisant', cls: 'ln-appr--insuffisant' };
}

function escHtml(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}
</script>
@endpush
