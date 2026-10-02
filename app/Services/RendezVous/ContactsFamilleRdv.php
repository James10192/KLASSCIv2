<?php

namespace App\Services\RendezVous;

use App\Models\ESBTPRdvReservation;
use App\Support\ColonnesDeployees;
use Illuminate\Support\Str;

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
    /**
     * Relations a charger pour que second() ne declenche aucune requete par ligne.
     * `verification_contact` (badge) seulement une fois la migration passee :
     * l'accueil reste lisible entre le pull et le migrate du deploiement.
     */
    public static function chargements(): array
    {
        return [
            'candidature:'.implode(',', ['id', 'statut', 'reference_publique', 'tuteur_nom', 'tuteur_telephone', 'tuteur_lien',
                ...ColonnesDeployees::si('esbtp_candidatures', 'verification_contact')]),
            'demande:'.implode(',', ['id', 'statut', 'etudiant_id', 'reference_publique',
                ...ColonnesDeployees::si('esbtp_reinscription_demandes', 'verification_contact')]),
            'demande.etudiant:id,nom,prenoms,matricule',
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

    /**
     * Le texte dans lequel l'accueil du jour cherche une famille.
     *
     * Le guichet tape ce qu'il a sous les yeux : le nom de la convocation, mais
     * aussi celui de l'eleve quand c'est un parent qui a reserve, son matricule,
     * le nom du tuteur. Accents, apostrophes et tirets sont retires ici et dans
     * la saisie (meme regle des deux cotes) : « DIABATE » retrouve « Diabaté »,
     * « NGUETTIA » retrouve « N'Guettia », « marie-daniel » retrouve
     * « Marie-Daniel ».
     */
    public function texteRecherche(ESBTPRdvReservation $reservation): string
    {
        $second = $this->second($reservation);
        $eleve = $reservation->demande?->etudiant;

        return self::normaliser(implode(' ', [
            $reservation->nom, $reservation->prenoms, $reservation->telephone,
            $this->reference($reservation),
            $second['nom'] ?? '', $second['telephone'] ?? '',
            $eleve?->nom, $eleve?->prenoms, $eleve?->matricule,
        ]));
    }

    /** Minuscules sans accents ; apostrophes retirees, tirets et blancs reduits a une espace. */
    public static function normaliser(string $texte): string
    {
        $texte = mb_strtolower(Str::ascii($texte), 'UTF-8');
        $texte = str_replace(["'", '`'], '', $texte);

        return trim((string) preg_replace('/[\s\-]+/u', ' ', $texte));
    }

    public function reference(ESBTPRdvReservation $reservation): string
    {
        return (string) ($reservation->porteur()?->referencePubliqueAffichee() ?? '');
    }
}
