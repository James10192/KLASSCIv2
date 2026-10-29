<?php

namespace App\Domain\Students\Accessibility\Actions;

use App\Models\ESBTPStudentAccessibilityProfile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Crée ou met à jour le profil d'accessibilité d'un étudiant à partir d'un
 * payload `accessibility[*]`. Retourne null si rien de significatif n'est
 * rempli (skip silencieux). Lance ValidationException sur données invalides.
 */
class AttachAccessibilityProfile
{
    public function execute(int $etudiantId, array $data, int $userId): ?ESBTPStudentAccessibilityProfile
    {
        $payload = $this->normalize($data);

        if (! $this->hasSignificantData($payload)) {
            return null;
        }

        $validated = $this->validate($payload);

        $existing = ESBTPStudentAccessibilityProfile::where('etudiant_id', $etudiantId)->first();

        if ($existing) {
            $existing->update(array_merge($validated, ['updated_by' => $userId]));
            return $existing->refresh();
        }

        return ESBTPStudentAccessibilityProfile::create(array_merge($validated, [
            'etudiant_id' => $etudiantId,
            'created_by'  => $userId,
            'updated_by'  => $userId,
        ]));
    }

    private function normalize(array $data): array
    {
        $bool = static fn ($v) => filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;

        return [
            'has_official_recognition' => $bool($data['has_official_recognition'] ?? false),
            'recognition_reference'    => $data['recognition_reference'] ?? null,
            'categories'               => array_values(array_filter((array) ($data['categories'] ?? []))),
            'short_description'        => $data['short_description'] ?? null,
            'full_description'         => $data['full_description'] ?? null,
            'accommodations'           => array_values(array_filter((array) ($data['accommodations'] ?? []))),
            'accommodations_notes'     => $data['accommodations_notes'] ?? null,
            'requires_third_time'      => $bool($data['requires_third_time'] ?? false),
            'third_time_percentage'    => isset($data['third_time_percentage']) && $data['third_time_percentage'] !== ''
                ? (int) $data['third_time_percentage']
                : 33,
            'assistant_required'       => $bool($data['assistant_required'] ?? false),
            'effective_from'           => ! empty($data['effective_from']) ? Carbon::parse($data['effective_from'])->toDateString() : null,
            'effective_to'             => ! empty($data['effective_to']) ? Carbon::parse($data['effective_to'])->toDateString() : null,
        ];
    }

    private function hasSignificantData(array $payload): bool
    {
        return $payload['has_official_recognition']
            || $payload['requires_third_time']
            || $payload['assistant_required']
            || ! empty($payload['categories'])
            || ! empty($payload['accommodations'])
            || ! empty($payload['short_description'])
            || ! empty($payload['full_description'])
            || ! empty($payload['recognition_reference'])
            || ! empty($payload['accommodations_notes']);
    }

    /** @throws ValidationException */
    private function validate(array $payload): array
    {
        return Validator::make($payload, ESBTPStudentAccessibilityProfile::validationRules())->validate();
    }
}
