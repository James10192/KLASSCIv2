<?php

namespace App\Services\LMD;

use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPLMDResultatUE;
use App\Models\ESBTPNote;
use App\Models\ESBTPUniteEnseignement;
use App\Services\AppreciationScaleService;
use App\Services\LMDBulletinService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LmdBulletinProjectionService
{
    public function __construct(
        private readonly LMDBulletinService $bulletins,
        private readonly LmdAcademicRuleProfile $rules,
        private readonly AppreciationScaleService $appreciations,
    ) {}

    public function calculerProjectionLive(
        int $etudiantId,
        int $classeId,
        int $anneeUniversitaireId,
        int $semestre
    ): array {
        $classe = ESBTPClasse::with(['parcours.mention.domaine', 'parcours.filiere', 'niveau', 'filiere'])
            ->findOrFail($classeId);
        $etudiant = ESBTPEtudiant::findOrFail($etudiantId);
        $ues = $this->bulletins->getUEsForSemestre($classe, $semestre);
        $periodeVariants = $this->bulletins->getPeriodeVariants($semestre);
        $bulletin = $this->bulletinsQuery($classeId, $anneeUniversitaireId, $semestre)
            ->where('etudiant_id', $etudiantId)
            ->first();
        $allNotes = ESBTPNote::where('etudiant_id', $etudiantId)
            ->where('classe_id', $classeId)
            ->whereHas('evaluation', function ($q) use ($periodeVariants, $anneeUniversitaireId) {
                $q->whereIn('periode', $periodeVariants)
                    ->where('annee_universitaire_id', $anneeUniversitaireId)
                    ->where('status', ESBTPEvaluation::STATUS_COMPLETED);
            })
            ->with('evaluation')
            ->get()
            ->groupBy('matiere_id');

        $enseignantMap = ESBTPEvaluation::where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->whereIn('periode', $periodeVariants)
            ->whereNotNull('enseignant_id')
            ->distinct()
            ->pluck('enseignant_id', 'matiere_id');

        return $this->buildProjection(
            $etudiant,
            $classe,
            $bulletin,
            $ues,
            $semestre,
            $anneeUniversitaireId,
            $allNotes,
            $enseignantMap
        );
    }

    public function calculerProjectionsClasse(
        int $classeId,
        int $anneeUniversitaireId,
        int $semestre
    ): Collection {
        $studentIds = $this->bulletins->studentIdsForGenerationCohort($classeId, $anneeUniversitaireId);
        if ($studentIds->isEmpty()) {
            return collect();
        }

        $classe = ESBTPClasse::with(['parcours.mention.domaine', 'parcours.filiere', 'niveau', 'filiere'])
            ->findOrFail($classeId);
        $ues = $this->bulletins->getUEsForSemestre($classe, $semestre);
        $periodeVariants = $this->bulletins->getPeriodeVariants($semestre);
        $students = ESBTPEtudiant::whereIn('id', $studentIds)->get()->keyBy('id');
        $bulletins = $this->bulletinsQuery($classeId, $anneeUniversitaireId, $semestre)
            ->whereIn('etudiant_id', $studentIds)
            ->get()
            ->keyBy('etudiant_id');
        $notesByStudent = ESBTPNote::whereIn('etudiant_id', $studentIds)
            ->where('classe_id', $classeId)
            ->whereHas('evaluation', function ($q) use ($periodeVariants, $anneeUniversitaireId) {
                $q->whereIn('periode', $periodeVariants)
                    ->where('annee_universitaire_id', $anneeUniversitaireId)
                    ->where('status', ESBTPEvaluation::STATUS_COMPLETED);
            })
            ->with('evaluation')
            ->get()
            ->groupBy('etudiant_id')
            ->map(fn (Collection $notes): Collection => $notes->groupBy('matiere_id'));
        $enseignantMap = ESBTPEvaluation::where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->whereIn('periode', $periodeVariants)
            ->whereNotNull('enseignant_id')
            ->distinct()
            ->pluck('enseignant_id', 'matiere_id');

        $projections = $studentIds
            ->map(function (int $studentId) use (
                $students,
                $classe,
                $bulletins,
                $ues,
                $semestre,
                $anneeUniversitaireId,
                $notesByStudent,
                $enseignantMap
            ): ?array {
                $student = $students->get($studentId);
                if (! $student) {
                    return null;
                }

                return $this->buildProjection(
                    $student,
                    $classe,
                    $bulletins->get($studentId),
                    $ues,
                    $semestre,
                    $anneeUniversitaireId,
                    $notesByStudent->get($studentId, collect()),
                    $enseignantMap
                );
            })
            ->filter()
            ->sortByDesc(fn (array $projection): float => (float) ($projection['moyenne_generale'] ?? -1))
            ->values();

        return $this->withLiveRanks($projections);
    }

    private function buildProjection(
        ESBTPEtudiant $etudiant,
        ESBTPClasse $classe,
        ?ESBTPLMDBulletin $bulletin,
        Collection $ues,
        int $semestre,
        int $anneeUniversitaireId,
        Collection $allNotes,
        Collection $enseignantMap
    ): array {
        $resultatsUEs = [];
        $creditsTotaux = 0;
        $missingEcues = 0;
        $totalEcues = 0;

        foreach ($ues as $ue) {
            $resultatUE = $this->calculerProjectionUE(
                $ue,
                (int) $etudiant->id,
                (int) $classe->id,
                $semestre,
                $anneeUniversitaireId,
                $allNotes,
                $enseignantMap
            );

            $resultatsUEs[] = $resultatUE;
            $creditsTotaux += $resultatUE['credit'];
            $missingEcues += $resultatUE['missing_ecues'];
            $totalEcues += $resultatUE['total_ecues'];
        }

        $moyenneGenerale = $this->calculerMoyenneGeneraleLive($resultatsUEs);
        $creditsCapitalises = $this->appliquerCompensationLive($resultatsUEs, $moyenneGenerale);
        $status = $this->determinerStatutProjection($ues, $totalEcues, $missingEcues);

        return [
            'etudiant' => $etudiant,
            'classe' => $classe,
            'bulletin' => $bulletin,
            'has_bulletin' => $bulletin !== null,
            'semestre' => $semestre,
            'annee_universitaire_id' => $anneeUniversitaireId,
            'resultats_ues' => collect($resultatsUEs),
            'moyenne_generale' => $moyenneGenerale,
            'mention_generale' => $moyenneGenerale !== null
                ? $this->appreciations->labelFor($moyenneGenerale, 'lmd', '')
                : null,
            'credits_capitalises' => $creditsCapitalises,
            'credits_totaux' => $creditsTotaux,
            'status' => $status,
            'is_complete' => $status === 'complete',
            'missing_ecues' => $missingEcues,
            'total_ecues' => $totalEcues,
        ];
    }

    private function bulletinsQuery(
        int $classeId,
        int $anneeUniversitaireId,
        int $semestre
    ): Builder {
        return ESBTPLMDBulletin::query()
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->where('semestre', $semestre)
            ->with(['resultatsUEs.uniteEnseignement', 'resultatsUEs.resultatsECUEs.matiere']);
    }

    private function calculerProjectionUE(
        ESBTPUniteEnseignement $ue,
        int $etudiantId,
        int $classeId,
        int $semestre,
        int $anneeUniversitaireId,
        Collection $allNotes,
        Collection $enseignantMap
    ): array {
        $ecues = $ue->getEcuesEffectifs();
        $totalPoints = 0;
        $totalCoefficients = 0;
        $missingEcues = 0;
        $resultatsECUEs = [];

        foreach ($ecues as $ecue) {
            $moyenneECUE = $this->bulletins->calculerMoyenneECUE(
                $etudiantId,
                $ecue->id,
                $classeId,
                $semestre,
                $anneeUniversitaireId,
                $allNotes->get($ecue->id, collect())
            );
            $coefficient = $ecue->pivot?->coefficient_ecue ?? $ecue->coefficient_ecue ?? $ecue->coefficient ?? 1;

            if ($moyenneECUE !== null) {
                $totalPoints += $moyenneECUE * (float) $coefficient;
                $totalCoefficients += (float) $coefficient;
            } else {
                $missingEcues++;
            }

            $resultatsECUEs[] = [
                'matiere' => $ecue,
                'matiere_id' => $ecue->id,
                'moyenne' => $moyenneECUE,
                'credit' => (int) ($ecue->pivot?->credit_ecue ?? $ecue->credit_ecue ?? 0),
                'coefficient' => (float) $coefficient,
                'enseignant_id' => $enseignantMap->get($ecue->id),
            ];
        }

        $moyenneUE = $totalCoefficients > 0 ? round($totalPoints / $totalCoefficients, 2) : null;
        $threshold = $this->rules->validationThreshold();

        return [
            'unite_enseignement' => $ue,
            'unite_enseignement_id' => $ue->id,
            'moyenne' => $moyenneUE,
            'statut' => $moyenneUE !== null && $moyenneUE >= $threshold
                ? ESBTPLMDResultatUE::STATUT_AQ
                : ESBTPLMDResultatUE::STATUT_NAQ,
            'mention' => $this->bulletins->determinerMentionUE($moyenneUE),
            'credit' => (int) $ue->credit,
            'credits_capitalises' => 0,
            'resultats_ecues' => collect($resultatsECUEs),
            'missing_ecues' => $missingEcues,
            'total_ecues' => $ecues->count(),
        ];
    }

    private function calculerMoyenneGeneraleLive(array $resultatsUEs): ?float
    {
        $totalPoints = 0;
        $totalCredits = 0;

        foreach ($resultatsUEs as $resultat) {
            if ($resultat['moyenne'] !== null && $resultat['credit'] > 0) {
                $totalPoints += (float) $resultat['moyenne'] * $resultat['credit'];
                $totalCredits += $resultat['credit'];
            }
        }

        return $totalCredits > 0 ? round($totalPoints / $totalCredits, 2) : null;
    }

    private function appliquerCompensationLive(array &$resultatsUEs, ?float $moyenneGenerale): int
    {
        $threshold = $this->rules->validationThreshold();
        $creditsCapitalises = 0;

        foreach ($resultatsUEs as &$resultat) {
            if ($resultat['moyenne'] === null) {
                continue;
            }

            if ((float) $resultat['moyenne'] >= $threshold) {
                $resultat['statut'] = ESBTPLMDResultatUE::STATUT_AQ;
                $resultat['credits_capitalises'] = $resultat['credit'];
                $creditsCapitalises += $resultat['credit'];
            } elseif ($this->rules->interUeCompensationEnabled() && $moyenneGenerale !== null && $moyenneGenerale >= $threshold) {
                $resultat['statut'] = ESBTPLMDResultatUE::STATUT_APC;
                $resultat['credits_capitalises'] = $resultat['credit'];
                $creditsCapitalises += $resultat['credit'];
            }
        }

        return $creditsCapitalises;
    }

    private function withLiveRanks(Collection $projections): Collection
    {
        $rank = 0;
        $position = 0;
        $lastMoyenne = null;
        $effectifAvecMoyenne = $projections->filter(fn (array $p): bool => $p['moyenne_generale'] !== null)->count();

        return $projections->map(function (array $projection) use (&$rank, &$position, &$lastMoyenne, $effectifAvecMoyenne): array {
            if ($projection['moyenne_generale'] === null) {
                $projection['rang'] = null;
                $projection['effectif'] = $effectifAvecMoyenne;

                return $projection;
            }

            $position++;
            $moyenne = (float) $projection['moyenne_generale'];
            if ($lastMoyenne === null || $moyenne !== $lastMoyenne) {
                $rank = $position;
                $lastMoyenne = $moyenne;
            }

            $projection['rang'] = $rank;
            $projection['effectif'] = $effectifAvecMoyenne;

            return $projection;
        });
    }

    private function determinerStatutProjection(Collection $ues, int $totalEcues, int $missingEcues): string
    {
        if ($ues->isEmpty() || $totalEcues === 0) {
            return 'configuration_missing';
        }

        if ($missingEcues > 0) {
            return 'incomplete';
        }

        return 'complete';
    }
}
