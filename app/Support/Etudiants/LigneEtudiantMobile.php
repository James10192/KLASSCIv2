<?php

namespace App\Support\Etudiants;

use App\Models\ESBTPEtudiant;

/**
 * Une ligne de la liste des etudiants sur telephone (partial _index-mobile).
 *
 * Construite cote serveur pour la premiere tranche comme pour les suivantes,
 * afin qu'une seule forme de donnees alimente l'ecran. Les inscriptions sont
 * celles deja chargees par le controleur : aucune requete de plus par ligne.
 */
final class LigneEtudiantMobile
{
    /** @return array<string, mixed> */
    public static function depuis(ESBTPEtudiant $etudiant, ?int $anneeCouranteId): array
    {
        $courante = $anneeCouranteId
            ? $etudiant->inscriptions->firstWhere('annee_universitaire_id', $anneeCouranteId)
            : null;
        $derniere = $courante ?: $etudiant->inscriptions->sortByDesc('created_at')->first();

        [$etat, $libelle] = match (true) {
            $courante && $courante->workflow_step === 'etudiant_cree' => ['inscrit', 'Inscrit'],
            (bool) $courante => ['en_cours', 'En cours'],
            default => ['aucune', 'Sans inscription'],
        };

        return [
            'id' => $etudiant->id,
            'nom' => trim($etudiant->nom . ' ' . $etudiant->prenoms),
            'matricule' => $etudiant->matricule,
            'initiales' => self::initiales($etudiant),
            'photo' => $etudiant->photo_url,
            'classe' => $derniere?->classe?->name,
            'lmd' => ($derniere?->classe?->systeme_academique ?? '') === 'LMD',
            // Une classe d'une annee passee porte son annee : sinon on la lirait
            // comme la classe de cette annee.
            'annee' => $courante ? null : $derniere?->anneeUniversitaire?->name,
            'etat' => $etat,
            'etat_libelle' => $libelle,
            'actif' => $etudiant->statut === 'actif',
            'url' => route('esbtp.etudiants.show', $etudiant),
        ];
    }

    private static function initiales(ESBTPEtudiant $etudiant): string
    {
        $initiales = mb_substr((string) $etudiant->nom, 0, 1, 'UTF-8')
            . mb_substr((string) $etudiant->prenoms, 0, 1, 'UTF-8');

        return mb_strtoupper($initiales, 'UTF-8') ?: '?';
    }
}
