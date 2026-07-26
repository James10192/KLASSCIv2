<?php

declare(strict_types=1);

namespace App\Services\LMD;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPLMDBulletin;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class LmdCreditWalletService
{
    public function forStudent(ESBTPEtudiant $etudiant, ?int $currentClasseId = null, array $currentSemesters = []): array
    {
        $bulletins = ESBTPLMDBulletin::query()
            ->where('etudiant_id', $etudiant->id)
            ->with(['anneeUniversitaire', 'parcours', 'resultatsUEs.uniteEnseignement', 'resultatsECUEs.matiere', 'deliberation'])
            ->orderBy('annee_universitaire_id')
            ->orderBy('semestre')
            ->get();

        $published = $bulletins->filter(fn (ESBTPLMDBulletin $bulletin): bool => (bool) $bulletin->is_published);
        $capitalises = $this->sumKnown($published, 'credits_capitalises');
        $totaux = $this->sumKnown($published, 'credits_totaux');
        $classNames = DB::table('esbtp_classes')
            ->whereIn('id', $bulletins->pluck('classe_id')->filter()->unique()->values())
            ->pluck('name', 'id');

        return [
            'capitalises' => $capitalises,
            'totaux' => $totaux,
            'progression_pct' => $capitalises !== null && $totaux !== null && $totaux > 0
                ? round(($capitalises / $totaux) * 100, 1)
                : null,
            'source_label' => 'Bulletins LMD publiés',
            'last_publication_at' => $published->max('updated_at')?->format('d/m/Y H:i'),
            'entries' => $this->entries($published, $classNames),
            'bulletins' => $bulletins,
            'bulletins_for_current_context' => $currentClasseId
                ? $bulletins->where('classe_id', $currentClasseId)->values()
                : $bulletins,
            'has_unpublished_items' => $bulletins->contains(fn (ESBTPLMDBulletin $bulletin): bool => ! (bool) $bulletin->is_published),
            'semestres' => $currentSemesters,
        ];
    }

    private function sumKnown(Collection $bulletins, string $field): ?int
    {
        $known = $bulletins->filter(fn (ESBTPLMDBulletin $bulletin): bool => $bulletin->{$field} !== null);

        if ($known->isEmpty()) {
            return null;
        }

        return (int) $known->sum($field);
    }

    private function entries(Collection $published, Collection $classNames): Collection
    {
        return $published->map(function (ESBTPLMDBulletin $bulletin) use ($classNames): array {
            return [
                'bulletin_id' => $bulletin->id,
                'annee' => $bulletin->anneeUniversitaire?->display_name ?? $bulletin->anneeUniversitaire?->name ?? 'Annee non renseignee',
                'classe' => $classNames->get($bulletin->classe_id),
                'parcours' => $bulletin->parcours?->name,
                'semestre' => $bulletin->semestre,
                'credits' => $bulletin->credits_capitalises,
                'credits_attendus' => $bulletin->credits_totaux,
                'decision' => $bulletin->decision_deliberation,
                'moyenne' => $bulletin->moyenne_generale !== null ? (float) $bulletin->moyenne_generale : null,
                'published_at' => $bulletin->updated_at?->format('d/m/Y H:i'),
                'source' => 'bulletin_lmd',
            ];
        })->values();
    }
}
