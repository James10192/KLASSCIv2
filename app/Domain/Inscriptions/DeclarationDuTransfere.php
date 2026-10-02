<?php

namespace App\Domain\Inscriptions;

use App\Models\ESBTPCandidature;

/**
 * Ce qu'un candidat venu d'un autre établissement a déclaré : « l'année que je
 * suivais là-bas, je la recommence chez vous ».
 *
 * Un transféré n'a pas d'année précédente dans KLASSCI : sans sa déclaration,
 * le logiciel n'a rien à comparer et propose toujours « non ». Elle tient donc
 * lieu de proposition pour l'année de sa candidature, et une personne habilitée
 * la confirme comme les autres ({@see StatutRedoublant}).
 *
 * Lue par toutes les portes, y compris le parcours configurable, qui crée
 * l'inscription sans poser la question : c'est le recalcul de la proposition
 * qui la reprend, pas l'écran.
 */
class DeclarationDuTransfere
{
    /** Le motif enregistré quand une personne garde ce « oui » sans en écrire. */
    public const MOTIF = 'Déclaré par le candidat dans sa candidature : il recommence l\'année qu\'il suivait dans un autre établissement.';

    public function declareRecommencer(int $etudiantId, int $anneeId): bool
    {
        return $this->requete()
            ->where('etudiant_id', $etudiantId)
            ->where('annee_universitaire_id', $anneeId)
            ->exists();
    }

    /**
     * Tous les couples « étudiant:année » déclarés, en une requête (recensement).
     *
     * @return array<string, true>
     */
    public function couples(): array
    {
        return $this->requete()
            ->get(['etudiant_id', 'annee_universitaire_id'])
            ->mapWithKeys(fn ($c) => [$c->etudiant_id.':'.$c->annee_universitaire_id => true])
            ->all();
    }

    private function requete()
    {
        return ESBTPCandidature::query()
            ->whereNotNull('etudiant_id')
            ->where('est_transfert', true)
            ->where('redouble_niveau_origine', true)
            ->where('statut', '!=', ESBTPCandidature::STATUT_REJETEE);
    }
}
