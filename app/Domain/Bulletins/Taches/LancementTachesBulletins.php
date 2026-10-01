<?php

namespace App\Domain\Bulletins\Taches;

use App\Models\ESBTPClasse;
use App\Models\User;

/**
 * Crée une tâche, ou rend celle qui tourne déjà pour la même demande.
 *
 * Un double clic, ou un second onglet, ne lance pas deux fois soixante-dix
 * bulletins : la même personne, le même travail sur le même périmètre
 * retrouve la tâche en cours.
 */
class LancementTachesBulletins
{
    /** @param array<int, int> $etudiantIds */
    public function generation(
        User $demandeur,
        ESBTPClasse $classe,
        int $anneeId,
        string $periode,
        array $etudiantIds,
        bool $recalculer,
        ?string $raisonIncomplete,
    ): BulletinTache {
        $existante = $this->enCours($demandeur, BulletinTache::TYPE_GENERATION, $classe->id, $anneeId, $periode);
        if ($existante !== null) {
            return $existante;
        }

        return BulletinTache::create([
            'type' => BulletinTache::TYPE_GENERATION,
            'systeme' => 'BTS',
            'user_id' => $demandeur->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $anneeId,
            'periode' => $periode,
            'statut' => BulletinTache::EN_ATTENTE,
            'parametres' => [
                'classe_nom' => $classe->name,
                'recalculer' => $recalculer,
                'incomplete_reason' => $raisonIncomplete,
            ],
            'elements' => array_values(array_map('intval', $etudiantIds)),
            'total' => count($etudiantIds),
        ]);
    }

    /**
     * @param  array<int, int>  $bulletinIds
     * @param  array<string, mixed>  $contexte  entête, absents et filtres de la liste
     */
    public function export(User $demandeur, string $mode, array $bulletinIds, array $contexte): BulletinTache
    {
        $filtres = $contexte['filtres'] ?? [];
        $classeId = isset($filtres['classe_id']) ? (int) $filtres['classe_id'] : null;
        $anneeId = isset($filtres['annee_universitaire_id']) ? (int) $filtres['annee_universitaire_id'] : null;
        $periode = $filtres['periode_id'] ?? null;

        $existante = $this->enCours($demandeur, BulletinTache::TYPE_EXPORT, $classeId, $anneeId, $periode);
        if ($existante !== null && $existante->parametre('mode') === $mode
            && $existante->elements === array_values($bulletinIds)) {
            return $existante;
        }

        return BulletinTache::create([
            'type' => BulletinTache::TYPE_EXPORT,
            'systeme' => 'BTS',
            'user_id' => $demandeur->id,
            'classe_id' => $classeId,
            'annee_universitaire_id' => $anneeId,
            'periode' => $periode,
            'statut' => BulletinTache::EN_ATTENTE,
            'parametres' => [
                'mode' => $mode,
                'classe_nom' => $contexte['entete']['classe'] ?? null,
                'entete' => $contexte['entete'] ?? [],
                'ungenerated_ids' => $contexte['ungenerated_ids'] ?? [],
                'filtres' => $filtres,
            ],
            'elements' => array_values(array_map('intval', $bulletinIds)),
            'total' => count($bulletinIds),
        ]);
    }

    private function enCours(User $demandeur, string $type, ?int $classeId, ?int $anneeId, ?string $periode): ?BulletinTache
    {
        return BulletinTache::actives()
            ->where('user_id', $demandeur->id)
            ->where('type', $type)
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeId)
            ->where('periode', $periode)
            ->latest('id')
            ->first();
    }
}
