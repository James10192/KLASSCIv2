<?php

return [

    /*
     * Les rôles pour lesquels l'établissement exige un second facteur.
     *
     * Liste de noms de rôles, séparés par des virgules. Vide, personne n'est
     * concerné et la plateforme se comporte comme avant.
     *
     * Une liste plutôt qu'un simple oui/non : une école veut protéger sa
     * direction et sa comptabilité sans imposer un téléphone à deux mille
     * étudiants, dont beaucoup se connectent depuis un appareil partagé.
     *
     * Même activé, un compte qui n'a pas confirmé son second facteur depuis
     * son téléphone se connecte comme avant. Le réglage ouvre la possibilité,
     * il ne verrouille personne.
     *
     *   SECURITE_DOUBLE_AUTH_ROLES=superAdmin,comptable
     */
    'double_auth_roles' => env('SECURITE_DOUBLE_AUTH_ROLES', ''),

];
