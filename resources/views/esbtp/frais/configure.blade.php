@extends('layouts.app')

@section('title', 'Configuration des Frais - KLASSCI')

@push('styles')
<style>
/* ───────────────────────────────────────────────
   FRAIS CONFIGURE — KLASSCI Premium v2
   Namespace: fc-*
──────────────────────────────────────────────── */
:root {
    --fc-primary:    #0453cb;
    --fc-primary-d:  #033a8e;
    --fc-secondary:  #5e91de;
    --fc-dark:       #0f172a;
    --fc-text:       #1e293b;
    --fc-muted:      #64748b;
    --fc-success:    #10b981;
    --fc-warning:    #f59e0b;
    --fc-danger:     #ef4444;
    --fc-surface:    #f8fafc;
    --fc-white:      #ffffff;
    --fc-border:     #e2e8f0;
    --fc-shadow-sm:  0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    --fc-shadow-md:  0 4px 16px rgba(4,83,203,.06), 0 1px 3px rgba(15,23,42,.04);
    --fc-shadow-lg:  0 8px 30px rgba(4,83,203,.08), 0 2px 8px rgba(15,23,42,.04);
    --fc-radius:     14px;
}

/* ── HERO ── */
.fc-hero {
    background: linear-gradient(135deg, #071631 0%, #0a2d6e 35%, #0453cb 70%, #3674d1 100%);
    position: relative;
    overflow: hidden;
    padding: 2rem 2rem 1.75rem;
    border-radius: 18px;
    margin-bottom: 1.75rem;
    box-shadow: 0 8px 32px rgba(4,83,203,.18), 0 2px 8px rgba(15,23,42,.1), inset 0 1px 0 rgba(255,255,255,.08);
}
.fc-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse 50% 70% at 90% 30%, rgba(94,145,222,.15) 0%, transparent 70%);
    pointer-events: none;
}
.fc-hero-inner { position: relative; z-index: 1; display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
.fc-hero-label {
    display: inline-flex; align-items: center; gap: .35rem;
    background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12);
    border-radius: 6px; padding: .2rem .6rem;
    font-size: .65rem; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: rgba(255,255,255,.55);
    margin-bottom: .5rem;
}
.fc-hero-title { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; letter-spacing: -.3px; }
.fc-hero-sub { color: rgba(255,255,255,.5); font-size: .82rem; margin: .3rem 0 0; }
.fc-hero-actions { display: flex; gap: .6rem; align-items: center; flex-wrap: wrap; }
.fc-btn-ghost {
    background: rgba(255,255,255,.07); color: rgba(255,255,255,.85); border: 1px solid rgba(255,255,255,.15);
    padding: .5rem 1.1rem; border-radius: 9px; font-weight: 500; font-size: .8rem; text-decoration: none;
    transition: all .2s ease; display: inline-flex; align-items: center; gap: .4rem;
}
.fc-btn-ghost:hover { background: rgba(255,255,255,.14); color: #fff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,.15); }

/* ── INFO BAR ── */
.fc-info-bar {
    background: var(--fc-white);
    border: 1px solid var(--fc-border);
    border-radius: var(--fc-radius);
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: var(--fc-shadow-sm);
}
.fc-info-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: rgba(4,83,203,.06); color: var(--fc-primary);
    display: flex; align-items: center; justify-content: center;
    font-size: .95rem; flex-shrink: 0;
}
.fc-info-text { font-size: .82rem; color: var(--fc-muted); line-height: 1.5; }
.fc-info-text strong { color: var(--fc-text); }

