<?php

/*
|--------------------------------------------------------------------------
| Pièces à fournir — jeu PROPOSÉ à l'ouverture du catalogue
|--------------------------------------------------------------------------
|
| Ce fichier ne décrit pas ce qu'une école exige : il décrit ce que KLASSCI
| propose le jour où une école ouvre son catalogue, vide. L'école installe ce
| jeu d'un clic si elle le veut, puis retire, renomme et réordonne librement,
| jusqu'à tout supprimer. Rien ici n'est imposé, et rien n'est installé sans
| que quelqu'un ait cliqué.
|
| La liste vit en configuration plutôt qu'en dur dans le service parce que deux
| instances n'ouvrent pas sur le même métier : une université LMD et un BTS
| technique ne partent pas du même dossier type. Une instance peut donc publier
| son propre jeu de départ sans qu'on touche au code.
|
| La portée reste volontairement vide : le jeu proposé est le socle commun à
| toute l'école. C'est à l'école de restreindre ensuite une pièce à une filière
| ou à un niveau, depuis l'écran.
|
| 'forme_attendue' et 'echeance' s'écrivent en clair plutôt qu'en constantes
| d'énumération : un fichier de configuration doit rester lisible sans le code.
| Les valeurs valides sont celles de App\Enums\FormePieceDossier et
| App\Enums\EcheancePieceDossier, et sont normalisées à l'installation. Omettre
| l'une des deux clés fait retomber sur le réglage d'école correspondant
| (pieces_dossier.forme_defaut, pieces_dossier.echeance_defaut).
*/

return [

    'jeu_propose' => [
        [
            'code' => 'extrait_naissance',
            'libelle' => 'Extrait de naissance',
            'description' => "Extrait d'acte de naissance. Un exemplaire est repris chaque année pour le ministère.",
            'is_obligatoire' => true,
            'forme_attendue' => 'copie',
            'nombre_exemplaires' => 1,
            'echeance' => 'inscription',
        ],
        [
            'code' => 'photo_identite',
            'libelle' => "Photo d'identité",
            'description' => 'Photo récente, fond uni.',
            'is_obligatoire' => true,
            'forme_attendue' => 'original',
            'nombre_exemplaires' => 2,
            'echeance' => 'inscription',
        ],
        [
            'code' => 'piece_identite',
            'libelle' => "Pièce d'identité",
            'description' => "Carte nationale d'identité, passeport ou attestation d'identité en cours de validité.",
            'is_obligatoire' => true,
            'forme_attendue' => 'copie',
            'nombre_exemplaires' => 1,
            'echeance' => 'inscription',
        ],
        [
            'code' => 'releve_notes',
            'libelle' => 'Relevé de notes',
            'description' => 'Relevé de notes du dernier diplôme obtenu.',
            'is_obligatoire' => true,
            'forme_attendue' => 'copie',
            'nombre_exemplaires' => 1,
            'echeance' => 'inscription',
        ],
        [
            'code' => 'certificat_scolarite',
            'libelle' => 'Certificat de scolarité',
            'description' => "Certificat de scolarité de l'établissement précédent.",
            'is_obligatoire' => false,
            'forme_attendue' => 'copie',
            'nombre_exemplaires' => 1,
            'echeance' => 'inscription',
        ],
    ],

];
