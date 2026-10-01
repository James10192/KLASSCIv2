<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Services\Admissions\InscriptionWorkflowSettings;
use App\Services\Admissions\ManagedInscriptionSequence;

final class ManagedInscriptionQueueController extends Controller
{
    public function __construct(
        private readonly ManagedInscriptionSequence $sequence,
        private readonly InscriptionWorkflowSettings $settings,
    ) {
    }

    public function index(\Illuminate\Http\Request $request)
    {
        abort_unless($this->settings->usesManagedWorkflow(), 404);

        return view('esbtp.admissions.workflow.index', [
            'candidatures' => $this->sequence->cashierQueue($request->query('q')),
            'recherche' => (string) $request->query('q', ''),
            'mode' => $this->settings->mode(),
        ]);
    }
}
