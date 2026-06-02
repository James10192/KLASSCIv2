<?php

namespace App\Domain\Students;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;

/**
 * Service centralisé pour les comptages d'étudiants.
 *
 * Distingue clairement :
 *  - "inscrits année courante" (distinct etudiant_id avec inscription
 *    active+validée sur l'année universitaire en cours)
 *  - "total base" (toutes les fiches étudiants présentes dans la DB,
 *    tous statuts/années confondus — anciens diplômés, non-réinscrits…)
 *
 * Évite le piège ESBTPEtudiant::count() qui gonfle artificiellement
 * les dashboards à mesure que les années passent.
 */
class StudentCountService
{
    /**
     * Compteurs synthétiques pour les dashboards / API.
     *
     * @return array{
     *   inscrits_annee_courante: int,
     *   total_base: int,
     *   annee_courante_id: int|null,
     *   annee_courante_label: string|null
     * }
     */
    public function counts(): array
    {
        $annee = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        $inscritsAnneeCourante = 0;
        if ($annee) {
            $inscritsAnneeCourante = ESBTPInscription::query()
                ->where('annee_universitaire_id', $annee->id)
                ->where('status', 'active')
                ->where('workflow_step', 'etudiant_cree')
                ->distinct('etudiant_id')
                ->count('etudiant_id');
        }

        return [
            'inscrits_annee_courante' => $inscritsAnneeCourante,
            'total_base' => ESBTPEtudiant::count(),
            'annee_courante_id' => $annee?->id,
            'annee_courante_label' => $annee?->name,
        ];
    }

    /**
     * Helper court : nombre d'étudiants avec inscription active+validée
     * sur l'année en cours. Pour KPI principal des dashboards.
     */
    public function inscritsAnneeCourante(): int
    {
        return $this->counts()['inscrits_annee_courante'];
    }

    /**
     * Helper court : total étudiants en base (toutes années confondues).
     * Pour KPI complémentaire ou rétrocompat des endpoints existants.
     */
    public function totalBase(): int
    {
        return ESBTPEtudiant::count();
    }
}
