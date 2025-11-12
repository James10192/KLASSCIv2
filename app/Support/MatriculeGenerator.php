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

        $lastMatricule = ESBTPEtudiant::where('matricule', 'like', "{$matriculePrefix}%")
            ->whereNull('deleted_at')
            ->orderByRaw('CAST(SUBSTRING(matricule, ' . (strlen($matriculePrefix) + 1) . ') AS UNSIGNED) DESC')
            ->first();

        $seq = 1;
        if ($lastMatricule) {
            $seqStr = substr($lastMatricule->matricule, strlen($matriculePrefix));
            $maxSeq = (int) $seqStr;

            // Recherche incrémentale dans les 100 DERNIERS numéros pour trouver un trou
            $searchStart = max(1, $maxSeq - 99);

            for ($i = $searchStart; $i <= $maxSeq; $i++) {
                $testMatricule = $matriculePrefix . str_pad($i, 6, '0', STR_PAD_LEFT);

                // Vérifier si ce matricule existe (requête EXISTS rapide)
                $exists = ESBTPEtudiant::where('matricule', $testMatricule)
                    ->whereNull('deleted_at')
                    ->exists();

                if (!$exists) {
                    // Trou trouvé, utiliser ce numéro
                    $seq = $i;
                    break;
                }
            }

            // Si aucun trou trouvé, utiliser max + 1
            if ($seq === 1) {
                $seq = $maxSeq + 1;
            }
        }

        $seqFormatted = str_pad($seq, 6, '0', STR_PAD_LEFT);

        return $matriculePrefix . $seqFormatted;
    }
}
