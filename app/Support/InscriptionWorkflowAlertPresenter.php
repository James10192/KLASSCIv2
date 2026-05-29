<?php

namespace App\Support;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPInscription;
use Illuminate\Support\Facades\Auth;

class InscriptionWorkflowAlertPresenter
{
    private const PENDING_WORKFLOW_STEPS = ['prospect', 'documents_complets', 'en_validation'];

    public static function fromInscription(?ESBTPInscription $inscription, ?ESBTPAnneeUniversitaire $anneeUniversitaire): array
    {
        $showBanner = self::shouldShowBanner($inscription);

        return [
            'show_banner' => $showBanner,
            'year_label' => self::yearLabel($anneeUniversitaire),
            'status_label' => self::statusLabel($inscription?->status),
            'workflow_step_label' => self::workflowStepLabel($inscription?->workflow_step),
            'can_validate' => $showBanner && Auth::user()?->can('inscriptions.validate'),
            'validation_url' => $showBanner && $inscription ? route('esbtp.inscriptions.valider', $inscription->id) : null,
            'inscription_id' => $inscription?->id,
        ];
    }

    public static function shouldShowBanner(?ESBTPInscription $inscription): bool
    {
        if (! $inscription) {
            return false;
        }

        if (in_array($inscription->status, ['en_attente', 'pending'], true)) {
            return true;
        }

        return $inscription->status === 'active'
            && (
                $inscription->workflow_step === null
                || in_array($inscription->workflow_step, self::PENDING_WORKFLOW_STEPS, true)
            );
    }

    private static function yearLabel(?ESBTPAnneeUniversitaire $anneeUniversitaire): string
    {
        return (string) ($anneeUniversitaire?->name
            ?? $anneeUniversitaire?->libelle
            ?? 'cette année');
    }

    private static function statusLabel(?string $status): string
    {
        return match ($status) {
            'pending', 'en_attente' => 'En attente',
            'active' => 'Active',
            'validated' => 'Validée',
            'cancelled' => 'Annulée',
            null, '' => 'Non défini',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    private static function workflowStepLabel(?string $workflowStep): string
    {
        return match ($workflowStep) {
            'prospect' => 'Prospect',
            'documents_complets' => 'Documents complets',
            'en_validation' => 'En validation',
            'valide' => 'Validé',
            'etudiant_cree' => 'Étudiant créé',
            null, '' => 'Non défini',
            default => ucfirst(str_replace('_', ' ', $workflowStep)),
        };
    }
}
