<?php

namespace App\Http\Controllers\ESBTP;

use App\Domain\Comptabilite\Reliquats\Actions\CreerVersementReliquat;
use App\Enums\ModePaiement;
use App\Http\Controllers\Controller;
use App\Models\ESBTPInscription;
use App\Services\MobileMoneyPaymentGuard;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class ReliquatEncaissementController extends Controller
{
    public function etat(Request $request, ESBTPInscription $inscription)
    {
        abort_unless(app(MobileMoneyPaymentGuard::class)->canCreate($request->user()), 403);
        return response()->json(app(\App\Domain\Comptabilite\Reliquats\Services\LireReliquatsCaisse::class)->pour($inscription));
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
