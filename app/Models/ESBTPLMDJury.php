<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class ESBTPLMDJury extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $table = 'esbtp_lmd_jurys';

    /**
     * Les evenements reellement audites : les MUTATIONS, pas les lectures.
     *
     * `config/audit.php` active aussi `retrieved`, ce qui fait ecrire une ligne
     * dans `audits` a chaque fois qu'un modele est LU. Vingt et un modeles s'en
     * protegent deja par cette meme propriete ; ceux-ci ne le faisaient pas.
     *
     * Le cout n'etait pas theorique : ce modele est charge en eager-load avec la
     * liste des etudiants et cinq fois dans les classes. Afficher une classe de
     * quarante etudiants ecrivait quarante lignes en base, a chaque affichage.
     *
     * Ce qui reste trace : creation, modification, suppression, restauration.
     * La conservation OHADA porte sur les mutations, pas sur les consultations.
     */
    protected $auditEvents = ['created', 'updated', 'deleted', 'restored'];
    protected $fillable = [
        'annee_universitaire_id', 'session_id', 'parcours_id', 'classe_id', 'semestre',
        'libelle', 'date_jury',
        'pv_numero', 'pv_path', 'pv_genere_at', 'pv_genere_par',
        'status', 'clos_at', 'publie_at', 'publie_par',
        'observations',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'date_jury' => 'date',
        'pv_genere_at' => 'datetime',
        'clos_at' => 'datetime',
        'publie_at' => 'datetime',
        'semestre' => 'integer',
    ];

    protected $auditInclude = [
        'libelle',
        'date_jury',
        'status',
        'pv_numero',
        'pv_path',
        'pv_genere_at',
        'pv_genere_par',
        'publie_at',
        'publie_par',
        'clos_at',
    ];

    public const STATUSES = ['preparation', 'en_cours', 'clos', 'publie', 'archive'];

    public function anneeUniversitaire(): BelongsTo
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ESBTPLMDSession::class, 'session_id');
    }

    public function parcours(): BelongsTo
    {
        return $this->belongsTo(ESBTPLMDParcours::class, 'parcours_id');
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(ESBTPClasse::class, 'classe_id');
    }

    public function membres(): HasMany
    {
        return $this->hasMany(ESBTPLMDJuryMembre::class, 'jury_id');
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(ESBTPLMDJuryDecision::class, 'jury_id');
    }

    public function officialDocuments(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\App\Domain\OfficialDocuments\Models\OfficialDocument::class, 'source', 'source_type', 'source_id');
    }

    public function scopeNotLocked(Builder $query): Builder
    {
        return $query->whereNull('pv_genere_at');
    }

    public function isLocked(): bool
    {
        return $this->pv_genere_at !== null;
    }

    public function isPublished(): bool
    {
        return $this->status === 'publie';
    }

    /**
     * Cet utilisateur voit-il l'ensemble des jurys, ou seulement les siens ?
     *
     * Presider, deliberer ou publier suppose la vue d'ensemble. La direction des
     * etudes aussi : c'est un poste de supervision pedagogique, et les proces-verbaux
     * sont des pieces legales conservees dix ans que l'etablissement doit pouvoir
     * produire — y compris pour les deliberations qu'elle n'a pas presidees.
     * Consulter sans aucun de ces titres, en revanche, se borne a ses propres jurys.
     */
    public static function utilisateurVoitTousLesJurys(?\App\Models\User $user): bool
    {
        return (bool) ($user?->can('lmd.jury.preside')
            || $user?->can('lmd.jury.deliberate')
            || $user?->can('lmd.jury.publish')
            || $user?->can('identity.direct_studies'));
    }

    /**
     * Refuse l'ouverture d'une deliberation dont le simple consultant n'est pas membre.
     *
     * Porte par le modele plutot que par un controleur : la meme deliberation
     * s'ouvre depuis l'ecran du jury ET depuis l'export du proces-verbal annuel.
     * Une garde posee d'un seul cote laisserait l'autre porte ouverte.
     */
    public function assertConsultablePar(?\App\Models\User $user): void
    {
        // Exporter suppose de pouvoir consulter : le droit d'export choisit un
        // format, il n'ouvre pas une deliberation.
        abort_unless(
            (bool) $user?->can('lmd.jury.view'),
            403,
            "La consultation des délibérations ne vous est pas ouverte. Demandez ce droit au responsable de la scolarité."
        );

        if (self::utilisateurVoitTousLesJurys($user)) {
            return;
        }

        abort_unless(
            $this->membres()->where('user_id', $user?->getAuthIdentifier())->exists(),
            403,
            "Vous n'êtes pas membre de ce jury : ses délibérations ne vous sont pas accessibles. Demandez au président du jury de vous ajouter à sa composition."
        );
    }
}
