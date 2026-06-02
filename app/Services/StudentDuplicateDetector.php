<?php

namespace App\Services;

use App\Models\ESBTPEtudiant;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Détection de doublons étudiants par scoring pondéré multi-critères.
 *
 * Modèle inspiré du framework Fellegi-Sunter :
 * chaque champ comparé contribue positivement ou négativement au score final.
 *
 * Score maximal théorique : 100 points
 *   Nom      : 40 pts (exact) / 30 pts (fuzzy ≥85%) / 15 pts (fuzzy ≥70%)
 *   Prénom   : 30 pts (exact) / 22 pts (fuzzy ≥85%) / 12 pts (fuzzy ≥70%)
 *   Date DN  : 25 pts (exacte) / 15 pts (±3 jours) / 8 pts (même mois+année)
 *   Sexe     : +5 pts (concordant) / −10 pts (discordant si les deux renseignés)
 *
 * Seuils :
 *   ≥ 55 → alerte affichée (doublon possible)
 *   ≥ 75 → doublon probable
 *   ≥ 90 → quasi-certitude
 */
class StudentDuplicateDetector
{
    // Points attribués au nom
    private const NOM_EXACT   = 40;
    private const NOM_HIGH    = 30; // similarité ≥ 85%
    private const NOM_MEDIUM  = 15; // similarité ≥ 70%

    // Points attribués au prénom
    private const PRENOMS_EXACT  = 30;
    private const PRENOMS_HIGH   = 22; // similarité ≥ 85%
    private const PRENOMS_MEDIUM = 12; // similarité ≥ 70%

    // Points attribués à la date de naissance
    private const DATE_EXACT       = 25;
    private const DATE_NEAR_3DAYS  = 15; // ±3 jours (faute de frappe sur le jour)
    private const DATE_SAME_MONTH  = 8;  // même mois + année (jour inconnu/erroné)

    // Points sexe
    private const SEXE_MATCH    =  5;
    private const SEXE_MISMATCH = -10;

    // Score minimum pour être retourné (évite le bruit)
    private const SCORE_MIN_RETURN = 35;

    /**
     * Recherche des étudiants susceptibles d'être des doublons.
     *
     * @param  string      $nom            Nom de famille saisi
     * @param  string      $prenoms        Prénom(s) saisi (peut être vide)
     * @param  string|null $dateNaissance  Format Y-m-d
     * @param  string|null $sexe           'M' ou 'F'
     * @param  int         $limit          Nombre max de résultats
     */
    public function find(
        string $nom,
        string $prenoms = '',
        ?string $dateNaissance = null,
        ?string $sexe = null,
        int $limit = 10,
    ): Collection {
        $nom     = trim($nom);
        $prenoms = trim($prenoms);

        // Minimum : nom ≥ 2 caractères OU prénoms ≥ 2 caractères
        if (mb_strlen($nom) < 2 && mb_strlen($prenoms) < 2) {
            return collect();
        }

        // Récupère les candidats depuis la BDD par recherche LIKE large
        $candidates = $this->fetchCandidates($nom, $prenoms, $dateNaissance);

        $results = $candidates
            ->map(function (ESBTPEtudiant $candidate) use ($nom, $prenoms, $dateNaissance, $sexe) {
                $score    = $this->computeScore($nom, $prenoms, $dateNaissance, $sexe, $candidate);
                $breakdown = $this->computeBreakdown($nom, $prenoms, $dateNaissance, $sexe, $candidate);

                if ($score < self::SCORE_MIN_RETURN) {
                    return null;
                }

                return [
                    'id'            => $candidate->id,
                    'full_name'     => trim($candidate->nom . ' ' . $candidate->prenoms),
                    'matricule'     => $candidate->matricule,
                    'date_naissance'=> $candidate->date_naissance?->format('d/m/Y'),
                    'sexe'          => $candidate->sexe,
                    'score'         => min(99, round($score, 1)),
                    'confidence'    => $this->confidenceLabel($score),
                    'breakdown'     => $breakdown,
                ];
            })
            ->filter();

        return $results
            ->sortByDesc('score')
            ->values()
            ->take($limit);
    }

