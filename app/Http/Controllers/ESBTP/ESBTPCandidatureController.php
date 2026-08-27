<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTPCandidature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Corbeille des candidatures deposees en ligne par les nouveaux eleves.
 *
 * Elle existe pour la meme raison que la corbeille des reinscriptions : sans
 * elle, le canal public ne serait qu'un formulaire de contact.
 *
 * Une difference assumee avec la reinscription : on ne convertit PAS d'un clic.
 * Reinscrire, c'est reconduire un dossier qui existe ; admettre un nouveau,
 * c'est une decision — verifier les pieces, juger le dossier, affecter une
 * classe. La corbeille prepare donc le terrain (elle pre-remplit le formulaire
 * d'inscription) et laisse l'ecole decider. Convertir automatiquement
 * creerait des etudiants sur la foi d'un formulaire public.
 */
class ESBTPCandidatureController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:inscriptions.candidatures.view')->only('index');
        $this->middleware('permission:inscriptions.candidatures.process')->only(['accepter', 'rejeter']);
    }

    public function index(Request $request): View
    {
        $statut = $request->string('statut')->toString();
        $statutsConnus = [
            ESBTPCandidature::STATUT_EN_ATTENTE,
            ESBTPCandidature::STATUT_ACCEPTEE,
            ESBTPCandidature::STATUT_REJETEE,
            ESBTPCandidature::STATUT_CONVERTIE,
        ];

        $candidatures = ESBTPCandidature::query()
            ->with(['anneeUniversitaire:id,name', 'filiere:id,name', 'niveau:id,name', 'traitePar:id,name'])
            ->when(in_array($statut, $statutsConnus, true), fn ($q) => $q->where('statut', $statut))
            ->orderByRaw("FIELD(statut, 'en_attente') DESC")
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('esbtp.inscriptions.candidatures.index', [
            'candidatures' => $candidatures,
            'compteurs' => ESBTPCandidature::query()
                ->selectRaw('statut, COUNT(*) as total')
                ->groupBy('statut')
                ->pluck('total', 'statut'),
            'statutActif' => in_array($statut, $statutsConnus, true) ? $statut : '',
        ]);
    }

    /**
     * Marque une candidature comme acceptee.
     *
     * Ne cree rien : c'est un accuse de decision, qui sort le dossier de la
     * file d'attente et le range dans « a inscrire ». L'inscription elle-meme
     * se fait par le flux habituel, avec les pieces sous les yeux.
     */
    public function accepter(Request $request, ESBTPCandidature $candidature): RedirectResponse
    {
        if (! $candidature->estTraitable()) {
            return back()->with('error', 'Cette candidature a déjà été traitée.');
        }

        $candidature->update([
            'statut' => ESBTPCandidature::STATUT_ACCEPTEE,
            'traite_par' => auth()->id(),
            'traite_at' => now(),
        ]);

        Log::info('Candidature acceptee', [
            'candidature_id' => $candidature->id,
            'traite_par' => auth()->id(),
        ]);

        return back()->with('success', 'Candidature acceptée. Vous pouvez maintenant créer l\'inscription.');
    }

    public function rejeter(Request $request, ESBTPCandidature $candidature): RedirectResponse
    {
        if (! $candidature->estTraitable()) {
            return back()->with('error', 'Cette candidature a déjà été traitée.');
        }

        $valide = $request->validate([
            // Un rejet sans motif est un rejet qu'on ne saura pas expliquer a
            // la famille qui rappellera.
            'motif_rejet' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $candidature->update([
            'statut' => ESBTPCandidature::STATUT_REJETEE,
            'motif_rejet' => $valide['motif_rejet'],
            'traite_par' => auth()->id(),
            'traite_at' => now(),
        ]);

        return back()->with('success', 'Candidature rejetée.');
    }
}
