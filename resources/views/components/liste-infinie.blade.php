{{--
    Bas de liste a defilement infini. Se place juste apres la table (ou la grille).

    Rendu serveur de la premiere tranche ; le script public/js/liste-infinie.js
    charge la suite en rappelant la meme adresse avec les memes filtres, `page`
    et `mode=rows`, et ajoute les lignes dans `cible`. Cote controleur :
    App\Support\ListeInfinie::demandee() / reponse().

    Props :
      - paginateur : le paginateur de la premiere tranche
      - cible      : selecteur CSS du conteneur ou ajouter les lignes (un <tbody>)
      - libelle    : le nom des lignes au pluriel, pour le compteur (« inscriptions »)
      - url        : l'adresse a rappeler, par defaut celle de la page

    Les filtres transportes sont ceux de la requete qui a rendu cette tranche,
    pas ceux du formulaire : un filtre modifie sans etre applique ne doit pas
    se glisser dans la suite d'une liste qui ne le montre pas.
--}}
@props([
    'paginateur',
    'cible',
    'libelle' => 'éléments',
    'url' => null,
])

@php
    $_pagination = \App\Support\ListeInfinie::pagination($paginateur);
    $_query = http_build_query(request()->except(['page', 'mode']));
@endphp

<div {{ $attributes->merge(['class' => 'li-bas']) }}
     data-liste-infinie
     data-url="{{ $url ?? request()->url() }}"
     data-query="{{ $_query }}"
     data-cible="{{ $cible }}"
     data-libelle="{{ $libelle }}"
     data-page-suivante="{{ $_pagination['next_page'] ?? '' }}"
     data-total="{{ $_pagination['total'] ?? '' }}"
     data-affiches="{{ $_pagination['affiches'] }}">
    <span class="li-compteur" aria-live="polite"></span>
    <button type="button" class="li-plus" data-li-plus hidden>Charger la suite</button>
</div>
