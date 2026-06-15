<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Bulletin de paie mensuel d'un enseignant (table esbtp_salaires).
 *
 * Workflow OHADA (séparation des devoirs) : brouillon → valide → paye (annule).
 * Net = salaire_base (Σ gains heures) + primes − retenues (Σ retenues, dont
 * impôt ITS + CNPS). Le détail ligne à ligne vit dans esbtp_salaire_details.
 *
 * @see App\Domain\Comptabilite\Paie\PayrollComputationService
 */
class ESBTPSalaire extends Model implements Auditable
{
    use SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $table = 'esbtp_salaires';

    public const ST_BROUILLON = 'brouillon';
    public const ST_VALIDE    = 'valide';
    public const ST_PAYE      = 'paye';
    public const ST_ANNULE    = 'annule';

    protected $auditInclude = [
        'user_id', 'teacher_id', 'mois', 'annee', 'salaire_base', 'heures_total',
        'primes', 'retenues', 'impot_its', 'cnps', 'net_a_payer',
        'workflow_status', 'statut', 'prepared_by', 'validateur_id', 'paid_by',
        'date_paiement', 'mode_paiement', 'reference_paiement',
    ];

    /**
     * Pas d'audit « retrieved » (spam à chaque chargement de liste + casse les
     * agrégats sans id). On audite uniquement les mutations significatives.
     */
    protected $auditEvents = ['created', 'updated', 'deleted'];

    protected $fillable = [
        'user_id', 'teacher_id', 'annee_universitaire_id', 'mois', 'annee',
        'period_start', 'period_end',
        'salaire_base', 'heures_total', 'heures_supplementaires',
        'primes', 'retenues', 'impot_its', 'cnps', 'net_a_payer',
        'statut', 'workflow_status',
        'date_paiement', 'mode_paiement', 'reference_paiement', 'commentaires',
        'createur_id', 'prepared_by', 'prepared_at',
        'validateur_id', 'date_validation', 'paid_by', 'paid_at',
    ];

    protected $casts = [
        'period_start'           => 'date',
        'period_end'             => 'date',
        'salaire_base'           => 'decimal:2',
        'heures_total'           => 'decimal:2',
        'heures_supplementaires' => 'decimal:2',
        'primes'                 => 'decimal:2',
        'retenues'               => 'decimal:2',
        'impot_its'              => 'decimal:2',
        'cnps'                   => 'decimal:2',
        'net_a_payer'            => 'decimal:2',
        'date_paiement'          => 'date',
        'prepared_at'            => 'datetime',
        'date_validation'        => 'datetime',
        'paid_at'                => 'datetime',
    ];

    /** Libellés FR des statuts workflow. */
    public static function statutLabels(): array
    {
        return [
            self::ST_BROUILLON => 'Brouillon',
            self::ST_VALIDE    => 'Validé',
            self::ST_PAYE      => 'Payé',
            self::ST_ANNULE    => 'Annulé',
        ];
    }

    public function statutLabel(): string
    {
        return self::statutLabels()[$this->workflow_status] ?? ucfirst((string) $this->workflow_status);
    }

    // ── Relations ───────────────────────────────────────────
    public function details()
    {
        return $this->hasMany(ESBTPSalaireDetail::class, 'salaire_id')->orderBy('ordre');
    }

    public function gains()
    {
        return $this->details()->where('categorie', ESBTPSalaireDetail::CAT_GAIN);
    }

    public function retenuesLignes()
    {
        return $this->details()->where('categorie', ESBTPSalaireDetail::CAT_RETENUE);
    }

    public function teacher()
    {
        return $this->belongsTo(ESBTPTeacher::class, 'teacher_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function anneeUniversitaire()
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    public function preparePar()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function validePar()
    {
        return $this->belongsTo(User::class, 'validateur_id');
    }

    public function payePar()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    // ── État ────────────────────────────────────────────────
    public function isBrouillon(): bool { return $this->workflow_status === self::ST_BROUILLON; }
    public function isValide(): bool    { return $this->workflow_status === self::ST_VALIDE; }
    public function isPaye(): bool      { return $this->workflow_status === self::ST_PAYE; }
    public function isAnnule(): bool    { return $this->workflow_status === self::ST_ANNULE; }

    /** Un bulletin payé ou annulé est verrouillé (plus modifiable). */
    public function isLocked(): bool
    {
        return in_array($this->workflow_status, [self::ST_PAYE, self::ST_ANNULE], true);
    }
}
