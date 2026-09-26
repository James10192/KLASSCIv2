<?php

namespace App\Domain\Admissions;

use App\Enums\StatutConvocationRdv;
use App\Models\ESBTPRdvReservation;
use App\Services\RendezVous\MotifsRemiseConvocation;

/**
 * Ce que l'accueil doit savoir de la convocation d'une famille : est-elle
 * arrivee dans sa boite, a-t-elle rebondi, faut-il appeler ?
 *
 * « Envoyee » ne veut dire que « le fournisseur a accepte » : seule la date de
 * remise (convocation_delivree_at, relue chez MailPulse par
 * SynchroStatutsConvocations) prouve que la famille l'a reçue. Un echec porte
 * son code distant, que MotifsRemiseConvocation range en rebond ou autre.
 */
final class EtatConvocation
{
    /**
     * @return array{texte: string, detail: string, ton: string} ton : succes, attente, alerte, echec, neutre
     */
    public static function pour(?ESBTPRdvReservation $r): array
    {
        if ($r === null) {
            return self::etat('Pas de convocation', 'aucun rendez-vous', 'neutre');
        }

        $statut = $r->convocation_statut;
        $quand = fn ($date) => $date ? $date->translatedFormat('j M').' à '.$date->format('H:i') : '';

        return match (true) {
            $statut === StatutConvocationRdv::Envoyee && $r->convocation_delivree_at !== null
                => self::etat('Convocation délivrée', $quand($r->convocation_delivree_at), 'succes'),
            $statut === StatutConvocationRdv::Envoyee
                => self::etat('Convocation envoyée', 'remise pas encore confirmée', 'attente'),
            $statut === StatutConvocationRdv::Echec && MotifsRemiseConvocation::famille($r->convocation_code_distant) === 'rebond'
                => self::etat('Courriel rebondi', 'famille à prévenir par téléphone', 'echec'),
            $statut === StatutConvocationRdv::Echec
                => self::etat('Envoi échoué', 'famille à prévenir par téléphone', 'echec'),
            $statut === StatutConvocationRdv::Telephone
                => self::etat('Prévenue par téléphone', $quand($r->convocation_envoyee_at), 'succes'),
            $statut === StatutConvocationRdv::SansEmail
                => self::etat('Sans adresse e-mail', 'famille à prévenir par téléphone', 'alerte'),
            $statut === StatutConvocationRdv::EnAttente
                => self::etat('Convocation en attente', 'part au prochain envoi', 'attente'),
            $statut === StatutConvocationRdv::SansObjet
                => self::etat('Sans objet', 'créneau passé avant l\'envoi', 'neutre'),
            default => self::etat('Convocation non suivie', 'réservation d\'avant le suivi des envois', 'neutre'),
        };
    }

    /** @return array{texte: string, detail: string, ton: string} */
    private static function etat(string $texte, string $detail, string $ton): array
    {
        return ['texte' => $texte, 'detail' => $detail, 'ton' => $ton];
    }
}
