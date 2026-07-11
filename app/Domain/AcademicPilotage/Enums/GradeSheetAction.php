<?php

namespace App\Domain\AcademicPilotage\Enums;

enum GradeSheetAction: string
{
    case START_ENTRY = 'start_entry';
    case SUBMIT = 'submit';
    case RECEIVE = 'receive';
    case FINISH_ENTRY = 'finish_entry';
    case CONTROL = 'control';
    case VALIDATE = 'validate';
    case REQUEST_CORRECTION = 'request_correction';
    case REOPEN = 'reopen';
    case REJECT = 'reject';
    case CANCEL = 'cancel';

    public function requiresReason(): bool
    {
        return in_array($this, [
            self::VALIDATE,
            self::REQUEST_CORRECTION,
            self::REOPEN,
            self::REJECT,
            self::CANCEL,
        ], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::START_ENTRY => 'Démarrer la saisie',
            self::SUBMIT => 'Soumettre',
            self::RECEIVE => 'Réceptionner',
            self::FINISH_ENTRY => 'Terminer la saisie',
            self::CONTROL => 'Contrôler',
            self::VALIDATE => 'Valider',
            self::REQUEST_CORRECTION => 'Demander une correction',
            self::REOPEN => 'Rouvrir',
            self::REJECT => 'Rejeter',
            self::CANCEL => 'Annuler',
        };
    }

    public function authorizationAbility(): string
    {
        return match ($this) {
            self::SUBMIT => 'submit',
            self::RECEIVE => 'receive',
            self::START_ENTRY, self::FINISH_ENTRY => 'enter',
            self::CONTROL, self::REQUEST_CORRECTION => 'control',
            self::VALIDATE, self::REOPEN, self::REJECT, self::CANCEL => 'validate',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
