<?php

namespace App\Domain\Assistant\Outils\Presentation;

class AfficherGraphique extends OutilDePresentation
{
    private const TYPES = ['barres', 'courbe', 'anneau'];

    public function name(): string
    {
        return 'afficher_graphique';
    }

    public function libelle(): string
    {
        return 'Préparation du graphique…';
    }

    public function description(): string
    {
        return "Affiche un graphique à l'utilisateur à partir de chiffres que tu as DÉJÀ obtenus par un outil de données. "
            . "À utiliser pour une évolution dans le temps (courbe ou barres), une comparaison entre catégories (barres) "
            . "ou une répartition d'un total (anneau, 6 parts au plus). N'invente jamais une valeur : chaque nombre doit venir "
            . "d'un résultat d'outil de cet échange. Ne l'appelle pas pour moins de 3 valeurs : une phrase suffit.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'type' => ['type' => 'string', 'enum' => self::TYPES, 'description' => 'barres, courbe (évolution) ou anneau (répartition).'],
                'titre' => ['type' => 'string', 'description' => 'Titre court, ex. « Encaissements des six derniers mois ».'],
                'libelles' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Étiquettes de l\'axe ou des parts, ex. ["avr.", "mai", "juin"].'],
                'series' => [
                    'type' => 'array',
                    'description' => 'Une ou plusieurs séries, chacune avec autant de valeurs que de libellés.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'nom' => ['type' => 'string'],
                            'valeurs' => ['type' => 'array', 'items' => ['type' => 'number']],
                        ],
                        'required' => ['nom', 'valeurs'],
                    ],
                ],
                'unite' => ['type' => 'string', 'description' => 'Unité des valeurs, ex. « FCFA », « % », « étudiants ». Facultatif.'],
            ],
            'required' => ['type', 'titre', 'libelles', 'series'],
        ];
    }

    protected function widget(array $args): array|string
    {
        $type = in_array($args['type'] ?? '', self::TYPES, true) ? $args['type'] : 'barres';
        $libelles = array_values(array_map(fn ($l) => $this->texte($l, 40), (array) ($args['libelles'] ?? [])));
        $n = count($libelles);
        if ($n < 2 || $n > 36) {
            return 'Il faut entre 2 et 36 libellés.';
        }

        $series = [];
        foreach (array_slice((array) ($args['series'] ?? []), 0, 4) as $serie) {
            $valeurs = array_values((array) ($serie['valeurs'] ?? []));
            if (count($valeurs) !== $n) {
                return "La série « " . $this->texte($serie['nom'] ?? '', 40) . " » doit avoir {$n} valeurs, une par libellé.";
            }
            foreach ($valeurs as $v) {
                if (!is_numeric($v)) {
                    return 'Toutes les valeurs doivent être des nombres (sans unité ni espace).';
                }
            }
            $series[] = ['nom' => $this->texte($serie['nom'] ?? 'Série', 60), 'valeurs' => array_map('floatval', $valeurs)];
        }
        if ($series === []) {
            return 'Il faut au moins une série de valeurs.';
        }
        if ($type === 'anneau' && (count($series) > 1 || $n > 8)) {
            return 'Un anneau ne prend qu\'une série et 8 parts au plus. Utilise des barres.';
        }

        $unite = isset($args['unite']) ? $this->texte($args['unite'], 20) : null;

        return [
            'kind' => 'graphique',
            'type' => $type,
            'titre' => $this->texte($args['titre'] ?? ''),
            'libelles' => $libelles,
            'series' => $series,
            'unite' => $unite !== '' ? $unite : null,
        ];
    }
}
