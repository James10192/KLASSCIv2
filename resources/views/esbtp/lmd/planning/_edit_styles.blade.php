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
}
.lpe-teacher-btn:hover {
    border-color: #0453cb;
    color: #0453cb;
    background: rgba(4, 83, 203, .06);
}
.lpe-teacher-btn--assigned {
    border: 1px solid rgba(245, 158, 11, .3);
    background: rgba(245, 158, 11, .08);
    color: #b45309;
    border-style: solid;
}
.lpe-teacher-btn--assigned:hover {
    border-color: #f59e0b;
    background: rgba(245, 158, 11, .12);
    color: #92400e;
}
.lpe-teacher-btn i {
    font-size: .68rem;
}

.lpe-readonly-hint {
    font-size: .7rem;
    color: #94a3b8;
    font-style: italic;
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
    min-height: min(480px, 85vh);
    max-height: calc(100vh - 4rem);
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
    background: linear-gradient(135deg, #0a3d8f, #0453cb 60%, #3b7ddb);
    color: #fff;
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
    font-size: .72rem;
    color: rgba(255, 255, 255, .8);
    margin-top: .2rem;
}
.lpt-close {
    background: rgba(255, 255, 255, .15);
    border: none;
    color: #fff;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.lpt-close:hover { background: rgba(255, 255, 255, .25); }
.lpt-body {
    padding: 1.75rem 2rem;
    overflow-y: auto;
    flex: 1 1 auto;
    min-height: 280px;
}
.lpt-body .au-up {
    width: 100%;
    margin-bottom: 1rem;
}
.lpt-body .au-up-trigger { width: 100%; }
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

@@media (max-width: 640px) {
    .lpt-modal {
        min-height: 0;
        height: auto;
        max-height: 90vh;
        border-radius: 14px;
    }
    .lpt-header { padding: 1rem 1.15rem; }
    .lpt-body { padding: 1.25rem 1.15rem; min-height: 220px; }
    .lpt-actions { padding: .75rem 1.15rem; }
}
</style>
@endpush
@endonce
