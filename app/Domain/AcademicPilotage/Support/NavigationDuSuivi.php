<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Support;

use App\Domain\AcademicPilotage\Services\AcademicSystemNormalizer;
use App\Models\ESBTPClasse;

/**
 * Ce qui sert à naviguer depuis le suivi des notes : les périodes que la
 * classe peut afficher, et pour chaque matière l'adresse qui ouvre sa grille
 * de saisie sans refaire le chemin.
 *
 * Ajouté APRÈS le cache de la couverture : une aide de navigation, pas une
 * donnée calculée. Partagé par l'adresse du panneau et par Nanan.
 *
 * Une classe LMD a ses propres semestres (S3 et S4 en deuxième année) et sa
 * propre saisie : `esbtp.lmd.notes.index`, qui ouvre la classe et l'élément.
 */
final class NavigationDuSuivi
{
    public function __construct(private readonly AcademicSystemNormalizer $systemes) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function ajouter(array $payload, ESBTPClasse $classe, ?int $anneeUniversitaireId = null): array
    {
        $lmd = $this->estLmd($classe);
        $semestres = $this->semestres($classe);
        $payload['periodes'] = array_merge(
            [['valeur' => 'annuel', 'libelle' => 'Année']],
            array_map(fn (int $n) => ['valeur' => 'semestre'.$n, 'libelle' => 'S'.$n], $semestres),
        );

        if (! isset($payload['subjects']) || ! is_array($payload['subjects'])) {
            return $payload;
        }

        $semestre = (int) data_get($payload, 'maquette.semestre', 0);
        $periode = 'semestre'.(in_array($semestre, $semestres, true) ? $semestre : $semestres[0]);

        $payload['subjects'] = array_map(function (array $matiere) use ($classe, $periode, $lmd, $anneeUniversitaireId): array {
            if (empty($matiere['id'])) {
                return $matiere;
            }

            $params = array_filter([
                'annee_universitaire_id' => $anneeUniversitaireId,
                'classe_id' => $classe->id,
                'matiere_id' => (int) $matiere['id'],
                'periode' => $periode,
            ], static fn ($value) => $value !== null);

            $matiere['saisie_url'] = $lmd
                ? route('esbtp.lmd.notes.index', array_filter([
                    'annee_universitaire_id' => $anneeUniversitaireId,
                    'classe' => $classe->id,
                    'ecue' => (int) $matiere['id'],
                ], static fn ($value) => $value !== null))
                : route('esbtp.notes.index', $params);

            return $matiere;
        }, $payload['subjects']);

        return $payload;
    }

    /** @return list<int> */
    public function semestres(ESBTPClasse $classe): array
    {
        return $this->estLmd($classe) ? $classe->getSemestresLMD() : [1, 2];
    }

    private function estLmd(ESBTPClasse $classe): bool
    {
        // Un systeme inconnu n'empeche pas d'afficher le suivi : il se lit en BTS.
        try {
            return $this->systemes->normalize($classe->systeme_academique) === AcademicSystemNormalizer::LMD;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }
}
