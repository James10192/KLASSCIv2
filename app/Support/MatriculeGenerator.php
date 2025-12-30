<?php

namespace App\Support;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatriculeConfig;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPEtudiant;
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
                $matricule = $config->genererMatricule($genre, $annee);

                if (!ESBTPEtudiant::where('matricule', $matricule)->exists()) {
                    return $matricule;
                }
            }
        }

        return $this->generateFallback($context);
    }

    /**
     * Recherche la configuration active associée au niveau.
     */
    protected function resolveConfiguration(int $niveauId): ?ESBTPMatriculeConfig
    {
        $niveau = ESBTPNiveauEtude::find($niveauId);
        if (!$niveau) {
            return null;
        }

        $niveauCode = $niveau->code ?: Str::upper(Str::substr(Str::ascii($niveau->name ?? ''), 0, 3));
        if (!$niveauCode) {
            return null;
        }

        return ESBTPMatriculeConfig::where('niveau_etude_code', $niveauCode)
            ->where('is_active', true)
            ->first();
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

        // ⚡ Optimisation : récupérer toutes les séquences existantes en une seule requête
        $existing = ESBTPEtudiant::where('matricule', 'like', "{$matriculePrefix}%")
            ->whereNull('deleted_at')
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

        // 🔒 Double vérification finale pour éviter toute collision
        if (ESBTPEtudiant::where('matricule', $matricule)->exists()) {
            // Si collision, on incrémente automatiquement
            $seq = $existing->isNotEmpty() ? $existing->last() + 1 : 1;
            $seqFormatted = str_pad($seq, 6, '0', STR_PAD_LEFT);
            $matricule = $matriculePrefix . $seqFormatted;
        }

        return $matricule;
    }
}
