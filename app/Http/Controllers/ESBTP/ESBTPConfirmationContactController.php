<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Http\Requests\Verification\ConfirmerContactRequest;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPReinscriptionDemande;
use App\Services\Verification\ConfirmationContactEcole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

/**
 * « Confirmer le contact » : l'ecole a joint la famille (telephone, guichet)
 * et atteste que l'adresse ou le numero du dossier est le bon. La demande
 * redevient eligible au placement en rendez-vous et aux convocations.
 */
class ESBTPConfirmationContactController extends Controller
{
    public function __construct(private readonly ConfirmationContactEcole $confirmation)
    {
        $this->middleware('permission:inscriptions.candidatures.process')->only('candidature');
        $this->middleware('permission:reinscriptions.demandes.process')->only('reinscription');
    }

    public function candidature(ConfirmerContactRequest $request, ESBTPCandidature $candidature): RedirectResponse
    {
        return $this->confirmer($request, $candidature);
    }

    public function reinscription(ConfirmerContactRequest $request, ESBTPReinscriptionDemande $demande): RedirectResponse
    {
        return $this->confirmer($request, $demande);
    }

    private function confirmer(ConfirmerContactRequest $request, Model $demande): RedirectResponse
    {
        [$resultat, $replanifiees] = $this->confirmation->confirmer($demande, $request->empreinte(), (int) $request->user()->id);

        return match ($resultat) {
            ConfirmationContactEcole::MODIFIE_ENTRE_TEMPS => back()->with('error', 'Ce dossier a changé depuis l\'affichage de la page (nouveau dépôt ou autre agent). Rechargez-la et vérifiez le contact avant de confirmer.'),
            ConfirmationContactEcole::PAS_A_CONFIRMER => back()->with('info', 'Le contact de ce dossier n\'est pas à confirmer.'),
            default => back()->with('success', 'Contact confirmé : le dossier peut être convoqué.'.($replanifiees > 0
                ? ' '.$replanifiees.' convocation'.($replanifiees > 1 ? 's' : '').' de rendez-vous déjà pris, reprise'.($replanifiees > 1 ? 's' : '').' avec l\'adresse du dossier, partira avec le prochain envoi.'
                : '')),
        };
    }
}
