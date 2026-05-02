<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\User;

/**
 * Résout l'état actuel + le CTA d'une action_card dans le chat selon le viewer.
 *
 * Le snapshot dans payload est figé au moment du share. Mais le CTA est calculé
 * en temps réel via lookup de la ressource (inscription/paiement) pour que le
 * destinataire voie l'action correcte selon l'état courant + ses permissions.
 */
class ChatActionResolver
{
    /**
     * Construit le snapshot d'une inscription pour stockage dans payload.
     */
    public function snapshotInscription(ESBTPInscription $inscription): array
    {
        $inscription->loadMissing([
            'etudiant:id,nom,prenoms,matricule,photo',
            'classe:id,name',
            'anneeUniversitaire:id,name,libelle',
        ]);

        $etudiantName = trim(($inscription->etudiant?->nom ?? '') . ' ' . ($inscription->etudiant?->prenoms ?? ''));
        $totals = $this->totalsForInscription($inscription);

        return [
            'kind' => 'inscription',
            'id' => $inscription->id,
            'snapshot' => [
                'etudiant' => [
                    'id' => $inscription->etudiant_id,
                    'name' => $etudiantName ?: '—',
                    'matricule' => $inscription->etudiant?->matricule,
                    'photo_url' => $inscription->etudiant?->photo
                        ? asset('storage/' . $inscription->etudiant->photo)
                        : null,
                ],
                'classe' => $inscription->classe?->name ?? '—',
                'annee' => $inscription->anneeUniversitaire?->libelle
                    ?? $inscription->anneeUniversitaire?->name
                    ?? '—',
                'status' => $inscription->status,
                'workflow_step' => $inscription->workflow_step,
                'is_sous_reserve' => (bool) $inscription->is_sous_reserve,
                'montant_total' => $totals['attendu'],
                'montant_paye' => $totals['paye'],
                'solde_restant' => $totals['solde'],
            ],
        ];
    }

    /**
     * Construit le snapshot d'un paiement pour stockage dans payload.
     */
    public function snapshotPaiement(ESBTPPaiement $paiement): array
    {
        $paiement->loadMissing([
            'etudiant:id,nom,prenoms,matricule,photo',
            'inscription:id,etudiant_id,classe_id,workflow_step,status',
            'inscription.classe:id,name',
        ]);

        $etudiantName = trim(($paiement->etudiant?->nom ?? '') . ' ' . ($paiement->etudiant?->prenoms ?? ''));

        return [
            'kind' => 'paiement',
            'id' => $paiement->id,
            'inscription_id' => $paiement->inscription_id,
            'snapshot' => [
                'etudiant' => [
                    'id' => $paiement->etudiant_id,
                    'name' => $etudiantName ?: '—',
                    'matricule' => $paiement->etudiant?->matricule,
                    'photo_url' => $paiement->etudiant?->photo
                        ? asset('storage/' . $paiement->etudiant->photo)
                        : null,
                ],
                'classe' => $paiement->inscription?->classe?->name ?? '—',
                'montant' => (float) $paiement->montant,
                'mode_paiement' => $paiement->mode_paiement,
                'reference' => $paiement->reference_paiement ?? $paiement->numero_recu,
                'date_paiement' => optional($paiement->date_paiement)->toDateString(),
                'statut' => $paiement->statut,
                'is_validated' => $paiement->validateur_id !== null,
                'motif' => $paiement->motif,
                'inscription_workflow_step' => $paiement->inscription?->workflow_step,
            ],
        ];
    }

    /**
     * Calcule le CTA contextuel selon l'état courant de la ressource + perms du viewer.
     *
     * @return array{label: string, url: string, variant: 'primary'|'ghost', icon: string}|null
     */
    public function resolveCta(ChatMessage $message, User $viewer): ?array
    {
        if ($message->type !== 'action_card' || !is_array($message->payload)) {
            return null;
        }

        $kind = $message->payload['kind'] ?? null;
        $id = $message->payload['id'] ?? null;
        if (!$kind || !$id) {
            return null;
        }

        return match ($kind) {
            'inscription' => $this->ctaForInscription((int) $id, $viewer),
            'paiement' => $this->ctaForPaiement((int) $id, $viewer),
            default => null,
        };
    }

