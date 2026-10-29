{{-- La liste filtree. Rendue par la page et par index(?fragment=1). --}}
@php
    $_vues = [
        'a_traiter' => 'Demandes à traiter',
        'rendez_vous' => 'Avec un rendez-vous à venir',
        'recues' => 'Reçues au guichet, décision en attente',
        'inscrites' => 'Inscrites cette semaine',
        'rejetees' => 'Rejetées cette semaine',
        'toutes' => "Tout l'historique",
    ];
    $_filtresActifs = $filtres['q'] !== '' || $filtres['sans_rdv'] || $filtres['contact'] || $filtres['type'] !== '';
@endphp
<div class="dmi-etat-actif">
    <span><strong>{{ $_vues[$filtres['etat']] }}</strong> · {{ $demandes->total() }} dossier{{ $demandes->total() > 1 ? 's' : '' }}</span>
    @if($filtres['etat'] !== 'toutes')
        <button type="button" class="dmi-lien" data-dmi-etat="toutes">Tout l'historique</button>
    @else
        <button type="button" class="dmi-lien" data-dmi-etat="a_traiter">Revenir aux demandes à traiter</button>
    @endif
</div>
@if($demandes->total() === 0)
    <div class="dmi-vide">
        @if($_filtresActifs)
            <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
            <h3>Aucune demande ne correspond</h3>
            <p>Essayez un autre nom, un téléphone ou une référence, ou cherchez dans tout l'historique.</p>
            <button type="button" class="dmi-btn dmi-btn--ghost" data-dmi-effacer>Effacer les filtres</button>
        @elseif($filtres['etat'] === 'a_traiter')
            <i class="fas fa-circle-check" aria-hidden="true"></i>
            <h3>Rien en attente</h3>
            <p>Toutes les demandes ont reçu une décision. Contrôlé à {{ now()->format('H:i') }}.</p>
        @else
            <i class="fas fa-inbox" aria-hidden="true"></i>
            <h3>Aucune demande dans cette vue</h3>
            <p>Les nouvelles demandes arrivent ici dès qu'une famille dépose un dossier en ligne.</p>
        @endif
        @can('inscriptions.create')
            <a class="dmi-btn dmi-btn--ghost" href="{{ route('esbtp.inscriptions.create') }}"><i class="fas fa-user-plus"></i>Inscription directe au guichet</a>
        @endcan
    </div>
@else
    <div class="dmi-entetes" aria-hidden="true">
        <span>Demande</span><span>Parcours demandé</span><span>Rendez-vous</span><span style="text-align:right">Prochaine étape</span>
    </div>
    <div id="dmi-lignes">
        @foreach($demandes as $d)
            @include('esbtp.admissions.demandes._ligne', ['d' => $d])
        @endforeach
    </div>
    <x-liste-infinie :paginateur="$demandes" cible="#dmi-lignes" libelle="demandes" :url="route('esbtp.demandes.index')" />
@endif
