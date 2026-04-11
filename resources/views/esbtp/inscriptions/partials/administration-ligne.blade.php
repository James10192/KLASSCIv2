@php
    $validatedPayment = $inscription->paiements->firstWhere('status', 'validé');
    $pendingPayment = $inscription->paiements->firstWhere('status', 'en_attente');
    $hasPayment = (bool) ($validatedPayment || $pendingPayment);
    $paymentStatus = $validatedPayment ? 'validé' : ($pendingPayment ? 'en_attente' : 'aucun');
    $paymentAmount = $validatedPayment ? $validatedPayment->montant : ($pendingPayment ? $pendingPayment->montant : null);

    $hasProbleme = session('inscriptions_problemes') && isset(session('inscriptions_problemes')[$inscription->id]);
    $problemeInfo = $hasProbleme ? session('inscriptions_problemes')[$inscription->id] : null;
    $problemeClass = $hasProbleme ? ($problemeInfo['type'] === 'error' ? 'table-danger' : 'table-warning') : '';
@endphp
<tr class="{{ $problemeClass }}" data-inscription-id="{{ $inscription->id }}"
    data-has-payment="{{ $hasPayment ? 1 : 0 }}"
    data-payment-status="{{ $paymentStatus }}"
    data-student-label="{{ ($inscription->etudiant->nom ?? '') . ' ' . ($inscription->etudiant->prenoms ?? '') }}"
    data-matricule="{{ $inscription->etudiant->matricule ?? '' }}"
    data-classe-id="{{ $inscription->classe->id ?? '' }}"
    data-classe-label="{{ optional($inscription->classe)->nom ?? optional($inscription->classe)->name ?? '' }}">
    @can('access_admin')
    <td>
        @if($inscription->workflow_step !== 'etudiant_cree')
        <input type="checkbox" class="form-check-input inscription-checkbox"
               value="{{ $inscription->id }}"
               data-inscription-id="{{ $inscription->id }}">
        @endif
    </td>
    @endcan
    <td><strong>{{ $inscription->etudiant->matricule ?? 'N/A' }}</strong></td>
    <td>{{ $inscription->etudiant->nom ?? '' }} {{ $inscription->etudiant->prenoms ?? '' }}</td>
    <td>{{ optional($inscription->filiere)->nom ?? optional($inscription->filiere)->name ?? 'N/A' }}</td>
    <td>{{ optional($inscription->niveau)->nom ?? optional($inscription->niveau)->name ?? 'N/A' }}</td>
    <td>{{ optional($inscription->classe)->nom ?? optional($inscription->classe)->name ?? 'N/A' }}</td>
    <td>
        @switch($inscription->workflow_step)
            @case('prospect')
                <span class="badge bg-secondary">Prospect</span>
                @break
            @case('documents_complets')
                <span class="badge bg-info">Documents complets</span>
                @break
            @case('en_validation')
                <span class="badge bg-warning">En validation</span>
                @break
            @case('etudiant_cree')
                <span class="badge bg-success">Validée</span>
                @break
            @default
                <span class="badge bg-light text-dark">{{ $inscription->workflow_step }}</span>
        @endswitch
    </td>
    <td>
        @if($validatedPayment)
            <span class="badge bg-success">
                <i class="fas fa-check me-1"></i>Payé
            </span>
            <small class="d-block text-muted mt-1">
                {{ number_format($paymentAmount, 0, ',', ' ') }} F
            </small>
        @elseif($pendingPayment)
            <span class="badge bg-warning text-dark">
                <i class="fas fa-clock me-1"></i>En attente
            </span>
            <small class="d-block text-muted mt-1">
                {{ number_format($paymentAmount, 0, ',', ' ') }} F
            </small>
        @else
            <span class="badge bg-danger">
                <i class="fas fa-times me-1"></i>Non payé
            </span>
        @endif
    </td>
    <td>
        @if($hasProbleme)
            <div class="mb-2 d-flex flex-column gap-2">
                <span class="badge {{ $problemeInfo['type'] === 'error' ? 'bg-danger' : 'bg-warning text-dark' }} d-inline-flex align-items-start"
                      style="font-size: 0.75rem; white-space: normal; word-wrap: break-word; text-align: left; line-height: 1.3;">
                    <i class="fas {{ $problemeInfo['type'] === 'error' ? 'fa-exclamation-circle' : 'fa-exclamation-triangle' }} me-1 mt-1" style="flex-shrink: 0;"></i>
                    <span style="word-break: break-word;">{{ $problemeInfo['message'] }}</span>
                </span>

                @php
                    $raison = $problemeInfo['message'];
                    $isPaiementNonValide = str_contains($raison, 'paiement') && str_contains($raison, 'validé');
                    $isClassePleine = str_contains($raison, 'Classe pleine') || str_contains($raison, 'classe pleine');
                    $isSansPaiement = str_contains($raison, 'Aucun paiement') || str_contains($raison, 'sans paiement');
                @endphp

                @if($isPaiementNonValide)
                    <button type="button" class="btn btn-sm btn-outline-warning"
                            onclick="ouvrirModalValiderPaiement({{ $inscription->id }})">
                        <i class="fas fa-check-circle me-1"></i>Valider paiement
                    </button>
                @elseif($isClassePleine)
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary"
                                onclick="ouvrirModalChangerClasse({{ $inscription->id }})">
                            <i class="fas fa-exchange-alt me-1"></i>Changer classe
                        </button>
                        @if(auth()->user()->hasAnyPermission(['access_admin', 'can_manage_school']))
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                    onclick="handleInscriptionValidation({{ $inscription->id }}, {{ $hasPayment ? 'true' : 'false' }}, true)">
                                <i class="fas fa-bolt me-1"></i>Forcer validation
                            </button>
                        @endif
                    </div>
                @elseif($isSansPaiement)
                    <button type="button" class="btn btn-sm btn-outline-primary"
                            onclick="openPaymentModal({{ $inscription->id }}, { autoValidate: true })">
                        <i class="fas fa-wallet me-1"></i>Créer paiement
                    </button>
                @endif
            </div>
        @endif
        <div class="inscription-actions-wrapper" data-inscription-actions="{{ $inscription->id }}">
            <div class="inscription-actions-buttons">
                <a href="{{ route('esbtp.inscriptions.show', $inscription->id) }}"
                   class="action-btn action-view" title="Voir le dossier">
                    <i class="fas fa-folder-open"></i>
                </a>

                @if($inscription->workflow_step !== 'etudiant_cree')
                    <button type="button"
                            class="action-btn action-validate validate-inscription-btn"
                            data-inscription-id="{{ $inscription->id }}"
                            data-has-payment="{{ $hasPayment ? 1 : 0 }}"
                            title="Valider l'inscription">
                        <i class="fas fa-check-double"></i>
                    </button>
                @endif

                @if($pendingPayment)
                    <button class="action-btn action-payment"
                            onclick="ouvrirModalValiderPaiement({{ $inscription->id }})"
                            title="Valider le paiement">
                        <i class="fas fa-check-circle"></i>
                    </button>
                @elseif(!$hasPayment)
                    <button class="action-btn action-payment"
                            onclick="openPaymentModal({{ $inscription->id }})"
                            title="Associer un paiement">
                        <i class="fas fa-wallet"></i>
                    </button>
                @endif

                <button class="action-btn action-cancel"
                        onclick="openCancelModal({{ $inscription->id }})"
                        title="Annuler l'inscription">
                    <i class="fas fa-ban"></i>
                </button>

            </div>
            <div class="inscription-actions-spinner" aria-hidden="true">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            </div>
        </div>
    </td>
</tr>
