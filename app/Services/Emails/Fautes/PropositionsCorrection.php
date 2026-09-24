<?php

namespace App\Services\Emails\Fautes;

use App\Services\Emails\InventaireAdresses;
use App\Services\Verification\MasqueContact;

/**
 * Les corrections a proposer : chaque adresse dont le domaine est une faute
 * CONNUE (et, sur demande, PROBABLE : DomaineCanonique), avec la suggestion
 * canonique et les autres lignes du meme dossier qui portent la meme adresse.
 * Une adresse de compte (`users`) est signalee : c'est un identifiant de
 * connexion. Lecture seule, tout est masque.
 */
class PropositionsCorrection
{
    public const MAX = 500;

    private const AVERTISSEMENT_COMPTE = 'Adresse de connexion d\'un compte : la corriger change l\'identifiant avec lequel cette personne se connecte. Prévenez-la.';

    public function __construct(
        private readonly InventaireAdresses $inventaire,
        private readonly DomaineCanonique $canonique,
        private readonly LiensDossier $liens,
    ) {}

    /** @return array{total: int, propositions: list<array<string, mixed>>} */
    public function lister(bool $inclureComptes, bool $inclureProbables): array
    {
        $fautes = $this->domainesFautifs($inclureComptes, $inclureProbables);
        $propositions = [];
        $total = 0;
        foreach ($fautes as $cle => $domaines) {
            [$table, $colonne] = explode(':', $cle);
            foreach ($this->inventaire->lignes($table, $colonne, array_keys($domaines)) as $ligne) {
                $suggestion = $domaines[DomaineCanonique::domaineDe($ligne->email)] ?? null;
                if ($suggestion === null) {
                    continue; // meme regroupement que l'inventaire (trim, minuscules) : ne devrait pas arriver
                }
                $total++;
                if (count($propositions) < self::MAX) {
                    $propositions[] = $this->proposition(new CibleCorrection($table, $colonne, (int) $ligne->id), (string) $ligne->email, $suggestion);
                }
            }
        }

        return ['total' => $total, 'propositions' => $propositions];
    }

    /** @return array<string, array<string, array{domaine: string, nature: string}>> "table:colonne" => [domaine fautif => suggestion] */
    private function domainesFautifs(bool $inclureComptes, bool $inclureProbables): array
    {
        $fautes = [];
        foreach ($this->inventaire->domaines() as $ligne) {
            if ($ligne['table'] === CibleCorrection::TABLE_COMPTES && ! $inclureComptes) {
                continue;
            }
            $propose = $this->canonique->pour($ligne['domaine'], $inclureProbables);
            if ($propose !== null) {
                $fautes[$ligne['table'].':'.$ligne['colonne']][$ligne['domaine']] = $propose;
            }
        }

        return $fautes;
    }

    /** @param  array{domaine: string, nature: string}  $suggestion */
    private function proposition(CibleCorrection $cible, string $email, array $suggestion): array
    {
        $actuel = DomaineCanonique::domaineDe($email);
        $propose = $suggestion['domaine'];
        $compte = $cible->table === CibleCorrection::TABLE_COMPTES;

        return [
            'cle' => $cible->cle(),
            'table' => $cible->table,
            'colonne' => $cible->colonne,
            'id' => $cible->id,
            'email_masque' => MasqueContact::email($email),
            'domaine_actuel' => $actuel,
            'domaine_propose' => $propose,
            'nature' => $suggestion['nature'],
            'suggestion_masquee' => MasqueContact::email(DomaineCanonique::corriger($email, $propose)),
            'dossier_reference_masquee' => $this->liens->referenceMasquee($cible),
            'lie_a' => $this->liens->lies($cible, $email),
            'compte_de_connexion' => $compte,
            'avertissement' => $compte ? self::AVERTISSEMENT_COMPTE : null,
        ];
    }
}
