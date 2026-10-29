<?php

namespace App\Services;

use App\Domain\Notifications\ChannelDispatch;
use App\Domain\Notifications\EtudiantContact;
use App\Models\ESBTPRelance;
use Illuminate\Support\Facades\Auth;

/**
 * Persiste les actions de relance dans `esbtp_relances`. Distingue 2 états :
 *
 * - **intent** : le comptable a cliqué le bouton (ex WhatsApp deeplink) — on
 *   ne sait pas s'il a vraiment envoyé. Sert d'audit trail.
 * - **envoyee** : le comptable a confirmé l'envoi via le bouton "Marqué relancé".
 *
 * Permet de suivre le funnel intent → envoi confirmé pour analytics futurs.
 */
class RelanceActionLogger
{
    /**
     * Enregistre une intention de relance (clic sur un bouton canal).
     */
    public function logIntent(
        EtudiantContact $contact,
        ?int $inscriptionId,
        ChannelDispatch $dispatch,
        string $message,
    ): ESBTPRelance {
        return ESBTPRelance::create([
            'etudiant_id' => $contact->etudiantId,
            'inscription_id' => $inscriptionId,
            'type' => 'recouvrement',
            'canal' => $dispatch->channel,
            'niveau' => 1,
            'template_utilise' => 'recouvrement_default',
            'contenu_message' => $message,
            'date_envoi' => now(),
            'statut' => $dispatch->success ? ESBTPRelance::STATUT_INTENT : ESBTPRelance::STATUT_ECHEC,
            'declenchee_par' => Auth::id(),
            'response_data' => $dispatch->toArray(),
        ]);
    }

    /**
     * Confirme qu'un envoi (intent ou direct) a effectivement eu lieu.
     */
    public function confirmSent(int $relanceId): ESBTPRelance
    {
        $relance = ESBTPRelance::findOrFail($relanceId);
        $relance->update([
            'statut' => ESBTPRelance::STATUT_ENVOYEE,
            'confirmee_a' => now(),
        ]);
        return $relance;
    }

    /**
     * Crée et confirme une relance en un seul INSERT (canal manuel hors-app :
     * appel direct, en personne, etc.). Évite l'aller-retour intent → confirm.
     */
    public function logSent(
        EtudiantContact $contact,
        ?int $inscriptionId,
        string $note,
    ): ESBTPRelance {
        $now = now();
        return ESBTPRelance::create([
            'etudiant_id' => $contact->etudiantId,
            'inscription_id' => $inscriptionId,
            'type' => 'recouvrement',
            'canal' => 'manuel',
            'niveau' => 1,
            'template_utilise' => 'recouvrement_manuel',
            'contenu_message' => $note,
            'date_envoi' => $now,
            'confirmee_a' => $now,
            'statut' => ESBTPRelance::STATUT_ENVOYEE,
            'declenchee_par' => Auth::id(),
        ]);
    }

    /**
     * Compteur d'intents par étudiant (anti-spam UX : afficher "déjà relancé X fois").
     */
    public function countIntentsToday(int $etudiantId): int
    {
        return ESBTPRelance::where('etudiant_id', $etudiantId)
            ->where('type', 'recouvrement')
            ->whereDate('date_envoi', today())
            ->count();
    }
}
