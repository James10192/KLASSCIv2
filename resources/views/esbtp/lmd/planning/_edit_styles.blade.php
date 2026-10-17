{{-- Styles pour l'édition inline des planifications LMD (cellules + modal teacher).
     Namespaces : lpe-* (cellule éditable), lpt-* (modal teacher picker). --}}
@once
@push('styles')
<style>
/* ============ Cellule éditable (lpe-*) ============ */
.lpe-cell {
    cursor: pointer;
    position: relative;
    z-index: 0;
    transition: background-color .12s ease, box-shadow .12s ease;
}
.lpe-cell:hover {
    background-color: rgba(4, 83, 203, .06);
    box-shadow: inset 0 0 0 1px rgba(4, 83, 203, .25);
}
.lpe-cell:hover::after {
    content: "\f303"; /* fa pencil-alt */
    font-family: "Font Awesome 5 Free", "FontAwesome";
    font-weight: 900;
    position: absolute;
    top: 4px;
    right: 4px;
    font-size: .55rem;
    color: rgba(4, 83, 203, .55);
}
.lpe-cell--editing {
    background-color: #fff !important;
    box-shadow: inset 0 0 0 2px #0453cb !important;
    padding: 0 !important;
}
.lpe-cell--saving {
    opacity: .5;
    pointer-events: none;
    background-color: rgba(4, 83, 203, .08) !important;
}
.lpe-cell--saved {
    animation: lpe-flash 0.6s ease-out;
}
@@keyframes lpe-flash {
    0% { background-color: rgba(16, 185, 129, .25); }
    100% { background-color: transparent; }
}
.lpe-cell--error {
    box-shadow: inset 0 0 0 2px #dc2626 !important;
    background-color: rgba(220, 38, 38, .06) !important;
}
.lpe-input {
    width: 100%;
    height: 100%;
    border: none;
    outline: none;
    background: transparent;
    text-align: right;
    font: inherit;
    font-variant-numeric: tabular-nums;
    color: #0f172a;
    padding: .65rem 1rem;
    -moz-appearance: textfield;
}
.lpe-input::-webkit-outer-spin-button,
.lpe-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

.lpe-teacher-btn {
    background: transparent;
    border: 1px dashed #cbd5e1;
    color: #64748b;
    padding: .25rem .65rem;
    border-radius: 999px;
    font-size: .75rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    transition: all .15s ease;
    max-width: min(280px, 100%);
    min-width: 0;
}
.lpe-teacher-btn .lpe-teacher-name {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    min-width: 0;
}
.lpe-teacher-btn:hover {
    border-color: #0453cb;
    color: #0453cb;
    background: rgba(4, 83, 203, .06);
}
.lpe-teacher-btn--assigned {
    border: 1px solid rgba(4, 83, 203, .35);
    background: rgba(4, 83, 203, .08);
    color: #033a8e;
}
.lpe-teacher-btn--assigned:hover {
    border-color: #0453cb;
    background: rgba(4, 83, 203, .13);
    color: #033a8e;
}
.lpe-teacher-readonly {
    color: #334155;
    font-size: .78rem;
    overflow-wrap: anywhere;
}
.lpe-teacher-btn i {
    font-size: .68rem;
}

.lpe-readonly-hint {
    font-size: .7rem;
    color: #94a3b8;
    font-style: italic;
}

/* ============ Responsable UE chip (lpe-resp-*) ============ */
/* W1.2 — directive UEMOA 03/2007/CM : 1 responsable par UE.
   Namespace dédié (lpe-resp-*) pour distinguer visuellement de
   l'assignation enseignant ECUE (lpe-teacher-*) ; même palette
   monochrome bleu KLASSCI mais bouton plus distinctif (border solide,
   icon user-tie). */