/* ── ALERTS ── */
.fc-alert {
    border-radius: var(--fc-radius);
    padding: .85rem 1.1rem;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: center;
    gap: .6rem;
    font-size: .85rem;
    font-weight: 500;
}
.fc-alert.is-success { background: rgba(16,185,129,.06); border: 1px solid rgba(16,185,129,.2); color: #065f46; }
.fc-alert.is-error   { background: rgba(239,68,68,.06); border: 1px solid rgba(239,68,68,.2); color: #991b1b; }

/* ── CLASSES GRID ── */
.fc-section-title {
    font-size: .9rem; font-weight: 700; color: var(--fc-text); margin-bottom: 1.25rem;
    display: flex; align-items: center; gap: .5rem;
}
.fc-section-title i {
    width: 32px; height: 32px; border-radius: 9px;
    background: rgba(4,83,203,.07); color: var(--fc-primary);
    display: flex; align-items: center; justify-content: center;
    font-size: .8rem;
}
.fc-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 1rem;
}

/* ── CLASS CARD ── */
.fc-card {
    background: var(--fc-white);
    border: 1.5px solid var(--fc-border);
    border-radius: var(--fc-radius);
    padding: 1.25rem;
    transition: all .25s ease;
    box-shadow: var(--fc-shadow-sm);
    position: relative;
    overflow: hidden;
}
.fc-card:hover {
    box-shadow: var(--fc-shadow-lg);
    transform: translateY(-3px);
}
.fc-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    border-radius: 14px 14px 0 0;
    transition: opacity .2s;
}
.fc-card.is-complete::before  { background: var(--fc-success); }
.fc-card.is-partial::before   { background: var(--fc-warning); }
.fc-card.is-empty::before     { background: var(--fc-danger); }

.fc-card-header { display: flex; align-items: center; gap: .75rem; margin-bottom: 1rem; }
.fc-card-icon {
    width: 42px; height: 42px; border-radius: 11px;
    background: linear-gradient(135deg, var(--fc-primary), var(--fc-secondary));
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .95rem; flex-shrink: 0;
    box-shadow: 0 3px 8px rgba(4,83,203,.2);
}
.fc-card-name { font-weight: 700; color: var(--fc-text); font-size: .9rem; line-height: 1.2; }
.fc-card-meta { font-size: .72rem; color: var(--fc-muted); margin-top: 2px; }
.fc-card-students { font-size: .7rem; color: var(--fc-muted); margin-top: 2px; }

/* Stats row */
.fc-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .6rem;
    margin-bottom: 1rem;
}
.fc-stat {
    text-align: center;
    padding: .6rem .5rem;
    border-radius: 9px;
    background: var(--fc-surface);
}
.fc-stat-value { font-size: 1.1rem; font-weight: 800; line-height: 1; }
.fc-stat-label { font-size: .65rem; font-weight: 600; margin-top: .2rem; text-transform: uppercase; letter-spacing: .04em; }
.fc-stat.is-oblig .fc-stat-value { color: var(--fc-primary); }
.fc-stat.is-oblig .fc-stat-label { color: var(--fc-primary); }
.fc-stat.is-opt .fc-stat-value   { color: var(--fc-secondary); }
.fc-stat.is-opt .fc-stat-label   { color: var(--fc-secondary); }

/* Progress ring */
.fc-ring { text-align: center; margin-bottom: 1rem; }
.fc-ring svg { filter: drop-shadow(0 1px 3px rgba(0,0,0,.08)); }
.fc-ring circle { transition: stroke-dashoffset .6s ease; transform: rotate(-90deg); transform-origin: 50% 50%; }
.fc-ring-pct {
    position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%);
    font-size: .7rem; font-weight: 800; color: var(--fc-text);
}

