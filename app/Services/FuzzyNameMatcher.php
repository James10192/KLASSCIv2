<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FuzzyNameMatcher
{
    /**
     * Score et trie une collection d'éléments en fonction d'une requête de recherche.
     *
     * @param  string          $search   Terme recherché.
     * @param  \Illuminate\Support\Collection  $items   Collection de modèles déjà chargés.
     * @param  callable        $resolver Callback qui retourne une liste de chaînes à comparer.
     * @param  array           $options  Options (threshold, boosts, limit).
     * @return \Illuminate\Support\Collection
     */
    public function match(string $search, Collection $items, callable $resolver, array $options = []): Collection
    {
        $search = trim($search);

        if ($search === '') {
            return $items->map(function ($item) {
                $item->fuzzy_score = 100;
                return $item;
            });
        }

        $threshold = $options['threshold'] ?? 35;
        $limit = $options['limit'] ?? null;
        $boosts = $options['boosts'] ?? [];

        $searchTokens = $this->tokenize($search);
        if (empty($searchTokens)) {
            return collect();
        }

        $normalizedSearch = $this->normalizeString($search);

        $scored = $items->map(function ($item) use ($resolver, $searchTokens, $normalizedSearch, $boosts) {
            $targets = (array) $resolver($item);
            $score = $this->scoreTargets($searchTokens, $normalizedSearch, $targets, $boosts);
            $score = round(min(100, $score), 2);

            if (is_array($item)) {
                $item['fuzzy_score'] = $score;
                return $item;
            }

            if (is_object($item)) {
                $item->fuzzy_score = $score;
            }

            return $item;
        })->filter(function ($item) use ($threshold) {
            $score = is_array($item)
                ? ($item['fuzzy_score'] ?? 0)
                : ($item->fuzzy_score ?? 0);

            return $score >= $threshold;
        })->sortByDesc(function ($item) {
            return is_array($item)
                ? ($item['fuzzy_score'] ?? 0)
                : ($item->fuzzy_score ?? 0);
        })
        ->values();

        if ($limit !== null) {
            return $scored->take($limit);
        }

        return $scored;
    }

    /**
     * Calcule le score d'une liste de champs cibles vis-à-vis des tokens recherchés.
     */
    protected function scoreTargets(array $searchTokens, string $normalizedSearch, array $targets, array $boosts): float
    {
        $best = 0.0;

        foreach ($targets as $key => $value) {
            if (is_null($value)) {
                continue;
            }

            if (is_array($value)) {
                $value = implode(' ', array_filter($value, fn ($part) => filled($part)));
            }

            $value = (string) $value;
            $normalizedTarget = $this->normalizeString($value);

            if ($normalizedTarget === '') {
                continue;
            }

            $score = 0.0;

            if ($normalizedTarget === $normalizedSearch) {
                $score = 100.0;
            } else {
                $tokens = $this->tokenize($value);
                if (empty($tokens)) {
                    continue;
                }
                $score = $this->calculateTokenScore($searchTokens, $tokens);

                if (str_contains($normalizedTarget, $normalizedSearch)) {
                    $score = max($score, 92.0);
                }
            }

            $boostKey = is_string($key) ? $key : null;
            if ($boostKey && isset($boosts[$boostKey])) {
                $score += $boosts[$boostKey];
            }

            if (isset($boosts['*'])) {
                $score += $boosts['*'];
            }

            $best = max($best, $score);
        }

        return $best;
    }

    /**
     * Transforme une chaîne en tokens nettoyés.
     */
    protected function tokenize(string $value): array
    {
        $value = $this->normalizeString($value);
        if ($value === '') {
            return [];
        }

        $parts = preg_split('/\s+/u', $value) ?: [];
        $parts = array_filter($parts, fn ($part) => $part !== '');

        return array_values(array_unique($parts));
    }

    /**
     * Normalise une chaîne de caractères pour comparaison.
     */
    protected function normalizeString(string $value): string
    {
        $value = Str::ascii($value);
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9\s]/', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim($value);
    }

    /**
     * Calcule un score entre deux listes de tokens (0 à 100).
     *
     * Algorithme token-coverage : pour chaque token de recherche, trouve le
     * meilleur match parmi les tokens candidats (exact > prefix > contains >
     * levenshtein > similar_text). Order-independent par design.
     */
    protected function calculateTokenScore(array $searchTokens, array $candidateTokens): float
    {
        if (empty($searchTokens) || empty($candidateTokens)) {
            return 0.0;
        }

        // Score chaque search token contre tous les candidate tokens
        $tokenScores = [];
        foreach ($searchTokens as $searchToken) {
            $best = 0.0;
            $searchLen = strlen($searchToken);

            foreach ($candidateTokens as $candidateToken) {
                $candidateLen = strlen($candidateToken);

                // 1. Exact match
                if ($searchToken === $candidateToken) {
                    $best = 100.0;
                    break;
                }

                // 2. Prefix match (l'un commence par l'autre)
                if (str_starts_with($candidateToken, $searchToken) || str_starts_with($searchToken, $candidateToken)) {
                    $best = max($best, 90.0);
                    continue;
                }

                // 3. Contains (l'un contient l'autre)
                if (str_contains($candidateToken, $searchToken) || str_contains($searchToken, $candidateToken)) {
                    $best = max($best, 85.0);
                    continue;
                }

                // 4. Levenshtein (seulement pour tokens > 2 chars — les tokens courts comme "ME", "DE" sont trop sensibles)
                if ($searchLen > 2 && $candidateLen > 2) {
                    $distance = levenshtein($searchToken, $candidateToken);
                    if ($distance <= 1) {
                        $best = max($best, 80.0);
                        continue;
                    }
                    if ($distance <= 2) {
                        $best = max($best, 70.0);
                        continue;
                    }
                }

                // 5. Fallback : similar_text pour les cas restants
                similar_text($searchToken, $candidateToken, $similarity);
                if ($similarity > 70.0) {
                    $best = max($best, $similarity * 0.8);
                }
            }

            $tokenScores[] = $best;
        }

        // Score = moyenne des meilleurs scores par token
        $coverageScore = array_sum($tokenScores) / count($tokenScores);

        // Bonus bidirectionnel : si tous les candidate tokens sont aussi couverts
        $reverseCovered = 0;
        foreach ($candidateTokens as $candidateToken) {
            foreach ($searchTokens as $searchToken) {
                if ($searchToken === $candidateToken || str_starts_with($candidateToken, $searchToken) || str_contains($candidateToken, $searchToken)) {
                    $reverseCovered++;
                    break;
                }
            }
        }
        $reverseRatio = $reverseCovered / count($candidateTokens);
        $bidirectionalBonus = min($reverseRatio * 10, 10.0);

        return min(100.0, $coverageScore * 0.9 + $bidirectionalBonus);
    }
}
