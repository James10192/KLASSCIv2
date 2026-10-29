<?php

namespace Tests\Unit\Domain\Assistant;

use App\Domain\Assistant\Fournisseurs\EvenementModele;
use App\Domain\Assistant\Fournisseurs\FournisseurDeModele;
use App\Domain\Assistant\Fournisseurs\RequeteModele;
use App\Domain\Assistant\Modeles\ModeleIa;

/**
 * Fournisseur scripté : pour chaque clé de modèle, une file de tours, chaque
 * tour étant une liste d'événements. Garde les requêtes reçues.
 */
class FauxFournisseur implements FournisseurDeModele
{
    /** @var array<string, array<int, EvenementModele[]>> */
    public array $scripts = [];

    /** @var array<int, array{modele: string, requete: RequeteModele}> */
    public array $recues = [];

    public function diffuser(RequeteModele $requete, ModeleIa $modele, callable $arreter): iterable
    {
        $this->recues[] = ['modele' => $modele->cle, 'requete' => $requete];
        $tour = array_shift($this->scripts[$modele->cle]) ?? [EvenementModele::erreur('script_vide')];

        foreach ($tour as $evenement) {
            yield $evenement;
        }
    }

    public static function texte(string $texte, int $entree = 10, int $sortie = 5): array
    {
        return [EvenementModele::texte($texte), EvenementModele::usage($entree, $sortie), EvenementModele::fin('fin')];
    }

    public static function outil(string $id, string $nom, array $args = [], string $intro = ''): array
    {
        return array_values(array_filter([
            $intro !== '' ? EvenementModele::texte($intro) : null,
            EvenementModele::outilDebut($id, $nom),
            EvenementModele::outil($id, $nom, $args),
            EvenementModele::usage(20, 10),
            EvenementModele::fin('outils'),
        ]));
    }
}
