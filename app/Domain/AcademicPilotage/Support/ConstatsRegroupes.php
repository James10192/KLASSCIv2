<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Support;

/**
 * Regroupe les constats du pré-contrôle par cause, et non par étudiant.
 *
 * Vingt-six fois la même phrase n'apprend rien : quand toute une classe bute
 * sur la même cause (fiches non préparées, par exemple), l'écran doit dire UNE
 * fois quoi faire, et combien d'étudiants sont concernés.
 */
final class ConstatsRegroupes
{
    /**
     * @param  array<int, array{student_name: string, student_url?: ?string, issues?: array, warnings?: array}>  $lignes
     * @return array<int, array{code: string, severity: string, message: string, action_url: ?string, action_label: ?string, count: int, etudiants: array<int, array{nom: string, url: ?string}>}>
     */
    public static function depuis(array $lignes): array
    {
        $groupes = [];

        foreach ($lignes as $ligne) {
            $constats = array_merge($ligne['issues'] ?? [], $ligne['warnings'] ?? []);
            foreach ($constats as $constat) {
                $cle = ($constat['code'] ?? 'autre').'|'.($constat['severity'] ?? 'blocking');
                $groupes[$cle] ??= [
                    'code' => (string) ($constat['code'] ?? 'autre'),
                    'severity' => (string) ($constat['severity'] ?? 'blocking'),
                    // Le message propre à un étudiant (« 3 notes sur 5 ») ne
                    // peut pas servir de titre au groupe : on garde le premier,
                    // et l'écran montre le détail sous chaque nom.
                    'message' => (string) ($constat['message'] ?? ''),
                    'action_url' => $constat['action_url'] ?? null,
                    'action_label' => $constat['action_label'] ?? null,
                    'count' => 0,
                    'etudiants' => [],
                ];
                $groupes[$cle]['count']++;
                // Le nom et, si possible, le lien vers la fiche : on clique, on y est.
                $groupes[$cle]['etudiants'][] = [
                    'nom' => (string) $ligne['student_name'],
                    'url' => $ligne['student_url'] ?? null,
                ];
            }
        }

        // Les blocages d'abord, puis le groupe le plus nombreux.
        usort($groupes, fn (array $a, array $b): int => [$a['severity'] !== 'blocking', -$a['count']]
            <=> [$b['severity'] !== 'blocking', -$b['count']]);

        return array_values($groupes);
    }
}
