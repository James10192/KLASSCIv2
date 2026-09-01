@php
    $printGuard = app(\App\Services\DocumentPrintGuard::class);
    $printRequiresApproval = $printGuard->requiresApproval();
    $soldeImpaye = $printRequiresApproval ? $printGuard->soldeImpaye((int) $etudiantId) : 0;
    $latestApproval = $printRequiresApproval && $soldeImpaye <= 0
        ? $printGuard->latestApproved($documentType, (int) $etudiantId, $documentId ?? null)
        : null;
    $printAllowed = ! $printRequiresApproval || ($soldeImpaye <= 0 && $latestApproval);
@endphp
@if($printRequiresApproval)
    @if($soldeImpaye > 0)
        <div class="alert alert-danger py-2 px-3 mb-3">
            Impression bloquée : solde impayé de {{ number_format($soldeImpaye, 0, ',', ' ') }} F.
            L'étudiant doit régulariser en caisse avant toute demande.
        </div>
    @elseif($latestApproval)
        <div class="alert alert-success py-2 px-3 mb-3">
            Document approuvé. L'impression est autorisée.
        </div>
    @else
        <div class="alert alert-warning py-2 px-3 mb-3">
            L'impression officielle exige l'accord de la responsable scolarité.
        </div>
        @canany(['documents.view', 'documents.print', 'documents.approve'])
            <form method="POST" action="{{ route('esbtp.documents.approvals.store') }}" class="d-inline">
                @csrf
                <input type="hidden" name="document_type" value="{{ $documentType }}">
                <input type="hidden" name="etudiant_id" value="{{ $etudiantId }}">
                @if(!empty($documentId))
                    <input type="hidden" name="document_id" value="{{ $documentId }}">
                @endif
                <button class="btn-acasi secondary" type="submit">
                    <i class="fas fa-stamp me-1"></i>Demander l'approbation
                </button>
            </form>
        @endcanany
    @endif
@endif
