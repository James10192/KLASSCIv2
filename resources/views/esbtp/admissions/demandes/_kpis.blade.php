{{-- Bandeau de compteurs. Chaque compteur filtre la liste sur ce qu'il compte.
     Ils ne dependent d'aucun filtre : rendus au chargement, puis seulement
     apres une decision (index?fragment=1&compteurs=1). L'etat actif suit
     les filtres cote Alpine. --}}
@php
    $_k = $compteurs;
    $_cartes = [
        ['etat' => 'a_traiter', 'valeur' => $_k['a_traiter'], 'libelle' => 'À traiter',
            'repere' => count($types) > 1 ? $_k['nouvelles'].' nouvelle'.($_k['nouvelles'] > 1 ? 's' : '').' · '.$_k['reinscriptions'].' réinscription'.($_k['reinscriptions'] > 1 ? 's' : '') : 'demandes ouvertes'],
        ['etat' => 'rendez_vous', 'valeur' => $_k['rendez_vous'], 'libelle' => 'Rendez-vous fixé',
            'repere' => $_k['attendues_aujourdhui'] > 0 ? $_k['attendues_aujourdhui']." attendue".($_k['attendues_aujourdhui'] > 1 ? 's' : '')." à l'accueil aujourd'hui" : "personne d'attendu aujourd'hui"],
        ['etat' => 'recues', 'valeur' => $_k['recues'], 'libelle' => 'Reçues au guichet', 'repere' => 'décision en attente'],
        ['etat' => 'inscrites', 'valeur' => $_k['inscrites_semaine'], 'libelle' => 'Inscrites cette semaine',
            'repere' => $_k['inscrites_semaine_passee'].' la semaine dernière'],
        ['etat' => 'rejetees', 'valeur' => $_k['rejetees_semaine'], 'libelle' => 'Rejetées cette semaine', 'repere' => 'créneau libéré, motif gardé'],
    ];
@endphp
@foreach($_cartes as $_c)
    <button type="button" class="dmi-kpi" data-dmi-etat="{{ $_c['etat'] }}"
            :class="filtres.etat === '{{ $_c['etat'] }}' ? 'is-actif' : ''" :aria-pressed="filtres.etat === '{{ $_c['etat'] }}'"
            title="Afficher : {{ mb_strtolower($_c['libelle'], 'UTF-8') }}">
        <span class="dmi-kpi-valeur">{{ number_format($_c['valeur'], 0, ',', ' ') }}</span>
        <span class="dmi-kpi-libelle">{{ $_c['libelle'] }}</span>
        <span class="dmi-kpi-repere">{{ $_c['repere'] }}</span>
    </button>
@endforeach
