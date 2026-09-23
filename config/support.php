<?php

/*
|--------------------------------------------------------------------------
| KLASSCI Care — cote instance
|--------------------------------------------------------------------------
|
| Le Master est la source de verite des demandes. Ce fichier ne dit que ce
| que l'instance collecte, et combien de temps elle attend le Master.
| Blueprint : adminKlassci/docs/support/KLASSCI_CARE_BLUEPRINT.md
|
*/

return [

    // Delais courts : un signalement ne doit jamais bloquer une page.
    'delais' => [
        'connexion' => 2,
        'reponse' => 5,
        // Apres un echec, on ne rappelle pas le Master pendant ce temps.
        'coupe_circuit_secondes' => 60,
        // Fonctionnalites ouvertes a l'instance, relues toutes les 5 minutes.
        'fonctionnalites_secondes' => 300,
    ],

    /*
    | Les mots de l'ecole pour qualifier un signalement. Miroir de
    | App\Domain\Care\Tickets\Enums\CategorieClient cote Master : les deux
    | applications ne partagent pas de code (Laravel 9 ici, 12 la-bas). Une
    | categorie absente du Master est refusee par lui en 422, jamais perdue.
    */
    'categories' => [
        'PROBLEME' => ['libelle' => 'Quelque chose ne fonctionne pas', 'icone' => 'fa-bug'],
        'INFORMATION_INCORRECTE' => ['libelle' => 'Une information semble incorrecte', 'icone' => 'fa-circle-exclamation'],
        'QUESTION' => ['libelle' => 'Je ne comprends pas comment faire', 'icone' => 'fa-circle-question'],
        'SUGGESTION' => ['libelle' => "J'aimerais pouvoir faire quelque chose", 'icone' => 'fa-lightbulb'],
        'BLOQUE' => ['libelle' => 'Mon travail est bloqué', 'icone' => 'fa-hand'],
        'AUTRE' => ['libelle' => 'Autre', 'icone' => 'fa-ellipsis'],
    ],

    // Limites de saisie tant que le Master n'a jamais repondu. Des qu'il
    // repond, les siennes font foi (ClientMasterSupport::limites()).
    'limites_par_defaut' => [
        'description_min' => 10,
        'description_max' => 5000,
        'reponse_min' => 2,
    ],

    'boite_envoi' => [
        'tentatives_max' => 20,
    ],

    /*
    | Parametres de route reconnus comme « element concerne ». Le nom du
    | parametre est a gauche, le type transmis au Master a droite. Seul
    | l'identifiant part, jamais le contenu.
    */
    'entites_de_route' => [
        'etudiant' => 'etudiant',
        'student' => 'etudiant',
        'inscription' => 'inscription',
        'paiement' => 'paiement',
        'evaluation' => 'evaluation',
        'note' => 'note',
        'classe' => 'classe',
        'matiere' => 'matiere',
        'bulletin' => 'bulletin',
        'jury' => 'jury',
        'enseignant' => 'enseignant',
        'teacher' => 'enseignant',
        'seance' => 'seance',
        'seances_cour' => 'seance',
        'emploi_temp' => 'emploi_temps',
    ],

    /*
    | Repli quand la route ne porte pas de middleware `module.*.access` :
    | prefixe du nom de route → module. Approximatif par nature ; le module lu
    | sur la route prime toujours.
    */
    'modules_par_prefixe' => [
        'esbtp.notes.' => 'notes_evaluations',
        'notes.' => 'notes_evaluations',
        'esbtp.evaluations.' => 'notes_evaluations',
        'esbtp.bulletins.' => 'notes_evaluations',
        'resultats.' => 'notes_evaluations',
        'esbtp.lmd.' => 'lmd',
        'esbtp.paiements.' => 'comptabilite',
        'paiements.' => 'comptabilite',
        'esbtp.comptabilite.' => 'comptabilite',
        'frais.' => 'comptabilite',
        'inscriptions.' => 'etudiants',
        'esbtp.inscriptions.' => 'etudiants',
        'esbtp.etudiants.' => 'etudiants',
        'etudiants.' => 'etudiants',
        'esbtp.classes.' => 'academique',
        'esbtp.matieres.' => 'academique',
        'esbtp.filieres.' => 'academique',
        'emploi-temps.' => 'emploi_temps',
        'esbtp.emploi-temps.' => 'emploi_temps',
        'esbtp.attendances.' => 'presences',
        'teacher.' => 'enseignants',
        'esbtp.enseignants.' => 'enseignants',
        'personnel.' => 'personnel',
        'esbtp.annonces.' => 'communication',
    ],
];
