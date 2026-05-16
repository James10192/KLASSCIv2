<?php

namespace App\Services\LMD\Tpe;

use App\Enums\TpeDeclarationStatut;

/**
 * Option 2 — Journal auto-déclaratif sans intervention prof.
 *
 * L'étudiant déclare ses heures → elles sont marquées VALIDE immédiatement.
 * Pas de notification prof, pas d'écran de validation.
 *
 * Strategy par défaut (Setting `tpe.validation.enabled = false`).
 */
class AutoValidateStrategy implements TpeValidationStrategy
{
    public function initialStatut(): TpeDeclarationStatut
    {
        return TpeDeclarationStatut::VALIDE;
    }

    public function requiresTeacherAction(): bool
    {
        return false;
    }
}
