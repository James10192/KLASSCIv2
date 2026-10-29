@php
    $etatTexte = \App\Http\Controllers\ESBTPAnnonceCourrielController::STATUTS[$ligne->status] ?? 'Inconnu';
    $couleur = match($ligne->status) {
        'delivered' => 'success', 'failed' => 'danger',
        'delayed', 'retrying' => 'warning', default => 'secondary',
    };
@endphp
<tr data-li-cle="{{ $ligne->id }}">
    <td class="text-break">{{ $ligne->email }}</td>
    <td><span class="badge bg-{{ $couleur }}">{{ $etatTexte }}</span></td>
    <td>{{ (int) $ligne->attempts }}</td>
    <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($ligne->updated_at)->format('d/m/Y H:i') }}</td>
    <td><small>{{ $ligne->error_code ?: '—' }}</small></td>
</tr>
