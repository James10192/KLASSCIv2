<?php

namespace App\Models\Concerns;

trait EstPorteurDeRendezVous
{
    public function verrouillerPourRdv(): ?static
    {
        return $this->newQuery()->whereKey($this->getKey())->lockForUpdate()->first();
    }

    public function dejaInviteRdv(): bool
    {
        return $this->rdv_invite_at !== null;
    }

    public function marquerInviteRdv(): void
    {
        $this->forceFill(['rdv_invite_at' => now()])->save();
    }

    public function dossierClos(): bool
    {
        return in_array($this->statut, static::statutsDossierClos(), true);
    }
}
