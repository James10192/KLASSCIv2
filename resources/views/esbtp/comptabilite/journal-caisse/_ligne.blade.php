{{-- Une ligne du journal de caisse (bureau). Rendue par la page et par chaque
     tranche du defilement (ESBTPJournalCaisseController::index). --}}
<tr data-li-cle="{{ $p->id }}">
    <td>{{ optional($p->date_paiement)->format('d/m/Y') ?? '—' }}</td>
    <td><a href="{{ route('esbtp.paiements.show', $p->id) }}" class="jc-table-num">{{ $p->numero_recu ?: '#'.$p->id }}</a></td>
    <td>
        @if($p->inscription && $p->inscription->etudiant)
        <div>{{ trim(($p->inscription->etudiant->prenoms ?? '') . ' ' . ($p->inscription->etudiant->nom ?? '')) }}</div>
        <div class="jc-table-meta">{{ $p->inscription->etudiant->matricule ?? '—' }}{{ $p->inscription->classe ? ' · ' . $p->inscription->classe->name : '' }}</div>
        @else
        <span class="jc-table-meta">—</span>
        @endif
    </td>
    <td>{{ $p->fraisCategory->name ?? $p->motif ?? '—' }}</td>
    <td>{{ $p->mode_paiement ?? '—' }}</td>
    <td class="jc-table-amount {{ $p->status === 'rejeté' ? 'jc-table-amount--rejected' : ($p->status === 'en_attente' ? 'jc-table-amount--pending' : '') }}">
        {{ number_format((float) $p->montant, 0, ',', ' ') }}
    </td>
    <td>{{ $p->createdBy->name ?? '—' }}</td>
    <td>
        @if($p->validatedBy)
        {{ $p->validatedBy->name }}
        <div class="jc-table-meta">{{ optional($p->date_validation)->format('d/m/Y H:i') }}</div>
        @else
        <span class="jc-table-meta">—</span>
        @endif
    </td>
    <td><span class="jc-statut jc-statut--{{ $p->status }}">{{ ucfirst(str_replace('_', ' ', $p->status)) }}</span></td>
</tr>
