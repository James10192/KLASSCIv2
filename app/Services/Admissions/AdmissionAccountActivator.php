<?php

namespace App\Services\Admissions;

use App\Models\ESBTPCandidatureWorkflow;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Active un compte étudiant à partir d'un workflow déjà authentifié par son
 * canal (jeton e-mail ou URL WhatsApp signée).
 */
final class AdmissionAccountActivator
{
    public function activateWorkflow(ESBTPCandidatureWorkflow $workflow, string $password): ESBTPCandidatureWorkflow
    {
        return DB::transaction(function () use ($workflow, $password) {
            $workflow = ESBTPCandidatureWorkflow::query()
                ->lockForUpdate()
                ->with(['candidature', 'etudiant.user'])
                ->findOrFail($workflow->id);

            if ($workflow->accessActivated()) {
                return $workflow;
            }

            $user = $workflow->etudiant?->user;
            if (! $user) {
                throw ValidationException::withMessages([
                    'compte' => "Le compte étudiant n'a pas pu être préparé.",
                ]);
            }

            $user->forceFill([
                'password' => $password,
                'is_active' => true,
                'must_change_password' => false,
                'email_verified_at' => $user->email ? now() : $user->email_verified_at,
                'first_login_at' => $user->first_login_at ?: now(),
            ])->save();

            $workflow->forceFill([
                'activation_token_used_at' => now(),
                'access_activated_at' => now(),
                'activation_token_hash' => null,
                'activation_token_expires_at' => null,
                'state' => $this->nextState($workflow),
            ])->save();

            return $workflow->fresh(['candidature', 'etudiant.user']);
        });
    }

    private function nextState(ESBTPCandidatureWorkflow $workflow): string
    {
        if (! $workflow->paymentRecorded()) {
            return ESBTPCandidatureWorkflow::STATE_AWAITING_PAYMENT;
        }

        if (! $workflow->documentsValidated()) {
            return ESBTPCandidatureWorkflow::STATE_AWAITING_DOCUMENTS;
        }

        return $workflow->selected_class_id
            ? ESBTPCandidatureWorkflow::STATE_READY_TO_FINALIZE
            : ESBTPCandidatureWorkflow::STATE_AWAITING_STUDENT;
    }
}
