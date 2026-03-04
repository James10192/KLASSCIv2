<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ContractExpiryController extends Controller
{
    /**
     * Retourner le statut d'expiration en JSON (polling AJAX)
     */
    public function status(Request $request)
    {
        $expiryData = session('contract_expiry');
        $shouldShow = session('contract_expiry_should_show', false);
        $lastShown = session('contract_expiry_last_shown', 0);

        return response()->json([
            'has_warning'    => (bool) $expiryData,
            'should_show'    => $shouldShow && (bool) $expiryData,
            'expiry'         => $expiryData,
            'last_shown_ago' => $lastShown ? (time() - $lastShown) : null,
            'next_show_in'   => $lastShown
                ? max(0, \App\Http\Middleware\ContractExpiryMiddleware::DISMISS_COOLDOWN_SECONDS - (time() - $lastShown))
                : 0,
        ]);
    }

    /**
     * Marquer le modal comme "vu" — remet le cooldown 12h
     */
    public function dismiss(Request $request)
    {
        session([
            'contract_expiry_last_shown' => time(),
            'contract_expiry_should_show' => false,
        ]);

        return response()->json([
            'success'       => true,
            'next_show_in'  => \App\Http\Middleware\ContractExpiryMiddleware::DISMISS_COOLDOWN_SECONDS,
        ]);
    }
}
