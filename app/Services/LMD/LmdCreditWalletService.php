<?php

declare(strict_types=1);

namespace App\Services\LMD;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPLMDCreditWalletEntry;
use App\Models\ESBTPLMDBulletin;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class LmdCreditWalletService
{
    private const SOURCE_BULLETIN = 'lmd_bulletin';

    public function forStudent(ESBTPEtudiant $etudiant, ?int $currentClasseId = null, array $currentSemesters = []): array
    {
        $bulletins = ESBTPLMDBulletin::query()
            ->where('etudiant_id', $etudiant->id)
            ->with(['anneeUniversitaire', 'resultatsUEs.uniteEnseignement', 'resultatsECUEs.matiere', 'deliberation'])
            ->orderBy('annee_universitaire_id')
            ->orderBy('semestre')
            ->get();

        $ledger = $this->syncBulletins($bulletins);
        $published = $bulletins->filter(fn (ESBTPLMDBulletin $bulletin): bool => (bool) $bulletin->is_published);
        $capitalises = $ledger->isNotEmpty() ? (int) $ledger->sum('credit_delta') : $this->sumKnown($published, 'credits_capitalises');
        $totaux = $ledger->isNotEmpty() ? (int) $ledger->sum('credits_expected_delta') : $this->sumKnown($published, 'credits_totaux');
        $classNames = DB::table('esbtp_classes')
            ->whereIn('id', $bulletins->pluck('classe_id')->filter()->unique()->values())
            ->pluck('name', 'id');
        $parcoursNames = $this->parcoursNames($bulletins->pluck('parcours_id')->filter()->unique()->values());
        $entries = $ledger->isNotEmpty()
            ? $this->ledgerEntries($ledger)
            : $this->entries($published, $classNames, $parcoursNames);

        return [
            'capitalises' => $capitalises,
            'totaux' => $totaux,
            'progression_pct' => $capitalises !== null && $totaux !== null && $totaux > 0
                ? round(($capitalises / $totaux) * 100, 1)
                : null,
            'source_label' => $ledger->isNotEmpty() ? 'Ledger credits LMD' : 'Bulletins LMD publies',
            'last_publication_at' => $published->max('updated_at')?->format('d/m/Y H:i'),
            'entries' => $entries,
            'ledger_entries_count' => $ledger->count(),
            'bulletins' => $bulletins,
            'bulletins_for_current_context' => $currentClasseId
                ? $bulletins->where('classe_id', $currentClasseId)->values()
                : $bulletins,
            'has_unpublished_items' => $bulletins->contains(fn (ESBTPLMDBulletin $bulletin): bool => ! (bool) $bulletin->is_published),
            'semestres' => $currentSemesters,
        ];
    }

    public function syncBulletin(ESBTPLMDBulletin $bulletin): void
    {
        $bulletin->loadMissing(['anneeUniversitaire']);
        $this->syncBulletins(collect([$bulletin]));
    }

    private function syncBulletins(Collection $bulletins): Collection
    {
        if (! Schema::hasTable('esbtp_lmd_credit_wallet_entries')) {
            return collect();
        }

        $bulletins->each(fn (ESBTPLMDBulletin $bulletin): ?ESBTPLMDCreditWalletEntry => $this->syncOneBulletin($bulletin));

        return ESBTPLMDCreditWalletEntry::query()
            ->whereIn('etudiant_id', $bulletins->pluck('etudiant_id')->filter()->unique()->values())
            ->orderBy('id')
            ->get();
    }

    private function syncOneBulletin(ESBTPLMDBulletin $bulletin): ?ESBTPLMDCreditWalletEntry
    {
        $payload = $this->sourcePayload($bulletin);
        $fingerprint = $this->fingerprint($payload);
        $base = ESBTPLMDCreditWalletEntry::query()
            ->where('source_type', self::SOURCE_BULLETIN)
            ->where('source_id', $bulletin->id);

        if ((clone $base)->where('source_fingerprint', $fingerprint)->exists()) {
            return null;
        }

        $creditBalance = (int) (clone $base)->sum('credit_delta');
        $expectedBalance = (int) (clone $base)->sum('credits_expected_delta');
        $targetCredits = (bool) $bulletin->is_published ? (int) ($bulletin->credits_capitalises ?? 0) : 0;
        $targetExpected = (bool) $bulletin->is_published ? (int) ($bulletin->credits_totaux ?? 0) : 0;

        return ESBTPLMDCreditWalletEntry::query()->create($this->entryAttributes(
            $bulletin,
            $payload,
            $fingerprint,
            $targetCredits - $creditBalance,
            $targetExpected - $expectedBalance,
        ));
    }

    private function sumKnown(Collection $bulletins, string $field): ?int
    {
        $known = $bulletins->filter(fn (ESBTPLMDBulletin $bulletin): bool => $bulletin->{$field} !== null);

        if ($known->isEmpty()) {
            return null;
        }

        return (int) $known->sum($field);
    }

    private function entryAttributes(
        ESBTPLMDBulletin $bulletin,
        array $payload,
        string $fingerprint,
        int $creditDelta,
        int $expectedDelta,
    ): array {
        return [
            'etudiant_id' => $bulletin->etudiant_id,
            'bulletin_id' => $bulletin->id,
            'source_type' => self::SOURCE_BULLETIN,
            'source_id' => $bulletin->id,
            'source_fingerprint' => $fingerprint,
            'event_type' => $this->eventType($bulletin, $creditDelta, $expectedDelta),
            'credit_delta' => $creditDelta,
            'credits_expected_delta' => $expectedDelta,
            'annee_universitaire_id' => $bulletin->annee_universitaire_id,
            'classe_id' => $bulletin->classe_id,
            'parcours_id' => $bulletin->parcours_id,
            'semestre' => $bulletin->semestre,
            'moyenne_generale' => $bulletin->moyenne_generale,
            'decision' => $bulletin->decision_deliberation,
            'source_published_at' => $bulletin->is_published ? $bulletin->updated_at : null,
            'source_snapshot' => $payload,
        ];
    }

    private function sourcePayload(ESBTPLMDBulletin $bulletin): array
    {
        return [
            'bulletin_id' => (int) $bulletin->id,
            'etudiant_id' => (int) $bulletin->etudiant_id,
            'annee_universitaire_id' => (int) $bulletin->annee_universitaire_id,
            'annee' => $bulletin->anneeUniversitaire?->display_name ?? $bulletin->anneeUniversitaire?->name,
            'classe_id' => (int) $bulletin->classe_id,
            'classe' => $this->className($bulletin->classe_id),
            'parcours_id' => $bulletin->parcours_id,
            'parcours' => $this->parcoursName($bulletin->parcours_id),
            'semestre' => $bulletin->semestre,
            'moyenne_generale' => $bulletin->moyenne_generale !== null ? (float) $bulletin->moyenne_generale : null,
            'credits_capitalises' => (int) ($bulletin->credits_capitalises ?? 0),
            'credits_totaux' => (int) ($bulletin->credits_totaux ?? 0),
            'decision' => $bulletin->decision_deliberation,
            'is_published' => (bool) $bulletin->is_published,
            'updated_at' => $bulletin->updated_at?->toISOString(),
        ];
    }
    private function className(?int $classeId): ?string
    {
        if (! $classeId || ! Schema::hasTable('esbtp_classes')) {
            return null;
        }

        return DB::table('esbtp_classes')->where('id', $classeId)->value('name');
    }

    private function parcoursName(?int $parcoursId): ?string
    {
        if (! $parcoursId || ! Schema::hasTable('esbtp_lmd_parcours')) {
            return null;
        }

        return DB::table('esbtp_lmd_parcours')->where('id', $parcoursId)->value('name');
    }

    /**
     * Noms des parcours indexes par identifiant, charges en une seule requete.
     */
    private function parcoursNames(Collection $parcoursIds): Collection
    {
        if ($parcoursIds->isEmpty() || ! Schema::hasTable('esbtp_lmd_parcours')) {
            return collect();
        }

        return DB::table('esbtp_lmd_parcours')->whereIn('id', $parcoursIds)->pluck('name', 'id');
    }

    private function fingerprint(array $payload): string
    {
        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function eventType(ESBTPLMDBulletin $bulletin, int $creditDelta, int $expectedDelta): string
    {
        if (! (bool) $bulletin->is_published && ($creditDelta !== 0 || $expectedDelta !== 0)) {
            return 'credit_reversed';
        }

        if ($creditDelta < 0 || $expectedDelta < 0) {
            return 'credit_adjusted';
        }

        return $creditDelta === 0 && $expectedDelta === 0 ? 'source_state_recorded' : 'credit_capitalized';
    }

    private function ledgerEntries(Collection $ledger): Collection
    {
        return $ledger
            ->groupBy('source_id')
            ->map(fn (Collection $entries): ?array => $this->ledgerEntry($entries))
            ->filter()
            ->values();
    }

    private function ledgerEntry(Collection $entries): ?array
    {
        $credits = (int) $entries->sum('credit_delta');
        $expected = (int) $entries->sum('credits_expected_delta');
        if ($credits === 0 && $expected === 0) return null;
        $latest = $entries->sortByDesc('id')->first();
        $snapshot = $latest->source_snapshot ?? [];

        return [
            'bulletin_id' => $snapshot['bulletin_id'] ?? $latest->bulletin_id,
            'annee' => $snapshot['annee'] ?? 'Annee non renseignee',
            'classe' => $snapshot['classe'] ?? null,
            'parcours' => $snapshot['parcours'] ?? null,
            'semestre' => $snapshot['semestre'] ?? $latest->semestre,
            'credits' => $credits,
            'credits_attendus' => $expected,
            'decision' => $snapshot['decision'] ?? $latest->decision,
            'moyenne' => $snapshot['moyenne_generale'] ?? ($latest->moyenne_generale !== null ? (float) $latest->moyenne_generale : null),
            'published_at' => $latest->source_published_at?->format('d/m/Y H:i'),
            'source' => 'ledger_credit_wallet',
        ];
    }

    private function entries(Collection $published, Collection $classNames, Collection $parcoursNames): Collection
    {
        return $published->map(function (ESBTPLMDBulletin $bulletin) use ($classNames, $parcoursNames): array {
            return [
                'bulletin_id' => $bulletin->id,
                'annee' => $bulletin->anneeUniversitaire?->display_name ?? $bulletin->anneeUniversitaire?->name ?? 'Annee non renseignee',
                'classe' => $classNames->get($bulletin->classe_id),
                'parcours' => $parcoursNames->get($bulletin->parcours_id),
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