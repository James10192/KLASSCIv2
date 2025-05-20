<?php

namespace App\Models\ESBTP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ESBTPAnneeUniversitaire;

class FeeCategoryRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'fee_category_id',
        'filiere_id',
        'niveau_id',
        'annee_universitaire_id',
        'amount',
        'payment_schedule',
        'installments_allowed',
        'min_installment_amount',
        'late_fee',
    ];

    public function category()
    {
        return $this->belongsTo(FeeCategory::class, 'fee_category_id');
    }

    public function filiere()
    {
        return $this->belongsTo(ESBTPFiliere::class, 'filiere_id');
    }

    public function niveau()
    {
        return $this->belongsTo(ESBTPNiveauEtude::class, 'niveau_id');
    }

    public function anneeUniversitaire()
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    public function installments()
    {
        return $this->hasMany(FeeCategoryRuleInstallment::class, 'fee_category_rule_id');
    }

    /**
     * Retourne la règle applicable pour une filière/niveau/année donnée.
     * Si une règle existe pour l'année universitaire courante, elle est prioritaire.
     * Sinon, on prend la règle par défaut (sans année).
     */
    public static function getApplicableRule($feeCategoryId, $filiereId, $niveauId, $anneeUniversitaireId = null)
    {
        $query = static::where('fee_category_id', $feeCategoryId)
            ->where('filiere_id', $filiereId)
            ->where('niveau_id', $niveauId);
        if ($anneeUniversitaireId) {
            $rule = (clone $query)->where('annee_universitaire_id', $anneeUniversitaireId)->first();
            if ($rule) return $rule;
        }
        // Fallback: règle sans année (récurrente)
        return $query->whereNull('annee_universitaire_id')->first();
    }
}
