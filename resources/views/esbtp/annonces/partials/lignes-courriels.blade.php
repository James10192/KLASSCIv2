@forelse($destinataires as $livraison)
    @php
        $etatTexte = match($livraison->status) {
            'delivered' => 'Délivré', 'delayed' => 'Retardé',
            'submitted' => 'Soumis', 'failed' => 'Échec',
            'retrying' => 'Nouvelle tentative', 'processing' => 'En cours',
            'awaiting_dispatch' => 'À confirmer', default => 'En attente'
        };
        $couleur = match($livraison->status) {
            'delivered' => 'success', 'failed' => 'danger',
            'delayed', 'retrying' => 'warning', default => 'secondary'
        };
    @endphp
    <tr>
        <td class="text-break">{{ $livraison->email }}</td>
        <td><span class="badge bg-{{ $couleur }}">{{ $etatTexte }}</span></td>
        <td>{{ (int) $livraison->attempts }}</td>
        <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($livraison->updated_at)->format('d/m/Y H:i') }}</td>
        <td class="text-break"><small>{{ $livraison->error_code ?: '—' }}</small></td>
    </tr>
@empty
    <tr><td colspan="5" class="text-muted py-3 text-center">
        Aucune adresse pour ce filtre. Vérifiez le traitement de la file.
    </td></tr>
@endforelse
