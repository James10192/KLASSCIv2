@php
    /** @var array $widget */
    $anneeEnCours = \App\Models\ESBTPAnneeUniversitaire::getCurrent();

    $duQuery = \App\Models\ESBTPInscription::query();
    $payeQuery = \App\Models\ESBTPPaiement::query();
    if ($anneeEnCours) {
        $duQuery->where('annee_universitaire_id', $anneeEnCours->id);
        $payeQuery->where('annee_universitaire_id', $anneeEnCours->id);
    }
    $totalDu = (float) $duQuery->sum(\DB::raw('COALESCE(montant_scolarite, 0) + COALESCE(frais_inscription, 0)'));
    $totalPaye = (float) $payeQuery->where(function ($q) {
        $q->whereIn('status', ['validé', 'valide'])
          ->orWhereIn('statut', ['validé', 'valide']);
    })->sum('montant');

    $balance = max(0.0, $totalDu - $totalPaye);
@endphp

<x-dw-widget
    :icon="$widget['icon'] ?? 'fa-balance-scale'"
    :label="$widget['label']"
    :value="number_format($balance, 0, ',', ' ')"
    unit="FCFA"
    :hint="'estimation année ' . ($anneeEnCours?->name ?? 'en cours')"
    :color="$widget['color'] ?? 'warning'"
/>
