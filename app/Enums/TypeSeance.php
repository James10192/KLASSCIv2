<?php

namespace App\Enums;

enum TypeSeance: string
{
    case CM      = 'CM';
    case TD      = 'TD';
    case TP      = 'TP';
    case PROJET  = 'PROJET';
    case TPE     = 'TPE';
    case EXAMEN  = 'EXAMEN';
    case AUTRE   = 'AUTRE';

    public function label(): string
    {
        return match($this) {
            self::CM     => 'Cours Magistral',
            self::TD     => 'Travaux Dirigés',
            self::TP     => 'Travaux Pratiques',
            self::PROJET => 'Projet',
            self::TPE    => 'Travail Personnel Étudiant',
            self::EXAMEN => 'Examen',
            self::AUTRE  => 'Autre',
        };
    }

    /** Returns all case values as a plain array (for Rule::in()). */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Map legacy type_seance strings to canonical enum values.
     * Conservative: ambiguous values ('cours') → AUTRE, not CM.
     */
    public static function fromLegacy(?string $raw): self
    {
        if ($raw === null || $raw === '') {
            return self::AUTRE;
        }

        $upper = strtoupper(trim($raw));

        return match($upper) {
            'CM'     => self::CM,
            'TD'     => self::TD,
            'TP'     => self::TP,
            'PROJET' => self::PROJET,
            'TPE'    => self::TPE,
            'EXAMEN' => self::EXAMEN,
            default  => self::AUTRE,
        };
    }

    /** Whether this type counts toward volume horaire tracking (CM/TD/TP). */
    public function isVolumeTracked(): bool
    {
        return in_array($this, [self::CM, self::TD, self::TP], true);
    }

    /** Returns ['VALUE' => 'Label'] array for <x-au-select> :options prop. */
    public static function selectOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }

    /**
     * Monochrome KLASSCI blue badge styles per case.
     * Returns ['VALUE' => ['bg' => rgba, 'color' => hex, 'border' => rgba, 'icon' => fa-icon]].
     *
     * Rule premium-redesign: l'icône distingue le type, pas la couleur (monochrome only).
     * EXAMEN garde le rouge sémantique (action/risque), TPE/AUTRE en muted.
     */
    public static function badgeStyles(): array
    {
        return [
            self::CM->value => [
                'bg'     => 'rgba(4, 83, 203, .14)',
                'color'  => '#033a8e',
                'border' => 'rgba(4, 83, 203, .3)',
                'icon'   => 'fa-chalkboard-user',
            ],
            self::TD->value => [
                'bg'     => 'rgba(4, 83, 203, .1)',
                'color'  => '#033a8e',
                'border' => 'rgba(4, 83, 203, .22)',
                'icon'   => 'fa-pen-ruler',
            ],
            self::TP->value => [
                'bg'     => 'rgba(4, 83, 203, .07)',
                'color'  => '#0453cb',
                'border' => 'rgba(4, 83, 203, .18)',
                'icon'   => 'fa-flask-vial',
            ],
            self::PROJET->value => [
                'bg'     => 'rgba(4, 83, 203, .06)',
                'color'  => '#0453cb',
                'border' => 'rgba(4, 83, 203, .15)',
                'icon'   => 'fa-diagram-project',
            ],
            self::TPE->value => [
                'bg'     => 'rgba(100, 116, 139, .1)',
                'color'  => '#475569',
                'border' => 'rgba(100, 116, 139, .22)',
                'icon'   => 'fa-user-pen',
            ],
            self::EXAMEN->value => [
                'bg'     => 'rgba(220, 38, 38, .1)',
                'color'  => '#b91c1c',
                'border' => 'rgba(220, 38, 38, .22)',
                'icon'   => 'fa-file-circle-check',
            ],
            self::AUTRE->value => [
                'bg'     => 'rgba(148, 163, 184, .14)',
                'color'  => '#475569',
                'border' => 'rgba(148, 163, 184, .28)',
                'icon'   => 'fa-circle-question',
            ],
        ];
    }

    /** Returns the badge style for this case (or AUTRE fallback). */
    public function badgeStyle(): array
    {
        return self::badgeStyles()[$this->value] ?? self::badgeStyles()[self::AUTRE->value];
    }

    /** Convenience: inline style string ready for `style="..."` attribute. */
    public function badgeInlineStyle(): string
    {
        $s = $this->badgeStyle();
        return sprintf(
            'background:%s;color:%s;border:1px solid %s;',
            $s['bg'],
            $s['color'],
            $s['border']
        );
    }

    /** Convenience: Font Awesome icon class for this case. */
    public function badgeIcon(): string
    {
        return $this->badgeStyle()['icon'];
    }
}
