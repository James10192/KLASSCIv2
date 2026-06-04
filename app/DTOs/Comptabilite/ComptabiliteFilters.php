<?php

namespace App\DTOs\Comptabilite;

use Illuminate\Http\Request;

final class ComptabiliteFilters
{
    public function __construct(
        public readonly ?int $anneeId,
        public readonly ?int $filiereId,
        public readonly ?int $classeId,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            anneeId: self::nullableInt($request->get('annee')),
            filiereId: self::nullableInt($request->get('filiere')),
            classeId: self::nullableInt($request->get('classe')),
        );
    }

    public static function empty(): self
    {
        return new self(anneeId: null, filiereId: null, classeId: null);
    }

    /**
     * Force une année par défaut quand l'utilisateur n'en a pas explicitement choisi une.
     *
     * Sans cette méthode, le dashboard sans filtre user agrégeait toutes années
     * confondues, ce qui causait des KPIs incohérents avec la CLI (qui filtre par
     * annee courante par défaut). Voir audit 2026-06-04 §2.12.
     */
    public function withAnneeDefault(int $defaultAnneeId): self
    {
        if ($this->anneeId !== null) {
            return $this;
        }

        return new self(
            anneeId: $defaultAnneeId,
            filiereId: $this->filiereId,
            classeId: $this->classeId,
        );
    }

    private static function nullableInt(mixed $value): ?int
    {
        return ($value === null || $value === '') ? null : (int) $value;
    }
}
