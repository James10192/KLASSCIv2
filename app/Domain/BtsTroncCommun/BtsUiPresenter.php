<?php

namespace App\Domain\BtsTroncCommun;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
class BtsUiPresenter
{
    public function __construct(
        private BtsPhaseResolver $resolver,
        private BtsPhaseTimelineBuilder $timelineBuilder
    ) {
    }

    public function forInscription(?ESBTPInscription $inscription): ?array
    {
        if (! $inscription) {
            return null;
        }

        $inscription->loadMissing([
            'filiere',
            'phases.classe.filiere',
            'inscriptionOrigine.classe.filiere',
            'inscriptionSpecialisation.classe.filiere',
        ]);

        if (! $inscription->filiere?->isTroncCommun() && ! $inscription->isSpecialisation() && $inscription->phases->isEmpty()) {
            return null;
        }

        $journey = $this->resolver->buildJourney($inscription);
        $current = $journey['current_phase'];
        $timeline = $this->timelineBuilder->build($inscription);

        return [
            'is_bts_tc' => true,
            'source_model' => $journey['source_model'],
            'badge' => $this->buildBadge($current),
            'current_phase' => $current,
            'timeline' => $timeline,
            'destination' => collect($timeline)->firstWhere('type_phase', 'specialisation'),
            'history' => $this->buildHistory($timeline),
        ];
    }

    public function forStudent(ESBTPEtudiant $etudiant): ?array
    {
        $inscription = $etudiant->inscriptions
            ->first(fn (ESBTPInscription $item) => $item->filiere?->isTroncCommun() || $item->isSpecialisation() || $item->phases->isNotEmpty());

        return $this->forInscription($inscription);
    }

    private function buildBadge(?array $phase): array
    {
        if (! $phase) {
            return ['label' => 'TC en attente', 'tone' => 'muted'];
        }

        if ($phase['type_phase'] === 'specialisation') {
            return ['label' => 'Spécialisation', 'tone' => 'success'];
        }

        return ['label' => 'Tronc commun', 'tone' => 'info'];
    }

    private function buildHistory(array $timeline): array
    {
        return collect($timeline)->map(function (array $phase) {
            $label = $phase['type_phase'] === 'specialisation'
                ? 'Orientation vers ' . ($phase['classe'] ?? 'classe cible')
                : 'Entrée en tronc commun';

            return [
                'label' => $label,
                'date' => $phase['date_activation'],
                'meta' => trim(($phase['filiere'] ?? '') . ' ' . ($phase['classe'] ?? '')),
            ];
        })->values()->all();
    }
}
