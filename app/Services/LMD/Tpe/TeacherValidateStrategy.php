<?php

namespace App\Services\LMD\Tpe;

use App\Enums\TpeDeclarationStatut;

/**
 * Option 3 — Workflow validation prof activé.
 *
 * L'étudiant déclare ses heures → statut EN_ATTENTE.
 * L'enseignant principal de l'ECUE doit valider ou rejeter via
 * /esbtp/tpe-validation.
 *
 * Strategy activée quand Setting `tpe.validation.enabled = true`.
 *
 * IMPORTANT — opt-in dormant : code 100% présent en DB/code mais inactif
 * tant que l'école ne flip pas le toggle. Aucun risque par défaut, dispo
 * immédiatement si une école différenciée le demande.
 */
class TeacherValidateStrategy implements TpeValidationStrategy
{
    public function initialStatut(): TpeDeclarationStatut
    {
        return TpeDeclarationStatut::EN_ATTENTE;
    }

    public function requiresTeacherAction(): bool
    {
        return true;
    }
}
