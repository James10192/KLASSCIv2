{{-- Une declaration TPE en attente : rendue par la page et par la suite chargee au defilement. --}}
@php
    $initial = mb_strtoupper(mb_substr($decl->etudiant->prenoms ?? $decl->etudiant->nom ?? '?', 0, 1, 'UTF-8'), 'UTF-8');
    $nomComplet = trim(($decl->etudiant->prenoms ?? '') . ' ' . ($decl->etudiant->nom ?? '')) ?: 'Étudiant inconnu';
    $semaine = optional($decl->semaine_debut)->isoFormat('D MMM YYYY');
@endphp
<div class="tv-decl-row" data-li-cle="{{ $decl->id }}">
    <div class="tv-decl-etudiant">
        <div class="tv-avatar">{{ $initial }}</div>
        <div>
            <div class="tv-decl-name">{{ $nomComplet }}</div>
            <div class="tv-decl-meta">
                {{ $decl->matiere->name ?? 'Matière supprimée' }}
                @if ($decl->matiere && $decl->matiere->uniteEnseignement)
                    · {{ $decl->matiere->uniteEnseignement->name }}
                @endif
            </div>
            @if ($decl->description)
                <div class="tv-decl-meta" style="margin-top:.35rem; max-width: 480px;">
                    <em>{{ \Illuminate\Support\Str::limit($decl->description, 200) }}</em>
                </div>
            @endif
        </div>
    </div>
    <div>
        <div class="tv-decl-meta">Semaine du</div>
        <div style="font-weight: 600; color: #1e293b; font-size: .88rem;">{{ $semaine ?: '—' }}</div>
    </div>
    <div class="tv-decl-heures">
        {{ number_format((float) $decl->heures, 2, ',', ' ') }}h
    </div>
    <div class="tv-actions">
        <form method="POST" action="{{ route('esbtp.tpe-validation.validate', $decl) }}" style="display:inline;">
            @csrf
            @method('PATCH')
            <button type="submit" class="tv-btn--validate" title="Valider">
                <i class="fas fa-check"></i>Valider
            </button>
        </form>
        <button type="button" class="tv-btn--reject"
                @click="openReject({{ $decl->id }}, '{{ route('esbtp.tpe-validation.reject', $decl) }}')"
                title="Rejeter">
            <i class="fas fa-times"></i>Rejeter
        </button>
    </div>
</div>
