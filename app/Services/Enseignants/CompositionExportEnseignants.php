<?php

namespace App\Services\Enseignants;

use Illuminate\Validation\ValidationException;

/**
 * Une seule definition des colonnes et rubriques pour PDF et Excel.
 * Les filtres decident QUOI lire ; ces options ne changent que la presentation.
 */
final class CompositionExportEnseignants
{
    public static function definitions(): array
    {
        return [
            'liste' => [
                'colonnes' => ['nom' => 'Nom du professeur', 'systeme' => 'Système', 'matiere' => 'Matière', 'ue' => 'UE LMD', 'classe' => 'Classe', 'filiere' => 'Filière', 'niveau' => "Niveau d'études", 'parcours' => 'Parcours LMD', 'semestre' => 'Semestre', 'source' => 'Source du nom'],
                'defaut' => ['nom'],
                'regroupements' => ['matiere' => 'Par matière', 'ue' => 'Par UE LMD', 'parcours' => 'Par parcours LMD', 'classe' => 'Par classe', 'filiere' => 'Par filière', 'niveau' => 'Par niveau', 'semestre' => 'Par semestre', 'systeme' => 'Par système', 'aucun' => 'Sans regroupement'],
                'regroupement' => 'matiere',
            ],
            'matieres' => [
                'colonnes' => ['nom' => 'Nom du professeur', 'systeme' => 'Système', 'matiere' => 'Matière', 'ue' => 'UE LMD', 'parcours' => 'Parcours LMD', 'classes' => 'Classes et semestres', 'semestres' => 'Semestres', 'nb_classes' => 'Nombre de classes', 'S1' => 'S1 BTS', 'S2' => 'S2 BTS', 'total' => 'Affectations'],
                'defaut' => ['nom', 'classes', 'S1', 'S2', 'total'],
                'regroupements' => ['matiere' => 'Par matière', 'ue' => 'Par UE LMD', 'parcours' => 'Par parcours LMD', 'systeme' => 'Par système', 'aucun' => 'Sans regroupement'],
                'regroupement' => 'matiere',
            ],
            'affectations' => [
                'colonnes' => ['nom' => 'Professeur saisi', 'systeme' => 'Système', 'matiere' => 'Matière', 'ue' => 'UE LMD', 'parcours' => 'Parcours LMD', 'classe' => 'Classe', 'filiere' => 'Filière', 'niveau' => 'Niveau', 'semestre' => 'Semestre', 'source' => 'Source du nom'],
                'defaut' => ['classe', 'matiere', 'nom'],
                'regroupements' => ['semestre' => 'Par semestre', 'matiere' => 'Par matière', 'ue' => 'Par UE LMD', 'parcours' => 'Par parcours LMD', 'systeme' => 'Par système', 'classe' => 'Par classe', 'niveau' => 'Par niveau', 'filiere' => 'Par filière', 'aucun' => 'Sans regroupement'],
                'regroupement' => 'semestre',
            ],
            'specialisations' => [
                'colonnes' => ['nom' => 'Enseignant', 'systeme' => 'Système', 'matricule' => 'Matricule', 'regime' => 'Régime', 'statut' => 'Statut', 'telephone' => 'Téléphone', 'email' => 'Email', 'origine' => 'Origine'],
                'defaut' => ['nom', 'matricule', 'regime', 'statut', 'telephone', 'email'],
                'regroupements' => ['specialisation' => 'Par spécialisation', 'systeme' => 'Par système', 'aucun' => 'Sans regroupement'],
                'regroupement' => 'specialisation',
            ],
            'annuaire' => [
                'colonnes' => ['nom' => 'Enseignant', 'systeme' => 'Système', 'matricule' => 'Matricule', 'specialisation' => 'Spécialisation', 'regime' => 'Régime', 'statut' => 'Statut', 'telephone' => 'Téléphone', 'email' => 'Email'],
                'defaut' => ['nom', 'matricule', 'specialisation', 'regime', 'statut', 'telephone', 'email'],
                'regroupements' => ['aucun' => 'Sans regroupement', 'systeme' => 'Par système', 'specialisation' => 'Par spécialisation'],
                'regroupement' => 'aucun',
            ],
            'rapprochement' => [
                'colonnes' => ['nom' => 'Nom saisi', 'systeme' => 'Système', 'classes' => 'Classes', 'total' => 'Affectations', 'origine' => 'Origine'],
                'defaut' => ['nom', 'classes', 'total'],
                'regroupements' => ['initiale' => 'Par initiale', 'systeme' => 'Par système', 'aucun' => 'Sans regroupement'],
                'regroupement' => 'initiale',
            ],
            'synthese' => [
                'colonnes' => ['classe' => 'Classe', 'systeme' => 'Système', 'semestres' => 'Semestres', 'S1' => 'S1 BTS', 'S2' => 'S2 BTS', 'total' => 'Affectations', 'enseignants_saisis' => 'Noms distincts'],
                'defaut' => ['classe', 'S1', 'S2', 'total', 'enseignants_saisis'],
                'regroupements' => ['aucun' => 'Sans regroupement', 'systeme' => 'Par système'],
                'regroupement' => 'aucun',
            ],
        ];
    }