    // -------------------------------------------------------------------------
    // Récupération des candidats
    // -------------------------------------------------------------------------

    /**
     * Requête SQL large pour obtenir les candidats à scorer.
     * On utilise LIKE sur les tokens du nom et prénom + date exacte.
     * La précision est assurée par le scoring PHP, pas par le SQL.
     */
    private function fetchCandidates(string $nom, string $prenoms, ?string $dateNaissance): Collection
    {
        $nomNorm     = $this->normalizeString($nom);
        $prenomsNorm = $this->normalizeString($prenoms);

        // Tokens utiles (≥ 2 chars)
        $tokens = array_filter(
            array_merge(
                $this->tokenize($nomNorm),
                $this->tokenize($prenomsNorm),
            ),
            fn ($t) => mb_strlen($t) >= 2,
        );

        $query = ESBTPEtudiant::query()
            ->select('id', 'nom', 'prenoms', 'matricule', 'date_naissance', 'sexe')
            ->whereNull('deleted_at');

        if (!empty($tokens)) {
            $query->where(function ($q) use ($tokens) {
                foreach ($tokens as $token) {
                    $like = '%' . $token . '%';
                    $q->orWhere('nom', 'like', $like)
                      ->orWhere('prenoms', 'like', $like);
                }
            });
        }

        // Inclure aussi les correspondances par date (même si le nom est différent)
        if ($dateNaissance) {
            if (!empty($tokens)) {
                $query->orWhereDate('date_naissance', $dateNaissance);
            } else {
                $query->whereDate('date_naissance', $dateNaissance);
            }
        }

        return $query->limit(300)->get();
    }

    // -------------------------------------------------------------------------
    // Score principal
    // -------------------------------------------------------------------------

    private function computeScore(
        string $nom,
        string $prenoms,
        ?string $dateNaissance,
        ?string $sexe,
        ESBTPEtudiant $candidate,
    ): float {
        $score = 0.0;

        // --- Nom ---
        $score += $this->scoreNom($nom, $candidate->nom ?? '');

        // --- Prénom ---
        if ($prenoms !== '') {
            $score += $this->scorePrenoms($prenoms, $candidate->prenoms ?? '');
        }

        // --- Date de naissance ---
        if ($dateNaissance) {
            $score += $this->scoreDateNaissance($dateNaissance, $candidate->date_naissance?->format('Y-m-d'));
        }

        // --- Sexe ---
        if ($sexe && $candidate->sexe) {
            if (strtoupper($sexe) === strtoupper($candidate->sexe)) {
                $score += self::SEXE_MATCH;
            } else {
                $score += self::SEXE_MISMATCH;
            }
        }

        return max(0.0, $score);
    }

    /**
     * Détail du score par champ (pour affichage debug/UI).
     */
    private function computeBreakdown(
        string $nom,
        string $prenoms,
        ?string $dateNaissance,
        ?string $sexe,
        ESBTPEtudiant $candidate,
    ): array {
        return [
            'nom'    => round($this->scoreNom($nom, $candidate->nom ?? ''), 1),
            'prenoms'=> $prenoms !== '' ? round($this->scorePrenoms($prenoms, $candidate->prenoms ?? ''), 1) : null,
            'date'   => $dateNaissance ? round($this->scoreDateNaissance($dateNaissance, $candidate->date_naissance?->format('Y-m-d')), 1) : null,
        ];
    }

    // -------------------------------------------------------------------------
    // Scoring par champ
    // -------------------------------------------------------------------------

