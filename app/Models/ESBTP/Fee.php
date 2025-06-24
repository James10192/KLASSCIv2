<?php

namespace App\Models\ESBTP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;

class Fee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'fee_category_id',
        'class_id',
        'academic_year_id',
        'inscription_id',
        'amount',
        'description',
        'due_date',
        'payment_schedule',
        'installments_allowed',
        'min_installment_amount',
        'late_fee',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'min_installment_amount' => 'decimal:2',
        'late_fee' => 'decimal:2',
        'installments_allowed' => 'boolean',
    ];

    public function class()
    {
        return $this->belongsTo(ESBTPClasse::class, 'class_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'academic_year_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function inscriptions()
    {
        return $this->hasMany(ESBTPInscription::class, 'fee_id');
    }

    public function category()
    {
        return $this->belongsTo(FeeCategory::class, 'fee_category_id');
    }

    public function inscription()
    {
        return $this->belongsTo(ESBTPInscription::class, 'inscription_id');
    }

    // Scopes pour le dashboard
    public function scopeDue($query)
    {
        return $query->where('status', 'pending')->whereDate('due_date', '>=', now());
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')->whereDate('due_date', '<', now());
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePartiallyPaid($query)
    {
        return $query->where('status', 'partially_paid');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Agrégations globales
    public static function totalDue()
    {
        return static::pending()->sum('amount');
    }

    public static function totalPaid()
    {
        return static::paid()->sum('amount');
    }

    public static function totalOverdue()
    {
        return static::overdue()->sum('amount');
    }

    // Montant payé pour une échéance donnée
    public function totalPaidAmount()
    {
        return $this->payments()->where('status', 'completed')->sum('amount');
    }

    // Méthodes utilitaires
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 0, ',', ' ') . ' FCFA';
    }

    public function getStatusLabelAttribute()
    {
        $statuses = [
            'pending' => 'En attente',
            'paid' => 'Payé',
            'partially_paid' => 'Partiellement payé',
            'cancelled' => 'Annulé',
        ];

        return $statuses[$this->status] ?? 'Inconnu';
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'pending' => 'warning',
            'paid' => 'success',
            'partially_paid' => 'info',
            'cancelled' => 'danger',
        ];

        return $colors[$this->status] ?? 'secondary';
    }
}
