<?php

namespace App\Services;

use App\Models\ESBTPEtudiant;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StudentDuplicateDetector
{
    /**
     * Recherche des étudiants susceptibles d'être des doublons.
     *
     * @param  string      $nom
     * @param  string      $prenoms
     * @param  string|null $dateNaissance Format Y-m-d
     * @param  string|null $sexe
     * @param  int         $limit
     * @return \Illuminate\Support\Collection
     */
    public function find(string $nom, string $prenoms, ?string $dateNaissance = null, ?string $sexe = null, int $limit = 10): Collection
    {
        $nom = trim($nom);
        $prenoms = trim($prenoms);

        if (mb_strlen($nom) < 2 && mb_strlen($prenoms) < 2) {
            return collect();
        }

        $inputTokens = $this->normalizeTokens($nom, $prenoms);

        if (empty($inputTokens)) {
            return collect();
        }

        $query = ESBTPEtudiant::query()
            ->select('id', 'nom', 'prenoms', 'matricule', 'date_naissance', 'sexe');

        $query->where(function ($q) use ($inputTokens) {
            foreach ($inputTokens as $token) {
                if (mb_strlen($token) < 2) {
                    continue;
                }
                $like = '%' . $token . '%';
                $q->orWhere('nom', 'like', $like)
                  ->orWhere('prenoms', 'like', $like);
            }
        });

        if ($dateNaissance) {
            $query->orWhereDate('date_naissance', $dateNaissance);
        }

        $candidates = $query
            ->limit(200)
            ->get();

        $results = $candidates->map(function (ESBTPEtudiant $candidate) use ($inputTokens, $dateNaissance, $sexe) {
            $candidateTokens = $this->normalizeTokens($candidate->nom, $candidate->prenoms);

            if (empty($candidateTokens)) {
                return null;
            }

            $score = $this->calculateScore($inputTokens, $candidateTokens, $dateNaissance, $candidate->date_naissance?->format('Y-m-d'), $sexe, $candidate->sexe);

            if ($score < 45) {
                return null;
            }

            $matchedTokens = array_values(array_intersect($inputTokens, $candidateTokens));

            return [
                'id' => $candidate->id,
                'full_name' => trim($candidate->prenoms . ' ' . $candidate->nom),
                'matricule' => $candidate->matricule,
                'date_naissance' => $candidate->date_naissance?->format('d/m/Y'),
                'sexe' => $candidate->sexe,
                'score' => min(99, round($score, 1)),
                'matched_tokens' => $matchedTokens,
            ];
        })->filter();

        return $results
            ->sortByDesc('score')
            ->values()
            ->take($limit);
    }

    /**
     * Normalise les noms/prénoms en tokens comparables.
     */
    protected function normalizeTokens(string ...$values): array
    {
        $tokens = [];

        foreach ($values as $value) {
            $value = mb_strtolower(trim($value));
            if ($value === '') {
                continue;
            }

            $parts = preg_split('/[\s\-]+/u', $value) ?: [];
            foreach ($parts as $part) {
                $ascii = Str::ascii($part);
                $ascii = mb_strtolower($ascii);
                $clean = preg_replace('/[^a-z]/', '', $ascii);
                if ($clean !== '') {
                    $tokens[] = $clean;
                }
            }
        }

        $tokens = array_values(array_unique($tokens));
        sort($tokens);

        return $tokens;
    }

    /**
     * Calcule un score de similarité entre 0 et 100.
     */
    protected function calculateScore(array $inputTokens, array $candidateTokens, ?string $inputDate, ?string $candidateDate, ?string $inputSexe, ?string $candidateSexe): float
    {
        $inputString = implode(' ', $inputTokens);
        $candidateString = implode(' ', $candidateTokens);

        similar_text($inputString, $candidateString, $overallSimilarity);

        $tokenSimilarities = [];
        foreach ($inputTokens as $inputToken) {
            $best = 0.0;
            foreach ($candidateTokens as $candidateToken) {
                similar_text($inputToken, $candidateToken, $similarity);
                $best = max($best, $similarity);
            }
            $tokenSimilarities[] = $best;
        }

        $avgTokenSimilarity = !empty($tokenSimilarities)
            ? array_sum($tokenSimilarities) / count($tokenSimilarities)
            : 0.0;

        $score = ($overallSimilarity * 0.7) + ($avgTokenSimilarity * 0.3);

        if ($inputDate && $candidateDate) {
            if ($inputDate === $candidateDate) {
                $score += 15;
            } elseif ($this->isNearbyDate($inputDate, $candidateDate)) {
                $score += 7;
            }
        }

        if ($inputSexe && $candidateSexe && strtoupper($inputSexe) === strtoupper($candidateSexe)) {
            $score += 5;
        }

        return min(100, $score);
    }

    /**
     * Vérifie si deux dates sont proches (±30 jours).
     */
    protected function isNearbyDate(string $inputDate, string $candidateDate): bool
    {
        try {
            $input = \Carbon\Carbon::parse($inputDate);
            $candidate = \Carbon\Carbon::parse($candidateDate);
            return abs($input->diffInDays($candidate)) <= 30;
        } catch (\Exception $e) {
            return false;
        }
    }
}
