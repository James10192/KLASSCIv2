<?php

namespace App\View\Components;

use App\Models\ESBTPInscription;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Badge de statut d'inscription — 5 états monochrome bleu KLASSCI.
 *
 * Centralise la logique dupliquée 5 fois dans le projet (controller, partials,
 * JS) pour résoudre le statut affichable depuis `status` + `workflow_step`.
 *
 * États retournés :
 *  - validee    : active + workflow_step=etudiant_cree
 *  - non_validee: active + workflow_step != etudiant_cree
 *  - en_attente : status=en_attente OR pending
 *  - annulee    : status=annulée OR cancelled
 *  - terminee   : status=terminée
 *  - inconnu    : fallback
 *
 * Usage :
 *   <x-inscription-status-badge :inscription="$inscription" />
 */
class InscriptionStatusBadge extends Component
{
    public string $key;

    public string $label;

    public string $icon;

    public string $title;

    public function __construct(ESBTPInscription $inscription)
    {
        [$this->key, $this->label, $this->icon, $this->title] = $this->resolveStatus($inscription);
    }

    /**
     * @return array{0: string, 1: string, 2: string, 3: string}
     */
    private function resolveStatus(ESBTPInscription $inscription): array
    {
        $status = (string) ($inscription->status ?? '');
        $workflow = (string) ($inscription->workflow_step ?? '');

        if (in_array($status, ['cancelled', 'annulée', 'annulee'], true)) {
            return ['annulee', 'Annulée', 'fa-times-circle', 'Inscription annulée'];
        }

        if (in_array($status, ['terminée', 'terminee'], true)) {
            return ['terminee', 'Terminée', 'fa-flag-checkered', 'Inscription terminée'];
        }

        if (in_array($status, ['pending', 'en_attente'], true)) {
            return ['en_attente', 'En attente', 'fa-clock', 'En attente de validation'];
        }

        if ($status === 'active' && $workflow !== 'etudiant_cree') {
            $workflowLabel = match ($workflow) {
                'prospect' => 'prospect',
                'documents_complets' => 'documents collectés',
                'en_validation' => 'validation en cours',
                'valide' => 'validé, étudiant non créé',
                default => $workflow !== '' ? $workflow : 'non défini',
            };

            return [
                'non_validee',
                'Non validée',
                'fa-exclamation-triangle',
                "Étape workflow : {$workflowLabel}",
            ];
        }

        if ($status === 'active' && $workflow === 'etudiant_cree') {
            return ['validee', 'Validée', 'fa-check-circle', 'Inscription validée · étudiant créé'];
        }

        return ['inconnu', ucfirst($status ?: 'Inconnu'), 'fa-circle', ''];
    }

    public function render(): View
    {
        return view('components.inscription-status-badge');
    }
}
