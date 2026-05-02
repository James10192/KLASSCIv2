<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Snapshot + résolution CTA des action_card du chat.
 *
 * Le snapshot dans payload est figé à l'envoi. Le CTA est calculé en temps réel
 * depuis l'état courant de la ressource + les permissions du viewer.
 *
 * Pour les hot paths (ChatController::show()), utiliser preload() + resolveCta()
 * avec les maps pré-chargées pour éviter le N+1.
 */
class ChatActionResolver
{
    public function snapshotInscription(ESBTPInscription $inscription): array
    {
        $inscription->loadMissing([
            'etudiant:id,nom,prenoms,matricule,photo',
            'classe:id,name',
            'anneeUniversitaire:id,name,libelle',
        ]);

        $relance = app(RelanceCalculationService::class)->preloadForSingle($inscription);
        $attendu = (float) $relance->calculerTotalDu($inscription);
        $paye = (float) $inscription->paiements()->valides()->sum('montant');

        $etudiantName = trim(($inscription->etudiant?->nom ?? '') . ' ' . ($inscription->etudiant?->prenoms ?? ''));

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
                'workflow_label' => self::workflowLabel($inscription->workflow_step),
                'workflow_chip_class' => self::workflowChipClass($inscription->workflow_step),
                'is_sous_reserve' => (bool) $inscription->is_sous_reserve,
                'montant_total' => $attendu,
                'montant_paye' => $paye,
                'solde_restant' => max(0, $attendu - $paye),
            ],
        ];
    }

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
                'statut' => $paiement->status,
                'is_validated' => $paiement->status === 'validé',
                'motif' => $paiement->motif,
            ],
        ];
    }

    /**
     * Pré-charge les ressources référencées par toutes les action_card de la collection,
     * pour éviter le N+1 dans show().
     *
     * @param  iterable<ChatMessage>  $messages
     * @return array{inscriptions: Collection, paiements: Collection}
     */
    public function preload(iterable $messages): array
    {
        $inscriptionIds = [];
        $paiementIds = [];

        foreach ($messages as $m) {
            if ($m->type !== 'action_card' || !is_array($m->payload)) {
                continue;
            }
            $kind = $m->payload['kind'] ?? null;
            $id = $m->payload['id'] ?? null;
            if (!$id) {
                continue;
            }
            if ($kind === 'inscription') {
                $inscriptionIds[] = (int) $id;
            } elseif ($kind === 'paiement') {
                $paiementIds[] = (int) $id;
                if (!empty($m->payload['inscription_id'])) {
                    $inscriptionIds[] = (int) $m->payload['inscription_id'];
                }
            }
        }

        $inscriptions = empty($inscriptionIds) ? collect() : ESBTPInscription::query()
            ->select(['id', 'workflow_step', 'status', 'paiement_validation_id'])
            ->whereIn('id', array_unique($inscriptionIds))
            ->get()
            ->keyBy('id');

        $paiements = empty($paiementIds) ? collect() : ESBTPPaiement::query()
            ->select(['id', 'inscription_id', 'status', 'validateur_id'])
            ->whereIn('id', array_unique($paiementIds))
            ->get()
            ->keyBy('id');

        return ['inscriptions' => $inscriptions, 'paiements' => $paiements];
    }

    /**
     * Résout le CTA d'une action_card pour un viewer.
     *
     * @param  array{inscriptions: Collection, paiements: Collection}|null  $maps  Pré-chargé via preload() pour batch
     * @return array{label: string, url: string, variant: string, icon: string}|null
     */
    public function resolveCta(ChatMessage $message, User $viewer, ?array $maps = null): ?array
    {
        if ($message->type !== 'action_card' || !is_array($message->payload)) {
            return null;
        }

        $kind = $message->payload['kind'] ?? null;
        $id = (int) ($message->payload['id'] ?? 0);
        if (!$kind || !$id) {
            return null;
        }

        return match ($kind) {
            'inscription' => $this->ctaForInscription($id, $viewer, $maps),
            'paiement' => $this->ctaForPaiement($id, $viewer, $maps),
            default => null,
        };
    }

    private function ctaForInscription(int $inscriptionId, User $viewer, ?array $maps): ?array
    {
        $inscription = $maps['inscriptions']->get($inscriptionId)
            ?? ESBTPInscription::query()
                ->select(['id', 'workflow_step', 'status', 'paiement_validation_id'])
                ->find($inscriptionId);

        if (!$inscription) {
            return $this->ctaSoftDeleted();
        }

        // Branches ordrées du plus avancé au moins avancé.
        return match ((string) $inscription->workflow_step) {
            'etudiant_cree' => $this->ctaView(
                'Voir l\'inscription',
                route('esbtp.inscriptions.show', $inscription->id),
                'fa-eye',
                $viewer->can('inscriptions.view'),
            ),
            'en_validation' => $this->ctaValidatePaiement($inscription, $viewer)
                ?? $this->ctaFallbackInscription($inscription, $viewer),
            'prospect', 'documents_complets' => $this->ctaCreatePaiement($inscription, $viewer)
                ?? $this->ctaFallbackInscription($inscription, $viewer),
            default => $this->ctaFallbackInscription($inscription, $viewer),
        };
    }

    private function ctaForPaiement(int $paiementId, User $viewer, ?array $maps): ?array
    {
        $paiement = $maps['paiements']->get($paiementId)
            ?? ESBTPPaiement::query()
                ->select(['id', 'inscription_id', 'status', 'validateur_id'])
                ->find($paiementId);

        if (!$paiement) {
            return $this->ctaSoftDeleted();
        }

        $isValidated = $paiement->status === 'validé';

        if ($isValidated && $paiement->inscription_id) {
            $inscription = $maps['inscriptions']->get($paiement->inscription_id)
                ?? ESBTPInscription::query()
                    ->select(['id', 'workflow_step'])
                    ->find($paiement->inscription_id);

            if ($inscription
                && $inscription->workflow_step !== 'etudiant_cree'
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

        if (!$isValidated && $viewer->can('paiements.validate')) {
            return [
                'label' => 'Valider le paiement',
                'url' => route('esbtp.paiements.show', $paiement->id),
                'variant' => 'primary',
                'icon' => 'fa-shield-check',
            ];
        }

        return $this->ctaView(
            'Voir le paiement',
            route('esbtp.paiements.show', $paiement->id),
            'fa-eye',
            $viewer->can('paiements.view'),
        );
    }

    private function ctaCreatePaiement(ESBTPInscription $inscription, User $viewer): ?array
    {
        if (!$viewer->can('paiements.create')) {
            return null;
        }
        return [
            'label' => 'Encaisser un paiement',
            'url' => route('esbtp.paiements.create', ['inscription_id' => $inscription->id]),
            'variant' => 'primary',
            'icon' => 'fa-cash-register',
        ];
    }

    private function ctaValidatePaiement(ESBTPInscription $inscription, User $viewer): ?array
    {
        if (!$inscription->paiement_validation_id || !$viewer->can('paiements.validate')) {
            return null;
        }
        return [
            'label' => 'Valider le paiement',
            'url' => route('esbtp.paiements.show', $inscription->paiement_validation_id),
            'variant' => 'primary',
            'icon' => 'fa-shield-check',
        ];
    }

    private function ctaFallbackInscription(ESBTPInscription $inscription, User $viewer): ?array
    {
        return $this->ctaView(
            'Voir l\'inscription',
            route('esbtp.inscriptions.show', $inscription->id),
            'fa-eye',
            $viewer->can('inscriptions.view'),
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
     * Source unique des labels workflow_step (cohérence avec WorkflowStepBadge).
     */
    public static function workflowLabel(?string $step): string
    {
        return match ((string) $step) {
            'prospect' => 'Prospect',
            'documents_complets' => 'Documents complets',
            'en_validation' => 'En validation',
            'etudiant_cree' => 'Validée',
            default => 'Non défini',
        };
    }

    public static function workflowChipClass(?string $step): string
    {
        return match ((string) $step) {
            'etudiant_cree' => 'acard-chip--success',
            'en_validation', 'documents_complets' => 'acard-chip--warning',
            'prospect' => 'acard-chip',
            default => 'acard-chip',
        };
    }
}
