<?php

namespace App\Models\Concerns;

use App\Enums\StatutVerificationContact;
use App\Services\TenantScolariteSettings;
use App\Services\Verification\ContactDeVerification;
use Illuminate\Database\Eloquent\Builder;

/**
 * Le contact d'une demande deposee sur le portail public (candidature,
 * reinscription), et ce que l'ecole peut en faire.
 *
 * La demande reste toujours visible. Quand le reglage d'instance
 * `inscriptions.portail.verification_contact` est actif et que le contact
 * n'est pas prouve, elle est retenue : ni placement automatique en
 * rendez-vous, ni convocation par courriel, jusqu'a « Confirmer le contact ».
 * Reglage coupe, rien n'est retenu, meme une demande marquee auparavant.
 */
trait AttendVerificationContact
{
    /**
     * Pose par le depot quand un redepot a change l'adresse ou le numero.
     * Propriete PHP declaree, pas un attribut : elle n'est jamais ecrite en base.
     */
    public bool $contactModifieAuDepot = false;

    /** Un code attend la famille. */
    public function contactNonVerifie(): bool
    {
        return in_array($this->verification_contact, StatutVerificationContact::valeursEnAttente(), true);
    }

    /** Contact jamais prouve, et reglage actif : pas de convocation ni de placement automatique. */
    public function contactAConfirmer(): bool
    {
        return in_array($this->verification_contact, StatutVerificationContact::valeursAConfirmer(), true)
            && app(TenantScolariteSettings::class)->verificationContactActive();
    }

    /** @param  Builder<static>  $query */
    public function scopeContactUtilisable(Builder $query): Builder
    {
        if (! app(TenantScolariteSettings::class)->verificationContactActive()) {
            return $query;
        }

        $colonne = $this->qualifyColumn('verification_contact');

        return $query->where(fn (Builder $q) => $q->whereNull($colonne)->orWhereNotIn($colonne, StatutVerificationContact::valeursAConfirmer()));
    }

    /** Le filtre « Contact non verifie » des corbeilles. */
    public function scopeContactNonConfirme(Builder $query): Builder
    {
        return $query->whereIn($this->qualifyColumn('verification_contact'), StatutVerificationContact::valeursAConfirmer());
    }

    /**
     * Ce que l'agent avait sous les yeux : l'etat, le contact et la derniere
     * ecriture. « Confirmer le contact » n'agit que si rien n'a bouge depuis.
     */
    public function empreinteContact(): string
    {
        return hash('sha256', implode('|', [
            (string) $this->verification_contact,
            // Le contact tel que la verification le lit (etudiant pour une
            // reinscription) : modifier la fiche de l'etudiant change l'empreinte.
            json_encode($this->contactAffiche()),
            (string) $this->updated_at?->getTimestamp(),
        ]));
    }

    /** @return array{canal: string, destination: string}|null */
    public function contactAffiche(): ?array
    {
        $contact = app(ContactDeVerification::class)->pour($this);

        return $contact === null ? null : ['canal' => $contact['canal']->value, 'destination' => $contact['destination']];
    }

    /**
     * Change l'etat de verification (et d'autres champs au besoin), sans
     * declencher les observateurs : c'est le portail qui ecrit, pas un agent.
     *
     * @param  array<string, mixed>  $autres
     */
    public function poserVerificationContact(?StatutVerificationContact $statut, array $autres = []): void
    {
        $this->forceFill(($statut === null ? [] : ['verification_contact' => $statut->value]) + $autres)->saveQuietly();
    }

    /** Le type publie par la route de verification : `candidature` ou `reinscription`. */
    abstract public function typeDemandePublique(): string;
}
