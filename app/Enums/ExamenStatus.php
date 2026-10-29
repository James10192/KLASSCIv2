<?php

namespace App\Enums;

/**
 * Statuts du workflow d'un examen planifié UEMOA.
 *
 * draft        → brouillon (admin scolarité)
 * planned      → planifié (convocation émise, surveillants assignés)
 * in_progress  → en cours (jour J, copies en cours)
 * completed    → terminé (épreuve passée, notes saisies)
 * notes_locked → notes verrouillées (anti-tampering post-jury)
 * cancelled    → annulé (force majeure, report)
 */
enum ExamenStatus: string
{
    case DRAFT = 'draft';
    case PLANNED = 'planned';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case NOTES_LOCKED = 'notes_locked';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::PLANNED => 'Planifié',
            self::IN_PROGRESS => 'En cours',
            self::COMPLETED => 'Terminé',
            self::NOTES_LOCKED => 'Notes verrouillées',
            self::CANCELLED => 'Annulé',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT => 'exp-status--draft',
            self::PLANNED => 'exp-status--planned',
            self::IN_PROGRESS => 'exp-status--in_progress',
            self::COMPLETED => 'exp-status--completed',
            self::NOTES_LOCKED => 'exp-status--notes_locked',
            self::CANCELLED => 'exp-status--cancelled',
        };
    }

    public function icon(): ?string
    {
        return match ($this) {
            self::DRAFT => 'fa-pen-to-square',
            self::PLANNED => 'fa-calendar-check',
            self::IN_PROGRESS => 'fa-spinner',
            self::COMPLETED => 'fa-check-circle',
            self::NOTES_LOCKED => 'fa-lock',
            self::CANCELLED => 'fa-ban',
        };
    }

    /** Statuts modifiables manuellement par scolarité (exclus : notes_locked qui se déclenche via verrouillage). */
    public static function editable(): array
    {
        return [
            self::DRAFT,
            self::PLANNED,
            self::IN_PROGRESS,
            self::COMPLETED,
            self::CANCELLED,
        ];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** ['value' => 'Label FR'] pour le picker premium. */
    public static function selectOptions(): array
    {
        $opts = [];
        foreach (self::cases() as $case) {
            $opts[$case->value] = $case->label();
        }
        return $opts;
    }

    /** ['value' => 'Label FR'] sans notes_locked (réservé au verrouillage automatique). */
    public static function editableOptions(): array
    {
        $opts = [];
        foreach (self::editable() as $case) {
            $opts[$case->value] = $case->label();
        }
        return $opts;
    }

    /** Label depuis une string raw (rétrocompat vues legacy). */
    public static function labelFor(?string $raw): string
    {
        $case = self::tryFrom((string) $raw);
        return $case?->label() ?? ucfirst(str_replace('_', ' ', (string) $raw));
    }

    public static function badgeClassFor(?string $raw): string
    {
        $case = self::tryFrom((string) $raw);
        return $case?->badgeClass() ?? 'exp-status--draft';
    }
}
