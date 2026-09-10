<?php

namespace App\Contracts;

interface PorteurDeRendezVous
{
    public function verrouillerPourRdv(): ?static;

    /** @return array{candidature_id: int|null, reinscription_demande_id: int|null} */
    public function clesReservationRdv(): array;

    /** @return array{nom: string, prenoms: string, telephone: string, date_naissance: string, email: ?string} */
    public function snapshotRdv(): array;

    public function emailRdv(): ?string;

    public function referencePubliqueAffichee(): ?string;

    public function assurerReferencePublique(): string;

    public function dejaInviteRdv(): bool;

    public function marquerInviteRdv(): void;
}
