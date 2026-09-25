{{-- Un jury : rendu par la page et par la suite chargee au defilement. --}}
<tr data-li-cle="{{ $j->id }}">
    <td style="font-weight:600;color:#0453cb;">{{ $j->libelle }}</td>
    <td>{{ optional($j->date_jury)->format('d/m/Y') ?? '—' }}</td>
    <td>{{ $j->parcours?->name ?? '—' }} @if($j->classe) · {{ $j->classe->name }}@endif</td>
    <td><span style="background:#f1f5f9;padding:.15rem .45rem;border-radius:5px;font-size:.72rem;color:#475569;font-weight:600;">{{ $j->membres->count() }}</span></td>
    <td><span class="juy-status juy-status--{{ $j->status }}">{{ str_replace('_',' ',$j->status) }}</span></td>
    <td>
        @if($j->pv_numero)
        <span style="font-family:'Courier New',monospace;font-size:.72rem;color:#0453cb;font-weight:700;">{{ $j->pv_numero }}</span>
        @else
        <span style="color:#94a3b8;font-size:.78rem;">—</span>
        @endif
    </td>
    <td>
        <a href="{{ route('esbtp.lmd.jurys.show', $j) }}" style="padding:.3rem .7rem;border-radius:6px;background:#f1f5f9;color:#0453cb;text-decoration:none;font-size:.78rem;font-weight:600;">
            <i class="fas fa-eye"></i>
        </a>
    </td>
</tr>
