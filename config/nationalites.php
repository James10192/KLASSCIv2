<?php

/**
 * Les nationalites que les formulaires proposent.
 *
 * Extrait d'une vue Blade ou chaque entree etait recopiee a la main avec sa
 * comparaison de selection. Un vocabulaire ferme est de la donnee : le mettre
 * ici permet a la vue de le parcourir, et au portail public de le publier —
 * ce qui compte, car un candidat qui tape « ivoirienne » en clair produirait
 * une valeur que la liste de l'ecole ne reconnaitrait pas, et un champ
 * obligatoire qui retombe silencieusement a vide.
 *
 * L'ordre est celui de la vue d'origine : la Cote d'Ivoire en tete, puis les
 * groupes par region. Les cles sont les valeurs stockees, accents compris.
 *
 * CERTAINES ENTREES FIGURENT SOUS PLUSIEURS GROUPES, A DESSEIN : « Sud-
 * Africaine » se trouve sous Afrique anglophone et sous Afrique australe,
 * parce qu'on la cherche des deux cotes. Ce ne sont pas des doublons a
 * nettoyer — les supprimer casserait la navigation du select de l'ecole.
 * App\Support\Nationalites les aplatit et les deduplique la ou une liste
 * sans entetes en ferait des repetitions. Aucun decompte ici : la prochaine
 * modification de ce fichier sera precisement d'y ajouter une entree.
 */
