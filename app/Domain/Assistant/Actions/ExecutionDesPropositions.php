<?php

namespace App\Domain\Assistant\Actions;

use App\Models\ChatbotActionLog;
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
 * L'issue (validée, refusée, expirée, données changées, échec) n'est écrite
 * qu'ici, dans le journal. La carte la montre sur place, et la conversation
 * la rend au modèle à la place du résultat d'origine de l'outil
 * (issuePourModele) : au tour suivant, Nanan sait ce qui est arrivé, sans
 * qu'un message séparé s'intercale dans le fil.
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

        if ($proposition->sansObjet !== null && $proposition->manques === []) {
            // Pas une question : la demande est déjà satisfaite.
            return [
                'sans_objet' => true,
                'message' => 'Rien à enregistrer : ' . $proposition->sansObjet . ' Dis-le en une phrase ; ne propose rien et ne pose pas de question.',
            ];
        }

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
            'message' => 'Proposition présentée, en attente. RIEN N\'EST ENREGISTRÉ tant que la personne ne l\'a pas validée. '
                . 'La carte s\'affiche sous ta réponse : elle montre le détail, porte les boutons et affichera l\'issue. '
                . 'Réponds en une ou deux phrases qui commencent par « Je propose » (jamais « a été saisie », « ajoutée » ou « enregistrée ») '
                . 'et reprends l\'avertissement principal s\'il y en a un. Ne recopie pas la carte et ne dis pas sur quoi cliquer. '
                . 'Au tour suivant, ce résultat sera remplacé par l\'issue réelle : fie-toi à elle.',
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
        } catch (PropositionPerimee $e) {
            $journal->update(['status' => 'expired', 'error_message' => mb_substr($e->getMessage(), 0, 1000)]);

            return $this->refus($e->getMessage() . ' Rien n\'a été enregistré : demandez à Nanan de refaire la proposition.', 'perimee');
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
                'resultat' => ['message' => $resultat['message'], 'details' => $resultat['details'] ?? null, 'lien' => $resultat['lien'] ?? null],
                'ip' => request()->ip(),
                'user_agent' => mb_substr((string) request()->userAgent(), 0, 255),
            ]),
        ]);

        return ['statut' => 'executee', 'message' => $resultat['message'], 'lien' => $resultat['lien'] ?? null];
    }

    public function refuser(ChatbotActionLog $journal, $user): array
    {
        if ((int) $journal->user_id !== (int) $user->id) {
            return $this->refus('Cette proposition ne vous est pas destinée.');
        }
        // Atomique, comme la validation : un « Refuser » qui croise un « Valider »
        // dans un autre onglet ne peut pas annoncer un refus après l'écriture.
        $refusee = ChatbotActionLog::whereKey($journal->id)->where('status', 'proposed')
            ->update(['status' => 'rejected', 'rejected_at' => now()]);
        if ($refusee !== 1) {
            return $this->refus('Cette proposition a déjà été traitée.', 'traitee');
        }
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
            // Réservée mais jamais close (arrêt brutal pendant l'écriture) : on ne sait
            // pas si c'est écrit. Ni « expirée » ni « refaire » : vérifier d'abord.
            'approved' => 'a_verifier',
            'executed' => 'executee',
            'rejected' => 'refusee',
            'failed' => 'echec',
            // Annulée à la validation parce que les données avaient changé.
            'expired' => $journal->error_message ? 'perimee' : 'expiree',
            default => 'expiree',
        };
    }

    /**
     * Ce que la carte rouverte affiche sous l'état : le message de l'écriture et
     * son lien, comme juste après le clic.
     *
     * @return array{message: ?string, lien: ?string}
     */
    public static function issueAffichee(ChatbotActionLog $journal): array
    {
        $resultat = (array) ($journal->action_data['resultat'] ?? []);

        return ['message' => $resultat['message'] ?? null, 'lien' => $resultat['lien'] ?? null];
    }

    /**
     * Le résultat de l'outil tel que le modèle le relit aux tours suivants : son
     * issue RÉELLE, et non « en attente de validation » figé au moment où il l'a
     * proposée. Sans cela, il ignorait qu'une proposition avait été validée,
     * refusée ou avait expiré, et la reproposait ou l'annonçait encore à faire.
     */
    public static function issuePourModele(ChatbotActionLog $journal): array
    {
        $etat = self::etat($journal);
        $ecrit = self::issueAffichee($journal)['message'];

        return [
            'proposition' => $journal->id,
            'statut' => $etat,
            'issue' => match ($etat) {
                'en_attente' => 'Toujours en attente : la personne n\'a ni validé ni refusé. Rien n\'est enregistré.',
                'executee' => 'Validée par la personne et enregistrée' . ($ecrit ? ' : ' . $ecrit : '.'),
                'refusee' => 'Refusée par la personne : rien n\'a été enregistré.',
                'perimee' => 'Validation refusée : les données avaient changé depuis la proposition. Rien n\'a été enregistré ; il faut la refaire.',
                'echec' => 'La validation a échoué : rien n\'a été modifié.',
                'a_verifier' => 'Validation interrompue pendant l\'écriture : vérifier l\'état réel avant de la refaire.',
                default => 'Expirée sans validation : rien n\'a été enregistré ; il faut la refaire si elle est toujours voulue.',
            },
        ];
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
