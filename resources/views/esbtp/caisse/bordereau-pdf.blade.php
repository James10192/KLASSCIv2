@php
    $pdfCfg = \App\Helpers\SettingsHelper::getPdfSettings();
    $accent = $pdfCfg['primary_color'] ?? '#0453cb';
    $jour = $session->business_date;
@endphp
<x-pdf-document
    title="Bordereau journalier"
    :subtitle="'Caisse · '.$jour->isoFormat('dddd D MMMM YYYY')"
    orientation="portrait">
<style>
    .bd-hero { background: {{ $accent }}; color:#fff; padding:12px 14px; border-radius:8px; margin-bottom:12px; }
    .bd-hero strong { font-size:18pt; display:block; }
    .bd-table { width:100%; border-collapse:collapse; font-size:8.5pt; }
    .bd-table th { background: {{ $accent }}; color:#fff; padding:5px 6px; text-align:left; }
    .bd-table td { padding:4px 6px; border-bottom:1px solid #e2e8f0; }
    .bd-sign { margin-top:28px; width:100%; }
    .bd-sign td { width:50%; text-align:center; padding-top:36px; font-size:9pt; }
</style>

<div class="bd-hero">
    <div>Total encaissé ce jour</div>
    <strong>{{ number_format($aggregat['total'], 0, ',', ' ') }} FCFA</strong>
    <div>{{ $aggregat['count'] }} versement(s) validé(s) · {{ $aggregat['annules'] }} annulé(s)</div>
    <div>Caissier : {{ $caissier->name }} · Édité le {{ now()->format('d/m/Y H:i') }}</div>
</div>

<h4>Récapitulatif par méthode</h4>
<table class="bd-table">
    <thead><tr><th>Mode</th><th>Nombre</th><th>Montant</th></tr></thead>
    <tbody>
    @forelse($aggregat['par_mode'] as $mode)
        <tr>
            <td>{{ $mode['label'] }}</td>
            <td>{{ $mode['count'] }}</td>
            <td>{{ number_format($mode['total'], 0, ',', ' ') }} F</td>
        </tr>
    @empty
        <tr><td colspan="3">Aucun encaissement validé.</td></tr>
    @endforelse
    </tbody>
</table>

<h4>Détail des versements</h4>
<table class="bd-table">
    <thead><tr><th>Heure</th><th>Étudiant</th><th>Mode</th><th>Statut</th><th>Montant</th></tr></thead>
    <tbody>
    @foreach($paiements as $p)
        <tr>
            <td>{{ $p->created_at?->format('H:i') }}</td>
            <td>{{ trim(($p->etudiant->nom ?? '').' '.($p->etudiant->prenoms ?? '')) ?: '—' }}</td>
            <td>{{ $p->mode_paiement }}</td>
            <td>{{ $p->status }}</td>
            <td>{{ number_format((float)$p->montant, 0, ',', ' ') }} F</td>
        </tr>
    @endforeach
    </tbody>
</table>

@if($session->isLocked())
<p>Espèces théoriques : {{ number_format((float)$session->expected_amount, 0, ',', ' ') }} F
· Compté : {{ $session->counted_amount !== null ? number_format((float)$session->counted_amount, 0, ',', ' ').' F' : 'inconnu' }}
· Écart : {{ $session->variance !== null ? number_format((float)$session->variance, 0, ',', ' ').' F' : 'inconnu' }}</p>
@endif

<table class="bd-sign">
    <tr>
        <td>Le Caissier<br><strong>{{ $caissier->name }}</strong></td>
        <td>La Comptabilité</td>
    </tr>
</table>
<p style="font-size:8pt;color:#64748b;margin-top:16px;">À conserver pour la comptabilité de l'établissement.</p>
</x-pdf-document>
