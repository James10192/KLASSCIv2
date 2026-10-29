<?php

namespace App\Services\Emails\Fautes;

use App\Enums\EtatEmail;
use App\Services\Emails\AnalyseurEmail;
use App\Services\Emails\ResolveurDns;

/**
 * Ce qu'une adresse doit remplir, AU MOMENT D'ECRIRE, pour etre corrigee.
 * Refuse par defaut : tout doute laisse l'adresse telle quelle.
 *
 * - L'adresse ENTIERE est analysee (forme comprise), pas seulement le domaine.
 * - Faute CONNUE (tables explicites des listes partagees : fautes connues,
 *   extensions fautives) : corrigee vers sa suggestion.
 * - Faute PROBABLE (distance d'edition) : seulement avec `inclure_probables`,
 *   et seulement si le resolveur repond, a l'instant et sans cache, que le
 *   domaine n'existe pas (NXDOMAIN pour MX et pour A). Sans reponse sure :
 *   rien.
 * - `domaine_propose` doit etre exactement la suggestion ; l'adresse corrigee
 *   doit etre valide.
 *
 * Les domaines se comparent apres `trim` et passage en minuscules.
 */
class ControleEcriture
{
    public function __construct(
        private readonly AnalyseurEmail $analyseur,
        private readonly ResolveurDns $dns,
    ) {}

    /**
     * @param  array{domaine_propose: string, domaine_actuel?: ?string}  $correction
     * @return array{0: ?string, 1: ?string} le motif de refus, ou l'adresse corrigee
     */
    public function verifier(?string $actuelle, array $correction, bool $inclureProbables): array
    {
        $analyse = $this->analyseur->analyser($actuelle);
        if ($analyse->etat === EtatEmail::Invalide) {
            return [MotifsCorrection::ADRESSE_INVALIDE, null];
        }
        $probable = $analyse->etat === EtatEmail::FauteProbable;
        if ((! $probable && $analyse->etat !== EtatEmail::FauteDeFrappe) || $analyse->domaineSuggere() === null) {
            return [MotifsCorrection::MODIFIEE, null];
        }

        $domaine = (string) $analyse->domaine;
        $demande = $correction['domaine_actuel'] ?? null;
        $motif = match (true) {
            $demande !== null && mb_strtolower(trim($demande)) !== $domaine => MotifsCorrection::MODIFIEE,
            mb_strtolower(trim($correction['domaine_propose'])) !== $analyse->domaineSuggere() => MotifsCorrection::NON_CANONIQUE,
            $probable && ! $inclureProbables => MotifsCorrection::PROBABLE_NON_AUTORISEE,
            $probable && $this->dns->domaineInexistant($domaine) !== true => MotifsCorrection::DNS_NON_CONFIRME,
            default => null,
        };
        if ($motif !== null) {
            return [$motif, null];
        }

        $nouvelle = DomaineCanonique::corriger((string) $actuelle, (string) $analyse->domaineSuggere());

        return $this->analyseur->analyser($nouvelle)->etat === EtatEmail::Valide
            ? [null, $nouvelle]
            : [MotifsCorrection::ADRESSE_INVALIDE, null];
    }
}
