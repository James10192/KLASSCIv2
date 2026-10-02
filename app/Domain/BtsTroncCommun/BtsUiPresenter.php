<?php

namespace App\Domain\BtsTroncCommun;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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
            'inscription' => $inscription,  // Pour que les partials puissent retrouver l'inscription source
            'inscription_id' => $inscription->id,
        ];
    }

    /**
     * Relations lues par forStudent() sur les inscriptions deja chargees d'un
     * etudiant. Une liste les precharge en une fois : loadMissing(RELATIONS_ETUDIANT).
     */
    public const RELATIONS_ETUDIANT = [
        'inscriptions.anneeUniversitaire',
        'inscriptions.filiere',
        'inscriptions.phases.classe.filiere',
        'inscriptions.inscriptionOrigine.classe.filiere',
        'inscriptions.inscriptionSpecialisation.classe.filiere',
    ];

    /**
     * Toutes les inscriptions d'un ou plusieurs etudiants, dans l'ordre ou
     * forStudent() les examine quand aucune inscription chargee n'est de tronc
     * commun. Une liste l'interroge une fois pour toute la page.
     */
    public function inscriptionsCandidates(): Builder
    {
        return ESBTPInscription::query()
            ->with([
                'anneeUniversitaire',
                'filiere',
                'phases.classe.filiere',
                'inscriptionOrigine.classe.filiere',
                'inscriptionSpecialisation.classe.filiere',
            ])
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderByDesc('date_inscription')
            ->orderByDesc('id');
    }

    /**
     * @param  Collection<int, ESBTPInscription>|null  $candidates  toutes les inscriptions
     *         de l'etudiant, dans l'ordre de inscriptionsCandidates() ; null = les lire.
     */
    public function forStudent(ESBTPEtudiant $etudiant, ?Collection $candidates = null): ?array
    {
        $etudiant->loadMissing(self::RELATIONS_ETUDIANT);

        $inscription = $etudiant->inscriptions
            ->sortByDesc(function (ESBTPInscription $item) {
                return sprintf(
                    '%d-%d-%s-%010d',
                    $item->status === 'active' ? 1 : 0,
                    $item->anneeUniversitaire?->is_current ? 1 : 0,
                    optional($item->date_inscription)->format('YmdHis') ?? '00000000000000',
                    $item->id
                );
            })
            ->first(fn (ESBTPInscription $item) => $item->filiere?->isTroncCommun() || $item->isSpecialisation() || $item->phases->isNotEmpty());

        if (! $inscription) {
            $candidates ??= $this->inscriptionsCandidates()
                ->where('etudiant_id', $etudiant->id)
                ->get();
            $inscription = $candidates
                ->first(fn (ESBTPInscription $item) => $item->filiere?->isTroncCommun() || $item->isSpecialisation() || $item->phases->isNotEmpty());
        }

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
