<?php

namespace App\Http\Controllers\Securite;

use App\Domain\Securite\CodesDeSecours;
use App\Domain\Securite\DoubleAuthentification;
use App\Helpers\SettingsHelper;
use App\Http\Controllers\Controller;
use App\Http\Middleware\ExigerDoubleAuthentification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * L'inscription au second facteur, et sa présentation à la connexion.
 *
 * Deux parcours, tenus séparés à dessein : on s'inscrit depuis son profil, à
 * tête reposée ; on présente son code au moment de se connecter, souvent
 * pressé. Les mélanger produirait un écran qui essaie de faire les deux et
 * qu'on ne comprend ni dans un cas ni dans l'autre.
 */
class DoubleAuthentificationController extends Controller
{
    public function __construct(private DoubleAuthentification $doubleAuth)
    {
        $this->middleware('auth');
    }

    /* ─────────────────────── S'inscrire ─────────────────────── */

    /** L'écran de mise en place : QR code, secret en clair, et l'explication. */
    public function reglages(Request $request)
    {
        $utilisateur = $request->user();
        $confirmee = $this->doubleAuth->estConfirmee($utilisateur);

        $secret = null;
        $qrCode = null;

        if (! $confirmee) {
            // Un secret par visite tant que rien n'est confirmé : si quelqu'un
            // abandonne à mi-chemin, le secret à moitié posé ne doit pas
            // rester valable.
            $secret = $this->doubleAuth->nouveauSecret();
            $request->session()->put('double_auth_secret_en_attente', $secret);

            $qrCode = $this->doubleAuth->qrCodeSvg($this->doubleAuth->adresseOtp(
                $this->nomEtablissement(),
                $utilisateur->email ?: $utilisateur->username,
                $secret,
            ));
        }

        return view('securite.double-auth.reglages', [
            'confirmee' => $confirmee,
            'secret' => $secret,
            'qrCode' => $qrCode,
            'exigeeParLEcole' => $this->doubleAuth->rolesConcernes($utilisateur),
            'codesRestants' => $confirmee ? count($utilisateur->double_auth_codes_secours ?? []) : 0,
        ]);
    }

    /** Confirme le secret en attente, et remet les codes de secours. */
    public function confirmer(Request $request)
    {
        $request->validate(
            ['code' => ['required', 'string']],
            ['code.required' => 'Saisissez le code affiché par votre application.'],
        );

        $secret = $request->session()->get('double_auth_secret_en_attente');

        if ($secret === null) {
            return back()->withErrors(['code' => 'La mise en place a expiré. Rechargez la page et recommencez.']);
        }

        if (! $this->doubleAuth->codeValide($secret, $request->input('code'))) {
            return back()->withErrors(['code' => "Ce code ne correspond pas. Vérifiez que l'heure de votre téléphone est à jour, puis réessayez."]);
        }

        $codes = CodesDeSecours::generer();

        $utilisateur = $request->user();
        $utilisateur->forceFill([
            'double_auth_secret' => $secret,
            'double_auth_codes_secours' => $codes,
            'double_auth_confirme_le' => now(),
        ])->save();

        $request->session()->forget('double_auth_secret_en_attente');
        $request->session()->put(ExigerDoubleAuthentification::CLE_SESSION, true);

        Log::info('[securite] second facteur active', ['user_id' => $utilisateur->id]);

        // Les codes ne sont montrés qu'ici, une seule fois. Les garder en clair
        // pour pouvoir les réafficher annulerait leur intérêt.
        return view('securite.double-auth.codes', ['codes' => $codes]);
    }

    /** Retire le second facteur, après avoir revérifié le mot de passe. */
    public function desactiver(Request $request)
    {
        $request->validate(
            ['password' => ['required', 'current_password']],
            ['password.current_password' => 'Ce mot de passe ne correspond pas.'],
        );

        $utilisateur = $request->user();

        // On redemande le mot de passe : sans cela, une session laissée
        // ouverte sur un poste partagé suffirait à retirer la protection.
        $utilisateur->forceFill([
            'double_auth_secret' => null,
            'double_auth_codes_secours' => null,
            'double_auth_confirme_le' => null,
        ])->save();

        $request->session()->forget(ExigerDoubleAuthentification::CLE_SESSION);

        Log::warning('[securite] second facteur desactive', ['user_id' => $utilisateur->id]);

        return redirect()->route('securite.double-auth.reglages')
            ->with('success', 'La double authentification est désactivée pour votre compte.');
    }

    /* ─────────────────────── Se connecter ─────────────────────── */

    /** L'écran de saisie, après le mot de passe. */
    public function demande(Request $request)
    {
        if (! $this->doubleAuth->estExigee($request->user())) {
            return redirect()->intended('/');
        }

        return view('securite.double-auth.demande');
    }

    /**
     * Vérifie le code, ou un code de secours.
     *
     * La limitation de débit est posée sur la route : six chiffres, c'est un
     * million de possibilités, et sans limite un script en essaie assez pour
     * en trouver une avant l'expiration du créneau.
     */
    public function verifier(Request $request)
    {
        $request->validate(['code' => ['required', 'string']]);

        $utilisateur = $request->user();
        $saisi = trim($request->input('code'));

        if ($this->doubleAuth->codeValide($utilisateur->double_auth_secret, $saisi)) {
            $request->session()->put(ExigerDoubleAuthentification::CLE_SESSION, true);

            return redirect()->intended('/');
        }

        $restants = CodesDeSecours::consommer($utilisateur->double_auth_codes_secours ?? [], $saisi);

        if ($restants !== null) {
            $utilisateur->forceFill(['double_auth_codes_secours' => $restants])->save();
            $request->session()->put(ExigerDoubleAuthentification::CLE_SESSION, true);

            Log::warning('[securite] connexion par code de secours', [
                'user_id' => $utilisateur->id,
                'restants' => count($restants),
            ]);

            return redirect()->intended('/')->with(
                'warning',
                'Vous vous êtes connecté avec un code de secours. Il ne vous en reste que ' . count($restants) . '.',
            );
        }

        Log::warning('[securite] code de second facteur refuse', [
            'user_id' => $utilisateur->id,
            'ip' => $request->ip(),
        ]);

        return back()->withErrors(['code' => 'Code incorrect.']);
    }

    /** Le nom de l'établissement, qui distingue les lignes dans l'application. */
    private function nomEtablissement(): string
    {
        try {
            $nom = SettingsHelper::get('school_name');
        } catch (\Throwable) {
            $nom = null;
        }

        return $nom ?: config('app.name', 'KLASSCI');
    }
}
