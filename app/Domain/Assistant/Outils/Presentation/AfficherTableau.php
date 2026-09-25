<?php

namespace App\Domain\Assistant\Outils\Presentation;

class AfficherTableau extends OutilDePresentation
{
    private const TYPES = ['texte', 'montant', 'nombre', 'date', 'lien', 'statut'];
    private const TONS = ['succes', 'alerte', 'danger', 'neutre'];

    public function name(): string
    {
        return 'afficher_tableau';
    }

    public function libelle(): string
    {
        return 'Mise en forme du tableau…';
    }

    public function description(): string
    {
        return "Affiche un tableau que tu construis toi-même : comparaison, croisement de plusieurs résultats, classement recalculé. "
            . "Ne l'utilise PAS pour montrer à nouveau le résultat d'un outil de données : celui-ci est déjà affiché. "
            . "Toutes les valeurs viennent de résultats d'outils de cet échange. Colonnes « lien » : {\"url\": \"/esbtp/...\", \"texte\": \"...\"}. "
            . "Colonnes « statut » : {\"texte\": \"En retard\", \"ton\": \"danger\"}. Montants en nombre brut, sans « FCFA ».";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'titre' => ['type' => 'string'],
                'colonnes' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'cle' => ['type' => 'string'],
                            'libelle' => ['type' => 'string'],
                            'type' => ['type' => 'string', 'enum' => self::TYPES],
                        ],
                        'required' => ['cle', 'libelle', 'type'],
                    ],
                ],
                'lignes' => ['type' => 'array', 'items' => ['type' => 'object'], 'description' => 'Objets indexés par les clés des colonnes.'],
            ],
            'required' => ['titre', 'colonnes', 'lignes'],
        ];
    }

    protected function widget(array $args): array|string
    {
        $colonnes = [];
        foreach (array_slice((array) ($args['colonnes'] ?? []), 0, 8) as $c) {
            $cle = $this->texte($c['cle'] ?? '', 40);
            if ($cle === '') {
                continue;
            }
            $colonnes[] = [
                'cle' => $cle,
                'libelle' => $this->texte($c['libelle'] ?? $cle, 40),
                'type' => in_array($c['type'] ?? '', self::TYPES, true) ? $c['type'] : 'texte',
            ];
        }
        if ($colonnes === []) {
            return 'Il faut au moins une colonne avec une clé.';
        }

        $lignes = [];
        foreach (array_slice((array) ($args['lignes'] ?? []), 0, 100) as $ligne) {
            if (!is_array($ligne)) {
                continue;
            }
            $propre = [];
            foreach ($colonnes as $c) {
                $propre[$c['cle']] = $this->cellule($ligne[$c['cle']] ?? null, $c['type']);
            }
            $lignes[] = $propre;
        }
        if ($lignes === []) {
            return 'Le tableau n\'a aucune ligne.';
        }

        return ['kind' => 'tableau', 'titre' => $this->texte($args['titre'] ?? ''), 'colonnes' => $colonnes, 'lignes' => $lignes, 'total' => count($lignes)];
    }

    private function cellule($valeur, string $type)
    {
        return match ($type) {
            'montant', 'nombre' => is_numeric($valeur) ? (float) $valeur : null,
            'lien' => is_array($valeur) && $this->urlInterne($valeur['url'] ?? null)
                ? ['url' => $valeur['url'], 'texte' => $this->texte($valeur['texte'] ?? $valeur['url'], 80)]
                : ($valeur === null ? null : $this->texte(is_array($valeur) ? ($valeur['texte'] ?? '') : $valeur, 80)),
            'statut' => [
                'texte' => $this->texte(is_array($valeur) ? ($valeur['texte'] ?? '') : $valeur, 40),
                'ton' => is_array($valeur) && in_array($valeur['ton'] ?? '', self::TONS, true) ? $valeur['ton'] : 'neutre',
            ],
            default => $valeur === null ? null : $this->texte(is_scalar($valeur) ? $valeur : json_encode($valeur), 160),
        };
    }

    /** Seuls les liens vers l'application : un modèle ne doit pas pouvoir afficher un lien externe. */
    private function urlInterne($url): bool
    {
        return is_string($url) && str_starts_with($url, '/') && !str_starts_with($url, '//');
    }
}