/* Status badge */
.fc-badge {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .25rem .65rem; border-radius: 8px;
    font-size: .68rem; font-weight: 600;
    margin-bottom: 1rem;
}
.fc-badge.is-complete { background: rgba(16,185,129,.08); color: #059669; }
.fc-badge.is-partial  { background: rgba(245,158,11,.08); color: #b45309; }
.fc-badge.is-empty    { background: rgba(239,68,68,.08); color: #dc2626; }

/* Configure button */
.fc-btn-config {
    width: 100%;
    background: var(--fc-primary);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: .65rem 1rem;
    font-size: .82rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .4rem;
    transition: all .2s;
    box-shadow: 0 2px 6px rgba(4,83,203,.18);
}
.fc-btn-config:hover {
    background: var(--fc-primary-d);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(4,83,203,.25);
}

/* ── MODAL ── */
#configurationModal.modal.show {
    display: flex !important; align-items: center !important; justify-content: center !important;
    position: fixed !important; inset: 0 !important; z-index: 1055 !important; padding: 1rem !important;
}
#configurationModal.modal.show .modal-dialog {
    position: static !important; margin: 0 !important;
    max-width: 90vw !important; width: 900px !important; max-height: 90vh !important;
    transform: none !important; display: flex !important; flex-direction: column !important;
}
#configurationModal .modal-content {
    background: var(--fc-white) !important;
    border-radius: 18px !important;
    box-shadow: 0 20px 60px rgba(15,23,42,.15), 0 4px 16px rgba(4,83,203,.1) !important;
    max-height: 100% !important; overflow: hidden !important;
    display: flex !important; flex-direction: column !important;
    border: none !important;
}
#configurationModal .modal-header {
    background: linear-gradient(135deg, #071631 0%, #0453cb 100%) !important;
    color: #fff !important;
    padding: 1.25rem 1.5rem !important;
    border: none !important;
    border-radius: 18px 18px 0 0 !important;
}
#configurationModal .modal-header .modal-title { font-size: 1rem; font-weight: 600; }
#configurationModal .modal-body { overflow-y: auto !important; flex-grow: 1 !important; padding: 1.5rem !important; }
#configurationModal .modal-footer {
    border-top: 1px solid var(--fc-border) !important;
    padding: 1rem 1.5rem !important;
    gap: .5rem;
}
#configurationModal .modal-footer .btn-secondary {
    background: var(--fc-surface); color: var(--fc-muted); border: 1.5px solid var(--fc-border);
    border-radius: 9px; font-weight: 500; font-size: .82rem; padding: .5rem 1rem;
}
#configurationModal .modal-footer .btn-primary {
    background: var(--fc-primary); border: none; border-radius: 9px;
    font-weight: 600; font-size: .82rem; padding: .5rem 1.25rem;
    box-shadow: 0 2px 6px rgba(4,83,203,.2);
}
#configurationModal .modal-footer .btn-primary:hover { background: var(--fc-primary-d); }

/* Modal info bar */
.fc-modal-info {
    background: rgba(4,83,203,.04);
    border: 1px solid rgba(4,83,203,.12);
    border-radius: 10px;
    padding: .75rem 1rem;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: center;
    gap: .6rem;
    font-size: .82rem;
    color: var(--fc-text);
}
.fc-modal-info i { color: var(--fc-primary); font-size: .9rem; }

/* ── EMPTY STATE ── */
.fc-empty {
    text-align: center; padding: 3rem 2rem; color: var(--fc-muted);
}
.fc-empty i { font-size: 2.5rem; opacity: .15; margin-bottom: 1rem; display: block; }
.fc-empty p { font-size: .85rem; }

