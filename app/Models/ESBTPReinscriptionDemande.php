<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Demande de reinscription deposee par un etudiant depuis le portail public.
 *
 * Une demande est inerte : elle n'ouvre aucun droit, ne genere aucun frais et
 * ne compte dans aucun effectif. C'est la scolarite qui la convertit, et la
 * conversion passe par ReeinscriptionService::effectuerReinscription — le flux
 * canonique. C'est cette separation qui rend le canal public acceptable.
 */
class ESBTPReinscriptionDemande extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'esbtp_reinscription_demandes';

    public const STATUT_EN_ATTENTE = 'en_attente';

    public const STATUT_REJETEE = 'rejetee';

    public const STATUT_CONVERTIE = 'convertie';

    /**
     * Cle du compteur affiche en badge dans la barre laterale. Partagee entre
     * qui le calcule (AppServiceProvider) et qui l'invalide (la corbeille) :
     * deux litteraux divergeraient en silence, et le badge mentirait.
     */
    public const CLE_CACHE_EN_ATTENTE = 'reinscriptions.demandes.en_attente';

    /** Statuts admis, dans l'ordre du cycle de vie d'une demande. */
    public const STATUTS = [
        self::STATUT_EN_ATTENTE,
        self::STATUT_REJETEE,
        self::STATUT_CONVERTIE,
    ];

    /**
     * Les evenements reellement audites : les MUTATIONS, pas les lectures.
     *
     * `config/audit.php` active aussi `retrieved`, ce qui fait ecrire une ligne
     * dans `audits` a chaque fois qu'un modele est LU. Vingt et un modeles s'en
     * protegent deja par cette meme propriete ; ceux-ci ne le faisaient pas.
     *
     * Le cout n'etait pas theorique : c'est par ce canal que la table `audits` a
     * enfle au point que la page qui la consulte ne repondait plus.
     *
     * Ce qui reste trace : creation, modification, suppression, restauration.
     * La conservation OHADA porte sur les mutations, pas sur les consultations.
     */
    protected $auditEvents = ['created', 'updated', 'deleted', 'restored'];
    protected $fillable = [
        'etudiant_id',
        'annee_universitaire_id',
        'classe_souhaitee_id',
        'statut',
        'consentement_at',
        'ip_hash',
        'motif_rejet',
        'traite_par',
        'traite_at',
        'inscription_id',
        'reference_publique',
        'rdv_invite_at',
    ];

    protected $casts = [
        'consentement_at' => 'datetime',
        'traite_at' => 'datetime',
        'rdv_invite_at' => 'datetime',
    ];

    /**
     * Journal d'audit restreint a ce qui engage : le statut et son traitement.
     * L'empreinte d'adresse n'y figure pas, elle sert au controle d'abus, pas
     * a la tracabilite administrative.
     */
    protected $auditInclude = [
        'statut',
        'classe_souhaitee_id',
        'motif_rejet',
        'traite_par',
        'traite_at',
        'inscription_id',
        // Rouvrir une demande traitee reecrit l'horodatage du consentement.
        // Sans cette ligne, la preuve du consentement initial — que la loi
        // ivoirienne 2013-450 impose de pouvoir produire — serait detruite
        // sans laisser de trace. L'empreinte d'adresse, elle, reste hors audit :
        // la conserver en plusieurs exemplaires irait contre la minimisation.
        'consentement_at',
    ];

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(ESBTPRdvReservation::class, 'reinscription_demande_id');
    }

    public function anneeUniversitaire(): BelongsTo
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    public function classeSouhaitee(): BelongsTo
    {
        return $this->belongsTo(ESBTPClasse::class, 'classe_souhaitee_id');
    }

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(ESBTPInscription::class, 'inscription_id');
    }

    public function traitePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traite_par');
    }

    public function scopeEnAttente(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_EN_ATTENTE);
    }

    /**
     * Une demande n'est traitable qu'une fois. Rejouer une conversion
     * creerait une seconde inscription, donc une seconde facturation.
     */
    public function estTraitable(): bool
    {
        return $this->statut === self::STATUT_EN_ATTENTE;
    }

    public function libelleStatut(): string
    {
        return match ($this->statut) {
            self::STATUT_EN_ATTENTE => 'En attente',
            self::STATUT_REJETEE => 'Rejetée',
            self::STATUT_CONVERTIE => 'Réinscrit',
            default => $this->statut,
        };
    }
}
