<?php

namespace App\Models\ESBTP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        return $this->belongsTo(Student::class);
    }

    public function category()
    {
        return $this->belongsTo(\App\Models\ESBTP\PaymentCategory::class, 'category_id');
    }

    public function inscription()
    {
        return $this->belongsTo(\App\Models\ESBTP\ESBTPInscription::class, 'inscription_id');
    }

    public function fee()
    {
        return $this->belongsTo(\App\Models\ESBTP\Fee::class, 'fee_id');
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
}
