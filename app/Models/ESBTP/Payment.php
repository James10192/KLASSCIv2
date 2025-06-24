<?php

namespace App\Models\ESBTP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'inscription_id',
        'fee_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference_number',
        'description',
        'category_id',
        'status',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function student()
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'student_id');
    }

    public function category()
    {
        return $this->belongsTo(PaymentCategory::class, 'category_id');
    }

    public function inscription()
    {
        return $this->belongsTo(ESBTPInscription::class, 'inscription_id');
    }

    public function fee()
    {
        return $this->belongsTo(Fee::class, 'fee_id');
    }

    // Scopes pour le dashboard
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Agrégations globales
    public static function totalReceived()
    {
        return static::completed()->sum('amount');
    }

    public static function totalPending()
    {
        return static::pending()->sum('amount');
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
            'completed' => 'Complété',
            'failed' => 'Échoué',
            'refunded' => 'Remboursé',
        ];

        return $statuses[$this->status] ?? 'Inconnu';
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'pending' => 'warning',
            'completed' => 'success',
            'failed' => 'danger',
            'refunded' => 'info',
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    public function getPaymentMethodLabelAttribute()
    {
        $methods = [
            'cash' => 'Espèces',
            'bank_transfer' => 'Virement bancaire',
            'check' => 'Chèque',
            'mobile_money' => 'Mobile Money',
        ];

        return $methods[$this->payment_method] ?? $this->payment_method;
    }
}
