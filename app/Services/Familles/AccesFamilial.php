<?php

namespace App\Services\Familles;

use App\Models\ESBTPFamilyAccessGrant;
use App\Models\ESBTPParent;
use App\Models\ESBTPEtudiant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final class AccesFamilial
{
    /**
     * Lien tuteur administratif != preuve de droit numérique.
     * Un compte étudiant, même partagé historiquement, ne devient jamais
     * un compte parent par le simple fait d'être lié à une fiche parent.
     */
    public function peutConsulter(ESBTPFamilyAccessGrant $grant, User $user): bool
    {
        if ($user->hasRole('etudiant')
            || $grant->verified_at === null || $grant->revoked_at !== null
            || ! $grant->expires_at?->isFuture()) {
            return false;
        }

        $parent = $grant->parent;
        $etudiant = $grant->etudiant;

        if (! $parent || ! $etudiant || ! $parent->user_id
            || (int) $parent->user_id !== (int) $user->id
            || (int) $etudiant->user_id === (int) $user->id) {
            return false;
        }

        // La relation tuteur doit TOUJOURS être courante, même si un ancien
        // grant est encore présent après un changement de responsable.
        if (! $parent->etudiants()->whereKey($etudiant->id)
            ->wherePivot('is_tuteur', true)->exists()) {
            return false;
        }

        // Au dix-huitième anniversaire, l'autorisation d'un tiers majeur est
        // réévaluée. Une date de naissance inconnue exige le même consentement.
        if (($etudiant->date_naissance === null || $etudiant->date_naissance->age >= 18)
            && ! $grant->student_consent_at) {
            return false;
        }

        return true;
    }

    public function approuver(
        ESBTPParent $parent,
        ESBTPEtudiant $etudiant,
        User $agent,
        string $evidenceType,
        string $reference,
        ?string $consentReference
    ): ESBTPFamilyAccessGrant {
        if (! $parent->etudiants()->whereKey($etudiant->id)
            ->wherePivot('is_tuteur', true)->exists()) {
            throw ValidationException::withMessages(['parent_id' => 'Le responsable doit être tuteur de cet étudiant dans KLASSCI.']);
        }
        if ($parent->user_id && (int) $parent->user_id === (int) $etudiant->user_id) {
            throw ValidationException::withMessages(['parent_id' => 'Un parent ne peut pas partager le compte étudiant.']);
        }
        if (($etudiant->date_naissance === null || $etudiant->date_naissance->age >= 18)
            && ! $consentReference) {
            throw ValidationException::withMessages(['consent_reference' => "L'accord explicite de l'étudiant majeur est obligatoire."]);
        }

        $key = (string) config('app.key');

        return DB::transaction(function () use ($parent, $etudiant, $agent, $evidenceType, $reference, $consentReference, $key) {
            return ESBTPFamilyAccessGrant::query()->updateOrCreate(
                ['parent_id' => $parent->id, 'etudiant_id' => $etudiant->id],
                [
                    'verified_by' => $agent->id,
                    'verified_at' => now(),
                    'student_consent_at' => $consentReference ? now() : null,
                    'evidence_type' => $evidenceType,
                    'evidence_hash' => hash_hmac('sha256', trim($reference), $key),
                    'consent_hash' => $consentReference ? hash_hmac('sha256', trim($consentReference), $key) : null,
                    'expires_at' => now()->addYear(),
                    'revoked_at' => null,
                    'revoked_by' => null,
                ]
            );
        });
    }

    public function revoquer(ESBTPFamilyAccessGrant $grant, User $agent): void
    {
        $grant->forceFill(['revoked_at' => now(), 'revoked_by' => $agent->id])->save();
        Log::info('Family access revoked', [
            'grant_id' => $grant->id,
            'revoked_by' => $agent->id,
        ]);
    }
}
