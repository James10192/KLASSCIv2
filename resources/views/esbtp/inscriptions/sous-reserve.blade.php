@extends('layouts.app')

@section('title', 'Inscriptions sous réserve')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .sous-reserve-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .filter-bar {
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
    }
    .badge-reserve {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #f59e0b;
    }
    .badge-paye {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        background: #d1fae5;
        color: #065f46;
    }
    .badge-en-attente {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        background: #fee2e2;
        color: #991b1b;
    }
    .bulk-actions {
        display: flex;
        gap: 0.5rem;
        align-items: center;
        margin-bottom: 1rem;
    }
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--text-secondary, #64748b);
    }
    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        color: #10b981;
    }
    .table-modern th { font-size: 13px; }
    .table-modern td { font-size: 13px; vertical-align: middle; }
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
                    <i class="fas fa-arrow-left"></i> Retour aux inscriptions
                </a>
            </div>
        </div>

        {{-- Filtre par année --}}
        <div class="main-card mb-3">
            <div class="filter-bar">
                <label class="fw-bold" style="font-size: 14px;">
                    <i class="fas fa-filter me-1"></i> Filtrer par année :
                </label>
                <form method="GET" action="{{ route('esbtp.inscriptions.sous-reserve') }}" class="d-flex gap-2 align-items-center">
                    <select name="annee_id" class="form-select form-select-sm" style="width: auto; min-width: 200px;" onchange="this.form.submit()">
                        <option value="">Toutes les années</option>
                        @foreach($annees as $annee)
                            <option value="{{ $annee->id }}" {{ $anneeFilterId == $annee->id ? 'selected' : '' }}>
                                {{ $annee->name }}
                                @if($annee->is_current) (Courante) @endif
                            </option>
                        @endforeach
                    </select>
                </form>
                <span class="text-muted" style="font-size: 13px;">
                    <strong>{{ $inscriptions->count() }}</strong> inscription(s) sous réserve
                </span>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i><strong>Attention :</strong> {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($inscriptions->isEmpty())
            <div class="main-card">
                <div class="empty-state">
                    <i class="fas fa-check-double"></i>
                    <h4>Aucune inscription sous réserve</h4>
                    <p>Toutes les inscriptions pour cette période sont confirmées.</p>
                </div>
            </div>
        @else
            {{-- Bulk actions --}}
            <form method="POST" action="{{ route('esbtp.inscriptions.lever-reserves-bulk') }}" id="bulkForm">
                @csrf
                <div class="main-card">
                    <div class="bulk-actions">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAll">
                            <label class="form-check-label fw-bold" for="selectAll" style="font-size: 13px;">
                                Tout sélectionner
                            </label>
                        </div>
                        <button type="button" class="btn btn-success btn-sm" id="bulkConfirmBtn" disabled
                                onclick="confirmBulkLever()">
                            <i class="fas fa-check-double me-1"></i> Lever les réserves sélectionnées
                        </button>
                        <span class="text-muted ms-2" style="font-size: 12px;" id="selectedCount">0 sélectionné(s)</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-modern table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 40px;"></th>
                                    <th>Etudiant</th>
                                    <th>Classe</th>
                                    <th>Filière</th>
                                    <th>Année</th>
                                    <th>Condition</th>
                                    <th>Paiement</th>
                                    <th>Statut workflow</th>
                                    <th style="width: 160px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($inscriptions as $inscription)
                                <tr>
                                    <td>
                                        <input class="form-check-input row-checkbox"
                                               type="checkbox" name="inscription_ids[]"
                                               value="{{ $inscription->id }}">
                                    </td>
                                    <td>
                                        <strong>{{ $inscription->etudiant->nom ?? '—' }}</strong>
                                        {{ $inscription->etudiant->prenoms ?? '' }}
                                    </td>
                                    <td>{{ $inscription->classe->name ?? '—' }}</td>
                                    <td>{{ $inscription->filiere->name ?? '—' }}</td>
                                    <td>
                                        {{ $inscription->anneeUniversitaire->name ?? '—' }}
                                        @if($anneeEnCours && $inscription->annee_universitaire_id == $anneeEnCours->id)
                                            <br><small class="text-success fw-bold">Année courante</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge-reserve">
                                            <i class="fas fa-clock me-1"></i>{{ $inscription->condition_reserve ?? 'Non précisé' }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $totalPaye = $inscription->paiements->where('status', 'validé')->sum('montant');
                                        @endphp
                                        @if($totalPaye > 0)
                                            <span class="badge-paye">{{ number_format($totalPaye, 0, ',', ' ') }} F</span>
                                        @else
                                            <span class="badge-en-attente">Aucun</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $inscription->workflow_step_label }}</small>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <form method="POST" action="{{ route('esbtp.inscriptions.lever-reserve', $inscription) }}"
                                                  onsubmit="return confirm('Confirmer la levée de réserve pour {{ addslashes($inscription->etudiant->nom ?? '') }} {{ addslashes($inscription->etudiant->prenoms ?? '') }} ?')">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm" title="Lever la réserve">
                                                    <i class="fas fa-check"></i> Confirmer
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('esbtp.inscriptions.annuler', $inscription) }}"
                                                  onsubmit="return confirm('Annuler l\'inscription de {{ addslashes($inscription->etudiant->nom ?? '') }} {{ addslashes($inscription->etudiant->prenoms ?? '') }} ? Cette action est irréversible.')">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="motif_annulation" value="Condition de réserve non remplie">
                                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Annuler l'inscription">
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

{{-- Modal de confirmation bulk --}}
<div class="modal fade" id="bulkConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-check-double me-2"></i>Confirmation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Vous allez lever la réserve pour <strong id="bulkCountText">0</strong> inscription(s).</p>
                <p class="text-muted">Les documents (certificats, attestations) afficheront désormais
                   "Est régulièrement inscrit(e)" au lieu de "Sera régulièrement inscrit(e) sous réserve".</p>
                <p><strong>Confirmer cette action ?</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" id="bulkConfirmSubmit">
                    <i class="fas fa-check-double me-1"></i> Confirmer la levée
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
            if (document.querySelectorAll('.row-checkbox:checked').length === checkboxes.length) {
                selectAll.checked = true;
            }
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
