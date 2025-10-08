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
     */
    protected function calculateTokenScore(array $searchTokens, array $candidateTokens): float
    {
        if (empty($searchTokens) || empty($candidateTokens)) {
            return 0.0;
        }

        $searchString = implode(' ', $searchTokens);
        $candidateString = implode(' ', $candidateTokens);

        similar_text($searchString, $candidateString, $overallSimilarity);

        $tokenSimilarities = [];
        foreach ($searchTokens as $searchToken) {
            $best = 0.0;
            foreach ($candidateTokens as $candidateToken) {
                similar_text($searchToken, $candidateToken, $similarity);
                $best = max($best, $similarity);
            }
            $tokenSimilarities[] = $best;
        }

        $averageTokens = !empty($tokenSimilarities)
            ? array_sum($tokenSimilarities) / count($tokenSimilarities)
            : 0.0;

        return ($overallSimilarity * 0.7) + ($averageTokens * 0.3);
    }
}
