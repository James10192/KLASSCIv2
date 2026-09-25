{{-- Bandeau de compteurs. Chaque compteur filtre la liste sur ce qu'il compte.
     Rendu aussi seul par index(?fragment=1). --}}
@php
    $_k = $compteurs;
    $_etat = $filtres['etat'];
    $_cartes = [
        ['etat' => 'a_traiter', 'valeur' => $_k['a_traiter'], 'libelle' => 'À traiter',
            'repere' => count($types) > 1 ? $_k['nouvelles'].' nouvelle'.($_k['nouvelles'] > 1 ? 's' : '').' · '.$_k['reinscriptions'].' réinscription'.($_k['reinscriptions'] > 1 ? 's' : '') : 'demandes ouvertes'],
        ['etat' => 'rendez_vous', 'valeur' => $_k['rendez_vous'], 'libelle' => 'Rendez-vous fixé',
            'repere' => $_k['rendez_vous_aujourdhui'] > 0 ? 'dont '.$_k['rendez_vous_aujourdhui']." attendue".($_k['rendez_vous_aujourdhui'] > 1 ? 's' : '')." aujourd'hui" : "personne d'attendu aujourd'hui"],
        ['etat' => 'recues', 'valeur' => $_k['recues'], 'libelle' => 'Reçues au guichet', 'repere' => 'décision en attente'],
        ['etat' => 'inscrites', 'valeur' => $_k['inscrites_semaine'], 'libelle' => 'Inscrites cette semaine',
            'repere' => $_k['inscrites_semaine_passee'].' la semaine dernière'],
        ['etat' => 'rejetees', 'valeur' => $_k['rejetees_semaine'], 'libelle' => 'Rejetées cette semaine', 'repere' => 'créneau libéré, motif gardé'],
    ];
@endphp
@foreach($_cartes as $_c)
    <button type="button" class="dmi-kpi {{ $_etat === $_c['etat'] ? 'is-actif' : '' }}" data-dmi-etat="{{ $_c['etat'] }}"
            aria-pressed="{{ $_etat === $_c['etat'] ? 'true' : 'false' }}" title="Afficher : {{ mb_strtolower($_c['libelle'], 'UTF-8') }}">
        <span class="dmi-kpi-valeur">{{ number_format($_c['valeur'], 0, ',', ' ') }}</span>
        <span class="dmi-kpi-libelle">{{ $_c['libelle'] }}</span>
        <span class="dmi-kpi-repere">{{ $_c['repere'] }}</span>
    </button>
@endforeach
