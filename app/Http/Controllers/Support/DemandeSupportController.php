<?php

namespace App\Http\Controllers\Support;

use App\Domain\Support\Actions\SoumettreDemande;
use App\Domain\Support\Exceptions\MasterSupportIndisponible;
use App\Domain\Support\Exceptions\MasterSupportRefus;
use App\Domain\Support\Models\SupportOutbox;
use App\Domain\Support\Services\ContexteDePage;
use App\Domain\Support\Services\DisponibiliteSupport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\SoumettreDemandeRequest;
use App\Services\Care\ClientMasterSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * KLASSCI Care cote ecole : signaler, puis suivre ses demandes.
 *
 * Mince par construction : le contexte se calcule dans ContexteDePage, l'envoi
 * dans SoumettreDemande, le transport dans ClientMasterSupport. Les demandes
 * elles-memes vivent au Master ; cette application n'en garde que la boite
 * d'envoi.
 */
class DemandeSupportController extends Controller
{
    public function __construct(
        private readonly DisponibiliteSupport $disponibilite,
        private readonly ClientMasterSupport $master,
    ) {
    }

    public function store(SoumettreDemandeRequest $request, SoumettreDemande $soumettre): JsonResponse
    {
        abort_unless($this->disponibilite->signalement(), 404);

        try {
            $resultat = $soumettre->executer(
                $request->user(),
                $request->validated('categorie'),
                $request->validated('description'),
                ContexteDePage::assainir((array) $request->validated('contexte', []), $request),
                $request->validated('cle'),
                $request->attributes->get('request_id'),
            );
        } catch (MasterSupportRefus $e) {
            if ($e->codeErreur === 'idempotency_key_reused') {
                // Le brouillon a change depuis un envoi que le Master a bien recu :
                // le navigateur tire une cle neuve et renvoie. Pas une faute a montrer.
                return response()->json(['erreur' => 'cle_perimee'], 409);
            }

            // Un refus ici est un defaut d'integration, pas une faute de l'utilisateur :
            // on le journalise et on lui propose le courriel plutot que de lui montrer un code.
            Log::error('KLASSCI Care : signalement refusé par le Master', ['statut' => $e->statut, 'code' => $e->codeErreur, 'erreurs' => $e->erreurs]);

            return response()->json([
                'message' => "Votre demande n'a pas pu être transmise. Écrivez-nous à ".config('app.support_email').'.',
            ], 422);
        }

        if ($resultat['en_attente'] ?? false) {
            return response()->json([
                'en_attente' => true,
                'message' => 'Votre demande est enregistrée. Elle sera transmise au support dès que la connexion sera rétablie.',
            ], 202);
        }

        return response()->json([
            'reference' => $resultat['reference'] ?? null,
            'statut' => $resultat['statut']['libelle'] ?? null,
            'suivi_url' => $this->disponibilite->suivi() && isset($resultat['reference'])
                ? route('support.demandes.show', $resultat['reference']) : null,
        ], 201);
    }

    public function index(Request $request)
    {
        abort_unless($this->disponibilite->suivi(), 404);

        $portee = $this->portee($request);
        $demandes = null;
        $indisponible = false;
        try {
            $demandes = $this->master->lister($request->user()->getKey(), $portee, max(1, (int) $request->query('page', 1)));
        } catch (MasterSupportIndisponible|MasterSupportRefus) {
            $indisponible = true;
        }

        $donnees = [
            'demandes' => $demandes['data'] ?? [],
            'meta' => $demandes['meta'] ?? ['page' => 1, 'pages' => 1, 'total' => 0],
            'portee' => $portee,
            'indisponible' => $indisponible,
        ];
        if ($request->ajax()) {
            return response()->json(['liste' => view('support.demandes._liste', $donnees)->render(), 'portee' => $portee]);
        }

        return view('support.demandes.index', $donnees + [
            'boiteEnvoi' => SupportOutbox::aMontrer()->where('user_id', $request->user()->getKey())->latest()->get(),
            'peutVoirEcole' => $request->user()->can('support.tickets.view_school'),
            'signalementOuvert' => $this->disponibilite->signalement(),
        ]);
    }

    public function show(Request $request, string $reference)
    {
        abort_unless($this->disponibilite->suivi(), 404);
        abort_unless(preg_match('/^KC-\d{4}-\d{6,}$/', $reference) === 1, 404);

        try {
            $demande = $this->master->afficher($reference, $request->user()->getKey(), $this->portee($request, defaut: 'school'));
        } catch (MasterSupportIndisponible|MasterSupportRefus) {
            return view('support.demandes.show', ['demande' => null, 'reference' => $reference, 'indisponible' => true]);
        }

        abort_if($demande === null, 404);

        return view('support.demandes.show', ['demande' => $demande, 'reference' => $reference, 'indisponible' => false]);
    }

    /**
     * `school` seulement avec la permission ; tout autre cas retombe sur `mine`.
     * Le Master borne deja a l'instance, cette permission borne a la personne.
     */
    private function portee(Request $request, string $defaut = 'mine'): string
    {
        $voulue = $request->query('portee', $defaut === 'school' ? 'ecole' : 'moi');

        return $voulue === 'ecole' && $request->user()->can('support.tickets.view_school') ? 'school' : 'mine';
    }
}
