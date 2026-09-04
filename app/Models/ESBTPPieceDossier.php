<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Une entree du catalogue des pieces a fournir au dossier d'inscription.
 *
 * Le scope (filiere_id, niveau_id) porte la variabilite entre ecoles et entre
 * filieres. La ligne (NULL, NULL) est le defaut de l'etablissement.
 */
class ESBTPPieceDossier extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'esbtp_pieces_dossier';

    protected $fillable = [
        'code',
        'libelle',
        'description',
        'filiere_id',
        'niveau_id',
        'est_obligatoire',
        'nombre_exemplaires',
        'ordre',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'est_obligatoire' => 'boolean',
        'is_active' => 'boolean',
        'nombre_exemplaires' => 'integer',
        'ordre' => 'integer',
    ];

    public function filiere()
    {
        return $this->belongsTo(ESBTPFiliere::class, 'filiere_id');
    }

    public function niveau()
    {
        return $this->belongsTo(ESBTPNiveauEtude::class, 'niveau_id');
    }

    public function etats()
    {
        return $this->hasMany(ESBTPInscriptionPiece::class, 'piece_id');
    }

    public function scopeActif(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Specificite du scope : sert a departager deux definitions du meme code.
     * Plus le score est haut, plus la ligne l'emporte sur les autres.
     *
     * (filiere + niveau) 3 > (filiere) 2 > (niveau) 1 > defaut etablissement 0
     */
    public function specificite(): int
    {
        return ($this->filiere_id !== null ? 2 : 0) + ($this->niveau_id !== null ? 1 : 0);
    }

    /**
     * La definition s'applique-t-elle a une inscription de ce scope ?
     * Une ligne de scope NULL s'applique a tout le monde.
     */
    public function couvre(?int $filiereId, ?int $niveauId): bool
    {
        if ($this->filiere_id !== null && (int) $this->filiere_id !== (int) $filiereId) {
            return false;
        }

        if ($this->niveau_id !== null && (int) $this->niveau_id !== (int) $niveauId) {
            return false;
        }

        return true;
    }
}
