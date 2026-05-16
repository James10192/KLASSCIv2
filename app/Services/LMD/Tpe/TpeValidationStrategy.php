<?php

namespace App\Services\LMD\Tpe;

use App\Enums\TpeDeclarationStatut;

/**
 * Strategy de validation des déclarations TPE.
 *
 * Pilotée via container binding dans AppServiceProvider, déterminée par
 * le Setting `tpe.validation.enabled` :
 *  - false → AutoValidateStrategy (Option 2 : journal seul, valide direct)
 *  - true  → TeacherValidateStrategy (Option 3 : workflow prof activé)
 *
 * Marcel garde `tpe.validation.enabled = false` par défaut pour ne pas
 * surcharger les profs. L'école l'active via /esbtp/settings sans redeploy.
 */
interface TpeValidationStrategy
{
    /**
     * Statut initial à appliquer à une déclaration nouvellement créée.
     */
    public function initialStatut(): TpeDeclarationStatut;

    /**
     * Indique si la Strategy nécessite une action manuelle du prof
     * (valide / rejete) après création.
     *
     * Sert à conditionner les notifications, le rendu UI (badge "en attente"),
     * et les routes de validation.
     */
    public function requiresTeacherAction(): bool;
}
