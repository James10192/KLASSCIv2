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
    @php
        // Une demande deja posee rend le bouton inoperant : redemander repondrait
        // « une demande est deja en attente ». Autant le dire avant le clic.
        $demandeEnCours = \App\Models\ESBTPDocumentApproval::demandeEnAttentePour(
            $documentType,
            (int) $etudiantId,
            isset($documentId) && $documentId !== '' ? (int) $documentId : null
        );
    @endphp
    <div class="alert alert-warning py-2 px-3 mb-3">
        {{ $printDecision->message() }}
    </div>
    @if($demandeEnCours)
        <div class="alert alert-info py-2 px-3 mb-3">
            <i class="fas fa-hourglass-half me-1"></i>
            Demande envoyée{{ $demandeEnCours->created_at ? ' '.$demandeEnCours->created_at->diffForHumans() : '' }} :
            elle attend l'accord d'une personne habilitée.
        </div>
    @else
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
