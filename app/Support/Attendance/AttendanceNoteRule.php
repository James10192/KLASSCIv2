<?php

declare(strict_types=1);

namespace App\Support\Attendance;

/**
 * Règle de note d'assiduité entièrement configurable par tenant.
 *
 * Structure :
 * - zero_bonus : note appliquée quand AUCUNE heure d'absence (justifiée + non justifiée = 0).
 * - unjustified : tranches d'heures contiguës depuis 0 pour les absences NON justifiées.
 * - justified : tranches d'heures contiguës depuis 0 pour les absences justifiées.
 *
 * Chaque tranche = ['min' => float, 'max' => float|null, 'note' => float] avec
 * intervalle [min, max) ; la dernière tranche a max = null (infini).
 *
 * Résolution : si total d'heures == 0 → zero_bonus ; sinon
 * note(tranche non justifiée) + note(tranche justifiée) — les deux contributions
 * s'additionnent. Le barème justifié par défaut est une tranche unique [0, ∞) → 0,
 * ce qui reproduit exactement le comportement historique (le justifié n'influait
 * que sur le bonus zéro).
 *
 * Value object pur : aucune dépendance Laravel, testable sans DB.
 */
final class AttendanceNoteRule
{
    public const MAX_BRACKETS = 10;

    public const NOTE_MIN = -20.0;

    public const NOTE_MAX = 20.0;

    /**
     * @param  list<array{min: float, max: float|null, note: float}>  $unjustified
     * @param  list<array{min: float, max: float|null, note: float}>  $justified
     */
    private function __construct(
        public readonly float $zeroBonus,
        public readonly array $unjustified,
        public readonly array $justified,
    ) {}

    /**
     * Construit la règle depuis un tableau décodé (setting JSON).
     * Lève une InvalidArgumentException si la structure est invalide —
     * utiliser validationErrors() en amont pour un retour utilisateur.
     */
    public static function fromArray(array $data): self
    {
        $errors = self::validationErrors($data);
        if ($errors !== []) {
            throw new \InvalidArgumentException('Règle d\'assiduité invalide : '.implode(' ; ', $errors));
        }

        return new self(
            (float) $data['zero_bonus'],
            self::normalizeBrackets($data['unjustified']),
            self::normalizeBrackets($data['justified']),
        );
    }

    /**
     * Construit la règle depuis les 5 clés legacy (shape de
     * BulletinService::getAttendanceNoteSettings()). Reproduit bit-à-bit
     * l'ancien algorithme : tranches non justifiées [0,2) [2,3) [3,5) [5,∞),
     * justifié sans effet.
     *
     * @param  array{zero_unjustified: float, one_unjustified: float, two_unjustified: float, three_to_four_unjustified: float, five_or_more_unjustified: float}  $legacy
     */
    public static function fromLegacySettings(array $legacy): self
    {
        return new self(
            (float) ($legacy['zero_unjustified'] ?? 0.13),
            [
                ['min' => 0.0, 'max' => 2.0, 'note' => (float) ($legacy['one_unjustified'] ?? 0.0)],
                ['min' => 2.0, 'max' => 3.0, 'note' => (float) ($legacy['two_unjustified'] ?? -0.13)],
                ['min' => 3.0, 'max' => 5.0, 'note' => (float) ($legacy['three_to_four_unjustified'] ?? -0.39)],
                ['min' => 5.0, 'max' => null, 'note' => (float) ($legacy['five_or_more_unjustified'] ?? -0.50)],
            ],
            [
                ['min' => 0.0, 'max' => null, 'note' => 0.0],
            ],
        );
    }

    public function resolve(float $justifiedHours, float $unjustifiedHours): float
    {
        $justifiedHours = max(0.0, $justifiedHours);
        $unjustifiedHours = max(0.0, $unjustifiedHours);

        // Le bonus/malus « zéro » ne s'applique que sans AUCUNE heure d'absence.
        if (($justifiedHours + $unjustifiedHours) === 0.0) {
            return $this->zeroBonus;
        }

        return $this->noteForHours($this->unjustified, $unjustifiedHours)
            + $this->noteForHours($this->justified, $justifiedHours);
    }

    /** @return array{zero_bonus: float, unjustified: list<array{min: float, max: float|null, note: float}>, justified: list<array{min: float, max: float|null, note: float}>} */
    public function toArray(): array
    {
        return [
            'zero_bonus' => $this->zeroBonus,
            'unjustified' => $this->unjustified,
            'justified' => $this->justified,
        ];
    }

