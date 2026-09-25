<?php

namespace App\Domain\Lms\Synchronisation;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPTeacher;
use App\Models\User;

/**
 * Quand le COMPTE d'un eleve ou d'un enseignant change sur un point que le LMS
 * recoit, sa fiche avance, et repart donc dans son flux de synchronisation.
 *
 * Seules ces colonnes comptent : la derniere visite, ecrite sur le compte a
 * chaque navigation (`UpdateLastLogin`), ne doit rien faire repartir. Celle-ci
 * passe d'ailleurs par `saveQuietly()`, qui n'emet aucun evenement.
 *
 * Mise a jour en query builder : aucun observateur des fiches ne se declenche.
 */
final class CompteLmsSuitLaFiche
{
    private const COLONNES = ['is_active', 'email', 'username', 'name'];

    public static function ecouter(): void
    {
        User::updated(function (User $compte) {
            if ($compte->wasChanged(self::COLONNES)) {
                self::avancer($compte);
            }
        });
        User::deleted(fn (User $compte) => self::avancer($compte));
        User::restored(fn (User $compte) => self::avancer($compte));
    }

    private static function avancer(User $compte): void
    {
        foreach ([ESBTPEtudiant::class, ESBTPTeacher::class] as $fiche) {
            $requete = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($fiche), true)
                ? $fiche::withTrashed()
                : $fiche::query();

            $requete->where('user_id', $compte->getKey())->toBase()->update(['updated_at' => now()]);
        }
    }
}
