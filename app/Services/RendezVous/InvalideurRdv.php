<?php

namespace App\Services\RendezVous;

use App\Enums\StatutReservationRdv;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPRdvReservation;
use Illuminate\Support\Str;

class InvalideurRdv
{
    /**
     * @param  array<string, mixed>  $nouvellesValeurs
     */
    public function siIdentiteCandidatureChangee(ESBTPCandidature $actuelle, array $nouvellesValeurs): int
    {
        if ($this->memeIdentite($actuelle, $nouvellesValeurs)) {
            return 0;
        }

        return ESBTPRdvReservation::query()
            ->where('candidature_id', $actuelle->id)
            ->whereIn('statut', StatutReservationRdv::valeursOccupantes())
            ->update(['statut' => StatutReservationRdv::Annulee->value]);
    }

    /**
     * @param  array<string, mixed>  $nouvellesValeurs
     */
    private function memeIdentite(ESBTPCandidature $candidature, array $nouvellesValeurs): bool
    {
        $normaliser = static fn ($valeur): string => preg_replace(
            '/\s+/',
            ' ',
            mb_strtoupper(Str::ascii(trim((string) $valeur)), 'UTF-8')
        );

        $nom = $nouvellesValeurs['nom'] ?? $candidature->nom;
        $prenoms = $nouvellesValeurs['prenoms'] ?? $candidature->prenoms;
        $naissance = $nouvellesValeurs['date_naissance'] ?? $candidature->date_naissance;

        $naissanceActuelle = $candidature->date_naissance;
        $naissanceActuelle = $naissanceActuelle instanceof \DateTimeInterface
            ? $naissanceActuelle->format('Y-m-d')
            : (string) $naissanceActuelle;
        $naissanceNouvelle = $naissance instanceof \DateTimeInterface
            ? $naissance->format('Y-m-d')
            : (string) $naissance;

        return $normaliser($candidature->nom) === $normaliser($nom)
            && $normaliser($candidature->prenoms) === $normaliser($prenoms)
            && $naissanceActuelle === $naissanceNouvelle;
    }
}
