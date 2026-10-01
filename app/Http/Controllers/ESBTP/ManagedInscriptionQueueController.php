<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Services\Admissions\InscriptionWorkflowSettings;
use App\Services\Admissions\ManagedInscriptionSequence;
use Illuminate\Http\Request;

final class ManagedInscriptionQueueController extends Controller
{
    public function __construct(
        private readonly ManagedInscriptionSequence $sequence,
        private readonly InscriptionWorkflowSettings $settings,
    ) {
    }

    public function index(Request $request)
    {
        abort_unless($this->settings->usesManagedWorkflow(), 404);

        $etapes = [
            ManagedInscriptionSequence::ETAPE_CAISSE => 'À encaisser',
            ManagedInscriptionSequence::ETAPE_PIECES => 'Pièces à contrôler',
            ManagedInscriptionSequence::ETAPE_SUITE => 'Activation, classe, finalisation',
            ManagedInscriptionSequence::ETAPE_TOUS => 'Tous les dossiers en cours',
        ];
        $etape = (string) $request->query('etape', $this->etapeParDefaut($request));
        if (! array_key_exists($etape, $etapes)) {
            $etape = ManagedInscriptionSequence::ETAPE_TOUS;
        }

        return view('esbtp.admissions.workflow.index', [
            'candidatures' => $this->sequence->dossiers($etape, $request->query('q')),
            'recherche' => (string) $request->query('q', ''),
            'etape' => $etape,
            'etapes' => $etapes,
        ]);
    }

    /** L'agent arrive sur ce qu'il peut traiter, sans réglage. */
    private function etapeParDefaut(Request $request): string
    {
        $user = $request->user();

        return match (true) {
            $user->can('paiements.create') && $user->can('paiements.validate') && ! $user->can('pieces_dossier.suivre') => ManagedInscriptionSequence::ETAPE_CAISSE,
            $user->can('pieces_dossier.suivre') && ! $user->can('inscriptions.validate') => ManagedInscriptionSequence::ETAPE_PIECES,
            default => ManagedInscriptionSequence::ETAPE_TOUS,
        };
    }
}
