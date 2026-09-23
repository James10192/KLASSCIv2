<?php

namespace App\Http\Controllers\ESBTP;

use App\Enums\StatutVerificationContact;
use App\Http\Controllers\Controller;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPReinscriptionDemande;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * « Confirmer le contact » : l'ecole a joint la famille (telephone, guichet)
 * et atteste que l'adresse ou le numero du dossier est le bon. La demande
 * redevient eligible au placement en rendez-vous et aux convocations.
 */
class ESBTPConfirmationContactController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:inscriptions.candidatures.process')->only('candidature');
        $this->middleware('permission:reinscriptions.demandes.process')->only('reinscription');
    }

    public function candidature(ESBTPCandidature $candidature): RedirectResponse
    {
        return $this->confirmer($candidature);
    }

    public function reinscription(ESBTPReinscriptionDemande $demande): RedirectResponse
    {
        return $this->confirmer($demande);
    }

    private function confirmer(Model $demande): RedirectResponse
    {
        if (! $demande->contactAConfirmer()) {
            return back()->with('info', 'Le contact de ce dossier n\'est pas à confirmer.');
        }

        $demande->poserVerificationContact(StatutVerificationContact::Verifie);
        Log::info('Contact confirme par l\'etablissement', [
            'type' => $demande->typeDemandePublique(),
            'id' => $demande->getKey(),
            'par_utilisateur' => auth()->id(),
        ]);

        return back()->with('success', 'Contact confirmé : le dossier peut être convoqué.');
    }
}
