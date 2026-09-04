<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Une entree du journal des maquettes : un geste, son auteur, sa date, et
 * l'etat des pivots avant et apres.
 *
 * Volontairement sans SoftDeletes : une trace qu'on peut faire disparaitre ne
 * repond plus a la question qu'elle sert a repondre.
 */
class ESBTPLMDJournalMaquette extends Model
{
    use HasFactory;

    protected $table = 'esbtp_lmd_journal_maquette';

    protected $fillable = [
        'unite_enseignement_id',
        'action',
        'libelle',
        'etat_avant',
        'etat_apres',
        'annulee_at',
        'annulee_par',
        'created_by',
    ];

    protected $casts = [
        'etat_avant' => 'array',
        'etat_apres' => 'array',
        'annulee_at' => 'datetime',
    ];

    public function unite()
    {
        return $this->belongsTo(ESBTPUniteEnseignement::class, 'unite_enseignement_id');
    }

    public function auteur()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function annulePar()
    {
        return $this->belongsTo(User::class, 'annulee_par');
    }

    public function estAnnulee(): bool
    {
        return $this->annulee_at !== null;
    }
}
