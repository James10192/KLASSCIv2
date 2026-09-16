<?php

namespace App\Services\LMD;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class PerimetreComposante
{
    /** @return list<int> Vide = pas de restriction (défaut CI). */
    public function idsPour(User $user): array
    {
        if (! $user->relationLoaded('composantes')) {
            $user->load('composantes:id');
        }

        return $user->composantes->pluck('id')->map(static fn ($id): int => (int) $id)->all();
    }

    public function restreindreEtudiants(Builder $requete, User $user): Builder
    {
        $ids = $this->idsPour($user);
        if ($ids === []) {
            return $requete;
        }

        return $requete->whereHas('inscriptions.classe.parcours.mention', function (Builder $q) use ($ids) {
            $q->whereIn('domaine_id', $ids);
        });
    }
}
