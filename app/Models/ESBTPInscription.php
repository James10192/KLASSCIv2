<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPInscription extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_inscriptions';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'etudiant_id',
        'annee_universitaire_id',
        'filiere_id',
        'niveau_id',
        'classe_id',
        'date_inscription',
        'type_inscription', // Première inscription, réinscription, etc.
        'status', // active, annulée, etc.
        'montant_scolarite',
        'frais_inscription',
        'numero_recu',
        'date_paiement',
        'mode_paiement',
        'observations',
        'documents_fournis', // JSON avec liste des documents
        'date_validation',
        'validated_by',
        'created_by',
        'updated_by'
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array
     */
    protected $casts = [
        'date_inscription' => 'date',
        'date_paiement' => 'date',
        'date_validation' => 'date',
        'documents_fournis' => 'array',
        'montant_scolarite' => 'float',
        'frais_inscription' => 'float',
    ];

    /**
     * Relation avec l'étudiant.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function etudiant()
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    /**
     * Relation avec l'année universitaire.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function anneeUniversitaire()
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    /**
     * Relation avec la filière.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function filiere()
    {
        return $this->belongsTo(ESBTPFiliere::class, 'filiere_id');
    }

    /**
     * Relation avec le niveau d'étude.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function niveau()
    {
        return $this->belongsTo(ESBTPNiveauEtude::class, 'niveau_id');
    }

    /**
     * Relation avec la classe.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function classe()
    {
        return $this->belongsTo(ESBTPClasse::class, 'classe_id');
    }

    /**
     * Relation avec les paiements de scolarité.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function paiements()
    {
        return $this->hasMany(ESBTPPaiement::class, 'inscription_id');
    }

    /**
     * Relation avec les paiements centralisés (nouveau système).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function payments()
    {
        return $this->hasMany(\App\Models\ESBTP\Payment::class, 'inscription_id');
    }

    /**
     * Utilisateur qui a validé l'inscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Utilisateur qui a créé l'entrée.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Utilisateur qui a mis à jour l'entrée.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Obtenir le montant total payé pour cette inscription.
     *
     * @return float
     */
    public function getMontantPayeAttribute()
    {
        return $this->paiements()->where('status', 'validé')->sum('montant');
    }

    /**
     * Obtenir le solde restant à payer.
     *
     * @return float
     */
    public function getSoldeRestantAttribute()
    {
        return $this->montant_scolarite - $this->montant_paye;
    }

    /**
     * Vérifier si l'inscription est entièrement payée.
     *
     * @return bool
     */
    public function getEstPayeeAttribute()
    {
        return $this->solde_restant <= 0;
    }

    /**
     * Obtenir le pourcentage payé de la scolarité.
     *
     * @return int
     */
    public function getPourcentagePayeAttribute()
    {
        if ($this->montant_scolarite <= 0) {
            return 100;
        }

        return min(100, round(($this->montant_paye / $this->montant_scolarite) * 100));
    }

    /**
     * Scope pour filtrer les inscriptions actives.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActives($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope pour filtrer les inscriptions par année universitaire.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $anneeId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeParAnnee($query, $anneeId)
    {
        return $query->where('annee_universitaire_id', $anneeId);
    }

    /**
     * Scope pour filtrer les inscriptions de l'année en cours.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAnneeEnCours($query)
    {
        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        if (!$anneeEnCours) {
            return $query->whereRaw('1=0'); // Retourne une requête vide si aucune année en cours
        }

        return $query->where('annee_universitaire_id', $anneeEnCours->id);
    }

    /**
     * Vérifie si l'inscription est pour l'année en cours.
     *
     * @return bool
     */
    public function getEstPourAnneeEnCoursAttribute()
    {
        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        if (!$anneeEnCours) {
            return false;
        }

        return $this->annee_universitaire_id === $anneeEnCours->id;
    }

    /**
     * Alias pour la relation avec le niveau d'étude.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function niveauEtude()
    {
        return $this->niveau();
    }
}
