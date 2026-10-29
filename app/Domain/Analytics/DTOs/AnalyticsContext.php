<?php

namespace App\Domain\Analytics\DTOs;

use Illuminate\Http\Request;

/**
 * Contexte d'exécution d'un Predictor : portée de la requête analytique.
 * Tous les champs sont nullables — null = "tous". Conventionnellement, plus
 * un champ est rempli, plus la prédiction est granulaire (étudiant > classe
 * > filière > année > tenant).
 */
final class AnalyticsContext
{
    public function __construct(
        public readonly ?int $anneeId,
        public readonly ?int $filiereId,
        public readonly ?int $classeId,
        public readonly ?int $etudiantId,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            anneeId: self::nullableInt($request->get('annee')),
            filiereId: self::nullableInt($request->get('filiere')),
            classeId: self::nullableInt($request->get('classe')),
            etudiantId: self::nullableInt($request->get('etudiant')),
        );
    }

    public static function empty(): self
    {
        return new self(null, null, null, null);
    }

    /**
     * Hash stable du contexte pour clé cache et storage analytics_predictions.
     */
    public function hash(): string
    {
        return hash('sha256', json_encode($this->toArray()));
    }

    /**
     * @return array{anneeId:?int, filiereId:?int, classeId:?int, etudiantId:?int}
     */
    public function toArray(): array
    {
        return [
            'anneeId' => $this->anneeId,
            'filiereId' => $this->filiereId,
            'classeId' => $this->classeId,
            'etudiantId' => $this->etudiantId,
        ];
    }

    private static function nullableInt(mixed $value): ?int
    {
        return ($value === null || $value === '') ? null : (int) $value;
    }
}