return [

    [
        'titre' => null,
        'entrees' => [
            'Ivoirienne' => '🇨🇮',
        ],
    ],

    [
        'titre' => 'Afrique francophone',
        'entrees' => [
            'Algérienne' => '🇩🇿',
            'Angolaise' => '🇦🇴',
            'Béninoise' => '🇧🇯',
            'Botswanaise' => '🇧🇼',
            'Burkinabè' => '🇧🇫',
            'Burundaise' => '🇧🇮',
            'Camerounaise' => '🇨🇲',
            'Cap-verdienne' => '🇨🇻',
            'Centrafricaine' => '🇨🇫',
            'Comorienne' => '🇰🇲',
            'Congolaise (RDC)' => '🇨🇩',
            'Congolaise (RC)' => '🇨🇬',
            'Djiboutienne' => '🇩🇯',
            'Égyptienne' => '🇪🇬',
            'Érythréenne' => '🇪🇷',
            'Éthiopienne' => '🇪🇹',
            'Gabonaise' => '🇬🇦',
            'Gambienne' => '🇬🇲',
            'Ghanéenne' => '🇬🇭',
            'Guinéenne' => '🇬🇳',
            'Bissau-Guinéenne' => '🇬🇼',
            'Équato-Guinéenne' => '🇬🇶',
            'Kényane' => '🇰🇪',
            'Lesothane' => '🇱🇸',
            'Libérienne' => '🇱🇷',
            'Libyenne' => '🇱🇾',
            'Malgache' => '🇲🇬',
            'Malawite' => '🇲🇼',
            'Malienne' => '🇲🇱',
            'Marocaine' => '🇲🇦',
            'Mauritanienne' => '🇲🇷',
            'Mozambicaine' => '🇲🇿',
            'Namibienne' => '🇳🇦',
            'Nigérienne' => '🇳🇪',
            'Nigériane' => '🇳🇬',
            'Rwandaise' => '🇷🇼',
            'Sénégalaise' => '🇸🇳',
            'Seychelloise' => '🇸🇨',
            'Sierra-Léonaise' => '🇸🇱',
            'Somalienne' => '🇸🇴',
            'Sud-Africaine' => '🇿🇦',
            'Soudanaise' => '🇸🇩',
            'Sud-Soudanaise' => '🇸🇸',
            'Tanzanienne' => '🇹🇿',
            'Tchadienne' => '🇹🇩',
            'Togolaise' => '🇹🇬',
            'Tunisienne' => '🇹🇳',
            'Zambienne' => '🇿🇲',
            'Zimbabwéenne' => '🇿🇼',
        ],
    ],

    [
        'titre' => 'Afrique anglophone',
        'entrees' => [
            'Sud-Africaine' => '🇿🇦',
            'Ghanéenne' => '🇬🇭',
            'Sierra-Léonaise' => '🇸🇱',
            'Libérienne' => '🇱🇷',
            'Nigériane' => '🇳🇬',
            'Gambienne' => '🇬🇲',
            'Kényane' => '🇰🇪',
            'Ougandaise' => '🇺🇬',
            'Tanzanienne' => '🇹🇿',
            'Botswanaise' => '🇧🇼',
            'Namibienne' => '🇳🇦',
        ],
    ],

    [
        'titre' => 'Afrique lusophone',
        'entrees' => [
            'Angolaise' => '🇦🇴',
            'Mozambicaine' => '🇲🇿',
            'Cap-verdienne' => '🇨🇻',
            'Bissau-Guinéenne' => '🇬🇼',
            'Santoméenne' => '🇸🇹',
        ],
    ],

    [
        'titre' => 'Afrique arabophone',
        'entrees' => [
            'Marocaine' => '🇲🇦',
            'Algérienne' => '🇩🇿',
            'Tunisienne' => '🇹🇳',
            'Libyenne' => '🇱🇾',
            'Égyptienne' => '🇪🇬',
            'Mauritanienne' => '🇲🇷',
            'Soudanaise' => '🇸🇩',
        ],
    ],

    [
        'titre' => 'Afrique centrale',
        'entrees' => [
            'Gabonaise' => '🇬🇦',
            'Congolaise (RC)' => '🇨🇬',
            'Congolaise (RDC)' => '🇨🇩',
            'Centrafricaine' => '🇨🇫',
            'Équato-Guinéenne' => '🇬🇶',
        ],
    ],

    [
        'titre' => 'Afrique australe',
        'entrees' => [
            'Sud-Africaine' => '🇿🇦',
            'Namibienne' => '🇳🇦',
            'Botswanaise' => '🇧🇼',
            'Zimbabwéenne' => '🇿🇼',
            'Zambienne' => '🇿🇲',
            'Malawite' => '🇲🇼',
        ],
    ],

    [
        'titre' => 'Afrique de l\'Est',
        'entrees' => [
            'Tanzanienne' => '🇹🇿',
            'Kényane' => '🇰🇪',
            'Ougandaise' => '🇺🇬',
            'Rwandaise' => '🇷🇼',
            'Burundaise' => '🇧🇮',
            'Éthiopienne' => '🇪🇹',
            'Somalienne' => '🇸🇴',
        ],
    ],

    [
        'titre' => 'Afrique de l\'Ouest',
        'entrees' => [
            'Ivoirienne' => '🇨🇮',
            'Burkinabè' => '🇧🇫',
            'Ghanéenne' => '🇬🇭',
            'Malienne' => '🇲🇱',
            'Nigérienne' => '🇳🇪',
            'Nigériane' => '🇳🇬',
            'Sénégalaise' => '🇸🇳',
            'Togolaise' => '🇹🇬',
            'Béninoise' => '🇧🇯',
            'Guinéenne' => '🇬🇳',
            'Cap-verdienne' => '🇨🇻',
            'Sierra-Léonaise' => '🇸🇱',
            'Libérienne' => '🇱🇷',
        ],
    ],

    [
        'titre' => 'Europe',
        'entrees' => [
            'Française' => '🇫🇷',
            'Belge' => '🇧🇪',
            'Suisse' => '🇨🇭',
            'Allemande' => '🇩🇪',
            'Italienne' => '🇮🇹',
            'Espagnole' => '🇪🇸',
            'Portugaise' => '🇵🇹',
            'Grecque' => '🇬🇷',
            'Turque' => '🇹🇷',
            'Polonaise' => '🇵🇱',
            'Tchèque' => '🇨🇿',
            'Hongroise' => '🇭🇺',
            'Roumaine' => '🇷🇴',
            'Bulgare' => '🇧🇬',
            'Croate' => '🇭🇷',
            'Serbe' => '🇷🇸',
            'Slovène' => '🇸🇮',
            'Slovaque' => '🇸🇰',
            'Ukrainienne' => '🇺🇦',
        ],
    ],

    [
        'titre' => 'Amériques',
        'entrees' => [
            'Canadienne' => '🇨🇦',
            'Américaine' => '🇺🇸',
            'Brésilienne' => '🇧🇷',
            'Haïtienne' => '🇭🇹',
            'Dominicaine' => '🇩🇴',
            'Mexicaine' => '🇲🇽',
            'Colombienne' => '🇨🇴',
        ],
    ],

    [
        'titre' => 'Asie',
        'entrees' => [
            'Libanaise' => '🇱🇧',
            'Saoudienne' => '🇸🇦',
            'Iranienne' => '🇮🇷',
            'Israélienne' => '🇮🇱',
            'Indienne' => '🇮🇳',
            'Chinoise' => '🇨🇳',
            'Japonaise' => '🇯🇵',
            'Coréenne' => '🇰🇷',
            'Thaïlandaise' => '🇹🇭',
            'Vietnamienne' => '🇻🇳',
            'Malaisienne' => '🇲🇾',
            'Singapourienne' => '🇸🇬',
            'Indonésienne' => '🇮🇩',
            'Philippine' => '🇵🇭',
        ],
    ],

    [
        'titre' => 'Océanie',
        'entrees' => [
            'Australienne' => '🇦🇺',
            'Néo-Zélandaise' => '🇳🇿',
        ],
    ],

    [
        'titre' => 'Autre',
        'entrees' => [
            'Autre' => '🌍',
        ],
    ],
];
