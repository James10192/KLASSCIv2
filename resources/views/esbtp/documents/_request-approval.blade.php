@php
    $printRequiresApproval = app(\App\Services\TenantScolariteSettings::class)->printRequiresApproval();
    $latestApproval = $printRequiresApproval
        ? app(\App\Services\DocumentPrintGuard::class)->latestApproved($documentType, (int) $etudiantId, $documentId ?? null)
        : null;
@endphp
@if($printRequiresApproval)
    <div class="alert alert-warning py-2 px-3 mb-3">
        @if($latestApproval)
            Document approuvé. L'impression est autorisée.
        @else
            L'impression officielle exige l'accord du responsable scolarité. L'aperçu reste libre.
        @endif
    </div>
    @canany(['documents.view', 'documents.print', 'documents.approve'])
        @unless($latestApproval)
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
        @endunless
    @endcanany
@endif
