@extends('layouts.app')

@section('title', 'Inscriptions sous réserve')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .sr-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
    .sr-stat-card {
        background: #fff; border-radius: 12px; padding: 20px 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,.08); position: relative; overflow: hidden;
        transition: transform .2s, box-shadow .2s;
    }
    .sr-stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.12); }
    .sr-stat-card::before { content:''; position:absolute; top:0; left:0; right:0; height:4px; }
    .sr-stat-card.primary::before { background: linear-gradient(90deg, #0453cb, #5e91de); }
    .sr-stat-card.success::before { background: linear-gradient(90deg, #059669, #10b981); }
    .sr-stat-card.danger::before { background: linear-gradient(90deg, #dc2626, #ef4444); }
    .sr-stat-top { display: flex; justify-content: space-between; align-items: flex-start; }
    .sr-stat-icon {
        width: 44px; height: 44px; border-radius: 10px; display: flex;
        align-items: center; justify-content: center; font-size: 1.1rem; color: #fff;
    }
    .sr-stat-value { font-size: 1.8rem; font-weight: 700; color: #1e293b; line-height: 1; margin-top: 12px; }
    .sr-stat-label { font-size: .78rem; color: #64748b; text-transform: uppercase; letter-spacing: .5px; margin-top: 4px; }

    .sr-filter-card {
        background: #fff; border-radius: 12px; padding: 20px 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,.08); margin-bottom: 1.5rem;
    }
    .sr-filter-title { font-size: .9rem; font-weight: 700; color: #1e293b; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
    .sr-filter-title i { color: #0453cb; }

    .sr-table-card {
        background: #fff; border-radius: 12px; overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,.08);
    }
    .sr-table-header {
        padding: 16px 24px; display: flex; justify-content: space-between;
        align-items: center; border-bottom: 1px solid #e2e8f0; flex-wrap: wrap; gap: 10px;
    }
    .sr-table thead th {
        background: linear-gradient(135deg, #0453cb, #1b64d4); color: #fff;
        font-size: .78rem; font-weight: 600; text-transform: uppercase; letter-spacing: .4px;
        padding: 12px 16px; white-space: nowrap; border: none;
    }
    .sr-table tbody td { padding: 12px 16px; font-size: .85rem; color: #334155; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
    .sr-table tbody tr:hover { background: rgba(4,83,203,.03); }
    .sr-table tbody tr:last-child td { border-bottom: none; }

    .sr-badge {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 4px 10px; border-radius: 6px; font-size: .75rem; font-weight: 600;
    }
    .sr-badge.reserve { background: rgba(245,158,11,.1); color: #92400e; border: 1px solid rgba(245,158,11,.3); }
    .sr-badge.paye { background: rgba(16,185,129,.1); color: #065f46; }
    .sr-badge.non-paye { background: rgba(239,68,68,.1); color: #991b1b; }
    .sr-badge.workflow { background: rgba(59,130,246,.1); color: #1e40af; }

    .sr-btn { display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; border-radius: 6px; font-size: .8rem; font-weight: 600; border: none; cursor: pointer; transition: all .2s; }
    .sr-btn.confirm { background: linear-gradient(135deg, #059669, #10b981); color: #fff; }
    .sr-btn.confirm:hover { transform: translateY(-1px); box-shadow: 0 3px 8px rgba(16,185,129,.35); }
    .sr-btn.cancel { background: transparent; color: #dc2626; border: 1px solid #fecaca; }
    .sr-btn.cancel:hover { background: #fef2f2; }

    .sr-bulk-bar {
        padding: 12px 24px; display: flex; align-items: center; gap: 12px;
        background: linear-gradient(135deg, #f0f9ff, #e0f2fe); border-bottom: 1px solid #bae6fd;
    }
    .sr-bulk-count { font-size: .82rem; color: #0369a1; font-weight: 600; }

    .sr-empty {
        text-align: center; padding: 60px 20px;
    }
    .sr-empty-icon {
        width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 16px;
        background: linear-gradient(135deg, #d1fae5, #a7f3d0);
        display: flex; align-items: center; justify-content: center;
    }
    .sr-empty-icon i { font-size: 2rem; color: #059669; }
    .sr-empty h4 { color: #1e293b; font-size: 1.1rem; margin-bottom: 6px; }
    .sr-empty p { color: #64748b; font-size: .88rem; }

    .sr-student-name { font-weight: 600; color: #1e293b; }
    .sr-student-name small { font-weight: 400; color: #64748b; display: block; margin-top: 2px; }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        {{-- Header --}}
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-clipboard-check me-2"></i>Inscriptions sous réserve</h1>
                <p class="header-subtitle">Gérer les inscriptions conditionnelles en attente de confirmation</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.inscriptions.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour aux inscriptions
                </a>
            </div>
        </div>

        {{-- Stat cards --}}
        <div class="sr-stats">
            <div class="sr-stat-card primary">
                <div class="sr-stat-top">
                    <div>
                        <div class="sr-stat-value">{{ $stats['total'] }}</div>
                        <div class="sr-stat-label">Total sous réserve</div>
                    </div>
                    <div class="sr-stat-icon" style="background: linear-gradient(135deg, #0453cb, #5e91de);">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                </div>
            </div>
            <div class="sr-stat-card success">
                <div class="sr-stat-top">
                    <div>
                        <div class="sr-stat-value">{{ $stats['avec_paiement'] }}</div>
                        <div class="sr-stat-label">Avec paiement</div>
                    </div>
                    <div class="sr-stat-icon" style="background: linear-gradient(135deg, #059669, #10b981);">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
            <div class="sr-stat-card danger">
                <div class="sr-stat-top">
                    <div>
                        <div class="sr-stat-value">{{ $stats['sans_paiement'] }}</div>
                        <div class="sr-stat-label">Sans paiement</div>
                    </div>
                    <div class="sr-stat-icon" style="background: linear-gradient(135deg, #dc2626, #ef4444);">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i><strong>Attention :</strong> {{ session('warning') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        {{-- Filtres --}}
        <div class="sr-filter-card">
            <div class="sr-filter-title"><i class="fas fa-filter"></i> Filtrer les inscriptions</div>
            <form method="GET" action="{{ route('esbtp.inscriptions.sous-reserve') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label" style="font-size:.82rem; font-weight:600; color:#334155;">
                            <i class="fas fa-search me-1"></i> Recherche
                        </label>
                        <input type="text" class="form-control" name="search" value="{{ $search ?? '' }}" placeholder="Nom, prénom ou matricule...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size:.82rem; font-weight:600; color:#334155;">
                            <i class="fas fa-calendar-alt me-1"></i> Année universitaire
                        </label>
                        <select name="annee_id" class="form-select">
                            <option value="">Toutes les années</option>
                            @foreach($annees as $annee)
                                <option value="{{ $annee->id }}" {{ ($anneeFilterId ?? '') == $annee->id ? 'selected' : '' }}>
                                    {{ $annee->name }}@if($annee->is_current) (Courante)@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size:.82rem; font-weight:600; color:#334155;">
                            <i class="fas fa-graduation-cap me-1"></i> Condition
                        </label>
                        <select name="condition" class="form-select">
                            <option value="">Toutes</option>
                            <option value="BACCALAURÉAT" {{ request('condition') === 'BACCALAURÉAT' ? 'selected' : '' }}>BACCALAURÉAT</option>
                            <option value="BTS" {{ request('condition') === 'BTS' ? 'selected' : '' }}>BTS</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn-acasi primary w-100">
                            <i class="fas fa-search me-1"></i> Filtrer
                        </button>
                    </div>
                </div>
            </form>
        </div>

        @if($inscriptions->isEmpty())
            <div class="sr-table-card">
                <div class="sr-empty">
                    <div class="sr-empty-icon"><i class="fas fa-check-double"></i></div>
                    <h4>Aucune inscription sous réserve</h4>
                    <p>Toutes les inscriptions pour cette période sont confirmées.</p>
                </div>
            </div>
        @else
            <form method="POST" action="{{ route('esbtp.inscriptions.lever-reserves-bulk') }}" id="bulkForm">
                @csrf
                <div class="sr-table-card">
                    {{-- Bulk bar --}}
                    <div class="sr-bulk-bar">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="selectAll">
                            <label class="form-check-label fw-bold" for="selectAll" style="font-size:.82rem; color:#0369a1;">
                                Tout sélectionner
                            </label>
                        </div>
                        <button type="button" class="sr-btn confirm" id="bulkConfirmBtn" disabled onclick="confirmBulkLever()">
                            <i class="fas fa-check-double"></i> Lever les réserves
                        </button>
                        <span class="sr-bulk-count" id="selectedCount">0 sélectionné(s)</span>
                        <span style="margin-left:auto; font-size:.82rem; color:#64748b;">
                            <strong>{{ $inscriptions->count() }}</strong> résultat(s)
                        </span>
                    </div>

                    {{-- Table --}}
                    <div class="table-responsive">
                        <table class="table sr-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width:40px;"></th>
                                    <th>Étudiant</th>
                                    <th>Classe</th>
                                    <th>Année</th>
                                    <th>Condition</th>
                                    <th>Paiement</th>
                                    <th>Workflow</th>
                                    <th style="width:150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($inscriptions as $inscription)
                                <tr>
                                    <td>
                                        <input class="form-check-input row-checkbox" type="checkbox" name="inscription_ids[]" value="{{ $inscription->id }}">
                                    </td>
                                    <td>
                                        <div class="sr-student-name">
                                            {{ $inscription->etudiant->nom ?? '—' }} {{ $inscription->etudiant->prenoms ?? '' }}
                                            <small>{{ $inscription->etudiant->matricule ?? '' }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        {{ $inscription->classe->name ?? '—' }}
                                        @if($inscription->filiere)
                                            <br><small style="color:#64748b;">{{ $inscription->filiere->name }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $inscription->anneeUniversitaire->name ?? '—' }}</strong>
                                        @if($anneeEnCours && $inscription->annee_universitaire_id == $anneeEnCours->id)
                                            <br><small style="color:#059669; font-weight:600;">Année courante</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="sr-badge reserve">
                                            <i class="fas fa-clock"></i> {{ $inscription->condition_reserve ?? 'Non précisé' }}
                                        </span>
                                    </td>
                                    <td>
                                        @php $totalPaye = $inscription->paiements->where('status', 'validé')->sum('montant'); @endphp
                                        @if($totalPaye > 0)
                                            <span class="sr-badge paye"><i class="fas fa-check-circle"></i> {{ number_format($totalPaye, 0, ',', ' ') }} F</span>
                                        @else
                                            <span class="sr-badge non-paye"><i class="fas fa-times-circle"></i> Aucun</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="sr-badge workflow"><i class="fas fa-tasks"></i> {{ $inscription->workflow_step_label }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <form method="POST" action="{{ route('esbtp.inscriptions.lever-reserve', $inscription) }}"
                                                  onsubmit="return confirm('Confirmer la levée de réserve pour {{ addslashes(($inscription->etudiant->nom ?? '') . ' ' . ($inscription->etudiant->prenoms ?? '')) }} ?')">
                                                @csrf
                                                <button type="submit" class="sr-btn confirm" title="Lever la réserve">
                                                    <i class="fas fa-check"></i> Confirmer
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('esbtp.inscriptions.annuler', $inscription) }}"
                                                  onsubmit="return confirm('Annuler cette inscription ? Cette action est irréversible.')">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="motif_annulation" value="Condition de réserve non remplie">
                                                <button type="submit" class="sr-btn cancel" title="Annuler l'inscription">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>
        @endif

    </div>
</div>

{{-- Modal confirmation bulk --}}
<div class="modal fade" id="bulkConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:none; border-radius:14px; overflow:hidden;">
            <div class="modal-header" style="background:linear-gradient(135deg, #059669, #10b981); color:#fff; border:none; padding:20px 24px;">
                <h5 class="modal-title"><i class="fas fa-check-double me-2"></i> Confirmation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:24px;">
                <p>Vous allez lever la réserve pour <strong id="bulkCountText">0</strong> inscription(s).</p>
                <p style="color:#64748b; font-size:.88rem;">Les documents afficheront désormais "Est régulièrement inscrit(e)" sans la mention sous réserve.</p>
            </div>
            <div class="modal-footer" style="border:none; padding:16px 24px;">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="sr-btn confirm" id="bulkConfirmSubmit" style="padding:8px 20px;">
                    <i class="fas fa-check-double"></i> Confirmer la levée
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.row-checkbox');
    const bulkBtn = document.getElementById('bulkConfirmBtn');
    const selectedCount = document.getElementById('selectedCount');

    function updateCount() {
        const checked = document.querySelectorAll('.row-checkbox:checked').length;
        selectedCount.textContent = checked + ' sélectionné(s)';
        bulkBtn.disabled = checked === 0;
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateCount();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            if (!this.checked) selectAll.checked = false;
            if (document.querySelectorAll('.row-checkbox:checked').length === checkboxes.length) selectAll.checked = true;
            updateCount();
        });
    });
});

function confirmBulkLever() {
    const checked = document.querySelectorAll('.row-checkbox:checked').length;
    document.getElementById('bulkCountText').textContent = checked;
    const modal = new bootstrap.Modal(document.getElementById('bulkConfirmModal'));
    modal.show();
    document.getElementById('bulkConfirmSubmit').onclick = function() {
        modal.hide();
        document.getElementById('bulkForm').submit();
    };
}
</script>
@endpush