    private function scoreNom(string $input, string $candidate): float
    {
        if ($input === '' || $candidate === '') {
            return 0.0;
        }

        $sim = $this->bestTokenSimilarity(
            $this->normalizeString($input),
            $this->normalizeString($candidate),
        );

        if ($sim >= 99.5) return self::NOM_EXACT;
        if ($sim >= 85.0) return self::NOM_HIGH;
        if ($sim >= 70.0) return self::NOM_MEDIUM;

        return 0.0;
    }

    private function scorePrenoms(string $input, string $candidate): float
    {
        if ($input === '' || $candidate === '') {
            return 0.0;
        }

        $sim = $this->bestTokenSimilarity(
            $this->normalizeString($input),
            $this->normalizeString($candidate),
        );

        if ($sim >= 99.5) return self::PRENOMS_EXACT;
        if ($sim >= 85.0) return self::PRENOMS_HIGH;
        if ($sim >= 70.0) return self::PRENOMS_MEDIUM;

        return 0.0;
    }

    private function scoreDateNaissance(?string $input, ?string $candidate): float
    {
        if (!$input || !$candidate) {
            return 0.0;
        }

        if ($input === $candidate) {
            return self::DATE_EXACT;
        }

        // ±3 jours (faute de saisie sur le jour)
        try {
            $dInput     = \Carbon\Carbon::parse($input);
            $dCandidate = \Carbon\Carbon::parse($candidate);
            $diffDays   = abs($dInput->diffInDays($dCandidate));

            if ($diffDays <= 3) {
                return self::DATE_NEAR_3DAYS;
            }

            // Même mois + même année (jour différent ou inconnu)
            if ($dInput->year === $dCandidate->year && $dInput->month === $dCandidate->month) {
                return self::DATE_SAME_MONTH;
            }
        } catch (\Exception) {
            // Date invalide → pas de points
        }

        return 0.0;
    }

    // -------------------------------------------------------------------------
    // Similarité textuelle
    // -------------------------------------------------------------------------

    /**
     * Meilleure similarité entre un token input et tous les tokens du candidat.
     * Combine Jaro-Winkler (performant pour noms courts avec inversions)
     * et similar_text (bon pour les longues chaînes).
     *
     * Retourne un score 0–100.
     */
    private function bestTokenSimilarity(string $input, string $candidate): float
    {
        $inputTokens     = $this->tokenize($input);
        $candidateTokens = $this->tokenize($candidate);

        if (empty($inputTokens) || empty($candidateTokens)) {
            return 0.0;
        }

        // Cas : un seul token de chaque côté → comparaison directe
        if (count($inputTokens) === 1 && count($candidateTokens) === 1) {
            return $this->tokenSim($inputTokens[0], $candidateTokens[0]);
        }

        // Cas multi-tokens : chaque token input cherche son meilleur match
        // dans les tokens candidats, puis on fait la moyenne pondérée
        $scores = [];
        foreach ($inputTokens as $iToken) {
            $best = 0.0;
            foreach ($candidateTokens as $cToken) {
                $best = max($best, $this->tokenSim($iToken, $cToken));
            }
            // Pondérer par la longueur du token (tokens courts = moins significatifs)
            $weight   = max(1, mb_strlen($iToken));
            $scores[] = [$best, $weight];
        }

        $weightedSum   = array_sum(array_map(fn ($s) => $s[0] * $s[1], $scores));
        $totalWeight   = array_sum(array_map(fn ($s) => $s[1], $scores));

        return $totalWeight > 0 ? $weightedSum / $totalWeight : 0.0;
    }

    /**
     * Similarité entre deux tokens normalisés.
     * Retourne le maximum de Jaro-Winkler et similar_text.
     */
    private function tokenSim(string $a, string $b): float
    {
        if ($a === $b) {
            return 100.0;
        }
        if ($a === '' || $b === '') {
            return 0.0;
        }

        // similar_text (% mode)
        similar_text($a, $b, $similarTextScore);

        // Jaro-Winkler (retourne 0–1, on convertit en 0–100)
        $jaroScore = $this->jaroWinkler($a, $b) * 100.0;

        // Retenir le meilleur des deux
        return max($similarTextScore, $jaroScore);
    }

