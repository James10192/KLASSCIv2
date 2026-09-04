<?php

namespace App\Models;

use App\Enums\EcheanceDocumentRequis;
use App\Enums\FormeDocumentRequis;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Une piece du catalogue de l'etablissement.
 *
 * Le catalogue dit ce que l'ecole reclame et a qui ; il ne dit jamais ou en est
 * un etudiant. L'etat « fourni / manquant » se pose sur l'inscription (lot 2),
 * parce que l'ecole redemande les pieces a chaque annee pour le ministere.
 */
class ESBTPDocumentRequis extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'esbtp_documents_requis';

    protected $fillable = [
        'code',
        'libelle',
        'description',
        'is_obligatoire',
        'forme_attendue',
        'nombre_exemplaires',
        'echeance',
        'filiere_ids',
        'niveau_ids',
        'is_active',
        'ordre',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_obligatoire'     => 'boolean',
        'is_active'          => 'boolean',
        'nombre_exemplaires' => 'integer',
        'ordre'              => 'integer',
        'filiere_ids'        => 'array',
        'niveau_ids'         => 'array',
        'forme_attendue'     => FormeDocumentRequis::class,
        'echeance'           => EcheanceDocumentRequis::class,
    ];

    public function scopeActives(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Ordre d'affichage stable : l'ordre choisi par l'ecole, puis le libelle. */
    public function scopeOrdonne(Builder $query): Builder
    {
        return $query->orderBy('ordre')->orderBy('libelle');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Portee vide = toutes les filieres. On ne stocke jamais « toutes » en
     * extension : une filiere creee demain doit heriter des pieces communes
     * sans qu'on ait a rouvrir le catalogue.
     */
    public function concerneToutesFilieres(): bool
    {
        return empty($this->filiere_ids);
    }

    public function concerneTousNiveaux(): bool
    {
        return empty($this->niveau_ids);
    }

    /**
     * La piece s'applique-t-elle a cette filiere et ce niveau ?
     *
     * Volontairement sans acces base : appelable sur une collection deja
     * chargee, et testable sans base de donnees.
     */
    public function sApplique(?int $filiereId, ?int $niveauId): bool
    {
        if (! $this->concerneToutesFilieres()) {
            if ($filiereId === null || ! in_array((int) $filiereId, array_map('intval', $this->filiere_ids), true)) {
                return false;
            }
        }

        if (! $this->concerneTousNiveaux()) {
            if ($niveauId === null || ! in_array((int) $niveauId, array_map('intval', $this->niveau_ids), true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Resume de portee lisible au guichet (« Toutes filieres / Licence 1 »).
     * Les noms sont resolus par l'appelant pour ne pas declencher de requete
     * par ligne dans une liste.
     */
    public function libelleScope(array $nomsFilieres = [], array $nomsNiveaux = []): string
    {
        $filieres = $this->concerneToutesFilieres()
            ? 'Toutes filieres'
            : implode(', ', array_map(
                fn ($id) => $nomsFilieres[(int) $id] ?? ('Filiere #' . $id),
                $this->filiere_ids
            ));

        $niveaux = $this->concerneTousNiveaux()
            ? 'Tous niveaux'
            : implode(', ', array_map(
                fn ($id) => $nomsNiveaux[(int) $id] ?? ('Niveau #' . $id),
                $this->niveau_ids
            ));

        return $filieres . ' / ' . $niveaux;
    }
}
