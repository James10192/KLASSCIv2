{{-- Une session de rattrapage : rendu par la page et par la suite chargee au defilement. --}}
<tr data-li-cle="{{ $s->id }}">
    <td style="font-weight:600;color:#0453cb;">{{ $s->libelle }}</td>
    <td><span class="rtp-chip rtp-chip--{{ $s->type }}">{{ $s->type }}</span></td>
    <td>{{ $s->parcours->name ?? '—' }}</td>
    <td>{{ $s->semestre ? 'S'.$s->semestre : '—' }}</td>
    <td>{{ optional($s->date_debut)->format('d/m/Y') }} — {{ optional($s->date_fin)->format('d/m/Y') }}</td>
    <td><span class="rtp-status rtp-status--{{ $s->status }}">{{ str_replace('_',' ',$s->status) }}</span></td>
    <td>
        <a href="{{ route('esbtp.lmd.rattrapage.show', $s) }}" style="padding:.3rem .7rem;border-radius:6px;background:#f1f5f9;color:#0453cb;text-decoration:none;font-size:.78rem;font-weight:600;">
            <i class="fas fa-eye"></i>
        </a>
    </td>
</tr>
