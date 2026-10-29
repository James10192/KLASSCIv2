<?php

namespace App\Http\Controllers\Support\Concerns;

use App\Domain\Support\Exceptions\MasterSupportIndisponible;
use App\Domain\Support\Exceptions\MasterSupportRefus;
use Illuminate\Http\JsonResponse;

/**
 * Repondre et joindre obeissent aux memes regles : seul l'auteur ecrit, et
 * seulement si l'identifiant de l'instance le permet. Les deux formulaires
 * doivent disparaitre ensemble, d'ou une seule source.
 *
 * Suppose `$this->master` (ClientMasterSupport).
 */
trait EcritSurUneDemande
{
    private function peutRepondre(array $demande, int $utilisateurId): bool
    {
        return ($demande['statut']['code'] ?? null) !== 'FERME'
            && (string) ($demande['rapporteur']['id'] ?? '') === (string) $utilisateurId
            && in_array('support:update', $this->master->portees(), true);
    }

    private function peutJoindre(array $demande, int $utilisateurId): bool
    {
        return $this->peutRepondre($demande, $utilisateurId)
            && count($demande['pieces_jointes'] ?? []) < $this->master->limites()['pieces_max'];
    }

    /**
     * La demande a ete fermee pendant que l'ecole ecrivait : le statut affiche
     * rejoint celui du Master, et plus rien ne s'envoie depuis cette page. Le
     * texte deja saisi reste lisible, pour etre copie.
     */
    private function demandeFermee(string $reference, int $auteur): JsonResponse
    {
        try {
            $statut = $this->master->afficher($reference, $auteur)['statut'] ?? null;
        } catch (MasterSupportIndisponible|MasterSupportRefus) {
            $statut = null;
        }

        return response()->json([
            'message' => 'Cette demande est fermée : ouvrez-en une nouvelle si le problème revient.',
            'statut' => $statut ? view('support.demandes._statut', ['statut' => $statut])->render() : null,
            'peut_repondre' => false,
            'peut_joindre' => false,
        ], 409);
    }
}