    private function ctaForInscription(int $inscriptionId, User $viewer): ?array
    {
        $inscription = ESBTPInscription::query()
            ->select(['id', 'workflow_step', 'status', 'paiement_validation_id'])
            ->find($inscriptionId);

        if (!$inscription) {
            return $this->ctaSoftDeleted();
        }

        $step = $inscription->workflow_step;

        // Inscription validée → fin du chain
        if ($step === 'inscription_validee' || $step === 'validee') {
            return $this->ctaView(
                'Voir l\'inscription',
                route('esbtp.inscriptions.show', $inscription->id),
                'fa-eye',
                $viewer->can('inscriptions.view'),
            );
        }

        // Paiement validé mais inscription pas encore validée → valider inscription
        if ($step === 'paiement_valide' && $viewer->can('inscriptions.validate')) {
            return [
                'label' => 'Valider l\'inscription',
                'url' => route('esbtp.inscriptions.show', $inscription->id) . '#valider',
                'variant' => 'primary',
                'icon' => 'fa-check-double',
            ];
        }

        // Paiement créé mais pas validé → valider le paiement
        if ($step === 'paiement_cree' && $inscription->paiement_validation_id && $viewer->can('paiements.validate')) {
            return [
                'label' => 'Valider le paiement',
                'url' => route('esbtp.paiements.show', $inscription->paiement_validation_id),
                'variant' => 'primary',
                'icon' => 'fa-shield-check',
            ];
        }

        // Pas de paiement encore → encaisser
        if (in_array($step, ['etudiant_cree', null], true) && $viewer->can('paiements.create')) {
            return [
                'label' => 'Encaisser un paiement',
                'url' => route('esbtp.paiements.create', ['inscription_id' => $inscription->id]),
                'variant' => 'primary',
                'icon' => 'fa-cash-register',
            ];
        }

        // Fallback : juste voir
        return $this->ctaView(
            'Voir l\'inscription',
            route('esbtp.inscriptions.show', $inscription->id),
            'fa-eye',
            $viewer->can('inscriptions.view'),
        );
    }

    private function ctaForPaiement(int $paiementId, User $viewer): ?array
    {
        $paiement = ESBTPPaiement::query()
            ->select(['id', 'inscription_id', 'statut', 'validateur_id'])
            ->find($paiementId);

        if (!$paiement) {
            return $this->ctaSoftDeleted();
        }

        $isValidated = $paiement->validateur_id !== null
            || in_array($paiement->statut, ['valide', 'validé', 'completed'], true);

        // Paiement validé → focus sur l'inscription liée
        if ($isValidated && $paiement->inscription_id) {
            $inscription = ESBTPInscription::query()
                ->select(['id', 'workflow_step'])
                ->find($paiement->inscription_id);

            if ($inscription
                && $inscription->workflow_step !== 'inscription_validee'
                && $inscription->workflow_step !== 'validee'
                && $viewer->can('inscriptions.validate')
            ) {
                return [
                    'label' => 'Valider l\'inscription',
                    'url' => route('esbtp.inscriptions.show', $inscription->id) . '#valider',
                    'variant' => 'primary',
                    'icon' => 'fa-check-double',
                ];
            }
        }

        // Paiement en attente → valider
        if (!$isValidated && $viewer->can('paiements.validate')) {
            return [
                'label' => 'Valider le paiement',
                'url' => route('esbtp.paiements.show', $paiement->id),
                'variant' => 'primary',
                'icon' => 'fa-shield-check',
            ];
        }

        // Fallback : voir
        return $this->ctaView(
            'Voir le paiement',
            route('esbtp.paiements.show', $paiement->id),
            'fa-eye',
            $viewer->can('paiements.view'),
        );
    }

    private function ctaView(string $label, string $url, string $icon, bool $allowed): ?array
    {
        if (!$allowed) {
            return null;
        }
        return ['label' => $label, 'url' => $url, 'variant' => 'ghost', 'icon' => $icon];
    }

    private function ctaSoftDeleted(): array
    {
        return [
            'label' => 'Ressource supprimée',
            'url' => '#',
            'variant' => 'disabled',
            'icon' => 'fa-ban',
        ];
    }

    /**
     * Totaux frais pour une inscription : attendu / payé / solde.
     *
     * @return array{attendu: float, paye: float, solde: float}
     */
    private function totalsForInscription(ESBTPInscription $inscription): array
    {
        $attendu = (float) ESBTPFraisSubscription::query()
            ->where('inscription_id', $inscription->id)
            ->where('is_active', true)
            ->sum('amount');

        $paye = (float) ESBTPPaiement::query()
            ->where('inscription_id', $inscription->id)
            ->whereNotNull('validateur_id')
            ->sum('montant');

        return [
            'attendu' => $attendu,
            'paye' => $paye,
            'solde' => max(0, $attendu - $paye),
        ];
    }
}
