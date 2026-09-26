{{-- Les quatre compteurs du jour. Rendus par la page et par index(?fragment=1)
     apres chaque coche. Le vert et l'orange portent un etat (reçue, retard). --}}
@php
    $_k = $compteurs;
    $_cartes = [
        ['valeur' => $_k['attendues'], 'libelle' => 'Attendues', 'repere' => $_k['a_recevoir'] > 0 ? $_k['a_recevoir'].' encore à recevoir' : 'personne d\'autre à recevoir', 'ton' => ''],
        ['valeur' => $_k['recues'], 'libelle' => 'Reçues', 'repere' => 'cochées au guichet', 'ton' => 'succes'],
        ['valeur' => $_k['en_retard'], 'libelle' => 'En retard', 'repere' => $_k['non_venues'] > 0 ? $_k['non_venues'].' non venue'.($_k['non_venues'] > 1 ? 's' : '').' à reprogrammer' : 'au-delà de la tolérance', 'ton' => $_k['en_retard'] > 0 ? 'alerte' : ''],
        ['valeur' => $_k['finalisees'], 'libelle' => 'Inscriptions finalisées', 'repere' => 'parmi les familles du jour', 'ton' => ''],
    ];
@endphp
@foreach($_cartes as $_c)
    <div class="adj-kpi {{ $_c['ton'] ? 'adj-kpi--'.$_c['ton'] : '' }}">
        <span class="adj-kpi-libelle">{{ $_c['libelle'] }}</span>
        <span class="adj-kpi-valeur">{{ number_format($_c['valeur'], 0, ',', ' ') }}</span>
        <span class="adj-kpi-repere">{{ $_c['repere'] }}</span>
    </div>
@endforeach
