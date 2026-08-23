<?php

namespace App\Domain\BtsTroncCommun;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use Illuminate\Support\Collection;

class BtsAnnualAggregationService
{
    public function __construct(
        private BtsPhaseResolver $resolver,
        private BtsAnnualClassMapResolver $carteAnnuelle,
    ) {
    }

    public function resolveStudentContext(
        ESBTPEtudiant $etudiant,
        ?int $anneeId,
        ?int $requestedClasseId,
        string $periode,
        bool $includeAllStatuses = true
    ): array {
        $allYearInscriptions = $etudiant->inscriptions()
            ->with([
                'filiere',
                'classe.filiere',
                'classe.niveau',
                'phases.classe.filiere',
                'inscriptionOrigine.classe.filiere',
                'inscriptionSpecialisation.classe.filiere',
            ])
            ->when($anneeId, fn ($query) => $query->where('annee_universitaire_id', $anneeId))
            ->orderByDesc('date_inscription')
            ->orderByDesc('id')
            ->get();

        $filtered = $includeAllStatuses ? $allYearInscriptions : $allYearInscriptions->where('status', 'active')->values();
        $selected = $requestedClasseId
            ? $filtered->firstWhere('classe_id', $requestedClasseId)
            : $filtered->first();
        $selected ??= $requestedClasseId
            ? $allYearInscriptions->firstWhere('classe_id', $requestedClasseId)
            : $allYearInscriptions->first();

        if (! $selected) {
            return [
                'inscription' => null,
                'effective_classe_id' => $requestedClasseId,
                'classe_par_semestre' => [],
                'journey' => null,
                'source_model' => 'phase_based',
            ];
        }

        $journey = $this->resolver->buildJourney($selected);
        $semester = match ($periode) {
            'semestre2' => 2,
            default => 1,
        };
        $phase = $periode === 'annuel'
            ? ($journey['current_phase'] ?? null)
            : $this->resolver->resolveSemesterPhase($selected, $semester);

        // La carte annuelle est la seule source de « quelle classe porte quel
        // semestre » : elle sait aussi lire les notes quand le parcours n'a
        // jamais ete saisi. Sans elle, choisir « Semestre 2 » en restant sur le
        // tronc commun affichait zero, alors que les notes existaient en
        // specialite.
        // On passe l'inscription deja elue : deux elections dans le meme systeme
        // finissaient par se contredire sur les dossiers reinscrits.
        $carte = $anneeId
            ? $this->carteAnnuelle->resolveForInscription($selected, (int) $anneeId, $requestedClasseId)
            : null;

        $classeDuSemestre = null;

        if ($carte !== null && $periode !== 'annuel') {
            $classeDuSemestre = $periode === 'semestre2'
                ? $carte['semestre2_classe_id']
                : $carte['semestre1_classe_id'];
        }

        return [
            'inscription' => $selected,
            'effective_classe_id' => $classeDuSemestre ?? $phase['classe_id'] ?? $selected->classe_id,
            'classe_par_semestre' => $carte === null ? [] : [
                'semestre1' => $carte['semestre1_classe_id'],
                'semestre2' => $carte['semestre2_classe_id'],
            ],
            'journey' => $journey,
            'source_model' => $journey['source_model'],
            'effective_phase' => $phase,
            'all_inscriptions' => $allYearInscriptions,
        ];
    }
}
