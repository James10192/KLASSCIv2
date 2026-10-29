@php
    /*
     * La decision d'impression est calculee par la vue parente : elle en a besoin
     * pour ses propres boutons (Apercu PDF, Generer PDF...). Une variable definie
     * ici ne remonterait PAS a la vue parente : une partial incluse est rendue dans
     * une portee separee. La parente transmet donc $printDecision ; on ne recalcule que
     * si un appelant ne l'a pas fait.
     */
    $printDecision = $printDecision ?? app(\App\Services\DocumentPrintGuard::class)->decide(
        auth()->user(),
        $documentType,
        (int) $etudiantId,
        isset($documentId) ? (int) $documentId : null
    );
@endphp
@if($printDecision->isUnpaid())
    <div class="alert alert-danger py-2 px-3 mb-3">
        {{ $printDecision->message() }}
    </div>
@elseif($printDecision->isApproved())
    <div class="alert alert-success py-2 px-3 mb-3">
        Document approuvé. L'impression est autorisée.
    </div>
@elseif($printDecision->needsApprovalRequest())
    <div class="alert alert-warning py-2 px-3 mb-3">
        {{ $printDecision->message() }}
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
