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
| 'forme_attendue', 'echeance' et 'appartenance' s'écrivent en clair plutôt
| qu'en constantes d'énumération : un fichier de configuration doit rester
| lisible sans le code. Les valeurs valides sont celles de
| App\Enums\FormePieceDossier, App\Enums\EcheancePieceDossier et
| App\Enums\AppartenancePieceDossier, et sont normalisées à l'installation.
| Omettre 'forme_attendue' ou 'echeance' fait retomber sur le réglage d'école
| correspondant (pieces_dossier.forme_defaut, pieces_dossier.echeance_defaut).
|
| 'appartenance' dit si la pièce est un dépôt qui DURE et que chaque inscription
| consomme ('etudiant'), ou une formalité redonnée chaque rentrée
| ('inscription'). 'exemplaires_par_inscription' se lit toujours à la lumière de
| celle-ci : deux photos par inscription sur une pièce qui dure, c'est six
| photos déposées une fois pour une licence de trois ans.
|
| 'duree_validite_mois' est FACULTATIVE, et son absence — comme la valeur null —
| veut dire « ne périme jamais ». Ne l'écrivez jamais à zéro pour dire cela :
| zéro se lit « valide zéro mois », donc périmée à l'instant du dépôt.
*/

return [

    'jeu_propose' => [
        [
            'code' => 'extrait_naissance',
            'libelle' => 'Extrait de naissance',
            'description' => "Extrait d'acte de naissance.",
            'is_obligatoire' => true,
            'forme_attendue' => 'copie',
            'exemplaires_par_inscription' => 1,
            'echeance' => 'inscription',
            // L'état civil ne change pas : un extrait déposé une fois sert
            // toute la scolarité, et ne périme jamais.
            'appartenance' => 'etudiant',
        ],
        [
            'code' => 'photo_identite',
            'libelle' => "Photo d'identité",
            'description' => 'Photo récente, fond uni.',
            'is_obligatoire' => true,
            'forme_attendue' => 'original',
            'exemplaires_par_inscription' => 2,
            'echeance' => 'inscription',
            // Deux par inscription, prélevées sur ce que l'étudiant a déposé :
            // six photos couvrent une licence sans qu'on les redemande.
            'appartenance' => 'etudiant',
        ],
        [
            'code' => 'piece_identite',
            'libelle' => "Pièce d'identité",
            'description' => "Carte nationale d'identité, passeport ou attestation d'identité en cours de validité.",
            'is_obligatoire' => true,
            'forme_attendue' => 'copie',
            'exemplaires_par_inscription' => 1,
            'echeance' => 'inscription',
            'appartenance' => 'etudiant',
        ],
        [
            'code' => 'releve_notes',
            'libelle' => 'Relevé de notes',
            'description' => 'Relevé de notes du dernier diplôme obtenu.',
            'is_obligatoire' => true,
            'forme_attendue' => 'copie',
            'exemplaires_par_inscription' => 1,
            'echeance' => 'inscription',
            'appartenance' => 'etudiant',
        ],
        [
            'code' => 'certificat_scolarite',
            'libelle' => 'Certificat de scolarité',
            'description' => "Certificat de scolarité de l'établissement précédent.",
            'is_obligatoire' => false,
            'forme_attendue' => 'copie',
            'exemplaires_par_inscription' => 1,
            'echeance' => 'inscription',
            // Celle-ci se redonne : le certificat de l'année précédente n'est
            // pas celui de l'année en cours.
            'appartenance' => 'inscription',
        ],
    ],

];
