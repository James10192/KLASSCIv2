<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Candidature deposee par un NOUVEL eleve depuis le portail public.
 *
 * Inerte, comme une demande de reinscription : elle n'ouvre aucun droit, ne
 * genere aucun frais, ne compte dans aucun effectif. C'est l'ecole qui la
 * convertit en etudiant puis en inscription. Cette separation est ce qui rend
 * le canal public acceptable.
 */
class ESBTPCandidature extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'esbtp_candidatures';

    public const STATUT_EN_ATTENTE = 'en_attente';

    public const STATUT_ACCEPTEE = 'acceptee';

    public const STATUT_REJETEE = 'rejetee';

    public const STATUT_CONVERTIE = 'convertie';

    protected $fillable = [
        'nom', 'prenoms', 'date_naissance', 'sexe',
        'telephone', 'email',
        'filiere_id', 'niveau_id', 'voeu_libre',
        'annee_universitaire_id',
        'serie_bac', 'etablissement_origine', 'annee_bac',
        'message', 'statut', 'consentement_at', 'ip_hash',
        'motif_rejet', 'traite_par', 'traite_at',
        'etudiant_id', 'inscription_id',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'consentement_at' => 'datetime',
        'traite_at' => 'datetime',
        'annee_bac' => 'integer',
    ];

    /**
     * `consentement_at` est audite : le rouvrir le reecrit, et la preuve du
     * consentement initial — que la loi ivoirienne 2013-450 impose de pouvoir
     * produire — disparaitrait sans trace. L'empreinte d'adresse reste dehors :
     * la conserver en plusieurs exemplaires irait contre la minimisation.
     */
    protected $auditInclude = [
        'statut', 'filiere_id', 'niveau_id', 'motif_rejet',
        'traite_par', 'traite_at', 'etudiant_id', 'inscription_id',
        'consentement_at',
    ];

    public function anneeUniversitaire(): BelongsTo
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    public function filiere(): BelongsTo
    {
        return $this->belongsTo(ESBTPFiliere::class, 'filiere_id');
    }

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(ESBTPNiveauEtude::class, 'niveau_id');
    }

    public function traitePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traite_par');
    }

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    public function scopeEnAttente(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_EN_ATTENTE);
    }

    public function estTraitable(): bool
    {
        return $this->statut === self::STATUT_EN_ATTENTE;
    }

    public function nomComplet(): string
    {
        return trim($this->nom.' '.$this->prenoms);
    }

    /** Le voeu tel qu'il doit s'afficher : la filiere choisie, sinon le texte libre. */
    public function voeu(): string
    {
        $depuisListe = trim(($this->filiere?->name ?? '').' '.($this->niveau?->name ?? ''));

        return $depuisListe !== '' ? $depuisListe : (string) ($this->voeu_libre ?? '');
    }
}
