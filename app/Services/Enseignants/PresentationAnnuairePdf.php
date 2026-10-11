<?php

namespace App\Services\Enseignants;

use Illuminate\Support\Str;

/**
 * Vue de travail pour l'édition de l'annuaire : aucune écriture, aucun
 * rapprochement automatique. Les noms des bulletins restent fidèles à la saisie.
 */
final class PresentationAnnuairePdf
{
    public static function preparer(array $rows, array $affectations, string $vue): array
    {
        $enregistres = [];
        $saisies = [];
        foreach ($rows as $row) {
            if (str_starts_with((string) ($row['origine'] ?? 'Enregistré'), 'Enregistré')) {
                $row['email_affiche'] = self::contactValide((string) ($row['email'] ?? ''), true);
                $row['telephone_affiche'] = self::contactValide((string) ($row['telephone'] ?? ''));
                $enregistres[] = $row;
            } else {
                $saisies[] = $row;
            }
        }

        $parClasse = [];
        $parNom = [];
        foreach ($affectations as $item) {
            $classe = trim((string) $item['classe']);
            $semestre = (string) $item['semestre'];
            if (! isset($parClasse[$classe])) {
                $parClasse[$classe] = ['classe' => $classe, 'S1' => 0, 'S2' => 0, 'total' => 0, 'noms' => []];
            }
            $parClasse[$classe]['total']++;
            if (in_array($semestre, ['S1', 'S2'], true)) {
                $parClasse[$classe][$semestre]++;
            }
            $parClasse[$classe]['noms'][self::normaliser((string) $item['nom'])] = true;
            $cle = self::normaliser((string) $item['nom']);
            $parNom[$cle] = ($parNom[$cle] ?? 0) + 1;
        }
        foreach ($parClasse as &$classe) {
            $classe['enseignants_saisis'] = count($classe['noms']);
            unset($classe['noms']);
        }
        unset($classe);
        uksort($parClasse, 'strnatcasecmp');

        foreach ($saisies as &$ligne) {
            $ligne['affectations_nombre'] = $parNom[self::normaliser((string) $ligne['nom'])] ?? 0;
            $classes = array_filter(array_map('trim', explode(',', (string) ($ligne['classes'] ?? ''))));
            $ligne['classes_nombre'] = count($classes);
        }
        unset($ligne);
        usort($enregistres, fn ($a, $b) => strnatcasecmp($a['nom'], $b['nom']));
        usort($saisies, fn ($a, $b) => strnatcasecmp($a['nom'], $b['nom']));

        $groupesSaisies = [];
        foreach ($saisies as $ligne) {
            $initiale = mb_strtoupper(mb_substr(trim((string) $ligne['nom']), 0, 1, 'UTF-8'), 'UTF-8');
            if (! preg_match('/^\p{L}$/u', $initiale)) $initiale = '#';
            $groupesSaisies[$initiale][] = $ligne;
        }

        // Ces correspondances ne sont que des pistes, pas des identités validées.
        // Retirer uniquement la civilité au début du libellé, sans fuzzy matching.
        $groupesVariantes = [];
        foreach (array_merge($enregistres, $saisies) as $ligne) {
            $cle = self::normaliser((string) $ligne['nom'], true);
            if (mb_strlen($cle, 'UTF-8') < 9 || ! str_contains($cle, ' ')) continue;
            $groupesVariantes[$cle][(string) $ligne['nom']] = true;
        }
        $pistes = [];
        foreach ($groupesVariantes as $noms) {
            if (count($noms) < 2) continue;
            $pistes[] = implode(' / ', array_keys($noms));
        }
        sort($pistes, SORT_NATURAL | SORT_FLAG_CASE);

        $parSemestre = ['S1' => [], 'S2' => []];
        foreach ($affectations as $item) {
            if (isset($parSemestre[$item['semestre']])) {
                $parSemestre[$item['semestre']][] = $item;
            }
        }

        return [
            'vue' => $vue,
            'fiches' => $enregistres,
            'saisies' => $saisies,
            'saisiesGroupes' => $groupesSaisies,
            'repartition' => array_values($parClasse),
            'affectationsSemestres' => $parSemestre,
            'pistes' => $pistes,
            'nombreFiches' => count($enregistres),
            'nombreSaisies' => count($saisies),
            'nombreClasses' => count($parClasse),
        ];
    }

    public static function contactValide(string $valeur, bool $email = false): string
    {
        $valeur = trim($valeur);
        if ($valeur === '' || $valeur === '—' || preg_match('/neant|néant|aucun|non[ -]renseign|^n\/?a$/iu', $valeur)) {
            return '—';
        }
        if ($email && filter_var($valeur, FILTER_VALIDATE_EMAIL) === false) return '—';
        return $valeur;
    }

    private static function normaliser(string $nom, bool $sansTitre = false): string
    {
        $nom = Str::ascii(mb_strtolower(trim($nom), 'UTF-8'));
        $nom = trim(preg_replace('/\s+/u', ' ', preg_replace('/[.,;:()\-]+/u', ' ', $nom)));
        if ($sansTitre) {
            $nom = preg_replace('/^(?:(?:m|mr|mme|mlle|monsieur|madame|dr|docteur|prof|professeur)\s+)+/u', '', $nom);
        }
        return trim($nom);
    }
}