    public static function options(string $vue, ?array $colonnes, ?string $regroupement): array
    {
        $def = self::definitions()[$vue] ?? null;
        if ($def === null) throw ValidationException::withMessages(['vue' => 'Document inconnu.']);
        $selection = $colonnes ?? $def['defaut'];
        if ($selection === [] || count($selection) > count($def['colonnes'])
            || count($selection) !== count(array_unique($selection))
            || array_diff($selection, array_keys($def['colonnes'])) !== []) {
            throw ValidationException::withMessages(['colonnes' => 'Choisissez au moins une colonne valide pour ce document.']);
        }
        $groupe = $regroupement ?? $def['regroupement'];
        if (! array_key_exists($groupe, $def['regroupements'])) {
            throw ValidationException::withMessages(['regroupement' => 'Ce regroupement ne convient pas au document sélectionné.']);
        }
        if ($vue === 'matieres' && $groupe === 'aucun' && ! in_array('matiere', $selection, true)) {
            throw ValidationException::withMessages([
                'colonnes' => 'Sans classement par matière, conservez la colonne Matière pour identifier les affectations.',
            ]);
        }
        // Ordre stable choisi dans le formulaire, jamais de colonne arbitraire.
        return ['colonnes' => array_values($selection), 'regroupement' => $groupe,
            'personnalise' => $vue === 'liste'
                || ($selection !== $def['defaut'] || $groupe !== $def['regroupement']),
        ];
    }

