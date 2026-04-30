@php
    $validatedPayment = $inscription->paiements->firstWhere('status', 'validé');
    $pendingPayment = $inscription->paiements->firstWhere('status', 'en_attente');
    $hasPayment = (bool) ($validatedPayment || $pendingPayment);
    $paymentStatus = $validatedPayment ? 'validé' : ($pendingPayment ? 'en_attente' : 'aucun');
    $paymentAmount = $validatedPayment ? $validatedPayment->montant : ($pendingPayment ? $pendingPayment->montant : null);

    $hasProbleme = session('inscriptions_problemes') && isset(session('inscriptions_problemes')[$inscription->id]);
    $problemeInfo = $hasProbleme ? session('inscriptions_problemes')[$inscription->id] : null;

    $fullName = trim(($inscription->etudiant->nom ?? '') . ' ' . ($inscription->etudiant->prenoms ?? ''));
    $initials = strtoupper(substr($inscription->etudiant->nom ?? '?', 0, 1));
    $hue = crc32($fullName) % 360;
    $photoUrl = $inscription->etudiant->photo_url ?? null;
@endphp
<tr data-inscription-id="{{ $inscription->id }}"
    data-href="{{ route('esbtp.inscriptions.show', $inscription->id) }}"
    data-has-payment="{{ $hasPayment ? 1 : 0 }}"
    data-payment-status="{{ $paymentStatus }}"
    data-student-label="{{ $fullName }}"
    data-matricule="{{ $inscription->etudiant->matricule ?? '' }}"
    data-classe-id="{{ $inscription->classe->id ?? '' }}"
    data-classe-label="{{ optional($inscription->classe)->nom ?? optional($inscription->classe)->name ?? '' }}">
    @can('admin.access')
    <td data-no-row-click>
        @if($inscription->workflow_step !== 'etudiant_cree')
            <input type="checkbox" class="form-check-input inscription-checkbox ia-row-checkbox"
                   value="{{ $inscription->id }}"
                   data-inscription-id="{{ $inscription->id }}"
                   aria-label="Sélectionner cette inscription">
        @endif
    </td>
    @endcan
    <td>
        <div class="ii-student">
            @if($photoUrl)
                <img src="{{ $photoUrl }}" alt="" class="ii-student-photo">
            @else
                <div class="ii-student-photo" style="background:hsl({{ $hue }}, 60%, 55%);">{{ $initials }}</div>
            @endif
            <div>
                <div class="ii-student-name">{{ $fullName }}</div>
                <div class="ii-student-meta">{{ $inscription->etudiant->matricule ?? '—' }}</div>
            </div>
        </div>
    </td>
    <td>
        <div>{{ optional($inscription->filiere)->name ?? optional($inscription->filiere)->nom ?? 'N/A' }}</div>
        <div class="ii-student-meta">{{ optional($inscription->niveau)->name ?? optional($inscription->niveau)->nom ?? 'N/A' }}</div>
    </td>
    <td>{{ optional($inscription->classe)->name ?? optional($inscription->classe)->nom ?? '—' }}</td>
    <td>
        <x-workflow-step-badge :inscription="$inscription" />
    </td>
    <td>
        @if($validatedPayment)
            <span class="ii-paiement-chip ii-paiement-chip--paye">
                <i class="fas fa-check"></i> Payé
            </span>
            <div class="ii-student-meta">{{ number_format($paymentAmount, 0, ',', ' ') }} F</div>
        @elseif($pendingPayment)
            <span class="ii-paiement-chip ii-paiement-chip--attente">
                <i class="fas fa-clock"></i> En attente
            </span>
            <div class="ii-student-meta">{{ number_format($paymentAmount, 0, ',', ' ') }} F</div>
        @else
            <span class="ii-paiement-chip ii-paiement-chip--aucun">
                <i class="fas fa-times"></i> Aucun
            </span>
        @endif
    </td>
    <td>{{ optional($inscription->created_at)->format('d/m/Y') }}</td>
    <td style="text-align:right;" data-no-row-click>
        @if($hasProbleme)
            <div class="mb-2 d-flex flex-column gap-1" style="align-items:flex-end;">
                <span class="ii-paiement-chip {{ $problemeInfo['type'] === 'error' ? 'ii-paiement-chip--aucun' : 'ii-paiement-chip--attente' }}"
                      style="white-space:normal; text-align:left; line-height:1.3; max-width:240px;">
                    <i class="fas {{ $problemeInfo['type'] === 'error' ? 'fa-exclamation-circle' : 'fa-exclamation-triangle' }}"></i>
                    <span>{{ $problemeInfo['message'] }}</span>
                </span>
                @php
                    $raison = $problemeInfo['message'];
                    $isPaiementNonValide = str_contains($raison, 'paiement') && str_contains($raison, 'validé');
                    $isClassePleine = str_contains($raison, 'Classe pleine') || str_contains($raison, 'classe pleine');
                    $isSansPaiement = str_contains($raison, 'Aucun paiement') || str_contains($raison, 'sans paiement');
                @endphp
                @if($isPaiementNonValide)
                    <button type="button" class="ii-btn ii-btn--outline" style="font-size:.72rem; padding:.3rem .6rem;"
                            onclick="ouvrirModalValiderPaiement({{ $inscription->id }})">
                        <i class="fas fa-check-circle"></i> Valider paiement
                    </button>
                @elseif($isClassePleine)
                    <button type="button" class="ii-btn ii-btn--outline" style="font-size:.72rem; padding:.3rem .6rem;"
                            onclick="ouvrirModalChangerClasse({{ $inscription->id }})">
                        <i class="fas fa-exchange-alt"></i> Changer classe
                    </button>
                @elseif($isSansPaiement)
                    <button type="button" class="ii-btn ii-btn--outline" style="font-size:.72rem; padding:.3rem .6rem;"
                            onclick="openPaymentModal({{ $inscription->id }}, { autoValidate: true })">
                        <i class="fas fa-wallet"></i> Créer paiement
                    </button>
                @endif
            </div>
        @endif
        <div class="ii-actions inscription-actions-wrapper" data-inscription-actions="{{ $inscription->id }}" style="justify-content:flex-end;">
            <div class="inscription-actions-buttons" style="display:inline-flex; gap:.35rem;">
                @if($inscription->workflow_step !== 'etudiant_cree')
                    <button type="button" class="ii-action-btn ii-action-btn--primary validate-inscription-btn"
                            data-inscription-id="{{ $inscription->id }}"
                            data-has-payment="{{ $hasPayment ? 1 : 0 }}"
                            title="Valider l'inscription">
                        <i class="fas fa-check-double"></i>
                    </button>
                @endif
                @if($pendingPayment)
                    <button class="ii-action-btn ii-action-btn--primary"
                            onclick="ouvrirModalValiderPaiement({{ $inscription->id }})"
                            title="Valider le paiement">
                        <i class="fas fa-check-circle"></i>
                    </button>
                @elseif(!$hasPayment)
                    <button class="ii-action-btn ii-action-btn--primary"
                            onclick="openPaymentModal({{ $inscription->id }})"
                            title="Associer un paiement">
                        <i class="fas fa-wallet"></i>
                    </button>
                @endif
                <button class="ii-action-btn ii-action-btn--danger"
                        onclick="openCancelModal({{ $inscription->id }})"
                        title="Annuler l'inscription">
                    <i class="fas fa-ban"></i>
                </button>
            </div>
            <div class="inscription-actions-spinner" aria-hidden="true" style="display:none;">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            </div>
        </div>
    </td>
</tr>
