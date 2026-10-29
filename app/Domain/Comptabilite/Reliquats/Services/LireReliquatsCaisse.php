<?php

namespace App\Domain\Comptabilite\Reliquats\Services;

use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPReliquatDetail;
use App\Services\Finances\SituationFinanciereEtudiant;

final class LireReliquatsCaisse
{
    public function pour(ESBTPInscription $inscription): array
    {
        $ids = ESBTPInscription::query()->where('etudiant_id', $inscription->etudiant_id)->pluck('id');
        $reliquats = ESBTPReliquatDetail::query()->whereIn('inscription_source_id', $ids)
            ->with(['inscriptionSource.anneeUniversitaire', 'inscriptionDestination.anneeUniversitaire', 'fraisSubscription.fraisCategory'])
            ->orderBy('id')->get();
        $doublons = $reliquats->groupBy('frais_subscription_id')->filter(fn ($lignes) => $lignes->count() > 1);
        $reserve = ESBTPPaiement::query()->whereIn('reliquat_detail_id', $reliquats->pluck('id'))
            ->where('type_paiement', 'reliquat')->where('status', 'en_attente')
            ->encaissements()->selectRaw('reliquat_detail_id, SUM(montant) AS total')
            ->groupBy('reliquat_detail_id')->pluck('total', 'reliquat_detail_id');

        $lignes = $reliquats->map(function ($r) use ($reserve, $doublons) {
            $attente = (float) ($reserve[$r->id] ?? 0);
            $coherent = $r->inscriptionSource && $r->inscriptionDestination
                && (int) $r->inscriptionSource->etudiant_id === (int) $r->inscriptionDestination->etudiant_id
                && $r->fraisSubscription && (int) $r->fraisSubscription->inscription_id === (int) $r->inscription_source_id;
            $montantDisponible = max(0, round($r->solde_restant - $attente, 2));
            $anomalie = !$coherent || $doublons->has($r->frais_subscription_id);
            return [
                'id' => (int) $r->id,
                'frais' => $r->fraisSubscription?->fraisCategory?->name ?? 'Frais',
                'origine' => $r->inscriptionSource?->anneeUniversitaire?->name ?? '—',
                'destination' => $r->inscriptionDestination?->anneeUniversitaire?->name ?? '—',
                'initial' => (float) $r->montant_reliquat,
                'regle' => (float) $r->montant_regle,
                'attente' => $attente,
                'disponible' => $montantDisponible,
                'anomalie' => $anomalie,
                'payable' => !$anomalie && $montantDisponible >= 1,
            ];
        })->values();

        $situation = app(SituationFinanciereEtudiant::class)->pour((int) $inscription->etudiant_id, (int) $inscription->id);

        // Une dette non reportée reste payable sur l'inscription qui l'a créée.
        // Ne JAMAIS créer une inscription de la nouvelle année à la caisse.
        $anneeActuelle = \App\Models\ESBTPAnneeUniversitaire::query()->where('is_current', true)->value('id');
        $inscriptionsParId = ESBTPInscription::query()->where('etudiant_id', $inscription->etudiant_id)
            ->get(['id', 'annee_universitaire_id'])->keyBy('id');
        $dettesDirectes = $situation['annees']->filter(function (array $ligne) use ($inscriptionsParId, $anneeActuelle) {
            $origine = $inscriptionsParId->get($ligne['inscription_id']);
            return $origine && ($anneeActuelle === null || (int) $origine->annee_universitaire_id !== (int) $anneeActuelle)
                && (float) $ligne['non_reporte'] >= 1;
        })->map(fn (array $ligne) => [
            'inscription_id' => $ligne['inscription_id'],
            'annee' => $ligne['annee'],
            'classe' => $ligne['classe'],
            'montant' => (float) $ligne['non_reporte'],
            'url' => route('esbtp.paiements.create', [
                'inscription_id' => $ligne['inscription_id'],
                'etudiant_id' => $inscription->etudiant_id,
            ]),
        ])->values();

        return [
            'reliquats' => $lignes,
            'dettes_non_reportees' => $dettesDirectes,
            'soldes' => ['courant' => $situation['courant'], 'arrieres' => $situation['arrieres'], 'total' => $situation['total']],
            'inscription_id' => (int) $inscription->id,
        ];
    }
}
