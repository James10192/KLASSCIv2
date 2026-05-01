{{--
    Styles partagés entre les formulaires create (ns=ec) et edit (ns=ee).
    Hero, status toggle (edit-only), availability grid (edit-only) et textarea
    restent définis dans la vue qui les utilise.
--}}
.{{ $ns }}-form { max-width: 1100px; margin: 0 auto; }

.{{ $ns }}-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    margin-bottom: 1.25rem;
    transition: box-shadow .2s ease;
}
.{{ $ns }}-card:hover {
    box-shadow: 0 4px 16px rgba(4,83,203,.06), 0 1px 3px rgba(15,23,42,.04);
}
.{{ $ns }}-card-body { padding: 1.5rem 1.75rem; }

.{{ $ns }}-section-header {
    display: flex; align-items: center; gap: .75rem;
    margin-bottom: 1.25rem;
}
.{{ $ns }}-section-icon {
    width: 38px; height: 38px;
    border-radius: 10px;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .9rem; flex-shrink: 0;
}
.{{ $ns }}-section-title { margin: 0; font-size: 1.05rem; font-weight: 700; color: #0f172a; }
.{{ $ns }}-section-sub { margin: 0; font-size: .8rem; color: #64748b; }

.{{ $ns }}-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 1rem 1.25rem;
}
.{{ $ns }}-field { display: flex; flex-direction: column; gap: .35rem; }
.{{ $ns }}-field-wide { grid-column: 1 / -1; }

.{{ $ns }}-label {
    font-size: .8rem; font-weight: 600;
    color: #1e293b; letter-spacing: .01em;
}
.{{ $ns }}-label .req { color: #dc2626; margin-left: 2px; }

.{{ $ns }}-input,
.{{ $ns }}-select {
    width: 100%;
    padding: .6rem .8rem;
    border: 1px solid #e2e8f0;
    border-radius: 9px;
    font-size: .9rem;
    color: #0f172a;
    background: #fff;
    transition: border-color .15s, box-shadow .15s;
}
.{{ $ns }}-input:focus,
.{{ $ns }}-select:focus {
    outline: none;
    border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4,83,203,.1);
}
.{{ $ns }}-input.is-invalid,
.{{ $ns }}-select.is-invalid { border-color: #dc2626; }

.{{ $ns }}-help { font-size: .73rem; color: #64748b; line-height: 1.4; }
.{{ $ns }}-error { font-size: .76rem; color: #dc2626; font-weight: 500; }

.{{ $ns }}-regime-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: .75rem;
}
.{{ $ns }}-regime-card {
    position: relative;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 1rem 1.1rem;
    cursor: pointer;
    transition: all .15s ease;
    background: #fff;
}
.{{ $ns }}-regime-card:hover { border-color: #94a3b8; transform: translateY(-1px); }
.{{ $ns }}-regime-card.active {
    border-color: #0453cb;
    background: rgba(4,83,203,.04);
    box-shadow: 0 0 0 3px rgba(4,83,203,.08);
}
.{{ $ns }}-regime-card input[type="radio"] {
    position: absolute; opacity: 0; pointer-events: none;
}
.{{ $ns }}-regime-icon {
    width: 32px; height: 32px;
    border-radius: 8px;
    background: #eef2f7;
    color: #475569;
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem;
    margin-bottom: .55rem;
    transition: all .15s ease;
}
.{{ $ns }}-regime-card.active .{{ $ns }}-regime-icon {
    background: #0453cb; color: #fff;
}
.{{ $ns }}-regime-name { font-weight: 700; font-size: .92rem; color: #0f172a; margin: 0 0 .15rem; }
.{{ $ns }}-regime-desc { font-size: .73rem; color: #64748b; margin: 0; line-height: 1.4; }

.{{ $ns }}-collapse-toggle {
    width: 100%;
    background: transparent; border: none;
    display: flex; align-items: center; gap: .75rem;
    padding: 0; cursor: pointer; text-align: left;
}
.{{ $ns }}-collapse-toggle:hover .{{ $ns }}-section-title { color: #0453cb; }
.{{ $ns }}-toggle-chevron {
    margin-left: auto;
    color: #94a3b8;
    transition: transform .25s ease;
}
.{{ $ns }}-card[data-collapsed="false"] .{{ $ns }}-toggle-chevron { transform: rotate(180deg); }

.{{ $ns }}-collapse-body {
    overflow: hidden;
    max-height: 0;
    transition: max-height .3s ease, margin-top .3s ease;
    margin-top: 0;
}
.{{ $ns }}-card[data-collapsed="false"] .{{ $ns }}-collapse-body {
    max-height: 1500px;
    margin-top: 1.25rem;
}

.{{ $ns }}-conditional { display: none; }
.{{ $ns }}-conditional.show { display: flex; }

.{{ $ns }}-actions {
    display: flex;
    justify-content: flex-end;
    gap: .6rem;
    padding: 1.25rem 0;
}

.{{ $ns }}-alert {
    border-radius: 10px;
    padding: .85rem 1rem;
    margin-bottom: 1rem;
    display: flex; align-items: flex-start; gap: .65rem;
    font-size: .87rem; line-height: 1.5;
    border: 1px solid transparent;
}
.{{ $ns }}-alert-warning {
    background: rgba(245,158,11,.08);
    border-color: rgba(245,158,11,.25);
    color: #78350f;
}
.{{ $ns }}-alert-info {
    background: rgba(4,83,203,.06);
    border-color: rgba(4,83,203,.18);
    color: #1e293b;
}
.{{ $ns }}-alert-success {
    background: rgba(16,185,129,.08);
    border-color: rgba(16,185,129,.25);
    color: #065f46;
}
.{{ $ns }}-alert-icon { margin-top: 2px; flex-shrink: 0; }

@media (max-width: 768px) {
    .{{ $ns }}-card-body { padding: 1.1rem; }
    .{{ $ns }}-grid { grid-template-columns: 1fr; }
    .{{ $ns }}-regime-grid { grid-template-columns: 1fr; }
    .{{ $ns }}-actions { flex-direction: column-reverse; }
    .{{ $ns }}-actions .btn-acasi { width: 100%; justify-content: center; }
}
