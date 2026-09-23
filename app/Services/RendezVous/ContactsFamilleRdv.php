<?php

namespace App\Services\RendezVous;

use App\Models\ESBTPRdvReservation;

/**
 * Qui appeler pour une reservation, quand la convocation ne peut pas partir.
 *
 * La reservation porte le telephone du candidat (obligatoire). Le second
 * contact vient du dossier : le tuteur saisi sur la candidature, ou le premier
 * parent rattache a l'etudiant pour une reinscription. Une seule source, lue par
 * l'ecran d'accueil et par l'export des familles a prevenir.
 */
class ContactsFamilleRdv
{
    /** Relations a charger pour que second() ne declenche aucune requete par ligne. */
    public static function chargements(): array
    {
        return [
            'candidature:id,statut,reference_publique,tuteur_nom,tuteur_telephone,tuteur_lien',
            'demande:id,statut,etudiant_id,reference_publique',
            'demande.etudiant:id',
            'demande.etudiant.parents:esbtp_parents.id,nom,prenoms,telephone',
        ];
    }

    /** @return array{nom: string, telephone: string}|null */
    public function second(ESBTPRdvReservation $reservation): ?array
    {
        $candidature = $reservation->candidature;
        if ($candidature !== null && trim((string) $candidature->tuteur_telephone) !== '') {
            $lien = trim((string) $candidature->tuteur_lien);

            return [
                'nom' => trim((string) $candidature->tuteur_nom).($lien !== '' ? ' ('.$lien.')' : ''),
                'telephone' => (string) $candidature->tuteur_telephone,
            ];
        }

        $parent = $reservation->demande?->etudiant?->parents
            ?->first(fn ($p) => trim((string) $p->telephone) !== '');
        if ($parent !== null) {
            return ['nom' => trim($parent->nom.' '.$parent->prenoms), 'telephone' => (string) $parent->telephone];
        }

        return null;
    }

    public function reference(ESBTPRdvReservation $reservation): string
    {
        return (string) ($reservation->porteur()?->referencePubliqueAffichee() ?? '');
    }
}