.lpe-resp-btn {
    background: transparent;
    border: 1px dashed rgba(4, 83, 203, .35);
    color: #0453cb;
    padding: .25rem .65rem;
    border-radius: 999px;
    font-size: .75rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    transition: all .15s ease;
    max-width: 100%;
}
.lpe-resp-btn:hover {
    border-color: #0453cb;
    background: rgba(4, 83, 203, .08);
    color: #033a8e;
}
.lpe-resp-btn--assigned {
    border: 1px solid rgba(4, 83, 203, .4);
    background: rgba(4, 83, 203, .1);
    color: #033a8e;
    border-style: solid;
}
.lpe-resp-btn--assigned:hover {
    border-color: #0453cb;
    background: rgba(4, 83, 203, .15);
}
.lpe-resp-btn i {
    font-size: .72rem;
    flex-shrink: 0;
}
.lpe-resp-btn .lpe-resp-name {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.lpe-resp-readonly {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    font-size: .75rem;
    color: #64748b;
}
.lpe-resp-readonly i {
    color: #94a3b8;
    font-size: .72rem;
}

/* ============ Modal teacher picker (lpt-*) ============ */
.lpt-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, .55);
    z-index: 2050;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    opacity: 0;
    pointer-events: none;
    transition: opacity .18s ease;
}
.lpt-backdrop--open {
    opacity: 1;
    pointer-events: auto;
}
.lpt-modal {
    background: #fff;
    border-radius: 16px;
    max-width: 560px;
    width: 100%;
    min-height: 0;
    max-height: min(90dvh, 760px);
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(15, 23, 42, .25);
    display: flex;
    flex-direction: column;
    transform: translateY(8px);
    transition: transform .18s ease;
}
.lpt-backdrop--open .lpt-modal { transform: translateY(0); }
.lpt-header {
    padding: 1.1rem 1.5rem;
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    color: #0f172a;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}
.lpt-header h3 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: .6rem;
}
.lpt-header-meta {
    font-size: .75rem;
    color: #64748b;
    margin-top: .25rem;
    overflow-wrap: anywhere;
}
.lpt-close {
    background: #f1f5f9;
    border: none;
    color: #64748b;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.lpt-close:hover { background: #e2e8f0; color: #0453cb; }
.lpt-body {
    padding: 1.25rem 1.5rem;
    overflow-y: auto;
    overscroll-behavior: contain;
    flex: 1 1 auto;
    min-height: 0;
}
.lpt-body .au-up {
    width: 100%;
    margin-bottom: 1rem;
}
.lpt-body .au-up-trigger { width: 100%; }
.lpt-body .au-up-menu { z-index: 20; }
.lpt-context-hint {
    color: #64748b;
    background: #f8fafc;
    border-radius: 10px;
    font-size: .79rem;
    line-height: 1.5;
    padding: .65rem .85rem;
    margin: 0 0 1rem;
}
.lpt-context-hint i { color: #0453cb; margin-right: .35rem; }
.lpt-section-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: .75rem;
    margin: .75rem 0;
    font-size: .8rem;
    font-weight: 700;
    color: #1e293b;
}
.lpt-count {
    background: #eaf2ff;
    color: #0453cb;
    border-radius: 999px;
    padding: .22rem .6rem;
    font-size: .7rem;
}
.lpt-selected-list, .lpt-teacher-pool {
    display: flex;
    flex-direction: column;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
}
.lpt-selected-list { max-height: 235px; overflow-y: auto; background: #f8fbff; }
.lpt-teacher-pool { max-height: 255px; overflow-y: auto; background: #fff; }
.lpt-selected-option, .lpt-available-option {
    display: flex;
    align-items: center;
    gap: .7rem;
    padding: .65rem .8rem;
    min-width: 0;
    border: 0;
    border-bottom: 1px solid #edf2f8;
    background: transparent;
    text-align: left;
    color: #1e293b;
}
.lpt-selected-option:last-child, .lpt-available-option:last-child { border-bottom: 0; }
.lpt-available-option { cursor: pointer; width: 100%; }
.lpt-available-option:hover, .lpt-available-option:focus-visible { background: #edf5ff; outline-color: #0453cb; }
.lpt-avatar {
    width: 34px;
    height: 34px;
    flex: 0 0 34px;
    border-radius: 10px;
    display: grid;
    place-items: center;
    background: #e8f0ff;
    color: #0453cb;
    font-size: .73rem;
    font-weight: 750;
}
.lpt-option-details { display: flex; flex-direction: column; min-width: 0; flex: 1; }
.lpt-option-details strong { font-size: .82rem; overflow-wrap: anywhere; }
.lpt-option-details small { font-size: .7rem; color: #64748b; overflow-wrap: anywhere; }
.lpt-available-option > i { color: #0453cb; font-size: 1rem; }
.lpt-remove {
    border: 1px solid #cdddf3;
    background: #fff;
    color: #64748b;
    width: 30px;
    height: 30px;
    border-radius: 8px;
    cursor: pointer;
}
.lpt-remove:hover { border-color: #dc2626; color: #b91c1c; }
.lpt-available-title { margin-top: 1.25rem; }
.lpt-search-wrap {
    position: relative;
    margin: 0 0 .65rem;
}
.lpt-search-wrap > i {
    position: absolute;
    left: .85rem;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
}
.lpt-search-wrap input {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    padding: .7rem .8rem .7rem 2.5rem;
    background: #fff;
    color: #0f172a;
    font-size: .83rem;
    outline: none;
}
.lpt-search-wrap input:focus { border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.12); }
.lpt-load-error {
    display: flex;
    flex-direction: column;
    gap: .65rem;
    padding: 1rem;
    background: #fff5f5;
    border: 1px solid #fecaca;
    border-radius: 10px;
    color: #b91c1c;
    font-size: .82rem;
}
.lpt-load-error button { align-self: flex-start; background: #fff; color: #0453cb; border: 1px solid #cbd5e1; border-radius: 8px; padding: .4rem .8rem; cursor: pointer; }
.lpt-empty-hint {
    background: #f8fafc;
    padding: .75rem 1rem;
    border-radius: 8px;
    border: 1px dashed #e2e8f0;
    color: #64748b;
    font-size: .8rem;
    display: flex;
    align-items: center;
    gap: .5rem;
    font-style: italic;
}
.lpt-empty-hint i { color: #cbd5e1; font-size: .9rem; }
.lpt-actions {
    padding: .85rem 1.5rem;
    border-top: 1px solid #f1f5f9;
    display: flex;
    justify-content: flex-end;
    gap: .5rem;
    background: #f8fafc;
    flex-shrink: 0;
    flex-wrap: wrap;
}
.lpt-btn {
    padding: .5rem 1rem;
    border-radius: 9px;
    font-size: .85rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all .15s ease;
}
.lpt-btn-secondary { background: #e2e8f0; color: #1e293b; }
.lpt-btn-secondary:hover { background: #cbd5e1; }
.lpt-btn-danger { background: #fee2e2; color: #b91c1c; }
.lpt-btn-danger:hover { background: #fecaca; }
.lpt-btn-primary { background: #0453cb; color: #fff; }
.lpt-btn-primary:hover { background: #033a8e; }
.lpt-btn:disabled { opacity: .5; cursor: not-allowed; }

.lpt-loading {
    padding: 2rem;
    text-align: center;
    color: #64748b;
    font-size: .9rem;
}
.lpt-loading i {
    font-size: 1.5rem;
    margin-bottom: .5rem;
    color: #0453cb;
    animation: lpt-spin .7s linear infinite;
    display: block;
}
@@keyframes lpt-spin { to { transform: rotate(360deg); } }

.lpt-toast {
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    z-index: 3000;
    padding: .75rem 1.1rem;
    border-radius: 10px;
    font-size: .85rem;
    font-weight: 600;
    color: #fff;
    box-shadow: 0 8px 30px rgba(15, 23, 42, .25);
    display: flex;
    align-items: center;
    gap: .6rem;
    opacity: 0;
    transform: translateY(8px);
    pointer-events: none;
    transition: opacity .2s ease, transform .2s ease;
}
.lpt-toast--show {
    opacity: 1;
    transform: translateY(0);
    pointer-events: auto;
}
.lpt-toast--success { background: linear-gradient(135deg, #059669, #10b981); }
.lpt-toast--error { background: linear-gradient(135deg, #b91c1c, #dc2626); }

/* ============ Widget progression volumes (lpv-*) ============ */
.lpv-cell {
    padding: .6rem 1rem .6rem 2rem;
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
}
.lpv-bars {
    display: flex;
    gap: 1.25rem;
    flex-wrap: wrap;
}
.lpv-bar-group {
    display: flex;
    align-items: center;
    gap: .5rem;
    min-width: 200px;
    flex: 1;
}
.lpv-bar-label {
    font-size: .72rem;
    font-weight: 600;
    color: #475569;
    width: 80px;
    flex-shrink: 0;
    display: flex;
    justify-content: space-between;
}
.lpv-bar-nums {
    font-size: .68rem;
    color: #94a3b8;
    font-weight: 400;
}
.lpv-bar-sep { margin: 0 .15rem; }
.lpv-bar-track {
    flex: 1;
    height: 6px;
    background: #e2e8f0;
    border-radius: 999px;
    overflow: hidden;
}
.lpv-bar-fill {
    height: 100%;
    background: #0453cb;
    border-radius: 999px;
    transition: width .4s ease;
}
.lpv-bar-fill--warn { background: #f59e0b; }
.lpv-bar-fill--done { background: #10b981; }
.lpv-bar-pct {
    font-size: .68rem;
    color: #64748b;
    width: 34px;
    text-align: right;
    flex-shrink: 0;
}
.lpv-loading {
    font-size: .75rem;
    color: #94a3b8;
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .25rem 0;
}

@@media (max-width: 640px) {
    .lpt-modal {
        min-height: 0;
        height: auto;
        max-height: calc(100dvh - 24px);
        border-radius: 14px;
    }
    .lpt-header { padding: 1rem 1.15rem; }
    .lpt-body { padding: 1rem 1.15rem; min-height: 0; }
    .lpt-actions { padding: .75rem 1.15rem; }
}
</style>
@endpush
@endonce
