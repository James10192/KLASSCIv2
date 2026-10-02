<?php

namespace App\Http\Controllers\Assistant;

use App\Domain\Assistant\Support\ConversationDeSupport;
use App\Domain\Assistant\Support\FilDeSupport;
use App\Domain\Assistant\Support\GuideDeSupport;
use App\Domain\Assistant\Support\Intention;
use App\Domain\Support\Services\ContexteDePage;
use App\Domain\Support\Services\DisponibiliteSupport;
use App\Http\Controllers\Controller;
use App\Services\Care\ClientMasterSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

/**
 * Nanan dans « Aide & support » : un tour de conversation par appel.
 *
 * Mince par construction : la conversation vit dans ConversationDeSupport,
 * l'envoi de la demande reste celui du support (support.demandes.store), que
 * l'écran appelle lui-même une fois le récapitulatif relu.
 */
class SupportController extends Controller
{
    public function tour(Request $request, DisponibiliteSupport $disponibilite, ConversationDeSupport $conversation, ClientMasterSupport $master): JsonResponse
    {
        abort_unless($disponibilite->signalement(), 404);

        // Chaque tour coûte un appel au modèle : la limite par minute de la
        // route ne borne pas une journée entière.
        $cle = 'support-tour:'.$request->user()->getKey().':'.now()->toDateString();
        if (RateLimiter::tooManyAttempts($cle, (int) config('support.tours_par_jour', 40))) {
            return response()->json([
                'message' => "Vous avez beaucoup échangé avec Nanan aujourd'hui. Ouvrez « Mes demandes d'aide » pour écrire directement à l'équipe support.",
            ], 429);
        }
        RateLimiter::hit($cle, (int) now()->diffInSeconds(now()->endOfDay()) + 1);

        $donnees = $request->validate([
            'intention' => ['required', Rule::in(array_column(Intention::cases(), 'value'))],
            'fil' => ['nullable', 'array', 'max:' . FilDeSupport::MESSAGES_MAX],
            'fil.*.role' => ['required', Rule::in(['nanan', 'personne'])],
            'fil.*.texte' => ['required', 'string', 'max:' . FilDeSupport::LONGUEUR_MAX],
            'fil.*.type' => ['nullable', Rule::in(FilDeSupport::TYPES)],
            'page' => ['nullable', 'array'],
            'page.titre' => ['nullable', 'string', 'max:200'],
            'recapitulatif' => ['nullable', 'boolean'],
        ]);

        $intention = Intention::from($donnees['intention']);
        $fil = FilDeSupport::depuis($donnees['fil'] ?? []);
        $verifie = ContexteDePage::assainir((array) $request->input('page', []), $request);
        $page = [
            // Le titre de l'onglet ne sert qu'à Nanan pour situer la personne :
            // ConversationDeSupport le retire de chaque tour, et la transcription
            // ci-dessous aussi. Au Master ne partent que la route et le module.
            'titre' => isset($donnees['page']['titre']) ? mb_substr(trim($donnees['page']['titre']), 0, 120) : null,
            'route' => $verifie['route_name'] ?? null,
            'module' => $verifie['module'] ?? null,
            'entite' => $verifie['entity'] ?? null,
        ];

        $tour = $conversation->tour($request->user(), $intention, $fil, $page, (bool) ($donnees['recapitulatif'] ?? false));

        $reponse = $tour->versTableau();
        if ($tour->recap !== null) {
            // L'échange accompagne la demande : la moitié de la place au plus,
            // le reste revient au récapitulatif que la personne peut allonger.
            $reponse['transcription'] = ConversationDeSupport::sansTitre(
                $fil->transcription(intdiv((int) $master->limites()['description_max'], 2)),
                array_filter([(string) $page['titre'], (string) GuideDeSupport::titrePage($page)])
            );
        }

        return response()->json($reponse);
    }
}
