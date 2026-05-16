@extends('layouts.app')

@section('title', 'Traitement des justifications')

@push('styles')
<style>
/* =========================================================
   ADMIN PROCESSING — Premium (namespace jap-*)
   Liste des justifications PENDING + actions validate/reject
   ========================================================= */
[x-cloak] { display: none !important; }

.jap-page { padding: 0 0 2rem; }

.jap-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.75rem;
    color: #fff;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 30px rgba(4,83,203,.18);
}
.jap-hero-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    flex-wrap: wrap; gap: 1rem;
}
.jap-hero-left { display: flex; align-items: center; gap: 1rem; }
.jap-hero-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; color: #fff; flex-shrink: 0;
}
.jap-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
.jap-hero p { color: rgba(255,255,255,.72); font-size: .88rem; margin: 0; }

.jap-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
.jap-kpi {
    flex: 1; min-width: 140px;
    background: rgba(255,255,255,.10);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px;
    padding: .9rem 1rem;
    display: flex; align-items: center; gap: .75rem;
}
.jap-kpi-icon {
    width: 36px; height: 36px; border-radius: 10px;
    background: rgba(255,255,255,.12);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .9rem;
}
.jap-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; line-height: 1; }
.jap-kpi-label { font-size: .72rem; color: rgba(255,255,255,.7); margin-top: .15rem; }

