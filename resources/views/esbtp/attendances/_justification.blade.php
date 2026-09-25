{{-- Une justification d'absence a traiter : rendue par la page et par la suite chargee au defilement.
     Attend $abs et $statusFilter. --}}
@php
    $etu = $abs->etudiant;
    $matiereName = optional($abs->matiere)->name ?? optional($abs->seanceCours->matiere ?? null)->name ?? '—';
    $initials = strtoupper(mb_substr($etu->prenoms ?? '?', 0, 1, 'UTF-8') . mb_substr($etu->nom ?? '?', 0, 1, 'UTF-8'));
    $hasDoc = !empty($abs->document_path);
@endphp
<div class="jap-card" data-li-cle="{{ $abs->id }}">
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
