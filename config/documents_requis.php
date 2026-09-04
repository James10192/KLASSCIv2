<?php

/*
|--------------------------------------------------------------------------
| Catalogue des pieces a fournir — jeu propose a la creation d'une instance
|--------------------------------------------------------------------------
|
| Ce fichier ne decrit PAS ce que l'ecole exige : il decrit ce que KLASSCI
| PROPOSE le jour ou une ecole ouvre son catalogue. L'ecole installe ce jeu
| d'un clic, puis retire, renomme et reordonne librement — y compris tout
| supprimer. Rien ici n'est impose.
|
| La liste vit en configuration plutot qu'en dur dans le service parce que
| deux instances n'ouvrent pas sur le meme metier : une universite LMD et un
| BTS technique ne partent pas du meme dossier type. Une instance peut donc
| publier son propre jeu de depart sans toucher au code.
|
| 'portee_filieres' et 'portee_niveaux' restent volontairement vides : le jeu
| propose est le socle commun a toute l'ecole. C'est a l'ecole de restreindre
| ensuite une piece a une filiere ou un niveau donne.
|
| 'forme_attendue' et 'echeance' sont ecrits en clair plutot qu'en constantes
| d'enum : un fichier de configuration doit rester lisible sans le code, et il
| est charge avant que l'autoloader ne soit garanti disponible. Les valeurs
| valides restent celles de App\Enums\FormeDocumentRequis et
| App\Enums\EcheanceDocumentRequis, et sont normalisees a l'installation.
*/

return [

    'pieces_par_defaut' => [
        [
            'code'               => 'extrait_naissance',
            'libelle'            => 'Extrait de naissance',
            'description'        => 'Extrait d\'acte de naissance. Un exemplaire est repris chaque annee pour le ministere.',
            'is_obligatoire'     => true,
            'forme_attendue'     => 'copie',
            'nombre_exemplaires' => 1,
            'echeance'           => 'inscription',
        ],
        [
            'code'               => 'photo_identite',
            'libelle'            => 'Photo d\'identite',
            'description'        => 'Photo recente, fond uni.',
            'is_obligatoire'     => true,
            'forme_attendue'     => 'original',
            'nombre_exemplaires' => 2,
            'echeance'           => 'inscription',
        ],
        [
            'code'               => 'piece_identite',
            'libelle'            => 'Copie de la piece d\'identite',
            'description'        => 'Carte nationale d\'identite, passeport ou attestation d\'identite en cours de validite.',
            'is_obligatoire'     => true,
            'forme_attendue'     => 'copie',
            'nombre_exemplaires' => 1,
            'echeance'           => 'inscription',
        ],
        [
            'code'               => 'releve_notes',
            'libelle'            => 'Releve de notes',
            'description'        => 'Releve de notes du dernier diplome obtenu.',
            'is_obligatoire'     => true,
            'forme_attendue'     => 'copie',
            'nombre_exemplaires' => 1,
            'echeance'           => 'inscription',
        ],
        [
            'code'               => 'certificat_scolarite',
            'libelle'            => 'Certificat de scolarite',
            'description'        => 'Certificat de scolarite de l\'etablissement precedent.',
            'is_obligatoire'     => false,
            'forme_attendue'     => 'copie',
            'nombre_exemplaires' => 1,
            'echeance'           => 'inscription',
        ],
    ],

];
