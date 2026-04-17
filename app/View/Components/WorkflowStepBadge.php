<?php

namespace App\View\Components;

use App\Models\ESBTPInscription;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Badge d'etape de workflow d'inscription — monochrome bleu KLASSCI.
 *
 * Utilise sur la page administration ou l'on a besoin de voir specifiquement
 * a quelle etape de validation se trouve l'inscription (prospect / documents_complets /
 * en_validation / etudiant_cree), pas juste status=active.
 */
class WorkflowStepBadge extends Component
{
    public string $key;

    public string $label;

    public string $icon;

    public function __construct(ESBTPInscription $inscription)
    {
        [$this->key, $this->label, $this->icon] = $this->resolveWorkflow($inscription);
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function resolveWorkflow(ESBTPInscription $inscription): array
    {
        return match ((string) ($inscription->workflow_step ?? '')) {
            'prospect' => ['prospect', 'Prospect', 'fa-user-plus'],
            'documents_complets' => ['documents', 'Documents complets', 'fa-folder-open'],
            'en_validation' => ['validation', 'En validation', 'fa-hourglass-half'],
            'etudiant_cree' => ['validee', 'Validee', 'fa-check-circle'],
            default => ['inconnu', 'Non defini', 'fa-circle'],
        };
    }

    public function render(): View
    {
        return view('components.workflow-step-badge');
    }
}
