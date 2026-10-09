<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTPFamilyAccessGrant;
use App\Services\Familles\InvitationResponsable;
use App\Services\Familles\ReglagesFamille;
use App\Rules\MotDePasseNonGenerique;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class InvitationFamilialeController extends Controller
{
    public function inviter(
        Request $request,
        ESBTPFamilyAccessGrant $grant,
        ReglagesFamille $settings,
        InvitationResponsable $service
    ): RedirectResponse {
        abort_unless($settings->enabled(), 404);
        $input = $request->validate([
            'confirmed_email' => ['required', 'email', 'max:255'],
            'verification_contact' => ['required', 'accepted'],
        ]);

        $service->preparer($grant, $request->user(), $input['confirmed_email']);

        return back()->with('success', "Invitation responsable placée dans la file sécurisée. L'envoi n'est pas encore confirmé.");
    }

    public function form(string $token, ReglagesFamille $settings, InvitationResponsable $service): View
    {
        abort_unless($settings->enabled(), 404);
        $invite = $service->parJeton($token);

        return view('esbtp.familles.activation', [
            'token' => $token,
            'parent' => $invite->grant->parent,
        ]);
    }

    public function activer(
        Request $request,
        string $token,
        ReglagesFamille $settings,
        InvitationResponsable $service
    ): RedirectResponse {
        abort_unless($settings->enabled(), 404);
        $password = $request->validate([
            'password' => ['required', 'string', 'min:12', 'confirmed', new MotDePasseNonGenerique],
        ])['password'];

        $user = $service->activer($token, $password);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('esbtp.famille.espace')
            ->with('success', 'Votre compte responsable indépendant est activé.');
    }
}
