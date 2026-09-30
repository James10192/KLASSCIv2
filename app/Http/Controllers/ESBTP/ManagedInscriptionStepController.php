<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPCandidatureWorkflow;
use App\Models\ESBTPPieceDeposee;
use App\Services\Admissions\InscriptionWorkflowSettings;
use App\Services\Admissions\ManagedInscriptionSequence;
use App\Services\Admissions\ManagedInscriptionWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Ecritures des etapes physiques du parcours gere.
 *
 * La sequence est verifiee ici avant chaque mutation, de sorte qu'une URL
 * appelee directement ne puisse pas inverser « caisse puis pieces » ou
 * « pieces puis caisse ».
 */
final class ManagedInscriptionStepController extends Controller
{
    public function __construct(
        private readonly ManagedInscriptionWorkflow $managed,
        private readonly ManagedInscriptionSequence $sequence,
        private readonly InscriptionWorkflowSettings $settings,
    ) {
    }

    public function pay(Request $request, ESBTPCandidature $candidature)
    {
        $this->guardManagedWorkflow();
        $workflow = $this->managed->ensure($candidature);
        $this->sequence->assertPaymentAllowed($workflow);

        $allowedModes = array_keys(config('payment_modes.labels', []));
        $data = $request->validate([
            'montant' => ['required', 'numeric', 'min:1'],
            'mode_paiement' => ['required', 'string', Rule::in($allowedModes)],
            'reference_paiement' => ['nullable', 'string', 'max:120'],
            'numero_transaction' => ['nullable', 'string', 'max:120'],
        ]);

        $workflow = $this->managed->recordPayment($candidature, $data, (int) Auth::id());

        return redirect()
            ->route('esbtp.admissions.workflow.show', $candidature)
            ->with('success', 'Préinscription encaissée et rattachée à la candidature. Reçu : '.($workflow->paiement?->numero_recu ?? '—'));
    }

    public function receivePiece(Request $request, ESBTPCandidatureWorkflow $workflow)
    {
        $this->guardManagedWorkflow();
        $this->sequence->assertDocumentsAllowed($workflow->fresh());

        $data = $request->validate([
            'piece_id' => ['required', 'integer'],
            'quantite' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        $this->managed->receivePiece(
            $workflow,
            (int) $data['piece_id'],
            (int) $data['quantite'],
            (int) Auth::id(),
        );

        return back()->with('success', 'Pièce enregistrée dans le dossier physique.');
    }

    public function decidePiece(
        Request $request,
        ESBTPCandidatureWorkflow $workflow,
        ESBTPPieceDeposee $depot,
    ) {
        $this->guardManagedWorkflow();
        $this->sequence->assertDocumentsAllowed($workflow->fresh());

        $data = $request->validate([
            'decision' => ['required', Rule::in(['valider', 'refuser'])],
            'motif' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->managed->decidePiece(
            $workflow,
            $depot,
            $data['decision'] === 'valider',
            $data['motif'] ?? null,
            (int) Auth::id(),
        );

        return back()->with(
            'success',
            $data['decision'] === 'valider' ? 'Pièce validée.' : 'Pièce refusée avec motif.',
        );
    }

    public function validateDocuments(ESBTPCandidatureWorkflow $workflow)
    {
        $this->guardManagedWorkflow();
        $this->sequence->assertDocumentsAllowed($workflow->fresh());
        $this->managed->validateDocuments($workflow, (int) Auth::id());

        return back()->with('success', 'Contrôle physique terminé : le dossier de pièces est complet.');
    }

    private function guardManagedWorkflow(): void
    {
        abort_unless($this->settings->usesManagedWorkflow(), 404);
    }
}
