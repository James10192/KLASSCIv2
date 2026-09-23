<?php

namespace App\Models\Concerns;

use App\Enums\StatutVerificationContact;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

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
 */
trait AttendVerificationContact
{
    public const PORTEE_VERIFICATION = 'contact_verifie';

    /** @var array<string, bool> table => la colonne existe (memo par processus) */
    private static array $colonnePresente = [];

    public static function bootAttendVerificationContact(): void
    {
        static::addGlobalScope(self::PORTEE_VERIFICATION, function (Builder $query) {
            // Au deploiement, le code arrive avant `migrate` : sans la colonne,
            // chaque page qui compte les demandes (badge du menu) tomberait.
            $table = $query->getModel()->getTable();
            if (! (self::$colonnePresente[$table] ??= Schema::hasColumn($table, 'verification_contact'))) {
                return;
            }

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

    /** Le type publie par la route de verification : `candidature` ou `reinscription`. */
    abstract public function typeDemandePublique(): string;
}
