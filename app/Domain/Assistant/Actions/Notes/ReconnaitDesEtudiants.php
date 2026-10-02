<?php

namespace App\Domain\Assistant\Actions\Notes;

use App\Models\ESBTPEtudiant;
use Illuminate\Support\Str;

/**
 * Reconnaître un étudiant désigné par la personne, dans une liste fermée (la
 * classe, la cohorte de l'évaluation) : par matricule, ou par nom complet. Un
 * nom qui désigne zéro ou plusieurs étudiants n'est jamais deviné.
 */
trait ReconnaitDesEtudiants
{
    private function manqueDEtudiant(string $designation, int $i, array $trouves, array $proches): string
    {
        $liste = fn (array $es) => implode(', ', array_map(fn ($e) => $this->nom($e) . ' (' . $e->matricule . ')', array_slice($es, 0, 5)));

        return match (true) {
            $designation === '' => 'Ligne ' . ($i + 1) . ' : étudiant non précisé.',
            count($trouves) > 1 => "« {$designation} » désigne plusieurs étudiants : " . $liste($trouves) . '. Lequel ?',
            $proches !== [] => "« {$designation} » ne correspond exactement à personne ; plusieurs étudiants possibles : " . $liste($proches) . '. Lequel ?',
            default => "« {$designation} » : aucun étudiant de cette classe ne correspond. Demande le matricule.",
        };
    }

    /**
     * Matricule exact, sinon nom complet exact (mots dans n'importe quel ordre).
     * Les « proches » (tous les mots donnés figurent dans le nom) ne sont JAMAIS
     * retenus : ils servent seulement à poser la bonne question.
     *
     * @return array{0: ESBTPEtudiant[], 1: ESBTPEtudiant[]}
     */
    private function reconnaitre(string $designation, $etudiants): array
    {
        if ($designation === '') {
            return [[], []];
        }
        $cle = $this->normaliser($designation);
        $parMatricule = $etudiants->filter(fn ($e) => $this->normaliser((string) $e->matricule) === $cle)->values()->all();
        if ($parMatricule !== []) {
            return [$parMatricule, []];
        }

        $mots = collect(explode(' ', $cle))->filter()->sort()->values();
        $exacts = [];
        $proches = [];
        foreach ($etudiants as $e) {
            $siens = collect(explode(' ', $this->normaliser($e->nom . ' ' . $e->prenoms)))->filter()->sort()->values();
            if ($siens->all() === $mots->all()) {
                $exacts[] = $e;
            } elseif ($mots->diff($siens)->isEmpty()) {
                $proches[] = $e;
            }
        }

        return [$exacts, $proches];
    }

    private function normaliser(string $texte): string
    {
        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9 ]/', ' ', Str::lower(Str::ascii($texte)))));
    }

    private function nom(?ESBTPEtudiant $e): string
    {
        return $e ? trim(mb_strtoupper((string) $e->nom, 'UTF-8') . ' ' . $e->prenoms) : 'Étudiant';
    }
}
