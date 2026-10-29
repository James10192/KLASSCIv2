<?php

namespace App\Domain\Assistant\Retours;

use App\Domain\Support\Models\SupportOutbox;
use App\Domain\Support\Services\DisponibiliteSupport;
use App\Models\ChatbotMessage;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Met un avis 👍 / 👎 en route vers le Master, par la boite d'envoi KLASSCI Care.
 *
 * Jamais d'appel au Master ici : la personne vient de cliquer, elle n'attend
 * rien. La boite d'envoi (support:vider-boite-envoi, chaque minute) porte
 * l'envoi, la reprise et le recul — les memes que pour un signalement.
 *
 * Changer d'avis :
 *  - tant que la version precedente n'a jamais ete essayee, elle est
 *    simplement remplacee (meme cle) : seul l'etat final part ;
 *  - une fois essayee ou partie, une NOUVELLE version part avec une nouvelle
 *    cle (« uuid:2 », « uuid:3 »…). Une cle d'idempotence est liee a son corps :
 *    la rejouer avec un autre avis serait refuse (idempotency_key_reused).
 *    Le Master rapproche les versions par `message_ref` + `utilisateur.id`.
 *
 * Ne leve jamais : un incident ici se journalise, l'avis reste enregistre
 * localement et la reponse a la personne n'en depend pas.
 */
class TransmettreRetour
{
    public const QUESTION_MAX = 2000;

    public const REPONSE_MAX = 4000;

    public function __construct(private readonly DisponibiliteSupport $disponibilite)
    {
    }

    public function executer(RetourDeReponse $retour, ChatbotMessage $message, User $user): void
    {
        try {
            if (! $this->disponibilite->signalement()) {
                return;
            }

            DB::transaction(fn () => $this->mettreEnRoute($retour, $message, $user));
        } catch (Throwable $e) {
            Log::warning('KLASSCI Care : avis assistant non mis en route', [
                'retour_id' => $retour->id,
                'erreur' => $e->getMessage(),
            ]);
        }
    }

    public static function cle(string $uuid, int $version): string
    {
        return $version <= 1 ? $uuid : $uuid.':'.$version;
    }

    private function mettreEnRoute(RetourDeReponse $retour, ChatbotMessage $message, User $user): void
    {
        $charge = $this->charge($retour, $message, $user);

        if ($retour->care_uuid === null) {
            $retour->forceFill(['care_uuid' => (string) Str::uuid(), 'care_version' => 0]);
        }

        $courante = $retour->care_version > 0
            ? SupportOutbox::where('idempotency_key', self::cle($retour->care_uuid, $retour->care_version))->first()
            : null;

        if ($courante !== null && $this->memeAvis($courante->payload, $charge)) {
            return;
        }

        // Jamais essayee ni reservee par un envoi en cours : on la remplace,
        // la personne n'a fait que se raviser. Mise a jour conditionnelle et
        // atomique : si la boite d'envoi vient de la reserver
        // (ViderBoiteEnvoiSupport pose next_attempt_at avant d'appeler le
        // Master), rien n'est ecrase et une nouvelle version part.
        if ($courante !== null) {
            $remplacee = SupportOutbox::whereKey($courante->getKey())
                ->whereNull('sent_at')->whereNull('abandoned_at')
                ->where('attempts', 0)->whereNull('next_attempt_at')
                ->update(['payload' => json_encode($charge), 'updated_at' => now()]);
            if ($remplacee === 1) {
                return;
            }
        }

        $retour->care_version++;
        $retour->save();

        SupportOutbox::create([
            'kind' => SupportOutbox::AVIS_ASSISTANT,
            'user_id' => $user->getKey(),
            'idempotency_key' => self::cle($retour->care_uuid, $retour->care_version),
            'payload' => $charge,
        ]);
    }

    /** `donne_le` change a chaque enregistrement : il ne decide pas d'un renvoi. */
    private function memeAvis(array $a, array $b): bool
    {
        unset($a['donne_le'], $b['donne_le']);

        return $a == $b;
    }

    /**
     * Le corps attendu par POST /api/v1/support/retours-assistant. Contrat
     * partage avec le Master : ne pas y ajouter de champ sans lui.
     */
    private function charge(RetourDeReponse $retour, ChatbotMessage $message, User $user): array
    {
        $question = ChatbotMessage::where('conversation_id', $message->conversation_id)
            ->where('role', 'user')
            ->where('id', '<', $message->id)
            ->latest('id')
            ->value('content');

        $conversation = $message->conversation;

        return [
            'avis' => $retour->avis,
            'raison' => $retour->raison,
            'commentaire' => $retour->commentaire,
            // Le Master exige les deux : une reponse reduite a une carte de
            // proposition, ou sans question avant, partirait vide et serait
            // refusee (422), donc l'avis abandonne.
            'question' => mb_substr(self::ouRepli($question, '[sans question]'), 0, self::QUESTION_MAX),
            'reponse' => mb_substr(self::ouRepli($message->content, self::repliReponse($message)), 0, self::REPONSE_MAX),
            'modele' => $retour->modele,
            // Le chemin seul, sans requete : jamais d'identifiant ni de recherche saisie.
            'page' => $this->page($conversation?->context['last_page_path'] ?? null),
            'utilisateur' => [
                'id' => (int) $user->getKey(),
                'nom' => (string) $user->name,
                'role' => $user->getRoleNames()->first(),
            ],
            'conversation_ref' => $conversation?->session_id,
            'message_ref' => (string) $message->id,
            'donne_le' => ($retour->updated_at ?? now())->toIso8601String(),
        ];
    }

    private static function ouRepli(mixed $texte, string $repli): string
    {
        $texte = trim((string) $texte);

        return $texte !== '' ? $texte : $repli;
    }

    /** « [proposition : <titre>] » quand la reponse n'est qu'une carte. */
    private static function repliReponse(ChatbotMessage $message): string
    {
        $titre = self::titreDeCarte($message->display_data);

        return $titre !== null ? '[proposition : '.$titre.']' : '[proposition]';
    }

    private static function titreDeCarte(mixed $donnees, int $profondeur = 0): ?string
    {
        if (! is_array($donnees) || $profondeur > 4) {
            return null;
        }
        if (isset($donnees['titre']) && is_string($donnees['titre']) && trim($donnees['titre']) !== '') {
            return mb_substr(trim($donnees['titre']), 0, 200);
        }
        foreach ($donnees as $valeur) {
            $titre = self::titreDeCarte($valeur, $profondeur + 1);
            if ($titre !== null) {
                return $titre;
            }
        }

        return null;
    }

    private function page(?string $chemin): ?string
    {
        if (! is_string($chemin) || $chemin === '') {
            return null;
        }

        return mb_substr((string) strtok($chemin, '?#'), 0, 255) ?: null;
    }
}
