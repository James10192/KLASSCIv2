<?php

namespace App\Models\Concerns;

use App\Enums\StatutVerificationContact;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * Une demande publique dont le contact n'est pas encore verifie n'existe pas
 * pour l'ecole.
 *
 * Portee GLOBALE, et c'est voulu : la corbeille, le badge de la barre
 * laterale, l'affectation des rendez-vous, la recherche par reference du
 * portail… une douzaine de requetes lisent ces tables, et en oublier une
 * suffirait a faire traiter un dossier dont l'adresse n'a jamais repondu.
 *
 * Seuls le depot (qui doit retrouver la demande pour la relancer) et la
 * verification elle-meme lisent sans la portee : `sansFiltreVerification()`.
 *
 * Les migrations passent au deploiement, avant la mise en service du code :
 * la colonne est presente, la portee ne la teste pas.
 */
trait AttendVerificationContact
{
    public const PORTEE_VERIFICATION = 'contact_verifie';

    /**
     * Pose par le depot quand un redepot a change l'adresse ou le numero.
     * Propriete PHP declaree, pas un attribut : elle n'est jamais ecrite en base.
     */
    public bool $contactModifieAuDepot = false;

    public static function bootAttendVerificationContact(): void
    {
        static::addGlobalScope(self::PORTEE_VERIFICATION, function (Builder $query) {
            $colonne = $query->getModel()->qualifyColumn('verification_contact');

            $query->where(fn (Builder $q) => $q
                ->whereNull($colonne)
                ->orWhereNotIn($colonne, StatutVerificationContact::valeursMasquees()));
        });
    }

    public static function sansFiltreVerification(): Builder
    {
        return static::query()->withoutGlobalScope(self::PORTEE_VERIFICATION);
    }

    public function contactNonVerifie(): bool
    {
        return in_array($this->verification_contact, StatutVerificationContact::valeursMasquees(), true);
    }

    /** Visible, mais contact jamais prouve : pas de convocation ni de placement automatique. */
    public function contactAConfirmer(): bool
    {
        return in_array($this->verification_contact, StatutVerificationContact::valeursAConfirmer(), true);
    }

    /**
     * Ce que l'agent avait sous les yeux : l'etat, le contact et la derniere
     * ecriture. « Confirmer le contact » n'agit que si rien n'a bouge depuis.
     */
    public function empreinteContact(): string
    {
        return hash('sha256', implode('|', [
            (string) $this->verification_contact,
            mb_strtolower(trim((string) $this->getAttribute('email'))),
            trim((string) $this->getAttribute('telephone')),
            (string) $this->updated_at?->getTimestamp(),
        ]));
    }

    /** @param  Builder<static>  $query */
    public function scopeContactUtilisable(Builder $query): Builder
    {
        $colonne = $this->qualifyColumn('verification_contact');

        return $query->where(fn (Builder $q) => $q->whereNull($colonne)->orWhereNotIn($colonne, StatutVerificationContact::valeursAConfirmer()));
    }

    /**
     * Change l'etat de verification (et d'autres champs au besoin). Si la
     * demande apparait ou disparait pour l'ecole, le compteur du menu est
     * invalide : sinon il mentirait pendant une minute.
     *
     * @param  array<string, mixed>  $autres
     */
    public function poserVerificationContact(?StatutVerificationContact $statut, array $autres = []): void
    {
        $avant = $this->contactNonVerifie();
        $this->forceFill(($statut === null ? [] : ['verification_contact' => $statut->value]) + $autres)->saveQuietly();

        if ($avant !== $this->contactNonVerifie()) {
            Cache::forget($this->cleCacheCompteur());
        }
    }

    /** Le type publie par la route de verification : `candidature` ou `reinscription`. */
    abstract public function typeDemandePublique(): string;

    /** La cle du compteur de la barre laterale qui compte ces demandes. */
    abstract public function cleCacheCompteur(): string;
}
