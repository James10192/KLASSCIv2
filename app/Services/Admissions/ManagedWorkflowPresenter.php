<?php

namespace App\Services\Admissions;

use App\Models\ESBTPCandidatureWorkflow;

/**
 * Traduit les jalons d'un dossier en étapes lisibles, dans l'ordre choisi par
 * l'établissement. Aucun écran n'affiche un code d'état technique.
 */
final class ManagedWorkflowPresenter
{
    public function __construct(
        private readonly InscriptionWorkflowSettings $settings,
        private readonly AdmissionActivationNotifier $notifier,
    ) {
    }

    /**
     * Le lien d'activation peut-il partir ? Il ne part que par un canal activé
     * et vers un contact prouvé : sinon rien n'est envoyé, et l'écran le dit.
     */
    public function contactJoignable(ESBTPCandidatureWorkflow $w): bool
    {
        return $this->notifier->peutEnvoyer($w);
    }

    /**
     * @return list<array{cle:string, libelle:string, statut:'faite'|'en_cours'|'a_venir'}>
     */
    public function etapes(ESBTPCandidatureWorkflow $w): array
    {
        $caisse = ['cle' => 'caisse', 'libelle' => 'Préinscription payée', 'faite' => $w->paymentRecorded()];
        $pieces = ['cle' => 'pieces', 'libelle' => 'Pièces contrôlées', 'faite' => $w->documentsValidated()];

        $liste = [
            ['cle' => 'demande', 'libelle' => 'Demande acceptée', 'faite' => true],
            ...($this->settings->mode() === InscriptionWorkflowSettings::MODE_PIECES_AVANT_CAISSE ? [$pieces, $caisse] : [$caisse, $pieces]),
            ['cle' => 'activation', 'libelle' => 'Espace étudiant activé', 'faite' => $w->accessActivated()],
            ['cle' => 'profil', 'libelle' => 'Informations complétées', 'faite' => $w->profileCompleted()],
            ['cle' => 'classe', 'libelle' => 'Classe choisie', 'faite' => (bool) $w->selected_class_id],
            ['cle' => 'inscription', 'libelle' => 'Inscription terminée', 'faite' => (bool) $w->final_inscription_id],
        ];

        $courante = false;

        return array_map(function (array $e) use (&$courante) {
            $statut = $e['faite'] ? 'faite' : ($courante ? 'a_venir' : 'en_cours');
            if (! $e['faite']) {
                $courante = true;
            }

            return ['cle' => $e['cle'], 'libelle' => $e['libelle'], 'statut' => $statut];
        }, $liste);
    }

    public static function piecesOuvertes(ESBTPCandidatureWorkflow $w): bool
    {
        return app(InscriptionWorkflowSettings::class)->mode() !== InscriptionWorkflowSettings::MODE_CAISSE_AVANT_PIECES
            || $w->paymentRecorded();
    }

    /** Ce que l'agent doit faire maintenant, en une phrase. */
    public function prochaineEtape(ESBTPCandidatureWorkflow $w): string
    {
        if ($w->final_inscription_id) {
            return 'Inscription terminée.';
        }

        foreach ($this->etapes($w) as $etape) {
            if ($etape['statut'] !== 'en_cours') {
                continue;
            }

            return match ($etape['cle']) {
                'caisse' => 'En attente du paiement de préinscription à la caisse.',
                'pieces' => 'En attente du contrôle physique des pièces au secrétariat.',
                'activation' => $this->contactJoignable($w)
                    ? "Compte étudiant à activer : le lien lui a été envoyé."
                    : "Lien d'activation non envoyé : aucun e-mail ni numéro vérifié. Confirmez le contact avec l'étudiant.",
                'profil' => "L'étudiant doit compléter ses informations dans son espace.",
                'classe' => $this->settings->classChoiceActor() === InscriptionWorkflowSettings::CLASS_ACTOR_STUDENT
                    ? "L'étudiant doit choisir sa classe dans son espace."
                    : "Classe à affecter par l'administration.",
                default => "Inscription prête à être finalisée.",
            };
        }

        return 'Inscription prête à être finalisée.';
    }
}