/* Filters bar */
.jap-filters {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1rem;
    box-shadow: 0 1px 3px rgba(15,23,42,.04);
    margin-bottom: 1rem;
}
.jap-filters-row {
    display: flex; gap: .5rem; flex-wrap: wrap; align-items: center;
}
.jap-filters-row > * { flex-shrink: 0; }
.jap-filter-field {
    display: flex; align-items: center; gap: .4rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 0 .75rem;
    min-width: 180px; flex: 1 1 180px;
}
.jap-filter-field label { color: #94a3b8; font-size: .9rem; margin: 0; }
.jap-filter-field input {
    flex: 1; border: none; padding: .55rem 0;
    font-size: .85rem; color: #1e293b; background: transparent;
}
.jap-filter-field input:focus { outline: none; }

.jap-tabs { display: flex; gap: .35rem; flex-wrap: wrap; margin-bottom: 1rem; }
.jap-tab {
    padding: .55rem 1rem;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #475569;
    font-size: .82rem; font-weight: 600;
    cursor: pointer; text-decoration: none;
    display: inline-flex; align-items: center; gap: .4rem;
    transition: all .15s;
}
.jap-tab:hover { color: #0453cb; border-color: #0453cb; }
.jap-tab--active {
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff; border-color: transparent;
    box-shadow: 0 2px 8px rgba(4,83,203,.25);
}
.jap-tab--active:hover { color: #fff; }

/* Card par étudiant + absence */
.jap-list { display: flex; flex-direction: column; gap: .75rem; }
.jap-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.1rem 1.25rem;
    box-shadow: 0 1px 3px rgba(15,23,42,.04);
}
.jap-card-top {
    display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-start;
}
.jap-avatar {
    width: 44px; height: 44px; border-radius: 50%;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 1rem;
    flex-shrink: 0;
}
.jap-card-info { flex: 1; min-width: 0; }
.jap-card-name { font-size: .95rem; font-weight: 700; color: #1e293b; margin: 0; }
.jap-card-meta {
    display: flex; gap: 1rem; flex-wrap: wrap;
    font-size: .78rem; color: #64748b; margin-top: .25rem;
}
.jap-card-meta i { color: #94a3b8; }
.jap-card-actions { display: flex; gap: .5rem; flex-wrap: wrap; }

.jap-justification-block {
    margin-top: .75rem;
    padding: .75rem .9rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-size: .85rem;
    color: #334155;
    white-space: pre-wrap;
}
.jap-justification-block strong { color: #0453cb; }

.jap-doc-link {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .4rem .65rem;
    border-radius: 8px;
    background: rgba(4,83,203,.07);
    color: #0453cb;
    font-size: .78rem; font-weight: 600;
    text-decoration: none;
    border: 1px dashed rgba(4,83,203,.30);
    margin-top: .5rem;
}
.jap-doc-link:hover { background: rgba(4,83,203,.12); color: #0453cb; }

.jap-action-btn {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .5rem 1rem;
    border-radius: 9px;
    font-size: .8rem; font-weight: 600;
    border: 1px solid transparent;
    cursor: pointer; transition: all .15s;
    text-decoration: none;
}
.jap-action-btn--approve {
    background: linear-gradient(135deg, #047857, #10b981);
    color: #fff; box-shadow: 0 2px 8px rgba(16,185,129,.25);
}
.jap-action-btn--approve:hover { transform: translateY(-1px); color: #fff; }
.jap-action-btn--reject {
    background: #fff; color: #dc2626; border-color: #dc2626;
}
.jap-action-btn--reject:hover { background: rgba(220,38,38,.06); color: #dc2626; }

.jap-badge {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .3rem .55rem;
    border-radius: 8px;
    font-size: .68rem; font-weight: 700;
    text-transform: uppercase;
}
.jap-badge--warning  { background: rgba(245,158,11,.10); color: #b45309; border: 1px solid rgba(245,158,11,.30); }
.jap-badge--success  { background: rgba(16,185,129,.10); color: #047857; border: 1px solid rgba(16,185,129,.30); }
.jap-badge--danger   { background: rgba(220,38,38,.08); color: #b91c1c; border: 1px solid rgba(220,38,38,.30); }

.jap-empty {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 3rem 1.5rem;
    text-align: center; color: #94a3b8;
}
.jap-empty-icon {
    width: 64px; height: 64px;
    border-radius: 16px;
    background: linear-gradient(135deg, rgba(4,83,203,.06), rgba(59,125,219,.12));
    color: #0453cb;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 1.5rem; margin-bottom: 1rem;
}
.jap-empty h3 { font-size: 1rem; color: #1e293b; margin: 0 0 .35rem; font-weight: 700; }
.jap-empty p { font-size: .85rem; color: #64748b; margin: 0; }

/* Modal reject */
.jap-modal-backdrop {
    position: fixed; inset: 0;
    background: rgba(15,23,42,.55);
    backdrop-filter: blur(2px);
    z-index: 1050;
    display: flex; align-items: center; justify-content: center;
    padding: 1rem;
}
.jap-modal {
    background: #fff;
    border-radius: 16px;
    width: 100%; max-width: 480px;
    box-shadow: 0 20px 60px rgba(15,23,42,.30);
}
.jap-modal-header {
    background: linear-gradient(135deg, #991b1b, #dc2626 70%);
    color: #fff;
    padding: 1.25rem 1.5rem;
    border-radius: 16px 16px 0 0;
    display: flex; align-items: center; gap: .75rem;
}
.jap-modal-header h3 { margin: 0; font-size: 1.05rem; font-weight: 700; color: #fff; }
.jap-modal-body { padding: 1.5rem; }
.jap-modal-body label { font-size: .82rem; font-weight: 600; color: #334155; margin-bottom: .35rem; display: block; }
.jap-modal-body textarea {
    width: 100%; padding: .65rem .85rem;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-size: .9rem;
    color: #1e293b;
}
.jap-modal-body textarea:focus {
    outline: none; border-color: #dc2626;
    box-shadow: 0 0 0 3px rgba(220,38,38,.12);
}
.jap-modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #f1f5f9;
    display: flex; gap: .5rem; justify-content: flex-end;
}
.jap-btn--secondary {
    background: #f1f5f9; color: #475569;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: .55rem 1.1rem;
    font-size: .85rem; font-weight: 600;
    cursor: pointer;
}
.jap-btn--danger {
    background: linear-gradient(135deg, #991b1b, #dc2626);
    color: #fff; border: none;
    border-radius: 10px;
    padding: .55rem 1.3rem;
    font-size: .85rem; font-weight: 600;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(220,38,38,.25);
}

@media (max-width: 768px) {
    .jap-hero { padding: 1.5rem 1.25rem 1.25rem; }
    .jap-hero h1 { font-size: 1.2rem; }
    .jap-kpi { min-width: calc(50% - .25rem); }
}
@media (max-width: 576px) {
    .jap-kpi { min-width: 100%; }
}
</style>
@endpush

@section('content')
<div class="jap-page" x-data="adminProcessingPage()" x-cloak>
    <div class="jap-hero">
        <div class="jap-hero-top">
            <div class="jap-hero-left">
                <div class="jap-hero-icon"><i class="fas fa-clipboard-check"></i></div>
                <div>
                    <h1>Traitement des justifications d'absence</h1>
                    <p>Validez ou rejetez les justifications soumises par les étudiants.</p>
                </div>
            </div>
        </div>

        <div class="jap-kpis">
            <div class="jap-kpi">
                <div class="jap-kpi-icon"><i class="fas fa-clock"></i></div>
                <div>
                    <div class="jap-kpi-value">{{ (int) $kpis['pending'] }}</div>
                    <div class="jap-kpi-label">En attente</div>
                </div>
            </div>
            <div class="jap-kpi">
                <div class="jap-kpi-icon"><i class="fas fa-check-circle"></i></div>
                <div>
                    <div class="jap-kpi-value">{{ (int) $kpis['approved'] }}</div>
                    <div class="jap-kpi-label">Validées</div>
                </div>
            </div>
            <div class="jap-kpi">
                <div class="jap-kpi-icon"><i class="fas fa-times-circle"></i></div>
                <div>
                    <div class="jap-kpi-value">{{ (int) $kpis['rejected'] }}</div>
                    <div class="jap-kpi-label">Rejetées</div>
                </div>
            </div>
            <div class="jap-kpi">
                <div class="jap-kpi-icon"><i class="fas fa-list"></i></div>
                <div>
                    <div class="jap-kpi-value">{{ (int) $kpis['total'] }}</div>
                    <div class="jap-kpi-label">Total traité</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Tabs status --}}
    <div class="jap-tabs">
        <a href="{{ route('esbtp.attendances.justifications.admin', ['status' => 'pending']) }}"
           class="jap-tab {{ $statusFilter === 'pending' ? 'jap-tab--active' : '' }}">
            <i class="fas fa-clock"></i> En attente ({{ (int) $kpis['pending'] }})
        </a>
        <a href="{{ route('esbtp.attendances.justifications.admin', ['status' => 'approved']) }}"
           class="jap-tab {{ $statusFilter === 'approved' ? 'jap-tab--active' : '' }}">
            <i class="fas fa-check-circle"></i> Validées
        </a>
        <a href="{{ route('esbtp.attendances.justifications.admin', ['status' => 'rejected']) }}"
           class="jap-tab {{ $statusFilter === 'rejected' ? 'jap-tab--active' : '' }}">
            <i class="fas fa-times-circle"></i> Rejetées
        </a>
    </div>

    {{-- Filters --}}
    <form method="GET" class="jap-filters">
        <input type="hidden" name="status" value="{{ $statusFilter }}">
        <div class="jap-filters-row">
            <div class="jap-filter-field" style="flex: 2 1 220px">
                <label><i class="fas fa-search"></i></label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher un étudiant (nom, prénom)">
            </div>
            <div class="jap-filter-field">
                <label><i class="fas fa-calendar"></i></label>
                <input type="date" name="date_debut" value="{{ request('date_debut') }}">
            </div>
            <div class="jap-filter-field">
                <label><i class="fas fa-calendar-alt"></i></label>
                <input type="date" name="date_fin" value="{{ request('date_fin') }}">
            </div>
            <button type="submit" class="jap-action-btn jap-action-btn--approve" style="background: linear-gradient(135deg, #0453cb, #3b7ddb); box-shadow: 0 2px 8px rgba(4,83,203,.25)">
                <i class="fas fa-filter"></i> Filtrer
            </button>
        </div>
    </form>

    @if($absences->isEmpty())
        <div class="jap-empty">
            <div class="jap-empty-icon"><i class="fas fa-inbox"></i></div>
            <h3>Aucune justification {{ $statusFilter === 'pending' ? 'en attente' : ($statusFilter === 'approved' ? 'validée' : 'rejetée') }}</h3>
            <p>La liste est vide pour les filtres sélectionnés.</p>
        </div>
    @else
        <div class="jap-list">
            @foreach($absences as $abs)
                @php
                    $etu = $abs->etudiant;
                    $matiereName = optional($abs->matiere)->name ?? optional($abs->seanceCours->matiere ?? null)->name ?? '—';
                    $initials = strtoupper(mb_substr($etu->prenoms ?? '?', 0, 1, 'UTF-8') . mb_substr($etu->nom ?? '?', 0, 1, 'UTF-8'));
                    $hasDoc = !empty($abs->document_path);
                @endphp
                <div class="jap-card">
                    <div class="jap-card-top">
                        <div class="jap-avatar">{{ $initials }}</div>
                        <div class="jap-card-info">
                            <p class="jap-card-name">{{ $etu->prenoms ?? '?' }} {{ $etu->nom ?? '?' }}</p>
                            <div class="jap-card-meta">
                                <span><i class="fas fa-book"></i> {{ $matiereName }}</span>
                                <span><i class="fas fa-calendar"></i> {{ optional($abs->date)->format('d/m/Y') ?? '—' }}</span>
                                @if($abs->justified_at)
                                    <span><i class="fas fa-paper-plane"></i> Soumis le {{ $abs->justified_at->format('d/m/Y') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="jap-card-actions">
                            @if($statusFilter === 'pending')
                                <form method="POST" action="{{ route('esbtp.attendances.process-justification', ['absenceId' => $abs->id]) }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="decision" value="approve">
                                    <button type="submit" class="jap-action-btn jap-action-btn--approve"
                                            onclick="return confirm('Valider cette justification ?')">
                                        <i class="fas fa-check"></i> Valider
                                    </button>
                                </form>
                                <button type="button" class="jap-action-btn jap-action-btn--reject"
                                        @click="openRejectModal({{ $abs->id }}, @js(($etu->prenoms ?? '') . ' ' . ($etu->nom ?? '')), @js($matiereName))">
                                    <i class="fas fa-times"></i> Rejeter
                                </button>
                            @elseif($statusFilter === 'approved')
                                <span class="jap-badge jap-badge--success"><i class="fas fa-check-circle"></i> Validée</span>
                            @elseif($statusFilter === 'rejected')
                                <span class="jap-badge jap-badge--danger"><i class="fas fa-times-circle"></i> Rejetée</span>
                            @endif
                        </div>
                    </div>

                    @if(!empty($abs->commentaire))
                        <div class="jap-justification-block">
                            <strong>Justification :</strong> {{ $abs->commentaire }}
                        </div>
                    @endif

                    @if($statusFilter === 'rejected' && !empty($abs->admin_comment))
                        <div class="jap-justification-block" style="border-left: 3px solid #dc2626; background: rgba(220,38,38,.04);">
                            <strong style="color:#dc2626">Motif du rejet :</strong> {{ $abs->admin_comment }}
                            @if($abs->processedBy)
                                <div style="margin-top:.25rem; font-size:.72rem; color:#94a3b8">
                                    par {{ $abs->processedBy->name }} le {{ optional($abs->processed_at)->format('d/m/Y H:i') }}
                                </div>
                            @endif
                        </div>
                    @endif

                    @if($hasDoc)
                        <a class="jap-doc-link"
                           href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('esbtp.justifications.document', now()->addMinutes(5), ['absence' => $abs->id]) }}"
                           target="_blank" rel="noopener">
                            <i class="fas fa-paperclip"></i> Voir le document justificatif
                        </a>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-3">
            {{ $absences->links() }}
        </div>
    @endif

    {{-- Reject modal --}}
    <div class="jap-modal-backdrop"
         x-show="rejectOpen"
         x-cloak
         x-transition.opacity
         @keydown.escape.window="rejectOpen = false"
         @click.self="rejectOpen = false">
        <div class="jap-modal" @click.stop>
            <div class="jap-modal-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Rejeter la justification</h3>
            </div>
            <form method="POST" :action="rejectAction()">
                @csrf
                <input type="hidden" name="decision" value="reject">
                <div class="jap-modal-body">
                    <p style="font-size:.85rem; color:#475569; margin-bottom:1rem">
                        Vous allez rejeter la justification de
                        <strong x-text="rejectContext.name"></strong>
                        pour <strong x-text="rejectContext.matiere"></strong>.
                    </p>
                    <label>Motif du rejet <span style="color:#dc2626">*</span></label>
                    <textarea name="admin_comment" rows="4" required minlength="5" maxlength="500"
                              placeholder="Expliquez clairement le motif du rejet (sera visible par l'étudiant)"></textarea>
                </div>
                <div class="jap-modal-footer">
                    <button type="button" class="jap-btn--secondary" @click="rejectOpen = false">Annuler</button>
                    <button type="submit" class="jap-btn--danger">
                        <i class="fas fa-times"></i> Rejeter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function adminProcessingPage() {
    return {
        rejectOpen: false,
        rejectContext: { id: null, name: '', matiere: '' },
        baseProcessUrl: @json(rtrim(url('/esbtp/attendances'), '/')),
        openRejectModal(id, name, matiere) {
            this.rejectContext = { id: id, name: name, matiere: matiere };
            this.rejectOpen = true;
        },
        rejectAction() {
            return this.baseProcessUrl + '/' + this.rejectContext.id + '/process-justification';
        }
    };
}
</script>
@endpush
