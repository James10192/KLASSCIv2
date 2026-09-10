<?php

namespace App\Models\Concerns;

use App\Services\Portail\ReferencePublique;

trait HasReferencePublique
{
    protected static function bootHasReferencePublique(): void
    {
        static::creating(function ($modele) {
            if (! is_string($modele->reference_publique) || $modele->reference_publique === '') {
                $modele->reference_publique = ReferencePublique::tirer();
            }
        });
    }

    public function assurerReferencePublique(): string
    {
        return app(ReferencePublique::class)->assurer($this);
    }

    public function referencePubliqueAffichee(): ?string
    {
        $refs = app(ReferencePublique::class);
        $brut = $refs->normaliser((string) $this->reference_publique);

        return $brut === '' ? null : $refs->formater($brut);
    }
}
