<?php

namespace App\Models;

use App\Enums\CanalVerification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * La verification du contact d'une demande publique (candidature ou
 * reinscription). Une ligne par demande ; un renvoi la reecrit.
 *
 * `demande_id` (UUID) est le seul identifiant qui sort d'ici.
 */
class ESBTPVerificationContact extends Model
{
    protected $table = 'esbtp_verifications_contact';

    protected $fillable = [
        'demande_id', 'verifiable_type', 'verifiable_id', 'canal', 'destination',
        'code_hash', 'jeton_hash', 'code_expire_at', 'jeton_expire_at', 'tentatives', 'tentatives_total',
        'mailpulse_verification_id', 'mailpulse_message_id', 'dernier_echec',
        'dernier_envoi_at', 'verifie_at',
    ];

    protected $hidden = ['code_hash', 'jeton_hash', 'destination'];

    protected $casts = [
        'canal' => CanalVerification::class,
        'code_expire_at' => 'datetime',
        'jeton_expire_at' => 'datetime',
        'dernier_envoi_at' => 'datetime',
        'verifie_at' => 'datetime',
        'tentatives' => 'integer',
        'tentatives_total' => 'integer',
    ];

    public function verifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function estVerifiee(): bool
    {
        return $this->verifie_at !== null;
    }
}
