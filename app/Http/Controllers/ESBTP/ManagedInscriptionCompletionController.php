<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTPCandidatureWorkflow;
use App\Services\Admissions\FinalizeManagedInscription;
use App\Services\Admissions\InscriptionWorkflowSettings;
use App\Services\Admissions\ManagedInscriptionWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Termine le parcours gere sans laisser le controleur principal recreer une
 * seconde logique d'inscription. Choix de classe par l'etudiant et creation
 * academique/frais/facture/conversion passent par FinalizeManagedInscription.
 */
final class ManagedInscriptionCompletionController extends Controller
{
    public function __construct(
        private readonly ManagedInscriptionWorkflow $managed,
        private readonly InscriptionWorkflowSettings $settings,
        private readonly FinalizeManagedInscription $finalizer,
    ) {
    }

    public function chooseClass(Request $request)
    {
        $this->guardManagedWorkflow();
        abort_unless(
            $this->settings->classChoiceActor() === InscriptionWorkflowSettings::CLASS_ACTOR_STUDENT,
            403,
        );

        $data = $request->validate(['classe_id' => ['required', 'integer']]);
        $workflow = $this->studentWorkflow($request);

        if (! $workflow->profileCompleted()) {
            throw ValidationException::withMessages([
                'profil' => "Complétez d'abord vos informations avant de confirmer votre classe.",
            ]);
        }

        // Choix et finalisation dans UNE transaction : si l'inscription ne
        // peut pas être créée (classe complétée entre-temps, dossier devenu
        // incomplet), rien n'est verrouillé et l'étudiant peut choisir à nouveau.
        $inscription = $this->finalizer->chooseAndFinalize(
            $workflow,
            (int) $data['classe_id'],
            (int) $request->user()->id,
        );

        return redirect()
            ->route('esbtp.admissions.workflow.student')
            ->with('success', 'Votre classe est confirmée et votre inscription est terminée. Bienvenue !');
    }

    public function finalize(ESBTPCandidatureWorkflow $workflow)
    {
        $this->guardManagedWorkflow();

        if (! $workflow->profileCompleted()) {
            throw ValidationException::withMessages([
                'profil' => "L'étudiant doit compléter ses informations avant la finalisation de l'inscription.",
            ]);
        }

        $inscription = $this->finalizer->handle($workflow, (int) Auth::id());

        return redirect()
            ->route('esbtp.inscriptions.show', $inscription)
            ->with('success', 'Inscription finalisée depuis la candidature en ligne.');
    }

    private function studentWorkflow(Request $request): ESBTPCandidatureWorkflow
    {
        $workflow = ESBTPCandidatureWorkflow::query()
            ->with(['candidature', 'etudiant.user', 'paiement', 'selectedClass', 'finalInscription'])
            ->whereHas('etudiant', fn ($q) => $q->where('user_id', $request->user()->id))
            ->latest('id')
            ->firstOrFail();

        abort_unless($this->managed->belongsToAuthenticatedStudent($workflow, $request->user()), 403);

        return $workflow;
    }

    private function guardManagedWorkflow(): void
    {
        abort_unless($this->settings->usesManagedWorkflow(), 404);
    }
}