    // -------------------------------------------------------------------------
    // Jaro-Winkler
    // -------------------------------------------------------------------------

    /**
     * Calcule la distance de Jaro-Winkler entre deux chaînes.
     * Très efficace pour les noms courts avec transpositions de lettres.
     * Retourne un score entre 0 (aucune similarité) et 1 (identique).
     */
    private function jaroWinkler(string $s1, string $s2): float
    {
        $jaro = $this->jaro($s1, $s2);

        if ($jaro < 0.7) {
            return $jaro;
        }

        // Préfixe commun (max 4 chars)
        $prefix = 0;
        $limit  = min(4, min(mb_strlen($s1), mb_strlen($s2)));
        for ($i = 0; $i < $limit; $i++) {
            if (mb_substr($s1, $i, 1) === mb_substr($s2, $i, 1)) {
                $prefix++;
            } else {
                break;
            }
        }

        return $jaro + ($prefix * 0.1 * (1 - $jaro));
    }

    private function jaro(string $s1, string $s2): float
    {
        $len1 = mb_strlen($s1);
        $len2 = mb_strlen($s2);

        if ($len1 === 0 && $len2 === 0) return 1.0;
        if ($len1 === 0 || $len2 === 0) return 0.0;
        if ($s1 === $s2) return 1.0;

        $matchDist = (int) max(floor(max($len1, $len2) / 2) - 1, 0);

        $s1Matches = array_fill(0, $len1, false);
        $s2Matches = array_fill(0, $len2, false);

        $matches      = 0;
        $transpositions = 0;

        for ($i = 0; $i < $len1; $i++) {
            $start = max(0, $i - $matchDist);
            $end   = min($i + $matchDist + 1, $len2);

            for ($j = $start; $j < $end; $j++) {
                if ($s2Matches[$j] || mb_substr($s1, $i, 1) !== mb_substr($s2, $j, 1)) {
                    continue;
                }
                $s1Matches[$i] = true;
                $s2Matches[$j] = true;
                $matches++;
                break;
            }
        }

        if ($matches === 0) return 0.0;

        $k = 0;
        for ($i = 0; $i < $len1; $i++) {
            if (!$s1Matches[$i]) continue;
            while (!$s2Matches[$k]) $k++;
            if (mb_substr($s1, $i, 1) !== mb_substr($s2, $k, 1)) $transpositions++;
            $k++;
        }

        return (
            ($matches / $len1) +
            ($matches / $len2) +
            (($matches - $transpositions / 2) / $matches)
        ) / 3.0;
    }

    // -------------------------------------------------------------------------
    // Normalisation
    // -------------------------------------------------------------------------

    /**
     * Normalise une chaîne : minuscules, ASCII, sans accents, sans ponctuation.
     */
    private function normalizeString(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = Str::ascii($value);         // Supprime accents (é→e, ç→c, etc.)
        $value = preg_replace('/[^a-z0-9\s\-]/', '', $value);
        $value = preg_replace('/\s+/', ' ', $value);
        return trim($value);
    }

    /**
     * Découpe une chaîne normalisée en tokens (mots).
     */
    private function tokenize(string $normalized): array
    {
        if ($normalized === '') {
            return [];
        }
        return array_values(array_filter(
            preg_split('/[\s\-]+/', $normalized) ?: [],
            fn ($t) => $t !== '',
        ));
    }

    // -------------------------------------------------------------------------
    // Label de confiance
    // -------------------------------------------------------------------------

    private function confidenceLabel(float $score): string
    {
        if ($score >= 90) return 'quasi-certain';
        if ($score >= 75) return 'probable';
        if ($score >= 55) return 'possible';
        return 'faible';
    }
}
