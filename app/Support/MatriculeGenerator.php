<?php

namespace App\Support;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatriculeConfig;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPSystemSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class MatriculeGenerator
{
    /**
     * Génère un matricule unique en tenant compte de la configuration personnalisée
     * lorsqu'elle est disponible, sinon applique un fallback interne.
     *
     * @param  array  $context
     * @return string
     */
    public function generate(array $context): string
    {
        $genre = strtoupper($context['genre'] ?? $context['sexe'] ?? 'M');
        $niveauId = $context['niveau_id'] ?? $context['niveau_etude_id'] ?? null;
        $anneeUniversitaireId = $context['annee_universitaire_id'] ?? null;

        if ($niveauId) {
            $config = $this->resolveConfiguration($niveauId);
            if ($config) {
                $annee = $this->resolveAnnee($anneeUniversitaireId);
                return $config->genererMatricule($genre, $annee);
            }
        }

        return $this->generateFallback($context);
    }

    /**
     * Recherche la configuration active associée au niveau.
     * Utilise type + year du niveau comme source primaire (champs structurés fiables),
     * puis fall back sur le champ code du niveau si type/year ne donnent rien.
     */
    protected function resolveConfiguration(int $niveauId): ?ESBTPMatriculeConfig
    {
        $niveau = ESBTPNiveauEtude::find($niveauId);
        if (!$niveau) {
            return null;
        }

        $etablissementId = ESBTPSystemSetting::getCurrentEtablissementId();

        // 1) Construire le code depuis type + year (source fiable)
        $codeFromTypeYear = $this->buildNiveauCode($niveau->type, $niveau->year);

        if ($codeFromTypeYear) {
            $config = ESBTPMatriculeConfig::where('etablissement_id', $etablissementId)
                ->where('niveau_etude_code', $codeFromTypeYear)
                ->where('is_active', true)
                ->first();

            if ($config) {
                return $config;
            }
        }

        // 2) Fallback sur niveau->code (pour les cas spéciaux comme L3Pro)
        if ($niveau->code) {
            $config = ESBTPMatriculeConfig::where('etablissement_id', $etablissementId)
                ->where('niveau_etude_code', $niveau->code)
                ->where('is_active', true)
                ->first();

            if ($config) {
                return $config;
            }
        }

        // 3) Dernier fallback : sans filtre etablissement (compatibilité anienne)
        $candidates = [$codeFromTypeYear, $niveau->code];
        foreach ($candidates as $code) {
            if (!$code) continue;

            $config = ESBTPMatriculeConfig::where('niveau_etude_code', $code)
                ->where('is_active', true)
                ->first();

            if ($config) {
                return $config;
            }
        }

        return null;
    }

    /**
     * Construit le niveau_etude_code à partir de type et year.
     *
     * Exemples :
     *   BTS + 1     → 1A
     *   BTS + 2     → 2A
     *   BTS + 5     → 5A
     *   Licence + 1 → L1
     *   Licence + 2 → L2
     *   Licence + 3 → L3
     *   Master + 1  → M1
     *   Master + 2  → M2
     */
    protected function buildNiveauCode(?string $type, ?int $year): ?string
    {
        if (!$type || $year === null) {
            return null;
        }

        $type = strtolower(trim($type));

        return match ($type) {
            'bts'       => $year . 'A',
            'licence'   => 'L' . $year,
            'master'    => 'M' . $year,
            'bachelor'  => 'B' . $year,
            'doctorat'  => 'D' . $year,
            'diplome', 'diplôme' => 'DIP' . $year,
            'certificat' => 'CER' . $year,
            default     => null,
        };
    }

    /**
     * Détermine l'année à utiliser pour la génération du matricule.
     */
    protected function resolveAnnee(?int $anneeUniversitaireId): int
    {
        if ($anneeUniversitaireId) {
            $annee = ESBTPAnneeUniversitaire::find($anneeUniversitaireId);
            if ($annee) {
                if ($annee->start_date) {
                    return Carbon::parse($annee->start_date)->year;
                }

                if ($annee->name && preg_match('/\d{4}/', $annee->name, $matches)) {
                    return (int) $matches[0];
                }
            }
        }

        return now()->year;
    }

    /**
     * Génère un matricule en utilisant la logique interne historique.
     */
    protected function generateFallback(array $context): string
    {
        $filiereId = $context['filiere_id'] ?? null;
        $niveauId = $context['niveau_id'] ?? $context['niveau_etude_id'] ?? null;
        $anneeUniversitaireId = $context['annee_universitaire_id'] ?? null;

        $filiere = $filiereId ? ESBTPFiliere::find($filiereId) : null;
        $niveau = $niveauId ? ESBTPNiveauEtude::find($niveauId) : null;

        $filiereCode = $filiere ? ($filiere->code ?? Str::upper(Str::substr(Str::ascii($filiere->name ?? $filiere->nom ?? ''), 0, 2))) : 'XX';
        $niveauCode = $niveau ? ($niveau->code ?? ($niveau->year ?? 'XX')) : 'XX';

        $anneeYear = $this->resolveAnnee($anneeUniversitaireId);
        $anneeCode = substr((string) $anneeYear, -2);

        $matriculePrefix = Str::upper($filiereCode . $niveauCode . $anneeCode);

        // ⚡ Optimisation : récupérer toutes les séquences existantes en une seule requête (inclut soft deleted)
        $existing = ESBTPEtudiant::withTrashed()
            ->where('matricule', 'like', "{$matriculePrefix}%")
            ->pluck('matricule')
            ->map(function ($m) {
                // Récupérer la partie après le tiret
                return (int) Str::afterLast($m, '-');
            })
            ->filter(fn($seq) => $seq > 0)
            ->sort()
            ->values();

        $seq = 1;

        if ($existing->isNotEmpty()) {
            // Trouver le premier trou dans la séquence
            for ($i = 1; $i <= $existing->last(); $i++) {
                if (!$existing->contains($i)) {
                    $seq = $i;
                    break;
                }
            }

            // Si aucun trou trouvé, incrémenter le max
            if ($seq === 1) {
                $seq = $existing->last() + 1;
            }
        }

        $seqFormatted = str_pad($seq, 6, '0', STR_PAD_LEFT);
        $matricule = $matriculePrefix . $seqFormatted;

        // 🔒 Double vérification finale pour éviter toute collision (inclut soft deleted)
        if (ESBTPEtudiant::withTrashed()->where('matricule', $matricule)->exists()) {
            // Si collision, on incrémente automatiquement
            $seq = $existing->isNotEmpty() ? $existing->last() + 1 : 1;
            $seqFormatted = str_pad($seq, 6, '0', STR_PAD_LEFT);
            $matricule = $matriculePrefix . $seqFormatted;
        }

        return $matricule;
    }
}
