<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ESBTPInscriptionController;
use App\Http\Requests\Inscription\StorePreInscriptionRequest;
use App\Services\Admissions\InscriptionWorkflowSettings;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Point d'entree unique de la preinscription caisse.
 *
 * Quand le workflow gere est active, le caissier travaille sur les candidatures
 * acceptees et ne ressaisit jamais l'identite de l'etudiant. Quand il est OFF,
 * on delegue strictement au flux historique afin de ne changer aucun tenant.
 */
final class ManagedCashierEntryController extends Controller
{
    public function __construct(private readonly InscriptionWorkflowSettings $settings)
    {
    }

    public function show(): Response
    {
        $this->settings->ensureDefaults();

        if ($this->settings->usesManagedWorkflow()) {
            return redirect()
                ->route('esbtp.admissions.workflow.index')
                ->with('info', 'Les preinscriptions sont alimentees par les candidatures en ligne acceptees.');
        }

        return app(ESBTPInscriptionController::class)->createPreInscription();
    }

    public function store(StorePreInscriptionRequest $request): Response
    {
        $this->settings->ensureDefaults();

        if ($this->settings->usesManagedWorkflow()) {
            return redirect()
                ->route('esbtp.admissions.workflow.index')
                ->with('warning', "La ressaisie manuelle est desactivee : ouvrez la candidature acceptee depuis la file de caisse.");
        }

        return app(ESBTPInscriptionController::class)->storePreInscription($request);
    }
}
