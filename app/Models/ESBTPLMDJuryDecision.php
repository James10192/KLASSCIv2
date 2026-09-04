<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class ESBTPLMDJuryDecision extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $table = 'esbtp_lmd_jury_decisions';

    /**
     * Les evenements reellement audites : les MUTATIONS, pas les lectures.
     *
     * `config/audit.php` active aussi `retrieved`, ce qui fait ecrire une ligne
     * dans `audits` a chaque fois qu'un modele est LU. Vingt et un modeles s'en
     * protegent deja par cette meme propriete ; ceux-ci ne le faisaient pas.
     *
     * Le cout n'etait pas theorique : c'est par ce canal que la table `audits` a
     * enfle au point que la page qui la consulte ne repondait plus.
     *
     * Ce qui reste trace : creation, modification, suppression, restauration.
     * La conservation OHADA porte sur les mutations, pas sur les consultations.
     */
    protected $auditEvents = ['created', 'updated', 'deleted', 'restored'];
    protected $fillable = [
        'jury_id', 'etudiant_id', 'bulletin_id',
        'decision_auto', 'decision', 'mention',
        'override_par_jury', 'motif_override', 'vote_resultat',
        'moyenne_generale', 'credits_obtenus', 'credits_attendus',
        'locked', 'locked_at',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'override_par_jury' => 'boolean',
        'locked' => 'boolean',
        'locked_at' => 'datetime',
        'moyenne_generale' => 'decimal:2',
        'credits_obtenus' => 'integer',
        'credits_attendus' => 'integer',
    ];

    protected $auditInclude = [
        'decision_auto',
        'decision',
        'mention',
        'override_par_jury',
        'motif_override',
        'vote_resultat',
        'moyenne_generale',
        'credits_obtenus',
        'locked',
        'locked_at',
    ];

    public const DECISIONS = [
        'admis',
        'admission_rattrapage',
        'ajourne',
        'exclu',
        'admis_sous_condition',
        'defere',
    ];

    public const MENTIONS = [
        'passable',
        'assez_bien',
        'bien',
        'tres_bien',
        'excellent',
    ];

    public const VOTE_RESULTATS = ['unanime', 'majorite', 'partage_voix_president'];

    public function jury(): BelongsTo
    {
        return $this->belongsTo(ESBTPLMDJury::class, 'jury_id');
    }

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    public function bulletin(): BelongsTo
    {
        return $this->belongsTo(ESBTPLMDBulletin::class, 'bulletin_id');
    }

    public function scopeNotLocked(Builder $query): Builder
    {
        return $query->where('locked', false);
    }

    public function isLocked(): bool
    {
        return (bool) $this->locked;
    }
}
