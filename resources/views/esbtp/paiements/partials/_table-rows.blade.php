{{-- Partial rows-only for infinite scroll AJAX append.
     Renvoie une suite de <tr data-paiement-id="…"> + modaux associés. --}}
@php
    $showCreatorColumn = $showCreatorColumn ?? (auth()->user()?->can('paiements.view') ?? false);
@endphp
@foreach($paiements as $paiement)
    @include('esbtp.paiements.partials.ligne-paiement', ['paiement' => $paiement, 'showCreatorColumn' => $showCreatorColumn])
@endforeach
