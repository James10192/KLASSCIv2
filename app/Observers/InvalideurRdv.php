<?php

namespace App\Observers;

use App\Enums\StatutReservationRdv;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPRdvReservation;
use App\Support\IdentitePersonne;

class InvalideurRdv
{
    public function updating(ESBTPCandidature $candidature): void
    {
        if (! $candidature->isDirty(['nom', 'prenoms', 'date_naissance'])) {
            return;
        }

        if (IdentitePersonne::concordent(
            $candidature->getOriginal('nom'),
            $candidature->getOriginal('prenoms'),
            $candidature->getOriginal('date_naissance'),
            $candidature->nom,
            $candidature->prenoms,
            $candidature->date_naissance,
        )) {
            return;
        }

        ESBTPRdvReservation::query()
            ->where('candidature_id', $candidature->id)
            ->whereIn('statut', StatutReservationRdv::valeursOccupantes())
            ->update(['statut' => StatutReservationRdv::Annulee->value]);
    }
}
