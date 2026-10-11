<?php

namespace App\Services\Finances;

use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPReliquatDetail;

/**
 * Lecture, jamais écriture : une dette appartient à l'inscription qui l'a créée.
 *
 * Le report est une référence vers cette créance, PAS un nouveau frais annuel.
 * On compte la partie reportée une seule fois, même si elle traverse plusieurs
 * années. Les anomalies historiques sont signalées, jamais corrigées en GET.
 */
final class SituationFinanciereEtudiant
{
    public function pour(int $etudiantId, ?int $inscriptionCouranteId = null): array
    {
        $inscriptions = ESBTPInscription::query()
            ->where('etudiant_id', $etudiantId)
            ->where('status', '!=', 'annulée')
            ->with(['anneeUniversitaire', 'classe'])
            ->get()
            ->sortBy(fn ($ins) => $ins->anneeUniversitaire?->start_date?->timestamp ?? 0)
            ->values();

        $ids = $inscriptions->pluck('id')->all();
        if ($ids === []) {
            return $this->vide();
        }

        $actuelle = $inscriptionCouranteId
            ? $inscriptions->firstWhere('id', $inscriptionCouranteId)
            : $inscriptions->last();
        // La dernière inscription connue est le repère, mais on ne réattribue
        // jamais les règlements d'une autre année à cette inscription.
        if (! $actuelle) {
            $actuelle = $inscriptions->last();
        }

        $frais = ESBTPFraisSubscription::query()
            ->whereIn('inscription_id', $ids)
            ->charged()
            ->get()
            ->groupBy('inscription_id');

        $reliquats = ESBTPReliquatDetail::query()
            ->whereIn('inscription_source_id', $ids)
            ->with(['fraisSubscription.fraisCategory', 'inscriptionSource.anneeUniversitaire', 'inscriptionDestination.anneeUniversitaire'])
            ->orderBy('id')
            ->get();

        // Une créance d'origine ne doit figurer QU'UNE fois dans les totaux.
        // Les éventuels doublons historiques sont exposés au contrôle, pas effacés.
        $doublons = $reliquats->groupBy('frais_subscription_id')->filter(fn ($g) => $g->count() > 1);
        $uniques = $reliquats->groupBy('frais_subscription_id')->map(fn ($g) => $g->last())->values();
        $reportParSource = $uniques->groupBy('inscription_source_id')
            ->map(fn ($g) => (float) $g->sum('montant_reliquat'));

        $annees = $inscriptions->map(function ($ins) use ($frais, $reportParSource, $actuelle) {
            $du = (float) ($frais->get($ins->id, collect()))->sum(fn ($s) => $s->chargedAmount());
            $paye = ESBTPPaiement::netPaidForInscription((int) $ins->id);
            $reporte = (float) ($reportParSource->get($ins->id) ?? 0);
            $nonReporte = max(0.0, round($du - $paye - $reporte, 2));

            return [
                'inscription_id' => (int) $ins->id,
                'annee' => $ins->anneeUniversitaire?->name ?? 'Année inconnue',
                'classe' => $ins->classe?->name ?? '—',
                'courante' => (int) $ins->id === (int) $actuelle->id,
                'du' => $du,
                'paye' => $paye,
                'reporte' => $reporte,
                'non_reporte' => $nonReporte,
            ];
        })->values();

        $lignesReliquats = $uniques->filter(fn ($r) => $r->statut !== 'totalement_regle' && $r->solde_restant > 0)
            ->map(fn ($r) => [
                'reliquat_id' => (int) $r->id,
                'inscription_source_id' => (int) $r->inscription_source_id,
                'annee_origine' => $r->inscriptionSource?->anneeUniversitaire?->name ?? '—',
                'annee_report' => $r->inscriptionDestination?->anneeUniversitaire?->name ?? '—',
                'frais' => $r->fraisSubscription?->fraisCategory?->name ?? 'Frais non précisé',
                'initial' => (float) $r->montant_reliquat,
                'regle' => (float) $r->montant_regle,
                'restant' => max(0.0, (float) $r->solde_restant),
            ])->values();

        $courant = (float) $annees->where('courante', true)->sum('non_reporte');
        $arriérésDirects = (float) $annees->where('courante', false)->sum('non_reporte');
        $arriérésReportes = (float) $lignesReliquats->sum('restant');
        $arriérés = round($arriérésDirects + $arriérésReportes, 2);

        return [
            'annees' => $annees,
            'reliquats' => $lignesReliquats,
            'courant' => round($courant, 2),
            'arrieres' => $arriérés,
            'arrieres_non_reportes' => round($arriérésDirects, 2),
            'arrieres_reportes' => round($arriérésReportes, 2),
            'total' => round($courant + $arriérés, 2),
            'anomalies' => $doublons->count(), // créances d'origine reportées plusieurs fois
            'inscription_courante_id' => (int) $actuelle->id,
        ];
    }

    private function vide(): array
    {
        return [
            'annees' => collect(), 'reliquats' => collect(), 'courant' => 0.0,
            'arrieres' => 0.0, 'arrieres_non_reportes' => 0.0,
            'arrieres_reportes' => 0.0, 'total' => 0.0,
            'anomalies' => 0, 'inscription_courante_id' => null,
        ];
    }
}
