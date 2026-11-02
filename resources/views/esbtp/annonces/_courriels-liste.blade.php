<div class="table-responsive">
    <table id="aps-email-table" class="table table-sm align-middle" aria-label="Statut de diffusion des courriels">
        <colgroup><col class="aps-col-address"><col class="aps-col-state"><col class="aps-col-attempts"><col class="aps-col-updated"><col class="aps-col-code"></colgroup>
        <thead><tr>
            <th scope="col">Destinataire</th>
            <th scope="col">État</th>
            <th scope="col">Tentatives</th>
            <th scope="col">Dernière mise à jour</th>
            <th scope="col">Motif / code</th>
        </tr></thead>
        <tbody>
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
            <tr data-livraison-id="{{ $livraison->id }}">
                <td class="aps-delivery-address">{{ $livraison->email }}</td>
                <td><span class="badge bg-{{ $couleur }}">{{ $etatTexte }}</span></td>
                <td class="aps-delivery-attempts">{{ (int) $livraison->attempts }}</td>
                <td class="aps-delivery-date">{{ \Illuminate\Support\Carbon::parse($livraison->updated_at)->format('d/m/Y') }} <span>{{ \Illuminate\Support\Carbon::parse($livraison->updated_at)->format('H:i') }}</span></td>
                <td class="aps-delivery-code"><small>{{ $livraison->error_code === 'local_rate_limit' ? 'Quota local temporaire' : ($livraison->error_code ?: '—') }}</small></td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-muted py-3 text-center">
                Aucune adresse pour ce filtre. Vérifiez le traitement de la file.
            </td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{-- La pagination classique reste un repli sans JS ; le JS la cache après initialisation. --}}
<div id="aps-email-pages" class="aps-email-page-links mt-2">{{ $destinataires->links() }}</div>
<div class="d-flex justify-content-center mt-2">
    <button type="button" id="aps-email-more" class="btn btn-outline-primary btn-sm" hidden>Charger plus de destinataires</button>
</div>
<p id="aps-email-ajax-status" class="small text-muted mt-2" role="status" aria-live="polite"></p>
