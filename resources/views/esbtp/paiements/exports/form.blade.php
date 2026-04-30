@extends('layouts.app')

@section('title', 'Export détaillé des paiements - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ═══════════════════════════════════════════════
       Namespace pe-* (paiements-export premium)
       ═══════════════════════════════════════════════ */
    .pe-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px;
        padding: 2rem 2.5rem 1.5rem;
        color: #fff;
        margin-bottom: 1.25rem;
    }
    .pe-hero-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .pe-hero-left {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .pe-hero-icon {
        width: 52px; height: 52px;
        border-radius: 14px;
        background: rgba(255,255,255,.12);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; flex-shrink: 0; color: #fff;
    }
    .pe-hero h1 {
        font-size: 1.45rem; font-weight: 700;
        color: #fff; margin: 0 0 .2rem;
        letter-spacing: -.01em;
    }
    .pe-hero p {
        color: rgba(255,255,255,.7);
        font-size: .88rem; margin: 0;
    }
    .pe-hero-actions {
        display: flex; gap: .5rem; align-items: center; flex-wrap: wrap;
    }
    .pe-btn {
        display: inline-flex; align-items: center; gap: .4rem;
        padding: .5rem 1rem; border-radius: 10px;
        font-size: .82rem; font-weight: 600;
        text-decoration: none; transition: all .2s ease;
        border: 1px solid rgba(255,255,255,.2);
        cursor: pointer;
    }
    .pe-btn--glass { background: rgba(255,255,255,.15); color: #fff; }
    .pe-btn--glass:hover { background: rgba(255,255,255,.22); color: #fff; }

    /* Card */
    .pe-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
        padding: 1.5rem;
        margin-bottom: 1.25rem;
    }
    .pe-section-header {
        display: flex; align-items: center; gap: .75rem;
        margin-bottom: 1rem;
    }
    .pe-section-icon {
        width: 40px; height: 40px; border-radius: 10px;
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: .95rem;
    }
    .pe-section-title { font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0; }
    .pe-section-subtitle { font-size: .8rem; color: #64748b; margin: 0; }

    /* Form */
    .pe-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem 1.25rem;
    }
    @media (max-width: 768px) { .pe-form-grid { grid-template-columns: 1fr; } }

    .pe-field { display: flex; flex-direction: column; gap: .35rem; }
    .pe-field-label {
        font-size: .78rem; font-weight: 600; color: #1e293b;
        display: flex; align-items: center; gap: .35rem;
    }
    .pe-field-label i { color: #0453cb; font-size: .72rem; }
    .pe-field-hint { font-size: .72rem; color: #64748b; margin-top: -.1rem; }

    .pe-input,
    .pe-select {
        width: 100%;
        padding: .55rem .8rem;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        background: #fff;
        font-size: .85rem;
        color: #1e293b;
        transition: border-color .15s, box-shadow .15s;
    }
    .pe-input:focus,
    .pe-select:focus {
        outline: none;
        border-color: #0453cb;
        box-shadow: 0 0 0 3px rgba(4,83,203,.12);
    }
    .pe-select[multiple] { min-height: 110px; }

    .pe-checkbox-group {
        display: flex; flex-wrap: wrap; gap: .5rem;
    }
    .pe-checkbox {
        display: inline-flex; align-items: center; gap: .45rem;
        padding: .35rem .75rem;
        border: 1px solid #cbd5e1;
        border-radius: 99px;
        font-size: .78rem;
        background: #f8fafc;
        cursor: pointer;
        transition: all .15s ease;
    }
    .pe-checkbox:hover { border-color: #0453cb; background: #f0f4ff; }
    .pe-checkbox input { margin: 0; }
    .pe-checkbox input:checked + span { color: #0453cb; font-weight: 600; }

    .pe-format-toggle {
        display: flex; gap: .65rem;
    }
    .pe-format-option {
        flex: 1;
        position: relative;
        cursor: pointer;
    }
    .pe-format-option input { position: absolute; opacity: 0; pointer-events: none; }
    .pe-format-card {
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 1rem 1.25rem;
        display: flex; align-items: center; gap: .75rem;
        background: #fff;
        transition: all .2s ease;
    }
    .pe-format-card i { font-size: 1.5rem; color: #64748b; }
    .pe-format-card .pe-format-title { font-size: .9rem; font-weight: 700; color: #1e293b; margin: 0; }
    .pe-format-card .pe-format-hint { font-size: .72rem; color: #64748b; margin: 0; }
    .pe-format-option input:checked + .pe-format-card {
        border-color: #0453cb;
        box-shadow: 0 4px 16px rgba(4,83,203,.12);
        background: linear-gradient(135deg, rgba(4,83,203,.04), rgba(59,125,219,.04));
    }
    .pe-format-option input:checked + .pe-format-card i { color: #0453cb; }

    /* Actions row */
    .pe-actions-row {
        display: flex; justify-content: space-between; align-items: center;
        gap: .75rem; flex-wrap: wrap;
        padding-top: 1rem;
        border-top: 1px solid #e2e8f0;
        margin-top: .75rem;
    }
    .pe-preview-info {
        font-size: .82rem; color: #64748b;
        display: flex; align-items: center; gap: .5rem;
    }
    .pe-preview-info.is-success { color: #10b981; font-weight: 600; }
    .pe-preview-info.is-error { color: #dc2626; font-weight: 600; }

    .pe-action-btn {
        display: inline-flex; align-items: center; gap: .5rem;
        padding: .65rem 1.2rem;
        border-radius: 10px;
        font-size: .85rem; font-weight: 600;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all .2s ease;
    }
    .pe-action-btn--secondary {
        background: #fff; color: #1e293b; border-color: #cbd5e1;
    }
    .pe-action-btn--secondary:hover { border-color: #0453cb; color: #0453cb; }
    .pe-action-btn--primary {
        background: #0453cb; color: #fff;
    }
    .pe-action-btn--primary:hover { background: #033a8e; }
    .pe-action-btn:disabled {
        opacity: .55; cursor: not-allowed;
    }

    @media (max-width: 768px) {
        .pe-hero { padding: 1.5rem 1.25rem 1.25rem; border-radius: 14px; }
        .pe-hero-top { flex-direction: column; }
        .pe-card { padding: 1.25rem; }
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    {{-- Hero --}}
    <div class="pe-hero">
        <div class="pe-hero-top">
            <div class="pe-hero-left">
                <div class="pe-hero-icon"><i class="fas fa-file-export"></i></div>
                <div>
                    <h1>Export détaillé des paiements</h1>
                    <p>États financiers filtrables — PDF (≤ 500 lignes) ou Excel/CSV (jusqu'à 50&nbsp;000 lignes)</p>
                </div>
            </div>
            <div class="pe-hero-actions">
                <a href="{{ route('esbtp.paiements.index') }}" class="pe-btn pe-btn--glass">
                    <i class="fas fa-arrow-left"></i> Retour aux paiements
                </a>
            </div>
        </div>
    </div>

    {{-- Form Card --}}
    <form id="pe-form" method="POST" action="{{ route('esbtp.paiements.export-detaille.generate') }}" novalidate>
        @csrf

        <div class="pe-card">
            <div class="pe-section-header">
                <div class="pe-section-icon"><i class="fas fa-filter"></i></div>
                <div>
                    <h3 class="pe-section-title">Filtres</h3>
                    <p class="pe-section-subtitle">Affinez la sélection des paiements à exporter</p>
                </div>
            </div>

            <div class="pe-form-grid">
                {{-- Étudiant (matricule + nom) --}}
                <div class="pe-field">
                    <label class="pe-field-label" for="pe-etudiant">
                        <i class="fas fa-user-graduate"></i> Étudiant (matricule ou nom)
                    </label>
                    <input type="text" id="pe-etudiant-search" class="pe-input"
                        placeholder="Rechercher par matricule ou nom…" autocomplete="off">
                    <input type="hidden" name="etudiant_id" id="pe-etudiant" value="">
                    <div class="pe-field-hint" id="pe-etudiant-hint">Laisser vide pour tous les étudiants</div>
                </div>

                {{-- Format --}}
                <div class="pe-field">
                    <label class="pe-field-label">
                        <i class="fas fa-file-alt"></i> Format
                    </label>
                    <div class="pe-format-toggle">
                        <label class="pe-format-option">
                            <input type="radio" name="format" value="pdf" checked>
                            <div class="pe-format-card">
                                <i class="fas fa-file-pdf"></i>
                                <div>
                                    <p class="pe-format-title">PDF</p>
                                    <p class="pe-format-hint">≤ 500 lignes</p>
                                </div>
                            </div>
                        </label>
                        <label class="pe-format-option">
                            <input type="radio" name="format" value="excel">
                            <div class="pe-format-card">
                                <i class="fas fa-file-excel"></i>
                                <div>
                                    <p class="pe-format-title">Excel/CSV</p>
                                    <p class="pe-format-hint">≤ 50 000 lignes</p>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Filière --}}
                <div class="pe-field">
                    <label class="pe-field-label" for="pe-filiere">
                        <i class="fas fa-stream"></i> Filière
                    </label>
                    <select name="filiere_id" id="pe-filiere" class="pe-select">
                        <option value="">— Toutes les filières —</option>
                        @foreach($filieres as $f)
                            <option value="{{ $f->id }}">{{ $f->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Niveau --}}
                <div class="pe-field">
                    <label class="pe-field-label" for="pe-niveau">
                        <i class="fas fa-layer-group"></i> Niveau d'études
                    </label>
                    <select name="niveau_id" id="pe-niveau" class="pe-select">
                        <option value="">— Tous les niveaux —</option>
                        @foreach($niveaux as $n)
                            <option value="{{ $n->id }}">{{ $n->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Classes (multi) --}}
                <div class="pe-field" style="grid-column: 1 / -1;">
                    <label class="pe-field-label" for="pe-classes">
                        <i class="fas fa-chalkboard"></i> Classes (multi-sélection)
                    </label>
                    <select name="classe_ids[]" id="pe-classes" class="pe-select" multiple>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}">
                                {{ $c->name }}{{ $c->filiere ? ' — ' . $c->filiere->name : '' }}
                            </option>
                        @endforeach
                    </select>
                    <div class="pe-field-hint">Ctrl/Cmd + clic pour sélectionner plusieurs classes — vide = toutes</div>
                </div>

                {{-- Date début --}}
                <div class="pe-field">
                    <label class="pe-field-label" for="pe-date-debut">
                        <i class="fas fa-calendar-day"></i> Date début
                    </label>
                    <input type="date" name="date_debut" id="pe-date-debut" class="pe-input">
                </div>

                {{-- Date fin --}}
                <div class="pe-field">
                    <label class="pe-field-label" for="pe-date-fin">
                        <i class="fas fa-calendar-check"></i> Date fin
                    </label>
                    <input type="date" name="date_fin" id="pe-date-fin" class="pe-input">
                </div>

                {{-- Modes --}}
                <div class="pe-field" style="grid-column: 1 / -1;">
                    <label class="pe-field-label">
                        <i class="fas fa-money-check-alt"></i> Mode(s) de paiement
                    </label>
                    <div class="pe-checkbox-group">
                        @forelse($modes as $mode)
                            <label class="pe-checkbox">
                                <input type="checkbox" name="modes[]" value="{{ $mode }}">
                                <span>{{ ucfirst($mode) }}</span>
                            </label>
                        @empty
                            <span class="pe-field-hint">Aucun mode disponible</span>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="pe-actions-row">
                <div class="pe-preview-info" id="pe-preview-info">
                    <i class="fas fa-info-circle"></i>
                    <span>Cliquez sur « Vérifier » pour compter les lignes correspondant aux filtres</span>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" id="pe-btn-preview" class="pe-action-btn pe-action-btn--secondary">
                        <i class="fas fa-search"></i> Vérifier
                    </button>
                    <button type="submit" id="pe-btn-generate" class="pe-action-btn pe-action-btn--primary" disabled>
                        <i class="fas fa-download"></i> Générer le fichier
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        'use strict';

        // Inline toast helper (utilise window.showToast si déjà défini, sinon fallback)
        function toast(msg, type) {
            type = type || 'info';
            if (typeof window.showToast === 'function') {
                window.showToast(msg, type);
                return;
            }
            // Fallback BS5 toast inline
            let container = document.getElementById('pe-toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'pe-toast-container';
                container.style.cssText = 'position:fixed; top:1rem; right:1rem; z-index:10050; display:flex; flex-direction:column; gap:.5rem;';
                document.body.appendChild(container);
            }
            const colors = {
                success: '#10b981', info: '#0453cb', warning: '#f59e0b', error: '#dc2626'
            };
            const el = document.createElement('div');
            el.style.cssText = 'background:' + (colors[type] || colors.info) + '; color:#fff; padding:.85rem 1.1rem; border-radius:10px; box-shadow:0 10px 30px rgba(0,0,0,.15); max-width:380px; font-size:.88rem; font-weight:500;';
            el.textContent = msg;
            container.appendChild(el);
            setTimeout(() => el.remove(), 5000);
        }

        // Étudiant autocomplete (simple — fetch on input)
        const etudiantSearchInput = document.getElementById('pe-etudiant-search');
        const etudiantHidden = document.getElementById('pe-etudiant');
        const etudiantHint = document.getElementById('pe-etudiant-hint');
        let etudiantTimer = null;

        if (etudiantSearchInput) {
            etudiantSearchInput.addEventListener('input', function () {
                clearTimeout(etudiantTimer);
                const q = this.value.trim();
                etudiantHidden.value = '';
                if (q.length < 2) {
                    etudiantHint.textContent = 'Tapez au moins 2 caractères';
                    return;
                }
                etudiantTimer = setTimeout(() => {
                    fetch('{{ route('esbtp.api.parents.search') }}?q=' + encodeURIComponent(q) + '&context=etudiant', {
                        headers: { 'Accept': 'application/json' }
                    })
                    .then(r => r.json())
                    .then(json => {
                        // Note: cette API renvoie soit un array, soit { items: [] } selon contexte.
                        // En cas d'absence d'API étudiant dédiée, on laisse l'utilisateur taper le matricule
                        // → le serveur ignorera le filtre etudiant_id si le hidden est vide.
                        etudiantHint.textContent = q
                            ? 'Saisissez le matricule exact (filtre flou non disponible) — laisser vide ignore ce critère'
                            : 'Laisser vide pour tous les étudiants';
                    })
                    .catch(() => {
                        etudiantHint.textContent = 'Filtre étudiant non disponible — laisser vide';
                    });
                }, 350);
            });
        }

        // Cohérence dates
        const dateDebut = document.getElementById('pe-date-debut');
        const dateFin = document.getElementById('pe-date-fin');
        if (dateDebut && dateFin) {
            dateDebut.addEventListener('change', () => { dateFin.min = dateDebut.value; });
            dateFin.addEventListener('change', () => { dateDebut.max = dateFin.value; });
        }

        // Format change → reset preview
        const formatRadios = document.querySelectorAll('input[name="format"]');
        formatRadios.forEach(r => r.addEventListener('change', resetPreview));

        // Reset preview state on any filter change
        ['pe-filiere', 'pe-niveau', 'pe-classes', 'pe-date-debut', 'pe-date-fin', 'pe-etudiant-search'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('change', resetPreview);
        });
        document.querySelectorAll('input[name="modes[]"]').forEach(cb => cb.addEventListener('change', resetPreview));

        function resetPreview() {
            document.getElementById('pe-btn-generate').disabled = true;
            const info = document.getElementById('pe-preview-info');
            info.className = 'pe-preview-info';
            info.querySelector('span').textContent = 'Filtres modifiés — relancez « Vérifier »';
        }

        // Preview AJAX
        document.getElementById('pe-btn-preview').addEventListener('click', function () {
            const form = document.getElementById('pe-form');
            const fd = new FormData(form);

            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Vérification…';

            fetch('{{ route('esbtp.paiements.export-detaille.preview') }}', {
                method: 'POST',
                body: fd,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(async r => {
                const json = await r.json().catch(() => ({}));
                return { ok: r.ok, status: r.status, json: json };
            })
            .then(({ ok, status, json }) => {
                const info = document.getElementById('pe-preview-info');
                const generateBtn = document.getElementById('pe-btn-generate');

                if (ok && json.success) {
                    info.className = 'pe-preview-info is-success';
                    info.querySelector('span').textContent = json.message || (json.count + ' lignes prêtes');
                    generateBtn.disabled = false;
                    toast(json.message || 'Prévisualisation OK', 'success');
                } else {
                    info.className = 'pe-preview-info is-error';
                    info.querySelector('span').textContent = json.message || ('Erreur ' + status);
                    generateBtn.disabled = true;
                    toast(json.message || 'Erreur de prévisualisation', 'error');
                }
            })
            .catch((err) => {
                toast('Erreur réseau : ' + err.message, 'error');
                document.getElementById('pe-btn-generate').disabled = true;
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-search"></i> Vérifier';
            });
        });

        // Submit handler — re-preview before submit (safety)
        document.getElementById('pe-form').addEventListener('submit', function (e) {
            const generateBtn = document.getElementById('pe-btn-generate');
            if (generateBtn.disabled) {
                e.preventDefault();
                toast('Veuillez d\'abord cliquer sur « Vérifier »', 'warning');
                return;
            }
            // Laisser le POST classique télécharger le fichier
        });
    })();
</script>
@endpush
