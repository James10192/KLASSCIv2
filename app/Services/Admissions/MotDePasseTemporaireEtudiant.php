<?php

namespace App\Services\Admissions;

use Illuminate\Support\Str;

/**
 * Transition pour la création et la récupération des comptes étudiants
 * historiques. À remplacer à terme par une invitation à usage unique :
 * aucun secret ne doit être commun à plusieurs élèves.
 */
final class MotDePasseTemporaireEtudiant
{
    public static function generer(): string
    {
        return Str::random(24);
    }
}
