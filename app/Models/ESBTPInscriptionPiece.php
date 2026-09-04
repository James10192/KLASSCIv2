<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Etat d'une piece pour UNE inscription (donc pour une annee donnee).
 *
 * L'absence de ligne vaut « manquant » : le suivi n'exige aucun backfill pour
 * dire la verite le jour ou l'ecole configure son catalogue.
 */
class ESBTPInscriptionPiece extends Model
{
    use HasFactory;

    protected $table = 'esbtp_inscription_pieces';

    public const STATUT_MANQUANT = 'manquant';
    public const STATUT_FOURNI = 'fourni';
    public const STATUT_NON_APPLICABLE = 'non_applicable';

    protected $fillable = [
        'inscription_id',
        'piece_id',
        'statut',
        'exemplaires_fournis',
        'date_fourniture',
        'recu_par',
        'commentaire',
    ];

    protected $casts = [
        'date_fourniture' => 'date',
        'exemplaires_fournis' => 'integer',
    ];

    public function inscription()
    {
        return $this->belongsTo(ESBTPInscription::class, 'inscription_id');
    }

    public function piece()
    {
        return $this->belongsTo(ESBTPPieceDossier::class, 'piece_id');
    }

    public function recuPar()
    {
        return $this->belongsTo(User::class, 'recu_par');
    }

    /**
     * Les statuts qui dispensent de reclamer la piece. « non_applicable » couvre
     * le cas ou le secretariat a constate que la piece ne concerne pas l'etudiant.
     */
    public static function statutsSolde(): array
    {
        return [self::STATUT_FOURNI, self::STATUT_NON_APPLICABLE];
    }
}
