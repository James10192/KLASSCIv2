<?php

namespace App\Services\Admissions;

use App\Models\ESBTPCandidatureWorkflow;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Active un compte étudiant à partir d'un workflow déjà authentifié par son
 * canal (jeton e-mail ou URL WhatsApp signée).
 *
 * Activer n'est pas vérifier une adresse : seul le lien reçu PAR E-MAIL prouve
 * que l'étudiant lit cette boîte. Un lien WhatsApp ne touche pas à
 * `email_verified_at`.
 */
final class AdmissionAccountActivator
{
    public function __construct(private readonly InscriptionWorkflowSettings $settings)
    {
    }

    public function activateWorkflow(ESBTPCandidatureWorkflow $workflow, string $password, bool $emailProven = false): ESBTPCandidatureWorkflow
    {
        return DB::transaction(function () use ($workflow, $password, $emailProven) {
            $workflow = ESBTPCandidatureWorkflow::query()
                ->lockForUpdate()
                ->with(['candidature', 'etudiant.user'])
                ->findOrFail($workflow->id);

            // Double clic, ou deux onglets : le second ne réécrit pas le mot
            // de passe que le premier vient de poser.
            if ($workflow->accessActivated()) {
                throw ValidationException::withMessages([
                    'activation' => 'Cet espace étudiant a déjà été activé. Connectez-vous avec votre mot de passe.',
                ]);
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
                'email_verified_at' => $emailProven && $user->email ? now() : $user->email_verified_at,
                'first_login_at' => $user->first_login_at ?: now(),
            ])->save();

            $workflow->forceFill([
                'activation_token_used_at' => now(),
                'access_activated_at' => now(),
                'activation_token_hash' => null,
                'activation_token_expires_at' => null,
            ]);
            $workflow->state = ManagedInscriptionWorkflow::stateFor($workflow, $this->settings->mode());
            $workflow->save();

            return $workflow->fresh(['candidature', 'etudiant.user']);
        });
    }
}
