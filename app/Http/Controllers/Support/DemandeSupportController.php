<?php

namespace App\Http\Controllers\Support;

use App\Domain\Support\Actions\SoumettreDemande;
use App\Domain\Support\Exceptions\MasterSupportIndisponible;
use App\Domain\Support\Exceptions\MasterSupportRefus;
use App\Domain\Support\Exceptions\PorteeAbsente;
use App\Domain\Support\Models\SupportOutbox;
use App\Domain\Support\Services\ContexteDePage;
use App\Domain\Support\Services\DisponibiliteSupport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\RepondreDemandeRequest;
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

        return view('support.demandes.show', [
            'demande' => $demande,
            'reference' => $reference,
            'indisponible' => false,
            'peutRepondre' => $this->peutRepondre($demande, $request->user()->getKey()),
            'limites' => $this->master->limites(),
        ]);
    }

    /**
     * La reponse de l'ecole. Pas de boite d'envoi ici : si le Master ne
     * repond pas, le texte reste dans le formulaire et l'utilisateur le
     * renvoie, avec la meme cle, sans risque de doublon.
     */
    public function repondre(RepondreDemandeRequest $request, string $reference): JsonResponse
    {
        abort_unless($this->disponibilite->suivi(), 404);
        abort_unless(preg_match('/^KC-\d{4}-\d{6,}$/', $reference) === 1, 404);

        $auteur = $request->user()->getKey();

        try {
            // Toujours `mine` : lire les demandes de l'ecole n'autorise pas a ecrire
            // sur celles des collegues. Le Master repond 404 hors de ce perimetre.
            $demande = $this->master->repondre(
                $reference,
                $auteur,
                'mine',
                $request->validated('corps'),
                $request->user()->name,
                $request->validated('cle'),
            );
        } catch (PorteeAbsente) {
            return response()->json(['message' => "Votre établissement ne peut pas encore répondre au support depuis KLASSCI. Écrivez-nous à ".config('app.support_email').'.', 'peut_repondre' => false], 403);
        } catch (MasterSupportIndisponible) {
            return response()->json(['message' => "Le support est momentanément injoignable. Votre réponse est conservée ici : renvoyez-la dans un instant."], 503);
        } catch (MasterSupportRefus $e) {
            // 404 et non 403 au Master : une reference hors de portee n'existe pas.
            abort_if($e->statut === 404, 404);

            return $e->codeErreur === 'ticket_closed'
                ? $this->demandeFermee($reference, $auteur)
                : $this->refusInattendu($e);
        }

        return response()->json([
            'fil' => view('support.demandes._fil', ['messages' => $demande['messages'] ?? []])->render(),
            'statut' => view('support.demandes._statut', ['statut' => $demande['statut'] ?? []])->render(),
            'peut_repondre' => $this->peutRepondre($demande, $auteur),
        ]);
    }

    /**
     * La demande a ete fermee pendant que l'ecole ecrivait : le formulaire
     * disparait et le statut affiche rejoint celui du Master.
     */
    private function demandeFermee(string $reference, int $auteur): JsonResponse
    {
        try {
            $statut = $this->master->afficher($reference, $auteur)['statut'] ?? null;
        } catch (MasterSupportIndisponible|MasterSupportRefus) {
            $statut = null;
        }

        return response()->json([
            'message' => 'Cette demande est fermée : ouvrez-en une nouvelle si le problème revient.',
            'statut' => $statut ? view('support.demandes._statut', ['statut' => $statut])->render() : null,
            'peut_repondre' => false,
        ], 409);
    }

    /** Seul l'auteur de la demande repond, et seulement si l'identifiant le permet. */
    private function peutRepondre(array $demande, int $utilisateurId): bool
    {
        return ($demande['statut']['code'] ?? null) !== 'FERME'
            && (string) ($demande['rapporteur']['id'] ?? '') === (string) $utilisateurId
            && in_array('support:update', $this->master->portees(), true);
    }

    private function refusInattendu(MasterSupportRefus $e): JsonResponse
    {
        Log::error('KLASSCI Care : réponse refusée par le Master', ['statut' => $e->statut, 'code' => $e->codeErreur, 'erreurs' => $e->erreurs]);

        return response()->json(['message' => "Votre réponse n'a pas pu être transmise. Écrivez-nous à ".config('app.support_email').'.'], 422);
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
