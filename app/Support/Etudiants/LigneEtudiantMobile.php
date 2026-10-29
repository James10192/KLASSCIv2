<?php

namespace App\Support\Etudiants;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;

/**
 * Une ligne de la liste des etudiants sur telephone (partial _index-mobile).
 *
 * Construite cote serveur pour la premiere tranche comme pour les suivantes,
 * afin qu'une seule forme de donnees alimente l'ecran. Les inscriptions sont
 * celles deja chargees par le controleur : aucune requete de plus par ligne.
 */
final class LigneEtudiantMobile
{
    /**
     * @param  ESBTPInscription|null  $derniereConnue  derniere inscription, toutes annees : le
     *         controleur ne charge que celles de l'annee courante, elle vient d'une requete a part.
     * @param  array{a11y?: bool, valider?: bool}  $droits
     * @return array<string, mixed>
     */
    public static function depuis(ESBTPEtudiant $etudiant, ?int $anneeCouranteId, ?ESBTPInscription $derniereConnue = null, array $droits = []): array
    {
        $courante = $anneeCouranteId
            ? $etudiant->inscriptions->firstWhere('annee_universitaire_id', $anneeCouranteId)
            : null;
        $derniere = $courante ?: ($derniereConnue ?? $etudiant->inscriptions->sortByDesc('created_at')->first());

        [$etat, $libelle] = match (true) {
            $courante && $courante->workflow_step === 'etudiant_cree' => ['inscrit', 'Inscrit'],
            (bool) $courante => ['en_cours', 'En cours'],
            default => ['aucune', 'Non inscrit'],
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
            'a11y' => ($droits['a11y'] ?? false) ? $etudiant->accessibilityProfile?->summaryBadge() : null,
            // L'inscription en cours se valide depuis sa propre page.
            'a_valider_url' => ($droits['valider'] ?? false) && $etat === 'en_cours'
                ? route('esbtp.inscriptions.show', $courante)
                : null,
        ];
    }

    private static function initiales(ESBTPEtudiant $etudiant): string
    {
        $initiales = mb_substr((string) $etudiant->nom, 0, 1, 'UTF-8')
            . mb_substr((string) $etudiant->prenoms, 0, 1, 'UTF-8');

        return mb_strtoupper($initiales, 'UTF-8') ?: '?';
    }
}