    /**
     * Valide la structure d'une règle décodée. Retourne la liste des erreurs
     * (vide = valide). Règles : tranches contiguës depuis 0, min < max,
     * dernière tranche ouverte (max null), notes bornées, 1 à MAX_BRACKETS tranches.
     *
     * @return list<string>
     */
    public static function validationErrors(mixed $data): array
    {
        if (! is_array($data)) {
            return ['la règle doit être un objet JSON'];
        }

        $errors = [];

        if (! isset($data['zero_bonus']) || ! is_numeric($data['zero_bonus'])) {
            $errors[] = 'zero_bonus doit être un nombre';
        } elseif ((float) $data['zero_bonus'] < self::NOTE_MIN || (float) $data['zero_bonus'] > self::NOTE_MAX) {
            $errors[] = sprintf('zero_bonus doit être entre %s et %s', self::NOTE_MIN, self::NOTE_MAX);
        }

        foreach (['unjustified' => 'non justifiées', 'justified' => 'justifiées'] as $key => $label) {
            $errors = array_merge($errors, self::bracketErrors($data[$key] ?? null, $label));
        }

        return $errors;
    }

    /** @return list<string> */
    private static function bracketErrors(mixed $brackets, string $label): array
    {
        if (! is_array($brackets) || $brackets === [] || array_is_list($brackets) === false) {
            return ["tranches {$label} : liste de tranches requise"];
        }

        if (count($brackets) > self::MAX_BRACKETS) {
            return ["tranches {$label} : maximum ".self::MAX_BRACKETS.' tranches'];
        }

        $errors = [];
        $expectedMin = 0.0;
        $lastIndex = count($brackets) - 1;

        foreach ($brackets as $i => $bracket) {
            $pos = $i + 1;

            if (! is_array($bracket) || ! isset($bracket['min']) || ! is_numeric($bracket['min']) || ! array_key_exists('max', $bracket) || ! isset($bracket['note']) || ! is_numeric($bracket['note'])) {
                $errors[] = "tranches {$label} : tranche {$pos} incomplète (min, max, note requis)";
                continue;
            }

            $min = (float) $bracket['min'];
            $max = $bracket['max'] === null ? null : (is_numeric($bracket['max']) ? (float) $bracket['max'] : false);
            $note = (float) $bracket['note'];

            if ($max === false) {
                $errors[] = "tranches {$label} : tranche {$pos} a un max non numérique";
                continue;
            }

            if (abs($min - $expectedMin) > 0.0001) {
                $errors[] = "tranches {$label} : tranche {$pos} doit commencer à {$expectedMin}h (tranches contiguës depuis 0)";
            }

            if ($i === $lastIndex) {
                if ($max !== null) {
                    $errors[] = "tranches {$label} : la dernière tranche doit être ouverte (jusqu'à l'infini)";
                }
            } else {
                if ($max === null) {
                    $errors[] = "tranches {$label} : seule la dernière tranche peut être ouverte";
                } elseif ($max <= $min) {
                    $errors[] = "tranches {$label} : tranche {$pos} doit avoir max > min";
                } else {
                    $expectedMin = $max;
                }
            }

            if ($note < self::NOTE_MIN || $note > self::NOTE_MAX) {
                $errors[] = sprintf('tranches %s : note de la tranche %d hors bornes (%s à %s)', $label, $pos, self::NOTE_MIN, self::NOTE_MAX);
            }
        }

        return $errors;
    }

    /**
     * @param  list<array{min: float, max: float|null, note: float}>  $brackets
     */
    private function noteForHours(array $brackets, float $hours): float
    {
        foreach ($brackets as $bracket) {
            if ($bracket['max'] === null || $hours < $bracket['max']) {
                return $bracket['note'];
            }
        }

        // Inatteignable sur une règle validée (dernière tranche ouverte).
        return 0.0;
    }

    /**
     * @param  array<int, array<string, mixed>>  $brackets
     * @return list<array{min: float, max: float|null, note: float}>
     */
    private static function normalizeBrackets(array $brackets): array
    {
        // L'ordre est déjà garanti par validationErrors() (contiguïté stricte depuis 0),
        // donc pas de tri ici — on caste juste les types.
        return array_values(array_map(
            fn (array $b): array => [
                'min' => (float) $b['min'],
                'max' => $b['max'] === null ? null : (float) $b['max'],
                'note' => (float) $b['note'],
            ],
            $brackets
        ));
    }
}
