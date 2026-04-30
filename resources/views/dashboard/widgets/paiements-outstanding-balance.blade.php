@php
    /**
     * Widget : Solde restant à recouvrer (estimation simple)
     * Calcul : SOMME(montant_scolarite + frais_inscription) - SOMME(paiements validés)
     * sur l'année universitaire en cours. Utilisé pour donner un ordre de grandeur ;
     * la situation financière exacte est dans /esbtp/comptabilite.
     */
    $anneeEnCours = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();

    $duToken = \App\Models\ESBTPInscription::query();
    $paye = \App\Models\ESBTPPaiement::query();
    if ($anneeEnCours) {
        $duToken->where('annee_universitaire_id', $anneeEnCours->id);
        $paye->where('annee_universitaire_id', $anneeEnCours->id);
    }
    $totalDu = (float) $duToken->sum(\DB::raw('COALESCE(montant_scolarite, 0) + COALESCE(frais_inscription, 0)'));
    $totalPaye = (float) $paye->where(function ($q) {
        $q->where('status', 'validé')
          ->orWhere('status', 'valide')
          ->orWhere('statut', 'validé')
          ->orWhere('statut', 'valide');
    })->sum('montant');

    $balance = max(0.0, $totalDu - $totalPaye);
    $color = $widget['color'] ?? 'warning';
@endphp

<div class="dw-widget dw-widget--{{ $color }}">
    <div class="dw-widget-icon">
        <i class="fas {{ $widget['icon'] ?? 'fa-balance-scale' }}"></i>
    </div>
    <div class="dw-widget-body">
        <div class="dw-widget-label">{{ $widget['label'] }}</div>
        <div class="dw-widget-value">{{ number_format($balance, 0, ',', ' ') }} <span class="dw-widget-unit">FCFA</span></div>
        <div class="dw-widget-hint">
            estimation année {{ $anneeEnCours ? $anneeEnCours->name : 'en cours' }}
        </div>
    </div>
</div>
