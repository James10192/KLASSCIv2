<?php

namespace App\Models;

use App\Enums\StatutPieceDossier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Etat d'une piece du dossier pour UNE inscription.
 *
 * L'etat vit sur l'inscription et pas sur l'etudiant : l'ecole reprend un
 * exemplaire de chaque piece a chaque rentree. Un etudiant de troisieme annee a
 * donc trois lignes "extrait de naissance", une par annee, et c'est voulu.
 *
 * Ces lignes ne sont jamais supprimees par l'application. Une piece qu'on cesse
 * d'exiger se desactive dans le catalogue ; ce qui a deja ete remis reste ecrit.
 */
class ESBTPInscriptionPiece extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'esbtp_inscription_pieces';

    /**
     * Whitelist d'audit : ce qui engage l'ecole vis-a-vis d'un ministere, pas les
     * metadonnees de fichier qui ne prouvent rien par elles-memes.
     */
    protected $auditInclude = [
        'statut',
        'date_remise',
        'constate_par',
        'note',
        'fichier_path',
    ];

    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
    ];

    protected $fillable = [
        'inscription_id',
        'piece_dossier_id',
        'statut',
        'date_remise',
        'constate_par',
        'note',
        'fichier_path',
        'fichier_nom',
        'fichier_taille',
        'fichier_type',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'statut' => StatutPieceDossier::class,
        'date_remise' => 'date',
        'fichier_taille' => 'integer',
    ];

    public function inscription()
    {
        return $this->belongsTo(ESBTPInscription::class, 'inscription_id');
    }

    public function piece()
    {
        return $this->belongsTo(ESBTPPieceDossier::class, 'piece_dossier_id');
    }

    /** L'agent qui a dit avoir eu le papier en main. */
    public function constatePar()
    {
        return $this->belongsTo(User::class, 'constate_par');
    }

    /**
     * Lignes encore dues : statut manquante ET piece toujours exigee.
     *
     * La jointure sur le catalogue est ce qui evite le faux signal permanent :
     * une piece retiree du catalogue ne doit plus etre reclamee, mais sa ligne
     * reste en base pour ne pas effacer l'historique du dossier.
     */
    public function scopeDues(Builder $query): Builder
    {
        return $query
            ->where('statut', StatutPieceDossier::MANQUANTE->value)
            ->whereHas('piece', fn (Builder $q) => $q->where('is_active', true));
    }

    /** Restreint aux pieces que l'ecole declare obligatoires. */
    public function scopeObligatoires(Builder $query): Builder
    {
        return $query->whereHas('piece', fn (Builder $q) => $q->where('is_obligatoire', true));
    }

    /**
     * Vrai si la ligne ne concerne plus le dossier courant : la piece a ete
     * retiree du catalogue. La ligne reste affichable en historique.
     */
    public function estHistorique(): bool
    {
        return $this->piece !== null && ! $this->piece->is_active;
    }
}
