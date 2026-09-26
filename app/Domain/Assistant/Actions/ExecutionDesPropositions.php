<?php

namespace App\Domain\Assistant\Actions;

use App\Models\ChatbotActionLog;
use App\Models\ChatbotMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Cycle de vie d'une proposition de l'assistant :
 *
 *   proposer()  outil appelé par le modèle → proposition enregistrée (proposed),
 *               widget « approbation » montré à l'utilisateur ;
 *   valider()   clic « Valider » → contrôles, nouvelle préparation, comparaison
 *               d'empreinte, exécution, trace (executed / failed) ;
 *   refuser()   clic « Refuser » → rejected.
 *
 * Contrôles à la validation, tous avant toute écriture : la personne est celle
 * qui a reçu la proposition, le jeton signé correspond, la proposition n'est ni
 * traitée ni expirée, l'outil est toujours permis, la préparation refaite est
 * complète et identique (sinon les données ont changé : on repropose).
 */
class ExecutionDesPropositions
{
    private const DUREE_MINUTES = 30;

    public function __construct(private ContexteDEchange $contexte)
    {
    }

    public function proposer(ActionAgent $action, array $args, $user): array
    {
        $proposition = $action->preparer($args, $user);

        if (! $proposition->estComplete()) {
            // Rien n'est enregistré : l'assistant doit demander ce qui manque.
            return [
                'error' => 'Proposition incomplète. Demande à l\'utilisateur, sans deviner : ' . implode(' ; ', $proposition->manques),
                'manques' => $proposition->manques,
            ];
        }

        $conversation = $this->contexte->conversation;
        if (! $conversation) {
            return ['error' => 'Aucune conversation : la proposition ne peut pas être présentée.'];
        }

        $journal = ChatbotActionLog::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'action_type' => $action->cle(),
            'status' => 'proposed',
            'idempotency_key' => (string) Str::uuid(),
            'expires_at' => now()->addMinutes(self::DUREE_MINUTES),
            'action_data' => [
                'arguments' => $args,
                'empreinte' => $proposition->empreinte(),
                'titre' => $proposition->titre,
                'resume' => $proposition->resume,
            ],
        ]);

        return [
            'affiche' => true,
            'count' => 1,
            'proposition' => $journal->id,
            'statut' => 'en_attente_de_validation',
            'resume' => $proposition->resume,
            'message' => 'Proposition présentée. RIEN N\'EST ENREGISTRÉ tant que l\'utilisateur n\'a pas cliqué « Valider ». '
                . 'Ne dis pas que c\'est fait ; résume ce qui sera écrit et les avertissements.',
            'avertissements' => $proposition->avertissements,
            'widget' => $this->widget($journal, $proposition, $user),
        ];
    }

    /** @return array{statut: string, message: string, lien?: ?string} */
    public function valider(ChatbotActionLog $journal, $user, string $jeton): array
    {
        $refus = $this->refusAvantExecution($journal, $user, $jeton);
        if ($refus) {
            return $refus;
        }

        $action = app(RegistreDesActions::class)->action($journal->action_type);
        if (! $action || ! $action->isAvailableFor($user)) {
            return $this->refus('Vous n\'avez plus accès à cette action.');
        }

        // Réservation atomique : deux clics (ou deux onglets) ne peuvent pas
        // exécuter la même proposition deux fois.
        $reservee = ChatbotActionLog::whereKey($journal->id)->where('status', 'proposed')
            ->update(['status' => 'approved', 'approved_by' => $user->id, 'approved_at' => now()]);
        if ($reservee !== 1) {
            return $this->refus('Cette proposition a déjà été traitée.', 'traitee');
        }
        $journal->refresh();

        $proposition = $action->preparer((array) ($journal->action_data['arguments'] ?? []), $user);
        if (! $proposition->estComplete() || ! hash_equals((string) ($journal->action_data['empreinte'] ?? ''), $proposition->empreinte())) {
            $journal->update(['status' => 'expired', 'error_message' => 'Données modifiées depuis la proposition.']);

            return $this->refus('Les données ont changé depuis cette proposition. Demandez à Nanan de la refaire : rien n\'a été enregistré.', 'perimee');
        }

        try {
            $resultat = $action->executer($proposition, $user);
        } catch (\Throwable $e) {
            Log::error('assistant.action_en_echec', ['action' => $journal->action_type, 'journal' => $journal->id, 'erreur' => $e->getMessage()]);
            $journal->update(['status' => 'failed', 'error_message' => mb_substr($e->getMessage(), 0, 1000)]);

            return $this->refus('L\'enregistrement a échoué : rien n\'a été modifié. L\'erreur est journalisée.', 'echec');
        }

        $journal->update([
            'status' => 'executed',
            'model_type' => $resultat['model_type'] ?? null,
            'model_id' => $resultat['model_id'] ?? null,
            'action_data' => array_merge((array) $journal->action_data, [
                'resultat' => ['message' => $resultat['message'], 'details' => $resultat['details'] ?? null],
                'ip' => request()->ip(),
                'user_agent' => mb_substr((string) request()->userAgent(), 0, 255),
            ]),
        ]);

        // L'historique de la conversation garde la trace : le modèle saura, au
        // message suivant, que c'est fait (et ce qui a été fait).
        ChatbotMessage::create([
            'conversation_id' => $journal->conversation_id,
            'role' => 'assistant',
            'content' => '✓ ' . $resultat['message'],
            'display_type' => 'text',
            'metadata' => ['action_executee' => $journal->id],
        ]);

        return ['statut' => 'executee', 'message' => $resultat['message'], 'lien' => $resultat['lien'] ?? null];
    }

    public function refuser(ChatbotActionLog $journal, $user): array
    {
        if ((int) $journal->user_id !== (int) $user->id) {
            return $this->refus('Cette proposition ne vous est pas destinée.');
        }
        if ($journal->status !== 'proposed') {
            return $this->refus('Cette proposition a déjà été traitée.', 'traitee');
        }

        $journal->update(['status' => 'rejected', 'rejected_at' => now()]);
        ChatbotMessage::create([
            'conversation_id' => $journal->conversation_id,
            'role' => 'assistant',
            'content' => 'Proposition refusée : rien n\'a été enregistré.',
            'display_type' => 'text',
            'metadata' => ['action_refusee' => $journal->id],
        ]);

        return ['statut' => 'refusee', 'message' => 'Proposition refusée : rien n\'a été enregistré.'];
    }

    public static function jeton(ChatbotActionLog $journal, int $userId): string
    {
        return hash_hmac('sha256', $journal->id . '|' . $userId . '|' . ($journal->action_data['empreinte'] ?? ''), (string) config('app.key'));
    }

    /** État courant d'une proposition, pour l'historique rouvert. */
    public static function etat(ChatbotActionLog $journal): string
    {
        if ($journal->status === 'proposed' && $journal->expires_at && $journal->expires_at->isPast()) {
            return 'expiree';
        }

        return match ($journal->status) {
            'proposed' => 'en_attente',
            'executed' => 'executee',
            'rejected' => 'refusee',
            'failed' => 'echec',
            default => 'expiree',
        };
    }

    private function refusAvantExecution(ChatbotActionLog $journal, $user, string $jeton): ?array
    {
        if ((int) $journal->user_id !== (int) $user->id) {
            return $this->refus('Cette proposition ne vous est pas destinée.');
        }
        if (! hash_equals(self::jeton($journal, (int) $user->id), $jeton)) {
            return $this->refus('Proposition non reconnue.');
        }
        if ($journal->status !== 'proposed') {
            return $this->refus('Cette proposition a déjà été traitée.', 'traitee');
        }
        if ($journal->expires_at && $journal->expires_at->isPast()) {
            $journal->update(['status' => 'expired']);

            return $this->refus('Cette proposition a expiré. Demandez à Nanan de la refaire.', 'expiree');
        }

        return null;
    }

    private function refus(string $message, string $statut = 'refus'): array
    {
        return ['statut' => $statut, 'message' => $message];
    }

    private function widget(ChatbotActionLog $journal, Proposition $proposition, $user): array
    {
        return [
            'kind' => 'approbation',
            'id' => $journal->id,
            'jeton' => self::jeton($journal, (int) $user->id),
            'titre' => $proposition->titre,
            'resume' => $proposition->resume,
            'colonnes' => $proposition->tableau['colonnes'] ?? [],
            'lignes' => $proposition->tableau['lignes'] ?? [],
            'avertissements' => $proposition->avertissements,
            'risque' => $proposition->risque,
            'expire_a' => $journal->expires_at?->toIso8601String(),
            'etat' => 'en_attente',
            'valider_url' => route('chatbot.propositions.valider', $journal->id, false),
            'refuser_url' => route('chatbot.propositions.refuser', $journal->id, false),
        ];
    }
}