/* ── RESPONSIVE ── */
@media (max-width: 768px) {
    .fc-grid { grid-template-columns: 1fr; }
    .fc-hero { padding: 1.5rem; border-radius: 14px; }
    .fc-hero-title { font-size: 1.2rem; }
}
@media (max-width: 576px) {
    #configurationModal.modal.show { padding: .5rem !important; }
    #configurationModal.modal.show .modal-dialog { max-width: 95vw !important; width: 95vw !important; }
}
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        {{-- ── HERO ── --}}
        <div class="fc-hero">
            <div class="fc-hero-inner">
                <div>
                    <div class="fc-hero-label">
                        <i class="fas fa-coins" style="font-size:.6rem;"></i>
                        Gestion des frais
                    </div>
                    <h1 class="fc-hero-title">
                        <i class="fas fa-sliders-h" style="margin-right:.4rem;opacity:.75;font-size:.85em;"></i>
                        Configuration des Frais par Classe
                    </h1>
                    <p class="fc-hero-sub">Configurez les tarifs obligatoires pour chaque combinaison filière et niveau</p>
                </div>
                <div class="fc-hero-actions">
                    <a href="{{ route('esbtp.frais.optional-config') }}" class="fc-btn-ghost">
                        <i class="fas fa-puzzle-piece"></i> Frais Optionnels
                    </a>
                    <a href="{{ route('esbtp.frais.index') }}" class="fc-btn-ghost">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>

        {{-- ── ALERTS ── --}}
        @if(session('success'))
            <div class="fc-alert is-success">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="fc-alert is-error">
                <i class="fas fa-exclamation-triangle"></i>
                {{ session('error') }}
            </div>
        @endif

        {{-- ── INFO BAR ── --}}
        <div class="fc-info-bar">
            <div class="fc-info-icon"><i class="fas fa-info"></i></div>
            <div class="fc-info-text">
                <strong>Classe = Filière + Niveau.</strong>
                Cliquez sur une carte pour configurer les montants des frais obligatoires.
                Les frais <span style="color:var(--fc-danger);font-weight:600;">obligatoires</span> doivent tous être configurés pour chaque classe.
            </div>
        </div>

        {{-- ── CLASSES GRID ── --}}
        <div class="fc-section-title">
            <i class="fas fa-graduation-cap"></i>
            Classes ({{ $classes->count() }})
        </div>

        @if($classes->count() > 0)
            <div class="fc-grid">
                @foreach($classes as $classe)
                    @php
                        $totalRequired = $classe->total_obligatoires;
                        $totalConfigured = $classe->obligatoires_configures;
                        $percentage = $totalRequired > 0 ? ($totalConfigured / $totalRequired) * 100 : 0;
                        $circumference = 2 * 3.14159 * 22;
                        $strokeDashoffset = $circumference - ($percentage / 100) * $circumference;

                        if ($totalConfigured == $totalRequired) {
                            $statusClass = 'is-complete';
                            $statusIcon = 'fa-check-circle';
                            $statusText = 'Complet';
                            $ringColor = '#10b981';
                        } elseif ($totalConfigured > 0) {
                            $statusClass = 'is-partial';
                            $statusIcon = 'fa-exclamation-triangle';
                            $statusText = 'Partiel';
                            $ringColor = '#f59e0b';
                        } else {
                            $statusClass = 'is-empty';
                            $statusIcon = 'fa-times-circle';
                            $statusText = 'Non configuré';
                            $ringColor = '#ef4444';
                        }
                    @endphp

                    <div class="fc-card {{ $statusClass }}">
                        <div class="fc-card-header">
                            <div class="fc-card-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div>
                                <div class="fc-card-name">{{ $classe->name }}</div>
                                <div class="fc-card-meta">{{ $classe->filiere->name }} · {{ $classe->niveau->name }}</div>
                                <div class="fc-card-students"><i class="fas fa-users" style="margin-right:.25rem;"></i>{{ $classe->effectif }} étudiants</div>
                            </div>
                        </div>

                        <div class="fc-stats">
                            <div class="fc-stat is-oblig">
                                <div class="fc-stat-value">{{ $totalConfigured }}/{{ $totalRequired }}</div>
                                <div class="fc-stat-label">Obligatoires</div>
                            </div>
                            <div class="fc-stat is-opt">
                                @if($classe->optionnels_configures > 0)
                                    <div class="fc-stat-value">{{ $classe->optionnels_configures }}</div>
                                    <div class="fc-stat-label">Optionnels</div>
                                @else
                                    <div class="fc-stat-value" style="color:var(--fc-muted);">—</div>
                                    <div class="fc-stat-label" style="color:var(--fc-muted);">Optionnels</div>
                                @endif
                            </div>
                        </div>

                        <div class="fc-ring" style="position:relative;display:inline-block;width:100%;">
                            <div style="display:flex;align-items:center;justify-content:center;">
                                <div style="position:relative;width:52px;height:52px;">
                                    <svg width="52" height="52">
                                        <circle cx="26" cy="26" r="22" stroke="#e9eef5" stroke-width="4" fill="transparent"/>
                                        <circle cx="26" cy="26" r="22"
                                                stroke="{{ $ringColor }}"
                                                stroke-width="4"
                                                fill="transparent"
                                                stroke-linecap="round"
                                                stroke-dasharray="{{ $circumference }}"
                                                stroke-dashoffset="{{ $strokeDashoffset }}"/>
                                    </svg>
                                    <div class="fc-ring-pct">{{ number_format($percentage, 0) }}%</div>
                                </div>
                            </div>
                        </div>

                        <div style="text-align:center;">
                            <span class="fc-badge {{ $statusClass }}">
                                <i class="fas {{ $statusIcon }}" style="font-size:.65em;"></i>
                                {{ $statusText }}
                            </span>
                        </div>

                        <button type="button"
                                class="fc-btn-config configure-btn"
                                data-filiere-id="{{ $classe->filiere->id }}"
                                data-niveau-id="{{ $classe->niveau->id }}"
                                data-filiere-name="{{ $classe->filiere->name }}"
                                data-niveau-name="{{ $classe->niveau->name }}"
                                onclick="openConfigurationModal(this)">
                            <i class="fas fa-cogs"></i>
                            <span class="configure-text">Configurer les Frais</span>
                        </button>
                    </div>
                @endforeach
            </div>
        @else
            <div class="fc-empty">
                <i class="fas fa-graduation-cap"></i>
                <p>Aucune classe trouvée. Vérifiez que vous avez des filières et niveaux actifs.</p>
            </div>
        @endif

        {{-- ── MODAL ── --}}
        <div class="modal fade" id="configurationModal" tabindex="-1" aria-labelledby="configurationModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="configurationModalLabel">
                            <i class="fas fa-cogs" style="margin-right:.4rem;opacity:.8;"></i>
                            Configuration des Frais
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>

                    <div class="modal-body">
                        <div class="fc-modal-info">
                            <i class="fas fa-graduation-cap"></i>
                            <div>
                                <strong>Classe :</strong> <span id="modalClasseInfo">-</span><br>
                                <small style="color:var(--fc-muted);">Configurez les montants des frais obligatoires pour cette combinaison filière/niveau.</small>
                            </div>
                        </div>

                        <form id="configurationForm" method="POST" action="{{ route('esbtp.frais.update-configuration') }}">
                            @csrf
                            <input type="hidden" id="modalFiliereId" name="filiere_id">
                            <input type="hidden" id="modalNiveauId" name="niveau_id">

                            <div id="categoriesContainer">
                                <div style="text-align:center;padding:2rem;">
                                    <i class="fas fa-spinner fa-spin" style="font-size:1.5rem;color:var(--fc-primary);"></i>
                                    <p style="margin-top:.75rem;color:var(--fc-muted);font-size:.85rem;">Chargement des catégories...</p>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times" style="margin-right:.3rem;"></i>Annuler
                        </button>
                        <button type="button" id="saveConfigurationBtn" class="btn btn-primary">
                            <i class="fas fa-save" style="margin-right:.3rem;"></i>Enregistrer
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    debugLog('DOM ready - Simple modal system');

    window.openConfigurationModal = function(button) {
        debugLog('Opening configuration modal...');

        const filiereId = button.dataset.filiereId;
        const niveauId = button.dataset.niveauId;
        const filiereName = button.dataset.filiereName;
        const niveauName = button.dataset.niveauName;

        if (typeof bootstrap === 'undefined') {
            alert('Bootstrap non disponible');
            return;
        }

        document.getElementById('modalFiliereId').value = filiereId;
        document.getElementById('modalNiveauId').value = niveauId;
        document.getElementById('modalClasseInfo').textContent = `${filiereName} - ${niveauName}`;

        const modalElement = document.getElementById('configurationModal');
        const modal = new bootstrap.Modal(modalElement);
        modal.show();

        modalElement.addEventListener('shown.bs.modal', function() {
            loadCategories(filiereId, niveauId);
        }, { once: true });
    };

    function loadCategories(filiereId, niveauId) {
        const container = document.getElementById('categoriesContainer');
        const url = `{{ route('esbtp.frais.get-categories') }}?filiere_id=${filiereId}&niveau_id=${niveauId}&type=mandatory`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    container.innerHTML = data.html;
                } else {
                    container.innerHTML = '<div class="alert alert-warning">Aucune catégorie trouvée</div>';
                }
            })
            .catch(error => {
                debugError('Erreur:', error);
                container.innerHTML = '<div class="alert alert-danger">Erreur de chargement</div>';
            });
    }

    window.copyToAll = function(categoryId, sourceField) {
        const sourceInput = document.getElementById(`${sourceField}_${categoryId}`);
        if (!sourceInput || !sourceInput.value) {
            alert('Veuillez d\'abord saisir le montant source');
            return;
        }

        const value = sourceInput.value;
        const fields = ['amount_affecte', 'amount_reaffecte', 'amount_non_affecte'];

        fields.forEach(field => {
            const input = document.getElementById(`${field}_${categoryId}`);
            if (input) {
                input.value = value;
                input.style.backgroundColor = '#e6fffa';
                setTimeout(() => { input.style.backgroundColor = ''; }, 500);
            }
        });

        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copié!';
        btn.classList.add('btn-success');
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.classList.remove('btn-success');
        }, 1500);
    };

    document.getElementById('saveConfigurationBtn').addEventListener('click', function() {
        const form = document.getElementById('configurationForm');
        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('configurationModal')).hide();
                location.reload();
            } else {
                alert('Erreur: ' + (data.message || 'Impossible d\'enregistrer'));
            }
        })
        .catch(error => {
            debugError('Erreur:', error);
            alert('Erreur de connexion');
        });
    });
});
</script>
@endpush
