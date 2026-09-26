<?php

namespace App\View\Components\Chatbot;

use App\Models\ChatbotUserPreference;
use App\Domain\Assistant\Modeles\ModeleIa;
use App\Domain\Assistant\Modeles\RegistreDesModeles;
use App\Domain\Assistant\Outils\CatalogueOutils;
use Illuminate\Support\Facades\Log;
use Illuminate\View\Component;

/**
 * Assistant IA (panneau latéral sur ordinateur, plein écran sur téléphone).
 *
 * Les suggestions de l'écran d'accueil viennent des outils que l'utilisateur
 * peut réellement utiliser : aucune liste par rôle, le filtre est le même que
 * celui qui décide des outils déclarés au modèle.
 */
class Assistant extends Component
{
    private const MAX_SUGGESTIONS = 6;

    public function render()
    {
        return view('components.chatbot.assistant', [
            'astConfig' => $this->configuration(),
        ]);
    }

    private function configuration(): array
    {
        $user = auth()->user();

        return [
            'prenom' => $user ? $this->prenom($user) : '',
            'suggestions' => $user ? $this->suggestions($user) : [],
            'maxLength' => 1000,
            'modeles' => $user ? $this->modeles($user) : [],
            'csrfToken' => csrf_token(),
            'routes' => [
                'message' => route('chatbot.message'),
                'messageStream' => route('chatbot.message.stream'),
                'conversations' => route('chatbot.conversations'),
                'history' => route('chatbot.history', ['conversationId' => '__ID__']),
                'delete' => route('chatbot.delete', ['conversationId' => '__ID__']),
                'preferences' => route('chatbot.preferences'),
                'preferencesUpdate' => route('chatbot.preferences.update'),
                'preferencesMemory' => route('chatbot.preferences.memory'),
                'formFraisCategory' => route('chatbot.forms.frais-category'),
                'formFraisConfig' => route('chatbot.forms.frais-config'),
                'formInscriptionsFilter' => route('chatbot.forms.inscriptions-filter'),
                'retour' => route('chatbot.messages.retour', ['message' => '__ID__'], false),
                'pieces' => route('chatbot.pieces.deposer', [], false),
                'signaler' => route('chatbot.messages.signaler', ['message' => '__ID__'], false),
            ],
            'raisons' => \App\Domain\Assistant\Retours\RetourDeReponse::RAISONS,
            'care' => $user ? $this->careOuvert() : false,
            // Même borne que le serveur : la limite du Master moins la place des repères ajoutés.
            'signalementMax' => $user ? $this->limiteSignalement() : 4800,
        ];
    }

    /** « Signaler à KLASSCI Care » n'est proposé que si le support est ouvert à l'instance. */
    private function careOuvert(): bool
    {
        try {
            return app(\App\Domain\Support\Services\DisponibiliteSupport::class)->signalement();
        } catch (\Throwable $e) {
            Log::warning('assistant.care_indisponible', ['erreur' => $e->getMessage()]);

            return false;
        }
    }

    private function limiteSignalement(): int
    {
        try {
            return app(\App\Services\Care\ClientMasterSupport::class)->limites()['description_max'] - \App\Domain\Assistant\Retours\SignalerReponse::PLACE_DES_REPERES;
        } catch (\Throwable $e) {
            return (int) config('support.limites_par_defaut.description_max') - \App\Domain\Assistant\Retours\SignalerReponse::PLACE_DES_REPERES;
        }
    }

    private function prenom($user): string
    {
        $prefere = ChatbotUserPreference::where('user_id', $user->id)->value('preferred_name');
        $nom = $prefere ?: ($user->first_name ?: strtok((string) $user->name, ' '));

        return trim((string) $nom);
    }

    /**
     * Sélecteur de modèle : seulement pour qui a la permission, et seulement
     * les modèles autorisés par l'école et munis d'une clé.
     */
    private function modeles($user): array
    {
        if (!$user->can('assistant.model.choose')) {
            return [];
        }

        $registre = app(RegistreDesModeles::class);
        $disponibles = array_values(array_map(fn (ModeleIa $m) => $m->versPublic(), $registre->disponibles()));

        // « Automatique » d'abord et par défaut : le routeur choisit le modèle le
        // moins cher qui suffit. Forcer un modèle reste possible, pour tester.
        $auto = ['cle' => 'auto', 'libelle' => 'Automatique', 'fournisseur' => null];

        return count($disponibles) > 1
            ? ['liste' => array_merge([$auto], $disponibles), 'defaut' => 'auto']
            : [];
    }

    /** @return string[] */
    private function suggestions($user): array
    {
        try {
            $outils = app(CatalogueOutils::class)->pour($user);
        } catch (\Throwable $e) {
            // Sans outils, l'accueil garde son message : on le signale plutôt que de le taire.
            Log::warning('Assistant: suggestions indisponibles', ['error' => $e->getMessage()]);
            return [];
        }

        $suggestions = [];
        foreach ($outils as $outil) {
            $suggestion = $outil->suggestion();
            if ($suggestion && !in_array($suggestion, $suggestions, true)) {
                $suggestions[] = $suggestion;
            }
            if (count($suggestions) >= self::MAX_SUGGESTIONS) {
                break;
            }
        }

        return $suggestions;
    }
}
