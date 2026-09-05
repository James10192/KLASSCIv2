<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une prise de vue ouverte pour un étudiant, à faire depuis un téléphone.
 *
 * Le cycle tient en quatre états et ne revient jamais en arrière :
 *
 *   en_attente ──(le téléphone envoie)──▶ recue ──(le guichet tranche)──▶ validee
 *        │                                  │
 *        └──────────(délai passé,           └──▶ refusee
 *                    ou le guichet ferme)──▶ abandonnee
 *
 * Un refus n'efface pas la capture : il la clôt. Reprendre une photo ouvre une
 * NOUVELLE capture, avec un nouveau jeton. Réutiliser le jeton d'une photo
 * refusée rallongerait indéfiniment la vie d'une adresse publique.
 */
class ESBTPCapturePhoto extends Model
{
    use HasFactory;

    public const EN_ATTENTE = 'en_attente';
    public const RECUE = 'recue';
    public const VALIDEE = 'validee';
    public const REFUSEE = 'refusee';
    public const ABANDONNEE = 'abandonnee';

    protected $table = 'esbtp_captures_photo';

    protected $fillable = [
        'jeton',
        'etudiant_id',
        'ouverte_par',
        'expire_at',
        'etat',
        'fichier_provisoire',
        'recue_at',
        'ip_capture',
        'decidee_at',
        'decidee_par',
    ];

    protected $casts = [
        'expire_at' => 'datetime',
        'recue_at' => 'datetime',
        'decidee_at' => 'datetime',
    ];

    /**
     * Le jeton ne doit JAMAIS partir dans une réponse JSON.
     *
     * L'écran du guichet n'en a pas besoin : il reçoit l'adresse complète et le
     * code QR, une fois, à l'ouverture. Le laisser dans les réponses de sondage
     * le ferait traîner dans les journaux du navigateur et dans tout cache
     * intermédiaire, pendant toute la durée de l'attente.
     */
    protected $hidden = ['jeton'];

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    public function ouvreur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ouverte_par');
    }

    /** Le lien est-il encore ouvrable depuis un téléphone ? */
    public function estOuverte(): bool
    {
        return $this->etat === self::EN_ATTENTE && ! $this->estExpiree();
    }

    public function estExpiree(): bool
    {
        return $this->expire_at !== null && $this->expire_at->isPast();
    }

    /** Une photo attend une décision du guichet. */
    public function attendUneDecision(): bool
    {
        return $this->etat === self::RECUE;
    }

    /**
     * Les captures qui ne servent plus à rien et dont le fichier provisoire
     * peut être effacé.
     */
    public function scopePerimees(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('etat', self::EN_ATTENTE)->where('expire_at', '<', now());
        })->orWhereIn('etat', [self::REFUSEE, self::ABANDONNEE]);
    }
}
