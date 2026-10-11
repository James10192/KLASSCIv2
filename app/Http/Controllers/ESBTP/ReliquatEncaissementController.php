<?php

namespace App\Http\Controllers\ESBTP;

use App\Domain\Comptabilite\Reliquats\Actions\CreerVersementReliquat;
use App\Enums\ModePaiement;
use App\Http\Controllers\Controller;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPReliquatDetail;
use App\Services\Finances\SituationFinanciereEtudiant;
use App\Services\MobileMoneyPaymentGuard;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class ReliquatEncaissementController extends Controller
{
    public function etat(Request $request, ESBTPInscription $inscription)
    {
        abort_unless(app(MobileMoneyPaymentGuard::class)->canCreate($request->user()), 403);
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
        return response()->json([
            'reliquats' => $lignes,
            'soldes' => ['courant' => $situation['courant'], 'arrieres' => $situation['arrieres'], 'total' => $situation['total']],
            'inscription_id' => (int) $inscription->id,
        ]);
    }

    public function store(Request $request, CreerVersementReliquat $creer)
    {
        abort_unless(app(MobileMoneyPaymentGuard::class)->canCreate($request->user()), 403);
        $v = $request->validate([
            'reliquat_id' => 'required|integer|exists:esbtp_reliquats_details,id',
            'montant' => 'required|numeric|min:1',
            'mode_paiement' => 'required|string',
            'notes' => 'nullable|string|max:1000',
            'request_key' => 'nullable|uuid',
        ]);
        try {
            $paiement = $creer->execute(
                $request->user(), (int) $v['reliquat_id'],
                (float) $v['montant'], $v['mode_paiement'],
                $v['notes'] ?? null, $v['request_key'] ?? null
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (\App\Exceptions\CaisseCloturee $e) {
            throw ValidationException::withMessages(['mode_paiement' => $e->getMessage()]);
        }
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true, 'paiement_id' => $paiement->id,
                'numero_recu' => $paiement->numero_recu,
                'message' => 'Versement enregistré en attente de validation.',
            ], 201);
        }
        return back()->with('success', 'Versement de reliquat enregistré en attente de validation : '.$paiement->numero_recu);
    }
}
