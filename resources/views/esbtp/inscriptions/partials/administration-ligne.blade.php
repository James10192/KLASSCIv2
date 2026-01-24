@php
    $validatedPayment = $inscription->paiements->firstWhere('status', 'validé');
    $pendingPayment = $inscription->paiements->firstWhere('status', 'en_attente');
    $hasPayment = (bool) ($validatedPayment || $pendingPayment);
    $paymentStatus = $validatedPayment ? 'validé' : ($pendingPayment ? 'en_attente' : 'aucun');
    $paymentAmount = $validatedPayment ? $validatedPayment->montant : ($pendingPayment ? $pendingPayment->montant : null);
@endphp
<tr data-inscription-id="{{ $inscription->id }}" data-has-payment="{{ $hasPayment ? 1 : 0 }}" data-payment-status="{{ $paymentStatus }}">
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
        <div class="inscription-actions-wrapper" data-inscription-actions="{{ $inscription->id }}">
            <div class="inscription-actions-buttons">
                <a href="{{ route('esbtp.inscriptions.show', $inscription->id) }}"
                   class="btn btn-sm btn-outline-info" title="Voir détails">
                    <i class="fas fa-eye"></i>
                </a>

                @if($inscription->workflow_step !== 'etudiant_cree')
                    <button type="button"
                            class="btn btn-sm btn-outline-success validate-inscription-btn"
                            data-inscription-id="{{ $inscription->id }}"
                            data-has-payment="{{ $hasPayment ? 1 : 0 }}"
                            title="Valider l'inscription">
                        <i class="fas fa-check-circle"></i>
                    </button>
                @endif

                @if(!$hasPayment)
                    <button class="btn btn-sm btn-outline-warning"
                            onclick="openPaymentModal({{ $inscription->id }})"
                            title="Associer un paiement">
                        <i class="fas fa-credit-card"></i>
                    </button>
                @endif

                <button class="btn btn-sm btn-outline-danger"
                        onclick="openCancelModal({{ $inscription->id }})"
                        title="Annuler l'inscription">
                    <i class="fas fa-times"></i>
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
