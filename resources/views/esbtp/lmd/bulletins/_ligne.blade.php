{{-- Un bulletin LMD : rendu par la page et par la suite chargee au defilement. --}}
@php
    $moy = $b->moyenne_generale ?? 0;
    $moySlug = app(\App\Services\AppreciationScaleService::class)->classificationFor((float) $moy, 'lmd', '')['slug'];
    $moyClass = in_array($moySlug, ['excellent', 'tres-bien', 'bien'], true)
        ? 'lb-moy--excellent'
        : ($moySlug === 'passable' || $moySlug === 'assez-bien' ? 'lb-moy--good' : 'lb-moy--fail');
    $mention = $b->mention_generale;
    $credCap = $b->credits_capitalises ?? 0;
    $credTot = $b->credits_totaux ?? 0;
    $credPct = $credTot > 0 ? round(($credCap / $credTot) * 100) : 0;
    $credFillClass = $credPct >= 100 ? 'lb-credits-fill--full' : ($credPct >= 50 ? 'lb-credits-fill--mid' : 'lb-credits-fill--low');
@endphp
<tr data-li-cle="{{ $b->id }}">
    <td>
        <span class="lb-matricule">{{ $b->etudiant->matricule ?? '—' }}</span>
    </td>
    <td>
        <div class="lb-student-name">
            {{ $b->etudiant->nom ?? '' }} {{ $b->etudiant->prenoms ?? $b->etudiant->prenom ?? '' }}
        </div>
    </td>
    <td style="color:#64748b;">{{ $b->classe->name ?? '—' }}</td>
    <td style="text-align:center;">
        <span class="lb-semestre-tag">S{{ $b->semestre }}</span>
    </td>
    <td style="text-align:center;">
        <div class="lb-moyenne">
            <span class="lb-moyenne-value {{ $moyClass }}">{{ number_format($moy, 2) }}</span>
            @if($mention)
                <span class="lb-moyenne-mention {{ $moyClass }}">{{ $mention }}</span>
            @endif
        </div>
    </td>
    <td style="text-align:center;">
        <div class="lb-credits">
            <div class="lb-credits-text">{{ $credCap }} <span>/ {{ $credTot }}</span></div>
            <div class="lb-credits-bar">
                <div class="lb-credits-fill {{ $credFillClass }}" style="width:{{ $credPct }}%;"></div>
            </div>
        </div>
    </td>
    <td style="text-align:center;">
        <div class="lb-rang">
            <span class="lb-rang-value">{{ $b->rang ?? '—' }}</span>
            @if($b->effectif)
                <span class="lb-rang-total">/ {{ $b->effectif }}</span>
            @endif
        </div>
    </td>
    <td style="text-align:center;">
        @if($b->is_published)
            <span class="lb-badge lb-badge--published">
                <span class="lb-badge-dot"></span>Publié
            </span>
        @else
            <span class="lb-badge lb-badge--draft">
                <span class="lb-badge-dot"></span>Brouillon
            </span>
        @endif
    </td>
    <td style="text-align:center;">
        <div class="lb-actions">
            <a href="{{ route('esbtp.lmd.bulletins.show', $b) }}" class="lb-act lb-act--view" title="Détails (web)">
                <i class="fas fa-list-ul"></i>
            </a>
            <a href="{{ route('esbtp.lmd.bulletins.pdf-preview', $b) }}" class="lb-act lb-act--view" title="Aperçu PDF" target="_blank">
                <i class="fas fa-eye"></i>
            </a>
            <a href="{{ route('esbtp.lmd.bulletins.pdf', $b) }}" class="lb-act lb-act--pdf" title="Télécharger PDF">
                <i class="fas fa-download"></i>
            </a>
            <form method="POST" action="{{ route('esbtp.lmd.bulletins.toggle-publication', $b) }}" style="display:inline;">
                @csrf
                @method('PUT')
                <button type="submit" class="lb-act lb-act--publish" title="{{ $b->is_published ? 'Dépublier' : 'Publier' }}">
                    <i class="fas fa-{{ $b->is_published ? 'eye-slash' : 'check' }}"></i>
                </button>
            </form>
            <form method="POST" action="{{ route('esbtp.lmd.bulletins.destroy', $b) }}" style="display:inline;" onsubmit="return confirm('Supprimer ce bulletin et ses résultats ?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="lb-act lb-act--delete" title="Supprimer">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </form>
        </div>
    </td>
</tr>
