<?php

namespace App\Rules;

use App\Models\ESBTPEvaluation;
use Illuminate\Contracts\Validation\Rule;

/**
 * Vérifie qu'une note ne dépasse pas le barème de l'évaluation,
 * et que l'évaluation a un barème valide (> 0).
 *
 * Usage:
 *   'note' => ['nullable', 'numeric', new NoteRespectsBareme($evaluationId)]
 *
 * Note Laravel 9 : on utilise le contrat `Rule` (passes/message) car
 * `ValidationRule` n'existe qu'à partir de Laravel 10.
 */
class NoteRespectsBareme implements Rule
{
    private string $errorMessage = 'La note dépasse le barème de l\'évaluation.';

    /**
     * @param  mixed  $evaluationId  ID brut depuis la requête (string, int, null).
     */
    public function __construct(private mixed $evaluationId) {}

    public function passes($attribute, $value): bool
    {
        // Si la note est null/vide, on laisse passer (gestion par les autres rules : nullable, etc.)
        if ($value === null || $value === '') {
            return true;
        }

        if (! is_numeric($value)) {
            // Déléguer à la règle 'numeric'
            return true;
        }

        $evaluationId = $this->evaluationId;
        if ($evaluationId === null || $evaluationId === '' || ! is_numeric($evaluationId)) {
            $this->errorMessage = "L'évaluation associée est introuvable.";

            return false;
        }

        $evaluation = ESBTPEvaluation::query()->find((int) $evaluationId);
        if (! $evaluation) {
            $this->errorMessage = "L'évaluation associée est introuvable.";

            return false;
        }

        $bareme = (float) $evaluation->bareme;
        if ($bareme <= 0) {
            $this->errorMessage = "Le barème de l'évaluation est invalide ({$bareme}). Corrigez le barème avant de saisir des notes.";

            return false;
        }

        if ((float) $value > $bareme) {
            $this->errorMessage = "La note ({$value}) dépasse le barème de l'évaluation ({$bareme}).";

            return false;
        }

        return true;
    }

    public function message(): string
    {
        return $this->errorMessage;
    }
}