    public static function creer(array $rows, array $affectations, string $vue, array $options): array
    {
        $def = self::definitions()[$vue];
        $groupBy = $options['regroupement'];
        $colonnes = $options['colonnes'];
        $presentation = PresentationAnnuairePdf::preparer($rows, $affectations, $vue);
        $donnees = match ($vue) {
            'liste', 'affectations' => $affectations,
            'matieres' => self::lignesMatieres($presentation),
            'specialisations' => self::lignesFiches($presentation, true),
            'annuaire' => self::lignesFiches($presentation, false),
            'rapprochement' => array_map(fn ($r) => [
                'nom' => $r['nom'], 'systeme' => $r['systeme'] ?? 'BTS',
                'classes' => $r['classes'] ?? '—',
                'total' => $r['affectations_nombre'] ?? 0, 'origine' => $r['origine'] ?? '—',
                'initiale' => mb_strtoupper(mb_substr(trim($r['nom']), 0, 1, 'UTF-8'), 'UTF-8'),
            ], $presentation['saisies']),
            default => $presentation['repartition'],
        };

        $groupes = [];
        foreach ($donnees as $ligne) {
            $titre = match ($groupBy) {
                'aucun' => 'Enseignants sélectionnés',
                'specialisation' => ($ligne['specialisation'] ?? '—') === '—'
                    ? 'À classer — spécialisation non renseignée' : (string) $ligne['specialisation'],
                default => trim((string) ($ligne[$groupBy] ?? '')) ?: 'Non renseigné',
            };
            $systeme = (string) ($ligne['systeme'] ?? 'BTS');
            if (($options['separer_systemes'] ?? false) && $groupBy !== 'systeme') {
                $titre = $systeme.' — '.$titre;
                if ($systeme === 'LMD' && $groupBy === 'matiere') {
                    $titre .= ' · '.(string) ($ligne['ue'] ?? 'UE non précisée')
                        .' · '.(string) ($ligne['parcours'] ?? 'Parcours non précisé');
                }
            }
            $cle = mb_strtolower($titre, 'UTF-8');
            if ($groupBy === 'matiere') {
                $cle .= '|'.(string) ($ligne['matiere_id'] ?? '')
                    .'|'.($systeme === 'LMD' ? (string) ($ligne['parcours'] ?? '') : '');
            }
            if (! isset($groupes[$cle])) $groupes[$cle] = [
                'titre' => $titre, 'reference' => $ligne['matiere_id'] ?? null,
                'lignes' => [], 'cles' => [],
            ];
            $valeurs = [];
            foreach ($colonnes as $colonne) $valeurs[$colonne] = $ligne[$colonne] ?? '—';
            // La liste simplifiee ne repete pas la meme fiche dans une rubrique.
            // Si aucune rubrique : un seul nom (ou combinaison de colonnes).
            $unique = json_encode($valeurs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($vue === 'liste' && isset($groupes[$cle]['cles'][$unique])) continue;
            $groupes[$cle]['cles'][$unique] = true;
            $groupes[$cle]['lignes'][] = $valeurs;
        }
        $resultat = array_values($groupes);
        $titres = array_count_values(array_map(
            fn ($g) => mb_strtolower($g['titre'], 'UTF-8'), $resultat
        ));
        usort($resultat, fn ($a, $b) => strnatcasecmp($a['titre'], $b['titre']));
        foreach ($resultat as &$g) {
            if ($groupBy === 'matiere'
                && ($titres[mb_strtolower($g['titre'], 'UTF-8')] ?? 0) > 1
                && $g['reference'] !== null) {
                $g['titre'] .= ' · Réf. #'.$g['reference'];
            }
            usort($g['lignes'], fn ($a, $b) => strnatcasecmp(
                (string) ($a['nom'] ?? reset($a)), (string) ($b['nom'] ?? reset($b))
            ));
            unset($g['cles'], $g['reference']);
        }
        unset($g);
        $entetes = [];
        foreach ($colonnes as $key) $entetes[$key] = $def['colonnes'][$key];
        return [
            'groupes' => $resultat, 'entetes' => $entetes,
            'regroupement' => $groupBy, 'colonnes' => $colonnes,
            'total' => array_sum(array_map(fn ($g) => count($g['lignes']), $resultat)),
        ];
    }

    private static function lignesMatieres(array $presentation): array
    {
        $lignes = [];
        foreach ($presentation['matieresGroupes'] as $groupe) {
            foreach ($groupe['enseignants'] as $prof) {
                $lignes[] = [
                    'matiere' => $groupe['matiere'], 'matiere_id' => $groupe['matiere_id'] ?? null,
                    'systeme' => $groupe['systeme'] ?? 'BTS',
                    'ue' => $groupe['ue'] ?? '—', 'parcours' => $groupe['parcours'] ?? '—',
                    'nom' => $prof['nom'],
                    'semestres' => $prof['semestres'] ?? '—',
                    'classes' => $prof['classes'], 'nb_classes' => $prof['nombreClasses'],
                    'S1' => $prof['S1'], 'S2' => $prof['S2'], 'total' => $prof['total'],
                ];
            }
        }
        return $lignes;
    }

    private static function lignesFiches(array $presentation, bool $avecManuels): array
    {
        $lignes = $presentation['fiches'];
        if ($avecManuels) $lignes = array_merge($lignes, $presentation['saisies']);
        return array_map(fn ($r) => [
            'nom' => $r['nom'], 'systeme' => $r['systeme'] ?? 'BTS',
            'matricule' => $r['matricule'] ?? '—',
            'specialisation' => $r['specialisation'] ?? '—',
            'regime' => $r['regime'] ?? '—', 'statut' => $r['statut'] ?? '—',
            'telephone' => PresentationAnnuairePdf::contactValide((string) ($r['telephone'] ?? '')),
            'email' => PresentationAnnuairePdf::contactValide((string) ($r['email'] ?? ''), true),
            'origine' => $r['origine'] ?? '—',
        ], $lignes);
    }

    /** Limites prudentes en mm, selon libelles et quelques lignes de donnees. */
    public static function largeur(array $composition): int
    {
        $min = [
            'nom' => 57, 'systeme' => 20, 'matiere' => 44, 'ue' => 49,
            'parcours' => 46, 'source' => 39, 'semestres' => 34,
            'classe' => 35, 'classes' => 68,
            'filiere' => 42, 'niveau' => 34, 'semestre' => 20, 'matricule' => 29,
            'specialisation' => 45, 'regime' => 24, 'statut' => 19, 'telephone' => 32,
            'email' => 47, 'origine' => 35, 'S1' => 16, 'S2' => 16,
            'total' => 25, 'nb_classes' => 26, 'enseignants_saisis' => 24,
        ];
        $largeur = 0;
        foreach ($composition['colonnes'] as $col) $largeur += $min[$col] ?? 26;
        return $largeur;
    }

    public static function orientation(array $composition, string $choix): string
    {
        $largeur = self::largeur($composition);
        // A4, marges min. 10mm par cote ; laisser une marge confortable
        // aux tables et a la police des etablissements.
        if ($choix === 'portrait' && $largeur > 180) {
            throw ValidationException::withMessages([
                'orientation' => 'Les colonnes sélectionnées sont trop larges pour A4 portrait. Retirez des colonnes ou choisissez Automatique / Paysage.',
            ]);
        }
        if ($largeur > 265) {
            throw ValidationException::withMessages([
                'colonnes' => 'Les colonnes dépassent la largeur A4 paysage. Réduisez votre sélection.',
            ]);
        }
        return $choix === 'auto' ? ($largeur <= 180 ? 'portrait' : 'landscape')
            : ($choix === 'portrait' ? 'portrait' : 'landscape');
    }
}
