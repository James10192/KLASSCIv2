{{-- Styles pour l'edition en masse des planifications LMD.
     Namespace : lpb-* (LMD Planning Bulk). --}}
@once
@push('styles')
<style>
/* ============ Selection checkboxes ============ */
.lpb-check-cell {
    width: 36px;
    text-align: center;
    padding: 0 .35rem;
}
.lpb-check {
    width: 16px;
    height: 16px;
    accent-color: #0453cb;
    cursor: pointer;
    margin: 0;
}
.js-ecue-row.lpb-row-selected td {
    background-color: rgba(4, 83, 203, .06);
}

/* ============ Action bar sticky ============ */
.lpb-bar {
    position: fixed;
    left: 50%;
    bottom: 1.25rem;
    transform: translate(-50%, calc(100% + 2rem));
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 60%, #3b7ddb 100%);
    color: #fff;
    border-radius: 16px;
    padding: .9rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    box-shadow: 0 -4px 24px rgba(4, 83, 203, .25), 0 12px 40px rgba(15, 23, 42, .25);
    z-index: 1050;
    transition: transform .25s cubic-bezier(.4, 0, .2, 1), opacity .2s ease;
    opacity: 0;
    pointer-events: none;
    max-width: calc(100vw - 2rem);
}
.lpb-bar--visible {
    transform: translate(-50%, 0);
    opacity: 1;
    pointer-events: auto;
}
.lpb-bar-count {
    font-weight: 700;
    font-size: .95rem;
    display: flex;
    align-items: center;
    gap: .55rem;
}
.lpb-bar-count i {
    font-size: 1.1rem;
    opacity: .9;
}
.lpb-bar-actions {
    display: flex;
    gap: .65rem;
}
.lpb-bar-btn {
    border: none;
    border-radius: 10px;
    padding: .55rem 1.1rem;
    font-size: .85rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    transition: background-color .15s ease, transform .1s ease;
    font-family: inherit;
}
.lpb-bar-btn--ghost {
    background: rgba(255, 255, 255, .15);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, .2);
}
.lpb-bar-btn--ghost:hover {
    background: rgba(255, 255, 255, .25);
}
.lpb-bar-btn--white {
    background: #fff;
    color: #0453cb;
}
.lpb-bar-btn--white:hover {
    background: #f1f5f9;
    transform: translateY(-1px);
}

@@media (max-width: 768px) {
    .lpb-bar {
        flex-direction: column;
        align-items: stretch;
        gap: .75rem;
        bottom: .75rem;
        left: .75rem;
        right: .75rem;
        max-width: none;
        transform: translateY(calc(100% + 2rem));
    }
    .lpb-bar--visible { transform: translateY(0); }
    .lpb-bar-count { justify-content: center; }
    .lpb-bar-actions { justify-content: stretch; }
    .lpb-bar-btn { flex: 1; justify-content: center; }
}

/* ============ Modal bulk edit ============ */
.lpb-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, .55);
    backdrop-filter: blur(4px);
    z-index: 1080;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    opacity: 0;
    pointer-events: none;
    transition: opacity .18s ease;
}
.lpb-backdrop--open {
    opacity: 1;
    pointer-events: auto;
}
.lpb-modal {
    background: #fff;
    border-radius: 18px;
    width: 100%;
    max-width: 640px;
    max-height: 92vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 24px 60px rgba(15, 23, 42, .35);
    transform: scale(.96);
    transition: transform .2s cubic-bezier(.4, 0, .2, 1);
}
.lpb-backdrop--open .lpb-modal { transform: scale(1); }

.lpb-header {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 60%, #3b7ddb 100%);
    color: #fff;
    padding: 1.5rem 1.75rem 1.25rem;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
}
.lpb-header h3 {
    margin: 0 0 .25rem;
    font-size: 1.2rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: .6rem;
}
.lpb-header-meta {
    font-size: .82rem;
    opacity: .8;
    font-weight: 500;
}
.lpb-close {
    background: rgba(255, 255, 255, .12);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, .15);
    border-radius: 10px;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color .15s ease;
    flex-shrink: 0;
}
.lpb-close:hover { background: rgba(255, 255, 255, .22); }

.lpb-body {
    padding: 1.5rem 1.75rem;
    overflow-y: auto;
    flex: 1;
}

.lpb-targets {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: .85rem 1rem;
    margin-bottom: 1.25rem;
    font-size: .82rem;
}
.lpb-targets-label {
    font-weight: 600;
    color: #0453cb;
    margin-bottom: .35rem;
    display: flex;
    align-items: center;
    gap: .4rem;
}
.lpb-targets-list {
    color: #475569;
    max-height: 88px;
    overflow-y: auto;
    line-height: 1.55;
    font-variant-numeric: tabular-nums;
}
.lpb-target-chip {
    display: inline-block;
    background: #fff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 1px 7px;
    margin: 1px 3px 1px 0;
    font-size: .72rem;
    color: #1e293b;
    font-weight: 500;
}

.lpb-fields-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .75rem;
}
.lpb-field {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: .65rem .85rem;
    background: #fff;
    transition: border-color .15s ease, background-color .15s ease;
}
.lpb-field--enabled {
    border-color: #0453cb;
    background: rgba(4, 83, 203, .03);
}
.lpb-field-toggle {
    display: flex;
    align-items: center;
    gap: .5rem;
    font-size: .78rem;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    margin-bottom: .35rem;
    user-select: none;
}
.lpb-field-toggle input[type="checkbox"] {
    width: 14px;
    height: 14px;
    accent-color: #0453cb;
    cursor: pointer;
}
.lpb-field--enabled .lpb-field-toggle { color: #0453cb; }
.lpb-field-input {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: .42rem .65rem;
    font-size: .88rem;
    font-family: inherit;
    color: #1e293b;
    background: #fff;
}
.lpb-field-input:disabled {
    background: #f1f5f9;
    color: #94a3b8;
    cursor: not-allowed;
}
.lpb-field-input:focus {
    outline: none;
    border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4, 83, 203, .15);
}
.lpb-field--full { grid-column: 1 / -1; }

.lpb-warn {
    margin-top: 1rem;
    padding: .65rem .85rem;
    background: rgba(245, 158, 11, .08);
    border: 1px solid rgba(245, 158, 11, .25);
    border-radius: 10px;
    color: #92400e;
    font-size: .8rem;
    display: flex;
    align-items: flex-start;
    gap: .5rem;
}

.lpb-actions {
    padding: 1rem 1.75rem 1.5rem;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: .65rem;
    background: #fff;
}
.lpb-btn {
    border: none;
    border-radius: 10px;
    padding: .65rem 1.25rem;
    font-size: .88rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: .55rem;
    transition: background-color .15s ease, transform .1s ease;
    font-family: inherit;
}
.lpb-btn-secondary {
    background: #f1f5f9;
    color: #475569;
}
.lpb-btn-secondary:hover { background: #e2e8f0; }
.lpb-btn-primary {
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff;
}
.lpb-btn-primary:hover { transform: translateY(-1px); }
.lpb-btn-primary:disabled {
    opacity: .55;
    cursor: not-allowed;
    transform: none;
}

@@media (max-width: 600px) {
    .lpb-fields-grid { grid-template-columns: 1fr; }
    .lpb-modal { max-width: 100%; }
}
</style>
@endpush
@endonce
