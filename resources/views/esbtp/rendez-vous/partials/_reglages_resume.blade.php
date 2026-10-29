{{-- Ligne de resume des reglages, rafraichie apres chaque enregistrement. --}}
@php $_ouvertResume = $rdv->enabled(); @endphp
<span class="rdv-section-icon"><i class="fas fa-sliders"></i></span>
<span class="rdv-reglages-titre">
    <strong>Réglages des créneaux</strong>
    <span>
        @if($debit)
            {{ $debit['duree'] }} min · {{ $debit['capacite'] }} places · {{ $debit['creneaux_par_jour'] }} créneaux par jour
        @else
            À compléter avant de générer les créneaux
        @endif
    </span>
</span>
<span class="rdv-etat rdv-etat--{{ $_ouvertResume ? 'ouvert' : 'ferme' }}">{{ $_ouvertResume ? 'Ouvert aux familles' : 'Fermé aux familles' }}</span>
<i class="fas fa-chevron-down rdv-reglages-caret"></i>
