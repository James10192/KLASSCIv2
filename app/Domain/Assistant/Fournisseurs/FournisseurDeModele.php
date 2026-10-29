<?php

namespace App\Domain\Assistant\Fournisseurs;

use App\Domain\Assistant\Modeles\ModeleIa;

/**
 * Contrat unique d'un fournisseur de modèle de langage.
 *
 * Une requête neutre entre, un flux d'événements normalisés sort. Un
 * adaptateur ne lève jamais d'exception vers la boucle : une panne (clé
 * absente, HTTP en erreur, flux coupé) devient un événement `erreur`, ce qui
 * permet à la boucle de basculer sur le modèle suivant de la chaîne de repli.
 */
interface FournisseurDeModele
{
    /**
     * @param callable():bool $arreter vrai quand il faut cesser de lire (navigateur parti, délai dépassé)
     * @return iterable<EvenementModele>
     */
    public function diffuser(RequeteModele $requete, ModeleIa $modele, callable $arreter): iterable;
}
