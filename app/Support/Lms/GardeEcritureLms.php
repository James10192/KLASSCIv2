<?php

namespace App\Support\Lms;

use App\Models\ESBTPEvaluation;
use App\Models\ESBTPSeanceCours;
use App\Models\User;

/**
 * Qui peut ECRIRE dans KLASSCI par l'API du LMS.
 *
 * Trois routes d'ecriture de `LMSDataController` (notes d'une evaluation,
 * presences d'une visio, rappels de seance) ne controlaient que la connexion :
 * or le login du LMS donne un jeton a TOUS les utilisateurs, eleves compris.
 * Un eleve pouvait donc ecrire ses propres notes. Chaque ecriture passe
 * desormais ici.
 *
 * Est autorise, dans cet ordre :
 *  1. le jeton serveur du LMS, s'il porte le droit de l'ecriture ;
 *  2. l'administration, la coordination, la direction des etudes ;
 *  3. l'enseignant de l'evaluation ou de la seance.
 * Jamais un eleve, jamais un compte sans lien avec l'objet.
 */
final class GardeEcritureLms
{
    private const ENCADREMENT = ['admin.access', 'identity.coordinate', 'identity.direct_studies'];

    public static function peutNoter(?User $utilisateur, ESBTPEvaluation $evaluation): bool
    {
        if (! $utilisateur) {
            return false;
        }
        if (JetonServeurLms::estServeur($utilisateur)) {
            return JetonServeurLms::peut($utilisateur, JetonServeurLms::NOTES);
        }
        if ($utilisateur->hasAnyPermission(self::ENCADREMENT)) {
            return true;
        }
        if (! $utilisateur->can('identity.teach')) {
            return false;
        }

        // Meme regle que LMSWriteController::saveEvaluationNotes() : l'enseignant
        // de la matiere pour l'annee de l'evaluation, ou l'enseignant designe.
        return (int) $evaluation->enseignant_id === (int) $utilisateur->id
            || ($evaluation->matiere && $evaluation->matiere->enseignants()
                ->where('enseignant_id', $utilisateur->id)
                ->where('esbtp_enseignant_matiere.annee_universitaire_id', $evaluation->annee_universitaire_id)
                ->where('esbtp_enseignant_matiere.is_active', true)
                ->exists());
    }

    public static function peutGererLaSeance(?User $utilisateur, ESBTPSeanceCours $seance): bool
    {
        if (! $utilisateur) {
            return false;
        }
        if (JetonServeurLms::estServeur($utilisateur)) {
            return JetonServeurLms::peut($utilisateur, JetonServeurLms::PRESENCES);
        }
        if ($utilisateur->hasAnyPermission(self::ENCADREMENT)) {
            return true;
        }
        if (! $utilisateur->can('identity.teach')) {
            return false;
        }

        $profil = $utilisateur->teacherProfile;

        return $profil !== null && (int) $seance->teacher_id === (int) $profil->id;
    }
}
