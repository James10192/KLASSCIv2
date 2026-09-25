<?php

namespace App\Domain\Students;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use Illuminate\Database\Eloquent\Builder;

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

        $inscritsAnneeCourante = $annee ? $this->inscritsDe($annee->id) : 0;

        return [
            'inscrits_annee_courante' => $inscritsAnneeCourante,
            'total_base' => ESBTPEtudiant::count(),
            'annee_courante_id' => $annee?->id,
            'annee_courante_label' => $annee?->name,
        ];
    }

    /** Inscrits d'une année : inscription active, dossier étudiant créé, un étudiant compté une fois. */
    public function inscritsDe(int $anneeId): int
    {
        return $this->requeteInscrits($anneeId)
            ->distinct('esbtp_inscriptions.etudiant_id')
            ->count('esbtp_inscriptions.etudiant_id');
    }

    /**
     * La règle d'un « inscrit » en un seul endroit : inscription active, dossier
     * étudiant créé, non supprimée. Colonnes qualifiées, pour rester juste quand
     * l'appelant joint les classes (qui portent aussi annee_universitaire_id).
     */
    public function requeteInscrits(int $anneeId): Builder
    {
        return ESBTPInscription::query()
            ->where('esbtp_inscriptions.annee_universitaire_id', $anneeId)
            ->where('esbtp_inscriptions.status', 'active')
            ->where('esbtp_inscriptions.workflow_step', 'etudiant_cree');
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
