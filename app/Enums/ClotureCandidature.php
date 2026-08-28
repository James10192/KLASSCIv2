<?php

namespace App\Enums;

/**
 * L'issue de la fermeture d'une candidature apres creation de l'inscription.
 *
 * Deux issues, et aucune n'est une panne : ce sont les deux fins normales d'un
 * geste ordinaire de la scolarite. Les avoir modelisees en `bool` plus
 * exception obligeait l'appelant a reconstruire un etat a trois branches avec
 * deux `catch` et un journal duplique — et faisait dependre le message montre a
 * l'agent d'un `catch` dont la classe devait etre importee, faute de quoi il ne
 * correspondait jamais, en silence.
 *
 * Que l'inscription concerne bien le candidat se verifie AVANT sa creation,
 * dans RattachementCandidature::refuserSiNaissanceDivergente() : apres coup,
 * on ne pouvait que constater sur une inscription deja committee.
 */
enum ClotureCandidature: string
{
    /** La candidature est passee en « convertie » et porte l'etudiant cree. */
    case Fermee = 'fermee';

    /**
     * On n'a pas pu la fermer : elle n'etait plus « acceptee », ou plus la.
     *
     * Une seule cause ordinaire : un collegue l'a traitee entre-temps. Le
     * redepot public en etait une seconde ; il ne l'est plus depuis que
     * `PortailCandidatureService::refusDeRedepot()` refuse toute reouverture
     * d'une candidature acceptee, quelle que soit l'identite.
     *
     * La ligne DISPARUE tombe ici aussi. Rien dans l'application ne l'efface —
     * ce modele n'a pas de suppression douce et aucune route ne le supprime —
     * mais une purge ou une main sur la base restent possibles, et l'agent doit
     * alors lire la meme chose : l'inscription est faite, la candidature n'a
     * pas ete marquee, allez voir la corbeille.
     */
    case DejaChangee = 'deja_changee';
}
