<?php

namespace App\Domain\Students\Actions;

use App\Domain\Students\Accessibility\Actions\AttachAccessibilityProfile;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPParent;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UpdateStudentReinscriptionFicheAction
{
    private const STUDENT_FIELDS = [
        'telephone',
        'email_personnel',
        'ville',
        'commune',
        'adresse',
        'situation_matrimoniale',
        'nombre_enfants',
        'groupe_sanguin',
        'urgence_contact_nom',
        'urgence_contact_telephone',
        'urgence_contact_relation',
    ];

    private const PARENT_FIELDS = [
        'nom',
        'prenoms',
        'sexe',
        'profession',
        'adresse',
        'telephone',
        'telephone_secondaire',
        'email',
        'type_piece_identite',
        'numero_piece_identite',
    ];

    /**
     * @return array{etudiant: ESBTPEtudiant, accessibility_warning: ?string}
     */
    public function execute(ESBTPEtudiant $etudiant, array $data, User $actor): array
    {
        $accessibilityWarning = null;

        DB::transaction(function () use ($etudiant, $data, $actor, &$accessibilityWarning) {
            $this->updateEtudiantInfo($etudiant, $data, $actor);
            $this->syncParents($etudiant, $data['parents'] ?? [], $actor);
            $accessibilityWarning = $this->maybeUpdateAccessibility($etudiant, $data, $actor);
        });

        return [
            'etudiant'              => $etudiant->fresh(['parents', 'accessibilityProfile']),
            'accessibility_warning' => $accessibilityWarning,
        ];
    }

    private function updateEtudiantInfo(ESBTPEtudiant $etudiant, array $data, User $actor): void
    {
        $etudiant->fill(Arr::only($data, self::STUDENT_FIELDS));
        $etudiant->updated_by = $actor->id;
        $etudiant->save();

        // Sync compte user si email_personnel a changé
        if ($etudiant->user_id && array_key_exists('email_personnel', $data) && filled($data['email_personnel'])) {
            $user = User::find($etudiant->user_id);
            if ($user && $user->email !== $data['email_personnel']) {
                $user->email = $data['email_personnel'];
                $user->save();
            }
        }
    }

    private function syncParents(ESBTPEtudiant $etudiant, array $parentsPayload, User $actor): void
    {
        $syncMap = [];

        foreach ($parentsPayload as $payload) {
            if (!$this->isParentPayloadFilled($payload)) {
                continue;
            }

            $parentId = (int) Arr::get($payload, 'parent_id');
            $parent = $parentId ? ESBTPParent::find($parentId) : null;

            if ($parent) {
                $parent->fill(Arr::only($payload, self::PARENT_FIELDS));
                $parent->updated_by = $actor->id;
                $parent->save();
            } else {
                $parent = ESBTPParent::create(array_merge(
                    Arr::only($payload, self::PARENT_FIELDS),
                    ['created_by' => $actor->id]
                ));
            }

            $syncMap[$parent->id] = [
                'relation'   => Arr::get($payload, 'relation', 'Autre'),
                'is_tuteur'  => Arr::has($payload, 'is_tuteur') && filter_var($payload['is_tuteur'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($syncMap) >= 2) {
                break;
            }
        }

        $etudiant->parents()->sync($syncMap);
    }

    private function isParentPayloadFilled(array $payload): bool
    {
        return filled(Arr::get($payload, 'nom'))
            || filled(Arr::get($payload, 'parent_id'));
    }

    /**
     * Délègue la mise à jour du profil d'accessibilité à l'action dédiée.
     * Non bloquant — si la validation échoue, retourne un warning à flasher,
     * la mise à jour étudiant + parents reste sauvegardée.
     */
    private function maybeUpdateAccessibility(ESBTPEtudiant $etudiant, array $data, User $actor): ?string
    {
        if (! $actor->can('students.accessibility.edit')) {
            return null;
        }
        if (! array_key_exists('accessibility', $data) || ! is_array($data['accessibility'])) {
            return null;
        }

        try {
            app(AttachAccessibilityProfile::class)->execute(
                $etudiant->id,
                $data['accessibility'],
                $actor->id,
            );
            return null;
        } catch (ValidationException $e) {
            Log::warning('Quick-fiche accessibility validation failed', [
                'etudiant_id' => $etudiant->id,
                'errors'      => $e->errors(),
            ]);
            return 'Le profil d\'accessibilité n\'a pas pu être enregistré (données invalides). Les autres modifications ont bien été sauvegardées.';
        }
    }
}
