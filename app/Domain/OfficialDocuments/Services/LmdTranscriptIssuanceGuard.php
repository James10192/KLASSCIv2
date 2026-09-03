<?php

namespace App\Domain\OfficialDocuments\Services;

use App\Domain\OfficialDocuments\Exceptions\LmdTranscriptNotIssuableException;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPLMDResultatUE;
use Illuminate\Support\Collection;

/**
 * Conditions d'emission d'un releve de notes LMD.
 *
 * Volontairement sobre, et pour une raison : un releve n'est pas un acte de jury.
 * Il ne decide rien, il recopie ce qui a deja ete decide. Les seules conditions
 * sont donc que la matiere a recopier existe et soit definitive :
 *
 *  - au moins un bulletin LMD pour cet etudiant sur cette annee ;
 *  - tous ces bulletins publies (un bulletin non publie est encore un brouillon,
 *    l'ecole ne l'a pas arrete) ;
 *  - au moins un resultat d'UE, sinon le document serait un cadre vide.
 *
 * Le verrouillage en lecture (`lockForUpdate`) reprend le patron du PV : le
 * snapshot doit etre pris sur un etat qui ne bouge pas pendant qu'on le lit.
 */
class LmdTranscriptIssuanceGuard
{
    /**
     * @return array{student: ESBTPEtudiant, year: ESBTPAnneeUniversitaire, bulletins: Collection}
     */
    public function assertIssuable(int $studentId, int $yearId): array
    {
        $student = ESBTPEtudiant::query()->lockForUpdate()->findOrFail($studentId);
        $year = ESBTPAnneeUniversitaire::query()->withTrashed()->findOrFail($yearId);

        $bulletins = ESBTPLMDBulletin::query()
            ->where('etudiant_id', $student->id)
            ->where('annee_universitaire_id', $year->id)
            ->orderBy('semestre')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $reasons = $this->reasons($bulletins);
        if ($reasons !== []) {
            throw new LmdTranscriptNotIssuableException($reasons);
        }

        $bulletins->load([
            'classe',
            'parcours',
            'resultatsUEs.uniteEnseignement',
            'resultatsUEs.resultatsECUEs.matiere',
        ]);

        return ['student' => $student, 'year' => $year, 'bulletins' => $bulletins];
    }

    /**
     * Etat de preparation, sans lever d'exception : utile pour griser un bouton.
     *
     * @return array{ok: bool, reasons: array<int, string>, semesters: int}
     */
    public function readiness(int $studentId, int $yearId): array
    {
        $bulletins = ESBTPLMDBulletin::query()
            ->where('etudiant_id', $studentId)
            ->where('annee_universitaire_id', $yearId)
            ->get();

        $reasons = $this->reasons($bulletins);

        return [
            'ok' => $reasons === [],
            'reasons' => $reasons,
            'semesters' => $bulletins->count(),
        ];
    }

    /** @return array<int, string> */
    private function reasons(Collection $bulletins): array
    {
        if ($bulletins->isEmpty()) {
            return ['Aucun bulletin LMD n existe pour cet etudiant sur cette annee universitaire.'];
        }

        $reasons = [];

        if ($bulletins->contains(fn ($bulletin) => ! $bulletin->is_published)) {
            $reasons[] = 'Tous les bulletins de l annee doivent etre publies avant l emission du releve.';
        }

        // Comptage direct plutot que `withCount` : le nom de colonne genere par
        // Laravel pour la relation `resultatsUEs` est illisible et depend de la
        // casse du nom de relation. Ici la question posee est simple, la requete
        // qui y repond doit l'etre aussi.
        $resultCount = ESBTPLMDResultatUE::query()
            ->whereIn('bulletin_id', $bulletins->pluck('id'))
            ->count();

        if ($resultCount === 0) {
            $reasons[] = 'Aucun resultat d unite d enseignement n est rattache a ces bulletins.';
        }

        return $reasons;
    }
}
