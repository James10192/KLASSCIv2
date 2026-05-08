<?php

namespace App\Domain\Students\Accessibility\Actions;

use App\Models\ESBTPStudentAccessibilityProfile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Crée ou met à jour le profil d'accessibilité d'un étudiant à partir d'un
 * payload `accessibility[*]` (typiquement venant d'un sous-formulaire).
 *
 * Retourne null si aucun champ significatif n'est rempli (skip silencieux,
 * cas où l'utilisateur a ouvert la section optionnelle puis tout laissé vide).
 *
 * Lance ValidationException si des données invalides sont posées.
 *
 * Réutilisable depuis :
 *   - ESBTPStudentAccessibilityController::store (édition fiche étudiant)
 *   - ESBTPInscriptionController::store           (création d'inscription)
 *   - tout autre point d'entrée qui produit un payload accessibility
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

        $profile = ESBTPStudentAccessibilityProfile::updateOrCreate(
            ['etudiant_id' => $etudiantId],
            array_merge($validated, [
                'updated_by' => $userId,
                'created_by' => $userId,
            ])
        );

        return $profile;
    }

    /**
     * Cast les booléens et nettoie les arrays vides envoyés en chaînes.
     */
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

    /**
     * Détecte si l'utilisateur a réellement saisi quelque chose, sinon on
     * skip silencieusement (cas où la section optionnelle a été ouverte
     * sans rien remplir).
     */
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

    /**
     * @throws ValidationException
     */
    private function validate(array $payload): array
    {
        $categoryKeys = array_keys(ESBTPStudentAccessibilityProfile::CATEGORIES);
        $accommodationKeys = array_keys(ESBTPStudentAccessibilityProfile::ACCOMMODATIONS);

        return Validator::make($payload, [
            'has_official_recognition' => 'boolean',
            'recognition_reference'    => 'nullable|string|max:100',
            'categories'               => 'nullable|array',
            'categories.*'             => ['string', Rule::in($categoryKeys)],
            'short_description'        => 'nullable|string|max:200',
            'full_description'         => 'nullable|string|max:5000',
            'accommodations'           => 'nullable|array',
            'accommodations.*'         => ['string', Rule::in($accommodationKeys)],
            'accommodations_notes'     => 'nullable|string|max:2000',
            'requires_third_time'      => 'boolean',
            'third_time_percentage'    => 'nullable|integer|min:0|max:100',
            'assistant_required'       => 'boolean',
            'effective_from'           => 'nullable|date',
            'effective_to'             => 'nullable|date|after_or_equal:effective_from',
        ])->validate();
    }
}
