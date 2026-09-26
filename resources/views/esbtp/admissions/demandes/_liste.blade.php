{{-- La liste filtree. Rendue par la page et par index(?fragment=1). --}}
@php
    $_vues = [
        'a_traiter' => 'Dossiers ouverts',
        'rendez_vous' => 'Avec un rendez-vous à venir',
        'recues' => 'Reçus au guichet, décision en attente',
        'inscrites' => 'Inscrits cette semaine',
        'rejetees' => 'Rejetés cette semaine',
        'toutes' => "Tout l'historique",
    ];
    $_etape = \App\Domain\Admissions\EtapeDuDossier::depuis($filtres['etape'] ?: null);
    $_titre = $_etape ? 'Étape : '.$_etape->libelle() : $_vues[$filtres['etat']];
    $_filtresActifs = $filtres['q'] !== '' || $filtres['sans_rdv'] || $filtres['contact'] || $filtres['type'] !== '' || $_etape !== null;
@endphp
<div class="dmi-etat-actif">
    <span><strong>{{ $_titre }}</strong> · {{ $demandes->total() }} dossier{{ $demandes->total() > 1 ? 's' : '' }}</span>
    @if($_etape !== null)
        <button type="button" class="dmi-lien" data-dmi-etape="">Tous les dossiers ouverts</button>
    @elseif($filtres['etat'] !== 'toutes')
        <button type="button" class="dmi-lien" data-dmi-etat="toutes">Tout l'historique</button>
    @else
        <button type="button" class="dmi-lien" data-dmi-etat="a_traiter">Revenir aux dossiers ouverts</button>
    @endif
</div>
@if($demandes->total() === 0)
    <div class="dmi-vide">
        @if($_filtresActifs)
            <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
            <h3>Aucun dossier ne correspond</h3>
            <p>Essayez un autre nom, un téléphone ou une référence, choisissez une autre étape, ou cherchez dans tout l'historique.</p>
            <button type="button" class="dmi-btn dmi-btn--ghost" data-dmi-effacer>Effacer les filtres</button>
        @elseif($filtres['etat'] === 'a_traiter')
            <i class="fas fa-circle-check" aria-hidden="true"></i>
            <h3>Rien en attente</h3>
            <p>Tous les dossiers ont reçu une décision. Contrôlé à {{ now()->format('H:i') }}.</p>
        @else
            <i class="fas fa-inbox" aria-hidden="true"></i>
            <h3>Aucun dossier dans cette vue</h3>
            <p>Les nouveaux dossiers arrivent ici dès qu'une famille dépose une demande en ligne.</p>
        @endif
        @can('inscriptions.create')
            <a class="dmi-btn dmi-btn--ghost" href="{{ route('esbtp.inscriptions.create') }}"><i class="fas fa-user-plus"></i>Inscription directe au guichet</a>
        @endcan
    </div>
@else
    <div class="dmi-entetes" aria-hidden="true">
        <span>Personne</span><span>Étape</span><span>Rendez-vous</span><span>Convocation</span><span style="text-align:right">Suite</span>
    </div>
    <div id="dmi-lignes">
        @foreach($demandes as $d)
            @include('esbtp.admissions.demandes._ligne', ['d' => $d])
        @endforeach
    </div>
    <x-liste-infinie :paginateur="$demandes" cible="#dmi-lignes" libelle="dossiers" :url="route('esbtp.demandes.index')" />
@endif
