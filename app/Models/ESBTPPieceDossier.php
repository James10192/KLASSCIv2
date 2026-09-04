<?php

namespace App\Models;

use App\Enums\AppartenancePieceDossier;
use App\Enums\EcheancePieceDossier;
use App\Enums\FormePieceDossier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Une pièce du catalogue de l'établissement.
 *
 * Le catalogue dit ce que l'école réclame, et à qui. Il ne dit jamais où en est
 * un étudiant : cet état-là vivra dans les tables de dépôt et de consommation
 * du lot suivant (docs/lot-2-pieces-a-reprendre.md).
 *
 * Une pièce appartient d'abord à l'étudiant, pas à l'année : `appartenance` dit,
 * pièce par pièce, si le dépôt dure et se consomme sur plusieurs inscriptions,
 * ou s'il est redonné chaque rentrée.
 */
class ESBTPPieceDossier extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'esbtp_pieces_dossier';

    protected $fillable = [
        'code',
        'libelle',
        'description',
        'is_obligatoire',
        'forme_attendue',
        'exemplaires_par_inscription',
        'appartenance',
        'duree_validite_mois',
        'echeance',
        'is_active',
        'ordre',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_obligatoire' => 'boolean',
        'is_active' => 'boolean',
        'exemplaires_par_inscription' => 'integer',
        'ordre' => 'integer',
        'forme_attendue' => FormePieceDossier::class,
        'echeance' => EcheancePieceDossier::class,
        'appartenance' => AppartenancePieceDossier::class,
        // Le cast entier de Laravel laisse passer le nul tel quel, et c'est ce
        // qu'on veut ici : NULL veut dire « ne périme jamais ». Le jour où
        // quelqu'un le remplace par un `->default(0)` ou un `?? 0`, toute pièce
        // sans durée devient périmée à l'instant du dépôt.
        'duree_validite_mois' => 'integer',
    ];

    /**
     * Portée : deux relations additives. Aucune ligne = la pièce vaut pour tout
     * le monde. On ne stocke jamais « toutes » en extension, sans quoi une
     * filière créée demain n'hériterait de rien et le catalogue devrait être
     * rouvert à chaque ouverture de filière.
     */
    public function filieres(): BelongsToMany
    {
        return $this->belongsToMany(
            ESBTPFiliere::class,
            'esbtp_piece_dossier_filiere',
            'piece_dossier_id',
            'filiere_id'
        );
    }

    public function niveaux(): BelongsToMany
    {
        return $this->belongsToMany(
            ESBTPNiveauEtude::class,
            'esbtp_piece_dossier_niveau',
            'piece_dossier_id',
            'niveau_id'
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActives(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Ordre d'affichage stable : celui choisi par l'école, puis le libellé. */
    public function scopeOrdonne(Builder $query): Builder
    {
        return $query->orderBy('ordre')->orderBy('libelle');
    }

    /** @return array<int, int> */
    public function filiereIds(): array
    {
        return $this->filieres->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /** @return array<int, int> */
    public function niveauIds(): array
    {
        return $this->niveaux->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function concerneToutesFilieres(): bool
    {
        return $this->filieres->isEmpty();
    }

    public function concerneTousNiveaux(): bool
    {
        return $this->niveaux->isEmpty();
    }

    /**
     * La pièce s'applique-t-elle à cette filière et à ce niveau ?
     *
     * Les deux portées se cumulent : une pièce restreinte à la filière A et au
     * niveau 1 ne concerne que les étudiants qui sont dans les deux. Une portée
     * vide d'un côté ne restreint rien de ce côté.
     *
     * Aucun accès base ici : la méthode travaille sur les relations déjà
     * chargées, ce qui la rend appelable sur une collection entière sans
     * déclencher une requête par ligne, et testable sans base de données.
     */
    public function sApplique(?int $filiereId, ?int $niveauId): bool
    {
        if (! $this->concerneToutesFilieres()) {
            if ($filiereId === null || ! in_array((int) $filiereId, $this->filiereIds(), true)) {
                return false;
            }
        }

        if (! $this->concerneTousNiveaux()) {
            if ($niveauId === null || ! in_array((int) $niveauId, $this->niveauIds(), true)) {
                return false;
            }
        }

        return true;
    }

    /** Résumé de portée lisible au guichet : « Toutes filières / Licence 1 ». */
    public function libelleScope(): string
    {
        $filieres = $this->concerneToutesFilieres()
            ? 'Toutes filières'
            : $this->filieres->pluck('name')->implode(', ');

        $niveaux = $this->concerneTousNiveaux()
            ? 'Tous niveaux'
            : $this->niveaux->pluck('name')->implode(', ');

        return $filieres . ' / ' . $niveaux;
    }
}
