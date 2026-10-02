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
    /**
     * Une candidature sans année vaut pour l'année où l'école l'inscrit (le
     * parcours configurable retombe alors sur l'année courante). Deux
     * candidatures la même année : un seul « oui » suffit, l'école tranche en
     * confirmant.
     */
    public function declareRecommencer(int $etudiantId, int $anneeId): bool
    {
        return $this->requete()
            ->where('etudiant_id', $etudiantId)
            ->where(fn ($q) => $q->where('annee_universitaire_id', $anneeId)->orWhereNull('annee_universitaire_id'))
            ->exists();
    }

    /**
     * Tous les couples « étudiant:année » déclarés, en une requête (recensement).
     * Une candidature sans année donne « étudiant:* ».
     *
     * @return array<string, true>
     */
    public function couples(): array
    {
        return $this->requete()
            ->get(['etudiant_id', 'annee_universitaire_id'])
            ->mapWithKeys(fn ($c) => [$c->etudiant_id.':'.($c->annee_universitaire_id ?? '*') => true])
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
