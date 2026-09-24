<?php

namespace App\Services\Emails\Fautes;

use App\Services\Emails\InventaireAdresses;
use App\Services\Verification\MasqueContact;

/**
 * Les corrections a proposer : chaque adresse dont le domaine est une faute
 * de frappe certaine, avec la suggestion canonique et les autres lignes du
 * meme dossier qui portent la meme adresse. Lecture seule, tout est masque.
 */
class PropositionsCorrection
{
    public const MAX = 500;

    public function __construct(
        private readonly InventaireAdresses $inventaire,
        private readonly DomaineCanonique $canonique,
        private readonly LiensDossier $liens,
    ) {}

    /** @return array{total: int, propositions: list<array<string, mixed>>} */
    public function lister(bool $inclureComptes, bool $avecMx = true): array
    {
        $fautes = $this->domainesFautifs($inclureComptes, $avecMx);
        $propositions = [];
        $total = 0;
        foreach ($fautes as $cle => $domaines) {
            [$table, $colonne] = explode(':', $cle);
            foreach ($this->inventaire->lignes($table, $colonne, array_keys($domaines)) as $ligne) {
                $total++;
                if (count($propositions) < self::MAX) {
                    $propositions[] = $this->proposition(new CibleCorrection($table, $colonne, (int) $ligne->id), (string) $ligne->email, $domaines);
                }
            }
        }

        return ['total' => $total, 'propositions' => $propositions];
    }

    /** @return array<string, array<string, string>> "table:colonne" => [domaine fautif => domaine propose] */
    private function domainesFautifs(bool $inclureComptes, bool $avecMx): array
    {
        $fautes = [];
        foreach ($this->inventaire->domaines() as $ligne) {
            if ($ligne['table'] === CibleCorrection::TABLE_COMPTES && ! $inclureComptes) {
                continue;
            }
            $propose = $this->canonique->pour($ligne['domaine'], $avecMx);
            if ($propose !== null) {
                $fautes[$ligne['table'].':'.$ligne['colonne']][$ligne['domaine']] = $propose;
            }
        }

        return $fautes;
    }

    /** @param  array<string, string>  $domaines */
    private function proposition(CibleCorrection $cible, string $email, array $domaines): array
    {
        $actuel = DomaineCanonique::domaineDe($email);
        $propose = $domaines[$actuel] ?? (string) $this->canonique->pour($actuel);

        return [
            'cle' => $cible->cle(),
            'table' => $cible->table,
            'colonne' => $cible->colonne,
            'id' => $cible->id,
            'email_masque' => MasqueContact::email($email),
            'domaine_actuel' => $actuel,
            'domaine_propose' => $propose,
            'suggestion_masquee' => MasqueContact::email(DomaineCanonique::corriger($email, $propose)),
            'dossier_reference_masquee' => $this->liens->referenceMasquee($cible),
            'lie_a' => $this->liens->lies($cible, $email),
        ];
    }
}
