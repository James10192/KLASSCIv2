<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class ESBTPLMDJuryMembre extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $table = 'esbtp_lmd_jury_membres';

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
        'jury_id', 'user_id', 'role', 'present',
        'signature_data', 'signature_at', 'signature_ip', 'signature_user_agent',
        'notes',
    ];

    protected $casts = [
        'present' => 'boolean',
        'signature_at' => 'datetime',
    ];

    protected $auditInclude = [
        'role',
        'present',
        'signature_at',
        'signature_ip',
    ];

    public const ROLES = ['president', 'assesseur', 'secretaire', 'consultatif'];

    public function jury(): BelongsTo
    {
        return $this->belongsTo(ESBTPLMDJury::class, 'jury_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function hasSigned(): bool
    {
        return $this->signature_at !== null;
    }

    public function canBeSignedBy(int $userId): bool
    {
        return $this->present && (int) $this->user_id === $userId;
    }
}
