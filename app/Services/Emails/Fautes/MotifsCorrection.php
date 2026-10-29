<?php

namespace App\Services\Emails\Fautes;

/** Pourquoi une cle n'a pas ete corrigee (`ignorees[].motif`). */
final class MotifsCorrection
{
    /** Cle mal formee, colonne absente ou non inventoriee, compte sans `inclure_comptes`. */
    public const CLE_INVALIDE = 'cle_invalide';

    /** Aucune ligne a cet identifiant. */
    public const INTROUVABLE = 'introuvable';

    /** Plus de faute, `domaine_actuel` different, ou valeur changee avant le verrou. */
    public const MODIFIEE = 'modifiee_entre_temps';

    /** `domaine_propose` n'est pas la suggestion canonique du domaine actuel. */
    public const NON_CANONIQUE = 'domaine_non_canonique';

    /** Adresse actuelle ou corrigee mal formee. */
    public const ADRESSE_INVALIDE = 'adresse_invalide';

    /** Faute probable sans `inclure_probables`. */
    public const PROBABLE_NON_AUTORISEE = 'faute_probable_non_autorisee';

    /** Faute probable dont le resolveur n'a pas confirme, a l'instant, que le domaine n'existe pas. */
    public const DNS_NON_CONFIRME = 'dns_non_confirme';

    /** Une ligne liee du meme dossier (meme adresse) manque a la requete. */
    public const LIES_NON_VALIDES = 'lies_non_valides';

    /** Une autre cle du meme groupe est refusee : le groupe entier l'est. */
    public const GROUPE_REFUSE = 'groupe_refuse';

    /** L'adresse corrigee existe deja (contrainte d'unicite) : groupe annule. */
    public const CONFLIT = 'adresse_deja_utilisee';

    /** Erreur a l'ecriture : groupe annule, detail dans le journal. */
    public const ECHEC = 'echec_ecriture';
}
